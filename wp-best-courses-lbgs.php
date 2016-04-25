<?php
/*
 * Plugin Name: wp_best_courses_lbgs
 * Version: 1.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: wp-best-courses-lbgs
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
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

// Database table names used in this plugin (TODO: move them to a global plugin config)
global $wp_best_courses_lbgs_database_tables;
$wp_best_courses_lbgs_database_tables = array('best_events', 'lbg');

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
function wp_best_courses_lbgs_create_table( $table_name_without_prefix ) {
    //Global instance of the WordPress Database
    global $wpdb;

    //Name of the created table
    $table_name = $wpdb->prefix . $table_name_without_prefix;

    //Path to the create table database script file
    $path_script = plugin_dir_path ( __FILE__ ) . 'db-script/create_' . $table_name_without_prefix . '.sql';

    //Reading the content of the create table database script
    $file_script = fopen($path_script, "r") or die("Unable to open file!");
    $create_script_content = fread($file_script, filesize($path_script));
    fclose($file_script);

    //Charset
    $charset_collate = $wpdb->get_charset_collate();

    //Preparing the SQL statement
    $sql_script = "CREATE TABLE $table_name (
$create_script_content
) $charset_collate;";

    //DEBUG: Logging the SQL Statement
    //scsc_log("Creating database using script:\n" . $sql_script);

    //Creating (or upgrading) the databases
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_script );
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
}

add_action('best_courses_lbgs_cron_task', 'wp_best_courses_lbgs_cron_task');

/**
 * Plugin activation event.
 *
 * List of actions:
 * 1. Schedules cron events
 * 2. Creates all SQL tables
 */
function wp_best_courses_lbgs_activation() {
    wp_schedule_event(time(), 'hourly', 'best_courses_lbgs_cron_task');

    global $wp_best_courses_lbgs_database_tables;
    foreach($wp_best_courses_lbgs_database_tables as $table) {
        //scsc_log("Did table $table already exist", wp_best_courses_lbgs_exists_table($table));
        wp_best_courses_lbgs_create_table($table);
    }
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
