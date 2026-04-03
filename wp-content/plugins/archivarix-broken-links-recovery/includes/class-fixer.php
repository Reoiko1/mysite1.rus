<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Fixer — replaces or removes broken links in WordPress content.
 *
 * Handles multiple replacement strategies:
 * - Replace with Wayback Machine URL (with proper timestamp)
 * - Replace with custom URL
 * - Remove link but keep anchor text
 * - Remove link and anchor text entirely
 *
 * Uses direct $wpdb for content updates to avoid wp_kses_post sanitization
 * which would corrupt Web Archive URLs and re-encode entities.
 *
 * @package Archivarix_Broken_Links_Recovery
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Fixer uses direct queries for content updates to avoid wp_kses_post sanitization.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Fixer
 *
 * Link replacement and removal handler.
 */
class ABLR_Fixer {

	/**
	 * Replace a broken link with a Wayback Machine URL.
	 *
	 * @param int    $link_id     Link record ID.
	 * @param string $wayback_url Override Wayback URL (optional, auto-built from post date if empty).
	 * @param bool   $is_auto     Whether this is an automatic fix (default false).
	 *
	 * @return bool
	 */
	public static function replace_with_wayback( $link_id, $wayback_url = '', $is_auto = false ) {
		$link = ABLR_Database::get_link( $link_id );
		if ( ! $link ) {
			return false;
		}

		if ( empty( $wayback_url ) ) {
			// First, try using the Wayback URL already stored in the database
			// (returned by the Availability API with the correct snapshot date).
			if ( ! empty( $link->wayback_url ) ) {
				$wayback_url = $link->wayback_url;
			} else {
				// Fallback: build from post date (less reliable — may not match a snapshot).
				$post_date   = self::get_source_date( $link->source_type, $link->source_id );
				$wayback_url = ABLR_Checker::build_wayback_url( $link->url, $post_date );
			}
		}

		$replaced = self::replace_url_in_content( $link, $wayback_url );

		if ( $replaced ) {
			ABLR_Database::update_link_action( $link_id, 'replaced_wayback', $is_auto );
			ABLR_Database::add_log(
				$link_id,
				$link->url,
				$link->source_type,
				$link->source_id,
				'replaced_wayback',
				sprintf( 'Replaced with: %s%s', $wayback_url, $is_auto ? ' (auto)' : '' )
			);
		}

		return $replaced;
	}

	/**
	 * Replace a broken link with a custom URL.
	 *
	 * @param int    $link_id Link ID.
	 * @param string $new_url New URL to replace with.
	 * @param bool   $is_auto Whether this is an automatic fix (default false).
	 * @return bool True if replaced successfully.
	 */
	public static function replace_with_custom( $link_id, $new_url, $is_auto = false ) {
		$link = ABLR_Database::get_link( $link_id );
		if ( ! $link ) {
			return false;
		}

		$replaced = self::replace_url_in_content( $link, $new_url );

		if ( $replaced ) {
			ABLR_Database::update_link_action( $link_id, 'replaced_custom', $is_auto );
			ABLR_Database::add_log(
				$link_id,
				$link->url,
				$link->source_type,
				$link->source_id,
				'replaced_custom',
				sprintf( 'Replaced with: %s%s', $new_url, $is_auto ? ' (auto)' : '' )
			);
		}

		return $replaced;
	}

	/**
	 * Remove the <a> tag but keep the anchor text.
	 *
	 * @param int  $link_id Link ID.
	 * @param bool $is_auto Whether this is an automatic fix (default false).
	 * @return bool True if removed successfully.
	 */
	public static function remove_link_keep_text( $link_id, $is_auto = false ) {
		$link = ABLR_Database::get_link( $link_id );
		if ( ! $link ) {
			return false;
		}

		$removed = self::modify_link_in_content( $link, 'remove_link' );

		if ( $removed ) {
			ABLR_Database::update_link_action( $link_id, 'removed_link', $is_auto );
			ABLR_Database::add_log(
				$link_id,
				$link->url,
				$link->source_type,
				$link->source_id,
				'removed_link',
				'Link removed, anchor text kept' . ( $is_auto ? ' (auto)' : '' )
			);
		}

		return $removed;
	}

	/**
	 * Remove both the <a> tag and its anchor text.
	 *
	 * @param int  $link_id Link ID.
	 * @param bool $is_auto Whether this is an automatic fix (default false).
	 * @return bool True if removed successfully.
	 */
	public static function remove_link_and_text( $link_id, $is_auto = false ) {
		$link = ABLR_Database::get_link( $link_id );
		if ( ! $link ) {
			return false;
		}

		$removed = self::modify_link_in_content( $link, 'remove_all' );

		if ( $removed ) {
			ABLR_Database::update_link_action( $link_id, 'removed_text', $is_auto );
			ABLR_Database::add_log(
				$link_id,
				$link->url,
				$link->source_type,
				$link->source_id,
				'removed_text',
				'Link and anchor text removed' . ( $is_auto ? ' (auto)' : '' )
			);
		}

		return $removed;
	}

	/**
	 * Undo a previous action (restore original link).
	 *
	 * Handles:
	 * - replaced_wayback: Replace wayback URL back to original
	 * - replaced_custom: Replace custom URL back to original
	 * - removed_link: Wrap anchor text back into <a> tag
	 * - removed_text: Cannot restore deleted content, just reset status
	 * - whitelisted: Reset to pending for recheck
	 *
	 * @param int $link_id Link record ID.
	 * @return bool
	 */
	public static function undo_action( $link_id ) {
		$link = ABLR_Database::get_link( $link_id );
		if ( ! $link ) {
			return false;
		}

		// Nothing to undo if no action was taken.
		if ( empty( $link->action_taken ) || 'none' === $link->action_taken ) {
			return false;
		}

		$success = false;
		$action  = $link->action_taken;

		switch ( $action ) {
			case 'replaced_wayback':
				// Find wayback URL in content and replace with original.
				$success = self::undo_replace( $link, $link->wayback_url );
				break;

			case 'replaced_custom':
				// For custom replacements, we need to find what's there now.
				// Try to find a web.archive.org URL or any URL that replaced the original.
				$success = self::undo_replace_by_finding_current( $link );
				break;

			case 'removed_link':
				// Anchor text should still be in content, wrap it back in <a> tag.
				$success = self::undo_remove_link( $link );
				break;

			case 'removed_text':
				// Try to restore from Elementor backup first.
				if ( self::undo_elementor_widget_removal( $link ) ) {
					$success = true;
					break;
				}
				// Content was deleted - we cannot restore it automatically.
				// Just reset the status so user knows it needs manual attention.
				$success = true; // Mark as "success" to reset status.
				ABLR_Database::add_log(
					$link_id,
					$link->url,
					$link->source_type,
					$link->source_id,
					'undo_partial',
					'Link and text were deleted - cannot auto-restore. Status reset to broken.'
				);
				break;

			case 'whitelisted':
				// Just reset status to pending for recheck.
				$success = true;
				break;

			default:
				return false;
		}

		if ( $success ) {
			// Reset link status back to broken (or pending for whitelisted).
			ABLR_Database::update_link_action( $link_id, 'none' );

			// Update status back to broken.
			global $wpdb;
			$table = ABLR_Database::table( 'links' );
			$wpdb->update(
				$table,
				array( 'status' => 'broken' ),
				array( 'id' => $link_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( 'removed_text' !== $action ) {
				ABLR_Database::add_log(
					$link_id,
					$link->url,
					$link->source_type,
					$link->source_id,
					'undo',
					sprintf( 'Undid action: %s', $action )
				);
			}
		}

		return $success;
	}

	/**
	 * Undo a URL replacement by finding the replacement URL in content.
	 *
	 * @param object $link            Link record.
	 * @param string $replacement_url URL that was used as replacement.
	 * @return bool True if undo was successful.
	 */
	private static function undo_replace( $link, $replacement_url ) {
		if ( empty( $replacement_url ) ) {
			return false;
		}

		$meta_key = null;
		$content  = self::get_content( $link->source_type, $link->source_id, $replacement_url, $meta_key );
		if ( false === $content ) {
			return false;
		}

		$old_content = $content;

		// Build variants of the replacement URL to find.
		$url_variants = self::get_url_variants( $replacement_url, false );

		foreach ( $url_variants as $variant ) {
			$escaped_url = preg_quote( $variant, '/' );

			// Try replacing in href attribute.
			$new_content = preg_replace(
				'/(href\s*=\s*["\'])\s*' . $escaped_url . '\s*(["\'])/i',
				'${1}' . esc_url( $link->url ) . '${2}',
				$content,
				-1,
				$count
			);

			if ( $count > 0 ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}

			// Try plain URL replacement.
			$new_content = str_replace( $variant, $link->url, $content );
			if ( $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}
		}

		return false;
	}

	/**
	 * Undo a custom replacement by searching for web.archive.org URLs.
	 *
	 * @param object $link Link record.
	 * @return bool True if undo was successful.
	 */
	private static function undo_replace_by_finding_current( $link ) {
		$meta_key = null;
		$content  = self::get_content( $link->source_type, $link->source_id, '', $meta_key );
		if ( false === $content ) {
			return false;
		}

		$old_content = $content;

		// Try to find any web.archive.org URL that contains our original URL.
		$escaped_original = preg_quote( $link->url, '/' );

		// Pattern: web.archive.org URL containing our original URL.
		$pattern = '/https?:\/\/web\.archive\.org\/web\/\d+\/[^"\'\s]*?' . $escaped_original . '[^"\'\s]*/i';

		if ( preg_match( $pattern, $content, $matches ) ) {
			$wayback_url = $matches[0];
			$new_content = str_replace( $wayback_url, $link->url, $content );
			if ( $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}
		}

		// If wayback_url is stored, try that.
		if ( ! empty( $link->wayback_url ) ) {
			return self::undo_replace( $link, $link->wayback_url );
		}

		return false;
	}

	/**
	 * Undo link removal by wrapping anchor text back in <a> tag.
	 *
	 * @param object $link Link record.
	 * @return bool True if undo was successful.
	 */
	private static function undo_remove_link( $link ) {
		if ( empty( $link->anchor_text ) ) {
			return false;
		}

		$meta_key = null;
		$content  = self::get_content( $link->source_type, $link->source_id, '', $meta_key );
		if ( false === $content ) {
			return false;
		}

		$old_content = $content;

		// Find the anchor text (not already inside an <a> tag) and wrap it.
		$escaped_anchor = preg_quote( $link->anchor_text, '/' );

		// Negative lookbehind to ensure anchor text is not already in a link.
		// Match anchor text that's not preceded by href="..."> pattern.
		$new_content = preg_replace(
			'/(?<!["\'>])(' . $escaped_anchor . ')(?![^<]*<\/a>)/u',
			'<a href="' . esc_url( $link->url ) . '">$1</a>',
			$content,
			1, // Only replace first occurrence.
			$count
		);

		if ( $count > 0 && $new_content !== $content ) {
			return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
		}

		return false;
	}

	/**
	 * Undo Elementor widget removal by restoring from backup.
	 *
	 * @param object $link Link record.
	 * @return bool True if undo was successful.
	 */
	private static function undo_elementor_widget_removal( $link ) {
		// Only works for post types with Elementor data.
		if ( ! post_type_exists( $link->source_type ) ) {
			return false;
		}

		// Check for backup.
		$backup_key  = '_ablr_elementor_backup_' . md5( $link->url );
		$backup_data = get_post_meta( $link->source_id, $backup_key, true );

		if ( empty( $backup_data ) ) {
			return false;
		}

		// Restore original Elementor data.
		$result = update_post_meta( $link->source_id, '_elementor_data', $backup_data );

		if ( $result ) {
			// Clear Elementor CSS cache.
			delete_post_meta( $link->source_id, '_elementor_css' );
			clean_post_cache( $link->source_id );

			// Remove the backup.
			delete_post_meta( $link->source_id, $backup_key );

			// Trigger Elementor regeneration if available.
			if ( class_exists( '\Elementor\Plugin' ) ) {
				if ( method_exists( '\Elementor\Plugin', 'instance' ) ) {
					$elementor = \Elementor\Plugin::instance();
					if ( isset( $elementor->files_manager ) && method_exists( $elementor->files_manager, 'clear_cache' ) ) {
						$elementor->files_manager->clear_cache();
					}
				}
			}

			ABLR_Database::add_log(
				$link->id,
				$link->url,
				$link->source_type,
				$link->source_id,
				'undo',
				'Restored Elementor widget from backup'
			);

			return true;
		}

		return false;
	}

	/**
	 * Replace URL in the actual content source.
	 * Handles both <a href="..."> tags and plain URLs (oEmbed/auto-embed).
	 *
	 * @param object $link    Link record.
	 * @param string $new_url New URL to replace with.
	 * @return bool True if replaced successfully.
	 */
	private static function replace_url_in_content( $link, $new_url ) {
		$meta_key = null;
		$content  = self::get_content( $link->source_type, $link->source_id, $link->url, $meta_key );
		if ( false === $content ) {
			return false;
		}

		$old_content = $content; // Store original for meta update.

		// Build all possible URL variants to search for in content.
		$url_variants = self::get_url_variants( $link->url, $link->is_internal );

		// === Try 0: Elementor widget URL replacement for embed URLs ===
		// For video/embed URLs in Elementor content, use proper JSON parsing
		// to replace the URL in the widget settings.
		if ( '_elementor_data' === $meta_key && self::is_embed_url( $link->url ) ) {
			if ( self::replace_elementor_widget_url( $link->source_id, $link->url, $new_url ) ) {
				return true;
			}
			// Fall through to string-based methods if JSON replacement fails.
		}

		foreach ( $url_variants as $variant ) {
			$escaped_url = preg_quote( $variant, '/' );

			// === Try 1: Replace URL inside href attribute ===
			// \s* around URL handles old WordPress content (2005+) that may have
			// spaces or tabs inside href attributes, e.g. href=" http://... "
			$new_content = preg_replace(
				'/(href\s*=\s*["\'])\s*' . $escaped_url . '\s*(["\'])/i',
				'${1}' . esc_url( $new_url ) . '${2}',
				$content,
				-1,
				$count
			);

			if ( $count > 0 ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}

			// === Try 2: Plain URL replacement (oEmbed/auto-embed) ===
			// These are URLs that WordPress auto-embeds (YouTube, TikTok, Vimeo, etc.)
			// Simple string replace works for plain URLs.
			$new_content = str_replace( $variant, $new_url, $content );
			if ( $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}

			// === Try 3: JSON-escaped URL replacement (Elementor, etc.) ===
			// Elementor stores URLs in JSON format with escaped slashes: https:\/\/example.com
			$json_escaped_variant = str_replace( '/', '\\/', $variant );
			$json_escaped_new_url = str_replace( '/', '\\/', $new_url );
			$new_content          = str_replace( $json_escaped_variant, $json_escaped_new_url, $content );
			if ( $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}

			// === Try 4: HTML inside JSON (Elementor text-editor widget) ===
			// When HTML is stored in JSON, href attributes have escaped quotes: href=\"URL\"
			// The URL inside is also JSON-escaped: href=\"https:\/\/example.com\"
			$html_json_pattern = 'href=\\"' . $json_escaped_variant . '\\"';
			$html_json_new     = 'href=\\"' . $json_escaped_new_url . '\\"';
			$new_content       = str_replace( $html_json_pattern, $html_json_new, $content );
			if ( $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}

			// Also try with single-escaped quotes (some JSON encoders).
			$html_json_pattern_single = "href=\\'" . $json_escaped_variant . "\\'";
			$html_json_new_single     = "href=\\'" . $json_escaped_new_url . "\\'";
			$new_content              = str_replace( $html_json_pattern_single, $html_json_new_single, $content );
			if ( $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}
		}

		// Link was already replaced/removed — verify by checking href attributes and plain text.
		if ( self::is_link_absent_from_content( $content, $url_variants ) ) {
			return true;
		}

		ABLR_Database::add_log(
			0,
			$link->url,
			$link->source_type,
			$link->source_id,
			'fix_failed',
			'Failed to replace URL in content (no matching href or plain URL found)'
		);

		return false;
	}

	/**
	 * Modify a link tag in content (remove link or remove link+text).
	 * Handles both <a href="..."> tags and plain URLs (oEmbed/auto-embed).
	 *
	 * @param object $link   Link record.
	 * @param string $action Action to perform (remove_link or remove_text).
	 * @return bool True if modified successfully.
	 */
	private static function modify_link_in_content( $link, $action ) {
		$meta_key = null;
		$content  = self::get_content( $link->source_type, $link->source_id, $link->url, $meta_key );
		if ( false === $content ) {
			return false;
		}

		$old_content = $content; // Store original for meta update.

		// Build all possible URL variants to search for in content.
		$url_variants = self::get_url_variants( $link->url, $link->is_internal );

		// === Try 0: Elementor widget removal for embed URLs ===
		// For video/embed URLs in Elementor content, remove the entire widget element
		// instead of just the URL string. Removing just the URL leaves broken widgets.
		if ( '_elementor_data' === $meta_key && self::is_embed_url( $link->url ) ) {
			// Use proper JSON-based widget removal.
			$widget_action = 'remove_all' === $action ? 'remove_all' : 'remove_link';
			if ( self::remove_elementor_widget_by_url( $link->source_id, $link->url, $widget_action ) ) {
				return true;
			}
			// Fall through to string-based methods if widget removal fails.
		}

		// === Try 1: Standard <a href="..."> tags ===
		foreach ( $url_variants as $variant ) {
			$escaped_url = preg_quote( $variant, '/' );

			if ( 'remove_link' === $action ) {
				// \s* handles old content with spaces inside href attributes.
				$new_content = preg_replace(
					'/<a\s[^>]*href\s*=\s*["\'][\s]*' . $escaped_url . '[\s]*["\'][^>]*>(.*?)<\/a>/is',
					'$1',
					$content,
					-1,
					$count
				);
			} else {
				$new_content = preg_replace(
					'/<a\s[^>]*href\s*=\s*["\'][\s]*' . $escaped_url . '[\s]*["\'][^>]*>.*?<\/a>/is',
					'',
					$content,
					-1,
					$count
				);
			}

			if ( $count > 0 && $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}
		}

		// === Try 2: Plain URLs (oEmbed/auto-embed) — URL on its own line ===
		// These are URLs that WordPress auto-embeds (YouTube, TikTok, Vimeo, etc.)
		// Format: URL alone on a line, possibly wrapped in <p> tags.
		foreach ( $url_variants as $variant ) {
			$escaped_url = preg_quote( $variant, '/' );

			// Pattern: URL on its own line (possibly in <p> tags).
			// For 'remove_link' we keep the URL as plain text (no action needed — it's already text).
			// For 'remove_all' we remove the entire line including the URL.
			if ( 'remove_all' === $action ) {
				// Remove URL and surrounding <p> tags if present.
				$new_content = preg_replace(
					'/(<p[^>]*>)?\s*' . $escaped_url . '\s*(<\/p>)?[\r\n]*/is',
					'',
					$content,
					-1,
					$count
				);

				if ( $count > 0 && $new_content !== $content ) {
					return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
				}

				// Also try removing just the URL (in case it's inline).
				$new_content = str_replace( $variant, '', $content );
				if ( $new_content !== $content ) {
					return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
				}
			} elseif ( false !== strpos( $content, $variant ) ) {
				// 'remove_link' for plain URL — URL is already text, nothing to unwrap.
				// But we should still mark as success if URL exists in content.
				// Plain URL exists — for 'remove_link' there's nothing to do
				// (URL is already plain text, not a link).
				// Return true to mark as "fixed" since there's no link to remove.
				ABLR_Database::add_log(
					0,
					$link->url,
					$link->source_type,
					$link->source_id,
					'fix_skipped',
					'Plain URL (oEmbed) — no <a> tag to remove, URL left as text'
				);
				return true;
			}
		}

		// === Try 3: JSON-escaped URLs (Elementor, etc.) ===
		// Elementor stores HTML content in JSON with escaped slashes and quotes.
		// Format: <a href=\"https:\/\/example.com\">text<\/a>
		foreach ( $url_variants as $variant ) {
			$json_escaped_url = str_replace( '/', '\\/', $variant );
			$escaped_json_url = preg_quote( $json_escaped_url, '/' );

			if ( 'remove_link' === $action ) {
				// Match Elementor JSON format: <a href=\"url\">text<\/a>.
				$new_content = preg_replace(
					'/<a[^>]*href\s*=\s*\\\\["\']' . $escaped_json_url . '\\\\["\'][^>]*>(.*?)<\\\\\/a>/is',
					'$1',
					$content,
					-1,
					$count
				);
			} else {
				$new_content = preg_replace(
					'/<a[^>]*href\s*=\s*\\\\["\']' . $escaped_json_url . '\\\\["\'][^>]*>.*?<\\\\\/a>/is',
					'',
					$content,
					-1,
					$count
				);
			}

			if ( $count > 0 && $new_content !== $content ) {
				return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
			}

			// Also try simple removal of JSON-escaped URL.
			if ( 'remove_all' === $action ) {
				$new_content = str_replace( $json_escaped_url, '', $content );
				if ( $new_content !== $content ) {
					return self::save_content( $link->source_type, $link->source_id, $new_content, $meta_key, $old_content );
				}
			}
		}

		// Link was already replaced/removed by a previous action — verify by checking href attributes.
		if ( self::is_link_absent_from_content( $content, $url_variants ) ) {
			return true;
		}

		ABLR_Database::add_log(
			0,
			$link->url,
			$link->source_type,
			$link->source_id,
			'fix_failed',
			'Failed to remove link from content (no matching <a> tag or plain URL found)'
		);

		return false;
	}

	/**
	 * Build all possible URL variants for searching in content.
	 * Handles absolute → relative conversion for internal links, entity encoding,
	 * www/non-www, trailing slash, and protocol variants for ALL link types.
	 *
	 * @param string $url         The stored (absolute) URL.
	 * @param mixed  $is_internal Whether link is internal (1, '1', true).
	 * @return array  Unique URL variants to try, ordered by priority.
	 */
	private static function get_url_variants( $url, $is_internal = false ) {
		$variants = array( $url );

		// For internal links, also try the relative path form.
		// DB stores "https://site.com/path/page.htm" but content may have "/path/page.htm".
		if ( ! empty( $is_internal ) && '0' !== $is_internal ) {
			$site_url = rtrim( home_url(), '/' );
			if ( stripos( $url, $site_url ) === 0 ) {
				$relative_path = substr( $url, strlen( $site_url ) );
				if ( ! empty( $relative_path ) ) {
					$variants[] = $relative_path;
				}
			}
		}

		// www ↔ non-www variants (for ALL links, not just internal).
		$alt_url = preg_replace( '/^(https?:\/\/)www\./i', '$1', $url );
		if ( $alt_url !== $url ) {
			$variants[] = $alt_url;
		}
		$alt_url_www = preg_replace( '/^(https?:\/\/)(?!www\.)/i', '$1www.', $url );
		if ( $alt_url_www !== $url ) {
			$variants[] = $alt_url_www;
		}

		// Trailing slash variants.
		if ( substr( $url, -1 ) === '/' ) {
			$variants[] = rtrim( $url, '/' );
		} else {
			// Only add trailing slash if URL doesn't have a file extension.
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( $path && ! preg_match( '/\.\w{2,5}$/', $path ) ) {
				$variants[] = $url . '/';
			}
		}

		// HTML entity variants.
		$variants[] = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );
		$variants[] = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
		$variants[] = str_replace( '&amp;', '&', $url );
		$variants[] = str_replace( '&', '&amp;', $url );
		// WordPress-specific numeric entity for &.
		$variants[] = str_replace( '&', '&#038;', $url );
		$variants[] = str_replace( '&#038;', '&', $url );

		// http ↔ https variants.
		if ( stripos( $url, 'https://' ) === 0 ) {
			$http_variant = 'http://' . substr( $url, 8 );
			$variants[]   = $http_variant;
			// Also http+www / http+no-www combinations.
			$variants[] = preg_replace( '/^(http:\/\/)www\./i', '$1', $http_variant );
			$http_www   = preg_replace( '/^(http:\/\/)(?!www\.)/i', '$1www.', $http_variant );
			if ( $http_www !== $http_variant ) {
				$variants[] = $http_www;
			}
		} elseif ( stripos( $url, 'http://' ) === 0 ) {
			$https_variant = 'https://' . substr( $url, 7 );
			$variants[]    = $https_variant;
			// Also https+www / https+no-www combinations.
			$variants[] = preg_replace( '/^(https:\/\/)www\./i', '$1', $https_variant );
			$https_www  = preg_replace( '/^(https:\/\/)(?!www\.)/i', '$1www.', $https_variant );
			if ( $https_www !== $https_variant ) {
				$variants[] = $https_www;
			}
		}

		// Protocol-relative variants (//server/path).
		// HTML may have "//nwm-server/path" while DB stores "https://nwm-server/path".
		if ( preg_match( '/^https?:\/\/(.+)$/i', $url, $matches ) ) {
			$variants[] = '//' . $matches[1];
		}

		// Handle malformed URLs that may appear in old/migrated content.
		// These are stored with protocol but may appear differently in HTML.
		$parsed = wp_parse_url( $url );
		if ( ! empty( $parsed['host'] ) ) {
			// Try just host + path (no protocol).
			$host_path = $parsed['host'];
			if ( ! empty( $parsed['path'] ) ) {
				$host_path .= $parsed['path'];
			}
			if ( ! empty( $parsed['query'] ) ) {
				$host_path .= '?' . $parsed['query'];
			}
			$variants[] = $host_path;

			// URL-decoded version (spaces instead of %20, etc.).
			$variants[] = urldecode( $url );
			$variants[] = '//' . urldecode( $matches[1] ?? '' );
		}

		return array_unique( $variants );
	}

	/**
	 * Check if a link is truly absent from content by verifying:
	 * 1. No href attribute contains any of the URL variants
	 * 2. No plain URL (oEmbed) exists in content
	 * 3. No JSON-escaped URL (Elementor) exists in content
	 *
	 * @param string $content      Post/comment content.
	 * @param array  $url_variants Array of URL variants to check.
	 * @return bool True if the link is genuinely absent from content.
	 */
	private static function is_link_absent_from_content( $content, $url_variants ) {
		foreach ( $url_variants as $variant ) {
			$escaped = preg_quote( $variant, '/' );

			// Check for presence in an href attribute.
			// \s* handles old content with spaces inside href attributes.
			if ( preg_match( '/href\s*=\s*["\'][\s]*' . $escaped . '/i', $content ) ) {
				return false; // Link is still present in href.
			}

			// Check for plain URL (oEmbed/auto-embed).
			if ( strpos( $content, $variant ) !== false ) {
				return false; // Link is still present as plain URL.
			}

			// Check for JSON-escaped URL (Elementor, etc.).
			$json_escaped = str_replace( '/', '\\/', $variant );
			if ( strpos( $content, $json_escaped ) !== false ) {
				return false; // Link is still present in JSON-escaped form.
			}

			// Check for HTML inside JSON (Elementor text-editor widget).
			// Format: href=\"https:\/\/example.com\".
			$html_json_pattern = 'href=\\"' . $json_escaped . '\\"';
			if ( false !== strpos( $content, $html_json_pattern ) ) {
				return false; // Link is still present in HTML-inside-JSON form.
			}
		}
		return true; // None of the variants found — genuinely absent.
	}

	/**
	 * Get content from the appropriate source.
	 * Supports Elementor: checks _elementor_data first, then post_content.
	 *
	 * @param string      $source_type     Source type (post type or 'comment').
	 * @param int         $source_id       Source ID.
	 * @param string      $url             URL to search for (optional).
	 * @param string|null $meta_key_found  Output: meta key if URL found in meta.
	 * @return string|false Content string or false if not found.
	 */
	private static function get_content( $source_type, $source_id, $url = '', &$meta_key_found = null ) {
		// Any registered post type.
		if ( post_type_exists( $source_type ) ) {
			$post = get_post( $source_id );
			if ( ! $post ) {
				return false;
			}

			// Check Elementor data first (if plugin is active and post uses Elementor).
			$elementor_data = get_post_meta( $source_id, '_elementor_data', true );
			if ( ! empty( $elementor_data ) && is_string( $elementor_data ) ) {
				// If URL provided, check if Elementor data contains it.
				if ( ! empty( $url ) ) {
					$url_variants = self::get_url_variants( $url, false );
					foreach ( $url_variants as $variant ) {
						$json_escaped = str_replace( '/', '\\/', $variant );
						// Check raw, JSON-escaped, and HTML-inside-JSON versions.
						if ( strpos( $elementor_data, $variant ) !== false ||
							strpos( $elementor_data, $json_escaped ) !== false ||
							strpos( $elementor_data, 'href=\\"' . $json_escaped . '\\"' ) !== false ) {
							$meta_key_found = '_elementor_data';
							return $elementor_data;
						}
					}
				} else {
					// No URL to check — return Elementor data if it exists.
					$meta_key_found = '_elementor_data';
					return $elementor_data;
				}
			}

			// Fallback to post_content.
			return $post->post_content;
		}

		switch ( $source_type ) {
			case 'comment':
				$comment = get_comment( $source_id );
				return $comment ? $comment->comment_content : false;

			case 'comment_author':
				$comment = get_comment( $source_id );
				return $comment ? $comment->comment_author_url : false;

			case 'meta':
				$meta_values = get_post_meta( $source_id );
				if ( $meta_values ) {
					// Build URL variants for searching.
					$url_variants = ! empty( $url ) ? self::get_url_variants( $url, false ) : array();

					foreach ( $meta_values as $key => $values ) {
						if ( 0 === strpos( $key, '_' ) ) {
							continue;
						}
						foreach ( $values as $val ) {
							if ( is_string( $val ) && ! empty( $val ) ) {
								// If URL provided, check if this meta contains it.
								if ( ! empty( $url_variants ) ) {
									foreach ( $url_variants as $variant ) {
										if ( strpos( $val, $variant ) !== false ) {
											$meta_key_found = $key;
											return $val;
										}
									}
									continue; // URL not found in this meta, try next.
								}
								$meta_key_found = $key;
								return $val;
							}
						}
					}
				}
				return false;

			case 'widget':
				return self::get_widget_content( $source_id );

			default:
				return false;
		}
	}

	/**
	 * Save modified content back to the source.
	 *
	 * @param string $source_type Source type (post type or 'comment').
	 * @param int    $source_id   Source ID.
	 * @param string $content     New content to save.
	 * @param string $meta_key    Meta key if saving to post meta.
	 * @param string $old_value   Old value for comparison.
	 * @return bool True if saved successfully.
	 */
	private static function save_content( $source_type, $source_id, $content, $meta_key = '', $old_value = '' ) {
		// Any registered post type.
		if ( post_type_exists( $source_type ) ) {
			// Elementor data is stored in post meta, not post_content.
			if ( '_elementor_data' === $meta_key ) {
				$result = update_post_meta( $source_id, '_elementor_data', $content );
				if ( $result ) {
					// Clear Elementor CSS cache for this post.
					delete_post_meta( $source_id, '_elementor_css' );
					// Clear post cache.
					clean_post_cache( $source_id );
				}
				return (bool) $result;
			}

			// Standard post_content update.
			// Use direct $wpdb->update() instead of wp_update_post() to prevent
			// content sanitization (wp_kses_post) from corrupting Web Archive URLs,
			// re-encoding entities (&amp; ↔ &), or triggering save_post hooks.
			global $wpdb;
			$result = $wpdb->update(
				$wpdb->posts,
				array(
					'post_content'      => $content,
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', 1 ),
				),
				array( 'ID' => $source_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
			if ( false !== $result ) {
				clean_post_cache( $source_id );
			}
			return false !== $result;
		}

		switch ( $source_type ) {
			case 'comment':
				return wp_update_comment(
					array(
						'comment_ID'      => $source_id,
						'comment_content' => $content,
					)
				) !== false;

			case 'comment_author':
				return wp_update_comment(
					array(
						'comment_ID'         => $source_id,
						'comment_author_url' => $content,
					)
				) !== false;

			case 'meta':
				// Use specific meta key if provided.
				if ( ! empty( $meta_key ) ) {
					if ( ! empty( $old_value ) ) {
						return update_post_meta( $source_id, $meta_key, $content, $old_value );
					} else {
						return update_post_meta( $source_id, $meta_key, $content );
					}
				}
				return false;

			case 'widget':
				return self::save_widget_content( $source_id, $content );

			default:
				return false;
		}
	}

	/**
	 * Remove an Elementor widget element that contains a specific URL.
	 *
	 * Instead of just removing the URL string (which leaves a broken widget),
	 * this method parses the JSON structure, finds the widget containing the URL,
	 * and removes the entire widget element.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     URL to find and remove widget for.
	 * @param string $action  Action type: 'remove_all' removes widget, 'remove_link' clears URL field.
	 * @return bool True if widget was removed successfully.
	 */
	private static function remove_elementor_widget_by_url( $post_id, $url, $action = 'remove_all' ) {
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $elementor_data ) || ! is_string( $elementor_data ) ) {
			return false;
		}

		$data = json_decode( $elementor_data, true );
		if ( ! is_array( $data ) ) {
			return false;
		}

		// Build URL variants to search for.
		$url_variants = self::get_url_variants( $url, false );

		// Find and remove the widget containing this URL.
		$modified = self::find_and_remove_widget( $data, $url_variants, $action );

		if ( $modified ) {
			// Save backup of original Elementor data for undo (keyed by URL hash).
			$backup_key = '_ablr_elementor_backup_' . md5( $url );
			update_post_meta( $post_id, $backup_key, $elementor_data );
			// Re-encode to JSON with same flags WordPress/Elementor uses.
			$new_data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( ! $new_data ) {
				return false;
			}

			// Save modified data.
			$result = update_post_meta( $post_id, '_elementor_data', $new_data );
			if ( $result ) {
				// Clear Elementor CSS cache for this post.
				delete_post_meta( $post_id, '_elementor_css' );
				// Also clear global Elementor CSS if it exists.
				delete_post_meta( $post_id, '_elementor_page_settings' );
				// Clear post cache.
				clean_post_cache( $post_id );

				// Trigger Elementor regeneration if available.
				if ( class_exists( '\Elementor\Plugin' ) ) {
					// Clear file-based CSS.
					if ( method_exists( '\Elementor\Plugin', 'instance' ) ) {
						$elementor = \Elementor\Plugin::instance();
						if ( isset( $elementor->files_manager ) && method_exists( $elementor->files_manager, 'clear_cache' ) ) {
							$elementor->files_manager->clear_cache();
						}
					}
				}
			}
			return (bool) $result;
		}

		return false;
	}

	/**
	 * Recursively find and remove a widget containing a URL from Elementor data.
	 *
	 * @param array  $elements     Array of Elementor elements (passed by reference).
	 * @param array  $url_variants URL variants to search for.
	 * @param string $action       Action type: 'remove_all' or 'remove_link'.
	 * @return bool True if a widget was found and removed/modified.
	 */
	private static function find_and_remove_widget( &$elements, $url_variants, $action = 'remove_all' ) {
		$modified = false;

		foreach ( $elements as $index => &$element ) {
			// Check if this is a widget with matching URL.
			if ( isset( $element['elType'] ) && 'widget' === $element['elType'] ) {
				if ( self::widget_contains_url( $element, $url_variants ) ) {
					if ( 'remove_all' === $action ) {
						// Remove the entire widget element.
						unset( $elements[ $index ] );
						$elements = array_values( $elements ); // Re-index array.
						return true;
					} else {
						// For 'remove_link', clear the URL field but keep the widget.
						// This is less disruptive but may still show empty widget.
						self::clear_widget_url( $element, $url_variants );
						return true;
					}
				}
			}

			// Recurse into child elements.
			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) && ! empty( $element['elements'] ) ) {
				if ( self::find_and_remove_widget( $element['elements'], $url_variants, $action ) ) {
					$modified = true;
					// If child elements array is now empty after removal, clean up.
					if ( empty( $element['elements'] ) ) {
						$element['elements'] = array();
					}
					return true;
				}
			}
		}

		return $modified;
	}

	/**
	 * Check if an Elementor widget contains any of the URL variants.
	 *
	 * Uses strpos() for flexible matching instead of exact comparison,
	 * to handle URLs that may have query params or slight variations.
	 *
	 * @param array $widget       Widget element data.
	 * @param array $url_variants URL variants to check.
	 * @return bool True if widget contains any of the URLs.
	 */
	private static function widget_contains_url( $widget, $url_variants ) {
		if ( ! isset( $widget['settings'] ) || ! is_array( $widget['settings'] ) ) {
			return false;
		}

		$settings = $widget['settings'];

		// Video widget URL fields (plain strings).
		$video_fields = array(
			'youtube_url',
			'vimeo_url',
			'dailymotion_url',
			'videopress_url',
			'external_url',
			'insert_url', // Elementor uses this for some video sources.
		);

		foreach ( $video_fields as $field ) {
			if ( isset( $settings[ $field ] ) && is_string( $settings[ $field ] ) && ! empty( $settings[ $field ] ) ) {
				foreach ( $url_variants as $variant ) {
					// Use strpos for flexible matching (handles URLs with params, etc.).
					if ( strpos( $settings[ $field ], $variant ) !== false ||
						strpos( $variant, $settings[ $field ] ) !== false ) {
						return true;
					}
				}
			}
		}

		// Link fields (objects with 'url' key).
		$link_fields = array( 'link', 'url', 'button_link', 'image_link', 'hosted_url' );
		foreach ( $link_fields as $field ) {
			if ( isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ) {
				if ( isset( $settings[ $field ]['url'] ) && ! empty( $settings[ $field ]['url'] ) ) {
					foreach ( $url_variants as $variant ) {
						if ( strpos( $settings[ $field ]['url'], $variant ) !== false ||
							strpos( $variant, $settings[ $field ]['url'] ) !== false ) {
							return true;
						}
					}
				}
			}
		}

		// Check widget HTML content for URLs (text-editor widget).
		$html_fields = array( 'editor', 'html', 'content' );
		foreach ( $html_fields as $field ) {
			if ( isset( $settings[ $field ] ) && is_string( $settings[ $field ] ) ) {
				foreach ( $url_variants as $variant ) {
					if ( strpos( $settings[ $field ], $variant ) !== false ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Clear URL field from a widget's settings.
	 *
	 * @param array $widget       Widget element (passed by reference).
	 * @param array $url_variants URL variants to clear.
	 */
	private static function clear_widget_url( &$widget, $url_variants ) {
		if ( ! isset( $widget['settings'] ) || ! is_array( $widget['settings'] ) ) {
			return;
		}

		// Video widget URL fields.
		$video_fields = array(
			'youtube_url',
			'vimeo_url',
			'dailymotion_url',
			'videopress_url',
			'external_url',
			'insert_url',
		);

		foreach ( $video_fields as $field ) {
			if ( isset( $widget['settings'][ $field ] ) && is_string( $widget['settings'][ $field ] ) && ! empty( $widget['settings'][ $field ] ) ) {
				foreach ( $url_variants as $variant ) {
					// Use strpos for flexible matching.
					if ( strpos( $widget['settings'][ $field ], $variant ) !== false ||
						strpos( $variant, $widget['settings'][ $field ] ) !== false ) {
						$widget['settings'][ $field ] = '';
						return;
					}
				}
			}
		}

		// Link fields.
		$link_fields = array( 'link', 'url', 'button_link', 'image_link', 'hosted_url' );
		foreach ( $link_fields as $field ) {
			if ( isset( $widget['settings'][ $field ] ) && is_array( $widget['settings'][ $field ] ) ) {
				if ( isset( $widget['settings'][ $field ]['url'] ) && ! empty( $widget['settings'][ $field ]['url'] ) ) {
					foreach ( $url_variants as $variant ) {
						// Use strpos for flexible matching.
						if ( strpos( $widget['settings'][ $field ]['url'], $variant ) !== false ||
							strpos( $variant, $widget['settings'][ $field ]['url'] ) !== false ) {
							$widget['settings'][ $field ]['url'] = '';
							return;
						}
					}
				}
			}
		}
	}

	/**
	 * Replace URL in an Elementor widget using proper JSON parsing.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $old_url URL to find and replace.
	 * @param string $new_url New URL to replace with.
	 * @return bool True if URL was replaced successfully.
	 */
	private static function replace_elementor_widget_url( $post_id, $old_url, $new_url ) {
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $elementor_data ) || ! is_string( $elementor_data ) ) {
			return false;
		}

		$data = json_decode( $elementor_data, true );
		if ( ! is_array( $data ) ) {
			return false;
		}

		// Build URL variants to search for.
		$url_variants = self::get_url_variants( $old_url, false );

		// Find and replace URL in the widget.
		$modified = self::find_and_replace_widget_url( $data, $url_variants, $new_url );

		if ( $modified ) {
			// Re-encode to JSON.
			$new_data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( ! $new_data ) {
				return false;
			}

			$result = update_post_meta( $post_id, '_elementor_data', $new_data );
			if ( $result ) {
				delete_post_meta( $post_id, '_elementor_css' );
				clean_post_cache( $post_id );

				// Trigger Elementor regeneration if available.
				if ( class_exists( '\Elementor\Plugin' ) ) {
					if ( method_exists( '\Elementor\Plugin', 'instance' ) ) {
						$elementor = \Elementor\Plugin::instance();
						if ( isset( $elementor->files_manager ) && method_exists( $elementor->files_manager, 'clear_cache' ) ) {
							$elementor->files_manager->clear_cache();
						}
					}
				}
			}
			return (bool) $result;
		}

		return false;
	}

	/**
	 * Recursively find and replace URL in Elementor widget settings.
	 *
	 * @param array  $elements     Array of Elementor elements (passed by reference).
	 * @param array  $url_variants URL variants to search for.
	 * @param string $new_url      New URL to replace with.
	 * @return bool True if URL was found and replaced.
	 */
	private static function find_and_replace_widget_url( &$elements, $url_variants, $new_url ) {
		$modified = false;

		foreach ( $elements as &$element ) {
			if ( isset( $element['elType'] ) && 'widget' === $element['elType'] ) {
				if ( isset( $element['settings'] ) && is_array( $element['settings'] ) ) {
					// Replace in video URL fields.
					$video_fields = array( 'youtube_url', 'vimeo_url', 'dailymotion_url', 'videopress_url', 'external_url', 'insert_url' );
					foreach ( $video_fields as $field ) {
						if ( isset( $element['settings'][ $field ] ) && is_string( $element['settings'][ $field ] ) && ! empty( $element['settings'][ $field ] ) ) {
							foreach ( $url_variants as $variant ) {
								// Use strpos for flexible matching.
								if ( strpos( $element['settings'][ $field ], $variant ) !== false ||
									strpos( $variant, $element['settings'][ $field ] ) !== false ) {
									$element['settings'][ $field ] = $new_url;
									return true;
								}
							}
						}
					}

					// Replace in link fields.
					$link_fields = array( 'link', 'url', 'button_link', 'image_link', 'hosted_url' );
					foreach ( $link_fields as $field ) {
						if ( isset( $element['settings'][ $field ] ) && is_array( $element['settings'][ $field ] ) ) {
							if ( isset( $element['settings'][ $field ]['url'] ) && ! empty( $element['settings'][ $field ]['url'] ) ) {
								foreach ( $url_variants as $variant ) {
									// Use strpos for flexible matching.
									if ( strpos( $element['settings'][ $field ]['url'], $variant ) !== false ||
										strpos( $variant, $element['settings'][ $field ]['url'] ) !== false ) {
										$element['settings'][ $field ]['url'] = $new_url;
										return true;
									}
								}
							}
						}
					}
				}
			}

			// Recurse into child elements.
			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) && ! empty( $element['elements'] ) ) {
				if ( self::find_and_replace_widget_url( $element['elements'], $url_variants, $new_url ) ) {
					return true;
				}
			}
		}

		return $modified;
	}

	/**
	 * Check if a URL is an embed/video URL that should use widget removal.
	 *
	 * @param string $url URL to check.
	 * @return bool True if URL is an embed that needs widget removal.
	 */
	private static function is_embed_url( $url ) {
		$embed_domains = array(
			'youtube.com',
			'youtube-nocookie.com',
			'youtu.be',
			'vimeo.com',
			'player.vimeo.com',
			'dailymotion.com',
			'dai.ly',
			'twitch.tv',
			'player.twitch.tv',
			'facebook.com',
			'instagram.com',
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
			'ok.ru',
			'vk.com',
		);

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}

		$host = strtolower( preg_replace( '/^www\./i', '', $host ) );

		foreach ( $embed_domains as $domain ) {
			if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get widget content by widget instance key.
	 *
	 * Note: 'widget_text' and 'widget_custom_html' are WordPress core options
	 * that store Text and Custom HTML widget instances respectively.
	 * These are NOT plugin-owned options — they must be accessed by their core
	 * names to read/write widget content for broken link replacement.
	 *
	 * @param int $instance_key Widget instance key.
	 * @return string|false Widget content or false if not found.
	 */
	private static function get_widget_content( $instance_key ) {
		// WordPress core option: stores Text widget instances.
		$widgets = get_option( 'widget_text', array() );
		if ( isset( $widgets[ $instance_key ]['text'] ) ) {
			return $widgets[ $instance_key ]['text'];
		}

		// WordPress core option: stores Custom HTML widget instances.
		$html_widgets = get_option( 'widget_custom_html', array() );
		if ( isset( $html_widgets[ $instance_key ]['content'] ) ) {
			return $html_widgets[ $instance_key ]['content'];
		}

		return false;
	}

	/**
	 * Save widget content.
	 *
	 * Note: see get_widget_content() for why 'widget_text' and
	 * 'widget_custom_html' are WordPress core option names, not plugin options.
	 *
	 * @param int    $instance_key Widget instance key.
	 * @param string $content      New widget content.
	 * @return bool True if saved successfully.
	 */
	private static function save_widget_content( $instance_key, $content ) {
		// WordPress core option: stores Text widget instances.
		$widgets = get_option( 'widget_text', array() );
		if ( isset( $widgets[ $instance_key ]['text'] ) ) {
			$widgets[ $instance_key ]['text'] = $content;
			update_option( 'widget_text', $widgets );
			return true;
		}

		// WordPress core option: stores Custom HTML widget instances.
		$html_widgets = get_option( 'widget_custom_html', array() );
		if ( isset( $html_widgets[ $instance_key ]['content'] ) ) {
			$html_widgets[ $instance_key ]['content'] = $content;
			update_option( 'widget_custom_html', $html_widgets );
			return true;
		}

		return false;
	}

	/**
	 * Get post/comment publication date for Wayback timestamp.
	 *
	 * Never returns empty — falls back to 4 years ago if source not found
	 * or date is invalid. The Wayback Machine will redirect to the closest
	 * available snapshot.
	 *
	 * @param string $source_type Post type or 'comment'/'comment_author'.
	 * @param int    $source_id   Post or comment ID.
	 * @return string Date in Y-m-d H:i:s format.
	 */
	private static function get_source_date( $source_type, $source_id ) {
		if ( post_type_exists( $source_type ) ) {
			$post = get_post( $source_id );
			if ( $post && ! empty( $post->post_date ) && '0000-00-00 00:00:00' !== $post->post_date ) {
				return $post->post_date;
			}
		}

		switch ( $source_type ) {
			case 'comment':
			case 'comment_author':
				$comment = get_comment( $source_id );
				if ( $comment && ! empty( $comment->comment_date ) && '0000-00-00 00:00:00' !== $comment->comment_date ) {
					return $comment->comment_date;
				}
				break;
		}

		// Fallback: 4 years ago — Wayback will redirect to closest snapshot.
		return wp_date( 'Y-m-d H:i:s', strtotime( '-4 years' ) );
	}
}
