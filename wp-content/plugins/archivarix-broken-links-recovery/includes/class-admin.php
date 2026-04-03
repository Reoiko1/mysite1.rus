<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Autoloader expects this format.
/**
 * Admin interface for Archivarix Broken Links Recovery.
 *
 * Handles admin menu registration, script/style enqueuing, and page rendering.
 * Designed to be consistent with Archivarix External Images Importer styling.
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABLR_Admin
 *
 * Admin menu and page handler.
 */
class ABLR_Admin {

	/**
	 * Initialize admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . ABLR_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
	}

	/**
	 * Register admin menu page under Tools.
	 */
	public static function add_menu() {
		add_management_page(
			__( 'Archivarix Broken Links Recovery', 'archivarix-broken-links-recovery' ),
			__( 'Broken Links Recovery', 'archivarix-broken-links-recovery' ),
			'manage_options',
			'archivarix-broken-links-recovery',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JavaScript assets.
	 *
	 * Only loads on the plugin's admin page to avoid conflicts.
	 * Passes settings and localized strings to JavaScript.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'tools_page_archivarix-broken-links-recovery' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ablr-admin',
			ABLR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ABLR_VERSION
		);

		wp_enqueue_script(
			'ablr-admin',
			ABLR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ABLR_VERSION,
			true
		);

		// Pass settings and localized strings to JavaScript.
		wp_localize_script(
			'ablr-admin',
			'ablrData',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ablr_nonce' ),
				'settings' => get_option( 'ablr_settings', array() ),
				'strings'  => array(
					'scanning'            => __( 'Scanning...', 'archivarix-broken-links-recovery' ),
					'startScan'           => __( 'Start Scan', 'archivarix-broken-links-recovery' ),
					'scanStarted'         => __( 'Scan started', 'archivarix-broken-links-recovery' ),
					'scanResumed'         => __( 'Scan resumed', 'archivarix-broken-links-recovery' ),
					'scanComplete'        => __( 'Scan complete!', 'archivarix-broken-links-recovery' ),
					'scanCancelled'       => __( 'Scan paused.', 'archivarix-broken-links-recovery' ),
					'scanStopped'         => __( 'Scan stopped.', 'archivarix-broken-links-recovery' ),
					'error'               => __( 'Error', 'archivarix-broken-links-recovery' ),
					'confirmProcess'      => __( 'Start processing?', 'archivarix-broken-links-recovery' ),
					'confirmStop'         => __( 'Pause the scan? You can resume it later.', 'archivarix-broken-links-recovery' ),
					'confirmStopFull'     => __( 'Stop the scan completely? You will not be able to resume — only start a new scan.', 'archivarix-broken-links-recovery' ),
					'confirmRestart'      => __( 'Are you sure you want to restart from the beginning? Already checked links will be skipped (cached).', 'archivarix-broken-links-recovery' ),
					'confirmClear'        => __( 'Are you sure you want to clear all data? This cannot be undone.', 'archivarix-broken-links-recovery' ),
					'dataCleared'         => __( 'All data cleared.', 'archivarix-broken-links-recovery' ),
					'noLinksSelected'     => __( 'No links selected.', 'archivarix-broken-links-recovery' ),
					'enterCustomUrl'      => __( 'Enter replacement URL:', 'archivarix-broken-links-recovery' ),
					'noLinks'             => __( 'No links found.', 'archivarix-broken-links-recovery' ),
					'noLogs'              => __( 'No logs yet.', 'archivarix-broken-links-recovery' ),
					'copied'              => __( 'Copied!', 'archivarix-broken-links-recovery' ),
					'extracting'          => __( 'Extracting links', 'archivarix-broken-links-recovery' ),
					'checking'            => __( 'Checking links', 'archivarix-broken-links-recovery' ),
					'linksFound'          => __( 'links found', 'archivarix-broken-links-recovery' ),
					'linksLabel'          => __( 'Links', 'archivarix-broken-links-recovery' ),
					'checkedLabel'        => __( 'Checked', 'archivarix-broken-links-recovery' ),
					'broken'              => __( 'Broken', 'archivarix-broken-links-recovery' ),
					'fastChecked'         => __( 'internal', 'archivarix-broken-links-recovery' ),
					'itemsToScan'         => __( 'Items to scan', 'archivarix-broken-links-recovery' ),
					'pendingLinks'        => __( 'Pending links', 'archivarix-broken-links-recovery' ),
					'waybackView'         => __( 'View', 'archivarix-broken-links-recovery' ),
					'waybackSearch'       => __( 'Search', 'archivarix-broken-links-recovery' ),
					'waybackOpen'         => __( 'Open archived version in Web Archive', 'archivarix-broken-links-recovery' ),
					'waybackCheck'        => __( 'Search for this URL in Web Archive', 'archivarix-broken-links-recovery' ),
					'waybackNa'           => __( 'Not applicable for internal links', 'archivarix-broken-links-recovery' ),
					'internalNotEnabled'  => __( 'Internal link checking is not enabled. Go to Settings tab and enable "Check internal links for 404 errors", then run a new scan.', 'archivarix-broken-links-recovery' ),
					'descRemoveLink'      => __( 'If found in Web Archive — replace broken link with archive copy. If not found — remove the link tag but keep the anchor text visible.', 'archivarix-broken-links-recovery' ),
					'descRemoveAll'       => __( 'If found in Web Archive — replace broken link with archive copy. If not found — remove both the link tag and its anchor text completely.', 'archivarix-broken-links-recovery' ),
					'descSkipLink'        => __( 'Do not check Web Archive. Immediately remove the link tag but keep the anchor text visible. Faster processing — saves 1-3 seconds per broken link.', 'archivarix-broken-links-recovery' ),
					'descSkipAll'         => __( 'Do not check Web Archive. Immediately remove both the link tag and its anchor text completely. Faster processing — saves 1-3 seconds per broken link.', 'archivarix-broken-links-recovery' ),
					// Proxy strings.
					'noProxies'           => __( 'No proxies to test', 'archivarix-broken-links-recovery' ),
					'testingProxies'      => __( 'Testing proxies...', 'archivarix-broken-links-recovery' ),
					'proxiesWorking'      => __( 'working', 'archivarix-broken-links-recovery' ),
					'useWorkingOnly'      => __( 'Keep only working proxies', 'archivarix-broken-links-recovery' ),
					'proxiesKept'         => __( 'proxies kept', 'archivarix-broken-links-recovery' ),
					'proxiesSaved'        => __( 'proxies saved', 'archivarix-broken-links-recovery' ),
					'proxyConfigured'     => __( '1 proxy configured', 'archivarix-broken-links-recovery' ),
					'proxiesConfigured'   => __( 'proxies configured', 'archivarix-broken-links-recovery' ),
					'confirmClearProxies' => __( 'Clear all proxies?', 'archivarix-broken-links-recovery' ),
					// New UI strings for compact actions.
					'editSource'          => __( 'Edit Source', 'archivarix-broken-links-recovery' ),
					'editInWp'            => __( 'Edit post in WordPress', 'archivarix-broken-links-recovery' ),
					'viewPage'            => __( 'View Page', 'archivarix-broken-links-recovery' ),
					'viewOnSite'          => __( 'View on site', 'archivarix-broken-links-recovery' ),
					'useArchive'          => __( 'Replace with archived version', 'archivarix-broken-links-recovery' ),
					'keepText'            => __( 'Remove link, keep text', 'archivarix-broken-links-recovery' ),
					'deleteAll'           => __( 'Delete link and text', 'archivarix-broken-links-recovery' ),
					'replaceUrl'          => __( 'Replace with custom URL', 'archivarix-broken-links-recovery' ),
					'skipLink'            => __( 'Skip this link', 'archivarix-broken-links-recovery' ),
					'undoAction'          => __( 'Undo this action', 'archivarix-broken-links-recovery' ),
					'recheckLink'         => __( 'Recheck this link', 'archivarix-broken-links-recovery' ),
				),
			)
		);
	}

	/**
	 * Add "Settings" link to plugin row on Plugins page.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public static function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'tools.php?page=archivarix-broken-links-recovery' ),
			__( 'Settings', 'archivarix-broken-links-recovery' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render the admin page.
	 *
	 * Loads settings, progress data, and counts, then includes the template.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = get_option( 'ablr_settings', array() );
		$progress = get_option( 'ablr_scan_progress', array() );
		$counts   = ABLR_Database::get_status_counts();

		$process        = new ABLR_Scan_Process();
		$is_scan_active = $process->is_active();

		$tpl = ABLR_PLUGIN_DIR . 'templates/admin-page.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
		}
	}
}
