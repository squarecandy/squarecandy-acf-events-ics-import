<?php
/*
Plugin Name:       Square Candy ACF Events ICS Import
Plugin URI:        https://github.com/squarecandy/squarecandy-acf-events-ics-import
GitHub Plugin URI: https://github.com/squarecandy/squarecandy-acf-events-ics-import
Description:       Import ICS calendar feeds to create events in the Square Candy ACF Events plugin
Version:           1.0.0
Author:            Square Candy
Author URI:        http://squarecandy.net
Text Domain:       squarecandy-acf-events-ics-import
Requires at least: 5.0
Requires PHP:      7.4
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SQCDY_ICS_IMPORT_VERSION', '1.0.0');
define('SQCDY_ICS_IMPORT_DIR', plugin_dir_path(__FILE__));
define('SQCDY_ICS_IMPORT_URL', plugin_dir_url(__FILE__));
define('SQCDY_ICS_IMPORT_BASENAME', plugin_basename(__FILE__));

// Check dependencies on activation
register_activation_hook(__FILE__, 'sqcdy_ics_import_activate');
function sqcdy_ics_import_activate() {
    // Check for Square Candy ACF Events plugin
    if (!class_exists('WP_Post') || !post_type_exists('event')) {
        wp_die(
            'The Square Candy ACF Events ICS Import Plugin requires the Square Candy ACF Events plugin to be active.
            <br><br><button onclick="window.history.back()">&laquo; back</button>'
        );
    }

    // Check for ACF
    if (!function_exists('get_field') || !function_exists('update_field')) {
        wp_die(
            'The Square Candy ACF Events ICS Import Plugin requires Advanced Custom Fields (ACF) to be active.
            <br><br><button onclick="window.history.back()">&laquo; back</button>'
        );
    }
}

// Initialize plugin
add_action('admin_init', 'sqcdy_ics_import_init', 999);
function sqcdy_ics_import_init() {
    // Check dependencies
    if (!post_type_exists('event') || !function_exists('get_field')) {
        add_action('admin_notices', 'sqcdy_ics_import_dependency_notice');
        return;
    }

    // Load plugin files
    require_once SQCDY_ICS_IMPORT_DIR . 'includes/class-ics-parser.php';
    require_once SQCDY_ICS_IMPORT_DIR . 'includes/class-event-importer.php';
    require_once SQCDY_ICS_IMPORT_DIR . 'includes/admin-pages.php';
    require_once SQCDY_ICS_IMPORT_DIR . 'includes/admin-styles.php';

    // Initialize admin
    if (is_admin()) {
        add_action('admin_menu', 'sqcdy_ics_import_admin_menu');
        add_action('admin_init', 'sqcdy_ics_import_admin_init');
    }
}

// Show dependency notice
function sqcdy_ics_import_dependency_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>Square Candy ACF Events ICS Import:</strong> This plugin requires both the Square Candy ACF Events plugin and Advanced Custom Fields to be active.';
    echo '</p></div>';
}

// Add admin menu
function sqcdy_ics_import_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=event',
        'ICS Import',
        'ICS Import',
        'manage_options',
        'sqcdy-ics-import',
        'sqcdy_ics_import_admin_page'
    );
}

// Admin init
function sqcdy_ics_import_admin_init() {
    register_setting('sqcdy_ics_import', 'sqcdy_ics_import_feed_url');
    register_setting('sqcdy_ics_import', 'sqcdy_ics_import_default_category');
    register_setting('sqcdy_ics_import', 'sqcdy_ics_import_update_existing');
    register_setting('sqcdy_ics_import', 'sqcdy_ics_import_timezone');
}
