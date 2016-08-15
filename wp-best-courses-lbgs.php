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

namespace best\kosice\best_courses_lbgs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load composer
require_once( 'vendor/autoload.php' );

// Representation of the plugin for text translation purposes
const PLUGIN_NAME = 'wp-best-courses-lbgs';

// Temporary workaround to use for adding callback functions before moving the file contents into a class
$namespace = 'best\\kosice\\best_courses_lbgs\\';

/**
 * This function removes registered WP Cron events by a specified event name.
 * Source: <https://wordpress.org/support/topic/wp_unschedule_event-and-wp_clear_scheduled_hook-do-not-clear-events>
 *
 * Alternatives that were tried first, but were not working correctly:
 * wp_clear_scheduled_hook('best_courses_lbgs_cron_task');
 * wp_unschedule_event(wp_next_scheduled('best_courses_lbgs_cron_task'),'best_courses_lbgs_cron_task');
 */
function wp_unschedule_cron_events_by_name( $event_name ) {
    $cron_events = _get_cron_array();
    foreach ( $cron_events as $n_timestamp => $arr_event ) {
        if ( isset( $cron_events[ $n_timestamp ][ $event_name ] ) ) {
            unset( $cron_events[ $n_timestamp ] );
        }
    }
    _set_cron_array( $cron_events );
}

/**
 * Cron periodical (hourly) event of this plugin.
 * Is also run in the plugin activation event.
 *
 * List of actions:
 * Refreshes BEST database tables
 */
function wp_best_courses_lbgs_cron_task() {
    // If the user did not explicitly disable automatic refresh, the database gets refreshed
    if ( get_option( Database::OPTION_BASE_PREFIX . Settings::OPTION_NAME_AUTOMATIC_REFRESH
        , Settings::OPTION_DEFAULT_AUTOMATIC_REFRESH )
    ) {
        Database::refresh_db_best_events( LogRequestType::AUTOMATIC );
        Database::refresh_db_best_lbgs( LogRequestType::AUTOMATIC );
    }
}

add_action( 'best_courses_lbgs_cron_task', $namespace . 'wp_best_courses_lbgs_cron_task' );

/**
 * Plugin activation event.
 *
 * List of actions:
 * 1. Schedules cron events
 * 2. Attempts to upgrade the database and then creates any missing SQL tables
 * 3. Runs the cron event to refresh the BEST database
 */
function wp_best_courses_lbgs_activation() {
    wp_schedule_event( time(), 'hourly', 'best_courses_lbgs_cron_task' );

    // Attempts to upgrade the database version before creating any missing tables
    Database::upgrade_database();
    Database::create_all_tables();

    wp_best_courses_lbgs_cron_task();
}

register_activation_hook( __FILE__, $namespace . 'wp_best_courses_lbgs_activation' );

/**
 * Plugin deactivation event.
 *
 * List of actions:
 * 1. Removes cron scheduling
 */
function wp_best_courses_lbgs_deactivation() {
    wp_unschedule_cron_events_by_name( 'best_courses_lbgs_cron_task' );
}

register_deactivation_hook( __FILE__, $namespace . 'wp_best_courses_lbgs_deactivation' );

/**
 * Returns the main instance of best_courses_lbgs to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return best_courses_lbgs
 */
function best_courses_lbgs() {
    return best_courses_lbgs::instance( __FILE__, '1.0.0' );
}

best_courses_lbgs();

/**
 * Runs a PHP code in a file and instead of displaying the resulting HTML page, only returns it as a string.
 * Source: <http://stackoverflow.com/questions/1683771/execute-a-php-file-and-return-the-result-as-a-string>
 *
 * @param $php_file string PHP file to be run
 *
 * @return string PHP result as HTML, that is supposed to be displayed in the browser
 */
function run_php_file_for_html( $php_file ) {
    ob_start();
    /** @noinspection PhpIncludeInspection */
    include( $php_file );
    $returned = ob_get_clean();

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

/**
 * Register shortcode [best_events]
 */
function best_events_shortcode() {
    return run_php_file_for_html( 'includes/shortcodes/events.php' );
}

add_shortcode( 'best_events', $namespace . 'best_events_shortcode' );

/**
 * Register shortcode [best_lbgs]
 */
function best_lbgs_shortcode() {
    return run_php_file_for_html( 'includes/shortcodes/local-best-groups.php' );
}

add_shortcode( 'best_lbgs', $namespace . 'best_lbgs_shortcode' );

/**
 * Register shortcode [best_lbgs_map]
 */
function best_lbgs_map_shortcode() {
    return run_php_file_for_html( 'includes/shortcodes/lbgs-clickable-map.php' );
}

add_shortcode( 'best_lbgs_map', $namespace . 'best_lbgs_map_shortcode' );

/**
 * Add custom buttons to the TinyMCE editor using javascript.
 */
function wptuts_add_buttons( $plugin_array ) {
    $plugin_array['wptuts'] = best_courses_lbgs()->assets_url . 'js/shortcode.min.js';

    return $plugin_array;
}

/**
 * Register custom buttons in the TinyMCE editor.
 */
function wptuts_register_buttons( $buttons ) {
    array_push( $buttons, 'events', 'lbgs', 'lbgs_map' );

    return $buttons;
}

/**
 * Plugin initialization event.
 *
 * List of actions:
 * 1. Upgrades the database to the newest version
 * 2. Applying TinyMCE filters to add new buttons.
 * 3. Registers a custom post request_type for BEST events
 *    Reference: <http://www.wpbeginner.com/wp-tutorials/how-to-create-custom-post-types-in-wordpress/>
 *    (Is not currently being used for anything and may be removed later)
 */
function wp_best_courses_lbgs_init() {
    // Always attempts to upgrade the database version
    Database::upgrade_database();

    add_filter( "mce_external_plugins", "wptuts_add_buttons" );
    add_filter( 'mce_buttons', 'wptuts_register_buttons' );

    register_post_type( 'best-events',
        // Custom post request_type options
        array(
            'labels'  => array(
                'name'          => __( 'BEST Events' ),
                'singular_name' => __( 'BEST Event' )
            ),
            'public'  => true,
            //'has_archive'     => true,

            // Hiding from the user administration view
            'show_ui' => false,

            'capability_type' => 'page',
            'hierarchical'    => false,
            //'hierarchical'    => true,

            // Rewriting path in the address bar
            'rewrite'         => array(
                'slug'       => '/',
                'with_front' => true
            ),

            'supports' => array(
                //'title',
                //'editor',
                //'custom-fields',
            ),
        )
    );
}

add_action( 'init', $namespace . 'wp_best_courses_lbgs_init' );



/*function return_map_data(){
    global $wpdb;
    $wpdb->query("SELECT * FROM " . $wpdb->prefix . "best_lbgs" . "ORDER BY ")
}



add_action( 'wp_ajax_return_map_data', 'return_map_data' );
add_action( 'wp_ajax_nopriv_return_map_data', 'return_map_data' );*/
//define( 'WP_DEBUG_LOG', true );
//define( 'WP_DEBUG', true );

