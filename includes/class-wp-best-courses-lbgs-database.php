<?php
/**
 * Created by PhpStorm.
 * User: Steve
 * Date: 06.08.2016
 * Time: 18:35
 */

//TODO reconsider namespace
namespace best\kosice;

use best\kosice\datalib\best_kosice_data;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Database {
    //Plugin version of the database, any change here immediately upgrades database of all plugin users
    const TARGET_PLUGIN_DB_VERSION = 1;

    //Prefix for all plugin options
    const OPTION_BASE_PREFIX = 'best_courses_lbgs_';

    //Plugin options
    const OPTION_NAME_PLUGIN_DB_VERSION = 'best_courses_lbgs_plugin_db_version';
    const OPTION_NAME_PLUGIN_DB_UPGRADE_DISABLED = 'best_courses_lbgs_plugin_db_upgrade_disabled';

    //Use these constants as table names when possible, be DRY. Add table prefix. In future, enums may be created.
    const BEST_EVENTS_TABLE = 'best_events';
    const BEST_LBGS_TABLE = 'best_lbg';
    const BEST_HISTORY_TABLE = 'best_history';

    /**
     * Database version upgrading with the least destructive effect for the end user.
     *
     * Usage:
     * When increasing the DB version, increase the class constant TARGET_PLUGIN_DB_VERSION and add a new
     * switch case for the previous version.
     *
     * Database of all users will go through the required steps based on their currently installed version.
     */
    public static function upgrade_database() {
        //Thinking about the future: if at any point we add this "feature" and then user rolls back to old version,
        //we don't want to break his entire database
        if ( get_option( self::OPTION_NAME_PLUGIN_DB_UPGRADE_DISABLED, false ) ) {
            return;
        }

        $log = function ( $attempted_request ) {
            self::log_success( 'automatic', 'meta', 'Database upgrade', $attempted_request );
        };

        //If the current version is already higher (mostly after development),
        //it simply gets lowered to prevent anomalies
        if ( get_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 ) > self::TARGET_PLUGIN_DB_VERSION ) {
            update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, self::TARGET_PLUGIN_DB_VERSION );
            $log( 'Detected too large database version, setting down to ' . self::TARGET_PLUGIN_DB_VERSION );

            return;
        }

        //Upgrades the database towards the currently installed plugin version
        while ( ( $current_db_version = get_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 ) )
                < self::TARGET_PLUGIN_DB_VERSION
        ) {
            switch ( $current_db_version ) {
                //Initialization - if the database had none or an unknown version, drops and recreates everything
                //default: //Uncomment when testing to recreate database after any version change
                case 0:
                    self::drop_all_tables();
                    self::create_all_tables();
                    self::refresh_db_best_events( 'automatic' );
                    self::refresh_db_best_lbgs( 'automatic' );
                    update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, self::TARGET_PLUGIN_DB_VERSION );
                    $log( 'Database initialization: installed version ' . self::TARGET_PLUGIN_DB_VERSION );
                    break;
            }

            //Implicitly increases version by 1 after each step, unless there was an explicit external change
            if ( $current_db_version == get_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 ) &&
                 $current_db_version < self::TARGET_PLUGIN_DB_VERSION
            ) {
                update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, ++ $current_db_version );
                $log( 'Upgraded version from ' . ( $current_db_version - 1 ) . ' to ' . $current_db_version );
            }
        }
    }

    /**
     * Creates (or upgrades) all custom tables in the database.
     * Should be run at the time of the plugin activation.
     *
     * Studying references:
     * <https://codex.wordpress.org/Function_Reference/wpdb_Class>
     * <https://codex.wordpress.org/Creating_Tables_with_Plugins>
     *
     * @param $request_type string type of the operation request, can be 'automatic' or 'manual'
     */
    public static function create_all_tables( $request_type = 'automatic' ) {
        //Global instance of the WordPress Database
        global $wpdb;

        // sql for creating tables in heredoc

        $sql_events = <<< SQL
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}best_events (
id_event int(11) NOT NULL AUTO_INCREMENT,
event_name varchar(100) NOT NULL,
place varchar(40) NOT NULL,
dates varchar(40) NOT NULL,
event_type varchar(100) NOT NULL,
acad_compl varchar(20) DEFAULT NULL,
fee varchar(10) NOT NULL,
app_deadline varchar(30) DEFAULT NULL,
login_url varchar(1000) DEFAULT NULL,
PRIMARY KEY (id_event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL;

        //TODO rename lbg to lbgs for consistency (but I don't want to ruin current dbs of developers, maybe some patch?)
        $sql_lbgs = <<< SQL
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}best_lbg (
id_lbg int(11) NOT NULL AUTO_INCREMENT,
city varchar(50) NOT NULL,
state varchar(50) NOT NULL,
web_page varchar(200) DEFAULT NULL,
PRIMARY KEY (id_lbg)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL;

        //TODO possibly decide on a better name (and purpose) for this table
        $sql_history = <<< SQL
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}best_history (
id_history int(11) NOT NULL AUTO_INCREMENT,
time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
request_type varchar(50) NOT NULL CHECK(request_type IN('automatic', 'manual', 'unknown')),
target varchar(50) NOT NULL CHECK(target IN('events_db', 'lbgs_db', 'meta')),
operation varchar(50) NOT NULL,
attempted_request text DEFAULT NULL,
error_message text DEFAULT NULL,
PRIMARY KEY (id_history)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL;

        //Querying and error handling
        $operation = 'Table creation';

        if ( $wpdb->query( $sql_history ) ) {
            self::log_success( $request_type, 'meta', $operation, $wpdb->last_query );
        } else {
            self::log_error( $request_type, 'meta', $operation, $wpdb->last_query, $wpdb->last_error );
        }

        if ( $wpdb->query( $sql_events ) ) {
            self::log_success( $request_type, 'events_db', $operation, $wpdb->last_query );
        } else {
            self::log_error( $request_type, 'events_db', $operation, $wpdb->last_query, $wpdb->last_error );
        }

        if ( $wpdb->query( $sql_lbgs ) ) {
            self::log_success( $request_type, 'lbgs_db', $operation, $wpdb->last_query );
        } else {
            self::log_error( $request_type, 'lbgs_db', $operation, $wpdb->last_query, $wpdb->last_error );
        }
    }

    /**
     * Deletes all plugin tables from the database.
     *
     * @param $request_type string type of the operation request, can be 'automatic' or 'manual'
     */
    public static function drop_all_tables( $request_type = 'automatic' ) {
        //Global instance of the WordPress Database
        global $wpdb;

        if ( $wpdb->query( 'DROP TABLE IF EXISTS '
                           . $wpdb->prefix . self::BEST_EVENTS_TABLE
                           . ', '
                           . $wpdb->prefix . self::BEST_LBGS_TABLE
                           . ', '
                           . $wpdb->prefix . self::BEST_HISTORY_TABLE
        )
        ) {
            //Removes the stored plugin version setting, next time the DB has to be re-initialized
            update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 );
        } else {
            self::log_error( $request_type, 'meta', 'Dropping tables', $wpdb->last_query, $wpdb->last_error );
        }
    }

    /**
     * Logs an error into the database in order to be displayed to the administrator.
     * (If it becomes useful, it may even get its own class with enum like $target...)
     * @see wp_best_courses_lbgs_log_success
     *
     * @param $request_type string type of the operation request, can be 'automatic' or 'manual'
     * @param $target string the event where the error occurred, can be 'events_db', 'lbgs_db' or 'meta'
     * @param $operation string description of the action that is being performed
     * @param $attempted_request string request that has caused the error
     * @param $error_message string explanation of the problem that happened
     *
     * @return int|false false on logging error
     */
    public static function log_error( $request_type, $target, $operation, $attempted_request, $error_message = null ) {
        //Error is defined by error_message not being null
        if ( $error_message == null ) {
            $error_message = '(No error message)';
            //TODO check whether === null has the same effect, because this condition will be used when reading from DB to distinguish errors
        }

        //If the request type is explicitly unknown, we use the relevant column value
        if ( $request_type == null ) {
            $request_type = 'unknown';
        }

        global $wpdb;
        $table_name = esc_sql( $wpdb->prefix . Database::BEST_HISTORY_TABLE );

        return $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name"
                . "(request_type, target, operation, attempted_request, error_message) "
                . "VALUES (%s, %s, %s, %s, %s)"
                , $request_type
                , $target
                , $operation
                , $attempted_request
                , $error_message
            )
        );
    }

    /**
     * Logs a successful operation into the database in order to be displayed to the administrator.
     * (If it becomes useful, it may even get its own class with enum like $target...)
     * @see wp_best_courses_lbgs_log_error
     *
     * @param $request_type string type of the operation request, can be 'automatic' or 'manual'
     * @param $target string the event where the operation was performed, can be 'events_db', 'lbgs_db' or 'meta'
     * @param $operation string description of the action that is being performed
     * @param $attempted_request string the successfully executed request
     *
     * @return int|false false on logging error, which is also logged (if possible)
     */
    public static function log_success( $request_type, $target, $operation, $attempted_request ) {
        //If the request type is explicitly unknown, we use the relevant column value
        if ( $request_type == null ) {
            $request_type = 'unknown';
        }

        global $wpdb;
        $table_name = esc_sql( $wpdb->prefix . Database::BEST_HISTORY_TABLE );

        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name"
                . "(request_type, target, operation, attempted_request) "
                . "VALUES (%s, %s, %s, %s)"
                , $request_type
                , $target
                , $operation
                , $attempted_request
            )
        );

        //Logging the error
        if ( $result === false ) {
            self::log_error( $request_type, 'meta', 'Logging success', $wpdb->last_query, $wpdb->last_error );
            //Error during logging the error of logging success will not be logged or returned. Is this too meta?
        }

        return $result;
    }

    /**
     * Replaces table's contents in the database by new content, taking care of anomalies that can happen and logging them.
     *
     * @param $table_name string name of the table to be replaced
     * @param $request_type string type of the operation request, can be 'automatic' or 'manual'
     * @param $target string the event where the operation was performed, can be 'events_db', 'lbgs_db' or 'meta'
     * @param $operation string description of the action that is being performed
     * @param $insert callable {@param $table_name string @return string insert query}
     *        returns the sql-safe insert query to be run
     *
     * @return bool true on success, false on failure
     */
    public static function replace_db_table( $table_name, $request_type, $target, $operation, callable $insert ) {
        global $wpdb;
        $table_name = esc_sql( "{$wpdb->prefix}$table_name" );

        //Runs the callback for insert query
        $insert_query = $insert( $table_name );
        if ( $insert_query == null ) {
            self::log_error( $request_type, $target, $operation, 'Requested insert query', 'Returned no data' );

            return false;
        }

        //If the DB supports it, we will offer transaction rollback on error
        $wpdb->query( 'START TRANSACTION' );

        //Deletes all old entries
        $wpdb->query( "TRUNCATE TABLE $table_name" );

        //Running the query and problem handling
        if ( ! $wpdb->query( $insert_query ) ) {
            self::log_error( $request_type, $target, $operation, $wpdb->last_query, $wpdb->last_error );
            $wpdb->query( 'ROLLBACK' );

            return false;
        }

        //All went OK
        $last_query = $wpdb->last_query;
        $wpdb->query( 'COMMIT' );
        self::log_success( $request_type, $target, $operation, $last_query );

        return true;
    }

    /**
     * Refreshes the state of the BEST Events database table using parser data.
     *
     * @param $request_type string type of the operation request, can be 'automatic' or 'manual'
     *
     * @return bool true on success, false on failure
     */
    public static function refresh_db_best_events( $request_type ) {
        //Reads all courses from the remote db
        $parser  = best_kosice_data::instance();
        $courses = $parser->courses();

        //Used logger values
        $target    = 'events_db';
        $operation = 'Refreshing table using parser';

        if ( $courses['learning']['data'] ) {
            //Replaces the table by new insert data based on the callback using function which logs the result
            return self::replace_db_table( 'best_events', $request_type, $target, $operation,
                function ( $table_name ) use ( $courses ) {
                    if ( $table_name == null ) {
                        return null;
                    }

                    $insert_query =
                        "INSERT INTO $table_name (event_name, login_url, place, dates, event_type, acad_compl, fee) VALUES ";

                    //Inserts new entries
                    foreach ( $courses['learning']['data'] as $course ) {
                        //Next row
                        $insert_query .= '(' .
                                         //event_name
                                         "'" . esc_sql( $course[0] ) . "'," .
                                         //login_url
                                         "'" . esc_sql( $course[1] ) . "'," .
                                         //place
                                         "'" . esc_sql( $course[2] ) . "'," .
                                         //dates
                                         "'" . esc_sql( $course[3] ) . "'," .
                                         //event_type
                                         "'" . esc_sql( $course[4] ) . "'," .
                                         //acad_compl
                                         "'" . esc_sql( $course[5] ) . "'," .
                                         //fee
                                         "'" . esc_sql( $course[6] ) . "'" .
                                         '),';
                    }

                    return rtrim( $insert_query, ',' );
                } );
        } else {
            self::log_error( $request_type, $target, $operation
                , 'Requesting courses from parser'
                , 'Returned no data: ' . $parser->error_message()
            );

            return false;
        }
    }

    /**
     * Refreshes the state of the BEST Local Best Groups database table using parser data.
     *
     * @param $request_type string type of the operation request, can be 'automatic' or 'manual'
     *
     * @return bool true on success, false on failure
     */
    public static function refresh_db_best_lbgs( $request_type ) {
        //Reads all local best groups from the remote db
        $parser = best_kosice_data::instance();
        $lbgs   = $parser->lbgs();

        //Used logger values
        $target    = 'lbgs_db';
        $operation = 'Refreshing table using parser';

        if ( $lbgs ) {
            //Replaces the table by new insert data based on the callback using function which logs the result
            return self::replace_db_table( 'best_lbg', $request_type, $target, $operation,
                function ( $table_name ) use ( $lbgs ) {
                    if ( $table_name == null ) {
                        return null;
                    }

                    $insert_query = "INSERT INTO $table_name (web_page, city, state) VALUES ";

                    //Inserts new entries
                    foreach ( $lbgs as $lbg ) {
                        //Next row
                        $insert_query .= '(' .
                                         //web_page
                                         "'" . esc_sql( $lbg[0] ) . "'," .
                                         //city
                                         "'" . esc_sql( $lbg[2] ) . "'," .
                                         //state
                                         "'" . esc_sql( $lbg[1] ) . "'" .
                                         '),';
                    }

                    return rtrim( $insert_query, ',' );
                } );
        } else {
            self::log_error( $request_type, $target, $operation
                , 'Requesting courses from parser'
                , 'Returned no data: ' . $parser->error_message()
            );

            return false;
        }
    }

    /**
     * Counts the number of rows of a table in the database.
     *
     * @param $table_name_no_prefix string table name without prefix
     *
     * @return null|string number of rows, null on error
     */
    public static function count_db_table_rows( $table_name_no_prefix ) {
        global $wpdb;
        $table_name = esc_sql( "{$wpdb->prefix}$table_name_no_prefix" );

        return $wpdb->get_var(
            "SELECT count(*) FROM $table_name"
        );
    }
}
