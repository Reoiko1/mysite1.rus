<?php
/**
 * Custom header implementation
 *
 * @link https://codex.wordpress.org/Custom_Headers
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

function bakery_patisserie_shop_custom_header_setup() {
    register_default_headers( array(
        'default-image' => array(
            'url'           => get_template_directory_uri() . '/assets/images/sliderimage.png',
            'thumbnail_url' => get_template_directory_uri() . '/assets/images/sliderimage.png',
            'description'   => __( 'Default Header Image', 'bakery-patisserie-shop' ),
        ),
    ) );
}
add_action( 'after_setup_theme', 'bakery_patisserie_shop_custom_header_setup' );

/**
 * Styles the header image based on Customizer settings.
 */
function bakery_patisserie_shop_header_style() {
    $bakery_patisserie_shop_header_image = get_header_image() ? get_header_image() : get_template_directory_uri() . '/assets/images/sliderimage.png';

    $bakery_patisserie_shop_height     = get_theme_mod( 'bakery_patisserie_shop_header_image_height', 400 );
    $bakery_patisserie_shop_position   = get_theme_mod( 'bakery_patisserie_shop_header_background_position', 'center' );
    $bakery_patisserie_shop_attachment = get_theme_mod( 'bakery_patisserie_shop_header_background_attachment', 1 ) ? 'fixed' : 'scroll';

    $bakery_patisserie_shop_custom_css = "
        .header-img, .single-page-img, .external-div .box-image-page img, .external-div {
            background-image: url('" . esc_url( $bakery_patisserie_shop_header_image ) . "');
            background-size: cover;
            height: " . esc_attr( $bakery_patisserie_shop_height ) . "px;
            background-position: " . esc_attr( $bakery_patisserie_shop_position ) . ";
            background-attachment: " . esc_attr( $bakery_patisserie_shop_attachment ) . ";
        }

        @media (max-width: 1000px) {
            .header-img, .single-page-img, .external-div .box-image-page img,.external-div,.featured-image{
                height: 250px !important;
            }
            .box-text h2{
                font-size: 27px;
            }
        }
    ";

    wp_add_inline_style( 'bakery-patisserie-shop-style', $bakery_patisserie_shop_custom_css );
}
add_action( 'wp_enqueue_scripts', 'bakery_patisserie_shop_header_style' );

/**
 * Enqueue the main theme stylesheet.
 */
function bakery_patisserie_shop_enqueue_styles() {
    wp_enqueue_style( 'bakery-patisserie-shop-style', get_stylesheet_uri() );
}
add_action( 'wp_enqueue_scripts', 'bakery_patisserie_shop_enqueue_styles' );