<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Database management for Archivarix Broken Links Recovery.
 *
 * Handles all database operations:
 * - Custom tables creation (links, logs)
 * - Link record CRUD operations
 * - Log entries
 * - Statistics and counts
 *
 * Custom tables:
 * - {prefix}ablr_links: Stores discovered links with check results
 * - {prefix}ablr_logs: Activity log for all actions
 *
 * @package Archivarix_Broken_Links_Recovery
 */

// phpcs:disable WordPress.DB -- Database class legitimately uses direct queries.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Database
 *
 * Database operations handler for the plugin.
 */
class ABLR_Database {

	/**
	 * Get table name with prefix.
	 *
	 * @param string $name Table name without prefix.
	 * @return string Full table name.
	 */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'ablr_' . $name;
	}

	/**
	 * Create plugin tables.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$links_table = self::table( 'links' );
		$logs_table  = self::table( 'logs' );

		$sql = "CREATE TABLE {$links_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(2048) NOT NULL,
            url_hash char(32) NOT NULL,
            source_type varchar(20) NOT NULL DEFAULT 'post',
            source_id bigint(20) unsigned NOT NULL DEFAULT 0,
            anchor_text varchar(512) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'pending',
            http_code smallint(6) NOT NULL DEFAULT 0,
            redirect_url varchar(2048) NOT NULL DEFAULT '',
            fail_reason varchar(50) NOT NULL DEFAULT '',
            content_type varchar(128) NOT NULL DEFAULT '',
            wayback_available tinyint(1) NOT NULL DEFAULT 0,
            wayback_url varchar(2048) NOT NULL DEFAULT '',
            is_internal tinyint(1) NOT NULL DEFAULT 0,
            action_taken varchar(30) NOT NULL DEFAULT 'none',
            is_auto_fixed tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            checked_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY url_hash (url_hash),
            KEY status (status),
            KEY source_type_id (source_type, source_id),
            KEY action_taken (action_taken),
            KEY is_internal (is_internal),
            KEY status_internal (status, is_internal)
        ) {$charset};

        CREATE TABLE {$logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            link_id bigint(20) unsigned NOT NULL DEFAULT 0,
            url varchar(2048) NOT NULL DEFAULT '',
            source_type varchar(20) NOT NULL DEFAULT '',
            source_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action varchar(30) NOT NULL DEFAULT '',
            details text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id),
            KEY created_at (created_at)
        ) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Add missing indexes for existing installations.
		self::maybe_add_missing_indexes();

		// Add missing columns for existing installations.
		self::maybe_add_missing_columns();

		update_option( 'ablr_db_version', ABLR_VERSION );
	}

	/**
	 * Add missing indexes for performance optimization.
	 * This handles upgrades from older versions that didn't have these indexes.
	 */
	private static function maybe_add_missing_indexes() {
		global $wpdb;
		$table = self::table( 'links' );

		$indexes_to_add = array(
			'is_internal'        => '(is_internal)',
			'status_internal'    => '(status, is_internal)',
			'status_id'          => '(status, id)',                    // For status filter + id sort.
			'status_internal_id' => '(status, is_internal, id)',       // For full filter + sort.
		);

		foreach ( $indexes_to_add as $index_name => $index_columns ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.STATISTICS
                     WHERE table_schema = %s AND table_name = %s AND index_name = %s',
					DB_NAME,
					$table,
					$index_name
				)
			);

			if ( ! $exists ) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table/index names are hardcoded, not user input.
				$wpdb->query( "ALTER TABLE {$table} ADD INDEX {$index_name} {$index_columns}" );
			}
		}
	}

	/**
	 * Add missing columns for existing installations.
	 * This handles upgrades from older versions.
	 */
	private static function maybe_add_missing_columns() {
		global $wpdb;
		$table = self::table( 'links' );

		// Check if is_auto_fixed column exists.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from self::table().
		$column_exists = $wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE 'is_auto_fixed'" );

		if ( ! $column_exists ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name from self::table().
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN is_auto_fixed tinyint(1) NOT NULL DEFAULT 0 AFTER action_taken" );
		}
	}

	/**
	 * Insert or update a link record.
	 * Caching: links that were already checked (ok, broken, whitelisted)
	 * are not reset to pending — they keep their status across scans.
	 * Broken links (even unfixed) are cached to avoid re-checking known-broken URLs.
	 *
	 * @param array $data Link data to insert or update.
	 * @return array { id: int, cached: bool, cached_status: string|null }
	 */
	public static function upsert_link( $data ) {
		global $wpdb;
		$table = self::table( 'links' );

		$data['url_hash'] = md5( $data['url'] . $data['source_type'] . $data['source_id'] );
		$is_internal      = ! empty( $data['is_internal'] ) ? 1 : 0;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is from self::table().
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, status, action_taken, fail_reason FROM {$table} WHERE url_hash = %s",
				$data['url_hash']
			)
		);

		// Skip whitelisted links.
		if ( $existing && 'whitelisted' === $existing->status ) {
			return array(
				'id'            => $existing->id,
				'cached'        => true,
				'cached_status' => 'whitelisted',
			);
		}

		// Uncheckable links with timeout — always recheck (timeout likely due to server load).
		if ( $existing && 'uncheckable' === $existing->status && 'timeout' === $existing->fail_reason ) {
			$wpdb->update(
				$table,
				array(
					'url'         => $data['url'],
					'anchor_text' => isset( $data['anchor_text'] ) ? $data['anchor_text'] : '',
					'is_internal' => $is_internal,
					'status'      => 'pending',
					'http_code'   => 0,
					'fail_reason' => '',
					'checked_at'  => null,
					'updated_at'  => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);
			return array(
				'id'            => $existing->id,
				'cached'        => false,
				'cached_status' => null,
			);
		}

		// Cache: skip links that are already checked (ok or broken).
		// All checked links are preserved across scans to avoid redundant HTTP requests.
		//
		// EXCEPTIONS that trigger recheck:
		// 1) Internal 'ok' — DB check can produce false positives
		// 2) Internal 'broken' with action_taken='none' when auto_fix_internal is enabled —
		// allows auto-fix to run on links found before the setting was enabled
		//
		// External links (ok or broken) are always cached.
		if ( $existing && in_array( $existing->status, array( 'ok', 'broken' ), true ) ) {
			// Internal 'ok' — recheck (DB check may have been a false positive).
			if ( $is_internal && 'ok' === $existing->status ) {
				$wpdb->update(
					$table,
					array(
						'url'         => $data['url'],
						'anchor_text' => isset( $data['anchor_text'] ) ? $data['anchor_text'] : '',
						'is_internal' => $is_internal,
						'status'      => 'pending',
						'http_code'   => 0,
						'fail_reason' => '',
						'checked_at'  => null,
						'updated_at'  => current_time( 'mysql' ),
					),
					array( 'id' => $existing->id ),
					array( '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' ),
					array( '%d' )
				);
				return array(
					'id'            => $existing->id,
					'cached'        => false,
					'cached_status' => null,
				);
			}

			// Internal 'broken' with no action taken — recheck if auto_fix_internal is enabled.
			// This allows auto-fix to process links found before the setting was enabled.
			if ( $is_internal && 'broken' === $existing->status && 'none' === $existing->action_taken ) {
				$settings = get_option( 'ablr_settings', array() );
				if ( ! empty( $settings['auto_mode'] ) && 'auto' === $settings['auto_mode']
					&& ! empty( $settings['auto_fix_internal'] ) ) {
					$wpdb->update(
						$table,
						array(
							'url'         => $data['url'],
							'anchor_text' => isset( $data['anchor_text'] ) ? $data['anchor_text'] : '',
							'is_internal' => $is_internal,
							'status'      => 'pending',
							'http_code'   => 0,
							'fail_reason' => '',
							'checked_at'  => null,
							'updated_at'  => current_time( 'mysql' ),
						),
						array( 'id' => $existing->id ),
						array( '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' ),
						array( '%d' )
					);
					return array(
						'id'            => $existing->id,
						'cached'        => false,
						'cached_status' => null,
					);
				}
			}

			// All other cases (external ok/broken, internal broken already fixed) — keep cached.
			return array(
				'id'            => $existing->id,
				'cached'        => true,
				'cached_status' => $existing->status,
			);
		}

		if ( $existing ) {
			// Update existing record back to pending (e.g. was pending before).
			$wpdb->update(
				$table,
				array(
					'url'         => $data['url'],
					'anchor_text' => isset( $data['anchor_text'] ) ? $data['anchor_text'] : '',
					'is_internal' => $is_internal,
					'status'      => 'pending',
					'updated_at'  => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
			return array(
				'id'            => $existing->id,
				'cached'        => false,
				'cached_status' => null,
			);
		}

		$wpdb->insert(
			$table,
			array(
				'url'         => $data['url'],
				'url_hash'    => $data['url_hash'],
				'source_type' => $data['source_type'],
				'source_id'   => $data['source_id'],
				'anchor_text' => isset( $data['anchor_text'] ) ? $data['anchor_text'] : '',
				'is_internal' => $is_internal,
				'status'      => 'pending',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
		);

		return array(
			'id'            => $wpdb->insert_id,
			'cached'        => false,
			'cached_status' => null,
		);
	}

	/**
	 * Update link check results.
	 *
	 * @param int   $id   Link ID.
	 * @param array $data Check result data.
	 */
	public static function update_link_check( $id, $data ) {
		global $wpdb;
		$table = self::table( 'links' );

		$update = array(
			'status'     => $data['status'],
			'http_code'  => isset( $data['http_code'] ) ? $data['http_code'] : 0,
			'checked_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		if ( isset( $data['redirect_url'] ) ) {
			$update['redirect_url'] = $data['redirect_url'];
		}
		if ( isset( $data['fail_reason'] ) ) {
			$update['fail_reason'] = $data['fail_reason'];
		}
		if ( isset( $data['content_type'] ) ) {
			$update['content_type'] = $data['content_type'];
		}
		if ( isset( $data['wayback_available'] ) ) {
			$update['wayback_available'] = $data['wayback_available'] ? 1 : 0;
		}
		if ( isset( $data['wayback_url'] ) ) {
			$update['wayback_url'] = $data['wayback_url'];
		}

		$wpdb->update( $table, $update, array( 'id' => $id ) );

		delete_transient( 'ablr_status_counts' );
	}

	/**
	 * Update action taken on a link.
	 *
	 * @param int    $id      Link ID.
	 * @param string $action  Action taken.
	 * @param bool   $is_auto Whether this was an automatic fix (default false).
	 */
	public static function update_link_action( $id, $action, $is_auto = false ) {
		global $wpdb;
		$table = self::table( 'links' );

		$wpdb->update(
			$table,
			array(
				'action_taken'  => $action,
				'is_auto_fixed' => $is_auto ? 1 : 0,
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		delete_transient( 'ablr_status_counts' );
	}

	/**
	 * Set link status to whitelisted.
	 *
	 * @param int $id Link ID.
	 */
	public static function whitelist_link( $id ) {
		global $wpdb;
		$table = self::table( 'links' );

		$wpdb->update(
			$table,
			array(
				'status'       => 'whitelisted',
				'action_taken' => 'whitelisted',
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		delete_transient( 'ablr_status_counts' );
	}

	/**
	 * Remove link from whitelist (set back to pending for re-check).
	 *
	 * @param int $id Link ID.
	 */
	public static function unwhitelist_link( $id ) {
		global $wpdb;
		$table = self::table( 'links' );

		$wpdb->update(
			$table,
			array(
				'status'       => 'pending',
				'action_taken' => 'none',
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		delete_transient( 'ablr_status_counts' );
	}

	/**
	 * Add log entry.
	 *
	 * @param int    $link_id     Link ID.
	 * @param string $url         The URL.
	 * @param string $source_type Source type (post, page, etc.).
	 * @param int    $source_id   Source ID.
	 * @param string $action      Action performed.
	 * @param string $details     Additional details.
	 */
	public static function add_log( $link_id, $url, $source_type, $source_id, $action, $details ) {
		global $wpdb;
		$table = self::table( 'logs' );

		$wpdb->insert(
			$table,
			array(
				'link_id'     => $link_id,
				'url'         => $url,
				'source_type' => $source_type,
				'source_id'   => $source_id,
				'action'      => $action,
				'details'     => $details,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get links with filtering and pagination.
	 * Optimized: uses short-lived cache for count queries.
	 *
	 * @param array $args Query arguments.
	 * @return array Results with items, total, and pages.
	 */
	public static function get_links( $args = array() ) {
		global $wpdb;
		$table = self::table( 'links' );

		$defaults = array(
			'status'      => '',
			'per_page'    => 20,
			'page'        => 1,
			'orderby'     => 'id',
			'order'       => 'DESC',
			'search'      => '',
			'is_internal' => '',
		);

		$args   = wp_parse_args( $args, $defaults );
		$where  = array( '1=1' );
		$values = array();

		// Define fixed actions for reuse.
		$fixed_actions = array( 'replaced_wayback', 'removed_link', 'removed_text', 'replaced_custom' );

		if ( ! empty( $args['status'] ) ) {
			if ( 'fixed' === $args['status'] ) {
				// Fixed = links with fix action taken (any status).
				$placeholders = implode( ', ', array_fill( 0, count( $fixed_actions ), '%s' ) );
				$where[]      = "action_taken IN ({$placeholders})";
				$values       = array_merge( $values, $fixed_actions );
			} elseif ( 'broken' === $args['status'] ) {
				// Broken = all links with status broken (including fixed ones).
				$where[] = "status = 'broken'";
			} else {
				$where[]  = 'status = %s';
				$values[] = $args['status'];
			}
		}

		if ( '' !== $args['is_internal'] && in_array( $args['is_internal'], array( '0', '1' ), true ) ) {
			$where[]  = 'is_internal = %d';
			$values[] = (int) $args['is_internal'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(url LIKE %s OR anchor_text LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );

		$allowed_orderby = array( 'id', 'url', 'status', 'http_code', 'checked_at', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// per_page = 0 means show all, but limit to 500 for performance.
		$per_page = (int) $args['per_page'];

		if ( $per_page > 0 ) {
			$offset    = ( $args['page'] - 1 ) * $per_page;
			$limit_sql = $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
		} else {
			// "All" mode: cap at 500 to prevent browser/server timeouts.
			$limit_sql = ' LIMIT 500';
		}

		// Select only needed fields for performance.
		$fields = 'id, url, anchor_text, source_type, source_id, is_internal, status, http_code, redirect_url, fail_reason, wayback_available, wayback_url, action_taken, is_auto_fixed';

		// Build count cache key from query params.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Used only for cache key generation, not data storage.
		$cache_key = 'ablr_count_' . md5( $where_sql . serialize( $values ) );

		if ( ! empty( $values ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $fields/$table/$orderby/$order are hardcoded/whitelisted, $where_sql uses placeholders, $limit_sql is prepared.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$fields} FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}",
					$values
				) . $limit_sql
			);

			// Try to get count from cache (5 second cache to reduce DB load during rapid polling).
			$total = wp_cache_get( $cache_key, 'ablr' );
			if ( false === $total ) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Same as above.
				$total = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE {$where_sql}",
						$values
					)
				);
				wp_cache_set( $cache_key, $total, 'ablr', 5 );
			}
		} else {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			// $fields/$table/$orderby/$order are hardcoded/whitelisted, $where_sql is '1=1', $limit_sql is prepared or constant.
			$results = $wpdb->get_results(
				"SELECT {$fields} FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}" . $limit_sql
			);
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

			// Try to get count from cache.
			$total = wp_cache_get( $cache_key, 'ablr' );
			if ( false === $total ) {
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" );
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				wp_cache_set( $cache_key, $total, 'ablr', 5 );
			}
		}

		return array(
			'items' => $results,
			'total' => (int) $total,
			'pages' => $per_page > 0 ? (int) ceil( (int) $total / $per_page ) : 1,
		);
	}

	/**
	 * Get a single link by ID.
	 *
	 * @param int $id Link ID.
	 * @return object|null Link record or null if not found.
	 */
	public static function get_link( $id ) {
		global $wpdb;
		$table = self::table( 'links' );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get logs with pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array Results with items, total, and pages.
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;
		$table = self::table( 'logs' );

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'link_id'  => 0,
		);

		$args   = wp_parse_args( $args, $defaults );
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['link_id'] ) ) {
			$where[]  = 'link_id = %d';
			$values[] = $args['link_id'];
		}

		$where_sql = implode( ' AND ', $where );
		$offset    = ( $args['page'] - 1 ) * $args['per_page'];

		// Select only needed fields for performance.
		$fields = 'id, link_id, url, source_type, source_id, action, details, created_at';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $fields/$table are hardcoded, $where_sql uses placeholders.
		if ( ! empty( $values ) ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$fields} FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
					array_merge( $values, array( $args['per_page'], $offset ) )
				)
			);
			$total   = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE {$where_sql}",
					$values
				)
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$fields} FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
					$args['per_page'],
					$offset
				)
			);
			$total   = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$per_page = max( 1, (int) $args['per_page'] );

		return array(
			'items' => $results,
			'total' => (int) $total,
			'pages' => (int) ceil( (int) $total / $per_page ),
		);
	}

	/**
	 * Get counts by status for dashboard.
	 * Optimized: single query with detailed internal/external breakdown.
	 * Uses 10-second transient cache to reduce DB load on page loads.
	 *
	 * @param bool $force_refresh Skip cache and fetch fresh data.
	 * @return array Status counts.
	 */
	public static function get_status_counts( $force_refresh = false ) {
		$cache_key = 'ablr_status_counts';

		// Try cached value first (unless force refresh).
		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		global $wpdb;
		$table = self::table( 'links' );

		// Single query with conditional aggregation for all stats.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is from self::table().
		$row = $wpdb->get_row(
			"SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_internal = 1 THEN 1 ELSE 0 END) as total_internal,
                SUM(CASE WHEN is_internal = 0 THEN 1 ELSE 0 END) as total_external,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'pending' AND is_internal = 1 THEN 1 ELSE 0 END) as pending_internal,
                SUM(CASE WHEN status = 'pending' AND is_internal = 0 THEN 1 ELSE 0 END) as pending_external,
                SUM(CASE WHEN status = 'ok' THEN 1 ELSE 0 END) as ok,
                SUM(CASE WHEN status = 'ok' AND is_internal = 1 THEN 1 ELSE 0 END) as ok_internal,
                SUM(CASE WHEN status = 'ok' AND is_internal = 0 THEN 1 ELSE 0 END) as ok_external,
                SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) as broken,
                SUM(CASE WHEN status = 'broken' AND is_internal = 1 THEN 1 ELSE 0 END) as broken_internal,
                SUM(CASE WHEN status = 'broken' AND is_internal = 0 THEN 1 ELSE 0 END) as broken_external,
                SUM(CASE WHEN status = 'whitelisted' THEN 1 ELSE 0 END) as whitelisted,
                SUM(CASE WHEN status = 'whitelisted' AND is_internal = 1 THEN 1 ELSE 0 END) as whitelisted_internal,
                SUM(CASE WHEN status = 'whitelisted' AND is_internal = 0 THEN 1 ELSE 0 END) as whitelisted_external,
                SUM(CASE WHEN status = 'uncheckable' THEN 1 ELSE 0 END) as uncheckable,
                SUM(CASE WHEN status = 'uncheckable' AND is_internal = 1 THEN 1 ELSE 0 END) as uncheckable_internal,
                SUM(CASE WHEN status = 'uncheckable' AND is_internal = 0 THEN 1 ELSE 0 END) as uncheckable_external,
                SUM(CASE WHEN action_taken IN ('replaced_wayback', 'removed_link', 'removed_text', 'replaced_custom') THEN 1 ELSE 0 END) as fixed,
                SUM(CASE WHEN action_taken IN ('replaced_wayback', 'removed_link', 'removed_text', 'replaced_custom') AND is_internal = 1 THEN 1 ELSE 0 END) as fixed_internal,
                SUM(CASE WHEN action_taken IN ('replaced_wayback', 'removed_link', 'removed_text', 'replaced_custom') AND is_internal = 0 THEN 1 ELSE 0 END) as fixed_external
            FROM {$table}"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Calculate checked counts (total - pending).
		$checked_internal = (int) ( $row->total_internal ?? 0 ) - (int) ( $row->pending_internal ?? 0 );
		$checked_external = (int) ( $row->total_external ?? 0 ) - (int) ( $row->pending_external ?? 0 );

		$counts = array(
			'total'                => (int) ( $row->total ?? 0 ),
			'total_internal'       => (int) ( $row->total_internal ?? 0 ),
			'total_external'       => (int) ( $row->total_external ?? 0 ),
			'pending'              => (int) ( $row->pending ?? 0 ),
			'pending_internal'     => (int) ( $row->pending_internal ?? 0 ),
			'pending_external'     => (int) ( $row->pending_external ?? 0 ),
			'ok'                   => (int) ( $row->ok ?? 0 ),
			'ok_internal'          => (int) ( $row->ok_internal ?? 0 ),
			'ok_external'          => (int) ( $row->ok_external ?? 0 ),
			'broken'               => (int) ( $row->broken ?? 0 ),
			'broken_internal'      => (int) ( $row->broken_internal ?? 0 ),
			'broken_external'      => (int) ( $row->broken_external ?? 0 ),
			'whitelisted'          => (int) ( $row->whitelisted ?? 0 ),
			'whitelisted_internal' => (int) ( $row->whitelisted_internal ?? 0 ),
			'whitelisted_external' => (int) ( $row->whitelisted_external ?? 0 ),
			'uncheckable'          => (int) ( $row->uncheckable ?? 0 ),
			'uncheckable_internal' => (int) ( $row->uncheckable_internal ?? 0 ),
			'uncheckable_external' => (int) ( $row->uncheckable_external ?? 0 ),
			'fixed'                => (int) ( $row->fixed ?? 0 ),
			'fixed_internal'       => (int) ( $row->fixed_internal ?? 0 ),
			'fixed_external'       => (int) ( $row->fixed_external ?? 0 ),
			'checked_internal'     => max( 0, $checked_internal ),
			'checked_external'     => max( 0, $checked_external ),
		);

		// Cache for 10 seconds to reduce DB load.
		set_transient( $cache_key, $counts, 10 );

		return $counts;
	}

	/**
	 * Clear all data.
	 */
	public static function clear_all() {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'TRUNCATE TABLE ' . self::table( 'links' ) );
		$wpdb->query( 'TRUNCATE TABLE ' . self::table( 'logs' ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		delete_transient( 'ablr_status_counts' );
	}

	/**
	 * Get pending links for checking.
	 * Internal links are checked first (is_internal DESC) so that
	 * 404 results appear early when internal scanning is enabled.
	 *
	 * @param int $limit Maximum number of links to return.
	 * @return array Pending link records.
	 */
	public static function get_pending_links( $limit = 50 ) {
		global $wpdb;
		$table = self::table( 'links' );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY is_internal DESC, id ASC LIMIT %d", $limit ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get a single next pending link for processing.
	 *
	 * @return object|null Link record or null if none pending.
	 */
	public static function get_next_pending_link() {
		global $wpdb;
		$table = self::table( 'links' );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY is_internal DESC, id ASC LIMIT 1" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
