<?php
/**
 * Plugin Name: WP REST Location Sync Demo
 * Plugin URI:  https://github.com/example/wp-rest-location-sync-demo
 * Description: Demo plugin showing a maintainable pattern for syncing external location data into WordPress via the HTTP API, scheduled refreshes, and a simple admin sync workflow.
 * Version:     1.0.0
 * Author:      Troy Whitney
 * Author URI:  https://github.com/example
 * Text Domain: wp-rest-location-sync-demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IILLC_WPRLSD_VERSION', '1.0.0' );
define( 'IILLC_WPRLSD_FILE', __FILE__ );
define( 'IILLC_WPRLSD_PATH', plugin_dir_path( __FILE__ ) );
define( 'IILLC_WPRLSD_URL', plugin_dir_url( __FILE__ ) );
	require_once IILLC_WPRLSD_PATH . 'includes/class-iillc-location-repository.php';
require_once IILLC_WPRLSD_PATH . 'includes/class-iillc-api-client.php';
require_once IILLC_WPRLSD_PATH . 'includes/class-iillc-location-mapper.php';
require_once IILLC_WPRLSD_PATH . 'includes/class-iillc-location-sync.php';
require_once IILLC_WPRLSD_PATH . 'includes/class-iillc-scheduler.php';
require_once IILLC_WPRLSD_PATH . 'includes/class-iillc-admin-page.php';
require_once IILLC_WPRLSD_PATH . 'includes/class-iillc-plugin.php';

function iillc_wprlsd() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new IILLC_WPRLSD_Plugin();
	}

	return $plugin;
}

register_activation_hook( __FILE__, array( 'IILLC_WPRLSD_Scheduler', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'IILLC_WPRLSD_Scheduler', 'deactivate' ) );

iillc_wprlsd()->init();
