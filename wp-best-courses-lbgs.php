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

/**
 * Runs the main plugin entry point, instantiating Best_Courses_LBGS with the current PHP file.
 */
Best_Courses_LBGS::instance( __FILE__, '1.0.0' );

/*function return_map_data(){
    global $wpdb;
    $wpdb->query("SELECT * FROM " . $wpdb->prefix . "best_lbgs" . "ORDER BY ")
}

add_action( 'wp_ajax_return_map_data', 'return_map_data' );
add_action( 'wp_ajax_nopriv_return_map_data', 'return_map_data' );*/
//define( 'WP_DEBUG_LOG', true );
//define( 'WP_DEBUG', true );
