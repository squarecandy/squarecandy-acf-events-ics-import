<?php
/*
Plugin Name:       Square Candy ACF Events ICS Import
Plugin URI:        https://github.com/squarecandy/squarecandy-acf-events-ics-import
GitHub Plugin URI: https://github.com/squarecandy/squarecandy-acf-events-ics-import
Primary Branch:    main
Description:       Import ICS calendar feeds to create events in the Square Candy ACF Events plugin
Version: 1.0.1
Author:            Square Candy
Author URI:        http://squarecandy.net
Text Domain:       squarecandy-acf-events-ics-import
Requires at least: 5.0
Requires PHP:      7.4
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'functions.php';
