<?php
/**
 * Created by PhpStorm.
 * User: Steve
 * Date: 06.08.2016
 * Time: 18:35
 */

namespace best\kosice;

use best\kosice\datalib\best_kosice_data;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Database {
    /**
     * Database version upgrading with the least destructive effect.
     * Usage:
     * When increasing the DB version, increase the constant variable and add a new switch case for the version.
     * Database of all users will go through the required steps based on their currently installed version.
     *
     * @return void
     */
    public static function upgrade_database() {
        //Plugin version of the database
        $target_version_db = 1;

        $log = function ( $attempted_request ) {
            Database::log_success( 'automatic', 'meta', 'Database upgrade', $attempted_request );
        };

        //If current version is already higher, it simply gets lowered
        $current_version_db = get_option( 'version_db', 0 );
        if ( $current_version_db > $target_version_db ) {
            update_option( 'version_db', $target_version_db );
        } else {
            //Upgrades the database towards the currently installed plugin version
            while ( $current_version_db < $target_version_db ) {
                switch ( $current_version_db ) {
                    case 0:
                        //default: //Uncomment when testing
                        //If the database is being upgraded from an unknown version, drops and recreates everything
                        Database::drop_all_tables();
                        Database::create_all_tables();
                        $log( 'Upgrading version from 0 to 1' );
                        break;
                }
                update_option( 'version_db', ++ $current_version_db );
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
     */
    public static function create_all_tables() {
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
        $request_type = 'automatic';
        $operation    = 'Table creation';

        if ( $wpdb->query( $sql_history ) ) {
            Database::log_success( $request_type, 'meta', $operation, $wpdb->last_query );
        } else {
            Database::log_error( $request_type, 'meta', $operation, $wpdb->last_query, $wpdb->last_error );
        }

        if ( $wpdb->query( $sql_events ) ) {
            Database::log_success( $request_type, 'events_db', $operation, $wpdb->last_query );
        } else {
            Database::log_error( $request_type, 'events_db', $operation, $wpdb->last_query, $wpdb->last_error );
        }

        if ( $wpdb->query( $sql_lbgs ) ) {
            Database::log_success( $request_type, 'lbgs_db', $operation, $wpdb->last_query );
        } else {
            Database::log_error( $request_type, 'lbgs_db', $operation, $wpdb->last_query, $wpdb->last_error );
        }
    }

    /**
     * Deletes all plugin tables from the database.
     */
    public static function drop_all_tables() {
        //Global instance of the WordPress Database
        global $wpdb;

        $wpdb->query( 'DROP TABLE IF EXISTS '
                      . $wpdb->prefix . 'best_events'
                      . ', '
                      . $wpdb->prefix . 'best_lbg'
                      . ', '
                      . $wpdb->prefix . 'best_history'
        );
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
        $table_name = esc_sql( $wpdb->prefix . 'best_history' );

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
        $table_name = esc_sql( $wpdb->prefix . 'best_history' );

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
            Database::log_error( $request_type, 'meta', 'Logging success', $wpdb->last_query, $wpdb->last_error );
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
     * @param $insert callable {@param $table_name string @return string insert query} returns the insert query to be run
     *
     * @return bool true on success, false on failure
     */
    public static function replace_db_table( $table_name, $request_type, $target, $operation, callable $insert ) {
        global $wpdb;
        $table_name = esc_sql( $wpdb->prefix . $table_name );

        //Runs the callback for insert query
        $insert_query = $insert( $table_name );
        if ( $insert_query == null ) {
            Database::log_error( $request_type, $target, $operation, 'Requested insert query', 'Returned null' );

            return false;
        }

        //If the DB supports it, we will offer transaction rollback on error
        $wpdb->query( 'START TRANSACTION' );

        //Deletes all old entries
        $wpdb->query( "TRUNCATE TABLE $table_name" );

        //Running the query and problem handling
        if ( ! $wpdb->query( $insert_query ) ) {
            Database::log_error( $request_type, $target, $operation, $wpdb->last_query, $wpdb->last_error );
            $wpdb->query( 'ROLLBACK' );

            return false;
        }

        //All went OK
        $last_query = $wpdb->last_query;
        $wpdb->query( 'COMMIT' );
        Database::log_success( $request_type, $target, $operation, $last_query );

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

        //Fake data for offline testing
        //$courses = [ ['event', 'login', 'place', 'dates', 'event type', 'acad', 'fee'] ];

        //Used logger values
        $target    = 'events_db';
        $operation = 'Refreshing table using parser';

        if ( $courses ) {
            //Replaces the table by new insert data based on the callback using function which logs the result
            return Database::replace_db_table( 'best_events', $request_type, $target, $operation, function ( $table_name ) use ( $courses ) {
                if ( $table_name == null ) {
                    return null;
                }

                $insert_query = "INSERT INTO $table_name (event_name, login_url, place, dates, event_type, acad_compl, fee) VALUES ";

                //Inserts new entries
                foreach ( $courses as $course ) {
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
            Database::log_error( $request_type, $target, $operation
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

        //Fake data for offline testing
        //$lbgs = [ [ 'web', 'city', 'state' ] ];

        //Used logger values
        $target    = 'lbgs_db';
        $operation = 'Refreshing table using parser';

        if ( $lbgs ) {
            //Replaces the table by new insert data based on the callback using function which logs the result
            return Database::replace_db_table( 'best_lbg', $request_type, $target, $operation, function ( $table_name ) use ( $lbgs ) {
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
            Database::log_error( $request_type, $target, $operation
                , 'Requesting courses from parser'
                , 'Returned no data: ' . $parser->error_message()
            );

            return false;
        }
    }
}
