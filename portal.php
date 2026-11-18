<?php
/**
 * Plugin Name:       Portal
 * Plugin URI:        https://github.com/norico/portal
 * Description:       WordPress news federator
 * Author:            norico
 * Author URI:        https://github.com/norico/
 * Text Domain:       portal
 * Domain Path:       /languages
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'PORTAL_FILE', __FILE__ );
define( 'PORTAL_DIR', plugin_dir_path( PORTAL_FILE ) );
define( 'PORTAL_URL', plugin_dir_url( PORTAL_FILE ) );
define( 'PORTAL_BASENAME', plugin_basename( PORTAL_FILE ) );
define( 'PORTAL_VERSION', get_plugin_data( __FILE__)['Version'] );

// Load the autoloader.
if ( ! class_exists( 'Portal_Loader' ) ) {
	require_once PORTAL_DIR . 'includes/class-loader.php';
	require_once PORTAL_DIR . 'includes/class-wp-api.php';
}

// Register activation hook at top level (not inside another hook).
register_activation_hook( PORTAL_FILE, function() {
	$loader = new Portal_Loader( PORTAL_FILE );
	
	if ( ! $loader->check_requirements() ) {
		$php_version = $loader::REQUIRED_PHP;
		$wp_version  = $loader::REQUIRED_WP;

		if ( version_compare( PHP_VERSION, $php_version, '<' ) ) {
			wp_die( esc_html( "Portal requires PHP {$php_version} or higher." ) );
		}

		if ( version_compare( get_bloginfo( 'version' ), $wp_version, '<' ) ) {
			wp_die( esc_html( "Portal requires WordPress {$wp_version} or higher." ) );
		}
	}
} );

// Load textdomain on init hook (required by WordPress 6.7+ to avoid notice).
// Load plugin translations.
add_action( 'init', function() {
	load_plugin_textdomain(
		'portal',
		false,
		dirname( plugin_basename( PORTAL_FILE ) ) . '/languages'
	);
} );

// Initialize the plugin on plugins_loaded.
add_action( 'plugins_loaded', function() {
	$loader = new Portal_Loader( PORTAL_FILE );
	$loader->init();
} );
