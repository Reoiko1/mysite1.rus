<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * AJAX handler for Archivarix Broken Links Recovery.
 *
 * Handles all AJAX endpoints:
 * - Scan control (start, resume, stop)
 * - Link actions (fix, bulk, whitelist)
 * - Data retrieval (links, logs, progress)
 * - Settings management
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Ajax
 *
 * AJAX endpoint handler for plugin operations.
 */
class ABLR_Ajax {

	/**
	 * Initialize AJAX hooks.
	 */
	public static function init() {
		// Scan actions.
		add_action( 'wp_ajax_ablr_start_scan', array( __CLASS__, 'start_scan' ) );
		add_action( 'wp_ajax_ablr_resume_scan', array( __CLASS__, 'resume_scan' ) );
		add_action( 'wp_ajax_ablr_stop_scan', array( __CLASS__, 'stop_scan' ) );
		add_action( 'wp_ajax_ablr_stop_scan_full', array( __CLASS__, 'stop_scan_full' ) );
		add_action( 'wp_ajax_ablr_get_progress', array( __CLASS__, 'get_progress' ) );

		// Link actions.
		add_action( 'wp_ajax_ablr_fix_link', array( __CLASS__, 'fix_link' ) );
		add_action( 'wp_ajax_ablr_bulk_action', array( __CLASS__, 'bulk_action' ) );
		add_action( 'wp_ajax_ablr_whitelist_link', array( __CLASS__, 'whitelist_link' ) );
		add_action( 'wp_ajax_ablr_unwhitelist_link', array( __CLASS__, 'unwhitelist_link' ) );

		// Data.
		add_action( 'wp_ajax_ablr_get_links', array( __CLASS__, 'get_links' ) );
		add_action( 'wp_ajax_ablr_get_logs', array( __CLASS__, 'get_logs' ) );
		add_action( 'wp_ajax_ablr_clear_data', array( __CLASS__, 'clear_data' ) );
		add_action( 'wp_ajax_ablr_save_settings', array( __CLASS__, 'save_settings' ) );

		// Proxy management.
		add_action( 'wp_ajax_ablr_test_proxies', array( __CLASS__, 'test_proxies' ) );
	}

	/**
	 * Start a fresh scan (from scratch).
	 *
	 * Uses batched collection to handle large sites (100K+ posts) without
	 * running out of memory. Posts are collected in chunks using cursor-based
	 * pagination.
	 */
	public static function start_scan() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Clear emergency stop signal from previous scan.
		delete_transient( 'ablr_emergency_stop' );

		// Clear all previous scan data and caches for a fresh start.
		ABLR_Database::clear_all();
		ABLR_Social_Checker::clear_all_cache();
		ABLR_Checker::clear_all_video_cache();
		ABLR_Internal_Checker::clear_cache();
		ABLR_Internal_Checker::clear_all_http_cache();
		ABLR_Scan_Process::clear_settings_cache();

		// Get total count first (fast COUNT query, no memory impact).
		$total_items = ABLR_Scanner::count_scan_items();

		// Get current settings to store with scan progress.
		$settings = get_option( 'ablr_settings', array() );

		// Initialize progress with settings snapshot.
		update_option(
			'ablr_scan_progress',
			array(
				'status'              => 'extracting',
				'extracted'           => 0,
				'links_found'         => 0,
				'checked'             => 0,
				'checked_internal'    => 0,
				'broken'              => 0,
				'total_items'         => $total_items,
				'total_links'         => 0,
				'started_at'          => current_time( 'mysql' ),
				'scan_internal_links' => ! empty( $settings['scan_internal_links'] ),
			),
			false
		);

		// Phase 1: Collect items in batches and push to queue.
		// Cursor-based pagination: efficient for large sites, no OFFSET overhead.
		$batch_size = 500;

		$process    = new ABLR_Scan_Process();
		$last_id    = 0;
		$queued     = 0;
		$start_time = microtime( true );
		$time_limit = 25; // Leave buffer before PHP timeout.

		while ( true ) {
			$batch = ABLR_Scanner::collect_scan_items_batch( $last_id, $batch_size );

			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $batch as $item ) {
				$process->push_to_queue( $item );
				$last_id = $item['id'];
				++$queued;
			}

			// Save queue periodically to prevent data loss on timeout.
			if ( 0 === $queued % 5000 ) {
				$process->save();
			}

			// Check time limit — if approaching timeout, save and continue.
			$elapsed = microtime( true ) - $start_time;
			if ( $elapsed > $time_limit ) {
				break;
			}
		}

		$process->save()->dispatch();

		ABLR_Database::add_log(
			0,
			'',
			'',
			0,
			'scan_started',
			sprintf( 'Scan started. Items to scan: %d', $total_items )
		);

		wp_send_json_success(
			array(
				'message'     => 'Scan started.',
				'total_items' => $total_items,
			)
		);
	}

	/**
	 * Resume a cancelled/stopped scan.
	 * Picks up from where it left off — only processes pending links.
	 */
	public static function resume_scan() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Clear emergency stop signal from previous pause.
		delete_transient( 'ablr_emergency_stop' );

		$progress = get_option( 'ablr_scan_progress', array() );
		$settings = get_option( 'ablr_settings', array() );

		// Check if critical settings changed since scan started.
		$scan_started_with_internal = ! empty( $progress['scan_internal_links'] );
		$current_internal_setting   = ! empty( $settings['scan_internal_links'] );

		if ( $current_internal_setting && ! $scan_started_with_internal ) {
			// User enabled internal links checking after scan started.
			// Internal links were not extracted, so resume won't check them.
			wp_send_json_error(
				array(
					'code'    => 'settings_changed',
					'message' => __( 'Internal link checking was enabled after the scan started. Internal links were not collected during extraction. Please start a new scan to check internal links.', 'archivarix-broken-links-recovery' ),
				)
			);
			return;
		}

		// ── Batch pre-check: process remaining internal links first ──
		$batch_result = ABLR_Internal_Checker::batch_check_pending();

		if ( $batch_result['checked'] > 0 ) {
			$progress['checked']          = ( isset( $progress['checked'] ) ? (int) $progress['checked'] : 0 )
											+ $batch_result['checked'];
			$progress['checked_internal'] = ( isset( $progress['checked_internal'] ) ? (int) $progress['checked_internal'] : 0 )
											+ $batch_result['checked'];
			$progress['broken']           = ( isset( $progress['broken'] ) ? (int) $progress['broken'] : 0 )
											+ $batch_result['broken'];
			update_option( 'ablr_scan_progress', $progress, false );
		}
		// ── End batch pre-check ──

		// Get pending links that still need checking (mostly external after batch).
		$pending = ABLR_Database::get_pending_links( 10000 );

		if ( empty( $pending ) ) {
			// No pending links — nothing to resume. Mark complete.
			$progress['status']       = 'complete';
			$progress['completed_at'] = current_time( 'mysql' );
			update_option( 'ablr_scan_progress', $progress, false );
			wp_send_json_success( array( 'message' => 'No pending links to check. Scan is complete.' ) );
			return;
		}

		// Resume checking phase.
		$progress['status'] = 'checking';

		// Calculate total_links ONCE - only if not already set or is zero.
		// This prevents the progress bar from jumping around.
		$already_checked = isset( $progress['checked'] ) ? (int) $progress['checked'] : 0;
		$new_total       = $already_checked + count( $pending );

		if ( empty( $progress['total_links'] ) || $progress['total_links'] < $new_total ) {
			$progress['total_links'] = $new_total;
		}
		update_option( 'ablr_scan_progress', $progress, false );

		$process = new ABLR_Scan_Process();
		foreach ( $pending as $link ) {
			$process->push_to_queue(
				array(
					'phase'   => 'check',
					'link_id' => $link->id,
				)
			);
		}
		$process->save()->dispatch();

		ABLR_Database::add_log(
			0,
			'',
			'',
			0,
			'scan_resumed',
			sprintf( 'Scan resumed. Pending links to check: %d', count( $pending ) )
		);

		wp_send_json_success(
			array(
				'message'       => 'Scan resumed.',
				'pending_links' => count( $pending ),
			)
		);
	}

	/**
	 * Stop scan process.
	 */
	public static function stop_scan() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Set emergency stop transient FIRST - this is the fastest signal.
		set_transient( 'ablr_emergency_stop', '1', 300 );

		// IMPORTANT: Set status to 'cancelled' BEFORE cancelling process.
		// This prevents race condition where get_progress() auto-recovers.
		$progress           = get_option( 'ablr_scan_progress', array() );
		$progress['status'] = 'cancelled';
		update_option( 'ablr_scan_progress', $progress, false );

		// Clear object cache to ensure other processes see the new status.
		wp_cache_delete( 'ablr_scan_progress', 'options' );

		// Now cancel the background process.
		$process = new ABLR_Scan_Process();
		$process->cancel();

		ABLR_Database::add_log( 0, '', '', 0, 'scan_cancelled', 'Scan cancelled by user.' );

		wp_send_json_success( array( 'message' => 'Scan stopped.' ) );
	}

	/**
	 * Stop scan process completely (no resume possible without new scan).
	 */
	public static function stop_scan_full() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Set emergency stop transient FIRST - this is the fastest signal.
		set_transient( 'ablr_emergency_stop', '1', 300 );

		// IMPORTANT: Set status to 'stopped' BEFORE cancelling process.
		// This prevents race condition where get_progress() sees inactive process
		// but status is still 'checking', causing auto-recovery to restart scan.
		$progress               = get_option( 'ablr_scan_progress', array() );
		$progress['status']     = 'stopped';
		$progress['stopped_at'] = current_time( 'mysql' );
		update_option( 'ablr_scan_progress', $progress, false );

		// Clear object cache to ensure other processes see the new status.
		wp_cache_delete( 'ablr_scan_progress', 'options' );

		// Now cancel the background process.
		$process = new ABLR_Scan_Process();
		$process->cancel();

		ABLR_Database::add_log( 0, '', '', 0, 'scan_stopped', 'Scan fully stopped by user.' );

		wp_send_json_success( array( 'message' => 'Scan fully stopped.' ) );
	}

	/**
	 * Get current scan progress.
	 */
	public static function get_progress() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		// Read directly from database to bypass object cache (Redis/Memcached).
		// This prevents race condition where stop_scan sets 'cancelled' but
		// a parallel get_progress request reads stale cached value and auto-recovers.
		global $wpdb;
		$raw_value = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$progress  = $raw_value ? maybe_unserialize( $raw_value ) : array();
		$status    = isset( $progress['status'] ) ? $progress['status'] : '';

		// If scan was cancelled or stopped, never auto-recover. Just report status.
		if ( in_array( $status, array( 'cancelled', 'stopped' ), true ) ) {
			$progress['is_active'] = false;
			$progress['counts']    = ABLR_Database::get_status_counts();
			wp_send_json_success( $progress );
			return;
		}

		$process = new ABLR_Scan_Process();

		// Check if extraction is done and we need to start checking.
		if ( 'extracting' === $status && ! $process->is_active() ) {
			// ── Batch pre-check: process all internal links before queuing external ──
			$batch_result = ABLR_Internal_Checker::batch_check_pending();

			if ( $batch_result['checked'] > 0 ) {
				$progress['checked']          = ( isset( $progress['checked'] ) ? (int) $progress['checked'] : 0 )
												+ $batch_result['checked'];
				$progress['checked_internal'] = ( isset( $progress['checked_internal'] ) ? (int) $progress['checked_internal'] : 0 )
												+ $batch_result['checked'];
				$progress['broken']           = ( isset( $progress['broken'] ) ? (int) $progress['broken'] : 0 )
												+ $batch_result['broken'];
			}
			// ── End batch pre-check ──

			// Now get remaining pending links (mostly external after batch).
			$pending = ABLR_Database::get_pending_links( 10000 );
			if ( ! empty( $pending ) ) {
				$progress['status'] = 'checking';

				// Calculate total_links ONCE - only if not already set.
				// This prevents the progress bar from jumping around.
				$new_total = ( isset( $progress['checked'] ) ? (int) $progress['checked'] : 0 ) + count( $pending );
				if ( empty( $progress['total_links'] ) || $progress['total_links'] < $new_total ) {
					$progress['total_links'] = $new_total;
				}
				update_option( 'ablr_scan_progress', $progress, false );

				$check_process = new ABLR_Scan_Process();
				foreach ( $pending as $link ) {
					$check_process->push_to_queue(
						array(
							'phase'   => 'check',
							'link_id' => $link->id,
						)
					);
				}

				// Re-check status before dispatch to prevent race condition.
				// User may have clicked Pause after we read status at the beginning.
				$fresh_raw    = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$fresh_status = $fresh_raw ? maybe_unserialize( $fresh_raw ) : array();
				if ( isset( $fresh_status['status'] ) && in_array( $fresh_status['status'], array( 'cancelled', 'stopped' ), true ) ) {
					// User cancelled — abort dispatch, report stopped state.
					$fresh_status['is_active'] = false;
					$fresh_status['counts']    = ABLR_Database::get_status_counts();
					wp_send_json_success( $fresh_status );
					return;
				}

				$check_process->save()->dispatch();

				$progress['is_active'] = true;
				$progress['counts']    = ABLR_Database::get_status_counts();
				wp_send_json_success( $progress );
				return;
			} else {
				$progress['status']       = 'complete';
				$progress['completed_at'] = current_time( 'mysql' );
				update_option( 'ablr_scan_progress', $progress, false );
			}
		} elseif ( 'checking' === $status && ! $process->is_active() ) {
			$still_pending = ABLR_Database::get_pending_links( 1 );
			if ( empty( $still_pending ) ) {
				$progress['status']       = 'complete';
				$progress['completed_at'] = current_time( 'mysql' );
				update_option( 'ablr_scan_progress', $progress, false );
			} else {
				// Re-check status before auto-recovery to prevent race condition.
				// User may have clicked Pause after we read status at the beginning.
				$fresh_raw    = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$fresh_status = $fresh_raw ? maybe_unserialize( $fresh_raw ) : array();
				if ( isset( $fresh_status['status'] ) && in_array( $fresh_status['status'], array( 'cancelled', 'stopped' ), true ) ) {
					// User cancelled — abort auto-recovery, report stopped state.
					$fresh_status['is_active'] = false;
					$fresh_status['counts']    = ABLR_Database::get_status_counts();
					wp_send_json_success( $fresh_status );
					return;
				}

				$pending       = ABLR_Database::get_pending_links( 10000 );
				$check_process = new ABLR_Scan_Process();
				foreach ( $pending as $link ) {
					$check_process->push_to_queue(
						array(
							'phase'   => 'check',
							'link_id' => $link->id,
						)
					);
				}
				$check_process->save()->dispatch();
				$progress['is_active'] = true;
				$progress['counts']    = ABLR_Database::get_status_counts();
				wp_send_json_success( $progress );
				return;
			}
		}

		$progress['is_active'] = $process->is_active();
		$progress['counts']    = ABLR_Database::get_status_counts();

		wp_send_json_success( $progress );
	}

	/**
	 * Fix a single link.
	 */
	public static function fix_link() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
		$action  = isset( $_POST['fix_action'] ) ? sanitize_text_field( wp_unslash( $_POST['fix_action'] ) ) : '';

		if ( ! $link_id || ! $action ) {
			wp_send_json_error( 'Missing parameters.' );
		}

		$result = false;

		switch ( $action ) {
			case 'replace_wayback':
				$result = ABLR_Fixer::replace_with_wayback( $link_id );
				break;

			case 'replace_custom':
				$custom_url = isset( $_POST['custom_url'] ) ? esc_url_raw( wp_unslash( $_POST['custom_url'] ) ) : '';
				if ( empty( $custom_url ) ) {
					wp_send_json_error( 'Custom URL is required.' );
				}
				$result = ABLR_Fixer::replace_with_custom( $link_id, $custom_url );
				break;

			case 'remove_link':
				$result = ABLR_Fixer::remove_link_keep_text( $link_id );
				break;

			case 'remove_all':
				$result = ABLR_Fixer::remove_link_and_text( $link_id );
				break;

			default:
				wp_send_json_error( 'Unknown action.' );
		}

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Action applied.' ) );
		} else {
			wp_send_json_error( 'Failed to apply action. The link may have been already fixed.' );
		}
	}

	/**
	 * Bulk action on multiple links.
	 */
	public static function bulk_action() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$link_ids = isset( $_POST['link_ids'] ) ? array_map( 'absint', (array) $_POST['link_ids'] ) : array();
		$action   = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';

		if ( empty( $link_ids ) || empty( $action ) ) {
			wp_send_json_error( 'Missing parameters.' );
		}

		$processed = 0;

		foreach ( $link_ids as $lid ) {
			$ok = false;
			switch ( $action ) {
				case 'replace_wayback':
					$ok = ABLR_Fixer::replace_with_wayback( $lid );
					break;
				case 'remove_link':
					$ok = ABLR_Fixer::remove_link_keep_text( $lid );
					break;
				case 'remove_all':
					$ok = ABLR_Fixer::remove_link_and_text( $lid );
					break;
				case 'whitelist':
					ABLR_Database::whitelist_link( $lid );
					$ok = true;
					break;
				case 'undo':
					$ok = ABLR_Fixer::undo_action( $lid );
					break;
			}
			if ( $ok ) {
				++$processed;
			}
		}

		wp_send_json_success(
			array(
				'message'   => sprintf( '%d links processed.', $processed ),
				'processed' => $processed,
			)
		);
	}

	/**
	 * Whitelist a link.
	 */
	public static function whitelist_link() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
		if ( ! $link_id ) {
			wp_send_json_error( 'Missing link ID.' );
		}

		ABLR_Database::whitelist_link( $link_id );
		ABLR_Database::add_log( $link_id, '', '', 0, 'whitelisted', 'Link marked as whitelisted.' );

		wp_send_json_success( array( 'message' => 'Link whitelisted.' ) );
	}

	/**
	 * Remove link from whitelist.
	 */
	public static function unwhitelist_link() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
		if ( ! $link_id ) {
			wp_send_json_error( 'Missing link ID.' );
		}

		ABLR_Database::unwhitelist_link( $link_id );
		ABLR_Database::add_log( $link_id, '', '', 0, 'unwhitelisted', 'Link removed from whitelist.' );

		wp_send_json_success( array( 'message' => 'Link removed from whitelist.' ) );
	}

	/**
	 * Get links list (paginated).
	 */
	public static function get_links() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Whitelist allowed status values for security.
		$allowed_statuses = array( '', 'pending', 'ok', 'broken', 'whitelisted', 'uncheckable', 'fixed' );
		$status_input     = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$status           = in_array( $status_input, $allowed_statuses, true ) ? $status_input : '';

		$args = array(
			'status'      => $status,
			'is_internal' => isset( $_GET['is_internal'] ) ? sanitize_text_field( wp_unslash( $_GET['is_internal'] ) ) : '',
			'per_page'    => isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 30,
			'page'        => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
			'search'      => isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '',
			'orderby'     => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id',
			'order'       => isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC',
		);

		$result = ABLR_Database::get_links( $args );

		// Collect all unique source IDs for batch loading.
		$post_ids = array();
		foreach ( $result['items'] as $item ) {
			if ( post_type_exists( $item->source_type ) && $item->source_id > 0 ) {
				$post_ids[] = (int) $item->source_id;
			}
		}

		// Batch load all posts at once (single query instead of N queries).
		if ( ! empty( $post_ids ) ) {
			// This primes the WordPress object cache.
			_prime_post_caches( array_unique( $post_ids ), true, false );
		}

		// Cache for source data (within this request).
		$source_cache = array();

		foreach ( $result['items'] as &$item ) {
			$cache_key = $item->source_type . '_' . $item->source_id;

			if ( ! isset( $source_cache[ $cache_key ] ) ) {
				$post_date = '';
				$permalink = '';
				if ( post_type_exists( $item->source_type ) && $item->source_id > 0 ) {
					$post = get_post( $item->source_id );
					if ( $post ) {
						$post_date = $post->post_date;
						$permalink = get_permalink( $item->source_id );
					}
				}

				$source_cache[ $cache_key ] = array(
					'edit_url'  => self::get_edit_url( $item->source_type, $item->source_id ),
					'title'     => self::get_source_title( $item->source_type, $item->source_id ),
					'post_date' => $post_date,
					'permalink' => $permalink,
				);
			}

			$item->edit_url     = $source_cache[ $cache_key ]['edit_url'];
			$item->source_title = $source_cache[ $cache_key ]['title'];
			$item->post_date    = $source_cache[ $cache_key ]['post_date'];
			$item->permalink    = $source_cache[ $cache_key ]['permalink'];
		}

		// Include counts in response to avoid separate AJAX call.
		$result['counts'] = ABLR_Database::get_status_counts();

		wp_send_json_success( $result );
	}

	/**
	 * Get logs.
	 */
	public static function get_logs() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$args = array(
			'per_page' => isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 20,
			'page'     => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
			'link_id'  => isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0,
		);

		$result = ABLR_Database::get_logs( $args );

		// Cache for source titles to avoid repeated DB queries.
		static $source_cache = array();

		foreach ( $result['items'] as &$log ) {
			$log->edit_url     = '';
			$log->source_title = '';

			$source_type = $log->source_type;
			$source_id   = (int) $log->source_id;

			// Only lookup link if source is missing and link_id exists.
			if ( ( empty( $source_type ) || empty( $source_id ) ) && ! empty( $log->link_id ) ) {
				$cache_key = 'link_' . $log->link_id;
				if ( ! isset( $source_cache[ $cache_key ] ) ) {
					$link                       = ABLR_Database::get_link( (int) $log->link_id );
					$source_cache[ $cache_key ] = $link ? array( $link->source_type, (int) $link->source_id ) : array( '', 0 );
				}
				list( $source_type, $source_id ) = $source_cache[ $cache_key ];
			}

			if ( ! empty( $source_type ) && ! empty( $source_id ) ) {
				$log->source_type = $source_type;
				$log->source_id   = $source_id;

				// Cache source title lookups.
				$title_key = $source_type . '_' . $source_id;
				if ( ! isset( $source_cache[ $title_key ] ) ) {
					$source_cache[ $title_key ] = array(
						'edit_url' => self::get_edit_url( $source_type, $source_id ),
						'title'    => self::get_source_title( $source_type, $source_id ),
					);
				}
				$log->edit_url     = $source_cache[ $title_key ]['edit_url'];
				$log->source_title = $source_cache[ $title_key ]['title'];
			}
		}

		wp_send_json_success( $result );
	}

	/**
	 * Clear all plugin data.
	 */
	public static function clear_data() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$process = new ABLR_Scan_Process();
		$process->cancel();

		// Clear database tables.
		ABLR_Database::clear_all();
		delete_option( 'ablr_scan_progress' );

		// Clear all caches to ensure fresh scan.
		ABLR_Social_Checker::clear_all_cache();
		ABLR_Checker::clear_all_video_cache();
		ABLR_Internal_Checker::clear_cache();
		ABLR_Internal_Checker::clear_all_http_cache();
		ABLR_Scan_Process::clear_settings_cache();

		wp_send_json_success( array( 'message' => 'All data cleared.' ) );
	}

	/**
	 * Save settings.
	 */
	public static function save_settings() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Parse proxies from textarea.
		$proxies = array();
		if ( ! empty( $_POST['proxies'] ) ) {
			$proxy_lines = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['proxies'] ) ) );
			foreach ( $proxy_lines as $line ) {
				$proxy = ABLR_Checker::parse_proxy_line( $line );
				if ( $proxy ) {
					$proxies[] = $proxy;
				}
			}
		}

		$settings = array(
			'scan_post_types'     => isset( $_POST['scan_post_types'] ) && is_array( $_POST['scan_post_types'] )
									? array_map( 'sanitize_text_field', wp_unslash( $_POST['scan_post_types'] ) )
									: array(),
			'scan_internal_links' => ! empty( $_POST['scan_internal_links'] ) ? 1 : 0,
			'batch_size'          => isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 10,
			'proxies'             => $proxies,
			'auto_mode'           => isset( $_POST['auto_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['auto_mode'] ) ) : 'manual',
			'auto_action'         => isset( $_POST['auto_action'] ) ? sanitize_text_field( wp_unslash( $_POST['auto_action'] ) ) : 'remove_link',
			'auto_fix_internal'   => ! empty( $_POST['auto_fix_internal'] ) ? 1 : 0,
		);

		update_option( 'ablr_settings', $settings );

		wp_send_json_success(
			array(
				'message'     => 'Settings saved.',
				'proxy_count' => count( $proxies ),
			)
		);
	}

	/**
	 * Test proxies and return working ones.
	 */
	public static function test_proxies() {
		check_ajax_referer( 'ablr_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$proxy_text = isset( $_POST['proxies'] ) ? sanitize_textarea_field( wp_unslash( $_POST['proxies'] ) ) : '';

		if ( empty( $proxy_text ) ) {
			wp_send_json_success(
				array(
					'results'       => array(),
					'working'       => array(),
					'working_count' => 0,
					'total_count'   => 0,
				)
			);
		}

		$proxy_lines = explode( "\n", $proxy_text );
		$results     = array();
		$working     = array();

		foreach ( $proxy_lines as $line ) {
			$proxy = ABLR_Checker::parse_proxy_line( $line );
			if ( ! $proxy ) {
				continue;
			}

			$test_result = ABLR_Checker::test_proxy( $proxy );
			$results[]   = $test_result;

			if ( $test_result['working'] ) {
				// Reconstruct the proxy line in original format.
				$working_line = $proxy['host'] . ':' . $proxy['port'];
				if ( ! empty( $proxy['user'] ) && ! empty( $proxy['pass'] ) ) {
					$working_line .= ':' . $proxy['user'] . ':' . $proxy['pass'];
				}
				$working[] = $working_line;
			}
		}

		wp_send_json_success(
			array(
				'results'       => $results,
				'working'       => $working,
				'working_count' => count( $working ),
				'total_count'   => count( $results ),
			)
		);
	}

	/**
	 * Get edit URL for a source.
	 *
	 * @param string $source_type Source type (post type, comment, widget, etc.).
	 * @param int    $source_id   Source ID.
	 * @return string Edit URL or empty string.
	 */
	private static function get_edit_url( $source_type, $source_id ) {
		if ( post_type_exists( $source_type ) ) {
			return get_edit_post_link( $source_id, 'raw' );
		}

		switch ( $source_type ) {
			case 'comment':
			case 'comment_author':
				return admin_url( 'comment.php?action=editcomment&c=' . $source_id );

			case 'widget':
				return admin_url( 'widgets.php' );

			case 'meta':
				return get_edit_post_link( $source_id, 'raw' );

			default:
				return '';
		}
	}

	/**
	 * Get source title for display.
	 *
	 * @param string $source_type Source type (post type, comment, widget, etc.).
	 * @param int    $source_id   Source ID.
	 * @return string Source title for display.
	 */
	private static function get_source_title( $source_type, $source_id ) {
		if ( post_type_exists( $source_type ) ) {
			$post     = get_post( $source_id );
			$pt_obj   = get_post_type_object( $source_type );
			$pt_label = $pt_obj ? $pt_obj->labels->singular_name : ucfirst( $source_type );
			return $post ? $post->post_title : sprintf( '%s #%d (deleted)', $pt_label, $source_id );
		}

		switch ( $source_type ) {
			case 'comment':
			case 'comment_author':
				return sprintf( 'Comment #%d', $source_id );

			case 'widget':
				return sprintf( 'Widget #%d', $source_id );

			case 'meta':
				$post = get_post( $source_id );
				return $post ? sprintf( 'Meta: %s', $post->post_title ) : sprintf( 'Meta #%d', $source_id );

			default:
				return sprintf( '%s #%d', $source_type, $source_id );
		}
	}
}
