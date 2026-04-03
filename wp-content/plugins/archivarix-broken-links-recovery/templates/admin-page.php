<?php
/**
 * Admin page template for Archivarix Broken Links Recovery.
 *
 * @package Archivarix_Broken_Links_Recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ablr-admin-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="ablr-admin-container">
		<nav class="nav-tab-wrapper">
			<a href="#settings" class="nav-tab nav-tab-active"><?php esc_html_e( 'Settings', 'archivarix-broken-links-recovery' ); ?></a>
			<a href="#process" class="nav-tab"><?php esc_html_e( 'Process', 'archivarix-broken-links-recovery' ); ?></a>
			<a href="#links" class="nav-tab"><?php esc_html_e( 'Broken Links', 'archivarix-broken-links-recovery' ); ?></a>
		</nav>
		
		<!-- Settings Tab -->
		<div id="settings" class="ablr-tab-content active">
			<form id="ablr-settings-form">
				<h2><?php esc_html_e( 'Scan Sources', 'archivarix-broken-links-recovery' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Post Types', 'archivarix-broken-links-recovery' ); ?></th>
						<td>
							<fieldset>
								<?php
								$ablr_scan_post_types = isset( $settings['scan_post_types'] ) ? (array) $settings['scan_post_types'] : array( 'post', 'page' );
								$ablr_all_post_types  = get_post_types( array( 'public' => true ), 'objects' );
								foreach ( $ablr_all_post_types as $ablr_pt ) :
									// Skip 'attachment' — not useful for link scanning.
									if ( 'attachment' === $ablr_pt->name ) {
										continue;
									}
									?>
								<label><input type="checkbox" name="scan_post_types[]" value="<?php echo esc_attr( $ablr_pt->name ); ?>" <?php checked( in_array( $ablr_pt->name, $ablr_scan_post_types, true ) ); ?>> <?php echo esc_html( $ablr_pt->labels->name ); ?> <code>(<?php echo esc_html( $ablr_pt->name ); ?>)</code></label><br>
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Select which post types to scan for broken links.', 'archivarix-broken-links-recovery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Internal Links', 'archivarix-broken-links-recovery' ); ?></th>
						<td>
							<label><input type="checkbox" name="scan_internal_links" value="1" <?php checked( ! empty( $settings['scan_internal_links'] ) ); ?>> <?php esc_html_e( 'Check internal links for 404 errors', 'archivarix-broken-links-recovery' ); ?></label>
						</td>
					</tr>
				</table>
				
				<h2><?php esc_html_e( 'Proxy Settings', 'archivarix-broken-links-recovery' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="proxies"><?php esc_html_e( 'Proxy List', 'archivarix-broken-links-recovery' ); ?></label>
							<span class="ablr-optional-badge"><?php esc_html_e( 'Optional', 'archivarix-broken-links-recovery' ); ?></span>
						</th>
						<td>
							<?php $ablr_proxies = isset( $settings['proxies'] ) ? $settings['proxies'] : array(); ?>
							<div class="ablr-proxy-section">
								<div class="ablr-proxy-toggle">
									<button type="button" class="button" id="ablr-toggle-proxy">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php esc_html_e( 'Add Proxies', 'archivarix-broken-links-recovery' ); ?>
									</button>
									<?php if ( ! empty( $ablr_proxies ) ) : ?>
									<button type="button" class="button ablr-btn-danger" id="ablr-clear-proxies" style="margin-left: 8px;">
										<span class="dashicons dashicons-trash"></span>
										<?php esc_html_e( 'Clear Proxies', 'archivarix-broken-links-recovery' ); ?>
									</button>
									<span class="ablr-proxy-count">
										<?php
										/* translators: %d: number of proxies configured */
										echo esc_html( sprintf( _n( '%d proxy configured', '%d proxies configured', count( $ablr_proxies ), 'archivarix-broken-links-recovery' ), count( $ablr_proxies ) ) );
										?>
									</span>
									<?php endif; ?>
								</div>
								<div class="ablr-proxy-input" style="display:none;">
									<textarea name="proxies" id="proxies" rows="4" class="large-text code" placeholder="<?php esc_attr_e( 'ip:port:user:pass (one per line)', 'archivarix-broken-links-recovery' ); ?>">
									<?php
										// Convert stored proxies back to text format.
									if ( ! empty( $ablr_proxies ) ) {
										$ablr_lines = array();
										foreach ( $ablr_proxies as $ablr_p ) {
											$ablr_line = $ablr_p['host'] . ':' . $ablr_p['port'];
											if ( ! empty( $ablr_p['user'] ) && ! empty( $ablr_p['pass'] ) ) {
												$ablr_line .= ':' . $ablr_p['user'] . ':' . $ablr_p['pass'];
											}
											$ablr_lines[] = $ablr_line;
										}
										echo esc_textarea( implode( "\n", $ablr_lines ) );
									}
									?>
									</textarea>
									<p class="description">
										<?php esc_html_e( 'Format: ip:port:user:pass — one proxy per line.', 'archivarix-broken-links-recovery' ); ?>
									</p>
									<div class="ablr-proxy-actions">
										<button type="button" class="button" id="ablr-test-proxies">
											<span class="dashicons dashicons-update"></span>
											<?php esc_html_e( 'Test Proxies', 'archivarix-broken-links-recovery' ); ?>
										</button>
										<span class="ablr-proxy-status" id="ablr-proxy-status"></span>
									</div>
									<div class="ablr-proxy-results" id="ablr-proxy-results" style="display:none;"></div>
								</div>
							</div>
							<p class="description">
								<?php esc_html_e( 'Some sites may block automated scanning. Add proxies to bypass these restrictions.', 'archivarix-broken-links-recovery' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<h2><?php esc_html_e( 'Automatic Actions', 'archivarix-broken-links-recovery' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Processing Mode', 'archivarix-broken-links-recovery' ); ?></th>
						<td>
							<select name="auto_mode">
								<option value="manual" <?php selected( ( $settings['auto_mode'] ?? 'manual' ), 'manual' ); ?>><?php esc_html_e( 'Manual — Review and fix links manually', 'archivarix-broken-links-recovery' ); ?></option>
								<option value="auto" <?php selected( ( $settings['auto_mode'] ?? 'manual' ), 'auto' ); ?>><?php esc_html_e( 'Automatic — Fix broken links during scan', 'archivarix-broken-links-recovery' ); ?></option>
							</select>
						</td>
					</tr>
					<tr class="ablr-auto-options" <?php echo ( ( $settings['auto_mode'] ?? 'manual' ) !== 'auto' ) ? 'style="display:none;"' : ''; ?>>
						<th scope="row"><?php esc_html_e( 'Auto Action', 'archivarix-broken-links-recovery' ); ?></th>
						<td>
							<select name="auto_action">
								<option value="replace_wayback" <?php selected( ( $settings['auto_action'] ?? 'remove_link' ), 'replace_wayback' ); ?>><?php esc_html_e( 'Web Archive if possible, otherwise remove link', 'archivarix-broken-links-recovery' ); ?></option>
								<option value="remove_link" <?php selected( ( $settings['auto_action'] ?? 'remove_link' ), 'remove_link' ); ?>><?php esc_html_e( 'Web Archive if possible, otherwise remove link and anchor text', 'archivarix-broken-links-recovery' ); ?></option>
								<option value="skip_wayback_remove_link" <?php selected( ( $settings['auto_action'] ?? 'remove_link' ), 'skip_wayback_remove_link' ); ?>><?php esc_html_e( 'Skip Web Archive, just remove link', 'archivarix-broken-links-recovery' ); ?></option>
								<option value="skip_wayback_remove_all" <?php selected( ( $settings['auto_action'] ?? 'remove_link' ), 'skip_wayback_remove_all' ); ?>><?php esc_html_e( 'Skip Web Archive, remove link and anchor text', 'archivarix-broken-links-recovery' ); ?></option>
							</select>
							<p class="description ablr-action-description"></p>
						</td>
					</tr>
					<tr class="ablr-auto-options" <?php echo ( ( $settings['auto_mode'] ?? 'manual' ) !== 'auto' ) ? 'style="display:none;"' : ''; ?>>
						<th scope="row"><?php esc_html_e( 'Internal Links', 'archivarix-broken-links-recovery' ); ?></th>
						<td>
							<label><input type="checkbox" name="auto_fix_internal" value="1" <?php checked( ! empty( $settings['auto_fix_internal'] ) ); ?>> <?php esc_html_e( 'Apply auto-fix to internal 404 links', 'archivarix-broken-links-recovery' ); ?></label>
							<p class="description"><?php esc_html_e( 'When enabled, internal 404 links will be automatically fixed during scan. Web Archive is not used for internal links — they will be removed based on the action above (keep or remove anchor text).', 'archivarix-broken-links-recovery' ); ?></p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'archivarix-broken-links-recovery' ); ?></button>
				</p>
			</form>

			<div class="ablr-archivarix-promo">
				<a href="https://archivarix.com" target="_blank" class="ablr-promo-logo">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 76 76" width="36" height="36">
						<circle fill="#ffa700" cx="38" cy="38" r="37"/>
						<path fill="#fff" d="M23.4 19.1c1.9-.8 3.7-1.2 5.4-1.2 1.4 0 2.9.5 4.5 1.6.8.6 1.8 1.7 2.8 3.4.7 1.2 1.6 3.3 2.6 6.3l5.3 15c1.2 3.4 2.5 6 3.7 8 1.3 2 2.4 3.5 3.4 4.5s2.1 1.7 3.3 2.1c1.1.4 2.1.6 2.8.6s1.4-.1 2-.2v.4c-1.4.5-2.7.7-4.1.7-1.3 0-2.7-.3-4-1-1.3-.7-2.6-1.6-3.7-2.8C45 54 42.9 50.1 41.2 45l-1.7-4.9H27.6l-3 7.7c-.1.3-.2.7-.2 1 0 .3.2.7.5 1.1s.8.6 1.4.6h.3v.4h-8.7v-.4h.4c.7 0 1.4-.2 2-.6.7-.4 1.2-1 1.6-1.9l10.8-25.8c-1.6-2.2-3.5-3.3-5.8-3.3-1 0-2.2.2-3.3.7l-.2-.5zm4.7 19.6h11l-3.4-10.1c-.7-1.9-1.3-3.5-1.8-4.6l-5.8 14.7z"/>
					</svg>
					<span class="ablr-promo-logo-text">ARCHIVARIX</span>
				</a>
				<div class="ablr-promo-content">
					<p class="ablr-promo-text">
						<?php echo wp_kses( __( 'Restore websites from the <strong>Wayback Machine</strong> with high accuracy. Download archived sites, remove ads and trackers, optimize images, and get a fully functional copy powered by the free <strong>Archivarix CMS</strong>. Trusted by thousands of users worldwide since 2017 for website recovery, migration, and SEO projects.', 'archivarix-broken-links-recovery' ), array( 'strong' => array() ) ); ?>
					</p>
				</div>
				<div class="ablr-promo-links">
					<a href="https://archivarix.com" target="_blank" class="button button-primary"><?php esc_html_e( 'Visit Website', 'archivarix-broken-links-recovery' ); ?></a>
				</div>
			</div>
		</div>

		<!-- Process Tab -->
		<div id="process" class="ablr-tab-content">
			<div class="ablr-step">
				<h3><?php esc_html_e( 'Scan & Check Links', 'archivarix-broken-links-recovery' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Scan your content for links and check their status.', 'archivarix-broken-links-recovery' ); ?></p>
				<button type="button" class="button button-primary" id="ablr-scan-btn"><?php esc_html_e( 'Start Scan', 'archivarix-broken-links-recovery' ); ?></button>
				<div class="ablr-resume-buttons" style="display:none;">
					<p class="ablr-resume-notice"><?php esc_html_e( 'Previous scan was stopped. You can resume or start fresh.', 'archivarix-broken-links-recovery' ); ?></p>
					<button type="button" class="button button-primary" id="ablr-resume-btn"><?php esc_html_e( 'Resume', 'archivarix-broken-links-recovery' ); ?></button>
					<button type="button" class="button" id="ablr-restart-btn"><?php esc_html_e( 'Start Fresh', 'archivarix-broken-links-recovery' ); ?></button>
				</div>
				<div id="ablr-scan-results" class="ablr-results"></div>
			</div>
			<div class="ablr-background-status" style="display:none;">
				<span class="ablr-status-indicator running"></span>
				<span class="ablr-status-text"><?php esc_html_e( 'Background process is running...', 'archivarix-broken-links-recovery' ); ?></span>
				<button type="button" class="button button-small ablr-stop-btn" id="ablr-stop-btn"><?php esc_html_e( 'Pause', 'archivarix-broken-links-recovery' ); ?></button>
				<button type="button" class="button button-small ablr-stop-full-btn" id="ablr-stop-full-btn"><?php esc_html_e( 'Stop', 'archivarix-broken-links-recovery' ); ?></button>
			</div>
			<div class="ablr-progress-container" style="display:none;">
				<div class="ablr-progress-bar"><div class="ablr-progress-fill"></div></div>
				<div class="ablr-progress-text"></div>
				<div class="ablr-progress-stats">
					<div class="ablr-progress-stats-row ablr-progress-stats-totals">
						<span class="ablr-pstat ablr-pstat-total">
							<?php esc_html_e( 'Total:', 'archivarix-broken-links-recovery' ); ?> <strong id="prog-total">0</strong>
						</span>
						<span class="ablr-pstat ablr-pstat-internal">
							<?php esc_html_e( 'Internal:', 'archivarix-broken-links-recovery' ); ?> <strong id="prog-internal">0</strong>
						</span>
						<span class="ablr-pstat ablr-pstat-external">
							<?php esc_html_e( 'External:', 'archivarix-broken-links-recovery' ); ?> <strong id="prog-external">0</strong>
						</span>
					</div>
					<div class="ablr-progress-stats-row ablr-progress-stats-details">
						<span class="ablr-pstat ablr-pstat-checked">
							<?php esc_html_e( 'Checked:', 'archivarix-broken-links-recovery' ); ?>
							<span class="ablr-pstat-int" title="<?php esc_attr_e( 'Internal', 'archivarix-broken-links-recovery' ); ?>"><span id="prog-checked-int">0</span> int</span>
							<span class="ablr-pstat-ext" title="<?php esc_attr_e( 'External', 'archivarix-broken-links-recovery' ); ?>"><span id="prog-checked-ext">0</span> ext</span>
						</span>
						<span class="ablr-pstat ablr-pstat-broken">
							<?php esc_html_e( 'Broken:', 'archivarix-broken-links-recovery' ); ?>
							<span class="ablr-pstat-int"><span id="prog-broken-int">0</span> int</span>
							<span class="ablr-pstat-ext"><span id="prog-broken-ext">0</span> ext</span>
						</span>
						<span class="ablr-pstat ablr-pstat-fixed">
							<?php esc_html_e( 'Fixed:', 'archivarix-broken-links-recovery' ); ?>
							<span class="ablr-pstat-int"><span id="prog-fixed-int">0</span> int</span>
							<span class="ablr-pstat-ext"><span id="prog-fixed-ext">0</span> ext</span>
						</span>
						<span class="ablr-pstat ablr-pstat-uncheckable">
							<?php esc_html_e( 'Uncheckable:', 'archivarix-broken-links-recovery' ); ?>
							<span class="ablr-pstat-int"><span id="prog-uncheckable-int">0</span> int</span>
							<span class="ablr-pstat-ext"><span id="prog-uncheckable-ext">0</span> ext</span>
						</span>
						<span class="ablr-pstat ablr-pstat-whitelisted">
							<?php esc_html_e( 'Skipped:', 'archivarix-broken-links-recovery' ); ?>
							<span class="ablr-pstat-int"><span id="prog-whitelisted-int">0</span> int</span>
							<span class="ablr-pstat-ext"><span id="prog-whitelisted-ext">0</span> ext</span>
						</span>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Broken Links Tab (merged with Statistics) -->
		<div id="links" class="ablr-tab-content">
			<!-- Statistics Cards at Top -->
			<div class="ablr-stats-header">
				<div class="ablr-stats-grid ablr-stats-compact">
					<div class="ablr-stat-card total"><div class="ablr-stat-value" id="stat-total"><?php echo esc_html( $counts['total'] ); ?></div><div class="ablr-stat-label"><?php esc_html_e( 'Total', 'archivarix-broken-links-recovery' ); ?></div></div>
					<div class="ablr-stat-card success"><div class="ablr-stat-value" id="stat-ok"><?php echo esc_html( $counts['ok'] ); ?></div><div class="ablr-stat-label"><?php esc_html_e( 'OK', 'archivarix-broken-links-recovery' ); ?></div></div>
					<div class="ablr-stat-card failed"><div class="ablr-stat-value" id="stat-broken"><?php echo esc_html( $counts['broken'] ); ?></div><div class="ablr-stat-label"><?php esc_html_e( 'Broken', 'archivarix-broken-links-recovery' ); ?></div></div>
					<div class="ablr-stat-card fixed"><div class="ablr-stat-value" id="stat-fixed"><?php echo esc_html( $counts['fixed'] ); ?></div><div class="ablr-stat-label"><?php esc_html_e( 'Fixed', 'archivarix-broken-links-recovery' ); ?></div></div>
					<div class="ablr-stat-card uncheckable"><div class="ablr-stat-value" id="stat-uncheckable"><?php echo esc_html( $counts['uncheckable'] ); ?></div><div class="ablr-stat-label"><?php esc_html_e( 'Uncheckable', 'archivarix-broken-links-recovery' ); ?></div></div>
					<div class="ablr-stat-card whitelisted"><div class="ablr-stat-value" id="stat-whitelisted"><?php echo esc_html( $counts['whitelisted'] ); ?></div><div class="ablr-stat-label"><?php esc_html_e( 'Skipped', 'archivarix-broken-links-recovery' ); ?></div></div>
				</div>
				<button type="button" class="button ablr-btn-danger" id="ablr-clear-data-btn">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Clear All Data', 'archivarix-broken-links-recovery' ); ?>
				</button>
			</div>
			
			<div class="ablr-links-toolbar">
				<div class="ablr-links-filters">
					<select id="ablr-filter-status">
						<option value=""><?php esc_html_e( 'All statuses', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="broken"><?php esc_html_e( 'Broken', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="fixed"><?php esc_html_e( 'Fixed', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="uncheckable"><?php esc_html_e( 'Uncheckable', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="ok"><?php esc_html_e( 'OK', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="whitelisted"><?php esc_html_e( 'Skipped', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending', 'archivarix-broken-links-recovery' ); ?></option>
					</select>
					<input type="search" id="ablr-search" placeholder="<?php esc_attr_e( 'Search URL or anchor...', 'archivarix-broken-links-recovery' ); ?>">
					<button id="ablr-filter-apply" class="button"><?php esc_html_e( 'Filter', 'archivarix-broken-links-recovery' ); ?></button>
					<div class="ablr-link-type-switch">
						<button type="button" class="ablr-switch-btn active" data-filter=""><?php esc_html_e( 'All', 'archivarix-broken-links-recovery' ); ?></button>
						<button type="button" class="ablr-switch-btn" data-filter="external"><?php esc_html_e( 'External', 'archivarix-broken-links-recovery' ); ?> <span class="ablr-switch-count" id="ablr-count-external"></span></button>
						<button type="button" class="ablr-switch-btn" data-filter="internal"><?php esc_html_e( 'Internal 404', 'archivarix-broken-links-recovery' ); ?> <span class="ablr-switch-count" id="ablr-count-internal"></span></button>
					</div>
				</div>
				<div class="ablr-bulk-actions">
					<select id="ablr-bulk-action">
						<option value=""><?php esc_html_e( 'Bulk Actions', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="replace_wayback"><?php esc_html_e( 'Use Archive copy', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="remove_link"><?php esc_html_e( 'Keep text only', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="remove_all"><?php esc_html_e( 'Delete completely', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="whitelist"><?php esc_html_e( 'Skip', 'archivarix-broken-links-recovery' ); ?></option>
						<option value="undo"><?php esc_html_e( 'Undo', 'archivarix-broken-links-recovery' ); ?></option>
					</select>
					<button id="ablr-bulk-apply" class="button"><?php esc_html_e( 'Apply', 'archivarix-broken-links-recovery' ); ?></button>
				</div>
			</div>
			<div class="ablr-links-toolbar ablr-links-toolbar-bottom">
				<div class="ablr-per-page">
					<label><?php esc_html_e( 'Show:', 'archivarix-broken-links-recovery' ); ?>
						<select id="ablr-per-page">
							<option value="30">30</option>
							<option value="100">100</option>
							<option value="200">200</option>
							<option value="0"><?php esc_html_e( 'All', 'archivarix-broken-links-recovery' ); ?></option>
						</select>
					</label>
					<span id="ablr-links-total" class="ablr-total-count"></span>
				</div>
			</div>

			<div class="ablr-edit-warning">
				<span class="dashicons dashicons-warning"></span>
				<span><strong><?php esc_html_e( 'Tip:', 'archivarix-broken-links-recovery' ); ?></strong> <?php esc_html_e( 'For complex pages (Elementor, page builders), it\'s better to edit directly in the WordPress editor. Use "Edit Source" button to open the post.', 'archivarix-broken-links-recovery' ); ?></span>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="check-column"><input type="checkbox" id="ablr-select-all"></td>
						<th class="column-url"><?php esc_html_e( 'URL', 'archivarix-broken-links-recovery' ); ?></th>
						<th class="column-status"><?php esc_html_e( 'Status', 'archivarix-broken-links-recovery' ); ?></th>
						<th class="column-reason"><?php esc_html_e( 'Reason', 'archivarix-broken-links-recovery' ); ?></th>
						<th class="column-source"><?php esc_html_e( 'Edit Source', 'archivarix-broken-links-recovery' ); ?></th>
						<th class="column-wayback"><?php esc_html_e( 'Web Archive', 'archivarix-broken-links-recovery' ); ?></th>
						<th class="column-actions"><?php esc_html_e( 'Actions', 'archivarix-broken-links-recovery' ); ?></th>
					</tr>
				</thead>
				<tbody id="ablr-links-body">
					<tr><td colspan="7"><?php esc_html_e( 'Run a scan first, then view results here.', 'archivarix-broken-links-recovery' ); ?></td></tr>
				</tbody>
			</table>
			<div class="ablr-logs-pagination" id="ablr-links-pagination"></div>
		</div>
	</div>
</div>
