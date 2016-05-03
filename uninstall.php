<?php

/**
 *
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean-up unused meta, options, etc. in the database.
 *
 */

// If plugin is not being uninstalled, exit (do nothing)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Do something here if plugin is being uninstalled.

// Database table names used in this plugin (TODO: move them to a global plugin config)
global $wp_best_courses_lbgs_database_tables;
$wp_best_courses_lbgs_database_tables = array('best_events', 'lbg');

/**
 * Deletes a table from the database.
 * Should be run at the time of plugin deactivation on all custom tables.
 */
 function wp_best_courses_lbgs_drop_tables() {
     //Global instance of the WordPress Database
     global $wpdb;

     $wpdb->query('DROP TABLE IF EXISTS '.$wpdb->prefix.'best_events, '.$wpdb->prefix.'best_lbg');
 }
wp_best_courses_lbgs_drop_tables();
