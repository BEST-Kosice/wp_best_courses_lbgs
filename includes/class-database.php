<?php
/**
 * Created by PhpStorm.
 * User: scscgit
 * Date: 06.08.2016
 */

namespace best\kosice\best_courses_lbgs;

use best\kosice\datalib\best_kosice_data;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enum for request_type field of Database::log_success() and Database::log_error().
 * <p>Type of the operation request.
 *
 * @package best\kosice\best_courses_lbgs
 * @see     Database::log_success(), Database::log_error()
 */
abstract class LogRequestType {
    const MANUAL = 'manual';
    const AUTOMATIC = 'automatic';
}

/**
 * Enum for target field of Database::log_success() and Database::log_error().
 * <p>The event where the operation was performed or where the error has occurred.
 *
 * @package best\kosice\best_courses_lbgs
 * @see     Database::log_success(), Database::log_error()
 */
abstract class LogTarget {
    const EVENTS = 'events_db';
    const LBGS = 'lbgs_db';
    const META = 'meta';
    const TRANSLATION = 'translation';
}

/**
 * Enum for names of all tables (without prefix) that will be used for methods within this class.
 * <p>Use with global $wpdb->prefix as a prefix to make a full database table name for query purposes.
 *
 * @package best\kosice\best_courses_lbgs
 */
abstract class TableName {
    //TODO possibly decide on a better name (and purpose) for this table (it is history of plugin-specific events)
    const HISTORY = 'best_history';
    const EVENTS = 'best_events';
    //TODO rename lbg to lbgs for consistency (but I don't want to ruin current dbs of developers, maybe some patch?)
    const LBGS = 'best_lbg';
    //NOTE: ?? is a placeholder value for a specific language code
    const LBG_TRANSLATIONS = 'best_lbg_??';
}

// TODO: reconsider this, globals can conflict with other plugins and wordpress core itself; static/const is class local

define( 'HISTORY_DDL', "CREATE TABLE IF NOT EXISTS ?? (
id_history int(11) NOT NULL AUTO_INCREMENT,
time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
request_type varchar(50) NOT NULL CHECK(request_type IN
( '" . LogRequestType::AUTOMATIC . "', '" . LogRequestType::MANUAL . "', 'unknown' )),
target varchar(50) NOT NULL CHECK(target IN
( '" . LogTarget::EVENTS . "', '" . LogTarget::LBGS . "', '" . LogTarget::META . "', '" . LogTarget::TRANSLATION . "' )),
operation varchar(50) NOT NULL,
attempted_request text DEFAULT NULL,
error_message text DEFAULT NULL,
PRIMARY KEY (id_history)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );

define( 'EVENTS_DDL', "CREATE TABLE IF NOT EXISTS ?? (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );

define( 'LBGS_DDL', "CREATE TABLE IF NOT EXISTS ?? (
id_lbg int(11) NOT NULL AUTO_INCREMENT,
city varchar(50) NOT NULL,
state varchar(50) NOT NULL,
web_page varchar(200) DEFAULT NULL,
PRIMARY KEY (id_lbg)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );

define( 'LBGS_TRANSLATION_DDL', "CREATE TABLE IF NOT EXISTS ?? (
lbg_id char(2),
name varchar(50),
PRIMARY KEY (lbg_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;" );

/**
 * Static class for Database operations within the plugin.
 *
 * <p>TODO tasks:
 * Option for administrator to delete old history (either by defining max rows, or manually).
 * Encapsulate all direct database operations using some common interface - example: DAO pattern.
 * Change some functions to private when encapsulated enough.
 *
 * @package best\kosice\best_courses_lbgs
 * @author  scscgit
 */
class Database {

    // Plugin version of the database, any change here immediately upgrades database of all plugin users
    const TARGET_PLUGIN_DB_VERSION = 2;

    // Prefix for all plugin options
    const OPTION_BASE_PREFIX = 'best_courses_lbgs_';

    // Plugin option names with prefix
    const OPTION_NAME_PLUGIN_DB_VERSION = 'best_courses_lbgs_plugin_db_version';
    const OPTION_NAME_PLUGIN_DB_UPGRADE_DISABLED = 'best_courses_lbgs_plugin_db_upgrade_disabled';

    // Prefix for translation tables
    const BEST_LBGS_TRANSLATION_TABLE_PREFIX = 'best_lbg_';

    // TODO: move them out (e.g. here) from a global scope
    const HISTORY_DDL_STATEMENT = HISTORY_DDL;

    const EVENTS_DDL_STATEMENT = EVENTS_DDL;

    const LBGS_DDL_STATEMENT = LBGS_DDL;

    const LBGS_TRANSLATION_DDL_STATEMENT = LBGS_TRANSLATION_DDL;

    // Available translations with an existing corresponding lbgs_??.xml file
    static $LANG_CODES = array( "sk" );
    static $TRANSLATION_XML_FILENAME = 'lbgs_??';

    /**
     * Database version upgrading with the least destructive effect for the end user.
     * <p>When this is the first time running the upgrade, initializes (installs) the database to the latest version.
     *
     * <p>Usage:
     * <p>When increasing the DB version, increase the class constant TARGET_PLUGIN_DB_VERSION value and add a new
     * switch case for the previous version.
     *
     * <p>Database of all users will go through the required steps based on their currently installed version.
     *
     * @return bool true when an upgrade has occurred, false otherwise
     */
    public static function upgrade_database() {
        // Thinking about the future: if at any point we add this "feature" and then user rolls back to old version,
        // we don't want to break his entire database
        if ( get_option( self::OPTION_NAME_PLUGIN_DB_UPGRADE_DISABLED, false ) ) {
            return false;
        }

        $log = function ( $attempted_request ) {
            self::log_success( LogRequestType::AUTOMATIC, LogTarget::META, 'Database upgrade', $attempted_request );
        };

        // If the current version is already higher (mostly after development),
        // it simply gets lowered to prevent anomalies
        if ( get_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 ) > self::TARGET_PLUGIN_DB_VERSION ) {
            update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, self::TARGET_PLUGIN_DB_VERSION );
            $log( 'Detected too large database version, setting down to ' . self::TARGET_PLUGIN_DB_VERSION );

            return false;
        }

        global $wpdb;
        $upgraded = false;
        // Upgrades the database towards the currently installed plugin version
        while ( ( $current_db_version = get_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 ) )
                < self::TARGET_PLUGIN_DB_VERSION
        ) {
            $previous_db_version = $current_db_version;

            switch ( $current_db_version ) {
                //default: // Uncomment when testing to recreate tables after any version change
                // Initialization - if the database had none or an unknown version, drop and recreate everything
                case 0:
                    self::drop_all_tables();
                    self::create_all_tables( LogRequestType::AUTOMATIC );
                    self::refresh_db_best_events( LogRequestType::AUTOMATIC );
                    self::refresh_db_best_lbgs( LogRequestType::AUTOMATIC );
                    // create_all_tables() already called lbg_translations_init(), no need to call again here;
                    foreach ( self::$LANG_CODES as $code ) {
                        self::refresh_lbgs_translation_table( LogRequestType::AUTOMATIC, $code );
                    }
                    // Skips all other upgrade steps by setting the version to the highest
                    update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, self::TARGET_PLUGIN_DB_VERSION );
                    $log( 'Database initialization: installed version ' . self::TARGET_PLUGIN_DB_VERSION );
                    break;
                // Added LBGS translations
                case 1:
                    // in case of upgrading from version 1, however, we need to call lbg_translations_init();
                    self::lbgs_translations_init();
                    $operation = 'Upgrading DB by adding translation tables';
                    foreach ( self::$LANG_CODES as $code ) {
                        self::create_table(
                            str_replace( '??',
                                $wpdb->prefix . self::BEST_LBGS_TRANSLATION_TABLE_PREFIX . $code,
                                self::LBGS_TRANSLATION_DDL_STATEMENT ),
                            LogTarget::LBGS, LogRequestType::AUTOMATIC, $operation );
                        self::refresh_lbgs_translation_table( LogRequestType::AUTOMATIC, $code );
                    }
                    break;
                // End switch ( $current_db_version )
            }

            // Implicitly increases version by 1 after each step, unless there was an explicit external change
            $new_db_version = get_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 );
            if ( $current_db_version == $new_db_version && $current_db_version < self::TARGET_PLUGIN_DB_VERSION ) {
                update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, ++ $current_db_version );
                $log( 'Upgraded version from ' . ( $current_db_version - 1 ) . ' to ' . $current_db_version );
            }

            // Database was upgraded if the db version has changed
            if ( $previous_db_version < $new_db_version ) {
                $upgraded = true;
            }
        }

        return $upgraded;
    }

    /**
     * Creates all missing plugin-specific tables in the database.
     * <p>Does nothing if tables with the same name already exist.
     * <p>Should be run at the time of the plugin activation.
     *
     * <p>Studying references:
     * <https://codex.wordpress.org/Function_Reference/wpdb_Class>
     * <https://codex.wordpress.org/Creating_Tables_with_Plugins>
     *
     * @param $request_type string type of the operation request, use enum class LogRequestType
     *
     * @see LogRequestType
     */
    public static function create_all_tables( $request_type = LogRequestType::AUTOMATIC ) {
        global $wpdb;
        $operation = 'Table creation if missing';
        self::create_table(
            str_replace( '??', $wpdb->prefix . TableName::HISTORY, self::HISTORY_DDL_STATEMENT ),
            LogTarget::META, $request_type, $operation );
        self::create_table(
            str_replace( '??', $wpdb->prefix . TableName::EVENTS, self::EVENTS_DDL_STATEMENT ),
            LogTarget::EVENTS, $request_type, $operation );
        self::create_table(
            str_replace( '??', $wpdb->prefix . TableName::LBGS, self::LBGS_DDL_STATEMENT ),
            LogTarget::LBGS, $request_type, $operation );
        self::lbgs_translations_init();
        $operation = 'Upgrading DB by adding translation tables';
        foreach ( self::$LANG_CODES as $code ) {
            self::create_table(
                str_replace( '??',
                    $wpdb->prefix . self::BEST_LBGS_TRANSLATION_TABLE_PREFIX . $code,
                    self::LBGS_TRANSLATION_DDL_STATEMENT
                ),
                LogTarget::LBGS, LogRequestType::AUTOMATIC, $operation );
        }
    }

    /**
     * Deletes all plugin tables from the database.
     *
     * @param $request_type string type of the operation request, use enum class LogRequestType
     *
     * @see LogRequestType
     */
    public static function drop_all_tables( $request_type = LogRequestType::AUTOMATIC ) {
        global $wpdb;

        $success = true;
        self::lbgs_translations_init();
        foreach ( self::$LANG_CODES as $code ) {
            if ( ! $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . self::BEST_LBGS_TRANSLATION_TABLE_PREFIX .
                                 $code )
            ) {
                $success = false;
                self::log_error( $request_type, LogTarget::META, 'Dropping tables', $wpdb->last_query,
                    $wpdb->last_error );
            }
        }

        if ( $success && $wpdb->query( 'DROP TABLE IF EXISTS '
                                       . $wpdb->prefix . TableName::EVENTS
                                       . ', '
                                       . $wpdb->prefix . TableName::LBGS
                                       . ', '
                                       . $wpdb->prefix . TableName::HISTORY
            )
        ) {
            // Removes the stored plugin version setting, next time the DB has to be re-initialized
            update_option( self::OPTION_NAME_PLUGIN_DB_VERSION, 0 );
        } else {
            self::log_error( $request_type, LogTarget::META, 'Dropping tables', $wpdb->last_query, $wpdb->last_error );
        }
    }

    /**
     * Logs an error into the database in order to be displayed to the administrator.
     *
     * @param $request_type      string type of the operation request, use enum class LogRequestType
     * @param $target            string the event where the error occurred, use enum class LogTarget
     * @param $operation         string description of the action that is being performed
     * @param $attempted_request string request that has caused the error
     * @param $error_message     string explanation of the problem that happened
     *
     * @see LogRequestType, LogTarget
     * @see log_success()
     *
     * @return int|false false on logging error
     */
    public static function log_error( $request_type, $target, $operation, $attempted_request, $error_message = null ) {
        // Error is defined by error_message not being null
        if ( $error_message == null ) {
            $error_message = '(No error message)';
            //TODO check whether === null has the same effect, because this condition will be used when reading from DB to distinguish errors
        }

        // If the request type is explicitly unknown, we use the relevant column value
        if ( $request_type == null ) {
            $request_type = 'unknown';
        }

        global $wpdb;
        $table_name = esc_sql( $wpdb->prefix . TableName::HISTORY );

        return $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name "
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
     *
     * @param $request_type      string type of the operation request, use enum class LogRequestType
     * @param $target            string the event where the operation was performed, use enum class LogTarget
     * @param $operation         string description of the action that is being performed
     * @param $attempted_request string the successfully executed request
     *
     * @see LogRequestType, LogTarget
     * @see log_error()
     *
     * @return int|false false on logging error, which is also logged (if possible)
     */
    public static function log_success( $request_type, $target, $operation, $attempted_request ) {
        // If the request type is explicitly unknown, we use the relevant column value
        if ( $request_type == null ) {
            $request_type = 'unknown';
        }

        global $wpdb;
        $table_name = esc_sql( $wpdb->prefix . TableName::HISTORY );

        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name "
                . "(request_type, target, operation, attempted_request) "
                . "VALUES (%s, %s, %s, %s)"
                , $request_type
                , $target
                , $operation
                , $attempted_request
            )
        );

        // Logging the error
        if ( $result === false ) {
            self::log_error( $request_type, LogTarget::META, 'Logging a success', $wpdb->last_query,
                $wpdb->last_error );
            // Error during logging the error of logging success will not be logged or returned. Is this too meta?
        }

        return $result;
    }

    /**
     * Replaces table's contents in the database by new content,
     * taking care of anomalies that can happen and logging them.
     *
     * @param $table_name_no_prefix string name of the table without prefix to be replaced, use enum TableName
     * @param $request_type         string type of the operation request, use enum LogRequestType
     * @param $target               string the event where the operation was performed, use enum LogTarget
     * @param $operation            string description of the action that is being performed
     * @param $insert               callable {@param $table_name string @return string insert query}
     *                              returns the sql-safe insert query to be run
     *
     * @see LogRequestType, LogTarget
     *
     * @return bool true on success, false on failure
     */
    public static function replace_db_table( $table_name_no_prefix, $request_type, $target, $operation, callable $insert
    ) {
        global $wpdb;
        $table_name = esc_sql( "{$wpdb->prefix}$table_name_no_prefix" );

        // Runs the callback for insert query
        $insert_query = $insert( $table_name );
        if ( $insert_query == null ) {
            self::log_error( $request_type, $target, $operation, 'Requested insert query', 'Returned no data' );

            return false;
        }

        // If the DB supports it, we will offer transaction rollback on error
        $wpdb->query( 'START TRANSACTION' );

        // Deletes all old entries
        $wpdb->query( "TRUNCATE TABLE $table_name" );

        // Running the query and problem handling
        if ( ! $wpdb->query( $insert_query ) ) {
            self::log_error( $request_type, $target, $operation, $wpdb->last_query, $wpdb->last_error );
            $wpdb->query( 'ROLLBACK' );

            return false;
        }

        // All went OK
        $last_query = $wpdb->last_query;
        $wpdb->query( 'COMMIT' );
        self::log_success( $request_type, $target, $operation, $last_query );

        return true;
    }

    /**
     * Refreshes the state of the BEST Events database table using parser data.
     *
     * @param $request_type string type of the operation request, use enum LogRequestType
     *
     * @see LogRequestType
     *
     * @return bool true on success, false on failure (@see @return of replace_db_table())
     */
    public static function refresh_db_best_events( $request_type ) {
        // Reads all courses from the remote db
        $parser  = best_kosice_data::instance();
        $courses = $parser->courses();

        // Used logger values
        $target    = LogTarget::EVENTS;
        $operation = 'Refreshing table using parser';

        if ( $courses['learning']['data'] ) {
            // Replaces the table by new insert data based on the callback using function which logs the result
            return self::replace_db_table( TableName::EVENTS, $request_type, $target, $operation,
                function ( $table_name ) use ( $courses ) {
                    if ( ! $table_name ) {
                        return null;
                    }

                    $insert_query =
                        "INSERT INTO $table_name (event_name, login_url, place, dates, event_type, acad_compl, fee) VALUES ";

                    // Inserts new entries
                    foreach ( $courses['learning']['data'] as $course ) {
                        // Next row
                        $insert_query .= '(' .
                                         // event_name
                                         "'" . esc_sql( $course[0] ) . "'," .
                                         // login_url
                                         "'" . esc_sql( $course[1] ) . "'," .
                                         // place
                                         "'" . esc_sql( $course[2] ) . "'," .
                                         // dates
                                         "'" . esc_sql( $course[3] ) . "'," .
                                         // event_type
                                         "'" . esc_sql( $course[4] ) . "'," .
                                         // acad_compl
                                         "'" . esc_sql( $course[5] ) . "'," .
                                         // fee
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
     * @param $request_type string type of the operation request, use enum LogRequestType
     *
     * @see LogRequestType
     *
     * @return bool true on success, false on failure (@see @return of replace_db_table())
     */
    public static function refresh_db_best_lbgs( $request_type ) {
        // Reads all local best groups from the remote db
        $parser = best_kosice_data::instance();
        $lbgs   = $parser->lbgs();

        // Used logger values
        $target    = LogTarget::LBGS;
        $operation = 'Refreshing table using parser';

        if ( $lbgs ) {
            // Replaces the table by new insert data based on the callback using function which logs the result
            return self::replace_db_table( TableName::LBGS, $request_type, $target, $operation,
                function ( $table_name ) use ( $lbgs ) {
                    if ( $table_name == null ) {
                        return null;
                    }

                    $insert_query = "INSERT INTO $table_name (web_page, city, state) VALUES ";

                    // Inserts new entries
                    foreach ( $lbgs as $lbg ) {
                        // Next row
                        $insert_query .= '(' .
                                         // web_page
                                         "'" . esc_sql( $lbg[0] ) . "'," .
                                         // city
                                         "'" . esc_sql( $lbg[2] ) . "'," .
                                         // state
                                         "'" . esc_sql( $lbg[1] ) . "'" .
                                         '),';
                    }

                    return rtrim( $insert_query, ',' );
                } );
        } else {
            self::log_error( $request_type, $target, $operation
                , 'Requesting lbgs from parser'
                , 'Returned no data: ' . $parser->error_message()
            );

            return false;
        }
    }

    /**
     * Add a translation table of LBG names into the database. Values to insert are accessed from an XML document.
     *
     * @param $request_type string type of the operation request, use enum LogRequestType
     * @param $lang_code    string a 2-letter code in lowercase that represents the language code of a particular
     *                      language, e.g. sk - Slovak, etc.
     *
     * @see LogRequestType
     *
     * @return bool true on success, false on failure (@see @return of replace_db_table())
     */
    public static function refresh_lbgs_translation_table( $request_type, $lang_code ) {
        $lbgs = simplexml_load_file( BEST_Courses_LBGS::instance()->assets_dir . '/lang_xml/'
                                     . str_replace( '??', $lang_code, self::$TRANSLATION_XML_FILENAME )
                                     . '.xml' );
        // Used logger values
        $target    = LogTarget::TRANSLATION;
        $operation = 'Refreshing translation table for language code: ' . $lang_code;
        if ( $lbgs ) {
            // Replaces the table by new insert data based on the callback using function which logs the result
            return self::replace_db_table( self::BEST_LBGS_TRANSLATION_TABLE_PREFIX . $lang_code, $request_type,
                $target,
                $operation,
                function ( $table_name ) use ( $lbgs ) {
                    if ( $table_name == null ) {
                        return null;
                    }

                    $insert_query = "INSERT INTO $table_name (lbg_id, name) VALUES ";

                    // Inserts new entries
                    foreach ( $lbgs->children() as $lbg ) {
                        // Next row
                        $insert_query .= '(' .
                                         // lbg_id
                                         "'" . esc_sql( $lbg["id"] ) . "'," .
                                         // name
                                         "'" . esc_sql( $lbg ) . "'" .
                                         '),';
                    }

                    return rtrim( $insert_query, ',' );
                } );
        } else {
            self::log_error( $request_type, $target, $operation
                , 'Failed to retrieve LBGs from XML file for language: ' . $lang_code
                , 'Returned no data'
            );

            return false;
        }
    }


    /**
     * Initializes member variables required for creating and refreshing LBG translation tables,
     * namely, filename format minus .xml extension of XML translation files (default: lbgs_??,
     * where ?? is a language code) and the list of available languages (represented as an array
     * of language codes). Initialization information is retrieved from a config XML file in the
     * same directory as the XML translation files.
     *
     * It is necessary to call this function before any attempt at creating the LBG translations DB,
     * (including upgrading the DB to add translation tables) and also before manually updating
     * the translations database, in case it is done after adding new languages or changing the config.
     *
     * @see $TRANSLATION_XML_FILENAME, $LANG_CODES and the file lang.conf.xml
     *
     * @return bool true on success, false on failure
     */
    public static function lbgs_translations_init() {
        $config = simplexml_load_file( BEST_Courses_LBGS::instance()->assets_dir . '/lang_xml/lang.conf.xml' );
        if ( $config ) {
            self::$TRANSLATION_XML_FILENAME = $config->xpath( './format' )[0];
            self::$LANG_CODES               = $config->xpath( './langs/lang/@id' );

            return true;
        } else {
            return false;
        }
    }

    //General database querying and manipulation methods

    /**
     * Creates a database table by running an SQL DDL statement (e.g. 'CREATE TABLE ...')
     *
     * <p>TODO: reconsider usefulness of this function, it literally only queries the code to the DB.
     * <p>TODO: encapsulate lbgs_translations_init() in a way that it runs automatically before creating a
     * relevant table, this can give function create_table() a purpose. I think that putting a large switch
     * in this function for each TABLE NAME target, instead of $sql_ddl_statement is the best choice here,
     * automatically choosing DDLs as cases. Log target should be also implicitly defined together with each table.
     * We want the DB to be as safe as possible, not letting other parts of code change the query when calling this.
     *
     * @param $sql_ddl_statement string of the DDL SQL statement
     * @param $log_target        string of the target (table) to create, use enum LogTarget
     * @param $request_type      string request type, use enum LogRequestType
     * @param $operation         string description of the action that is being performed
     *
     * @see LogRequestType, LogTarget
     *
     * @return int|false false on logging error
     */
    public static function create_table(
        $sql_ddl_statement,
        $log_target,
        $request_type = LogRequestType::AUTOMATIC,
        $operation = 'Table creation if missing'
    ) {
        global $wpdb;
        // Querying and error handling
        //TODO: stop logging when table already existed (query still returns 1)

        // NOTE: History table has to be created first in order for logging to work. Learned it the hard way.
        if ( $wpdb->query( $sql_ddl_statement ) ) {
            self::log_success( $request_type, $log_target, $operation, $wpdb->last_query );
        } else {
            self::log_error( $request_type, $log_target, $operation, $wpdb->last_query, $wpdb->last_error );
        }
    }

    /**
     * Counts the number of rows of a table in the database.
     *
     * @param $table_name_no_prefix string table name without prefix, use enum TableName
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

    /**
     * Erases table contents in the database.
     *
     * @param $table_name_no_prefix string name of the table without prefix to be erased, use enum TableName
     * @param $request_type         string type of the operation request, use enum LogRequestType
     *
     * @see LogRequestType
     */
    public static function erase_table( $table_name_no_prefix, $request_type ) {
        global $wpdb;
        $table_name = esc_sql( "{$wpdb->prefix}$table_name_no_prefix" );
        $operation  = "Erasing a table";
        if ( $wpdb->query( "TRUNCATE TABLE $table_name" ) ) {
            Database::log_success( $request_type, LogTarget::META, $operation, $wpdb->last_query );
        } else {
            Database::log_error( $request_type, LogTarget::META, $operation, $wpdb->last_query, $wpdb->last_error );
        }
    }

    /**
     * Returns up to a N rows from the database.
     *
     * @param $max_number_of_rows       string number of rows that can be returned at most
     * @param $table_name_no_prefix     string name of the table without prefix to be selected from, use enum TableName
     * @param $condition                string|null SQL-safe condition (use esc_sql()) within 'WHERE' to be used to get
     *                                  the result, optional
     * @param $order_by                 string|null SQL-safe part (use esc_sql()) within 'ORDER BY' to order the
     *                                  results, optional
     *
     * @return array|null associative array column => value for the row values, null on error
     */
    public static function get_rows( $max_number_of_rows, $table_name_no_prefix, $condition = null, $order_by = null ) {
        global $wpdb;
        $table_name = esc_sql( "{$wpdb->prefix}$table_name_no_prefix" );
        if ( $condition ) {
            $condition = " WHERE $condition";
        }
        if ( $order_by ) {
            $order_by = " ORDER BY $order_by";
        }

        $rows = intval( $max_number_of_rows );

        return $wpdb->get_results( "SELECT * FROM $table_name$condition$order_by LIMIT $rows", ARRAY_A );
    }

    /**
     * Returns up to a single row from the database.
     *
     * @param $table_name_no_prefix string name of the table without prefix to be selected from, use enum TableName
     * @param $condition            string|null SQL-safe condition (use esc_sql()) within 'WHERE' to be used to get the
     *                              result, optional
     * @param $order_by             string|null SQL-safe part (use esc_sql()) within 'ORDER BY' to order the results,
     *                              optional
     *
     * @return array|null associative array column => value for the row values, null on error
     */
    public static function get_single_row( $table_name_no_prefix, $condition = null, $order_by = null ) {
        return self::get_rows( 1, $table_name_no_prefix, $condition, $order_by );
    }
}
