<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'SQCDY_ICS_IMPORT_VERSION', 'version-1.0.0' );
define( 'SQCDY_ICS_IMPORT_DIR', plugin_dir_path( __FILE__ ) );
define( 'SQCDY_ICS_IMPORT_URL', plugin_dir_url( __FILE__ ) );
define( 'SQCDY_ICS_IMPORT_BASENAME', plugin_basename( __FILE__ ) );

// Square Candy Common Functions
require_once SQCDY_ICS_IMPORT_DIR . 'inc/sqcdy-common.php';
require_once SQCDY_ICS_IMPORT_DIR . 'inc/sqcdy-plugin.php';


// Check dependencies on activation
register_activation_hook( __FILE__, 'sqcdy_ics_import_activate' );
function sqcdy_ics_import_activate() {
	// Check for Square Candy ACF Events plugin
	if ( ! class_exists( 'WP_Post' ) || ! post_type_exists( 'event' ) ) {
		wp_die(
			wp_kses_post(
				sprintf(
					/* translators: %s: Back button HTML */
					__( 'The Square Candy ACF Events ICS Import Plugin requires the Square Candy ACF Events plugin to be active. %s', 'squarecandy-acf-events-ics-import' ),
					'<br><br><button onclick="window.history.back()">&laquo; ' . esc_html__( 'back', 'squarecandy-acf-events-ics-import' ) . '</button>'
				)
			)
		);
	}

	// Check for ACF
	if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
		wp_die(
			wp_kses_post(
				sprintf(
					/* translators: %s: Back button HTML */
					__( 'The Square Candy ACF Events ICS Import Plugin requires Advanced Custom Fields (ACF) to be active. %s', 'squarecandy-acf-events-ics-import' ),
					'<br><br><button onclick="window.history.back()">&laquo; ' . esc_html__( 'back', 'squarecandy-acf-events-ics-import' ) . '</button>'
				)
			)
		);
	}
}

// Initialize plugin
add_action( 'init', 'sqcdy_ics_import_init', 20 );
function sqcdy_ics_import_init() {
	// Check dependencies - run dependency notice check on admin_notices
	add_action( 'admin_notices', 'sqcdy_ics_import_check_dependencies' );

	// Always load plugin files - let the admin menu function handle dependency checks
	require_once SQCDY_ICS_IMPORT_DIR . 'inc/class-sqcdy-ics-parser.php';
	require_once SQCDY_ICS_IMPORT_DIR . 'inc/class-sqcdy-event-importer.php';
	require_once SQCDY_ICS_IMPORT_DIR . 'inc/admin-pages.php';
	require_once SQCDY_ICS_IMPORT_DIR . 'inc/admin-styles.php';

	// Initialize admin
	if ( is_admin() ) {
		add_action( 'admin_menu', 'sqcdy_ics_import_admin_menu' );
		add_action( 'admin_init', 'sqcdy_ics_import_admin_init' );
	}
}

// Check dependencies and show notice if needed
function sqcdy_ics_import_check_dependencies() {
	if ( ! post_type_exists( 'event' ) || ! function_exists( 'get_field' ) ) {
		sqcdy_ics_import_dependency_notice();
	}
}

// Show dependency notice
function sqcdy_ics_import_dependency_notice() {
	printf(
		'<div class="notice notice-error"><p><strong>%1$s:</strong> %2$s</p></div>',
		esc_html__( 'Square Candy ACF Events ICS Import', 'squarecandy-acf-events-ics-import' ),
		esc_html__( 'This plugin requires both the Square Candy ACF Events plugin and Advanced Custom Fields to be active.', 'squarecandy-acf-events-ics-import' )
	);
}

// Add admin menu
function sqcdy_ics_import_admin_menu() {
	// Only add menu if dependencies are met
	if ( ! post_type_exists( 'event' ) || ! function_exists( 'get_field' ) ) {
		return;
	}

	add_submenu_page(
		'edit.php?post_type=event',
		__( 'ICS Import', 'squarecandy-acf-events-ics-import' ),
		__( 'ICS Import', 'squarecandy-acf-events-ics-import' ),
		'manage_options',
		'sqcdy-ics-import',
		'sqcdy_ics_import_admin_page'
	);
}

// Admin init
function sqcdy_ics_import_admin_init() {
	register_setting( 'sqcdy_ics_import', 'sqcdy_ics_import_feed_url' );
	register_setting( 'sqcdy_ics_import', 'sqcdy_ics_import_update_existing' );
	register_setting( 'sqcdy_ics_import', 'sqcdy_ics_import_timezone' );

	// Clean up legacy category option
	sqcdy_ics_import_cleanup_legacy_options();
}

// Clean up legacy options that are no longer used
function sqcdy_ics_import_cleanup_legacy_options() {
	// Only run once to clean up the old category option
	if ( ! get_option( 'sqcdy_ics_import_category_cleanup_done' ) ) {
		delete_option( 'sqcdy_ics_import_default_category' );
		update_option( 'sqcdy_ics_import_category_cleanup_done', true );
	}
}
