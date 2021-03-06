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

// Class autoloader
require_once( 'vendor/autoload.php' );

// This gets executed when the plugin is being uninstalled.
best\kosice\best_courses_lbgs\Database::drop_all_tables();
