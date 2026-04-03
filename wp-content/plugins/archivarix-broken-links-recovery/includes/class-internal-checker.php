<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Hybrid internal link checker for Archivarix Broken Links Recovery.
 *
 * Uses a two-phase approach:
 * 1. Fast database-driven check (resolves ~90% of links without HTTP)
 * 2. HTTP verification only for links that DB check couldn't confirm
 *
 * This balances accuracy (catching plugin redirects, soft 404s) with
 * performance (avoiding server overload from mass HTTP requests).
 *
 * The DB check handles:
 * - Posts, pages, CPTs by path/slug
 * - Taxonomy archives
 * - Date/author archives
 * - Uploaded files
 * - System paths
 *
 * HTTP verification catches:
 * - Plugin-created redirects (WPML, Yoast, Polylang, Rank Math)
 * - Soft 404 pages
 * - Server-side redirects
 * - Dynamic URL handling
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Internal_Checker
 *
 * Hybrid internal link checker using DB and HTTP verification.
 */
class ABLR_Internal_Checker {

	/**
	 * Cache of check results within current request.
	 * Key: normalized URL, Value: check result array.
	 *
	 * @var array
	 */
	private static $url_cache = array();

	/**
	 * Transient cache expiration for HTTP check results (1 hour).
	 * This prevents repeated HTTP requests for the same URL across scans.
	 *
	 * @var int
	 */
	const HTTP_CACHE_EXPIRATION = 3600;

	/**
	 * Transient cache expiration for failed/broken results (15 minutes).
	 * Shorter expiration allows re-checking broken links sooner.
	 *
	 * @var int
	 */
	const HTTP_CACHE_EXPIRATION_ERROR = 900;

	/**
	 * Transient cache prefix for internal HTTP check results.
	 *
	 * @var string
	 */
	const HTTP_CACHE_PREFIX = 'ablr_int_';

	/**
	 * Option key for global HTTP throttling timestamp.
	 * Uses options table (not transients) for atomic updates.
	 *
	 * @var string
	 */
	const THROTTLE_OPTION_KEY = 'ablr_internal_http_last_request';

	/**
	 * Option key for global consecutive timeout counter.
	 * Shared across all AJAX processes for adaptive throttling.
	 *
	 * @var string
	 */
	const TIMEOUT_COUNTER_KEY = 'ablr_internal_timeout_count';

	/**
	 * Cached public post types to avoid repeated get_post_types() calls.
	 *
	 * @var array|null
	 */
	private static $cached_post_types = null;

	/**
	 * Timestamp of last HTTP request (for staggering).
	 *
	 * @var float
	 */
	private static $last_request_time = 0;

	/**
	 * Minimum delay between HTTP requests in milliseconds.
	 * Higher value = less server load but slower checking.
	 *
	 * @var int
	 */
	private static $request_delay_ms = 5000;

	/**
	 * Counter for HTTP requests in current batch.
	 *
	 * @var int
	 */
	private static $http_request_count = 0;

	/**
	 * Maximum HTTP requests per batch before forcing a longer pause.
	 *
	 * @var int
	 */
	private static $max_requests_per_burst = 1;

	/**
	 * Longer pause after max_requests_per_burst (milliseconds).
	 *
	 * @var int
	 */
	private static $burst_pause_ms = 3000;

	/**
	 * Counter for consecutive timeouts (for adaptive throttling).
	 *
	 * @var int
	 */
	private static $consecutive_timeouts = 0;

	/**
	 * Paths that always exist (no check needed).
	 *
	 * @var array
	 */
	private static $always_ok_paths = array(
		'wp-admin',
		'wp-login.php',
		'wp-cron.php',
		'xmlrpc.php',
		'wp-json',
		'feed',
		'rss',
		'atom',
		'sitemap',
		'robots.txt',
		'favicon.ico',
	);

	/**
	 * Check an internal URL using hybrid approach.
	 *
	 * @param string $url      Full absolute URL.
	 * @param int    $link_id  Link record ID for logging.
	 *
	 * @return array Result compatible with ABLR_Checker::check_url() format.
	 */
	public static function check_url( $url, $link_id = 0 ) {
		$result = array(
			'status'            => 'ok',
			'http_code'         => 200,
			'redirect_url'      => '',
			'fail_reason'       => '',
			'content_type'      => 'text/html',
			'wayback_available' => false,
			'wayback_url'       => '',
		);

		// Normalize URL for caching.
		$normalized_url = self::normalize_url( $url );

		// Check cache first.
		if ( isset( self::$url_cache[ $normalized_url ] ) ) {
			return self::$url_cache[ $normalized_url ];
		}

		// Search URLs always exist on WordPress (/?s=query or /search/query).
		$query_string = wp_parse_url( $url, PHP_URL_QUERY );
		if ( $query_string && preg_match( '/(?:^|&)s=/', $query_string ) ) {
			self::$url_cache[ $normalized_url ] = $result;
			ABLR_Database::add_log( $link_id, $url, '', 0, 'check_ok_fast', 'Internal OK (search URL)' );
			return $result;
		}

		// Extract path for checks.
		$path = self::extract_path( $url );

		// === PHASE 1: Quick checks (no DB, no HTTP) ===

		// Home page always exists.
		if ( '' === $path || '/' === $path ) {
			self::$url_cache[ $normalized_url ] = $result;
			ABLR_Database::add_log( $link_id, $url, '', 0, 'check_ok_fast', 'Internal OK (home)' );
			return $result;
		}

		// Search path format (/search/query/).
		if ( preg_match( '/^search\//i', $path ) ) {
			self::$url_cache[ $normalized_url ] = $result;
			ABLR_Database::add_log( $link_id, $url, '', 0, 'check_ok_fast', 'Internal OK (search URL)' );
			return $result;
		}

		// System paths always exist.
		if ( self::is_always_ok_path( $path ) ) {
			self::$url_cache[ $normalized_url ] = $result;
			ABLR_Database::add_log( $link_id, $url, '', 0, 'check_ok_fast', 'Internal OK (system path)' );
			return $result;
		}

		// Uploaded files — check filesystem directly.
		if ( self::check_uploaded_file( $path ) ) {
			self::$url_cache[ $normalized_url ] = $result;
			ABLR_Database::add_log( $link_id, $url, '', 0, 'check_ok_fast', 'Internal OK (file exists)' );
			return $result;
		}

		// === PHASE 2: Database-driven check ===
		$db_match = self::resolve_path_via_db( $path );

		if ( false !== $db_match ) {
			// DB found matching content — mark as OK without HTTP.
			self::$url_cache[ $normalized_url ] = $result;
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_ok_db',
				sprintf( 'Internal OK (%s) [DB check]', $db_match )
			);
			return $result;
		}

		// === PHASE 3: HTTP verification for unresolved links ===
		// DB couldn't confirm this URL exists. Use HTTP to check.
		// This catches: plugin redirects, soft 404s, dynamic URLs.

		self::apply_request_delay();

		$http_result                        = self::http_check( $url, $link_id );
		self::$url_cache[ $normalized_url ] = $http_result;

		return $http_result;
	}

	/**
	 * Resolve path via WordPress database queries.
	 *
	 * @param string $path Path without leading/trailing slashes.
	 *
	 * @return string|false Description of match, or false if not found.
	 */
	private static function resolve_path_via_db( $path ) {
		// 1. Feed URLs.
		if ( self::is_feed_path( $path ) ) {
			return 'feed URL';
		}

		// 2. Strip pagination suffix.
		$path = self::strip_pagination( $path );
		if ( '' === $path ) {
			return 'paginated home';
		}

		// 3. CPT archive pages (e.g., /products/, /portfolio/).
		$cpt_archive = self::match_cpt_archive( $path );
		if ( $cpt_archive ) {
			return $cpt_archive;
		}

		// 3b. CPT single posts by rewrite slug (e.g., /portfolio/post-slug/).
		$cpt_single = self::match_cpt_single_post( $path );
		if ( $cpt_single ) {
			return $cpt_single;
		}

		// 4. Post/page/CPT by path.
		$post_match = self::match_post_by_path( $path );
		if ( $post_match ) {
			return $post_match;
		}

		// 5. Taxonomy archive.
		$tax_match = self::match_taxonomy_archive( $path );
		if ( $tax_match ) {
			return $tax_match;
		}

		// 6. Date archive.
		if ( self::is_date_archive( $path ) ) {
			return 'date archive';
		}

		// 7. Author archive.
		$author_match = self::match_author_archive( $path );
		if ( $author_match ) {
			return $author_match;
		}

		// 8. Attachment pages.
		$attachment_match = self::match_attachment( $path );
		if ( $attachment_match ) {
			return $attachment_match;
		}

		return false;
	}

	/**
	 * Perform HTTP check on internal URL.
	 *
	 * Called only when DB check couldn't confirm the URL exists.
	 *
	 * @param string $url     The URL to check.
	 * @param int    $link_id Link record ID for logging.
	 *
	 * @return array Check result.
	 */
	private static function http_check( $url, $link_id ) {
		$result = array(
			'status'            => 'ok',
			'http_code'         => 200,
			'redirect_url'      => '',
			'fail_reason'       => '',
			'content_type'      => 'text/html',
			'wayback_available' => false,
			'wayback_url'       => '',
		);

		// Check transient cache first.
		$cached = self::get_http_cached_result( $url );
		if ( false !== $cached ) {
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_cached_http',
				sprintf( 'Internal HTTP cached: %s (%d)', $cached['status'], $cached['http_code'] )
			);
			return $cached;
		}

		$settings = get_option( 'ablr_settings', array() );

		// Timeout for internal requests (10 seconds to allow slower servers to respond).
		$timeout = min( 10, isset( $settings['request_timeout'] ) ? (int) $settings['request_timeout'] : 15 );

		$user_agent = isset( $settings['user_agent'] ) && ! empty( $settings['user_agent'] )
						? $settings['user_agent']
						: 'Mozilla/5.0 (compatible; ArchivarixBot)';

		// Use HEAD request (minimal data transfer).
		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 5,
				'sslverify'   => false,
				'user-agent'  => $user_agent,
			)
		);

		++self::$http_request_count;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			// Timeout likely means server overload — mark as uncheckable for recheck.
			// Don't cache timeouts — they should be retried.
			if ( strpos( strtolower( $error_message ), 'timeout' ) !== false ||
				strpos( strtolower( $error_message ), 'timed out' ) !== false ) {
				$result['status']      = 'uncheckable';
				$result['fail_reason'] = 'timeout';

				// Adaptive throttling: increase GLOBAL timeout counter.
				++self::$consecutive_timeouts;
				$global_timeouts = self::increment_global_timeout_counter();

				// Use the higher of local or global counter for delay calculation.
				$timeout_count = max( self::$consecutive_timeouts, $global_timeouts );

				if ( $timeout_count >= 2 ) {
					// Add extra delay proportional to timeout count (max 15 seconds extra).
					$extra_delay = min( $timeout_count * 3000, 15000 );
					usleep( $extra_delay * 1000 );
					ABLR_Database::add_log(
						$link_id,
						$url,
						'',
						0,
						'check_timeout_internal',
						sprintf( 'Internal timeout (global: %d), added %dms delay: %s', $global_timeouts, $extra_delay, $error_message )
					);
				} else {
					ABLR_Database::add_log(
						$link_id,
						$url,
						'',
						0,
						'check_timeout_internal',
						sprintf( 'Internal link timeout (will recheck): %s', $error_message )
					);
				}

				return $result;
			}

			$result['status']      = 'broken';
			$result['fail_reason'] = self::classify_error( $error_message );

			// Cache error result.
			self::cache_http_result( $url, $result );

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_error_internal',
				sprintf( 'Internal link error: %s', $error_message )
			);

			return $result;
		}

		$http_code    = wp_remote_retrieve_response_code( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		$result['http_code']    = $http_code;
		$result['content_type'] = $content_type ? $content_type : '';

		// Reset timeout counters — server responded (even with error).
		self::$consecutive_timeouts = 0;
		self::reset_global_timeout_counter();

		// 4xx/5xx = broken.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;

			// Cache broken result.
			self::cache_http_result( $url, $result );

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_broken_internal',
				sprintf( 'Internal HTTP %d', $http_code )
			);

			return $result;
		}

		// Check for suspicious redirects.
		$redirect_result = self::analyze_redirect( $url, $response );
		if ( ! empty( $redirect_result['redirect_url'] ) ) {
			$result['redirect_url'] = $redirect_result['redirect_url'];
		}

		if ( ! empty( $redirect_result['is_broken'] ) ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = $redirect_result['reason'];

			// Cache broken redirect result.
			self::cache_http_result( $url, $result );

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_redirect_internal',
				sprintf( 'Internal redirect issue: %s', $redirect_result['reason'] )
			);

			return $result;
		}

		// All good — cache successful result.
		self::cache_http_result( $url, $result );

		ABLR_Database::add_log(
			$link_id,
			$url,
			'',
			0,
			'check_ok_http',
			sprintf( 'Internal OK (HTTP %d)', $http_code )
		);

		return $result;
	}

	/**
	 * Analyze redirect for internal links.
	 *
	 * @param string $original_url Original URL.
	 * @param array  $response     wp_remote response.
	 *
	 * @return array { redirect_url, is_broken, reason }
	 */
	private static function analyze_redirect( $original_url, $response ) {
		$result = array(
			'redirect_url' => '',
			'is_broken'    => false,
			'reason'       => '',
		);

		// Try to get final URL.
		$final_url = '';
		if ( isset( $response['http_response'] ) && is_object( $response['http_response'] ) ) {
			$final_url_obj = $response['http_response']->get_response_object();
			if ( $final_url_obj && isset( $final_url_obj->url ) ) {
				$final_url = $final_url_obj->url;
			}
		}

		if ( empty( $final_url ) || self::urls_match( $original_url, $final_url ) ) {
			return $result;
		}

		$result['redirect_url'] = $final_url;

		// Check for cross-domain redirect.
		$orig_host  = wp_parse_url( $original_url, PHP_URL_HOST );
		$final_host = wp_parse_url( $final_url, PHP_URL_HOST );

		$orig_host  = preg_replace( '/^www\./i', '', $orig_host ? $orig_host : '' );
		$final_host = preg_replace( '/^www\./i', '', $final_host ? $final_host : '' );

		if ( strtolower( $final_host ) !== strtolower( $orig_host ) ) {
			$result['is_broken'] = true;
			$result['reason']    = 'redirect_different_domain';
			return $result;
		}

		// Check for redirect to home when original was a specific page.
		$orig_path  = wp_parse_url( $original_url, PHP_URL_PATH );
		$orig_path  = $orig_path ? $orig_path : '';
		$final_path = wp_parse_url( $final_url, PHP_URL_PATH );
		$final_path = $final_path ? $final_path : '';

		if ( $orig_path && '/' !== $orig_path && ( '' === $final_path || '/' === $final_path ) ) {
			$result['is_broken'] = true;
			$result['reason']    = 'redirect_to_home';
			return $result;
		}

		return $result;
	}

	/**
	 * Apply staggered delay between HTTP requests.
	 * Uses database option for GLOBAL throttling across all AJAX requests.
	 */
	private static function apply_request_delay() {
		global $wpdb;

		// Check global timeout counter — if server is overloaded, add extra pre-delay.
		$global_timeouts = self::get_global_timeout_counter();
		if ( $global_timeouts >= 2 ) {
			// Server is struggling — add proportional delay before even trying.
			$overload_delay = min( $global_timeouts * 2000, 10000 );
			usleep( $overload_delay * 1000 );
		}

		// After burst of requests within this process, take a longer pause.
		if ( self::$http_request_count > 0 && 0 === self::$http_request_count % self::$max_requests_per_burst ) {
			usleep( self::$burst_pause_ms * 1000 );
		}

		// Global throttling: check last HTTP request time across ALL processes.
		// Use direct DB query for atomic read to avoid caching issues.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic read for throttling.
		$last_global = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::THROTTLE_OPTION_KEY
			)
		);

		$now = microtime( true );

		if ( $last_global ) {
			$last_global = (float) $last_global;
			$elapsed_ms  = ( $now - $last_global ) * 1000;

			if ( $elapsed_ms < self::$request_delay_ms ) {
				$sleep_ms = self::$request_delay_ms - $elapsed_ms;
				// Cap max sleep at 10 seconds to prevent infinite waits.
				$sleep_ms = min( $sleep_ms, 10000 );
				usleep( (int) ( $sleep_ms * 1000 ) );
				$now = microtime( true );
			}
		}

		// Update global timestamp atomically.
		// Use REPLACE for atomic upsert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic upsert for throttling.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                 VALUES (%s, %s, 'no')
                 ON DUPLICATE KEY UPDATE option_value = %s",
				self::THROTTLE_OPTION_KEY,
				$now,
				$now
			)
		);

		// Also update local tracker.
		self::$last_request_time = $now;
	}

	// =========================================================================
	// Database Resolution Methods
	// =========================================================================

	/**
	 * Get public post types with caching.
	 *
	 * @return array
	 */
	private static function get_public_post_types() {
		if ( null === self::$cached_post_types ) {
			self::$cached_post_types = get_post_types( array( 'public' => true ), 'names' );
		}
		return self::$cached_post_types;
	}

	/**
	 * Match path against posts/pages/CPTs.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return string|false
	 */
	private static function match_post_by_path( $path ) {
		$post_types = self::get_public_post_types();

		// Strip common extensions.
		$clean_path = preg_replace( '/\.(html?|php|aspx?)$/i', '', $path );

		// Try exact path.
		$post = get_page_by_path( $clean_path, OBJECT, $post_types );
		if ( $post && 'publish' === $post->post_status ) {
			if ( self::permalink_matches_path( $post, $path ) ) {
				return sprintf( '%s: "%s"', $post->post_type, $post->post_title );
			}
		}

		// Try with original path.
		if ( $clean_path !== $path ) {
			$post = get_page_by_path( $path, OBJECT, $post_types );
			if ( $post && 'publish' === $post->post_status ) {
				if ( self::permalink_matches_path( $post, $path ) ) {
					return sprintf( '%s: "%s"', $post->post_type, $post->post_title );
				}
			}
		}

		// Try last segment (for /category/post-slug structures).
		$segments = explode( '/', $path );
		if ( count( $segments ) > 1 ) {
			$last_segment = end( $segments );
			$last_clean   = preg_replace( '/\.(html?|php|aspx?)$/i', '', $last_segment );

			if ( ! empty( $last_clean ) ) {
				$post = get_page_by_path( $last_clean, OBJECT, $post_types );
				if ( $post && 'publish' === $post->post_status ) {
					if ( self::permalink_matches_path( $post, $path ) ) {
						return sprintf( '%s: "%s"', $post->post_type, $post->post_title );
					}
				}
			}
		}

		// Try url_to_postid as fallback.
		$post_id = url_to_postid( home_url( '/' . $path ) );
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( $post && 'publish' === $post->post_status ) {
				if ( self::permalink_matches_path( $post, $path ) ) {
					return sprintf( '%s: "%s"', $post->post_type, $post->post_title );
				}
			}
		}

		return false;
	}

	/**
	 * Check if post's permalink matches the given path.
	 *
	 * @param WP_Post $post Post object.
	 * @param string  $path Path being checked.
	 *
	 * @return bool
	 */
	private static function permalink_matches_path( $post, $path ) {
		$permalink = get_permalink( $post );
		if ( ! $permalink ) {
			return false;
		}

		$permalink_path = wp_parse_url( $permalink, PHP_URL_PATH );
		if ( null === $permalink_path ) {
			return false;
		}
		$permalink_path = trim( $permalink_path, '/' );

		// IMPORTANT: Check extension mismatch FIRST.
		// If requested path has .html but real permalink doesn't, they are different URLs.
		// This catches cases like "links.html" when the real page is just "links".
		$path_has_html = preg_match( '/\.html?$/i', $path );
		$perm_has_html = preg_match( '/\.html?$/i', $permalink_path );

		if ( $path_has_html && ! $perm_has_html ) {
			// Requested URL has .html extension but actual permalink doesn't.
			// These are different URLs — the .html version likely doesn't exist.
			return false;
		}

		if ( ! $path_has_html && $perm_has_html ) {
			// Requested URL doesn't have .html but actual permalink does.
			// These are different URLs.
			return false;
		}

		// Direct match.
		if ( $permalink_path === $path ) {
			return true;
		}

		// Match with trailing slash variance.
		if ( rtrim( $permalink_path, '/' ) === rtrim( $path, '/' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Match taxonomy archive.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return string|false
	 */
	private static function match_taxonomy_archive( $path ) {
		$segments   = explode( '/', $path );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		foreach ( $taxonomies as $tax ) {
			$rewrite_base = '';

			if ( ! empty( $tax->rewrite['slug'] ) ) {
				$rewrite_base = trim( $tax->rewrite['slug'], '/' );
			} elseif ( 'category' === $tax->name ) {
				$cat_base     = get_option( 'category_base' );
				$rewrite_base = ! empty( $cat_base ) ? trim( $cat_base, '/' ) : 'category';
			} elseif ( 'post_tag' === $tax->name ) {
				$tag_base     = get_option( 'tag_base' );
				$rewrite_base = ! empty( $tag_base ) ? trim( $tag_base, '/' ) : 'tag';
			}

			if ( empty( $rewrite_base ) ) {
				continue;
			}

			if ( stripos( $path, $rewrite_base . '/' ) === 0 ) {
				$term_slug  = substr( $path, strlen( $rewrite_base . '/' ) );
				$term_slug  = trim( $term_slug, '/' );
				$term_parts = explode( '/', $term_slug );
				$last_slug  = end( $term_parts );

				if ( ! empty( $last_slug ) ) {
					$term = get_term_by( 'slug', $last_slug, $tax->name );
					if ( $term && ! is_wp_error( $term ) ) {
						return sprintf( '%s archive: "%s"', $tax->labels->singular_name, $term->name );
					}
				}
			}
		}

		// Try direct category match (empty category base).
		$cat_base = get_option( 'category_base' );
		if ( empty( $cat_base ) || '.' === $cat_base ) {
			$term = get_term_by( 'slug', $segments[0], 'category' );
			if ( $term && ! is_wp_error( $term ) ) {
				return sprintf( 'category archive: "%s"', $term->name );
			}
		}

		return false;
	}

	/**
	 * Match author archive.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return string|false
	 */
	private static function match_author_archive( $path ) {
		$author_base = get_option( 'author_base', 'author' );
		if ( empty( $author_base ) ) {
			$author_base = 'author';
		}

		if ( stripos( $path, $author_base . '/' ) === 0 ) {
			$author_slug = substr( $path, strlen( $author_base . '/' ) );
			$author_slug = trim( $author_slug, '/' );
			$author_slug = preg_replace( '/\/page\/\d+$/', '', $author_slug );

			if ( ! empty( $author_slug ) ) {
				$user = get_user_by( 'slug', $author_slug );
				if ( $user ) {
					return sprintf( 'author archive: "%s"', $user->display_name );
				}
			}
		}

		return false;
	}

	/**
	 * Check if path is a date archive.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return bool
	 */
	private static function is_date_archive( $path ) {
		if ( preg_match( '/^(\d{4})(\/\d{2})?(\/\d{2})?$/', $path, $m ) ) {
			$year = (int) $m[1];
			return $year >= 1970 && $year <= 2100;
		}
		return false;
	}

	/**
	 * Match CPT archive pages (e.g., /products/, /portfolio/).
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return string|false
	 */
	private static function match_cpt_archive( $path ) {
		$post_types = get_post_types(
			array(
				'public'      => true,
				'has_archive' => true,
			),
			'objects'
		);

		foreach ( $post_types as $pt ) {
			$archive_slug = '';

			// Get archive slug from rewrite or post type name.
			if ( ! empty( $pt->rewrite['slug'] ) ) {
				$archive_slug = trim( $pt->rewrite['slug'], '/' );
			} elseif ( true === $pt->has_archive ) {
				$archive_slug = $pt->name;
			} elseif ( is_string( $pt->has_archive ) ) {
				$archive_slug = $pt->has_archive;
			}

			if ( empty( $archive_slug ) ) {
				continue;
			}

			// Exact match or match with pagination.
			if ( $path === $archive_slug || strpos( $path, $archive_slug . '/' ) === 0 ) {
				return sprintf( '%s archive', $pt->labels->name );
			}
		}

		return false;
	}

	/**
	 * Match CPT single posts by rewrite slug structure.
	 * Handles URLs like /portfolio/post-slug/ where 'portfolio' is CPT rewrite slug.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return string|false
	 */
	private static function match_cpt_single_post( $path ) {
		global $wpdb;

		$segments = explode( '/', $path );
		if ( count( $segments ) < 2 ) {
			return false;
		}

		// Get all public CPTs with their rewrite info.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $pt ) {
			// Skip built-in types (handled by other methods).
			if ( in_array( $pt->name, array( 'post', 'page', 'attachment' ), true ) ) {
				continue;
			}

			// Get rewrite slug for this CPT.
			$rewrite_slug = '';
			if ( ! empty( $pt->rewrite['slug'] ) ) {
				$rewrite_slug = trim( $pt->rewrite['slug'], '/' );
			} else {
				// Default: post type name.
				$rewrite_slug = $pt->name;
			}

			if ( empty( $rewrite_slug ) ) {
				continue;
			}

			// Check if path starts with this CPT's rewrite slug.
			if ( stripos( $path, $rewrite_slug . '/' ) !== 0 ) {
				continue;
			}

			// Extract post slug (everything after rewrite slug).
			$post_path = substr( $path, strlen( $rewrite_slug . '/' ) );
			$post_path = trim( $post_path, '/' );

			if ( empty( $post_path ) ) {
				continue;
			}

			// Handle hierarchical CPTs (parent/child structure).
			$path_segments = explode( '/', $post_path );
			$post_slug     = end( $path_segments );

			// Clean extension from slug.
			$post_slug = preg_replace( '/\.(html?|php|aspx?)$/i', '', $post_slug );

			if ( empty( $post_slug ) ) {
				continue;
			}

			// Search for post by slug in this CPT.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time lookup during link checking.
			$post = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT ID, post_title, post_status FROM {$wpdb->posts}
                     WHERE post_name = %s AND post_type = %s AND post_status = 'publish'
                     LIMIT 1",
					$post_slug,
					$pt->name
				)
			);

			if ( $post ) {
				return sprintf( '%s: "%s"', $pt->labels->singular_name, $post->post_title );
			}

			// Try with decoded slug (for URLs with special chars).
			$decoded_slug = urldecode( $post_slug );
			if ( $post_slug !== $decoded_slug ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time lookup during link checking.
				$post = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT ID, post_title, post_status FROM {$wpdb->posts}
                         WHERE post_name = %s AND post_type = %s AND post_status = 'publish'
                         LIMIT 1",
						$decoded_slug,
						$pt->name
					)
				);

				if ( $post ) {
					return sprintf( '%s: "%s"', $pt->labels->singular_name, $post->post_title );
				}
			}
		}

		return false;
	}

	/**
	 * Match attachment pages.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return string|false
	 */
	private static function match_attachment( $path ) {
		global $wpdb;

		// Try to find attachment by post_name (slug).
		$segments     = explode( '/', $path );
		$last_segment = end( $segments );

		if ( empty( $last_segment ) ) {
			return false;
		}

		// Clean extension from filename for matching.
		$slug = preg_replace( '/\.[a-z0-9]+$/i', '', $last_segment );

		// Look for attachment with this slug.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time lookup during link checking.
		$attachment = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
                 WHERE post_type = 'attachment'
                 AND (post_name = %s OR post_name = %s)
                 LIMIT 1",
				$slug,
				$last_segment
			)
		);

		if ( $attachment ) {
			return 'attachment page';
		}

		return false;
	}

	// =========================================================================
	// Helper Methods
	// =========================================================================

	/**
	 * Extract path from URL.
	 *
	 * @param string $url Full URL.
	 *
	 * @return string Path relative to site root.
	 */
	private static function extract_path( $url ) {
		$url = strtok( $url, '?#' );

		$site_url        = rtrim( home_url(), '/' );
		$site_url_no_www = preg_replace( '/^(https?:\/\/)www\./i', '$1', $site_url );
		$url_no_www      = preg_replace( '/^(https?:\/\/)www\./i', '$1', $url );

		if ( stripos( $url_no_www, $site_url_no_www ) === 0 ) {
			$path = substr( $url_no_www, strlen( $site_url_no_www ) );
		} else {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( null === $path ) {
				$path = '';
			}
		}

		return trim( $path, '/' );
	}

	/**
	 * Normalize URL for comparison/caching.
	 *
	 * @param string $url URL to normalize.
	 *
	 * @return string Normalized URL.
	 */
	private static function normalize_url( $url ) {
		$url = preg_replace( '/^https?:\/\//i', '', $url );
		$url = preg_replace( '/^www\./i', '', $url );
		$url = rtrim( $url, '/' );
		return strtolower( $url );
	}

	/**
	 * Compare two URLs.
	 *
	 * @param string $url1 First URL.
	 * @param string $url2 Second URL.
	 *
	 * @return bool True if essentially the same.
	 */
	private static function urls_match( $url1, $url2 ) {
		return self::normalize_url( $url1 ) === self::normalize_url( $url2 );
	}

	/**
	 * Check if path is always OK.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return bool
	 */
	private static function is_always_ok_path( $path ) {
		$path_lower = strtolower( $path );

		foreach ( self::$always_ok_paths as $ok_path ) {
			if ( $path_lower === $ok_path || strpos( $path_lower, $ok_path . '/' ) === 0 ) {
				return true;
			}
		}

		if ( preg_match( '/^wp-content\/(plugins|themes|mu-plugins)\//i', $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if path is a feed URL.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return bool
	 */
	private static function is_feed_path( $path ) {
		$feed_patterns = array( 'feed', 'rss', 'rss2', 'atom', 'rdf' );
		$segments      = explode( '/', $path );
		return in_array( end( $segments ), $feed_patterns, true );
	}

	/**
	 * Strip pagination suffix.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return string
	 */
	private static function strip_pagination( $path ) {
		$path = preg_replace( '/(\/|^)page\/\d+$/', '', $path );
		return trim( $path, '/' );
	}

	/**
	 * Check if path points to existing uploaded file.
	 *
	 * @param string $path Path without slashes.
	 *
	 * @return bool
	 */
	private static function check_uploaded_file( $path ) {
		$upload_dir  = wp_get_upload_dir();
		$upload_base = trim( str_replace( ABSPATH, '', $upload_dir['basedir'] ), '/' );

		if ( stripos( $path, $upload_base . '/' ) === 0 ) {
			return file_exists( ABSPATH . $path );
		}

		return false;
	}

	/**
	 * Increment global timeout counter.
	 * Shared across all AJAX processes.
	 *
	 * @return int New counter value.
	 */
	private static function increment_global_timeout_counter() {
		global $wpdb;

		// Atomic increment using SQL.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic counter increment.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                 VALUES (%s, 1, 'no')
                 ON DUPLICATE KEY UPDATE option_value = option_value + 1",
				self::TIMEOUT_COUNTER_KEY
			)
		);

		// Get current value.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic read after increment.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::TIMEOUT_COUNTER_KEY
			)
		);

		return (int) $count;
	}

	/**
	 * Reset global timeout counter.
	 * Called when server responds successfully.
	 */
	private static function reset_global_timeout_counter() {
		delete_option( self::TIMEOUT_COUNTER_KEY );
	}

	/**
	 * Get current global timeout counter value.
	 *
	 * @return int Current counter value.
	 */
	private static function get_global_timeout_counter() {
		return (int) get_option( self::TIMEOUT_COUNTER_KEY, 0 );
	}

	/**
	 * Classify connection error.
	 *
	 * @param string $message Error message.
	 *
	 * @return string Reason code.
	 */
	private static function classify_error( $message ) {
		$message = strtolower( $message );

		if ( strpos( $message, 'timed out' ) !== false || strpos( $message, 'timeout' ) !== false ) {
			return 'timeout';
		}
		if ( strpos( $message, 'could not resolve' ) !== false || strpos( $message, 'dns' ) !== false ) {
			return 'dns_failure';
		}
		if ( strpos( $message, 'connection refused' ) !== false ) {
			return 'connection_refused';
		}
		if ( strpos( $message, 'ssl' ) !== false || strpos( $message, 'certificate' ) !== false ) {
			return 'ssl_error';
		}
		if ( strpos( $message, 'reset' ) !== false ) {
			return 'connection_reset';
		}

		return 'connection_error';
	}

	// =========================================================================
	// Batch Processing
	// =========================================================================

	/**
	 * Batch-check ALL pending internal links.
	 *
	 * Uses hybrid approach:
	 * 1. Group links by normalized URL
	 * 2. For each unique URL: try DB check first, HTTP only if needed
	 * 3. Staggered HTTP requests with burst pauses
	 *
	 * @param int $time_limit Maximum seconds to spend.
	 *
	 * @return array { checked, broken, ok, http_requests }
	 */
	public static function batch_check_pending( $time_limit = 30 ) {
		global $wpdb;
		$table = ABLR_Database::table( 'links' );
		$start = microtime( true );

		// Reset state.
		self::$url_cache          = array();
		self::$last_request_time  = 0;
		self::$http_request_count = 0;

		// Get all pending internal links.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$links = $wpdb->get_results(
			"SELECT id, url, source_type, source_id
             FROM {$table}
             WHERE status = 'pending' AND is_internal = 1
             ORDER BY id ASC"
		);
		// phpcs:enable

		if ( empty( $links ) ) {
			return array(
				'checked'       => 0,
				'broken'        => 0,
				'ok'            => 0,
				'http_requests' => 0,
			);
		}

		$settings          = get_option( 'ablr_settings', array() );
		$auto_mode         = isset( $settings['auto_mode'] ) && 'auto' === $settings['auto_mode'];
		$auto_fix_internal = ! empty( $settings['auto_fix_internal'] );
		$auto_action       = isset( $settings['auto_action'] ) ? $settings['auto_action'] : 'remove_link';

		$checked = 0;
		$broken  = 0;
		$ok      = 0;

		// Group links by normalized URL.
		$url_groups = array();
		foreach ( $links as $link ) {
			$normalized = self::normalize_url( $link->url );
			if ( ! isset( $url_groups[ $normalized ] ) ) {
				$url_groups[ $normalized ] = array();
			}
			$url_groups[ $normalized ][] = $link;
		}

		foreach ( $url_groups as $group_links ) {
			// Time limit check.
			if ( microtime( true ) - $start > $time_limit ) {
				break;
			}

			// Check if scan was cancelled — bail out immediately.
			$progress = get_option( 'ablr_scan_progress', array() );
			if ( isset( $progress['status'] ) && in_array( $progress['status'], array( 'cancelled', 'stopped' ), true ) ) {
				break;
			}

			// Check first link (result cached for others).
			$first_link = $group_links[0];
			$result     = self::check_url( $first_link->url, $first_link->id );

			// Update all links in this group.
			foreach ( $group_links as $link ) {
				ABLR_Database::update_link_check( $link->id, $result );
				++$checked;

				if ( 'broken' === $result['status'] ) {
					++$broken;

					// Auto-fix internal links only if both auto_mode and auto_fix_internal are enabled.
					if ( $auto_mode && $auto_fix_internal ) {
						switch ( $auto_action ) {
							case 'remove_link':
							case 'skip_wayback_remove_link':
								ABLR_Fixer::remove_link_keep_text( $link->id, true ); // is_auto = true.
								break;
							case 'remove_all':
							case 'skip_wayback_remove_all':
								ABLR_Fixer::remove_link_and_text( $link->id, true ); // is_auto = true.
								break;
						}
					}
				} else {
					++$ok;
				}
			}
		}

		// Log results.
		$elapsed = round( microtime( true ) - $start, 2 );

		ABLR_Database::add_log(
			0,
			'',
			'',
			0,
			'batch_check_internal',
			sprintf(
				'Batch checked %d internal links (%d unique) in %ss: %d ok, %d broken, %d HTTP requests',
				$checked,
				count( $url_groups ),
				$elapsed,
				$ok,
				$broken,
				self::$http_request_count
			)
		);

		return array(
			'checked'       => $checked,
			'broken'        => $broken,
			'ok'            => $ok,
			'http_requests' => self::$http_request_count,
		);
	}

	/**
	 * Check batch of links (deprecated, use batch_check_pending).
	 *
	 * @param array $links Array of link objects.
	 *
	 * @return array link_id => result.
	 */
	public static function check_batch( $links ) {
		$results = array();
		foreach ( $links as $link ) {
			$results[ $link->id ] = self::check_url( $link->url, $link->id );
		}
		return $results;
	}

	/**
	 * Clear URL cache and other cached data.
	 */
	public static function clear_cache() {
		self::$url_cache            = array();
		self::$cached_post_types    = null;
		self::$last_request_time    = 0;
		self::$http_request_count   = 0;
		self::$consecutive_timeouts = 0;

		// Clear global throttle timestamp and timeout counter.
		delete_option( self::THROTTLE_OPTION_KEY );
		delete_option( self::TIMEOUT_COUNTER_KEY );
	}

	/**
	 * Get cached HTTP check result from transients.
	 *
	 * @param string $url URL to look up.
	 *
	 * @return array|false Cached result or false if not cached.
	 */
	private static function get_http_cached_result( $url ) {
		$cache_key = self::HTTP_CACHE_PREFIX . md5( $url );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		return false;
	}

	/**
	 * Cache HTTP check result in transients.
	 *
	 * @param string $url    URL that was checked.
	 * @param array  $result Check result.
	 */
	private static function cache_http_result( $url, $result ) {
		$cache_key = self::HTTP_CACHE_PREFIX . md5( $url );

		// Use shorter expiration for errors/broken links.
		$expiration = ( 'ok' === $result['status'] )
						? self::HTTP_CACHE_EXPIRATION
						: self::HTTP_CACHE_EXPIRATION_ERROR;

		set_transient( $cache_key, $result, $expiration );
	}

	/**
	 * Clear all internal HTTP cache.
	 */
	public static function clear_all_http_cache() {
		global $wpdb;

		// Delete all transients with our prefix.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time cache cleanup operation.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::HTTP_CACHE_PREFIX . '%',
				'_transient_timeout_' . self::HTTP_CACHE_PREFIX . '%'
			)
		);
	}

	/**
	 * Set request delay in milliseconds.
	 *
	 * @param int $delay_ms Delay between requests.
	 */
	public static function set_request_delay( $delay_ms ) {
		self::$request_delay_ms = max( 0, (int) $delay_ms );
	}

	/**
	 * Configure burst parameters.
	 *
	 * @param int $max_per_burst Max requests before pause.
	 * @param int $pause_ms      Pause duration in milliseconds.
	 */
	public static function set_burst_params( $max_per_burst, $pause_ms ) {
		self::$max_requests_per_burst = max( 1, (int) $max_per_burst );
		self::$burst_pause_ms         = max( 0, (int) $pause_ms );
	}
}
