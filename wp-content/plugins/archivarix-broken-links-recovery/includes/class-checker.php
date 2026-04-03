<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Link checker — HTTP validation, redirect analysis, parking detection, content-type check.
 *
 * Performs multi-level broken link detection:
 * 1. HTTP status codes (4xx, 5xx, connection errors)
 * 2. Redirect analysis (cross-domain, redirect to root)
 * 3. Domain parking detection (30+ signatures)
 * 4. Content-Type mismatch detection
 * 5. Soft 404 detection (200 OK but "not found" content)
 * 6. YouTube video availability via oEmbed API (cached)
 * 7. Vimeo video availability via oEmbed API (cached)
 * 8. TikTok video availability via oEmbed API (cached)
 *
 * Video platform checks are cached using WordPress transients:
 * - Successful/broken results: 1 hour (VIDEO_CACHE_EXPIRATION)
 * - Connection errors: 1 hour (VIDEO_CACHE_EXPIRATION_ERROR)
 *
 * Also handles Wayback Machine API integration for archive availability checks.
 * Supports proxy rotation for external link checking to avoid bot detection.
 * .gov/.edu domains: negative results converted to "uncheckable" (security policies).
 *
 * @package Archivarix_Broken_Links_Recovery
 */

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Uses dynamic placeholders for IN clauses.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Checker
 *
 * HTTP link checker with multi-level validation.
 */
class ABLR_Checker {

	/**
	 * Cache expiration time for video platforms (1 hour).
	 */
	const VIDEO_CACHE_EXPIRATION = 3600;

	/**
	 * Cache expiration for connection errors (1 hour).
	 */
	const VIDEO_CACHE_EXPIRATION_ERROR = 3600;

	/**
	 * Current proxy index for rotation.
	 *
	 * @var int
	 */
	private static $proxy_index = 0;

	/**
	 * Browser-like headers to avoid bot detection.
	 * Emulates Chrome 131 on Windows 11 (latest as of late 2024).
	 *
	 * @var array
	 */
	private static $browser_headers = array(
		'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
		'Accept-Language'           => 'en-US,en;q=0.9',
		'Cache-Control'             => 'max-age=0',
		'Sec-Ch-Ua'                 => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
		'Sec-Ch-Ua-Mobile'          => '?0',
		'Sec-Ch-Ua-Platform'        => '"Windows"',
		'Sec-Fetch-Dest'            => 'document',
		'Sec-Fetch-Mode'            => 'navigate',
		'Sec-Fetch-Site'            => 'none',
		'Sec-Fetch-User'            => '?1',
		'Upgrade-Insecure-Requests' => '1',
		'Priority'                  => 'u=0, i',
	);

	/**
	 * Browser User-Agent string (Chrome 131 on Windows 11).
	 *
	 * @var string
	 */
	private static $browser_user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

	/**
	 * Government/Education TLD suffixes where negative results may be unreliable.
	 *
	 * These domains often have strict security policies that block automated checks.
	 * If check returns "broken", it gets converted to "uncheckable" because we can't
	 * trust that the resource is actually broken — it might be bot protection.
	 *
	 * If check returns "ok", we keep it as "ok" because we successfully verified.
	 *
	 * @var array
	 */
	private static $gov_edu_tlds = array(
		'.gov',
		'.edu',
		'.gov.uk',
		'.gov.au',
		'.gov.ca',
		'.gov.nz',
		'.edu.au',
		'.edu.cn',
		'.ac.uk',
		'.ac.jp',
		'.go.jp',
		'.mil',
	);

	/**
	 * Known domain parking signatures in HTML body.
	 *
	 * Includes parking service domains and common parking page phrases.
	 * All strings are pre-lowercased to avoid repeated strtolower() calls
	 * during is_parked() checks (saves ~30 strtolower calls per URL).
	 *
	 * @var array
	 */
	private static $parking_signatures = array(
		// Parking service domains (already lowercase).
		'sedoparking.com',
		'bodis.com',
		'parkingcrew.com',
		'hugedomains.com',
		'afternic.com',
		'dan.com',
		'undeveloped.com',
		'godaddy.com/domainfind',
		'domainmarket.com',
		'sav.com',
		'porkbun.com/parking',
		'above.com',
		'domainlore.com',

		// Common parking page phrases (already lowercase).
		'this domain is for sale',
		'this domain may be for sale',
		'buy this domain',
		'domain is parked',
		'this website is for sale',
		'domain name for sale',
		'this site is currently unavailable',
		'parked free',
		'domain parking',
		'this domain has expired',
		'this page is provided courtesy of',
		'domaincontrol.com',
		'register.com/parking',
		'name.com/parking',
	);

	/**
	 * Get next proxy from the list (rotation).
	 *
	 * @return array|null Proxy config array or null if no proxies configured.
	 */
	private static function get_next_proxy() {
		$settings = get_option( 'ablr_settings', array() );
		$proxies  = isset( $settings['proxies'] ) ? $settings['proxies'] : array();

		if ( empty( $proxies ) ) {
			return null;
		}

		// Rotate through proxies.
		$proxy = $proxies[ self::$proxy_index % count( $proxies ) ];
		++self::$proxy_index;

		return $proxy;
	}

	/**
	 * Build cURL proxy string from proxy config.
	 *
	 * @param array $proxy Proxy config with host, port, user, pass.
	 * @return string cURL proxy string.
	 */
	private static function build_proxy_string( $proxy ) {
		if ( empty( $proxy ) ) {
			return '';
		}

		$proxy_url = $proxy['host'] . ':' . $proxy['port'];

		if ( ! empty( $proxy['user'] ) && ! empty( $proxy['pass'] ) ) {
			return $proxy['user'] . ':' . $proxy['pass'] . '@' . $proxy_url;
		}

		return $proxy_url;
	}

	/**
	 * Current proxy config for the active request.
	 * Used by the proxy filter callback.
	 *
	 * @var array|null
	 */
	private static $current_proxy = null;

	/**
	 * Apply proxy settings directly to cURL handle.
	 *
	 * This hook is called by WordPress AFTER it creates the cURL handle
	 * but BEFORE it executes the request. This is the most reliable way
	 * to add proxy settings because we work directly with the cURL handle.
	 *
	 * The http_request_args filter doesn't always work because WordPress
	 * may not pass the 'curl' array to curl_setopt_array().
	 *
	 * @param resource $handle The cURL handle.
	 * @param array    $r      The HTTP request arguments (unused, required by hook signature).
	 * @param string   $url    The request URL (unused, required by hook signature).
	 */
	public static function apply_proxy_to_curl( $handle, $r, $url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by http_api_curl hook signature.
		if ( empty( self::$current_proxy ) ) {
			return;
		}

		$proxy = self::$current_proxy;

		// Set proxy server.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Direct cURL required for proxy settings via http_api_curl hook.
		curl_setopt( $handle, CURLOPT_PROXY, $proxy['host'] . ':' . $proxy['port'] );

		// Set proxy type (HTTP proxy).
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP );

		// CRITICAL: Enable HTTP CONNECT tunneling for HTTPS requests.
		// This tells cURL to send a CONNECT request to the proxy first,
		// establishing a tunnel through which the HTTPS connection is made.
		// Without this, DNS resolution happens locally instead of through the proxy,
		// resulting in "Could not resolve host" errors.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $handle, CURLOPT_HTTPPROXYTUNNEL, true );

		// Proxy authentication if provided.
		if ( ! empty( $proxy['user'] ) && ! empty( $proxy['pass'] ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt( $handle, CURLOPT_PROXYUSERPWD, $proxy['user'] . ':' . $proxy['pass'] );
		}
	}

	/**
	 * Make HTTP request with browser emulation and optional proxy.
	 *
	 * @param string $url      URL to request.
	 * @param bool   $use_proxy Whether to use proxy rotation.
	 * @return array|WP_Error Response or error.
	 */
	public static function browser_request( $url, $use_proxy = true ) {
		$settings = get_option( 'ablr_settings', array() );
		$timeout  = 10; // Fixed timeout for external links.

		$args = array(
			'timeout'     => $timeout,
			'redirection' => 10,
			'sslverify'   => false,
			'user-agent'  => self::$browser_user_agent,
			'headers'     => self::$browser_headers,
		);

		// Add proxy if enabled and available.
		$filter_added = false;
		if ( $use_proxy ) {
			$proxy = self::get_next_proxy();
			if ( $proxy ) {
				// Store proxy in static variable for the filter callback.
				self::$current_proxy = $proxy;

				// Use http_api_curl hook - this is called with the actual cURL handle
				// and is the most reliable way to set proxy options.
				add_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999, 3 );
				$filter_added = true;
			}
		}

		$response = wp_remote_get( $url, $args );

		// Remove our hook after the request.
		if ( $filter_added ) {
			remove_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999 );
			self::$current_proxy = null;
		}

		return $response;
	}

	/**
	 * Test a single proxy for connectivity.
	 *
	 * @param array $proxy Proxy config with host, port, user, pass.
	 * @return array Result with 'working' boolean and 'message'.
	 */
	public static function test_proxy( $proxy ) {
		$test_url = 'https://httpbin.org/ip';
		$timeout  = 10;

		$args = array(
			'timeout'    => $timeout,
			'sslverify'  => false,
			'user-agent' => self::$browser_user_agent,
		);

		// Store proxy for the action callback.
		self::$current_proxy = $proxy;

		// Use http_api_curl action - works directly with cURL handle.
		add_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999, 3 );

		$response = wp_remote_get( $test_url, $args );

		// Remove our action.
		remove_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999 );
		self::$current_proxy = null;

		if ( is_wp_error( $response ) ) {
			return array(
				'working' => false,
				'message' => $response->get_error_message(),
				'proxy'   => $proxy['host'] . ':' . $proxy['port'],
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return array(
				'working' => false,
				'message' => sprintf( 'HTTP %d', $code ),
				'proxy'   => $proxy['host'] . ':' . $proxy['port'],
			);
		}

		// Try to get the IP from response to confirm proxy is working.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		$ip   = isset( $data['origin'] ) ? $data['origin'] : '';

		return array(
			'working' => true,
			'message' => 'OK',
			'proxy'   => $proxy['host'] . ':' . $proxy['port'],
			'ip'      => $ip,
		);
	}

	/**
	 * Parse proxy string into config array.
	 *
	 * Format: host:port:user:pass or host:port
	 *
	 * @param string $line Proxy line.
	 * @return array|null Proxy config or null if invalid.
	 */
	public static function parse_proxy_line( $line ) {
		$line = trim( $line );
		if ( empty( $line ) ) {
			return null;
		}

		// Remove any whitespace and tabs.
		$line = preg_replace( '/\s+/', '', $line );

		$parts = explode( ':', $line );

		if ( count( $parts ) < 2 ) {
			return null;
		}

		$proxy = array(
			'host' => $parts[0],
			'port' => $parts[1],
			'user' => '',
			'pass' => '',
		);

		if ( count( $parts ) >= 4 ) {
			$proxy['user'] = $parts[2];
			// Password may contain colons, so join all remaining parts.
			$proxy['pass'] = implode( ':', array_slice( $parts, 3 ) );
		}

		// Validate host and port.
		if ( empty( $proxy['host'] ) || ! is_numeric( $proxy['port'] ) ) {
			return null;
		}

		return $proxy;
	}

	/**
	 * Check if URL belongs to a government or education domain.
	 *
	 * These domains often have strict security policies that may block
	 * automated checks, so negative results should be treated as unreliable.
	 *
	 * @param string $url The URL to check.
	 * @return bool True if domain is .gov/.edu or similar.
	 */
	private static function is_gov_edu_domain( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		$host = strtolower( $host );

		// Check if host ends with any of the gov/edu TLDs.
		foreach ( self::$gov_edu_tlds as $tld ) {
			if ( substr( $host, -strlen( $tld ) ) === $tld ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if URL is a YouTube video link.
	 *
	 * Detects various YouTube URL formats:
	 * - https://www.youtube.com/watch?v=VIDEO_ID
	 * - https://youtube.com/watch?v=VIDEO_ID
	 * - https://youtu.be/VIDEO_ID
	 * - https://www.youtube.com/embed/VIDEO_ID
	 * - https://www.youtube.com/v/VIDEO_ID
	 * - https://www.youtube.com/shorts/VIDEO_ID
	 *
	 * @param string $url The URL to check.
	 * @return bool True if URL is a YouTube video link.
	 */
	private static function is_youtube_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		$host = strtolower( $host );
		$host = preg_replace( '/^www\./i', '', $host );

		// Check if it's a YouTube domain.
		if ( 'youtube.com' !== $host && 'youtu.be' !== $host ) {
			return false;
		}

		// For youtu.be, any path with an ID is a video.
		if ( 'youtu.be' === $host ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			return ! empty( $path ) && '/' !== $path;
		}

		// For youtube.com, check for video URL patterns.
		$path  = wp_parse_url( $url, PHP_URL_PATH );
		$path  = $path ? $path : '';
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		$query = $query ? $query : '';

		// Pattern: /watch?v=VIDEO_ID.
		if ( 0 === strpos( $path, '/watch' ) && false !== strpos( $query, 'v=' ) ) {
			return true;
		}

		// /embed/VIDEO_ID, /v/VIDEO_ID, /shorts/VIDEO_ID
		if ( preg_match( '#^/(embed|v|shorts)/[a-zA-Z0-9_-]+#', $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if URL is a Vimeo video link.
	 *
	 * Detects various Vimeo URL formats:
	 * - https://vimeo.com/VIDEO_ID
	 * - https://player.vimeo.com/video/VIDEO_ID
	 * - https://vimeo.com/channels/CHANNEL/VIDEO_ID
	 * - https://vimeo.com/groups/GROUP/videos/VIDEO_ID
	 * - https://vimeo.com/album/ALBUM/video/VIDEO_ID
	 * - https://vimeo.com/ondemand/PAGE/VIDEO_ID
	 *
	 * @param string $url The URL to check.
	 * @return bool True if URL is a Vimeo video link.
	 */
	private static function is_vimeo_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		$host = strtolower( $host );
		$host = preg_replace( '/^www\./i', '', $host );

		// Check if it's a Vimeo domain.
		if ( 'vimeo.com' !== $host && 'player.vimeo.com' !== $host ) {
			return false;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = $path ? $path : '';

		// For player.vimeo.com, check for /video/VIDEO_ID pattern.
		if ( 'player.vimeo.com' === $host ) {
			return 1 === preg_match( '#^/video/\d+#', $path );
		}

		// For vimeo.com, check for various video URL patterns.
		// Direct video: /VIDEO_ID (just digits).
		if ( preg_match( '#^/\d+$#', $path ) ) {
			return true;
		}

		// Channels pattern: /channels/CHANNEL/VIDEO_ID.
		if ( preg_match( '#^/channels/[^/]+/\d+$#', $path ) ) {
			return true;
		}

		// Groups pattern: /groups/GROUP/videos/VIDEO_ID.
		if ( preg_match( '#^/groups/[^/]+/videos/\d+$#', $path ) ) {
			return true;
		}

		// Albums pattern: /album/ALBUM/video/VIDEO_ID.
		if ( preg_match( '#^/album/\d+/video/\d+$#', $path ) ) {
			return true;
		}

		// On Demand pattern: /ondemand/PAGE/VIDEO_ID.
		if ( preg_match( '#^/ondemand/[^/]+/\d+$#', $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check Vimeo video availability using oEmbed API.
	 *
	 * The Vimeo oEmbed API returns video metadata if the video exists
	 * and is publicly accessible. Returns 404 for unavailable videos.
	 *
	 * Uses proxy rotation if proxies are configured in plugin settings.
	 *
	 * oEmbed endpoint: https://vimeo.com/api/oembed.json?url={video_url}
	 *
	 * @param string $url     The Vimeo video URL.
	 * @param int    $link_id Link record ID for logging.
	 * @return array Result with status, http_code, fail_reason.
	 */
	private static function check_vimeo_oembed( $url, $link_id = 0 ) {
		// Check cache first.
		$cached = self::get_video_cached_result( $url );
		if ( false !== $cached ) {
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'vimeo_cache_hit',
				sprintf( 'Using cached result for Vimeo: %s', $cached['status'] )
			);
			return $cached;
		}

		$result = array(
			'status'            => 'ok',
			'http_code'         => 0,
			'redirect_url'      => '',
			'fail_reason'       => '',
			'content_type'      => '',
			'wayback_available' => false,
			'wayback_url'       => '',
		);

		// Build oEmbed API URL.
		$oembed_url = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode( $url );

		$args = array(
			'timeout'    => 10,
			'sslverify'  => false,
			'user-agent' => self::$browser_user_agent,
		);

		// Use proxy if configured.
		$filter_added = false;
		$proxy        = self::get_next_proxy();
		if ( $proxy ) {
			self::$current_proxy = $proxy;
			add_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999, 3 );
			$filter_added = true;
		}

		$response = wp_remote_get( $oembed_url, $args );

		// Remove proxy hook after request.
		if ( $filter_added ) {
			remove_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999 );
			self::$current_proxy = null;
		}

		if ( is_wp_error( $response ) ) {
			$error_message         = $response->get_error_message();
			$result['status']      = 'broken';
			$result['fail_reason'] = self::classify_wp_error( $error_message );

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_vimeo_oembed',
				sprintf( 'Vimeo oEmbed API error: %s', $error_message )
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$result['http_code'] = $http_code;

		// oEmbed returns 200 for available videos.
		if ( 200 === $http_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// Verify we got valid oEmbed data.
			if ( ! empty( $data['title'] ) && ! empty( $data['type'] ) ) {
				$result['status'] = 'ok';

				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'check_vimeo_oembed',
					sprintf( 'Vimeo video available: %s', $data['title'] )
				);

				self::cache_video_result( $url, $result );
				return $result;
			}
		}

		// 404 = Not Found (video removed or doesn't exist).
		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'vimeo_not_found';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_vimeo_oembed',
				'Vimeo video not found or has been removed'
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// 403 = Forbidden (private video, password-protected, or embedding disabled).
		if ( 403 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'vimeo_unavailable';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_vimeo_oembed',
				'Vimeo video unavailable (HTTP 403) - private or embedding disabled'
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// 401 = Unauthorized (requires authentication).
		if ( 401 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'vimeo_unavailable';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_vimeo_oembed',
				'Vimeo video requires authentication'
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// Any other 4xx/5xx error code.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_vimeo_oembed',
				sprintf( 'Vimeo oEmbed API returned HTTP %d', $http_code )
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// Unexpected response - mark as OK but log.
		$result['status'] = 'ok';

		ABLR_Database::add_log(
			$link_id,
			$url,
			'',
			$http_code,
			'check_vimeo_oembed',
			sprintf( 'Vimeo oEmbed returned HTTP %d - assuming video exists', $http_code )
		);

		self::cache_video_result( $url, $result );
		return $result;
	}

	/**
	 * Check if URL is a TikTok video link.
	 *
	 * Detects various TikTok URL formats:
	 * - https://www.tiktok.com/@username/video/VIDEO_ID
	 * - https://tiktok.com/@username/video/VIDEO_ID
	 * - https://vm.tiktok.com/SHORT_ID (short links)
	 * - https://m.tiktok.com/v/VIDEO_ID
	 *
	 * @param string $url The URL to check.
	 * @return bool True if URL is a TikTok video link.
	 */
	private static function is_tiktok_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		$host = strtolower( $host );
		$host = preg_replace( '/^www\./i', '', $host );

		// Check if it's a TikTok domain.
		if ( 'tiktok.com' !== $host && 'vm.tiktok.com' !== $host && 'm.tiktok.com' !== $host ) {
			return false;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = $path ? $path : '';

		// Short links on vm.tiktok.com - any path is a video.
		if ( 'vm.tiktok.com' === $host ) {
			return ! empty( $path ) && '/' !== $path;
		}

		// Mobile pattern: m.tiktok.com/v/VIDEO_ID.
		if ( 'm.tiktok.com' === $host && preg_match( '#^/v/\d+#', $path ) ) {
			return true;
		}

		// Standard pattern: tiktok.com/@username/video/VIDEO_ID.
		if ( preg_match( '#^/@[^/]+/video/\d+#', $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check TikTok video availability using oEmbed API.
	 *
	 * The TikTok oEmbed API returns video metadata if the video exists
	 * and is publicly accessible.
	 *
	 * Uses proxy rotation if proxies are configured in plugin settings.
	 *
	 * oEmbed endpoint: https://www.tiktok.com/oembed?url={video_url}
	 *
	 * @param string $url     The TikTok video URL.
	 * @param int    $link_id Link record ID for logging.
	 * @return array Result with status, http_code, fail_reason.
	 */
	private static function check_tiktok_oembed( $url, $link_id = 0 ) {
		// Check cache first.
		$cached = self::get_video_cached_result( $url );
		if ( false !== $cached ) {
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'tiktok_cache_hit',
				sprintf( 'Using cached result for TikTok: %s', $cached['status'] )
			);
			return $cached;
		}

		$result = array(
			'status'            => 'ok',
			'http_code'         => 0,
			'redirect_url'      => '',
			'fail_reason'       => '',
			'content_type'      => '',
			'wayback_available' => false,
			'wayback_url'       => '',
		);

		// Build oEmbed API URL.
		$oembed_url = 'https://www.tiktok.com/oembed?url=' . rawurlencode( $url );

		$args = array(
			'timeout'    => 10,
			'sslverify'  => false,
			'user-agent' => self::$browser_user_agent,
		);

		// Use proxy if configured.
		$filter_added = false;
		$proxy        = self::get_next_proxy();
		if ( $proxy ) {
			self::$current_proxy = $proxy;
			add_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999, 3 );
			$filter_added = true;
		}

		$response = wp_remote_get( $oembed_url, $args );

		// Remove proxy hook after request.
		if ( $filter_added ) {
			remove_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999 );
			self::$current_proxy = null;
		}

		if ( is_wp_error( $response ) ) {
			$error_message         = $response->get_error_message();
			$result['status']      = 'broken';
			$result['fail_reason'] = self::classify_wp_error( $error_message );

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_tiktok_oembed',
				sprintf( 'TikTok oEmbed API error: %s', $error_message )
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$result['http_code'] = $http_code;

		// oEmbed returns 200 for available videos.
		if ( 200 === $http_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// Verify we got valid oEmbed data.
			if ( ! empty( $data['title'] ) || ! empty( $data['author_name'] ) ) {
				$result['status'] = 'ok';

				$title = ! empty( $data['title'] ) ? $data['title'] : $data['author_name'];
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'check_tiktok_oembed',
					sprintf( 'TikTok video available: %s', mb_substr( $title, 0, 100 ) )
				);

				self::cache_video_result( $url, $result );
				return $result;
			}
		}

		// 404 = Not Found (video removed or doesn't exist).
		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'tiktok_not_found';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_tiktok_oembed',
				'TikTok video not found or has been removed'
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// 400 = Bad Request (invalid URL format).
		if ( 400 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'tiktok_invalid_url';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_tiktok_oembed',
				'TikTok video URL is invalid'
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// Any other 4xx/5xx error code.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_tiktok_oembed',
				sprintf( 'TikTok oEmbed API returned HTTP %d', $http_code )
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// Unexpected response - mark as OK but log.
		$result['status'] = 'ok';

		ABLR_Database::add_log(
			$link_id,
			$url,
			'',
			$http_code,
			'check_tiktok_oembed',
			sprintf( 'TikTok oEmbed returned HTTP %d - assuming video exists', $http_code )
		);

		self::cache_video_result( $url, $result );
		return $result;
	}

	/**
	 * Get cached result for video URL.
	 *
	 * @param string $url Video URL to check cache for.
	 * @return array|false Cached result or false if not cached.
	 */
	private static function get_video_cached_result( $url ) {
		$cache_key = 'ablr_video_' . md5( $url );
		$cached    = get_transient( $cache_key );

		if ( false === $cached ) {
			return false;
		}

		// Validate cached data structure.
		if ( ! is_array( $cached ) || ! isset( $cached['status'] ) ) {
			delete_transient( $cache_key );
			return false;
		}

		return $cached;
	}

	/**
	 * Cache result for video URL.
	 *
	 * @param string $url    Video URL to cache result for.
	 * @param array  $result Result to cache.
	 */
	private static function cache_video_result( $url, $result ) {
		$cache_key = 'ablr_video_' . md5( $url );

		// Shorter expiration for connection errors (retry sooner).
		$fail_reason         = isset( $result['fail_reason'] ) ? $result['fail_reason'] : '';
		$is_connection_error = in_array(
			$fail_reason,
			array(
				'timeout',
				'dns_failure',
				'connection_refused',
				'ssl_error',
				'connection_reset',
				'connection_error',
			),
			true
		);

		$expiration = $is_connection_error
			? self::VIDEO_CACHE_EXPIRATION_ERROR
			: self::VIDEO_CACHE_EXPIRATION;

		set_transient( $cache_key, $result, $expiration );
	}

	/**
	 * Clear cached result for a video URL.
	 *
	 * @param string $url Video URL to clear cache for.
	 */
	public static function clear_video_cache( $url ) {
		$cache_key = 'ablr_video_' . md5( $url );
		delete_transient( $cache_key );
	}

	/**
	 * Clear all video checker cache.
	 *
	 * Uses direct DB query for efficiency with large numbers of cached items.
	 *
	 * @return int Number of cache entries deleted.
	 */
	public static function clear_all_video_cache() {
		global $wpdb;

		// Delete all transients with our prefix.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk delete of transients by pattern requires direct query.
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_ablr_video_%'
                OR option_name LIKE '_transient_timeout_ablr_video_%'"
		);

		return $deleted;
	}

	/**
	 * Check YouTube video availability using oEmbed API.
	 *
	 * The YouTube oEmbed API returns video metadata if the video exists
	 * and is publicly accessible. Returns 404 or error for unavailable videos.
	 *
	 * Uses proxy rotation if proxies are configured in plugin settings.
	 * This is useful when YouTube is blocked in the server's region.
	 *
	 * oEmbed endpoint: https://www.youtube.com/oembed?url={video_url}&format=json
	 *
	 * @param string $url     The YouTube video URL.
	 * @param int    $link_id Link record ID for logging.
	 * @return array Result with status, http_code, fail_reason.
	 */
	private static function check_youtube_oembed( $url, $link_id = 0 ) {
		// Check cache first.
		$cached = self::get_video_cached_result( $url );
		if ( false !== $cached ) {
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'youtube_cache_hit',
				sprintf( 'Using cached result for YouTube: %s', $cached['status'] )
			);
			return $cached;
		}

		$result = array(
			'status'            => 'ok',
			'http_code'         => 0,
			'redirect_url'      => '',
			'fail_reason'       => '',
			'content_type'      => '',
			'wayback_available' => false,
			'wayback_url'       => '',
		);

		// Build oEmbed API URL.
		$oembed_url = 'https://www.youtube.com/oembed?url=' . rawurlencode( $url ) . '&format=json';

		$args = array(
			'timeout'    => 10,
			'sslverify'  => false,
			'user-agent' => self::$browser_user_agent,
		);

		// Use proxy if configured (useful when YouTube is blocked in server's region).
		$filter_added = false;
		$proxy        = self::get_next_proxy();
		if ( $proxy ) {
			self::$current_proxy = $proxy;
			add_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999, 3 );
			$filter_added = true;
		}

		$response = wp_remote_get( $oembed_url, $args );

		// Remove proxy hook after request.
		if ( $filter_added ) {
			remove_action( 'http_api_curl', array( __CLASS__, 'apply_proxy_to_curl' ), 999 );
			self::$current_proxy = null;
		}

		if ( is_wp_error( $response ) ) {
			$error_message         = $response->get_error_message();
			$result['status']      = 'broken';
			$result['fail_reason'] = self::classify_wp_error( $error_message );

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_youtube_oembed',
				sprintf( 'YouTube oEmbed API error: %s', $error_message )
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$result['http_code'] = $http_code;

		// oEmbed returns 200 for available videos.
		if ( 200 === $http_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// Verify we got valid oEmbed data.
			if ( ! empty( $data['title'] ) && ! empty( $data['type'] ) ) {
				$result['status'] = 'ok';

				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'check_youtube_oembed',
					sprintf( 'YouTube video available: %s', $data['title'] )
				);

				self::cache_video_result( $url, $result );
				return $result;
			}
		}

		// 400 = Bad Request (invalid video ID or video not found).
		// 404 = Not Found (video removed).
		// YouTube oEmbed returns 400 for non-existent videos, not 404.
		if ( 400 === $http_code || 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'youtube_not_found';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_youtube_oembed',
				'YouTube video not found or has been removed'
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// 401 = Unauthorized (private video).
		// 403 = Forbidden (age-restricted, region-blocked, or embedding disabled).
		if ( 401 === $http_code || 403 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'youtube_unavailable';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_youtube_oembed',
				sprintf( 'YouTube video unavailable (HTTP %d) - private, age-restricted, or region-blocked', $http_code )
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// Any other 4xx/5xx error code.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_youtube_oembed',
				sprintf( 'YouTube oEmbed API returned HTTP %d', $http_code )
			);

			self::cache_video_result( $url, $result );
			return $result;
		}

		// Unexpected response - mark as OK but log.
		$result['status'] = 'ok';

		ABLR_Database::add_log(
			$link_id,
			$url,
			'',
			$http_code,
			'check_youtube_oembed',
			sprintf( 'YouTube oEmbed returned HTTP %d - assuming video exists', $http_code )
		);

		self::cache_video_result( $url, $result );
		return $result;
	}

	/**
	 * Check a single URL.
	 *
	 * Performs multi-level validation:
	 * 1. Social network check (Twitter, LinkedIn, Instagram, Facebook, Pinterest)
	 * 2. YouTube video check via oEmbed API
	 * 3. Vimeo video check via oEmbed API
	 * 4. TikTok video check via oEmbed API
	 * 5. HTTP status code check
	 * 6. Redirect analysis
	 * 7. Domain parking detection
	 * 8. Content-Type validation
	 * 9. Soft 404 detection
	 * 10. .gov/.edu → uncheckable conversion for negative results
	 *
	 * @param string $url     The URL to check.
	 * @param int    $link_id Link record ID for logging.
	 *
	 * @return array Result data with keys: status, http_code, redirect_url, fail_reason, content_type, etc.
	 */
	public static function check_url( $url, $link_id = 0 ) {
		$result = array(
			'status'            => 'ok',
			'http_code'         => 0,
			'redirect_url'      => '',
			'fail_reason'       => '',
			'content_type'      => '',
			'wayback_available' => false,
			'wayback_url'       => '',
		);

		// Check if domain is .gov/.edu which often have strict security policies.
		// We still check these URLs, but negative results will be converted to "uncheckable"
		// because we can't trust that a "broken" result is accurate.
		$is_gov_edu = self::is_gov_edu_domain( $url );

		// --- Social network check ---
		// Social networks (Twitter, LinkedIn, Instagram, Facebook, Pinterest) are handled
		// by ABLR_Social_Checker which attempts actual verification and uses caching.
		// Only marks as "uncheckable" if bot detection is actually encountered.
		$social_result = ABLR_Social_Checker::check_url( $url, $link_id );
		if ( null !== $social_result ) {
			return $social_result;
		}

		// --- YouTube video check via oEmbed API ---
		// YouTube videos require special handling because:
		// 1. Direct HTTP requests often return 200 even for unavailable videos
		// 2. YouTube's consent pages can cause false positives
		// 3. oEmbed API provides reliable video availability status.
		if ( self::is_youtube_url( $url ) ) {
			return self::check_youtube_oembed( $url, $link_id );
		}

		// --- Vimeo video check via oEmbed API ---
		// Vimeo videos also benefit from oEmbed checking:
		// 1. Reliable video availability status
		// 2. Detects private, removed, or password-protected videos.
		if ( self::is_vimeo_url( $url ) ) {
			return self::check_vimeo_oembed( $url, $link_id );
		}

		// --- TikTok video check via oEmbed API ---
		// TikTok provides oEmbed API for public videos.
		// Detects removed or private videos reliably.
		if ( self::is_tiktok_url( $url ) ) {
			return self::check_tiktok_oembed( $url, $link_id );
		}

		// --- Level 1: HTTP status check ---
		// Use browser emulation with optional proxy rotation.
		$response = self::browser_request( $url, true );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$error_lower   = strtolower( $error_message );

			// First, check for DNS/resolution failures — these are definitely broken links.
			// DNS failures through proxy CONNECT tunnel should still be marked as broken.
			$is_dns_failure = ( strpos( $error_lower, 'could not resolve' ) !== false ||
								strpos( $error_lower, 'name or service not known' ) !== false ||
								strpos( $error_lower, 'no such host' ) !== false ||
								strpos( $error_lower, 'getaddrinfo' ) !== false ||
								strpos( $error_lower, 'dns' ) !== false );

			if ( $is_dns_failure ) {
				$result['status']      = 'broken';
				$result['fail_reason'] = 'dns_failure';

				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					0,
					'check_broken',
					sprintf( 'Domain does not exist (DNS failure): %s', $error_message )
				);

				return $result;
			}

			// Check for proxy 502/503 errors - these indicate unreachable host, not bot protection.
			// When a proxy can't reach the target (domain exists but server is down),
			// it returns 502 Bad Gateway or 503 Service Unavailable.
			$is_proxy_gateway_error = ( strpos( $error_lower, '502' ) !== false ||
										strpos( $error_lower, '503' ) !== false ||
										strpos( $error_lower, 'bad gateway' ) !== false ||
										strpos( $error_lower, 'service unavailable' ) !== false );

			if ( $is_proxy_gateway_error ) {
				$result['status']      = 'broken';
				$result['fail_reason'] = 'connection_error';

				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					0,
					'check_broken',
					sprintf( 'Server unreachable (proxy gateway error): %s', $error_message )
				);

				return $result;
			}

			// Check if this looks like bot protection (CONNECT tunnel 403/blocked).
			// Only mark as bot_protected for .gov/.edu domains — other sites just fail.
			if ( strpos( $error_message, 'CONNECT tunnel' ) !== false ||
				strpos( $error_message, 'response 403' ) !== false ) {
				// Only treat as uncheckable/bot_protected for .gov/.edu domains.
				if ( $is_gov_edu ) {
					$result['status']      = 'uncheckable';
					$result['fail_reason'] = 'bot_protected';

					ABLR_Database::add_log(
						$link_id,
						$url,
						'',
						0,
						'check_uncheckable',
						sprintf( 'Blocked by bot protection (.gov/.edu): %s', $error_message )
					);

					return $result;
				}

				// Non .gov/.edu domain blocked — treat as broken.
				$result['status']      = 'broken';
				$result['fail_reason'] = 'connection_refused';

				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					0,
					'check_broken',
					sprintf( 'Connection blocked: %s', $error_message )
				);

				return $result;
			}

			$result['status']      = 'broken';
			$result['fail_reason'] = self::classify_wp_error( $error_message );

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_failed',
				sprintf( 'Connection error: %s', $error_message )
			);

			return self::maybe_convert_to_uncheckable( $result, $is_gov_edu, $url, $link_id );
		}

		$http_code    = wp_remote_retrieve_response_code( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$body         = wp_remote_retrieve_body( $response );

		$result['http_code']    = $http_code;
		$result['content_type'] = $content_type ? $content_type : '';

		// --- Check for bot protection responses ---
		// HTTP 429 = Rate limiting
		// HTTP 999 = LinkedIn's bot detection code
		// HTTP 503 = Service unavailable (often used by Cloudflare/anti-bot)
		// Mark as uncheckable/bot_protected for ALL domains — these codes indicate bot detection.
		if ( in_array( $http_code, array( 429, 999, 503 ), true ) ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'bot_protected';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'check_uncheckable',
				sprintf( 'Bot protection detected (HTTP %d)', $http_code )
			);

			return $result;
		}

		// HTTP 403 on .gov/.edu domains - always uncheckable.
		// These domains have strict security policies that block automated checks.
		if ( 403 === $http_code && $is_gov_edu ) {
			$result['status']      = 'uncheckable';
			$result['http_code']   = 403;
			$result['fail_reason'] = 'gov_edu_forbidden';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				403,
				'check_uncheckable',
				'HTTP 403 on .gov/.edu domain - security policy block'
			);

			return $result;
		}

		// HTTP 403 on non-.gov/.edu domains — mark as broken.
		// The .gov/.edu case is already handled above.
		if ( 403 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_403';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				403,
				'check_broken',
				'HTTP 403 Forbidden'
			);

			return $result;
		}

		// 4xx / 5xx (except handled above).
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_broken',
				sprintf( 'HTTP %d response', $http_code )
			);

			return self::maybe_convert_to_uncheckable( $result, $is_gov_edu, $url, $link_id );
		}

		// --- Level 2: Redirect analysis ---
		$redirect_result = self::analyze_redirects( $url, $response );
		if ( ! empty( $redirect_result['redirect_url'] ) ) {
			$result['redirect_url'] = $redirect_result['redirect_url'];
		}

		if ( ! empty( $redirect_result['is_broken'] ) ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = $redirect_result['reason'];

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_redirect',
				sprintf( 'Suspicious redirect: %s → %s (%s)', $url, $redirect_result['redirect_url'], $redirect_result['reason'] )
			);

			return self::maybe_convert_to_uncheckable( $result, $is_gov_edu, $url, $link_id );
		}

		// --- Level 3: Parking detection ---
		if ( self::is_parked( $body ) ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'parking';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_parking',
				'Domain parking page detected'
			);

			return self::maybe_convert_to_uncheckable( $result, $is_gov_edu, $url, $link_id );
		}

		// --- Level 4: Content-Type validation ---
		if ( self::is_content_type_mismatch( $url, $content_type ) ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'content_type';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_content_type',
				sprintf( 'Content-Type mismatch: expected text/html, got %s', $content_type )
			);

			return self::maybe_convert_to_uncheckable( $result, $is_gov_edu, $url, $link_id );
		}

		// --- Soft 404 detection ---
		if ( 200 === $http_code && self::is_soft_404( $body ) ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'soft_404';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'check_soft404',
				'Soft 404 detected (page returns 200 but contains not-found markers)'
			);

			return self::maybe_convert_to_uncheckable( $result, $is_gov_edu, $url, $link_id );
		}

		// All good.
		$result['status'] = 'ok';

		return $result;
	}

	/**
	 * Analyze redirect chain.
	 *
	 * Determines if a redirect is "suspicious" (potentially broken) or "normal".
	 *
	 * NORMAL redirects (NOT broken):
	 * - Same domain with/without www
	 * - Same root domain with different subdomains (e.g., ec.europa.eu → commission.europa.eu)
	 * - Language/region redirects within same domain
	 * - HTTP → HTTPS upgrades
	 * - Trailing slash normalization
	 * - consent.youtube.com (cookie consent pages)
	 *
	 * SUSPICIOUS redirects (marked as broken):
	 * - Completely different domain (e.g., example.com → parking.com)
	 * - Redirect to homepage root when original was a specific page (content removed)
	 *
	 * @param string $original_url The original URL being checked.
	 * @param array  $response     The HTTP response from wp_remote_get.
	 * @return array Analysis result with redirect_url, is_broken, and reason.
	 */
	private static function analyze_redirects( $original_url, $response ) {
		$result = array(
			'redirect_url' => '',
			'is_broken'    => false,
			'reason'       => '',
		);

		// wp_remote_get follows redirects automatically.
		// We need to check if the final URL differs significantly.
		$redirect_response = $response['http_response'] ?? null;

		// Try to get final URL from response object.
		// WordPress HTTP API stores the final URL after following redirects.
		$final_url = '';
		if ( isset( $response['http_response'] ) && is_object( $response['http_response'] ) ) {
			$final_url_obj = $response['http_response']->get_response_object();
			if ( $final_url_obj && isset( $final_url_obj->url ) ) {
				$final_url = $final_url_obj->url;
			}
		}

		// Fallback: check response headers for redirect info.
		// We avoid making an additional HEAD request since we already have the GET response.
		// The original GET request already followed redirects, so if we don't have final_url
		// from the response object, we check headers as a secondary source.
		if ( empty( $final_url ) ) {
			// Check for X-Final-URL or similar headers that some servers/proxies add.
			$x_final = wp_remote_retrieve_header( $response, 'x-final-url' );
			if ( ! empty( $x_final ) ) {
				$final_url = $x_final;
			}
		}

		if ( empty( $final_url ) || $final_url === $original_url ) {
			return $result;
		}

		$result['redirect_url'] = $final_url;

		// Parse both URLs.
		$orig_host_parsed  = wp_parse_url( $original_url, PHP_URL_HOST );
		$orig_host         = strtolower( $orig_host_parsed ? $orig_host_parsed : '' );
		$final_host_parsed = wp_parse_url( $final_url, PHP_URL_HOST );
		$final_host        = strtolower( $final_host_parsed ? $final_host_parsed : '' );
		$orig_path_parsed  = wp_parse_url( $original_url, PHP_URL_PATH );
		$orig_path         = $orig_path_parsed ? $orig_path_parsed : '';
		$final_path_parsed = wp_parse_url( $final_url, PHP_URL_PATH );
		$final_path        = $final_path_parsed ? $final_path_parsed : '';

		// Extract root domain (last 2 parts, or last 3 for country TLDs like .co.uk).
		$orig_root  = self::get_root_domain( $orig_host );
		$final_root = self::get_root_domain( $final_host );

		// Check if domains share the same root.
		$same_root_domain = ( $orig_root === $final_root );

		// If same root domain, this is a normal redirect (subdomain change, www, etc.).
		if ( $same_root_domain ) {
			// Exception: redirect to root "/" when original had a specific path.
			// This usually means content was removed and site redirects to homepage.
			if ( $orig_path && '/' !== $orig_path && strlen( $orig_path ) > 1 ) {
				// Check if final path is root or very short.
				if ( empty( $final_path ) || '/' === $final_path ) {
					$result['is_broken'] = true;
					$result['reason']    = 'redirect_to_root';
					return $result;
				}
			}

			// Same root domain, not a redirect to root — this is OK.
			return $result;
		}

		// Different root domain — this is suspicious.
		$result['is_broken'] = true;
		$result['reason']    = 'redirect_different_domain';
		return $result;
	}

	/**
	 * Extract root domain from hostname.
	 *
	 * Handles multi-part TLDs like .co.uk, .com.br, etc.
	 * Also handles special organizational domains like europa.eu where
	 * all subdomains (ec.europa.eu, commission.europa.eu) should be
	 * considered as the same root domain.
	 *
	 * @param string $host Full hostname (e.g., "www.example.co.uk").
	 * @return string Root domain (e.g., "example.co.uk").
	 */
	private static function get_root_domain( $host ) {
		if ( empty( $host ) ) {
			return '';
		}

		// Remove www. prefix.
		$host = preg_replace( '/^www\./i', '', $host );

		// Special organizational domains where all subdomains belong to the same organization.
		// These are treated as the root domain itself (e.g., ec.europa.eu -> europa.eu).
		$org_domains = array(
			'europa.eu',    // EU institutions (ec.europa.eu, commission.europa.eu, etc.).
			'gov.uk',       // UK government.
			'gov.au',       // Australian government.
			'gc.ca',        // Canadian government.
			'go.jp',        // Japanese government.
		);

		foreach ( $org_domains as $org_domain ) {
			if ( $host === $org_domain || substr( $host, -strlen( '.' . $org_domain ) ) === '.' . $org_domain ) {
				return $org_domain;
			}
		}

		// Known multi-part TLDs.
		$multi_tlds = array(
			'co.uk',
			'org.uk',
			'me.uk',
			'ac.uk',
			'com.au',
			'net.au',
			'org.au',
			'edu.au',
			'co.nz',
			'net.nz',
			'org.nz',
			'com.br',
			'org.br',
			'net.br',
			'co.jp',
			'ne.jp',
			'or.jp',
			'ac.jp',
			'com.cn',
			'net.cn',
			'org.cn',
			'co.in',
			'net.in',
			'org.in',
			'co.za',
			'org.za',
			'net.za',
			'com.mx',
			'org.mx',
			'gob.mx',
			'com.ar',
			'org.ar',
			'gov.ar',
			'co.kr',
			'or.kr',
			'ne.kr',
			'com.sg',
			'org.sg',
			'edu.sg',
			'com.hk',
			'org.hk',
			'edu.hk',
			'com.tw',
			'org.tw',
			'edu.tw',
			'co.il',
			'org.il',
			'ac.il',
			'com.pl',
			'org.pl',
			'net.pl',
			'com.tr',
			'org.tr',
			'edu.tr',
			'com.ua',
			'org.ua',
			'net.ua',
			'com.ru',
			'org.ru',
			'net.ru',
		);

		$parts     = explode( '.', $host );
		$num_parts = count( $parts );

		if ( $num_parts <= 2 ) {
			return $host;
		}

		// Check for multi-part TLD.
		$last_two = $parts[ $num_parts - 2 ] . '.' . $parts[ $num_parts - 1 ];

		if ( in_array( $last_two, $multi_tlds, true ) ) {
			// Include one more part for multi-part TLD.
			if ( $num_parts >= 3 ) {
				return $parts[ $num_parts - 3 ] . '.' . $last_two;
			}
			return $host;
		}

		// Standard TLD — return last 2 parts.
		return $last_two;
	}

	/**
	 * Detect domain parking pages.
	 * Signatures are pre-lowercased, so we only lowercase the body once.
	 *
	 * @param string $body The page body content.
	 * @return bool True if page appears to be a parked domain.
	 */
	private static function is_parked( $body ) {
		if ( empty( $body ) ) {
			return false;
		}

		$body_lower = strtolower( $body );

		// Signatures are already lowercase — no strtolower() needed in loop.
		foreach ( self::$parking_signatures as $signature ) {
			if ( strpos( $body_lower, $signature ) !== false ) {
				return true;
			}
		}

		// Additional heuristic: very small page with typical parking structure.
		if ( strlen( $body ) < 5000 ) {
			$link_count = substr_count( $body_lower, '<a ' );
			if ( $link_count > 5 && strpos( $body_lower, 'sponsored' ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check content-type mismatch.
	 * We expect text/html for regular page links.
	 *
	 * @param string $url          The URL being checked.
	 * @param string $content_type The content-type header value.
	 * @return bool True if there's a content-type mismatch.
	 */
	private static function is_content_type_mismatch( $url, $content_type ) {
		if ( empty( $content_type ) ) {
			return false;
		}

		$content_type = strtolower( $content_type );

		// We expect HTML for regular links.
		$expected_html = true;

		// Check if URL suggests a specific file type.
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( $path ) {
			if ( preg_match( '/\.(pdf|doc|docx|xls|xlsx|zip|rar|gz|tar)$/i', $path ) ) {
				$expected_html = false;
			}
		}

		if ( $expected_html ) {
			// If we expect HTML but got something binary/unexpected.
			if ( false === strpos( $content_type, 'text/html' )
				&& false === strpos( $content_type, 'text/plain' )
				&& false === strpos( $content_type, 'application/xhtml' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect soft 404 (page returns HTTP 200 but actually shows "not found").
	 *
	 * @param string $body The page body content.
	 * @return bool True if page appears to be a soft 404.
	 */
	private static function is_soft_404( $body ) {
		if ( empty( $body ) || strlen( $body ) > 200000 ) {
			return false;
		}

		// Extract <title> content.
		$title = '';
		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $body, $m ) ) {
			$title = strtolower( trim( $m[1] ) );
		}

		$soft_404_title_patterns = array(
			'404',
			'not found',
			'page not found',
			'страница не найдена',
			'ничего не найдено',
			'seite nicht gefunden',
			'page introuvable',
			'página no encontrada',
		);

		foreach ( $soft_404_title_patterns as $pattern ) {
			if ( strpos( $title, $pattern ) !== false ) {
				return true;
			}
		}

		// Check body for strong signals (but only in small pages to avoid false positives).
		if ( strlen( $body ) < 50000 ) {
			$body_lower = strtolower( $body );
			// Check for explicit "not found" messages in prominent positions.
			$body_patterns = array(
				'<h1>404</h1>',
				'<h1>page not found</h1>',
				'<h1>not found</h1>',
				'<h1>страница не найдена</h1>',
				'class="error-404"',
				'class="error404"',
				'id="error-404"',
			);

			foreach ( $body_patterns as $pattern ) {
				if ( strpos( $body_lower, $pattern ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check Wayback Machine availability for a URL at a specific date.
	 *
	 * @param string $url       The URL to check.
	 * @param string $timestamp Wayback timestamp (YYYYMMDD format).
	 *
	 * @return array  [ 'available' => bool, 'wayback_url' => string ]
	 */
	public static function check_wayback( $url, $timestamp = '' ) {
		$api_url = 'https://archive.org/wayback/available?url=' . rawurlencode( $url );
		if ( ! empty( $timestamp ) ) {
			$api_url .= '&timestamp=' . $timestamp;
		}

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'   => 10,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'available'   => false,
				'wayback_url' => '',
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['archived_snapshots']['closest']['available'] ) ) {
			return array(
				'available'   => true,
				'wayback_url' => $body['archived_snapshots']['closest']['url'],
			);
		}

		return array(
			'available'   => false,
			'wayback_url' => '',
		);
	}

	/**
	 * Build Wayback Machine URL with post date timestamp.
	 *
	 * NEVER produces a wildcard URL — always uses a real timestamp.
	 * The Wayback Machine will automatically redirect to the closest
	 * available snapshot for any given timestamp.
	 *
	 * @param string $url       The original URL to archive.
	 * @param string $post_date The post/page publication date (Y-m-d H:i:s format).
	 *
	 * @return string Complete Wayback Machine URL with timestamp.
	 */
	public static function build_wayback_url( $url, $post_date ) {
		if ( ! empty( $post_date ) ) {
			// Use the post publication date as the timestamp.
			// This increases the chance of finding a snapshot from when
			// the link was still alive and relevant to the content.
			$timestamp = wp_date( 'YmdHis', strtotime( $post_date ) );
		} else {
			// Fallback: 4 years ago — Wayback will redirect to closest snapshot.
			// Using a date in the past increases the chance of finding an older
			// snapshot that matches the original content.
			$timestamp = wp_date( 'YmdHis', strtotime( '-4 years' ) );
		}
		return 'https://web.archive.org/web/' . $timestamp . '/' . $url;
	}

	/**
	 * Classify WP error into a short reason code.
	 *
	 * @param string $message The error message to classify.
	 * @return string A short reason code.
	 */
	private static function classify_wp_error( $message ) {
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

	/**
	 * Convert broken result to uncheckable for .gov/.edu domains.
	 *
	 * Government and education domains often have strict security policies
	 * that block automated checks. Negative results may be false positives.
	 * Positive results (ok) are kept as-is since if we got through, the resource exists.
	 *
	 * @param array  $result     The check result.
	 * @param bool   $is_gov_edu Whether domain is .gov/.edu.
	 * @param string $url        The URL being checked.
	 * @param int    $link_id    Link ID for logging.
	 * @return array Modified result.
	 */
	private static function maybe_convert_to_uncheckable( $result, $is_gov_edu, $url, $link_id ) {
		// Only convert broken results for .gov/.edu domains.
		if ( 'broken' === $result['status'] && $is_gov_edu ) {
			$original_reason       = $result['fail_reason'];
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'gov_edu_unreliable';

			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$result['http_code'],
				'check_gov_edu_convert',
				sprintf( 'Converted broken→uncheckable for .gov/.edu domain (original: %s)', $original_reason )
			);
		}

		return $result;
	}
}
