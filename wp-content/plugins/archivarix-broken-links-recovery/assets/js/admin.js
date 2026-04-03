/**
 * Archivarix Broken Links Recovery — Admin JavaScript
 *
 * Handles all admin UI functionality:
 * - Tab navigation
 * - Settings form
 * - Scan control (start/stop/resume)
 * - Progress polling
 * - Links table with filtering and pagination
 * - Bulk and individual link actions
 * - Activity logs
 *
 * Designed to be consistent with Archivarix External Images Importer UX.
 */
(function($) {
    'use strict';

    var backgroundRunning = false;
    var linksPage = 1;

    // Progress tracking - ensure values never go backwards
    var progressState = {
        maxChecked: 0,
        maxTotalLinks: 0,
        maxBroken: 0,
        maxExtracted: 0,
        maxLinksFound: 0
    };

    // Determine if Web Archive checks are skipped (no Wayback lookups at all).
    // This affects UI visibility of Web Archive column and action buttons.
    var autoAction = (ablrData.settings && ablrData.settings.auto_action) ? ablrData.settings.auto_action : 'remove_link';
    var isSkipWayback = (autoAction === 'skip_wayback_remove_link' || autoAction === 'skip_wayback_remove_all');

    $(document).ready(function() {
        initTabs();
        initSettings();
        initScan();
        initLinkActions();
        checkBackground();

        // Hide Web Archive column and bulk action when skip_wayback is active.
        if (isSkipWayback) {
            $('.column-wayback').hide();
            $('#ablr-bulk-action option[value="replace_wayback"]').remove();
        }
    });

    // ── Tabs ──
    function initTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var id = $(this).attr('href');
            var $currentTab = $('.nav-tab-active');
            var currentId = $currentTab.attr('href');

            // Auto-save settings when leaving settings tab.
            if (currentId === '#settings' && id !== '#settings') {
                saveSettingsSilently();
            }

            // Stop polling when leaving process tab to prevent race conditions.
            // Polling will restart when returning to process tab if scan is active.
            if (currentId === '#process' && id !== '#process' && window.ablrPoll) {
                clearInterval(window.ablrPoll);
                window.ablrPoll = null;
            }

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.ablr-tab-content').removeClass('active');
            $(id).addClass('active');

            if (id === '#links') {
                loadLinks(1);
                // Note: counts are now included in loadLinks response.
            }

            // Resume polling when returning to process tab if scan is active.
            if (id === '#process' && backgroundRunning && !window.ablrPoll) {
                pollBackground();
            }
        });
    }

    /**
     * Save settings without showing a notification (silent save on tab switch).
     */
    function saveSettingsSilently() {
        var data = $('#ablr-settings-form').serializeArray();
        data.push({ name: 'action', value: 'ablr_save_settings' });
        data.push({ name: 'nonce', value: ablrData.nonce });

        $.post(ablrData.ajaxUrl, $.param(data), function(response) {
            if (response.success) {
                // Update ablrData.settings to reflect saved values.
                ablrData.settings.scan_internal_links = $('input[name="scan_internal_links"]').is(':checked') ? 1 : 0;

                // Update proxy count display silently.
                if (response.data.proxy_count > 0) {
                    var countText = response.data.proxy_count === 1
                        ? (ablrData.strings.proxyConfigured || '1 proxy configured')
                        : response.data.proxy_count + ' ' + (ablrData.strings.proxiesConfigured || 'proxies configured');
                    $('.ablr-proxy-count').text(countText).show();
                } else {
                    $('.ablr-proxy-count').hide();
                }

                // Update skip-wayback state from newly saved settings.
                var newAction = $('select[name="auto_action"]').val() || 'remove_link';
                autoAction = newAction;
                isSkipWayback = (newAction === 'skip_wayback_remove_link' || newAction === 'skip_wayback_remove_all');
                if (isSkipWayback) {
                    $('.column-wayback').hide();
                    $('#ablr-bulk-action option[value="replace_wayback"]').remove();
                } else {
                    $('.column-wayback').show();
                    if ($('#ablr-bulk-action option[value="replace_wayback"]').length === 0) {
                        $('#ablr-bulk-action option:first').after('<option value="replace_wayback">' + (ablrData.strings.bulkWayback || 'Replace with Web Archive') + '</option>');
                    }
                }
            }
        });
    }

    // ── Settings ──
    function initSettings() {
        $('select[name="auto_mode"]').on('change', function() {
            if ($(this).val() === 'auto') {
                $('.ablr-auto-options').show();
                updateActionDescription();
            } else {
                $('.ablr-auto-options').hide();
            }
        });

        // Dynamic description for auto_action.
        $('select[name="auto_action"]').on('change', function() {
            updateActionDescription();
        });
        // Initialize description on load.
        updateActionDescription();

        // Proxy section toggle.
        $('#ablr-toggle-proxy').on('click', function() {
            var $input = $('.ablr-proxy-input');
            var $icon = $(this).find('.dashicons');
            if ($input.is(':visible')) {
                $input.slideUp(200);
                $icon.removeClass('dashicons-minus').addClass('dashicons-plus-alt2');
            } else {
                $input.slideDown(200);
                $icon.removeClass('dashicons-plus-alt2').addClass('dashicons-minus');
            }
        });

        // Clear Proxies button.
        $('#ablr-clear-proxies').on('click', function() {
            if (!confirm(ablrData.strings.confirmClearProxies || 'Clear all proxies?')) return;
            $('#proxies').val('');
            // Submit form to save empty proxies.
            $('#ablr-settings-form').trigger('submit');
        });

        // Auto-expand if proxies exist.
        if ($('#proxies').val().trim()) {
            $('.ablr-proxy-input').show();
            $('#ablr-toggle-proxy .dashicons').removeClass('dashicons-plus-alt2').addClass('dashicons-minus');
        }

        // Test proxies button.
        $('#ablr-test-proxies').on('click', function() {
            var $btn = $(this);
            var $status = $('#ablr-proxy-status');
            var $results = $('#ablr-proxy-results');
            var proxies = $('#proxies').val().trim();

            if (!proxies) {
                $status.html('<span class="ablr-proxy-error">' + (ablrData.strings.noProxies || 'No proxies to test') + '</span>');
                return;
            }

            $btn.prop('disabled', true);
            $status.html('<span class="ablr-proxy-testing"><span class="ablr-spinner-inline"></span> ' + (ablrData.strings.testingProxies || 'Testing proxies...') + '</span>');
            $results.hide().empty();

            $.post(ablrData.ajaxUrl, {
                action: 'ablr_test_proxies',
                nonce: ablrData.nonce,
                proxies: proxies
            }, function(response) {
                $btn.prop('disabled', false);

                if (!response.success) {
                    $status.html('<span class="ablr-proxy-error">' + esc(response.data || 'Error') + '</span>');
                    return;
                }

                var data = response.data;
                var statusText = data.working_count + ' / ' + data.total_count + ' ' + (ablrData.strings.proxiesWorking || 'working');

                if (data.working_count === data.total_count) {
                    $status.html('<span class="ablr-proxy-success">✓ ' + statusText + '</span>');
                } else if (data.working_count > 0) {
                    $status.html('<span class="ablr-proxy-partial">⚠ ' + statusText + '</span>');
                } else {
                    $status.html('<span class="ablr-proxy-error">✕ ' + statusText + '</span>');
                }

                // Show detailed results.
                if (data.results.length > 0) {
                    var html = '<div class="ablr-proxy-results-list">';
                    $.each(data.results, function(i, r) {
                        var cls = r.working ? 'success' : 'failed';
                        var icon = r.working ? '✓' : '✕';
                        var ip = r.ip ? ' → ' + r.ip : '';
                        html += '<div class="ablr-proxy-result ' + cls + '">' +
                            '<span class="ablr-proxy-icon">' + icon + '</span> ' +
                            '<span class="ablr-proxy-addr">' + esc(r.proxy) + '</span>' +
                            '<span class="ablr-proxy-msg">' + esc(r.message) + ip + '</span>' +
                            '</div>';
                    });
                    html += '</div>';

                    // Add "Use working only" button if some failed.
                    if (data.working_count < data.total_count && data.working_count > 0) {
                        html += '<button type="button" class="button button-small" id="ablr-use-working">' +
                            (ablrData.strings.useWorkingOnly || 'Keep only working proxies') + '</button>';
                    }

                    $results.html(html).slideDown(200);

                    // Handle "Use working only" button (use event delegation to prevent memory leak).
                    $(document).off('click', '#ablr-use-working').on('click', '#ablr-use-working', function() {
                        $('#proxies').val(data.working.join('\n'));
                        $results.slideUp(200);
                        $status.html('<span class="ablr-proxy-success">✓ ' + data.working_count + ' ' + (ablrData.strings.proxiesKept || 'proxies kept') + '</span>');
                    });
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $status.html('<span class="ablr-proxy-error">' + (ablrData.strings.error || 'Error') + '</span>');
            });
        });

        $('#ablr-settings-form').on('submit', function(e) {
            e.preventDefault();

            var data = $(this).serializeArray();
            data.push({ name: 'action', value: 'ablr_save_settings' });
            data.push({ name: 'nonce', value: ablrData.nonce });

            $.post(ablrData.ajaxUrl, $.param(data), function(response) {
                if (response.success) {
                    // Update ablrData.settings to reflect saved values.
                    ablrData.settings.scan_internal_links = $('input[name="scan_internal_links"]').is(':checked') ? 1 : 0;

                    var msg = response.data.message;
                    if (response.data.proxy_count > 0) {
                        msg += ' (' + response.data.proxy_count + ' ' + (ablrData.strings.proxiesSaved || 'proxies saved') + ')';
                    }
                    showNotice(msg, 'success');

                    // Update proxy count display.
                    if (response.data.proxy_count > 0) {
                        var countText = response.data.proxy_count === 1
                            ? (ablrData.strings.proxyConfigured || '1 proxy configured')
                            : response.data.proxy_count + ' ' + (ablrData.strings.proxiesConfigured || 'proxies configured');
                        $('.ablr-proxy-count').text(countText).show();
                    } else {
                        $('.ablr-proxy-count').hide();
                    }

                    // Update skip-wayback state from newly saved settings.
                    var newAction = $('select[name="auto_action"]').val() || 'remove_link';
                    autoAction = newAction;
                    isSkipWayback = (newAction === 'skip_wayback_remove_link' || newAction === 'skip_wayback_remove_all');
                    if (isSkipWayback) {
                        $('.column-wayback').hide();
                        $('#ablr-bulk-action option[value="replace_wayback"]').remove();
                    } else {
                        $('.column-wayback').show();
                        if ($('#ablr-bulk-action option[value="replace_wayback"]').length === 0) {
                            $('#ablr-bulk-action option:first').after('<option value="replace_wayback">' + (ablrData.strings.bulkWayback || 'Replace with Web Archive') + '</option>');
                        }
                    }
                }
            });
        });
    }

    function updateActionDescription() {
        var val = $('select[name="auto_action"]').val();
        var desc = '';
        switch (val) {
            case 'remove_link':
                desc = ablrData.strings.descRemoveLink || 'If found in Web Archive — replace broken link with archive copy. If not found — remove the link tag but keep the anchor text visible.';
                break;
            case 'remove_all':
                desc = ablrData.strings.descRemoveAll || 'If found in Web Archive — replace broken link with archive copy. If not found — remove both the link tag and its anchor text completely.';
                break;
            case 'skip_wayback_remove_link':
                desc = ablrData.strings.descSkipLink || 'Do not check Web Archive. Immediately remove the link tag but keep the anchor text visible. Faster processing — saves 1-3 seconds per broken link.';
                break;
            case 'skip_wayback_remove_all':
                desc = ablrData.strings.descSkipAll || 'Do not check Web Archive. Immediately remove both the link tag and its anchor text completely. Faster processing — saves 1-3 seconds per broken link.';
                break;
        }
        $('.ablr-action-description').text(desc);
    }

    // ── Scan (start / resume / restart / stop) ──
    function initScan() {
        // Start fresh scan
        $('#ablr-scan-btn').on('click', function() {
            startFreshScan();
        });

        // Resume (continue from where it stopped)
        $('#ablr-resume-btn').on('click', function() {
            resumeScan();
        });

        // Restart from beginning
        $('#ablr-restart-btn').on('click', function() {
            if (!confirm(ablrData.strings.confirmRestart)) return;
            startFreshScan();
        });

        // Stop (pause — can resume later)
        $('#ablr-stop-btn').on('click', function() {
            if (!confirm(ablrData.strings.confirmStop)) return;

            $.post(ablrData.ajaxUrl, { action: 'ablr_stop_scan', nonce: ablrData.nonce }, function() {
                backgroundRunning = false;
                clearInterval(window.ablrPoll);
                window.ablrPoll = null;
                $('.ablr-background-status').hide();
                $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
                $('.ablr-resume-buttons').show();
                updateCounts();
            });
        });

        // Full stop (no resume)
        $('#ablr-stop-full-btn').on('click', function() {
            if (!confirm(ablrData.strings.confirmStopFull)) return;

            $.post(ablrData.ajaxUrl, { action: 'ablr_stop_scan_full', nonce: ablrData.nonce }, function() {
                backgroundRunning = false;
                clearInterval(window.ablrPoll);
                window.ablrPoll = null;
                $('.ablr-background-status').hide();
                $('.ablr-progress-container').hide();
                $('.ablr-resume-buttons').hide();
                $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
                $('#ablr-scan-results').html('<div class="ablr-scan-summary"><h4>' + ablrData.strings.scanStopped + '</h4></div>');
                updateCounts();
            });
        });

        // Clear all data
        $('#ablr-clear-data-btn').on('click', function() {
            if (!confirm(ablrData.strings.confirmClear)) return;

            $.post(ablrData.ajaxUrl, { action: 'ablr_clear_data', nonce: ablrData.nonce }, function(r) {
                if (r.success) {
                    updateCounts();
                    loadLinks(1);
                    $('.ablr-resume-buttons').hide();
                    showNotice(ablrData.strings.dataCleared, 'success');
                }
            });
        });
    }

    function startFreshScan() {
        if (backgroundRunning) return;

        // Reset progress state for fresh scan
        progressState = {
            maxChecked: 0,
            maxTotalLinks: 0,
            maxBroken: 0,
            maxExtracted: 0,
            maxLinksFound: 0
        };

        var $btn = $('#ablr-scan-btn'), $res = $('#ablr-scan-results');
        $btn.prop('disabled', true).addClass('disabled').text(ablrData.strings.scanning);
        $('.ablr-resume-buttons').hide();
        $res.html('<div class="ablr-spinner"></div>');

        // Show progress bar immediately with 0%.
        $('.ablr-progress-container').show();
        $('.ablr-progress-fill').css('width', '0%');
        $('.ablr-progress-text').text(ablrData.strings.scanning + '...');

        $.post(ablrData.ajaxUrl, { action: 'ablr_start_scan', nonce: ablrData.nonce }, function(r) {
            if (r.success) {
                backgroundRunning = true;
                $('.ablr-background-status').show();
                $res.html('<div class="ablr-scan-summary"><h4>⏳ ' + ablrData.strings.scanStarted + '</h4><p>' +
                    ablrData.strings.itemsToScan + ': ' + (r.data.total_items || 0) + '</p></div>');
                // Update progress bar with initial info.
                $('.ablr-progress-text').text(ablrData.strings.extracting + ': 0/' + (r.data.total_items || 0));
                pollBackground();
            } else {
                $res.html('<div class="ablr-error">' + esc(r.data || 'Error') + '</div>');
                $btn.prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
            }
        }).fail(function() {
            $res.html('<div class="ablr-error">' + ablrData.strings.error + '</div>');
            $btn.prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
        });
    }

    function resumeScan() {
        if (backgroundRunning) return;
        var $res = $('#ablr-scan-results');
        $('#ablr-scan-btn').prop('disabled', true).addClass('disabled');
        $('.ablr-resume-buttons').hide();
        $res.html('<div class="ablr-spinner"></div>');

        $.post(ablrData.ajaxUrl, { action: 'ablr_resume_scan', nonce: ablrData.nonce }, function(r) {
            if (r.success) {
                backgroundRunning = true;
                $('.ablr-background-status').show();
                $res.html('<div class="ablr-scan-summary"><h4>⏳ ' + ablrData.strings.scanResumed + '</h4><p>' +
                    ablrData.strings.pendingLinks + ': ' + (r.data.pending_links || 0) + '</p></div>');
                pollBackground();
            } else {
                // Check if this is a settings mismatch error.
                var errorMsg = r.data && r.data.message ? r.data.message : (r.data || 'Complete');
                var isSettingsError = r.data && r.data.code === 'settings_changed';
                if (isSettingsError) {
                    $res.html('<div class="ablr-error"><strong>⚠️ ' + esc(errorMsg) + '</strong></div>');
                    $('.ablr-resume-buttons').hide();
                } else {
                    $res.html('<div class="ablr-scan-summary success"><h4>✓ ' + esc(errorMsg) + '</h4></div>');
                }
                $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
            }
        }).fail(function() {
            $res.html('<div class="ablr-error">' + ablrData.strings.error + '</div>');
            $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
        });
    }

    // ── Background polling ──
    var pollErrorCount = 0;
    var maxPollErrors = 5;

    function checkBackground() {
        $.post(ablrData.ajaxUrl, { action: 'ablr_get_progress', nonce: ablrData.nonce }, function(r) {
            if (r.success && r.data.is_active) {
                backgroundRunning = true;
                $('.ablr-background-status').show();
                $('.ablr-resume-buttons').hide();
                $('#ablr-scan-btn').prop('disabled', true).addClass('disabled');
                pollBackground();
            }
            if (r.success && r.data.status === 'cancelled') {
                $('.ablr-resume-buttons').show();
            }
            if (r.success && r.data.status === 'stopped') {
                $('.ablr-resume-buttons').hide();
            }
            if (r.success && r.data.counts) {
                updateCountsFromData(r.data.counts);
            }
        });
    }

    function pollBackground() {
        if (window.ablrPoll) return;
        window.ablrPoll = setInterval(function() {
            $.post(ablrData.ajaxUrl, { action: 'ablr_get_progress', nonce: ablrData.nonce }, function(r) {
                pollErrorCount = 0; // Reset on success
                if (!r.success) return;
                var p = r.data;

                if (p.counts) {
                    updateCountsFromData(p.counts);
                }

                var pct = 0;
                var statusText = '';

                if (p.status === 'extracting') {
                    var total = p.total_items || 1;
                    // Monotonic: extracted and links_found never go backwards
                    var extracted = Math.max(progressState.maxExtracted, p.extracted || 0);
                    var linksFound = Math.max(progressState.maxLinksFound, p.links_found || 0);
                    progressState.maxExtracted = extracted;
                    progressState.maxLinksFound = linksFound;

                    pct = Math.round(extracted / total * 50);
                    statusText = ablrData.strings.extracting + ': ' + extracted + '/' + total +
                        ', ' + linksFound + ' ' + ablrData.strings.linksFound;
                } else if (p.status === 'checking') {
                    // Monotonic: checked, total_links, broken never go backwards
                    var checked = Math.max(progressState.maxChecked, p.checked || 0);
                    var totalLinks = Math.max(progressState.maxTotalLinks, p.total_links || 1);
                    var broken = Math.max(progressState.maxBroken, p.broken || 0);
                    var checkedInternal = p.checked_internal || 0;

                    progressState.maxChecked = checked;
                    progressState.maxTotalLinks = totalLinks;
                    progressState.maxBroken = broken;

                    // Ensure checked doesn't exceed total
                    if (checked > totalLinks) {
                        totalLinks = checked;
                        progressState.maxTotalLinks = totalLinks;
                    }

                    pct = 50 + Math.round(checked / totalLinks * 50);
                    statusText = ablrData.strings.checking + ': ' + checked + '/' + totalLinks +
                        ', ' + ablrData.strings.broken + ': ' + broken;
                    if (checkedInternal > 0) {
                        statusText += ' (' + checkedInternal + ' ' + ablrData.strings.fastChecked + ')';
                    }
                } else if (p.status === 'complete') {
                    pct = 100;

                    // Use final values from server for complete status
                    var finalLinksFound = p.links_found || progressState.maxLinksFound || 0;
                    var finalChecked = p.checked || progressState.maxChecked || 0;
                    var finalBroken = p.broken || progressState.maxBroken || 0;

                    statusText = ablrData.strings.scanComplete +
                        ' — ' + ablrData.strings.linksLabel + ': ' + finalLinksFound +
                        ', ' + ablrData.strings.checkedLabel + ': ' + finalChecked +
                        ', ' + ablrData.strings.broken + ': ' + finalBroken;

                    backgroundRunning = false;
                    clearInterval(window.ablrPoll);
                    window.ablrPoll = null;
                    $('.ablr-background-status').hide();
                    $('.ablr-resume-buttons').hide();
                    $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);

                    var $res = $('#ablr-scan-results');
                    $res.html('<div class="ablr-scan-summary success"><h4>✓ ' + ablrData.strings.scanComplete + '</h4><p>' +
                        ablrData.strings.linksLabel + ': ' + finalLinksFound +
                        ', ' + ablrData.strings.checkedLabel + ': ' + finalChecked +
                        ', ' + ablrData.strings.broken + ': ' + finalBroken +
                        '</p></div>');

                    // Reset progress state after completion
                    progressState = {
                        maxChecked: 0,
                        maxTotalLinks: 0,
                        maxBroken: 0,
                        maxExtracted: 0,
                        maxLinksFound: 0
                    };

                    updateCounts();
                } else if (p.status === 'cancelled') {
                    statusText = ablrData.strings.scanCancelled;
                    backgroundRunning = false;
                    clearInterval(window.ablrPoll);
                    window.ablrPoll = null;
                    $('.ablr-resume-buttons').show();
                } else if (p.status === 'stopped') {
                    statusText = ablrData.strings.scanStopped;
                    backgroundRunning = false;
                    clearInterval(window.ablrPoll);
                    window.ablrPoll = null;
                    $('.ablr-background-status').hide();
                    $('.ablr-resume-buttons').hide();
                    $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
                }

                if (pct > 0 || p.status === 'extracting' || p.status === 'checking') {
                    $('.ablr-progress-container').show();
                    $('.ablr-progress-fill').css('width', pct + '%');
                    $('.ablr-progress-text').text(statusText);
                }

                if (!p.is_active && p.status !== 'complete' && p.status !== 'cancelled' && p.status !== 'stopped' && p.status !== 'extracting' && p.status !== 'checking') {
                    backgroundRunning = false;
                    clearInterval(window.ablrPoll);
                    window.ablrPoll = null;
                    $('.ablr-background-status').hide();
                    $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
                }
            }).fail(function() {
                pollErrorCount++;
                if (pollErrorCount >= maxPollErrors) {
                    // Too many consecutive errors - stop polling to prevent hammering server
                    clearInterval(window.ablrPoll);
                    window.ablrPoll = null;
                    backgroundRunning = false;
                    $('.ablr-background-status').hide();
                    $('.ablr-resume-buttons').show();
                    $('#ablr-scan-btn').prop('disabled', false).removeClass('disabled').text(ablrData.strings.startScan);
                }
            });
        }, 3000);
    }

    // ── Counts ──
    function updateCounts() {
        $.post(ablrData.ajaxUrl, { action: 'ablr_get_progress', nonce: ablrData.nonce }, function(r) {
            if (r.success && r.data.counts) {
                updateCountsFromData(r.data.counts);
            }
        });
    }

    function updateCountsFromData(counts) {
        $('#stat-total').text(counts.total || 0);
        $('#stat-ok').text(counts.ok || 0);
        $('#stat-broken').text(counts.broken || 0);
        $('#stat-fixed').text(counts.fixed || 0);
        $('#stat-uncheckable').text(counts.uncheckable || 0);
        $('#stat-whitelisted').text(counts.whitelisted || 0);

        // Update link type switcher counts.
        var extCount = parseInt(counts.broken_external, 10) || 0;
        var intCount = parseInt(counts.broken_internal, 10) || 0;

        $('#ablr-count-external').text(extCount > 0 ? '(' + extCount + ')' : '');
        $('#ablr-count-internal').text(intCount > 0 ? '(' + intCount + ')' : '');

        // Update progress stats panel.
        $('#prog-total').text(counts.total || 0);
        $('#prog-internal').text(counts.total_internal || 0);
        $('#prog-external').text(counts.total_external || 0);
        $('#prog-checked-int').text(counts.checked_internal || 0);
        $('#prog-checked-ext').text(counts.checked_external || 0);
        $('#prog-broken-int').text(counts.broken_internal || 0);
        $('#prog-broken-ext').text(counts.broken_external || 0);
        $('#prog-fixed-int').text(counts.fixed_internal || 0);
        $('#prog-fixed-ext').text(counts.fixed_external || 0);
        $('#prog-uncheckable-int').text(counts.uncheckable_internal || 0);
        $('#prog-uncheckable-ext').text(counts.uncheckable_external || 0);
        $('#prog-whitelisted-int').text(counts.whitelisted_internal || 0);
        $('#prog-whitelisted-ext').text(counts.whitelisted_external || 0);
    }

    // ── Links table ──
    var linksPerPage = 30;
    var linksInternal = '';

    function loadLinks(page) {
        page = page || linksPage;
        linksPage = page;

        var perPage = parseInt($('#ablr-per-page').val(), 10);
        if (isNaN(perPage) || perPage < 0) perPage = 30;
        linksPerPage = perPage;

        // Show loading spinner immediately.
        var $body = $('#ablr-links-body');
        $body.html('<tr><td colspan="7"><div class="ablr-spinner"></div></td></tr>');

        var data = {
            action: 'ablr_get_links',
            nonce: ablrData.nonce,
            paged: page,
            status: $('#ablr-filter-status').val(),
            search: $('#ablr-search').val(),
            per_page: perPage,
            is_internal: linksInternal
        };

        $.get(ablrData.ajaxUrl, data, function(r) {
            if (!r.success) return;

            var items = r.data.items;
            $body.empty();

            // Update total count.
            var totalText = r.data.total + ' ' + ablrData.strings.linksLabel;
            $('#ablr-links-total').text(totalText);

            // Update stats from embedded counts (avoids separate AJAX call).
            if (r.data.counts) {
                updateCountsFromData(r.data.counts);
            }

            if (!items.length) {
                $body.append('<tr><td colspan="7">' + ablrData.strings.noLinks + '</td></tr>');
                $('#ablr-links-pagination').empty();
                return;
            }

            $.each(items, function(i, link) {
                $body.append(buildLinkRow(link));
            });

            if (r.data.pages > 1) {
                renderPagination('#ablr-links-pagination', r.data.pages, page, function(p) {
                    loadLinks(p);
                });
            } else {
                $('#ablr-links-pagination').empty();
            }
        });
    }

    function buildLinkRow(link) {
        var statusBadge = '<span class="ablr-badge ablr-badge-' + esc(link.status) + '">' + esc(link.status) + '</span>';
        if (link.action_taken && link.action_taken !== 'none' && link.action_taken !== 'whitelisted') {
            statusBadge += ' <span class="ablr-badge ablr-badge-fixed">fixed</span>';
        }
        // Internal link indicator
        if (link.is_internal && link.is_internal !== '0' && link.is_internal !== 0) {
            statusBadge += ' <span class="ablr-badge ablr-badge-internal">internal</span>';
        }

        var reasonLabel = '';
        if (link.fail_reason) {
            var reasons = {
                'http_400': 'HTTP 400', 'http_401': 'HTTP 401', 'http_403': 'HTTP 403',
                'http_404': 'HTTP 404', 'http_410': 'HTTP 410', 'http_500': 'HTTP 500',
                'http_502': 'HTTP 502', 'http_503': 'HTTP 503',
                'timeout': 'Timeout', 'dns_failure': 'DNS Failure',
                'connection_refused': 'Conn. Refused', 'ssl_error': 'SSL Error',
                'connection_reset': 'Conn. Reset', 'connection_error': 'Conn. Error',
                'redirect_different_domain': 'Redirect → diff. domain',
                'redirect_to_root': 'Redirect → root',
                'parking': 'Domain parking', 'content_type': 'Content-Type mismatch',
                'soft_404': 'Soft 404'
            };
            reasonLabel = '<span class="ablr-reason">' + (reasons[link.fail_reason] || esc(link.fail_reason)) + '</span>';
            if (link.http_code) {
                reasonLabel += '<br><span class="ablr-reason">HTTP ' + link.http_code + '</span>';
            }
        }

        var urlDisplay = '<div class="ablr-url"><a href="' + esc(link.url) + '" target="_blank" rel="noopener">' +
            truncate(link.url, 60) + '</a></div>';
        if (link.anchor_text) {
            urlDisplay += '<span class="ablr-anchor">&#x1f517; ' + esc(truncate(link.anchor_text, 50)) + '</span>';
        }
        if (link.redirect_url) {
            urlDisplay += '<span class="ablr-redirect">&#x27a1; <a href="' + esc(link.redirect_url) + '" target="_blank" rel="noopener" class="ablr-redirect-link">' + esc(truncate(link.redirect_url, 50)) + '</a></span>';
        }

        var sourceHtml = '';
        if (link.edit_url) {
            sourceHtml = '<div class="ablr-source-cell">' +
                '<a href="' + esc(link.edit_url) + '" target="_blank" class="ablr-edit-source-btn" title="' + (ablrData.strings.editInWp || 'Edit in WordPress') + '">' +
                '<span class="dashicons dashicons-edit"></span> ' + (ablrData.strings.editSource || 'Edit Source') +
                '</a>' +
                '<div class="ablr-source-meta">' +
                '<span class="ablr-source-title">' + esc(link.source_title || '') + '</span>' +
                '<span class="ablr-source-type-badge">' + esc(link.source_type) + '</span>' +
                '</div>';
            if (link.permalink) {
                sourceHtml += '<a href="' + esc(link.permalink) + '" target="_blank" class="ablr-view-page-link" title="' + (ablrData.strings.viewOnSite || 'View on site') + '">' +
                    '<span class="dashicons dashicons-external"></span> ' + (ablrData.strings.viewPage || 'View Page') + '</a>';
            }
            sourceHtml += '</div>';
        } else {
            sourceHtml = '<div class="ablr-source-cell"><span class="ablr-source-type-badge">' + esc(link.source_type) + ' #' + link.source_id + '</span></div>';
        }

        var isInternal = link.is_internal && link.is_internal !== '0' && link.is_internal !== 0;

        // Web Archive column: hidden entirely when skip_wayback is active.
        // For both internal and external broken links, show Web Archive search link.
        var waybackHtml = '';
        if (!isSkipWayback) {
            if (link.wayback_url) {
                waybackHtml = '<a href="' + esc(link.wayback_url) + '" target="_blank" rel="noopener" class="ablr-wayback-link" title="' + ablrData.strings.waybackOpen + '">&#x1f4c4; ' + ablrData.strings.waybackView + '</a>';
            } else if (link.wayback_available) {
                // Has availability flag but no specific URL — use post date or fallback timestamp.
                var wbTs = link.post_date ? getTimestampFromDate(link.post_date) : getFallbackTimestamp();
                var wbSearchUrl = 'https://web.archive.org/web/' + wbTs + '/' + link.url;
                waybackHtml = '<a href="' + esc(wbSearchUrl) + '" target="_blank" rel="noopener" class="ablr-wayback-link" title="' + ablrData.strings.waybackOpen + '">&#x1f4c4; ' + ablrData.strings.waybackView + '</a>';
            } else {
                // Not available or not checked — link to search with timestamp so user can verify.
                var wbTs2 = link.post_date ? getTimestampFromDate(link.post_date) : getFallbackTimestamp();
                var wbCheckUrl = 'https://web.archive.org/web/' + wbTs2 + '/' + link.url;
                waybackHtml = '<a href="' + esc(wbCheckUrl) + '" target="_blank" rel="noopener" class="ablr-wayback-link-none" title="' + ablrData.strings.waybackCheck + '">&#x1f50d; ' + ablrData.strings.waybackSearch + '</a>';
            }
        }

        var actionsHtml = '<div class="ablr-actions-compact">';
        if (link.status === 'broken' && (!link.action_taken || link.action_taken === 'none')) {
            // Web Archive button: show for external links with wayback data, OR for internal 404s (can check Wayback on demand).
            if (!isSkipWayback && ((link.wayback_available || link.wayback_url) || isInternal)) {
                actionsHtml += '<button class="ablr-action-btn ablr-action-archive ablr-fix-btn" data-id="' + link.id + '" data-action="replace_wayback" title="' + (ablrData.strings.useArchive || 'Replace with archived version') + '"><span class="dashicons dashicons-backup"></span></button>';
            }
            actionsHtml += '<button class="ablr-action-btn ablr-action-keep ablr-fix-btn" data-id="' + link.id + '" data-action="remove_link" title="' + (ablrData.strings.keepText || 'Remove link, keep text') + '"><span class="dashicons dashicons-editor-unlink"></span></button>';
            actionsHtml += '<button class="ablr-action-btn ablr-action-delete ablr-fix-btn" data-id="' + link.id + '" data-action="remove_all" title="' + (ablrData.strings.deleteAll || 'Delete link and text') + '"><span class="dashicons dashicons-trash"></span></button>';
            actionsHtml += '<button class="ablr-action-btn ablr-action-replace ablr-fix-btn" data-id="' + link.id + '" data-action="replace_custom" title="' + (ablrData.strings.replaceUrl || 'Replace with custom URL') + '"><span class="dashicons dashicons-admin-links"></span></button>';
            actionsHtml += '<button class="ablr-action-btn ablr-action-skip ablr-whitelist-btn" data-id="' + link.id + '" title="' + (ablrData.strings.skipLink || 'Skip this link') + '"><span class="dashicons dashicons-hidden"></span></button>';
        } else if (link.status === 'uncheckable' && (!link.action_taken || link.action_taken === 'none')) {
            actionsHtml += '<button class="ablr-action-btn ablr-action-keep ablr-fix-btn" data-id="' + link.id + '" data-action="remove_link" title="' + (ablrData.strings.keepText || 'Remove link, keep text') + '"><span class="dashicons dashicons-editor-unlink"></span></button>';
            actionsHtml += '<button class="ablr-action-btn ablr-action-delete ablr-fix-btn" data-id="' + link.id + '" data-action="remove_all" title="' + (ablrData.strings.deleteAll || 'Delete link and text') + '"><span class="dashicons dashicons-trash"></span></button>';
            actionsHtml += '<button class="ablr-action-btn ablr-action-replace ablr-fix-btn" data-id="' + link.id + '" data-action="replace_custom" title="' + (ablrData.strings.replaceUrl || 'Replace with custom URL') + '"><span class="dashicons dashicons-admin-links"></span></button>';
            actionsHtml += '<button class="ablr-action-btn ablr-action-skip ablr-whitelist-btn" data-id="' + link.id + '" title="' + (ablrData.strings.skipLink || 'Skip this link') + '"><span class="dashicons dashicons-hidden"></span></button>';
        } else if ((link.status === 'broken' || link.status === 'uncheckable') && link.action_taken && link.action_taken !== 'none') {
            actionsHtml += '<span class="ablr-fixed-badge">✓ ' + esc(link.action_taken.replace(/_/g, ' ')) + '</span>';
            if (!link.is_auto_fixed || link.is_auto_fixed === '0') {
                actionsHtml += '<button class="ablr-action-btn ablr-action-undo ablr-undo-btn" data-id="' + link.id + '" title="' + (ablrData.strings.undoAction || 'Undo') + '"><span class="dashicons dashicons-undo"></span></button>';
            }
        } else if (link.status === 'whitelisted') {
            actionsHtml += '<button class="ablr-action-btn ablr-action-recheck ablr-unwhitelist-btn" data-id="' + link.id + '" title="' + (ablrData.strings.recheckLink || 'Recheck') + '"><span class="dashicons dashicons-update"></span></button>';
        }
        actionsHtml += '</div>';

        if ((link.status === 'broken' || link.status === 'uncheckable') && (!link.action_taken || link.action_taken === 'none')) {
            actionsHtml += '<div class="ablr-custom-url-input" data-id="' + link.id + '"><input type="url" placeholder="https://...">' +
                '<button class="button ablr-apply-custom" data-id="' + link.id + '">OK</button></div>';
        }

        var waybackTd = isSkipWayback ? '' : '<td>' + waybackHtml + '</td>';

        return '<tr>' +
            '<td class="check-column"><input type="checkbox" class="ablr-link-cb" value="' + link.id + '"></td>' +
            '<td>' + urlDisplay + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td>' + reasonLabel + '</td>' +
            '<td>' + sourceHtml + '</td>' +
            waybackTd +
            '<td>' + actionsHtml + '</td></tr>';
    }

    // ── Link Actions ──
    function initLinkActions() {
        $(document).on('click', '.ablr-fix-btn', function() {
            var id = $(this).data('id');
            var action = $(this).data('action');

            if (action === 'replace_custom') {
                var $input = $('.ablr-custom-url-input[data-id="' + id + '"]');
                $input.toggle();
                return;
            }

            $.post(ablrData.ajaxUrl, {
                action: 'ablr_fix_link',
                nonce: ablrData.nonce,
                link_id: id,
                fix_action: action
            }, handleFixResponse);
        });

        $(document).on('click', '.ablr-apply-custom', function() {
            var id = $(this).data('id');
            var url = $(this).siblings('input').val();
            if (!url) {
                alert(ablrData.strings.enterCustomUrl);
                return;
            }

            $.post(ablrData.ajaxUrl, {
                action: 'ablr_fix_link',
                nonce: ablrData.nonce,
                link_id: id,
                fix_action: 'replace_custom',
                custom_url: url
            }, handleFixResponse);
        });

        $(document).on('click', '.ablr-whitelist-btn', function() {
            var id = $(this).data('id');
            $.post(ablrData.ajaxUrl, {
                action: 'ablr_whitelist_link',
                nonce: ablrData.nonce,
                link_id: id
            }, function(r) { if (r.success) { loadLinks(); updateCounts(); } });
        });

        $(document).on('click', '.ablr-unwhitelist-btn', function() {
            var id = $(this).data('id');
            $.post(ablrData.ajaxUrl, {
                action: 'ablr_unwhitelist_link',
                nonce: ablrData.nonce,
                link_id: id
            }, function(r) { if (r.success) { loadLinks(); updateCounts(); } });
        });

        $(document).on('click', '.ablr-undo-btn', function() {
            var id = $(this).data('id');
            $.post(ablrData.ajaxUrl, {
                action: 'ablr_bulk_action',
                nonce: ablrData.nonce,
                link_ids: [id],
                bulk_action: 'undo'
            }, function(r) { if (r.success) { loadLinks(); updateCounts(); } });
        });

        $('#ablr-select-all').on('change', function() {
            $('.ablr-link-cb').prop('checked', $(this).prop('checked'));
        });

        $('#ablr-bulk-apply').on('click', function() {
            var action = $('#ablr-bulk-action').val();
            if (!action) return;

            var ids = [];
            $('.ablr-link-cb:checked').each(function() { ids.push($(this).val()); });
            if (!ids.length) { alert(ablrData.strings.noLinksSelected); return; }

            $.post(ablrData.ajaxUrl, {
                action: 'ablr_bulk_action',
                nonce: ablrData.nonce,
                link_ids: ids,
                bulk_action: action
            }, function(r) { if (r.success) { loadLinks(); updateCounts(); } });
        });

        // Instant reload when status filter changes.
        $('#ablr-filter-status').on('change', function() {
            loadLinks(1);
        });

        $('#ablr-filter-apply').on('click', function() {
            linksInternal = '';
            $('.ablr-switch-btn').removeClass('active');
            $('.ablr-switch-btn[data-filter=""]').addClass('active');
            $('#ablr-internal-notice').remove();
            loadLinks(1);
        });
        $('#ablr-search').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                linksInternal = '';
                $('.ablr-switch-btn').removeClass('active');
                $('.ablr-switch-btn[data-filter=""]').addClass('active');
                $('#ablr-internal-notice').remove();
                loadLinks(1);
            }
        });

        // Link type switcher (All / External / Internal 404).
        $('.ablr-switch-btn').on('click', function() {
            var $btn = $(this);
            var filter = $btn.data('filter');

            // Update active state.
            $('.ablr-switch-btn').removeClass('active');
            $btn.addClass('active');

            // Remove any previous internal notice.
            $('#ablr-internal-notice').remove();

            if (filter === 'internal') {
                linksInternal = '1';
                $('#ablr-filter-status').val('broken');
                // Show hint if internal scanning not enabled.
                if (!ablrData.settings.scan_internal_links || ablrData.settings.scan_internal_links === '0') {
                    var $notice = $('<div id="ablr-internal-notice" class="ablr-notice ablr-notice-info">' +
                        ablrData.strings.internalNotEnabled + '</div>');
                    $('.ablr-links-toolbar-bottom').after($notice);
                }
            } else if (filter === 'external') {
                linksInternal = '0';
                $('#ablr-filter-status').val('broken');
            } else {
                // "All" — reset filters.
                linksInternal = '';
                $('#ablr-filter-status').val('');
            }
            loadLinks(1);
        });

        // Per page selector.
        $('#ablr-per-page').on('change', function() {
            loadLinks(1);
        });
    }

    function handleFixResponse(r) {
        if (r.success) {
            loadLinks();
            updateCounts();
        } else {
            alert(r.data || 'Error applying action.');
        }
    }

    // ── Copy to clipboard ──
    $(document).on('click', '.ablr-copyable-url', function() {
        var url = $(this).data('url');
        var $el = $(this);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url);
        } else {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }

        var $tip = $('<span class="ablr-copied-tooltip">' + ablrData.strings.copied + '</span>');
        $el.css('position', 'relative').append($tip);
        setTimeout(function() { $tip.fadeOut(200, function() { $(this).remove(); }); }, 1000);
    });

    // ── Pagination helper ──
    function renderPagination(selector, totalPages, currentPage, callback) {
        var $el = $(selector);
        var html = '<div class="ablr-pagination-wrapper">';

        html += '<span class="ablr-pagination-info">Page ' + currentPage + ' of ' + totalPages + '</span>';

        if (currentPage > 1) {
            html += '<button class="button ablr-page-first" title="First page">«</button> ';
            html += '<button class="button ablr-page-prev" title="Previous page">‹</button> ';
        }

        var maxVisible = 15;
        var startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        var endPage = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        if (startPage > 1) html += '<span class="ablr-pagination-ellipsis">...</span> ';

        for (var i = startPage; i <= endPage; i++) {
            var ac = i === currentPage ? ' active' : '';
            html += '<button class="button ablr-page-btn' + ac + '" data-page="' + i + '">' + i + '</button> ';
        }

        if (endPage < totalPages) html += '<span class="ablr-pagination-ellipsis">...</span> ';

        if (currentPage < totalPages) {
            html += '<button class="button ablr-page-next" title="Next page">›</button> ';
            html += '<button class="button ablr-page-last" title="Last page">»</button>';
        }

        html += '</div>';
        $el.html(html);

        $el.find('.ablr-page-btn').on('click', function() { callback($(this).data('page')); });
        $el.find('.ablr-page-prev').on('click', function() { if (currentPage > 1) callback(currentPage - 1); });
        $el.find('.ablr-page-next').on('click', function() { if (currentPage < totalPages) callback(currentPage + 1); });
        $el.find('.ablr-page-first').on('click', function() { callback(1); });
        $el.find('.ablr-page-last').on('click', function() { callback(totalPages); });
    }

    // ── Helpers ──
    function esc(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    /**
     * Convert a date string (Y-m-d H:i:s) to Wayback Machine timestamp format.
     *
     * @param {string} dateStr Date in WordPress format (e.g., "2020-05-15 10:30:00").
     * @return {string} Timestamp in Wayback format (YYYYMMDDHHmmss).
     */
    function getTimestampFromDate(dateStr) {
        if (!dateStr) return getFallbackTimestamp();
        
        var d = new Date(dateStr.replace(' ', 'T')); // Convert to ISO format for parsing
        if (isNaN(d.getTime())) return getFallbackTimestamp();
        
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        var h = ('0' + d.getHours()).slice(-2);
        var min = ('0' + d.getMinutes()).slice(-2);
        var s = ('0' + d.getSeconds()).slice(-2);
        return '' + y + m + day + h + min + s;
    }

    /**
     * Generate a Wayback Machine timestamp for 4 years ago.
     *
     * Format: YYYYMMDDHHmmss
     * The Wayback Machine will automatically redirect to the closest
     * available snapshot for any given timestamp.
     *
     * @return {string} Timestamp in Wayback format.
     */
    function getFallbackTimestamp() {
        var d = new Date();
        d.setFullYear(d.getFullYear() - 4);
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        var h = ('0' + d.getHours()).slice(-2);
        var min = ('0' + d.getMinutes()).slice(-2);
        var s = ('0' + d.getSeconds()).slice(-2);
        return '' + y + m + day + h + min + s;
    }

    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    function showNotice(message, type) {
        type = type || 'info';
        var $notice = $('<div class="ablr-notice ablr-notice-' + type + '">' + esc(message) + '</div>');
        $('.ablr-admin-wrap h1').after($notice);
        setTimeout(function() { $notice.fadeOut(function() { $notice.remove(); }); }, 3000);
    }

})(jQuery);
