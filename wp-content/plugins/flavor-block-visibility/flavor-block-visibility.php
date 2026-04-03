<?php
/**
 * Plugin Name: Flavor Block Visibility
 * Description: Adds responsive visibility controls (Hide on Desktop / Tablet / Mobile) to all Gutenberg blocks in the Advanced panel.
 * Version: 1.0.0
 * Author: Vlad Zelinskyi
 * Author URI: https://www.spacenerd.space/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flavor-block-visibility
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FLBVIS_VERSION', '1.0.0' );
define( 'FLBVIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ============================================
// Default breakpoints
// ============================================

/**
 * Get the default breakpoint values.
 *
 * @return array Default breakpoints.
 */
function flbvis_get_default_breakpoints() {
    return array(
        'mobile_max'  => 767,
        'tablet_min'  => 768,
        'tablet_max'  => 1024,
        'desktop_min' => 1025,
    );
}

/**
 * Get the current breakpoint values (saved or defaults).
 *
 * @return array Current breakpoints.
 */
function flbvis_get_breakpoints() {
    $defaults = flbvis_get_default_breakpoints();
    $saved    = get_option( 'flbvis_breakpoints', array() );

    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    return wp_parse_args( $saved, $defaults );
}

// ============================================
// Register block attributes
// ============================================

/**
 * Register responsive visibility attributes for all blocks.
 */
function flbvis_register_block_attributes() {
    $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

    foreach ( $registered_blocks as $block ) {
        $block->attributes['flbvisHideOnDesktop'] = array(
            'type'    => 'boolean',
            'default' => false,
        );
        $block->attributes['flbvisHideOnTablet'] = array(
            'type'    => 'boolean',
            'default' => false,
        );
        $block->attributes['flbvisHideOnMobile'] = array(
            'type'    => 'boolean',
            'default' => false,
        );
    }
}
add_action( 'init', 'flbvis_register_block_attributes', 100 );

// ============================================
// Editor assets
// ============================================

/**
 * Enqueue editor scripts and styles.
 */
function flbvis_enqueue_editor_assets() {
    wp_enqueue_script(
        'flbvis-editor-script',
        FLBVIS_PLUGIN_URL . 'src/editor.js',
        array(
            'wp-blocks',
            'wp-i18n',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-compose',
            'wp-hooks',
        ),
        FLBVIS_VERSION,
        true
    );

    wp_set_script_translations( 'flbvis-editor-script', 'flavor-block-visibility' );

    $bp = flbvis_get_breakpoints();
    wp_localize_script( 'flbvis-editor-script', 'flbvisSettings', array(
        'breakpoints' => array(
            'mobile_max'  => absint( $bp['mobile_max'] ),
            'tablet_min'  => absint( $bp['tablet_min'] ),
            'tablet_max'  => absint( $bp['tablet_max'] ),
            'desktop_min' => absint( $bp['desktop_min'] ),
        ),
    ) );

    wp_enqueue_style(
        'flbvis-editor-style',
        FLBVIS_PLUGIN_URL . 'src/editor.css',
        array(),
        FLBVIS_VERSION
    );
}
add_action( 'enqueue_block_editor_assets', 'flbvis_enqueue_editor_assets' );

// ============================================
// Frontend: conditional CSS loading
// ============================================

/**
 * Conditionally enqueue frontend styles only when needed.
 */
function flbvis_maybe_enqueue_frontend_styles() {
    if ( flbvis_current_content_has_hidden_blocks() ) {
        flbvis_enqueue_inline_styles();
    }
}
add_action( 'wp_enqueue_scripts', 'flbvis_maybe_enqueue_frontend_styles' );

/**
 * Check if the current page content uses any responsive visibility attributes.
 *
 * @return bool True if hidden blocks are found.
 */
function flbvis_current_content_has_hidden_blocks() {
    if ( is_singular() ) {
        global $post;
        if ( $post && ! empty( $post->post_content ) ) {
            if ( preg_match( '/"flbvisHideOn(Desktop|Tablet|Mobile)"\s*:\s*true/', $post->post_content ) ) {
                return true;
            }
        }
        return false;
    }

    global $wp_query;
    if ( $wp_query && ! empty( $wp_query->posts ) ) {
        foreach ( $wp_query->posts as $queried_post ) {
            if ( ! empty( $queried_post->post_content ) &&
                 preg_match( '/"flbvisHideOn(Desktop|Tablet|Mobile)"\s*:\s*true/', $queried_post->post_content ) ) {
                return true;
            }
        }
    }

    if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
        return true;
    }

    return false;
}

/**
 * Output inline CSS with current breakpoint values.
 */
function flbvis_enqueue_inline_styles() {
    static $enqueued = false;
    if ( $enqueued ) {
        return;
    }
    $enqueued = true;

    $bp = flbvis_get_breakpoints();

    $css = sprintf(
        '@media(min-width:%dpx){.flbvis-hide-desktop{display:none!important}}' .
        '@media(min-width:%dpx) and (max-width:%dpx){.flbvis-hide-tablet{display:none!important}}' .
        '@media(max-width:%dpx){.flbvis-hide-mobile{display:none!important}}',
        absint( $bp['desktop_min'] ),
        absint( $bp['tablet_min'] ),
        absint( $bp['tablet_max'] ),
        absint( $bp['mobile_max'] )
    );

    wp_register_style( 'flbvis-frontend-style', false, array(), FLBVIS_VERSION );
    wp_enqueue_style( 'flbvis-frontend-style' );
    wp_add_inline_style( 'flbvis-frontend-style', $css );
}

// ============================================
// Render: add CSS classes to blocks
// ============================================

/**
 * Add responsive visibility CSS classes to blocks on the frontend.
 *
 * @param string $block_content The block HTML content.
 * @param array  $block         The parsed block data.
 * @return string Modified block content.
 */
function flbvis_render_block( $block_content, $block ) {
    if ( empty( $block_content ) ) {
        return $block_content;
    }

    $attrs   = isset( $block['attrs'] ) ? $block['attrs'] : array();
    $classes = array();

    if ( ! empty( $attrs['flbvisHideOnDesktop'] ) ) {
        $classes[] = 'flbvis-hide-desktop';
    }
    if ( ! empty( $attrs['flbvisHideOnTablet'] ) ) {
        $classes[] = 'flbvis-hide-tablet';
    }
    if ( ! empty( $attrs['flbvisHideOnMobile'] ) ) {
        $classes[] = 'flbvis-hide-mobile';
    }

    if ( empty( $classes ) ) {
        return $block_content;
    }

    // Ensure CSS is loaded (covers edge cases: widgets, template parts, etc.)
    flbvis_enqueue_inline_styles();

    // Use WP_HTML_Tag_Processor (WP 6.2+).
    if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
        $processor = new WP_HTML_Tag_Processor( $block_content );
        if ( $processor->next_tag() ) {
            foreach ( $classes as $cls ) {
                $processor->add_class( $cls );
            }
            return $processor->get_updated_html();
        }
    }

    // Fallback for older WordPress versions.
    $extra_class = implode( ' ', $classes );

    // Check if the first tag already has a class attribute.
    if ( preg_match( '/^<[a-z][a-z0-9]*\s[^>]*class\s*=\s*["\']([^"\']*)["\']/', $block_content, $matches ) ) {
        // Append to existing class.
        $block_content = preg_replace(
            '/^(<[a-z][a-z0-9]*\s[^>]*class\s*=\s*["\'])/',
            '$1' . esc_attr( $extra_class ) . ' ',
            $block_content,
            1
        );
    } else {
        // Add new class attribute.
        $block_content = preg_replace(
            '/^(<[a-z][a-z0-9]*)([\s>])/i',
            '$1 class="' . esc_attr( $extra_class ) . '"$2',
            $block_content,
            1
        );
    }

    return $block_content;
}
add_filter( 'render_block', 'flbvis_render_block', 10, 2 );

// ============================================
// Settings Page
// ============================================

/**
 * Register plugin settings.
 */
function flbvis_register_settings() {
    register_setting(
        'flbvis_settings_group',
        'flbvis_breakpoints',
        array(
            'type'              => 'object',
            'sanitize_callback' => 'flbvis_sanitize_breakpoints',
            'default'           => flbvis_get_default_breakpoints(),
        )
    );
}
add_action( 'admin_init', 'flbvis_register_settings' );

/**
 * Sanitize breakpoint values.
 *
 * @param mixed $input Raw input from the settings form.
 * @return array Sanitized breakpoints.
 */
function flbvis_sanitize_breakpoints( $input ) {
    $defaults  = flbvis_get_default_breakpoints();

    if ( ! is_array( $input ) ) {
        return $defaults;
    }

    $sanitized = array();

    foreach ( $defaults as $key => $default_val ) {
        $val = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : $default_val;
        $sanitized[ $key ] = min( 9999, $val );
    }

    // Ensure logical order: mobile_max < tablet_min <= tablet_max < desktop_min.
    if ( $sanitized['tablet_min'] <= $sanitized['mobile_max'] ) {
        $sanitized['tablet_min'] = $sanitized['mobile_max'] + 1;
    }
    if ( $sanitized['tablet_max'] < $sanitized['tablet_min'] ) {
        $sanitized['tablet_max'] = $sanitized['tablet_min'];
    }
    if ( $sanitized['desktop_min'] <= $sanitized['tablet_max'] ) {
        $sanitized['desktop_min'] = $sanitized['tablet_max'] + 1;
    }

    return $sanitized;
}

/**
 * Add settings page to the admin menu.
 */
function flbvis_add_settings_page() {
    add_options_page(
        esc_html__( 'Flavor Block Visibility', 'flavor-block-visibility' ),
        esc_html__( 'Block Visibility', 'flavor-block-visibility' ),
        'manage_options',
        'flbvis-settings',
        'flbvis_render_settings_page'
    );
}
add_action( 'admin_menu', 'flbvis_add_settings_page' );

/**
 * Enqueue admin styles for the settings page.
 *
 * @param string $hook_suffix The current admin page hook.
 */
function flbvis_enqueue_admin_assets( $hook_suffix ) {
    if ( 'settings_page_flbvis-settings' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_style(
        'flbvis-admin-style',
        FLBVIS_PLUGIN_URL . 'src/admin.css',
        array(),
        FLBVIS_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'flbvis_enqueue_admin_assets' );

/**
 * Render the settings page.
 */
function flbvis_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $bp       = flbvis_get_breakpoints();
    $defaults = flbvis_get_default_breakpoints();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Flavor Block Visibility', 'flavor-block-visibility' ); ?></h1>
        <p><?php esc_html_e( 'Configure the breakpoints used to determine device types. These values control when blocks are shown or hidden.', 'flavor-block-visibility' ); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields( 'flbvis_settings_group' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="flbvis_mobile_max">
                            <?php esc_html_e( 'Mobile max-width', 'flavor-block-visibility' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" id="flbvis_mobile_max" name="flbvis_breakpoints[mobile_max]"
                               value="<?php echo esc_attr( $bp['mobile_max'] ); ?>"
                               min="0" max="9999" step="1" class="small-text" /> px
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %d: default mobile max-width in pixels */
                                esc_html__( 'Screens up to this width are considered mobile. Default: %d px', 'flavor-block-visibility' ),
                                absint( $defaults['mobile_max'] )
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="flbvis_tablet_min">
                            <?php esc_html_e( 'Tablet min-width', 'flavor-block-visibility' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" id="flbvis_tablet_min" name="flbvis_breakpoints[tablet_min]"
                               value="<?php echo esc_attr( $bp['tablet_min'] ); ?>"
                               min="0" max="9999" step="1" class="small-text" /> px
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %d: default tablet min-width in pixels */
                                esc_html__( 'Tablet range starts here. Default: %d px', 'flavor-block-visibility' ),
                                absint( $defaults['tablet_min'] )
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="flbvis_tablet_max">
                            <?php esc_html_e( 'Tablet max-width', 'flavor-block-visibility' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" id="flbvis_tablet_max" name="flbvis_breakpoints[tablet_max]"
                               value="<?php echo esc_attr( $bp['tablet_max'] ); ?>"
                               min="0" max="9999" step="1" class="small-text" /> px
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %d: default tablet max-width in pixels */
                                esc_html__( 'Tablet range ends here. Default: %d px', 'flavor-block-visibility' ),
                                absint( $defaults['tablet_max'] )
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="flbvis_desktop_min">
                            <?php esc_html_e( 'Desktop min-width', 'flavor-block-visibility' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" id="flbvis_desktop_min" name="flbvis_breakpoints[desktop_min]"
                               value="<?php echo esc_attr( $bp['desktop_min'] ); ?>"
                               min="0" max="9999" step="1" class="small-text" /> px
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %d: default desktop min-width in pixels */
                                esc_html__( 'Screens from this width and above are considered desktop. Default: %d px', 'flavor-block-visibility' ),
                                absint( $defaults['desktop_min'] )
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>

            <div class="flbvis-breakpoint-preview">
                <strong><?php esc_html_e( 'Current breakpoints:', 'flavor-block-visibility' ); ?></strong>
                <code>
                    <?php
                    printf(
                        /* translators: %1$d: mobile max, %2$d: tablet min, %3$d: tablet max, %4$d: desktop min */
                        esc_html__( 'Mobile: 0 – %1$dpx | Tablet: %2$dpx – %3$dpx | Desktop: %4$dpx+', 'flavor-block-visibility' ),
                        absint( $bp['mobile_max'] ),
                        absint( $bp['tablet_min'] ),
                        absint( $bp['tablet_max'] ),
                        absint( $bp['desktop_min'] )
                    );
                    ?>
                </code>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ============================================
// Settings link on Plugins page
// ============================================

/**
 * Add a Settings link to the plugin action links.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified links.
 */
function flbvis_plugin_action_links( $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( admin_url( 'options-general.php?page=flbvis-settings' ) ),
        esc_html__( 'Settings', 'flavor-block-visibility' )
    );
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'flbvis_plugin_action_links' );

// ============================================
// Cleanup on uninstall
// ============================================
register_uninstall_hook( __FILE__, 'flbvis_uninstall' );

/**
 * Clean up plugin data on uninstall.
 */
function flbvis_uninstall() {
    delete_option( 'flbvis_breakpoints' );
}
