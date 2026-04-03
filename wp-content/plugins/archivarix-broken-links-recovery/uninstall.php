<?php
/**
 * Uninstall script for Archivarix Broken Links Recovery.
 *
 * Removes all plugin data when the plugin is deleted through WordPress admin.
 * This file is called automatically by WordPress during plugin deletion.
 *
 * @package Archivarix_Broken_Links_Recovery
 */

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete plugin options.
delete_option( 'ablr_settings' );
delete_option( 'ablr_scan_progress' );
delete_option( 'ablr_db_version' );

// Delete background process options (WP Background Processing library).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wp_ablr_scan_process_%'"
);

// Drop custom tables.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ablr_links" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ablr_logs" );

// Delete all plugin transients (caches for social, video, internal checks).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup.
$wpdb->query(
	"DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_ablr_%'
        OR option_name LIKE '_transient_timeout_ablr_%'"
);

// Delete internal checker throttling options.
delete_option( 'ablr_internal_http_last_request' );
delete_option( 'ablr_internal_timeout_count' );

// Clear any scheduled cron events.
wp_clear_scheduled_hook( 'ablr_scheduled_scan' );
