<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Scanner — extracts links from WordPress content.
 *
 * Parses HTML content to find:
 * 1. All <a href="..."> links
 * 2. Embedded videos from <iframe src="..."> (YouTube, Vimeo, etc.)
 * 3. oEmbed URLs (bare video URLs on their own line)
 * 4. Gutenberg embed blocks (<!-- wp:embed {"url":"..."} -->)
 *
 * Supports both external and internal links (internal is opt-in via settings).
 *
 * Filters out:
 * - Anchor links (#section)
 * - mailto:, tel:, javascript:, data: URLs
 * - Image links (href pointing to image files)
 * - Links without anchor text that are just image wrappers
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Scanner
 *
 * Extracts links from WordPress content for checking.
 */
class ABLR_Scanner {

	/**
	 * Get post types to scan based on settings.
	 *
	 * @return array Array of post type names.
	 */
	public static function get_scan_post_types() {
		$settings        = get_option( 'ablr_settings', array() );
		$scan_post_types = isset( $settings['scan_post_types'] ) ? (array) $settings['scan_post_types'] : array();

		// Backward compat.
		if ( empty( $scan_post_types ) ) {
			if ( ! empty( $settings['scan_posts'] ) ) {
				$scan_post_types[] = 'post';
			}
			if ( ! empty( $settings['scan_pages'] ) ) {
				$scan_post_types[] = 'page';
			}
			if ( ! empty( $settings['scan_posts'] ) ) {
				$custom_types    = get_post_types(
					array(
						'public'   => true,
						'_builtin' => false,
					),
					'names'
				);
				$scan_post_types = array_merge( $scan_post_types, array_values( $custom_types ) );
			}
			$scan_post_types = array_unique( $scan_post_types );
		}

		return $scan_post_types;
	}

	/**
	 * Count total items to scan (fast COUNT query).
	 * Used for progress display without loading all IDs into memory.
	 *
	 * @return int Total number of posts to scan.
	 */
	public static function count_scan_items() {
		global $wpdb;

		$scan_post_types = self::get_scan_post_types();
		if ( empty( $scan_post_types ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $scan_post_types ), '%s' ) );
		// phpcs:disable WordPress.DB
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ({$placeholders}) AND post_status = 'publish'",
				$scan_post_types
			)
		);
		// phpcs:enable

		return (int) $count;
	}

	/**
	 * Collect items to scan in batches using cursor-based pagination.
	 * Memory-efficient for large sites (100K+ posts).
	 *
	 * @param int $last_id  Last processed post ID (cursor). Use 0 for first batch.
	 * @param int $limit    Number of items per batch. Default 1000.
	 * @return array Array of items with 'type' and 'id' keys.
	 */
	public static function collect_scan_items_batch( $last_id = 0, $limit = 1000 ) {
		global $wpdb;

		$scan_post_types = self::get_scan_post_types();
		if ( empty( $scan_post_types ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $scan_post_types ), '%s' ) );

		// Cursor-based pagination: ID > last_id ORDER BY ID ASC
		// This is efficient even for large offsets (unlike OFFSET which scans all rows).
		$query_args = array_merge( $scan_post_types, array( $last_id, $limit ) );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_type FROM {$wpdb->posts}
                 WHERE post_type IN ({$placeholders})
                 AND post_status = 'publish'
                 AND ID > %d
                 ORDER BY ID ASC
                 LIMIT %d",
				$query_args
			)
		);
		// phpcs:enable

		$items = array();
		foreach ( $results as $row ) {
			$items[] = array(
				'type' => $row->post_type,
				'id'   => (int) $row->ID,
			);
		}

		return $items;
	}

	/**
	 * Collect all items to scan based on settings.
	 *
	 * @deprecated Use collect_scan_items_batch() for large sites.
	 * @return array Array of items with 'type' and 'id' keys.
	 */
	public static function collect_scan_items() {
		$items   = array();
		$last_id = 0;
		$limit   = 1000;

		// Use batched collection internally to avoid memory issues.
		while ( true ) {
			$batch = self::collect_scan_items_batch( $last_id, $limit );
			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $batch as $item ) {
				$items[] = $item;
				$last_id = max( $last_id, $item['id'] );
			}
		}

		return $items;
	}

	/**
	 * Extract links from a single item.
	 * Returns both external and (optionally) internal links.
	 * Supports Elementor: extracts links from _elementor_data if present.
	 *
	 * @param array $item Item with 'type' and 'id' keys.
	 * @return array Array of extracted links.
	 */
	public static function extract_links( $item ) {
		$links         = array();
		$type          = $item['type'];
		$settings      = get_option( 'ablr_settings', array() );
		$scan_internal = ! empty( $settings['scan_internal_links'] );

		if ( ! post_type_exists( $type ) ) {
			return $links;
		}

		$post = get_post( $item['id'] );
		if ( ! $post ) {
			return $links;
		}

		// Track URLs to avoid duplicates between post_content and Elementor data.
		$found_urls = array();

		// Check for Elementor data first.
		$elementor_data = get_post_meta( $item['id'], '_elementor_data', true );
		if ( ! empty( $elementor_data ) && is_string( $elementor_data ) ) {
			// Extract links from Elementor JSON.
			$elementor_links = self::parse_links_from_elementor( $elementor_data, $scan_internal );
			foreach ( $elementor_links as $link ) {
				$url_key = md5( $link['url'] );
				if ( ! isset( $found_urls[ $url_key ] ) ) {
					$link['source_type']    = $type;
					$link['source_id']      = $item['id'];
					$links[]                = $link;
					$found_urls[ $url_key ] = true;
				}
			}
		}

		// Also check post_content (fallback HTML or non-Elementor content).
		$found = self::parse_links_from_html( $post->post_content, $scan_internal );
		foreach ( $found as $link ) {
			$url_key = md5( $link['url'] );
			if ( ! isset( $found_urls[ $url_key ] ) ) {
				$link['source_type']    = $type;
				$link['source_id']      = $item['id'];
				$links[]                = $link;
				$found_urls[ $url_key ] = true;
			}
		}

		// Extract embedded video URLs (iframe, oEmbed, Gutenberg blocks).
		$embeds = self::parse_embeds_from_html( $post->post_content );
		foreach ( $embeds as $embed ) {
			$url_key = md5( $embed['url'] );
			if ( ! isset( $found_urls[ $url_key ] ) ) {
				$embed['source_type']   = $type;
				$embed['source_id']     = $item['id'];
				$links[]                = $embed;
				$found_urls[ $url_key ] = true;
			}
		}

		return $links;
	}

	/**
	 * Parse <a href="..."> links from HTML string.
	 *
	 * @param string $html             HTML content to parse.
	 * @param bool   $include_internal Whether to include internal links.
	 * @return array Array of parsed links.
	 */
	public static function parse_links_from_html( $html, $include_internal = false ) {
		$links = array();

		if ( empty( $html ) || ! is_string( $html ) ) {
			return $links;
		}

		if ( ! preg_match_all( '/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER ) ) {
			return $links;
		}

		$site_url = home_url();

		foreach ( $matches as $match ) {
			$url         = trim( $match[1] );
			$anchor_html = $match[2];

			if ( empty( $url ) || '#' === $url[0] || preg_match( '/^(mailto|tel|javascript|data):/i', $url ) ) {
				continue;
			}

			// Convert relative URLs to absolute.
			$is_relative = false;
			if ( ! preg_match( '/^https?:\/\//i', $url ) ) {
				// Protocol-relative URLs (//example.com/...).
				if ( 0 === strpos( $url, '//' ) ) {
					$url = 'https:' . $url;
				} elseif ( '/' === $url[0] ) {
					// Absolute path relative to site root — this is an internal link.
					$url         = rtrim( $site_url, '/' ) . $url;
					$is_relative = true;
				} else {
					// Other relative URLs (no leading /) — skip, too ambiguous.
					continue;
				}
			}

			// Relative URLs are always internal.
			$is_external = $is_relative ? false : self::is_external_url( $url );

			if ( ! $is_external && ! $include_internal ) {
				continue;
			}

			$anchor_text = wp_strip_all_tags( $anchor_html );
			if ( empty( $anchor_text ) && preg_match( '/<img\s/i', $anchor_html ) ) {
				continue;
			}

			$path_part = wp_parse_url( $url, PHP_URL_PATH );
			if ( $path_part && preg_match( '/\.(jpg|jpeg|png|gif|webp|svg|bmp|ico|tiff?)$/i', $path_part ) ) {
				continue;
			}

			$links[] = array(
				'url'         => $url,
				'anchor_text' => mb_substr( trim( $anchor_text ), 0, 512 ),
				'is_internal' => ! $is_external,
			);
		}

		return $links;
	}

	/**
	 * Parse links from Elementor JSON data.
	 *
	 * Elementor stores content in _elementor_data meta field as JSON.
	 * URLs can be found in:
	 * - settings.editor (text-editor widget) — HTML content
	 * - settings.link.url (button, icon widgets)
	 * - settings.url.url (image widget)
	 * - Various other widget settings
	 *
	 * @param string $json_data      Elementor JSON string.
	 * @param bool   $include_internal Whether to include internal links.
	 * @return array Array of extracted links.
	 */
	public static function parse_links_from_elementor( $json_data, $include_internal = false ) {
		$links = array();

		if ( empty( $json_data ) || ! is_string( $json_data ) ) {
			return $links;
		}

		// Decode JSON to find HTML content within settings.
		$data = json_decode( $json_data, true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			// If JSON decode fails, try parsing as raw string (might contain HTML).
			// Unescape JSON slashes first.
			$html_content = str_replace( '\\/', '/', $json_data );
			return self::parse_links_from_html( $html_content, $include_internal );
		}

		// Recursively extract HTML content from Elementor structure.
		$html_parts = self::extract_elementor_html( $data );

		// Also extract direct URL links from Elementor settings.
		$direct_urls = self::extract_elementor_urls( $data );

		// Parse links from collected HTML.
		foreach ( $html_parts as $html ) {
			$found = self::parse_links_from_html( $html, $include_internal );
			$links = array_merge( $links, $found );
		}

		// Add direct URLs.
		foreach ( $direct_urls as $url_data ) {
			$url = $url_data['url'];

			// Skip anchors, mailto, etc.
			if ( empty( $url ) || '#' === $url[0] || preg_match( '/^(mailto|tel|javascript|data):/i', $url ) ) {
				continue;
			}

			// Skip images.
			$path_part = wp_parse_url( $url, PHP_URL_PATH );
			if ( $path_part && preg_match( '/\.(jpg|jpeg|png|gif|webp|svg|bmp|ico|tiff?)$/i', $path_part ) ) {
				continue;
			}

			$is_external = self::is_external_url( $url );
			if ( ! $is_external && ! $include_internal ) {
				continue;
			}

			$links[] = array(
				'url'         => $url,
				'anchor_text' => isset( $url_data['text'] ) ? mb_substr( trim( $url_data['text'] ), 0, 512 ) : '[Elementor link]',
				'is_internal' => ! $is_external,
			);
		}

		return $links;
	}

	/**
	 * Recursively extract HTML content from Elementor data structure.
	 *
	 * @param array $elements Elementor elements array.
	 * @return array Array of HTML strings.
	 */
	private static function extract_elementor_html( $elements ) {
		$html_parts = array();

		if ( ! is_array( $elements ) ) {
			return $html_parts;
		}

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			// Check settings for HTML content.
			if ( isset( $element['settings'] ) && is_array( $element['settings'] ) ) {
				$settings = $element['settings'];

				// Text editor widget — main content is in 'editor'.
				if ( isset( $settings['editor'] ) && is_string( $settings['editor'] ) ) {
					$html_parts[] = $settings['editor'];
				}

				// Other common HTML fields.
				$html_fields = array( 'html', 'content', 'text', 'description', 'caption' );
				foreach ( $html_fields as $field ) {
					if ( isset( $settings[ $field ] ) && is_string( $settings[ $field ] ) ) {
						$html_parts[] = $settings[ $field ];
					}
				}
			}

			// Recurse into child elements.
			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$child_html = self::extract_elementor_html( $element['elements'] );
				$html_parts = array_merge( $html_parts, $child_html );
			}
		}

		return $html_parts;
	}

	/**
	 * Recursively extract direct URL links from Elementor settings.
	 *
	 * @param array $elements Elementor elements array.
	 * @return array Array of URL data (url, text).
	 */
	private static function extract_elementor_urls( $elements ) {
		$urls = array();

		if ( ! is_array( $elements ) ) {
			return $urls;
		}

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( isset( $element['settings'] ) && is_array( $element['settings'] ) ) {
				$settings = $element['settings'];

				// Direct URL fields — link is in 'link', 'url', etc. as {url: '...', is_external: bool}.
				$link_fields = array(
					'link',
					'url',
					'button_link',
					'website_link',
					'image_link',
					'title_link',
					'cta_link',
					'read_more_link',
					'badge_link',
				);
				foreach ( $link_fields as $field ) {
					if ( isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ) {
						if ( isset( $settings[ $field ]['url'] ) && ! empty( $settings[ $field ]['url'] ) ) {
							$urls[] = array(
								'url'  => $settings[ $field ]['url'],
								'text' => isset( $settings['text'] ) ? $settings['text'] :
										( isset( $settings['button_text'] ) ? $settings['button_text'] :
										( isset( $settings['title'] ) ? $settings['title'] : '' ) ),
							);
						}
					}
				}

				// Repeater fields — arrays of items, each may have 'link' or 'url' subfield.
				// Common repeater names in Elementor widgets.
				$repeater_fields = array(
					'social_icon_list',  // Social Icons widget.
					'icon_list',         // Icon List widget.
					'price_list',        // Price List widget.
					'slides',            // Slides widget (Pro).
					'carousel',          // Various carousel widgets.
					'tabs',              // Tabs widget.
					'items',             // Generic items.
					'gallery',           // Gallery widget.
					'testimonials',      // Testimonials widget.
					'team_members',      // Team Members widget.
				);
				foreach ( $repeater_fields as $repeater ) {
					if ( isset( $settings[ $repeater ] ) && is_array( $settings[ $repeater ] ) ) {
						foreach ( $settings[ $repeater ] as $item ) {
							if ( ! is_array( $item ) ) {
								continue;
							}
							// Check for URL fields within repeater item.
							foreach ( $link_fields as $field ) {
								if ( isset( $item[ $field ] ) && is_array( $item[ $field ] ) ) {
									if ( isset( $item[ $field ]['url'] ) && ! empty( $item[ $field ]['url'] ) ) {
										$urls[] = array(
											'url'  => $item[ $field ]['url'],
											'text' => isset( $item['text'] ) ? $item['text'] :
													( isset( $item['title'] ) ? $item['title'] :
													( isset( $item['name'] ) ? $item['name'] : '' ) ),
										);
									}
								}
							}
						}
					}
				}

				// Video widget URL fields (plain string URLs).
				// See: https://github.com/elementor/elementor/blob/main/includes/widgets/video.php.
				$video_url_fields = array(
					'youtube_url',     // YouTube video URL.
					'vimeo_url',       // Vimeo video URL.
					'dailymotion_url', // Dailymotion video URL.
					'videopress_url',  // VideoPress video URL.
					'external_url',    // Self-hosted video external URL.
				);
				foreach ( $video_url_fields as $field ) {
					if ( isset( $settings[ $field ] ) && is_string( $settings[ $field ] ) && ! empty( $settings[ $field ] ) ) {
						$urls[] = array(
							'url'  => $settings[ $field ],
							'text' => '[embed video]',
						);
					}
				}

				// Media control fields (objects with 'url' key).
				// Used for video overlay images, hosted videos, etc.
				$media_fields = array(
					'image_overlay', // Video widget overlay image.
					'hosted_url',    // Self-hosted video (media library).
				);
				foreach ( $media_fields as $field ) {
					if ( isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ) {
						if ( isset( $settings[ $field ]['url'] ) && ! empty( $settings[ $field ]['url'] ) ) {
							$urls[] = array(
								'url'  => $settings[ $field ]['url'],
								'text' => '[media]',
							);
						}
					}
				}
			}

			// Recurse into child elements.
			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$child_urls = self::extract_elementor_urls( $element['elements'] );
				$urls       = array_merge( $urls, $child_urls );
			}
		}

		return $urls;
	}

	/**
	 * Check if URL is external.
	 *
	 * @param string $url URL to check.
	 * @return bool True if external, false if internal.
	 */
	public static function is_external_url( $url ) {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$url_host  = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $url_host ) {
			return false;
		}

		$site_host = preg_replace( '/^www\./i', '', $site_host );
		$url_host  = preg_replace( '/^www\./i', '', $url_host );

		return strtolower( $url_host ) !== strtolower( $site_host );
	}

	/**
	 * Known video embed domains.
	 * Used to filter iframe src URLs to only include video platforms.
	 *
	 * @var array
	 */
	private static $video_domains = array(
		'youtube.com',
		'youtube-nocookie.com',
		'youtu.be',
		'vimeo.com',
		'player.vimeo.com',
		'dailymotion.com',
		'dai.ly',
		'twitch.tv',
		'player.twitch.tv',
		'facebook.com',       // Facebook video embeds.
		'instagram.com',      // Instagram video embeds.
		'tiktok.com',
		'twitter.com',
		'x.com',
		'soundcloud.com',
		'spotify.com',
		'wistia.com',
		'fast.wistia.net',
		'vidyard.com',
		'loom.com',
		'rumble.com',
		'bitchute.com',
		'odysee.com',
		'rutube.ru',
		'ok.ru',              // Odnoklassniki video.
		'vk.com',             // VKontakte video.
	);

	/**
	 * Parse embedded video URLs from HTML.
	 *
	 * Extracts URLs from:
	 * 1. <iframe src="..."> tags (filtered to video platforms)
	 * 2. Gutenberg embed blocks (<!-- wp:embed {"url":"..."} -->)
	 * 3. oEmbed URLs (bare video URLs on their own line)
	 * 4. [embed]url[/embed] shortcodes
	 *
	 * @param string $html HTML content to parse.
	 * @return array Array of extracted embed links.
	 */
	public static function parse_embeds_from_html( $html ) {
		$embeds     = array();
		$found_urls = array(); // Track unique URLs.

		if ( empty( $html ) || ! is_string( $html ) ) {
			return $embeds;
		}

		// 1. Extract from <iframe src="...">
		$iframe_urls = self::extract_iframe_urls( $html );
		foreach ( $iframe_urls as $url ) {
			if ( ! isset( $found_urls[ $url ] ) ) {
				$found_urls[ $url ] = true;
				$embeds[]           = array(
					'url'         => $url,
					'anchor_text' => '[embed video]',
					'is_internal' => false,
					'is_embed'    => true,
				);
			}
		}

		// 2. Extract from Gutenberg embed blocks.
		$gutenberg_urls = self::extract_gutenberg_embed_urls( $html );
		foreach ( $gutenberg_urls as $url ) {
			if ( ! isset( $found_urls[ $url ] ) ) {
				$found_urls[ $url ] = true;
				$embeds[]           = array(
					'url'         => $url,
					'anchor_text' => '[embed video]',
					'is_internal' => false,
					'is_embed'    => true,
				);
			}
		}

		// 3. Extract from [embed]url[/embed] shortcodes.
		$shortcode_urls = self::extract_embed_shortcode_urls( $html );
		foreach ( $shortcode_urls as $url ) {
			if ( ! isset( $found_urls[ $url ] ) ) {
				$found_urls[ $url ] = true;
				$embeds[]           = array(
					'url'         => $url,
					'anchor_text' => '[embed video]',
					'is_internal' => false,
					'is_embed'    => true,
				);
			}
		}

		// 4. Extract bare oEmbed URLs (URL on its own line).
		$oembed_urls = self::extract_oembed_urls( $html );
		foreach ( $oembed_urls as $url ) {
			if ( ! isset( $found_urls[ $url ] ) ) {
				$found_urls[ $url ] = true;
				$embeds[]           = array(
					'url'         => $url,
					'anchor_text' => '[embed video]',
					'is_internal' => false,
					'is_embed'    => true,
				);
			}
		}

		return $embeds;
	}

	/**
	 * Extract URLs from <iframe src="..."> tags.
	 * Only includes URLs from known video platforms.
	 *
	 * @param string $html HTML content.
	 * @return array Array of video URLs.
	 */
	private static function extract_iframe_urls( $html ) {
		$urls = array();

		// Match <iframe ... src="..." ...> tags.
		if ( ! preg_match_all( '/<iframe[^>]*\ssrc\s*=\s*["\']([^"\']+)["\'][^>]*>/is', $html, $matches ) ) {
			return $urls;
		}

		foreach ( $matches[1] as $url ) {
			$url = trim( $url );

			// Handle protocol-relative URLs.
			if ( 0 === strpos( $url, '//' ) ) {
				$url = 'https:' . $url;
			}

			// Only include if it's a known video platform.
			if ( self::is_video_domain( $url ) ) {
				// Convert player URLs to canonical URLs where possible.
				$canonical = self::normalize_embed_url( $url );
				$urls[]    = $canonical;
			}
		}

		return $urls;
	}

	/**
	 * Extract URLs from Gutenberg embed blocks.
	 *
	 * Format: <!-- wp:embed {"url":"https://vimeo.com/123",...} -->
	 *
	 * @param string $html HTML content.
	 * @return array Array of embed URLs.
	 */
	private static function extract_gutenberg_embed_urls( $html ) {
		$urls = array();

		// Match Gutenberg embed blocks.
		if ( ! preg_match_all( '/<!--\s*wp:(?:core-)?embed[^\{]*\{[^}]*"url"\s*:\s*"([^"]+)"[^}]*\}/is', $html, $matches ) ) {
			return $urls;
		}

		foreach ( $matches[1] as $url ) {
			$url = trim( $url );
			// Unescape JSON-encoded URL.
			$url = str_replace( '\/', '/', $url );

			if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
				// Only include video platforms.
				if ( self::is_video_domain( $url ) ) {
					$urls[] = $url;
				}
			}
		}

		return $urls;
	}

	/**
	 * Extract URLs from [embed]url[/embed] shortcodes.
	 *
	 * @param string $html HTML content.
	 * @return array Array of embed URLs.
	 */
	private static function extract_embed_shortcode_urls( $html ) {
		$urls = array();

		// Match [embed]url[/embed] shortcodes.
		if ( ! preg_match_all( '/\[embed\]([^\[]+)\[\/embed\]/is', $html, $matches ) ) {
			return $urls;
		}

		foreach ( $matches[1] as $url ) {
			$url = trim( $url );
			if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
				if ( self::is_video_domain( $url ) ) {
					$urls[] = $url;
				}
			}
		}

		return $urls;
	}

	/**
	 * Extract bare oEmbed URLs (video URL on its own line).
	 *
	 * WordPress auto-embeds URLs that appear on their own line.
	 * We look for video URLs that are:
	 * - At the start of a line (or after <p>)
	 * - Not inside an <a> tag
	 * - On their own (followed by newline or </p>)
	 *
	 * @param string $html HTML content.
	 * @return array Array of video URLs.
	 */
	private static function extract_oembed_urls( $html ) {
		$urls = array();

		// Pattern: URL on its own line, possibly wrapped in <p> tags.
		// Matches: <p>https://vimeo.com/123</p> or just https://vimeo.com/123 on a line.
		$pattern = '/(?:^|<p[^>]*>|\n)\s*(https?:\/\/[^\s<>"\']+)\s*(?:<\/p>|$|\n)/im';

		if ( ! preg_match_all( $pattern, $html, $matches ) ) {
			return $urls;
		}

		foreach ( $matches[1] as $url ) {
			$url = trim( $url );

			// Remove trailing punctuation that might have been caught.
			$url = rtrim( $url, '.,;:!?)' );

			if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
				if ( self::is_video_domain( $url ) ) {
					$urls[] = $url;
				}
			}
		}

		return $urls;
	}

	/**
	 * Check if URL belongs to a known video platform.
	 *
	 * @param string $url URL to check.
	 * @return bool True if it's a video platform URL.
	 */
	private static function is_video_domain( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		$host = strtolower( $host );
		$host = preg_replace( '/^www\./i', '', $host );

		// Check exact match.
		if ( in_array( $host, self::$video_domains, true ) ) {
			return true;
		}

		// Check if it's a subdomain of a video platform.
		foreach ( self::$video_domains as $domain ) {
			if ( substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize embed/player URLs to canonical video URLs.
	 *
	 * Converts player URLs (like player.vimeo.com/video/123) to
	 * canonical URLs (vimeo.com/123) for consistent checking.
	 *
	 * @param string $url Embed URL.
	 * @return string Canonical URL.
	 */
	private static function normalize_embed_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = $path ? $path : '';

		if ( empty( $host ) ) {
			return $url;
		}

		$host = strtolower( $host );

		// Vimeo player: player.vimeo.com/video/123456 -> vimeo.com/123456.
		if ( 'player.vimeo.com' === $host && preg_match( '#^/video/(\d+)#', $path, $m ) ) {
			return 'https://vimeo.com/' . $m[1];
		}

		// YouTube embed: youtube.com/embed/VIDEO_ID -> youtube.com/watch?v=VIDEO_ID.
		if ( ( 'youtube.com' === $host || 'www.youtube.com' === $host || 'youtube-nocookie.com' === $host )
			&& preg_match( '#^/embed/([a-zA-Z0-9_-]+)#', $path, $m ) ) {
			return 'https://www.youtube.com/watch?v=' . $m[1];
		}

		// Dailymotion embed: dailymotion.com/embed/video/VIDEO_ID -> dailymotion.com/video/VIDEO_ID.
		if ( ( 'dailymotion.com' === $host || 'www.dailymotion.com' === $host )
			&& preg_match( '#^/embed/video/([a-zA-Z0-9]+)#', $path, $m ) ) {
			return 'https://www.dailymotion.com/video/' . $m[1];
		}

		return $url;
	}
}
