<?php
/**
 * Plugin Name: Archivarix Broken Links Recovery
 * Plugin URI: https://archivarix.com/en/blog/broken-links-recovery/
 * Description: Finds broken external and internal links in WordPress content and replaces them with Web Archive copies or manages them manually.
 * Version: 1.0.0
 * Author: Archivarix
 * Author URI: https://archivarix.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: archivarix-broken-links-recovery
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.9
 * Requires PHP: 7.4
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ABLR_VERSION', '1.0.0' );
define( 'ABLR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABLR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ABLR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload plugin classes.
 *
 * Classes with the ABLR_ prefix are loaded from the includes/ directory.
 * Class name format: ABLR_Some_Class -> includes/class-some-class.php
 */
spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'ABLR_';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = ABLR_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// Include WP Background Processing library (forked and prefixed to avoid conflicts).
require_once ABLR_PLUGIN_DIR . 'vendor/class-ablr-async-request.php';
require_once ABLR_PLUGIN_DIR . 'vendor/class-ablr-background-process.php';

/**
 * Plugin activation hook.
 *
 * Creates database tables and sets default settings.
 */
function ablr_activate() {
	ABLR_Database::create_tables();

	$defaults = array(
		'scan_post_types'     => array( 'post', 'page' ),
		'scan_internal_links' => 0,
		'batch_size'          => 10,
		'proxies'             => array(), // Proxy list for external link checking.
		'auto_mode'           => 'manual',
		'auto_action'         => 'remove_link',
		'auto_fix_internal'   => 0, // Apply auto-fix to internal 404 links (no Wayback, just remove).
	);

	if ( ! get_option( 'ablr_settings' ) ) {
		update_option( 'ablr_settings', $defaults );
	}
}
register_activation_hook( __FILE__, 'ablr_activate' );

/**
 * Plugin deactivation hook.
 *
 * Cancels any running background processes and clears scheduled events.
 */
function ablr_deactivate() {
	$scanner = new ABLR_Scan_Process();
	$scanner->cancel();
	wp_clear_scheduled_hook( 'ablr_scheduled_scan' );
}
register_deactivation_hook( __FILE__, 'ablr_deactivate' );

/**
 * Initialize plugin components.
 *
 * CRITICAL: The ABLR_Scan_Process must be instantiated during plugins_loaded
 * so its wp_ajax_ hooks are registered. Without this, the loopback dispatch
 * request has no handler and background processing silently fails.
 */
function ablr_init() {
	// Check for database upgrade (runs migrations when plugin is updated via ZIP upload).
	$db_version = get_option( 'ablr_db_version', '0' );
	if ( version_compare( $db_version, ABLR_VERSION, '<' ) ) {
		ABLR_Database::create_tables();
	}

	ABLR_Admin::init();
	ABLR_Ajax::init();

	// Instantiate background process to register its AJAX handlers.
	new ABLR_Scan_Process();
}
add_action( 'plugins_loaded', 'ablr_init' );
