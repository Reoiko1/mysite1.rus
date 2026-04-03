<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Background scan process for link extraction and checking.
 *
 * Uses WordPress background processing to handle large sites without timeouts.
 * Two-phase approach:
 *   Phase 1 (Extract): Parse post content and extract all links.
 *   Phase 2 (Check): Validate each link via HTTP or database lookup.
 *
 * For internal links, uses fast database-driven checker (no HTTP).
 * For external links, uses full HTTP checker with Wayback API lookup.
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Scan_Process
 *
 * Background process handler for link scanning.
 */
class ABLR_Scan_Process extends ABLR_Background_Process {

	/**
	 * Background process action identifier.
	 *
	 * @var string
	 */
	protected $action = 'scan_process';

	/**
	 * Number of links to check per task iteration.
	 * Higher values = faster scanning but more memory/CPU per request.
	 *
	 * @var int
	 */
	const LINKS_PER_TASK = 2;

	/**
	 * Cached settings to avoid repeated get_option() calls.
	 *
	 * @var array|null
	 */
	private static $cached_settings = null;

	/**
	 * Task counter for periodic cache refresh.
	 *
	 * @var int
	 */
	private static $task_counter = 0;

	/**
	 * Number of tasks between settings cache refreshes.
	 * Balances responsiveness to setting changes vs DB query overhead.
	 *
	 * @var int
	 */
	const SETTINGS_REFRESH_INTERVAL = 50;

	/**
	 * Get plugin settings with caching.
	 * Reduces database queries during batch processing.
	 *
	 * @return array
	 */
	private static function get_settings() {
		if ( null === self::$cached_settings ) {
			self::$cached_settings = get_option( 'ablr_settings', array() );
		}
		return self::$cached_settings;
	}

	/**
	 * Clear settings cache (call when settings might have changed).
	 */
	public static function clear_settings_cache() {
		self::$cached_settings = null;
	}

	/**
	 * Process a single queue item.
	 *
	 * Routes to appropriate handler based on item type:
	 * - No 'phase' key: link extraction task
	 * - 'phase' = 'check': link checking task
	 *
	 * @param array $item Queue item data.
	 * @return bool|array False to remove from queue, array to re-queue.
	 */
	protected function task( $item ) {
		// FAST CHECK: Emergency stop transient (set by cancel()).
		if ( get_transient( 'ablr_emergency_stop' ) ) {
			return false;
		}

		// Bail immediately if scan was cancelled or stopped by user.
		// Use direct DB query to bypass object cache (Redis/Memcached).
		global $wpdb;
		wp_cache_delete( 'ablr_scan_progress', 'options' );
		$raw_value = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$progress  = $raw_value ? maybe_unserialize( $raw_value ) : array();
		if ( isset( $progress['status'] ) && in_array( $progress['status'], array( 'cancelled', 'stopped' ), true ) ) {
			return false;
		}

		// Periodically refresh settings cache to pick up changes during long scans.
		++self::$task_counter;
		if ( 0 === self::$task_counter % self::SETTINGS_REFRESH_INTERVAL ) {
			self::clear_settings_cache();
		}

		if ( isset( $item['phase'] ) && 'check' === $item['phase'] ) {
			// Check current link.
			$this->task_check_link( $item );

			// Check additional links (LINKS_PER_TASK - 1 more).
			for ( $i = 1; $i < self::LINKS_PER_TASK; $i++ ) {
				// FAST CHECK: Emergency stop transient.
				if ( get_transient( 'ablr_emergency_stop' ) ) {
					return false;
				}

				// Re-check status before each additional link to respond quickly to pause.
				wp_cache_delete( 'ablr_scan_progress', 'options' );
				$raw_value = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$progress  = $raw_value ? maybe_unserialize( $raw_value ) : array();
				if ( isset( $progress['status'] ) && in_array( $progress['status'], array( 'cancelled', 'stopped' ), true ) ) {
					return false;
				}

				$next_link = ABLR_Database::get_next_pending_link();
				if ( $next_link ) {
					$this->task_check_link(
						array(
							'phase'   => 'check',
							'link_id' => $next_link->id,
						)
					);
				}
			}

			return false;
		}

		return $this->task_extract_links( $item );
	}

	/**
	 * Phase 1: Extract links from a content item and store them.
	 *
	 * Parses HTML content to find:
	 * 1. All <a href> links
	 * 2. Embedded videos (iframe src, Gutenberg embed blocks, oEmbed URLs)
	 *
	 * Stores all found links in the database for later checking.
	 *
	 * @param array $item Content item (post type and ID).
	 * @return bool Always false to remove from queue.
	 */
	private function task_extract_links( $item ) {
		$links = ABLR_Scanner::extract_links( $item );

		$cached_broken = 0;

		foreach ( $links as $link_data ) {
			$result = ABLR_Database::upsert_link( $link_data );
			if ( is_array( $result ) && ! empty( $result['cached'] ) && 'broken' === $result['cached_status'] ) {
				++$cached_broken;
			}
		}

		// Update progress counters.
		$progress                = get_option( 'ablr_scan_progress', array() );
		$progress['extracted']   = isset( $progress['extracted'] ) ? $progress['extracted'] + 1 : 1;
		$progress['links_found'] = isset( $progress['links_found'] ) ? $progress['links_found'] + count( $links ) : count( $links );
		update_option( 'ablr_scan_progress', $progress, false );

		// Log only if links were found (reduces log noise).
		if ( ! empty( $links ) ) {
			$details = sprintf( 'Extracted %d links from %s #%d', count( $links ), $item['type'], $item['id'] );
			if ( $cached_broken > 0 ) {
				$details .= sprintf( ' (%d broken from cache)', $cached_broken );
			}
			ABLR_Database::add_log( 0, '', $item['type'], $item['id'], 'scan_extract', $details );
		}

		return false;
	}

	/**
	 * Phase 2: Check a single link.
	 *
	 * For internal links: uses fast database-driven checker (no HTTP).
	 * For external links: uses HTTP checker with redirect/parking/soft-404 detection.
	 * If broken AND external: queries Wayback API for archive availability.
	 *
	 * @param array $item Queue item with link_id.
	 * @return bool Always false to remove from queue.
	 */
	private function task_check_link( $item ) {
		$link_id = $item['link_id'];
		$link    = ABLR_Database::get_link( $link_id );

		if ( ! $link || 'whitelisted' === $link->status ) {
			return false;
		}

		// Skip already-processed links (cache hit).
		if ( 'pending' !== $link->status ) {
			return false;
		}

		$is_internal = ! empty( $link->is_internal );

		// Internal links: use fast database-driven checker (no HTTP requests).
		// External links: use full HTTP checker with redirect/parking/soft-404 detection.
		if ( $is_internal ) {
			$result = ABLR_Internal_Checker::check_url( $link->url, $link_id );
		} else {
			$result = ABLR_Checker::check_url( $link->url, $link_id );
		}

		// Get cached settings once (avoids multiple get_option calls per link).
		$settings = self::get_settings();

		// If broken AND external — check Wayback availability.
		// Internal links do NOT get Wayback lookup (they're local content issues).
		// skip_wayback_* actions also skip the API call entirely for speed.
		if ( 'broken' === $result['status'] && ! $is_internal ) {
			$current_action = isset( $settings['auto_action'] ) ? $settings['auto_action'] : 'remove_link';
			$skip_wayback   = ( 'skip_wayback_remove_link' === $current_action || 'skip_wayback_remove_all' === $current_action );

			if ( ! $skip_wayback ) {
				// Get the post/page publication date to use as Wayback timestamp.
				// This increases the chance of finding a snapshot from when
				// the link was still alive and relevant.
				$post_date = '';
				if ( post_type_exists( $link->source_type ) ) {
					$post = get_post( $link->source_id );
					if ( $post && ! empty( $post->post_date ) && '0000-00-00 00:00:00' !== $post->post_date ) {
						$post_date = $post->post_date;
					}
				} elseif ( in_array( $link->source_type, array( 'comment', 'comment_author' ), true ) ) {
					$comment = get_comment( $link->source_id );
					if ( $comment && ! empty( $comment->comment_date ) && '0000-00-00 00:00:00' !== $comment->comment_date ) {
						$post_date = $comment->comment_date;
					}
				}

				// Build timestamp: use post date if available, otherwise fallback to 4 years ago.
				// Wayback Machine will redirect to the closest available snapshot.
				// Use wp_date() for correct timezone handling.
				$timestamp = ! empty( $post_date )
					? wp_date( 'YmdHis', strtotime( $post_date ) )
					: wp_date( 'YmdHis', strtotime( '-4 years' ) );
				$wayback   = ABLR_Checker::check_wayback( $link->url, $timestamp );

				$result['wayback_available'] = $wayback['available'];
				$result['wayback_url']       = $wayback['wayback_url'];
			}
		}

		// Update link record with check results.
		ABLR_Database::update_link_check( $link_id, $result );

		// Auto-fix if in automatic mode.
		if ( 'broken' === $result['status'] ) {
			if ( isset( $settings['auto_mode'] ) && 'auto' === $settings['auto_mode'] ) {
				// For internal links, only auto-fix if auto_fix_internal is enabled.
				if ( ! $is_internal || ! empty( $settings['auto_fix_internal'] ) ) {
					$this->auto_fix( $link_id, $link, $result, $settings, $is_internal );
				}
			}
		}

		// Update progress counters.
		$progress            = get_option( 'ablr_scan_progress', array() );
		$progress['checked'] = isset( $progress['checked'] ) ? $progress['checked'] + 1 : 1;
		if ( $is_internal ) {
			$progress['checked_internal'] = isset( $progress['checked_internal'] ) ? $progress['checked_internal'] + 1 : 1;
		}
		if ( 'broken' === $result['status'] ) {
			$progress['broken'] = isset( $progress['broken'] ) ? $progress['broken'] + 1 : 1;
		}
		update_option( 'ablr_scan_progress', $progress, false );

		return false;
	}

	/**
	 * Auto-fix a broken link based on settings.
	 *
	 * For internal links, Wayback is never used — go straight to fallback action.
	 * For skip_wayback_* actions, skip the Wayback replacement entirely for speed.
	 *
	 * @param int    $link_id     Link database ID.
	 * @param object $link        Link database record.
	 * @param array  $result      Check result with wayback_available flag.
	 * @param array  $settings    Plugin settings.
	 * @param bool   $is_internal Whether this is an internal link.
	 */
	private function auto_fix( $link_id, $link, $result, $settings, $is_internal = false ) {
		$auto_action = isset( $settings['auto_action'] ) ? $settings['auto_action'] : 'remove_link';

		// Skip Wayback actions — go straight to remove without checking archive.
		if ( 'skip_wayback_remove_link' === $auto_action ) {
			ABLR_Fixer::remove_link_keep_text( $link_id, true ); // is_auto = true.
			return;
		}
		if ( 'skip_wayback_remove_all' === $auto_action ) {
			ABLR_Fixer::remove_link_and_text( $link_id, true ); // is_auto = true.
			return;
		}

		// External links: try Wayback replacement first if archive is available.
		if ( ! $is_internal && ! empty( $result['wayback_available'] ) && ! empty( $result['wayback_url'] ) ) {
			// Pass the actual URL returned by the Wayback API (contains correct snapshot date).
			// Without this, replace_with_wayback rebuilds the URL from post_date which may
			// not match any snapshot.
			$replaced = ABLR_Fixer::replace_with_wayback( $link_id, $result['wayback_url'], true ); // is_auto = true.
			if ( $replaced ) {
				return;
			}
			// Wayback replacement failed (URL not found in content) — fall through to fallback action.
		}

		// Wayback not available, failed, or internal — apply fallback action.
		switch ( $auto_action ) {
			case 'replace_wayback':
			case 'remove_link':
				ABLR_Fixer::remove_link_keep_text( $link_id, true ); // is_auto = true.
				break;

			case 'remove_all':
				ABLR_Fixer::remove_link_and_text( $link_id, true ); // is_auto = true.
				break;
		}
	}

	/**
	 * Called when background processing is complete.
	 *
	 * Sets scan status to 'complete' unless user already cancelled/stopped.
	 * Logs final statistics.
	 */
	protected function complete() {
		parent::complete();

		$progress = get_option( 'ablr_scan_progress', array() );

		// Do not overwrite user-initiated cancel or stop.
		if ( isset( $progress['status'] ) && in_array( $progress['status'], array( 'extracting', 'cancelled', 'stopped' ), true ) ) {
			return;
		}

		$progress['status']       = 'complete';
		$progress['completed_at'] = current_time( 'mysql' );
		update_option( 'ablr_scan_progress', $progress, false );

		ABLR_Database::add_log(
			0,
			'',
			'',
			0,
			'scan_complete',
			sprintf(
				'Scan complete. Links found: %d, checked: %d (%d internal), broken: %d',
				isset( $progress['links_found'] ) ? $progress['links_found'] : 0,
				isset( $progress['checked'] ) ? $progress['checked'] : 0,
				isset( $progress['checked_internal'] ) ? $progress['checked_internal'] : 0,
				isset( $progress['broken'] ) ? $progress['broken'] : 0
			)
		);
	}
}
