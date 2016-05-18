<?php
/*
 * Plugin Name: wp_best_courses_lbgs
 * Version: 1.0
 * Plugin URI: http://www.best.tuke.sk/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: BEST AJ TY
 * Author URI: http://www.best.tuke.sk/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: wp-best-courses-lbgs
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author BEST AJ TY
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-wp-best-courses-lbgs.php' );
require_once( 'includes/class-wp-best-courses-lbgs-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-wp-best-courses-lbgs-admin-api.php' );
require_once( 'includes/lib/class-wp-best-courses-lbgs-post-type.php' );
require_once( 'includes/lib/class-wp-best-courses-lbgs-taxonomy.php'  );
require_once( 'includes/lib/class-wp-best-courses-lbgs-parser.php'    );

/**
 * Creates (or upgrades) a table in the database.
 * Should be run at the time of the plugin activation on all custom tables.
 *
 * Studying references:
 * <https://codex.wordpress.org/Function_Reference/wpdb_Class>
 * <https://codex.wordpress.org/Creating_Tables_with_Plugins>
 *
 * @param $table_name_without_prefix table name before WP adds a prefix.
 * Corresponding file {Name} will be searched on path: < wp-content/plugins/{Plugin}/db-script/create_{Name}.sql >
 * Content of the file must be only a list of rows expected BETWEEN parenthesis, in the following format:
 * CREATE TABLE name ( FILE_CONTENT );
 */
function wp_best_courses_lbgs_create_tables() {
    //Global instance of the WordPress Database
    global $wpdb;

    // sql for creating tables in heredoc

    $sql_events = <<< SQL
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}best_events (
id_event int(11) NOT NULL,
event_name varchar(100) NOT NULL,
place varchar(40) NOT NULL,
dates varchar(40) NOT NULL,
event_type varchar(100) NOT NULL,
acad_compl varchar(20) DEFAULT NULL,
fee varchar(10) NOT NULL,
app_deadline varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL;

// podľa mňa nieje potrebné kedže nieje relácia
    $sql_events_pk = <<< SQL
ALTER TABLE {$wpdb->prefix}best_events
ADD PRIMARY KEY (id_event)
SQL;

// auto increment
    $sql_events_auto_inc = <<< SQL
ALTER TABLE {$wpdb->prefix}best_events
MODIFY id_event int(11) NOT NULL AUTO_INCREMENT
SQL;

    $sql_lbg = <<< SQL
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}best_lbg (
id_lbg int(11) NOT NULL,
city varchar(50) NOT NULL,
state varchar(50) NOT NULL,
web_page varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL;

    $sql_lbg_pk = <<< SQL
ALTER TABLE {$wpdb->prefix}best_lbg
ADD PRIMARY KEY (id_lbg);
SQL;
    $sql_lbg_auto_inc = <<< SQL
ALTER TABLE {$wpdb->prefix}best_lbg
MODIFY id_lbg int(11) NOT NULL AUTO_INCREMENT;
SQL;

// CREATE EVENTS TABLE
  $wpdb->query($sql_events);
  $wpdb->query($sql_events_pk);
  $wpdb->query($sql_events_auto_inc);

// CREATE LBG TABLE
  $wpdb->query($sql_lbg);
  $wpdb->query($sql_lbg_pk);
  $wpdb->query($sql_lbg_auto_inc);
}

/**
 * Checks if a table with a specified name exists in the database.
 * Advised usage: interrupt plugin installation if the table already exists.
 *
 * @param $table_name_without_prefix table name before WP adds a prefix.
 * @return true if the table exists in the database, false otherwise.
 */
function wp_best_courses_lbgs_exists_table( $table_name_without_prefix ) {
    //Global instance of the WordPress Database
    global $wpdb;

    //Name of the checked table
    $table_name = $wpdb->prefix . $table_name_without_prefix;

    //Queries the database for a table with the same name
    return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
}

/**
 * Debug by scsc. Creates a new row entry in a debug file. Remember to delete the created file afterwards.
 * Will be removed in later release; used for testing cron functionality.
 */
function scsc_log($input1, $input2 = null) {
    if (defined('WP_DEBUG') && true === WP_DEBUG) {
        //File location: < Ampps/www/wp/scsc_log.txt >
        $myfile_path = "../scsc_log.txt";
        $myfile_number_limit = 6;

        //Creates an empty file if none exists
        fclose(fopen($myfile_path, "a+", $myfile_number_limit));

        $myfile_size = filesize($myfile_path);
        $myfile = fopen($myfile_path, "a+") or die("Unable to open file!");

        //Reads the first $myfile_number_limit characters and uses them as a counter, then saves the remaining data
        $number = fread($myfile, $myfile_number_limit);
        if($myfile_size - $myfile_number_limit > 0) {
            $data = fread($myfile, $myfile_size - $myfile_number_limit);
        } else {
            $data = '';
        }
        rewind($myfile);
        ftruncate($myfile, 0);

        //Increases the counter
        $myfile_powered = 1;
        for($i = 1; $i <  $myfile_number_limit; $i++)
        {
            $myfile_powered *= 10;
        }
        for($i = ((int)$number)+1; $i / $myfile_powered < 1; $i*=10)
        {
            fwrite($myfile, "0");
        }

        //Writes the data back, appending a new line with the current timestamp
        fwrite($myfile, ((int)$number)+1);
        fwrite($myfile, $data);
        fwrite($myfile, "\n");
        fwrite($myfile, date("h:i:sa"));
        fwrite($myfile, " - $input1.");

        //If the second parameter is present, prints it as a boolean
        if($input2 == true) {
            fwrite($myfile, " true");
        } else if(!is_null($input2)) {
            fwrite($myfile, ' false');
        }
        fclose($myfile);
    }
}

/**
 * This function removes registered WP Cron events by a specified event name.
 * Source: <https://wordpress.org/support/topic/wp_unschedule_event-and-wp_clear_scheduled_hook-do-not-clear-events>
 *
 * Alternatives that were tried first but were not working correctly:
 * wp_clear_scheduled_hook('best_courses_lbgs_cron_task');
 * wp_unschedule_event(wp_next_scheduled('best_courses_lbgs_cron_task'),'best_courses_lbgs_cron_task');
 */
function WPUnscheduleEventsByName($strEventName) {
    $arrCronEvents = _get_cron_array();
    foreach ($arrCronEvents as $nTimeStamp => $arrEvent)
        if (isset($arrCronEvents[$nTimeStamp][$strEventName])) unset( $arrCronEvents[$nTimeStamp] );
    _set_cron_array( $arrCronEvents );
}

/**
 * Cron support of this plugin.
 */
function wp_best_courses_lbgs_cron_task () {
    //TODO: put here code to execute on cron run

    //scsc_log("cron wp task has been run");

    refresh_db_best_events();
    refresh_db_lbgs();

}

add_action('best_courses_lbgs_cron_task', 'wp_best_courses_lbgs_cron_task');

//TODO javadocs
function refresh_db_best_events() {
    //Reads all courses from the remote db
    $parser = best\kosice\datalib\best_kosice_data::instance();
    $courses = $parser->courses();

    if ($courses) {
        global $wpdb;
        $tableName = $wpdb->prefix . 'best_events';

        //If the DB supports it, we will offer transaction rollback on error
        $wpdb->query('START TRANSACTION');

        //Deletes all old entries
        $wpdb->query("TRUNCATE TABLE " . $tableName);

        //Inserts new entries
        for ($i = 0; $i < count($courses); $i++) {
            //A concrete best course
            $course = $courses[$i];

            $insertResult = $wpdb->insert(
                $tableName,
                array(
                    'event_name' => $course[0],
                    //TODO fix add: odkaz na prihlasenie do kurzu @ https://trello.com/c/EPobfCTg/21-bugfix-v-tabu-ke-events-chyba-jeden-st-pec-na-odkaz-na-prihlasenie-na-kurz-napr-http-www-best-eu-org-student-courses-event-jsp-a
                    'place' => $course[2],
                    'dates' => $course[3],
                    'event_type' => $course[4],
                    'fee' => $course[5],
                    'acad_compl' => $course[6],
                )
            );

            //Problem handling
            if (!$insertResult) {
                $wpdb->query('ROLLBACK');
                return;
            }

            //DEBUG:
            //var_dump($course);
            //echo "<br>";
        }

        //All went OK
        $wpdb->query('COMMIT');
    }
}

//TODO javadocs
function refresh_db_lbgs() {
    //Reads all courses from the remote db
    $parser = best\kosice\datalib\best_kosice_data::instance();
    $lbgs = $parser->lbgs();

    if ($lbgs) {
        global $wpdb;
        $tableName = $wpdb->prefix . 'lbg';

        //If the DB supports it, we will offer transaction rollback on error
        $wpdb->query('START TRANSACTION');

        //Deletes all old entries
        $wpdb->query("TRUNCATE TABLE " . $tableName);

        //Inserts new entries
        for ($i = 0; $i < count($lbgs); $i++) {
            //A concrete local best group
            $lbg = $lbgs[$i];

            //Parsing, TODO: implement the first normal form of parser function output format
            $cityAndStateExploded = explode('(', $lbg[1]);
            $stateAbbreviation = explode(')', $cityAndStateExploded[1])[0];

            $insertResult = $wpdb->insert(
                $tableName,
                array(
                    'web_page' => $lbg[0],
                    'city' => $cityAndStateExploded[0],
                    'state' => $stateAbbreviation,
                )
            );

            //Problem handling
            if (!$insertResult) {
                $wpdb->query('ROLLBACK');
                return;
            }

            //DEBUG:
            //var_dump($lbg);
            //echo "<br>";
        }

        //All went OK
        $wpdb->query('COMMIT');
    }
}

/**
 * Plugin activation event.
 *
 * List of actions:
 * 1. Schedules cron events
 * 2. Creates all SQL tables
 */
function wp_best_courses_lbgs_activation() {
    wp_schedule_event(time(), 'hourly', 'best_courses_lbgs_cron_task');
    wp_best_courses_lbgs_create_tables();
}

register_activation_hook(__FILE__, 'wp_best_courses_lbgs_activation');

/**
 * Plugin deactivation event.
 *
 * List of actions:
 * 1. Removes cron scheduling
 */
function wp_best_courses_lbgs_deactivation() {
    WPUnscheduleEventsByName('best_courses_lbgs_cron_task');
}

register_deactivation_hook(__FILE__, 'wp_best_courses_lbgs_deactivation');

/**
 * Returns the main instance of wp_best_courses_lbgs to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object wp_best_courses_lbgs
 */
function wp_best_courses_lbgs () {
	$instance = wp_best_courses_lbgs::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = wp_best_courses_lbgs_Settings::instance( $instance );
	}

	return $instance;
}

wp_best_courses_lbgs();
