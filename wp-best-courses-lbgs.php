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
require_once( 'includes/lib/class-wp-best-courses-lbgs-taxonomy.php' );

/**
 * Cron support
 */
function wp_best_courses_lbgs_cron_task () {
    //TODO: put here code to execute on cron run

}

add_action('best_courses_lbgs_cron_task', 'wp_best_courses_lbgs_cron_task');

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