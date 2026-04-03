<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Social Media Link Checker — validates links to social networks.
 *
 * Handles verification of links to:
 * - Twitter/X
 * - LinkedIn
 * - Instagram
 * - Facebook
 * - Pinterest
 *
 * Unlike the main checker which marks social domains as "uncheckable" by default,
 * this class actually attempts to verify each link and only marks it as "uncheckable"
 * if bot detection is encountered. If the link clearly exists or doesn't exist,
 * it returns the appropriate status.
 *
 * Results are cached using WordPress transients to reduce API calls and
 * avoid triggering rate limits on subsequent checks of the same URL.
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Social_Checker
 *
 * Validates links to social networks with platform-specific detection.
 */
class ABLR_Social_Checker {

	/**
	 * Cache expiration time in seconds.
	 * Default: 7 days for confirmed results.
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = 604800;

	/**
	 * Cache expiration for uncheckable results (shorter to allow retry).
	 * Default: 24 hours.
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION_UNCHECKABLE = 86400;

	/**
	 * Social platform patterns for URL detection.
	 * Each platform has patterns to identify its URLs.
	 *
	 * @var array
	 */
	private static $platform_patterns = array(
		'twitter'   => array(
			'domains'      => array( 'twitter.com', 'x.com' ),
			// Matches profile URLs: twitter.com/username, x.com/username.
			// Matches tweet URLs: twitter.com/username/status/123456.
			'path_pattern' => '#^/[a-zA-Z0-9_]{1,15}(/status/\d+)?/?$#',
		),
		'linkedin'  => array(
			'domains'      => array( 'linkedin.com' ),
			// Matches profile URLs: linkedin.com/in/username.
			// Matches company URLs: linkedin.com/company/name.
			// Matches posts: linkedin.com/posts/username_activity-123456.
			'path_pattern' => '#^/(in|company|posts)/[a-zA-Z0-9_-]+#',
		),
		'instagram' => array(
			'domains'      => array( 'instagram.com' ),
			// Matches profile URLs: instagram.com/username.
			// Matches post URLs: instagram.com/p/ABC123.
			// Matches reel URLs: instagram.com/reel/ABC123.
			'path_pattern' => '#^/([a-zA-Z0-9_.]+|p/[a-zA-Z0-9_-]+|reel/[a-zA-Z0-9_-]+)/?$#',
		),
		'facebook'  => array(
			'domains'      => array( 'facebook.com', 'fb.com' ),
			// Matches profile/page URLs: facebook.com/username or facebook.com/pagename.
			// Matches post URLs: facebook.com/username/posts/123456.
			// Matches photo URLs: facebook.com/photo/?fbid=123456.
			'path_pattern' => '#^/([a-zA-Z0-9.]+|photo/?\?|[a-zA-Z0-9.]+/posts/)#',
		),
		'pinterest' => array(
			'domains'      => array( 'pinterest.com' ),
			// Matches profile URLs: pinterest.com/username.
			// Matches pin URLs: pinterest.com/pin/123456.
			// Matches board URLs: pinterest.com/username/boardname.
			'path_pattern' => '#^/([a-zA-Z0-9_]+|pin/\d+)#',
		),
	);

	/**
	 * Browser-like headers (same as main checker).
	 *
	 * @var array
	 */
	private static $browser_headers = array(
		'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
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
	);

	/**
	 * Browser User-Agent string.
	 *
	 * @var string
	 */
	private static $browser_user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

	/**
	 * Check if URL is a social media link that this class handles.
	 *
	 * @param string $url The URL to check.
	 * @return string|false Platform name (twitter, linkedin, etc.) or false if not a social URL.
	 */
	public static function get_platform( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		$host = strtolower( $host );
		$host = preg_replace( '/^www\./i', '', $host );
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = ( $path ) ? $path : '/';

		foreach ( self::$platform_patterns as $platform => $config ) {
			// Check if domain matches.
			if ( in_array( $host, $config['domains'], true ) ) {
				// Check if path matches expected pattern.
				if ( preg_match( $config['path_pattern'], $path ) ) {
					return $platform;
				}
				// Domain matches but path doesn't look like a specific resource.
				// Still return platform for homepage or other pages.
				if ( '/' === $path || '' === $path ) {
					return $platform;
				}
				return $platform;
			}
		}

		return false;
	}

	/**
	 * Check a social media URL.
	 *
	 * Returns cached result if available, otherwise performs the check.
	 *
	 * @param string $url     The social media URL to check.
	 * @param int    $link_id Link record ID for logging.
	 * @return array Result with status, http_code, fail_reason, etc.
	 */
	public static function check_url( $url, $link_id = 0 ) {
		$platform = self::get_platform( $url );
		if ( ! $platform ) {
			// Not a social URL we handle — return null to indicate caller should use default check.
			return null;
		}

		// Check cache first.
		$cached = self::get_cached_result( $url );
		if ( false !== $cached ) {
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'social_cache_hit',
				sprintf( 'Using cached result for %s: %s', $platform, $cached['status'] )
			);
			return $cached;
		}

		// Perform platform-specific check.
		$result = self::check_platform_url( $platform, $url, $link_id );

		// Cache the result.
		self::cache_result( $url, $result );

		return $result;
	}

	/**
	 * Perform platform-specific URL check.
	 *
	 * @param string $platform Platform name.
	 * @param string $url      URL to check.
	 * @param int    $link_id  Link ID for logging.
	 * @return array Check result.
	 */
	private static function check_platform_url( $platform, $url, $link_id ) {
		switch ( $platform ) {
			case 'twitter':
				return self::check_twitter( $url, $link_id );
			case 'linkedin':
				return self::check_linkedin( $url, $link_id );
			case 'instagram':
				return self::check_instagram( $url, $link_id );
			case 'facebook':
				return self::check_facebook( $url, $link_id );
			case 'pinterest':
				return self::check_pinterest( $url, $link_id );
			default:
				return self::check_generic_social( $url, $link_id, $platform );
		}
	}

	/**
	 * Check Twitter/X URL.
	 *
	 * Twitter API requires authentication. We use HTTP check with analysis:
	 * - 404 → broken (user/tweet doesn't exist)
	 * - 200 with "This account doesn't exist" → broken
	 * - 200 with valid profile/tweet data → ok
	 * - 403/429/bot detection → uncheckable
	 *
	 * @param string $url     Twitter URL.
	 * @param int    $link_id Link ID.
	 * @return array Result.
	 */
	private static function check_twitter( $url, $link_id ) {
		$result = self::init_result();

		$response = self::make_request( $url );

		if ( is_wp_error( $response ) ) {
			return self::handle_connection_error( $response, $url, $link_id, 'twitter' );
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$body                = wp_remote_retrieve_body( $response );
		$result['http_code'] = $http_code;

		// Clear 404 - account/tweet doesn't exist.
		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'twitter_not_found';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_twitter',
				'Twitter resource not found (HTTP 404)'
			);
			return $result;
		}

		// Bot detection responses.
		if ( in_array( $http_code, array( 429, 503 ), true ) ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'rate_limited';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_twitter',
				sprintf( 'Twitter rate limited (HTTP %d)', $http_code )
			);
			return $result;
		}

		// 403 could be suspended account OR bot detection.
		// Check body for clues.
		if ( 403 === $http_code ) {
			if ( false !== strpos( $body, 'suspended' ) || false !== strpos( $body, 'Account suspended' ) ) {
				$result['status']      = 'broken';
				$result['fail_reason'] = 'twitter_suspended';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_twitter',
					'Twitter account suspended'
				);
				return $result;
			}
			// Likely bot detection.
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'bot_protected';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_twitter',
				'Twitter blocked request (likely bot detection)'
			);
			return $result;
		}

		// HTTP 200 - analyze body.
		if ( 200 === $http_code ) {
			$body_lower = strtolower( $body );

			// Check for "account doesn't exist" type messages.
			$not_found_patterns = array(
				'this account doesn\'t exist',
				'hmm...this page doesn\'t exist',
				'page doesn\'t exist',
				'something went wrong',
			);

			foreach ( $not_found_patterns as $pattern ) {
				if ( strpos( $body_lower, $pattern ) !== false ) {
					$result['status']      = 'broken';
					$result['fail_reason'] = 'twitter_not_found';
					ABLR_Database::add_log(
						$link_id,
						$url,
						'',
						$http_code,
						'social_twitter',
						'Twitter page shows "doesn\'t exist" message'
					);
					return $result;
				}
			}

			// Check for bot challenge / JS redirect pages.
			if ( strpos( $body_lower, 'please wait' ) !== false && strpos( $body_lower, 'redirecting' ) !== false ) {
				$result['status']      = 'uncheckable';
				$result['fail_reason'] = 'bot_protected';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_twitter',
					'Twitter showing bot challenge page'
				);
				return $result;
			}

			// Looks like valid content.
			$result['status'] = 'ok';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_twitter',
				'Twitter resource exists'
			);
			return $result;
		}

		// Other error codes (5xx, etc.) - treat as uncheckable for now.
		if ( $http_code >= 500 ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'server_error';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_twitter',
				sprintf( 'Twitter server error (HTTP %d)', $http_code )
			);
			return $result;
		}

		// Any other 4xx - broken.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_twitter',
				sprintf( 'Twitter HTTP error %d', $http_code )
			);
			return $result;
		}

		// 2xx/3xx - assume OK.
		$result['status'] = 'ok';
		return $result;
	}

	/**
	 * Check LinkedIn URL.
	 *
	 * LinkedIn is very aggressive with bot detection.
	 * - authwall redirect → uncheckable (can't verify without login)
	 * - 404 → broken
	 * - 999 (LinkedIn's bot detection code) → uncheckable
	 * - 200 with profile data → ok
	 *
	 * @param string $url     LinkedIn URL.
	 * @param int    $link_id Link ID.
	 * @return array Result.
	 */
	private static function check_linkedin( $url, $link_id ) {
		$result = self::init_result();

		$response = self::make_request( $url );

		if ( is_wp_error( $response ) ) {
			return self::handle_connection_error( $response, $url, $link_id, 'linkedin' );
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$body                = wp_remote_retrieve_body( $response );
		$result['http_code'] = $http_code;

		// LinkedIn's custom bot detection status code.
		if ( 999 === $http_code ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'bot_protected';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_linkedin',
				'LinkedIn bot detection triggered (HTTP 999)'
			);
			return $result;
		}

		// Clear 404.
		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'linkedin_not_found';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_linkedin',
				'LinkedIn profile/page not found (HTTP 404)'
			);
			return $result;
		}

		// Rate limiting.
		if ( 429 === $http_code ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'rate_limited';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_linkedin',
				'LinkedIn rate limited'
			);
			return $result;
		}

		// Check for authwall redirect in body (requires login to view).
		if ( 200 === $http_code || 302 === $http_code || 303 === $http_code ) {
			$body_lower = strtolower( $body );

			// Check for authwall patterns.
			if ( false !== strpos( $body_lower, 'authwall' ) ||
				( false !== strpos( $body_lower, 'login' ) && false !== strpos( $body_lower, 'join now' ) ) ) {
				// LinkedIn requires login - we can't verify this link.
				$result['status']      = 'uncheckable';
				$result['fail_reason'] = 'requires_login';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_linkedin',
					'LinkedIn requires login to view this content'
				);
				return $result;
			}

			// Check for "page not found" in body.
			if ( strpos( $body_lower, 'page not found' ) !== false ||
				strpos( $body_lower, 'this page doesn\'t exist' ) !== false ) {
				$result['status']      = 'broken';
				$result['fail_reason'] = 'linkedin_not_found';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_linkedin',
					'LinkedIn page shows not found message'
				);
				return $result;
			}

			// Has real content - likely exists.
			if ( strlen( $body ) > 10000 && 200 === $http_code ) {
				$result['status'] = 'ok';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_linkedin',
					'LinkedIn resource appears to exist'
				);
				return $result;
			}
		}

		// Server errors.
		if ( $http_code >= 500 ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'server_error';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_linkedin',
				sprintf( 'LinkedIn server error (HTTP %d)', $http_code )
			);
			return $result;
		}

		// Other 4xx - likely broken.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_linkedin',
				sprintf( 'LinkedIn HTTP error %d', $http_code )
			);
			return $result;
		}

		// Default - uncheckable because LinkedIn is tricky.
		$result['status']      = 'uncheckable';
		$result['fail_reason'] = 'ambiguous_response';
		ABLR_Database::add_log(
			$link_id,
			$url,
			'',
			$http_code,
			'social_linkedin',
			'LinkedIn response ambiguous, cannot confirm status'
		);
		return $result;
	}

	/**
	 * Check Instagram URL.
	 *
	 * Instagram heavily relies on JavaScript. HTTP check can detect:
	 * - 404 → broken
	 * - "Sorry, this page isn't available" in body → broken
	 * - Login wall / bot detection → uncheckable
	 * - Valid looking response → ok
	 *
	 * @param string $url     Instagram URL.
	 * @param int    $link_id Link ID.
	 * @return array Result.
	 */
	private static function check_instagram( $url, $link_id ) {
		$result = self::init_result();

		$response = self::make_request( $url );

		if ( is_wp_error( $response ) ) {
			return self::handle_connection_error( $response, $url, $link_id, 'instagram' );
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$body                = wp_remote_retrieve_body( $response );
		$result['http_code'] = $http_code;

		// Clear 404.
		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'instagram_not_found';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_instagram',
				'Instagram resource not found (HTTP 404)'
			);
			return $result;
		}

		// Bot detection / rate limiting.
		if ( in_array( $http_code, array( 429, 503 ), true ) ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'rate_limited';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_instagram',
				sprintf( 'Instagram rate limited (HTTP %d)', $http_code )
			);
			return $result;
		}

		// Analyze body for HTTP 200.
		if ( 200 === $http_code ) {
			$body_lower = strtolower( $body );

			// "Sorry, this page isn't available" - account/post deleted.
			if ( strpos( $body_lower, 'sorry, this page isn\'t available' ) !== false ||
				strpos( $body_lower, 'page isn\'t available' ) !== false ) {
				$result['status']      = 'broken';
				$result['fail_reason'] = 'instagram_not_found';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_instagram',
					'Instagram shows "page isn\'t available" message'
				);
				return $result;
			}

			// Login required page.
			if ( strpos( $body_lower, 'log in' ) !== false &&
				strpos( $body_lower, 'sign up' ) !== false &&
				strlen( $body ) < 100000 ) {
				// Small page with login prompt - might be blocking.
				// But Instagram often shows this for private accounts.
				// If we see "private account" it's still valid.
				if ( strpos( $body_lower, 'private' ) !== false ) {
					$result['status'] = 'ok';
					ABLR_Database::add_log(
						$link_id,
						$url,
						'',
						$http_code,
						'social_instagram',
						'Instagram private account (exists but restricted)'
					);
					return $result;
				}

				// Can't determine if it's bot block or just login requirement.
				$result['status']      = 'uncheckable';
				$result['fail_reason'] = 'requires_login';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_instagram',
					'Instagram requires login to view content'
				);
				return $result;
			}

			// Check for challenge / CAPTCHA page.
			if ( strpos( $body_lower, 'challenge' ) !== false && strpos( $body_lower, 'security' ) !== false ) {
				$result['status']      = 'uncheckable';
				$result['fail_reason'] = 'bot_protected';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_instagram',
					'Instagram showing security challenge'
				);
				return $result;
			}

			// Looks like valid content (large page with Instagram data).
			if ( strlen( $body ) > 50000 ) {
				$result['status'] = 'ok';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_instagram',
					'Instagram resource appears to exist'
				);
				return $result;
			}
		}

		// Server errors.
		if ( $http_code >= 500 ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'server_error';
			return $result;
		}

		// Other 4xx.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;
			return $result;
		}

		// Default for ambiguous cases.
		$result['status']      = 'uncheckable';
		$result['fail_reason'] = 'ambiguous_response';
		return $result;
	}

	/**
	 * Check Facebook URL.
	 *
	 * Facebook has complex bot detection. Strategy:
	 * - Check Open Graph tags in response (fb:page_id, og:url)
	 * - Look for "content isn't available" messages
	 * - Detect login walls
	 *
	 * @param string $url     Facebook URL.
	 * @param int    $link_id Link ID.
	 * @return array Result.
	 */
	private static function check_facebook( $url, $link_id ) {
		$result = self::init_result();

		$response = self::make_request( $url );

		if ( is_wp_error( $response ) ) {
			return self::handle_connection_error( $response, $url, $link_id, 'facebook' );
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$body                = wp_remote_retrieve_body( $response );
		$result['http_code'] = $http_code;

		// Clear 404.
		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'facebook_not_found';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_facebook',
				'Facebook resource not found (HTTP 404)'
			);
			return $result;
		}

		// Rate limiting.
		if ( in_array( $http_code, array( 429, 503 ), true ) ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'rate_limited';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_facebook',
				sprintf( 'Facebook rate limited (HTTP %d)', $http_code )
			);
			return $result;
		}

		// Analyze body.
		if ( 200 === $http_code ) {
			$body_lower = strtolower( $body );

			// "This content isn't available" messages.
			$not_found_patterns = array(
				'this content isn\'t available',
				'this page isn\'t available',
				'the link you followed may be broken',
				'the page you requested cannot be displayed',
				'sorry, this page isn\'t available',
			);

			foreach ( $not_found_patterns as $pattern ) {
				if ( strpos( $body_lower, $pattern ) !== false ) {
					$result['status']      = 'broken';
					$result['fail_reason'] = 'facebook_not_found';
					ABLR_Database::add_log(
						$link_id,
						$url,
						'',
						$http_code,
						'social_facebook',
						'Facebook shows content unavailable message'
					);
					return $result;
				}
			}

			// Check for login requirement / bot detection.
			// Facebook often redirects to login for non-public content.
			if ( strpos( $body_lower, 'you must log in' ) !== false ||
				( strpos( $body_lower, 'log in' ) !== false && strlen( $body ) < 50000 ) ) {

				// Check if this is a legitimate "login to see more" or bot block.
				// Public pages usually have og:title even without login.
				if ( strpos( $body, 'og:title' ) !== false ) {
					// Has Open Graph data - page exists.
					$result['status'] = 'ok';
					ABLR_Database::add_log(
						$link_id,
						$url,
						'',
						$http_code,
						'social_facebook',
						'Facebook page exists (has OG tags)'
					);
					return $result;
				}

				$result['status']      = 'uncheckable';
				$result['fail_reason'] = 'requires_login';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_facebook',
					'Facebook requires login to view content'
				);
				return $result;
			}

			// Check for valid Facebook page indicators.
			if ( strpos( $body, 'fb:page_id' ) !== false ||
				strpos( $body, 'fb:profile_id' ) !== false ||
				strpos( $body, 'og:url' ) !== false ) {
				$result['status'] = 'ok';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_facebook',
					'Facebook resource confirmed via meta tags'
				);
				return $result;
			}

			// Large response likely has content.
			if ( strlen( $body ) > 100000 ) {
				$result['status'] = 'ok';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_facebook',
					'Facebook resource appears to exist (large response)'
				);
				return $result;
			}
		}

		// Server errors.
		if ( $http_code >= 500 ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'server_error';
			return $result;
		}

		// Other 4xx.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;
			return $result;
		}

		// Default.
		$result['status']      = 'uncheckable';
		$result['fail_reason'] = 'ambiguous_response';
		return $result;
	}

	/**
	 * Check Pinterest URL.
	 *
	 * Pinterest has oEmbed API that we can try first.
	 * Fallback to HTTP check with body analysis.
	 *
	 * @param string $url     Pinterest URL.
	 * @param int    $link_id Link ID.
	 * @return array Result.
	 */
	private static function check_pinterest( $url, $link_id ) {
		$result = self::init_result();

		// Try oEmbed first for pins.
		if ( strpos( $url, '/pin/' ) !== false ) {
			$oembed_result = self::check_pinterest_oembed( $url, $link_id );
			if ( 'uncheckable' !== $oembed_result['status'] ) {
				return $oembed_result;
			}
			// oEmbed failed or ambiguous - try HTTP.
		}

		$response = self::make_request( $url );

		if ( is_wp_error( $response ) ) {
			return self::handle_connection_error( $response, $url, $link_id, 'pinterest' );
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$body                = wp_remote_retrieve_body( $response );
		$result['http_code'] = $http_code;

		// Clear 404.
		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'pinterest_not_found';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_pinterest',
				'Pinterest resource not found (HTTP 404)'
			);
			return $result;
		}

		// Rate limiting.
		if ( in_array( $http_code, array( 429, 503 ), true ) ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'rate_limited';
			return $result;
		}

		// Analyze body for HTTP 200.
		if ( 200 === $http_code ) {
			$body_lower = strtolower( $body );

			// "Oops!" or "Sorry" pages.
			if ( strpos( $body_lower, 'oops!' ) !== false ||
				strpos( $body_lower, 'sorry, we couldn\'t find that page' ) !== false ||
				strpos( $body_lower, 'we couldn\'t find that page' ) !== false ) {
				$result['status']      = 'broken';
				$result['fail_reason'] = 'pinterest_not_found';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_pinterest',
					'Pinterest shows page not found message'
				);
				return $result;
			}

			// Check for valid Pinterest data.
			if ( strpos( $body, 'pinterestapp:' ) !== false ||
				strpos( $body, 'og:site_name" content="Pinterest' ) !== false ) {
				$result['status'] = 'ok';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_pinterest',
					'Pinterest resource confirmed'
				);
				return $result;
			}

			// Large response with Pinterest domain.
			if ( strlen( $body ) > 50000 && strpos( $body, 'pinterest' ) !== false ) {
				$result['status'] = 'ok';
				return $result;
			}
		}

		// Server errors.
		if ( $http_code >= 500 ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'server_error';
			return $result;
		}

		// Other 4xx.
		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;
			return $result;
		}

		// Default.
		$result['status']      = 'uncheckable';
		$result['fail_reason'] = 'ambiguous_response';
		return $result;
	}

	/**
	 * Check Pinterest pin via oEmbed API.
	 *
	 * Uses ABLR_Checker::browser_request() for proxy support.
	 *
	 * @param string $url     Pinterest pin URL.
	 * @param int    $link_id Link ID.
	 * @return array Result.
	 */
	private static function check_pinterest_oembed( $url, $link_id ) {
		$result = self::init_result();

		$oembed_url = 'https://www.pinterest.com/oembed.json?url=' . rawurlencode( $url );

		// Use browser_request for proxy support.
		$response = ABLR_Checker::browser_request( $oembed_url, true );

		if ( is_wp_error( $response ) ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'oembed_error';
			return $result;
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$result['http_code'] = $http_code;

		if ( 200 === $http_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! empty( $data['title'] ) || ! empty( $data['author_name'] ) ) {
				$result['status'] = 'ok';
				ABLR_Database::add_log(
					$link_id,
					$url,
					'',
					$http_code,
					'social_pinterest_oembed',
					'Pinterest pin confirmed via oEmbed'
				);
				return $result;
			}
		}

		if ( 404 === $http_code || 400 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'pinterest_not_found';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				$http_code,
				'social_pinterest_oembed',
				'Pinterest pin not found via oEmbed'
			);
			return $result;
		}

		// oEmbed inconclusive.
		$result['status']      = 'uncheckable';
		$result['fail_reason'] = 'oembed_inconclusive';
		return $result;
	}

	/**
	 * Generic social media check (fallback).
	 *
	 * @param string $url      URL.
	 * @param int    $link_id  Link ID.
	 * @param string $platform Platform name.
	 * @return array Result.
	 */
	private static function check_generic_social( $url, $link_id, $platform ) {
		$result = self::init_result();

		$response = self::make_request( $url );

		if ( is_wp_error( $response ) ) {
			return self::handle_connection_error( $response, $url, $link_id, $platform );
		}

		$http_code           = wp_remote_retrieve_response_code( $response );
		$result['http_code'] = $http_code;

		if ( 404 === $http_code ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = $platform . '_not_found';
			return $result;
		}

		if ( 200 === $http_code ) {
			$result['status'] = 'ok';
			return $result;
		}

		if ( in_array( $http_code, array( 403, 429, 503, 999 ), true ) ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'bot_protected';
			return $result;
		}

		if ( $http_code >= 400 ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'http_' . $http_code;
			return $result;
		}

		$result['status']      = 'uncheckable';
		$result['fail_reason'] = 'ambiguous_response';
		return $result;
	}

	/**
	 * Initialize empty result array.
	 *
	 * @return array Default result structure.
	 */
	private static function init_result() {
		return array(
			'status'            => 'ok',
			'http_code'         => 0,
			'redirect_url'      => '',
			'fail_reason'       => '',
			'content_type'      => '',
			'wayback_available' => false,
			'wayback_url'       => '',
		);
	}

	/**
	 * Make HTTP request with browser emulation and proxy support.
	 *
	 * Uses ABLR_Checker::browser_request() to ensure proxy rotation
	 * is applied when proxies are configured in plugin settings.
	 *
	 * @param string $url URL to request.
	 * @return array|WP_Error Response or error.
	 */
	private static function make_request( $url ) {
		// Use the main checker's browser_request method which handles proxy rotation.
		return ABLR_Checker::browser_request( $url, true );
	}

	/**
	 * Handle connection error and classify it.
	 *
	 * @param WP_Error $error    WP_Error object.
	 * @param string   $url      URL that was checked.
	 * @param int      $link_id  Link ID.
	 * @param string   $platform Platform name.
	 * @return array Result.
	 */
	private static function handle_connection_error( $error, $url, $link_id, $platform ) {
		$result  = self::init_result();
		$message = $error->get_error_message();

		// Connection errors that suggest bot blocking.
		if ( strpos( $message, 'CONNECT tunnel' ) !== false ||
			strpos( $message, 'response 403' ) !== false ||
			strpos( $message, '403' ) !== false ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'bot_protected';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'social_' . $platform,
				sprintf( 'Blocked by bot protection: %s', $message )
			);
			return $result;
		}

		// DNS failure likely means domain doesn't exist.
		if ( strpos( strtolower( $message ), 'could not resolve' ) !== false ||
			strpos( strtolower( $message ), 'dns' ) !== false ) {
			$result['status']      = 'broken';
			$result['fail_reason'] = 'dns_failure';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'social_' . $platform,
				sprintf( 'DNS resolution failed: %s', $message )
			);
			return $result;
		}

		// Timeouts could be temporary - uncheckable.
		if ( strpos( strtolower( $message ), 'timeout' ) !== false ||
			strpos( strtolower( $message ), 'timed out' ) !== false ) {
			$result['status']      = 'uncheckable';
			$result['fail_reason'] = 'timeout';
			ABLR_Database::add_log(
				$link_id,
				$url,
				'',
				0,
				'social_' . $platform,
				sprintf( 'Request timed out: %s', $message )
			);
			return $result;
		}

		// Other connection errors.
		$result['status']      = 'uncheckable';
		$result['fail_reason'] = 'connection_error';
		ABLR_Database::add_log(
			$link_id,
			$url,
			'',
			0,
			'social_' . $platform,
			sprintf( 'Connection error: %s', $message )
		);
		return $result;
	}

	/**
	 * Get cached result for URL.
	 *
	 * @param string $url URL to look up.
	 * @return array|false Cached result or false if not cached.
	 */
	private static function get_cached_result( $url ) {
		$cache_key = 'ablr_social_' . md5( $url );
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
	 * Cache result for URL.
	 *
	 * @param string $url    URL to cache result for.
	 * @param array  $result Result to cache.
	 */
	private static function cache_result( $url, $result ) {
		$cache_key = 'ablr_social_' . md5( $url );

		// Use shorter expiration for uncheckable results (to allow retry).
		$expiration = ( 'uncheckable' === $result['status'] )
			? self::CACHE_EXPIRATION_UNCHECKABLE
			: self::CACHE_EXPIRATION;

		set_transient( $cache_key, $result, $expiration );
	}

	/**
	 * Clear cached result for a URL.
	 *
	 * @param string $url URL to clear cache for.
	 */
	public static function clear_cache( $url ) {
		$cache_key = 'ablr_social_' . md5( $url );
		delete_transient( $cache_key );
	}

	/**
	 * Clear all social checker cache.
	 * Useful for bulk operations or settings changes.
	 *
	 * @return int Number of cache entries deleted.
	 */
	public static function clear_all_cache() {
		global $wpdb;

		// Delete all transients with our prefix.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time cache cleanup operation.
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_ablr_social_%'
                OR option_name LIKE '_transient_timeout_ablr_social_%'"
		);

		return $deleted;
	}
}
