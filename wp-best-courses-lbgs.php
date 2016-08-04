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

// Load composer
require_once( 'vendor/autoload.php' );

// Load plugin class files
require_once( 'includes/class-wp-best-courses-lbgs.php' );
require_once( 'includes/class-wp-best-courses-lbgs-settings.php' );

// Load plugin libraries
require_once( 'includes/class-wp-best-courses-lbgs-admin-api.php' );
require_once( 'includes/class-wp-best-courses-lbgs-parser.php'    );

/**
 * Creates (or upgrades) all custom tables in the database.
 * Should be run at the time of the plugin activation.
 *
 * Studying references:
 * <https://codex.wordpress.org/Function_Reference/wpdb_Class>
 * <https://codex.wordpress.org/Creating_Tables_with_Plugins>
 */
function wp_best_create_tables_events_lbgs() {
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
type varchar(50) NOT NULL CHECK(type IN('automatic', 'manual')),
target varchar(50) NOT NULL CHECK(target IN('events_db', 'lbgs_db', 'meta')),
error_message varchar(200) DEFAULT NULL,
attempted_action varchar(200) DEFAULT NULL,
PRIMARY KEY (id_history)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL;

    //Querying and error handling
    $error_message = 'Error during table creation: ';
    if ( ! $wpdb->query( $sql_events ) ) {
        wp_best_courses_log_error( 'automatic', 'events_db', $error_message . $wpdb->last_error, $sql_events );
    }
    if ( ! $wpdb->query( $sql_lbgs ) ) {
        wp_best_courses_log_error( 'automatic', 'lbgs_db', $error_message . $wpdb->last_error, $sql_lbgs );
    }
    if ( ! $wpdb->query( $sql_history ) ) {
        wp_best_courses_log_error( 'automatic', 'meta', $error_message . $wpdb->last_error, $sql_history );
    }
}

/**
 * Logs an error into the database in order to be displayed to the administrator.
 * (If it becomes useful, it may even get its own class with enum like $target...)
 *
 * @param $target string the event where the error occurred
 * @param $error_message string explanation of the problem that happened
 * @param $attempted_action string request that caused the error
 */
function wp_best_courses_log_error( $type, $target, $error_message, $attempted_action ) {
    global $wpdb;
    $tableName = esc_sql( $wpdb->prefix . 'best_history' );
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $tableName"
            . "(type, target, error_message, attempted_action)"
            . "VALUES (%s, %s, %s, %s)"
            , $type
            , $target
            , $error_message
            , $attempted_action
        )
    );
}

/**
 * This function removes registered WP Cron events by a specified event name.
 * Source: <https://wordpress.org/support/topic/wp_unschedule_event-and-wp_clear_scheduled_hook-do-not-clear-events>
 *
 * Alternatives that were tried first, but were not working correctly:
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
 * Cron periodical (hourly) event of this plugin.
 *
 * List of actions:
 * Refreshes BEST databases
 */
function wp_best_courses_lbgs_cron_task() {
    refresh_db_best_events();
    refresh_db_best_lbgs();
}

add_action( 'best_courses_lbgs_cron_task', 'wp_best_courses_lbgs_cron_task' );

/**
 * Refreshes the state of the BEST Events database.
 */
function refresh_db_best_events() {
    //Reads all courses from the remote db
    $parser = best\kosice\datalib\best_kosice_data::instance();
    $courses = $parser->courses();

    if ($courses) {
        global $wpdb;
        $tableName = esc_sql( $wpdb->prefix . 'best_events' );

        //If the DB supports it, we will offer transaction rollback on error
        $wpdb->query( 'START TRANSACTION' );

        //Deletes all old entries
        $wpdb->query( "TRUNCATE TABLE $tableName" );

        $insert = "INSERT INTO $tableName (event_name, login_url, place, dates, event_type, acad_compl, fee) VALUES ";

        //Inserts new entries
        foreach ( $courses as $course ) {

            //Next row
            $insert .= '(' .
                       //event_name
                       "'" . $course[0] . "'," .
                       //login_url
                       "'" . $course[1] . "'," .
                       //place
                       "'" . $course[2] . "'," .
                       //dates
                       "'" . $course[3] . "'," .
                       //event_type
                       "'" . $course[4] . "'," .
                       //acad_compl
                       "'" . $course[5] . "'," .
                       //fee
                       "'" . $course[6] . "'" .
                       '),';
        }

        $insert = rtrim( $insert, ',' );

        //Running the query and problem handling
        if ( ! $wpdb->query( esc_sql( $insert ) ) ) {
            $wpdb->query( 'ROLLBACK' );
            return;
        }

        //All went OK
        $wpdb->query( 'COMMIT' );
    }
}

/**
 * Refreshes the state of the BEST Local Best Groups database.
 */
function refresh_db_best_lbgs() {
    //Reads all local best groups from the remote db
    $parser = best\kosice\datalib\best_kosice_data::instance();
    $lbgs = $parser->lbgs();

    if ($lbgs) {
        global $wpdb;
        $tableName = esc_sql( $wpdb->prefix . 'best_lbg' );

        //If the DB supports it, we will offer transaction rollback on error
        $wpdb->query( 'START TRANSACTION' );

        //Deletes all old entries
        $wpdb->query( "TRUNCATE TABLE $tableName" );

        $insert = "INSERT INTO $tableName (web_page, city, state) VALUES ";

        //Inserts new entries
        foreach ( $lbgs as $lbg ) {

            //Next row
            $insert .= '(' .
                       //web_page
                       "'" . $lbg[0] . "'," .
                       //city
                       "'" . $lbg[2] . "'," .
                       //state
                       "'" . $lbg[1] . "'" .
                       '),';
        }

        $insert = rtrim( $insert, ',' );

        //Running the query and problem handling
        if ( ! $wpdb->query( esc_sql( $insert ) ) ) {
            $wpdb->query( 'ROLLBACK' );
            return;
        }

        //All went OK
        $wpdb->query( 'COMMIT' );
    }
}

/**
 * Plugin activation event.
 *
 * List of actions:
 * 1. Schedules cron events
 * 2. Creates all SQL tables
 * 3. Refreshes BEST databases
 */
function wp_best_courses_lbgs_activation() {
    wp_schedule_event( time(), 'hourly', 'best_courses_lbgs_cron_task' );

    wp_best_create_tables_events_lbgs();

    refresh_db_best_events();
    refresh_db_best_lbgs();
}

register_activation_hook( __FILE__, 'wp_best_courses_lbgs_activation' );

/**
 * Plugin deactivation event.
 *
 * List of actions:
 * 1. Removes cron scheduling
 */
function wp_best_courses_lbgs_deactivation() {
    WPUnscheduleEventsByName( 'best_courses_lbgs_cron_task' );
}

register_deactivation_hook( __FILE__, 'wp_best_courses_lbgs_deactivation' );

/**
 * Plugin initialization event.
 *
 * List of actions:
 * 1. Registers a custom post type for BEST events
 *    Reference: <http://www.wpbeginner.com/wp-tutorials/how-to-create-custom-post-types-in-wordpress/>
 *    (Is not currently being used for anything and may be removed later)
 */
function wp_best_courses_lbgs_init() {
    register_post_type( 'best-events',
        //Custom post type options
        array(
            'labels' => array(
                'name'          => __( 'BEST Events' ),
                'singular_name' => __( 'BEST Event' )
            ),
            'public'          => true,
            //'has_archive'     => true,

            //Hiding from the user administration view
            'show_ui'         => false,

            'capability_type' => 'page',
            'hierarchical'	  => false,
            //'hierarchical'    => true,

            //Rewriting path in the address bar
            'rewrite' => array(
                'slug'		 	=> '/',
                'with_front'	=> true
            ),

            'supports'        => array(
                //'title',
                //'editor',
                //'custom-fields',
            ),
        )
    );
}

//Hooking up initialization function to the theme setup
add_action( 'init', 'wp_best_courses_lbgs_init' );

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

/**
 * Runs a PHP code in a file and instead of displaying the resulting HTML page, only returns it as a string.
 * Source: <http://stackoverflow.com/questions/1683771/execute-a-php-file-and-return-the-result-as-a-string>
 *
 * @param $php_file string PHP file to be run
 * @return string PHP result as HTML, that is supposed to be displayed in the browser
 */
function run_php_file_for_html( $php_file ) {
    ob_start();
    include( $php_file );
    $returned = ob_get_contents();
    ob_end_clean();
    return $returned;

    //Alternative, that can be tested for possible higher performance:
    //ob_start();
    //get_template_part('my_form_template');
    //return ob_get_clean();
}

/**
 * Shortcodes for inserting PHP files into any WP page.
 *
 * Important points:
 * - Remember to use return and not echo,
 * anything that is echoed will be output to the browser, but it won't appear in the correct place on the page.
 * - Take caution when using hyphens in the name of your shortcodes.
 *
 * Reference: <https://codex.wordpress.org/Shortcode_API>
 */

//Shortcode [best_events]
function best_events_shortcode() {
    return run_php_file_for_html( 'includes/shortcodes/events.php' );
}
add_shortcode( 'best_events', 'best_events_shortcode' );

/**
 * REGISTER [best_lbgs]
 * @return [type] [description]
 */
function best_lbgs_shortcode() {
    return run_php_file_for_html( 'includes/shortcodes/local-best-groups.php' );
}
add_shortcode( 'best_lbgs', 'best_lbgs_shortcode' );

//shortcode [lbgs_clickable_map]
function best_lbgs_map_shortcode() {
    return run_php_file_for_html( 'includes/shortcodes/lbgs-clickable-map.php' );
}
add_shortcode( 'lbgs_clickable_map', 'best_lbgs_map_shortcode' );

function wptuts_add_buttons( $plugin_array ) {
    $plugin_array['wptuts'] = wp_best_courses_lbgs()->assets_url . 'js/shortcode.min.js';
    return $plugin_array;
}

function wptuts_register_buttons( $buttons ) {
    array_push( $buttons, 'events', 'lbgs' ); // dropcap', 'recentposts
    return $buttons;
}

function wptuts_buttons() {
    add_filter( "mce_external_plugins", "wptuts_add_buttons" );
    add_filter( 'mce_buttons', 'wptuts_register_buttons' );
}

add_action( 'init', 'wptuts_buttons' );
