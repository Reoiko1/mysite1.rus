<?php
/**
 * Bakery Patisserie Shop functions and definitions
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

function bakery_patisserie_shop_setup() {

	load_theme_textdomain( 'bakery-patisserie-shop', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'title-tag' );
	add_theme_support( "responsive-embeds" );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'bakery-patisserie-shop-featured-image', 2000, 1200, true );
	add_image_size( 'bakery-patisserie-shop-thumbnail-avatar', 100, 100, true );

	// Set the default content width.
	$GLOBALS['content_width'] = 525;

	// This theme uses wp_nav_menu() in two locations.
	register_nav_menus( array(
		'primary-menu'    => __( 'Primary Menu', 'bakery-patisserie-shop' ),
	) );

	// Add theme support for Custom Logo.
	add_theme_support( 'custom-logo', array(
		'width'       => 250,
		'height'      => 250,
		'flex-width'  => true,
    	'flex-height' => true,
	) );

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	add_theme_support( 'custom-background', array(
		'default-color' => 'ffffff'
	) );

	/*
	 * Enable support for Post Formats.
	 *
	 * See: https://codex.wordpress.org/Post_Formats
	 */
	add_theme_support( 'post-formats', array('image','video','gallery','audio',) );

	add_theme_support( 'html5', array('comment-form','comment-list','gallery','caption',) );

	add_theme_support( 'custom-header', apply_filters( 'bakery_patisserie_shop_custom_header_args', array(
        'default-text-color' => 'fff',
        'header-text'        => false,
        'width'              => 1600,
        'height'             => 400,
        'flex-width'         => true,
        'flex-height'        => true,
        'wp-head-callback'   => 'bakery_patisserie_shop_header_style',
        'default-image'      => get_template_directory_uri() . '/assets/images/sliderimage.png',
    ) ) );

	/**
	 * Implement the Custom Header feature.
	 */
	require get_parent_theme_file_path( '/inc/custom-header.php' );

}
add_action( 'after_setup_theme', 'bakery_patisserie_shop_setup' );

// Add function after setup:
function bakery_patisserie_shop_conditional_editor_styles() {

	add_editor_style( array( 'assets/css/editor-style.css', bakery_patisserie_shop_fonts_url() ) );

}
add_action( 'after_setup_theme', 'bakery_patisserie_shop_conditional_editor_styles', 11 );

/**
 * Register custom fonts.
 */
function bakery_patisserie_shop_fonts_url(){
	$bakery_patisserie_shop_font_url = '';
	$bakery_patisserie_shop_font_family = array();
	$bakery_patisserie_shop_font_family[] = 'Gochi Hand';
	$bakery_patisserie_shop_font_family[] = 'Satisfy';
	$bakery_patisserie_shop_font_family[] = 'League Spartan:wght@100..900';
	$bakery_patisserie_shop_font_family[] = 'Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900';
	$bakery_patisserie_shop_font_family[] = 'Manrope:wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Oxanium:wght@200;300;400;500;600;700;800';
	$bakery_patisserie_shop_font_family[] = 'Oswald:200,300,400,500,600,700';
	$bakery_patisserie_shop_font_family[] = 'Roboto Serif:wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Bad Script';
	$bakery_patisserie_shop_font_family[] = 'Bebas Neue';
	$bakery_patisserie_shop_font_family[] = 'Fjalla One';
	$bakery_patisserie_shop_font_family[] = 'PT Sans:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'PT Serif:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900';
	$bakery_patisserie_shop_font_family[] = 'Roboto Condensed:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Roboto+Flex:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Alex Brush';
	$bakery_patisserie_shop_font_family[] = 'Overpass:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Playball';
	$bakery_patisserie_shop_font_family[] = 'Alegreya:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Julius Sans One';
	$bakery_patisserie_shop_font_family[] = 'Arsenal:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Slabo 13px';
	$bakery_patisserie_shop_font_family[] = 'Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900';
	$bakery_patisserie_shop_font_family[] = 'Overpass Mono:wght@300;400;500;600;700';
	$bakery_patisserie_shop_font_family[] = 'Source Sans Pro:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700;1,900';
	$bakery_patisserie_shop_font_family[] = 'Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900';
	$bakery_patisserie_shop_font_family[] = 'Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700';
	$bakery_patisserie_shop_font_family[] = 'Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700';
	$bakery_patisserie_shop_font_family[] = 'Cabin:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700';
	$bakery_patisserie_shop_font_family[] = 'Arimo:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700';
	$bakery_patisserie_shop_font_family[] = 'Playfair Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Quicksand:wght@300;400;500;600;700';
	$bakery_patisserie_shop_font_family[] = 'Padauk:wght@400;700';
	$bakery_patisserie_shop_font_family[] = 'Mulish:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;0,1000;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900;1,1000';
	$bakery_patisserie_shop_font_family[] = 'Inconsolata:wght@200;300;400;500;600;700;800;900&family=Mulish:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;0,1000;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900;1,1000';
	$bakery_patisserie_shop_font_family[] = 'Bitter:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Mulish:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;0,1000;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900;1,1000';
	$bakery_patisserie_shop_font_family[] = 'Pacifico';
	$bakery_patisserie_shop_font_family[] = 'Indie Flower';
	$bakery_patisserie_shop_font_family[] = 'VT323';
	$bakery_patisserie_shop_font_family[] = 'Dosis:wght@200;300;400;500;600;700;800';
	$bakery_patisserie_shop_font_family[] = 'Frank Ruhl Libre:wght@300;400;500;700;900';
	$bakery_patisserie_shop_font_family[] = 'Fjalla One';
	$bakery_patisserie_shop_font_family[] = 'Figtree:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Oxygen:wght@300;400;700';
	$bakery_patisserie_shop_font_family[] = 'Arvo:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Noto Serif:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Lobster';
	$bakery_patisserie_shop_font_family[] = 'Crimson Text:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700';
	$bakery_patisserie_shop_font_family[] = 'Yanone Kaffeesatz:wght@200;300;400;500;600;700';
	$bakery_patisserie_shop_font_family[] = 'Anton';
	$bakery_patisserie_shop_font_family[] = 'Libre Baskerville:ital,wght@0,400;0,700;1,400';
	$bakery_patisserie_shop_font_family[] = 'Bree Serif';
	$bakery_patisserie_shop_font_family[] = 'Gloria Hallelujah';
	$bakery_patisserie_shop_font_family[] = 'Abril Fatface';
	$bakery_patisserie_shop_font_family[] = 'Varela Round';
	$bakery_patisserie_shop_font_family[] = 'Vampiro One';
	$bakery_patisserie_shop_font_family[] = 'Shadows Into Light';
	$bakery_patisserie_shop_font_family[] = 'Cuprum:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700';
	$bakery_patisserie_shop_font_family[] = 'Rokkitt:wght@100;200;300;400;500;600;700;800;900';
	$bakery_patisserie_shop_font_family[] = 'Vollkorn:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Francois One';
	$bakery_patisserie_shop_font_family[] = 'Orbitron:wght@400;500;600;700;800;900';
	$bakery_patisserie_shop_font_family[] = 'Patua One';
	$bakery_patisserie_shop_font_family[] = 'Acme';
	$bakery_patisserie_shop_font_family[] = 'Satisfy';
	$bakery_patisserie_shop_font_family[] = 'Josefin Slab:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700';
	$bakery_patisserie_shop_font_family[] = 'Quattrocento Sans:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Architects Daughter';
	$bakery_patisserie_shop_font_family[] = 'Russo One';
	$bakery_patisserie_shop_font_family[] = 'Monda:wght@400;700';
	$bakery_patisserie_shop_font_family[] = 'Righteous';
	$bakery_patisserie_shop_font_family[] = 'Lobster Two:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Hammersmith One';
	$bakery_patisserie_shop_font_family[] = 'Courgette';
	$bakery_patisserie_shop_font_family[] = 'Permanent Marke';
	$bakery_patisserie_shop_font_family[] = 'Cherry Swash:wght@400;700';
	$bakery_patisserie_shop_font_family[] = 'Cormorant Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700';
	$bakery_patisserie_shop_font_family[] = 'Poiret One';
	$bakery_patisserie_shop_font_family[] = 'BenchNine:wght@300;400;700';
	$bakery_patisserie_shop_font_family[] = 'Economica:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Handlee';
	$bakery_patisserie_shop_font_family[] = 'Cardo:ital,wght@0,400;0,700;1,400';
	$bakery_patisserie_shop_font_family[] = 'Alfa Slab One';
	$bakery_patisserie_shop_font_family[] = 'Averia Serif Libre:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Cookie';
	$bakery_patisserie_shop_font_family[] = 'Chewy';
	$bakery_patisserie_shop_font_family[] = 'Great Vibes';
	$bakery_patisserie_shop_font_family[] = 'Coming Soon';
	$bakery_patisserie_shop_font_family[] = 'Philosopher:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Days One';
	$bakery_patisserie_shop_font_family[] = 'Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Shrikhand';
	$bakery_patisserie_shop_font_family[] = 'Tangerine:wght@400;700';
	$bakery_patisserie_shop_font_family[] = 'IM Fell English SC';
	$bakery_patisserie_shop_font_family[] = 'Boogaloo';
	$bakery_patisserie_shop_font_family[] = 'Bangers';
	$bakery_patisserie_shop_font_family[] = 'Fredoka One';
	$bakery_patisserie_shop_font_family[] = 'Volkhov:ital,wght@0,400;0,700;1,400;1,700';
	$bakery_patisserie_shop_font_family[] = 'Shadows Into Light Two';
	$bakery_patisserie_shop_font_family[] = 'Marck Script';
	$bakery_patisserie_shop_font_family[] = 'Sacramento';
	$bakery_patisserie_shop_font_family[] = 'Unica One';
	$bakery_patisserie_shop_font_family[] = 'Dancing Script:wght@400;500;600;700';
	$bakery_patisserie_shop_font_family[] = 'Exo 2:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Archivo:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	$bakery_patisserie_shop_font_family[] = 'DM Serif Display:ital@0;1';
	$bakery_patisserie_shop_font_family[] = 'Open Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800';
	$bakery_patisserie_shop_font_family[] = 'Karla:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800';

	$bakery_patisserie_shop_query_args = array(
		'family'	=> rawurlencode(implode('|',$bakery_patisserie_shop_font_family)),
	);
	$bakery_patisserie_shop_font_url = add_query_arg($bakery_patisserie_shop_query_args,'//fonts.googleapis.com/css');
	return $bakery_patisserie_shop_font_url;
	$contents = wptt_get_webfont_url( esc_url_raw( $bakery_patisserie_shop_font_url ) );
}

/**
 * Register widget area.
 */
function bakery_patisserie_shop_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'bakery-patisserie-shop' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Add widgets here to appear in your sidebar on blog posts and archive pages.', 'bakery-patisserie-shop' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Page Sidebar', 'bakery-patisserie-shop' ),
		'id'            => 'sidebar-2',
		'description'   => __( 'Add widgets here to appear in your sidebar on pages.', 'bakery-patisserie-shop' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Sidebar 3', 'bakery-patisserie-shop' ),
		'id'            => 'sidebar-3',
		'description'   => __( 'Add widgets here to appear in your sidebar on blog posts and archive pages.', 'bakery-patisserie-shop' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer 1', 'bakery-patisserie-shop' ),
		'id'            => 'footer-1',
		'description'   => __( 'Add widgets here to appear in your footer.', 'bakery-patisserie-shop' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer 2', 'bakery-patisserie-shop' ),
		'id'            => 'footer-2',
		'description'   => __( 'Add widgets here to appear in your footer.', 'bakery-patisserie-shop' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer 3', 'bakery-patisserie-shop' ),
		'id'            => 'footer-3',
		'description'   => __( 'Add widgets here to appear in your footer.', 'bakery-patisserie-shop' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer 4', 'bakery-patisserie-shop' ),
		'id'            => 'footer-4',
		'description'   => __( 'Add widgets here to appear in your footer.', 'bakery-patisserie-shop' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'bakery_patisserie_shop_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function bakery_patisserie_shop_scripts() {
	// Add custom fonts, used in the main stylesheet.
	wp_enqueue_style( 'bakery-patisserie-shop-fonts', bakery_patisserie_shop_fonts_url(), array(), null );

	// owl
	wp_enqueue_style( 'owl-carousel-css', get_theme_file_uri( '/assets/css/owl.carousel.css' ) );

	// Bootstrap
	wp_enqueue_style( 'bootstrap-css', get_theme_file_uri( '/assets/css/bootstrap.css' ) );

	// Theme stylesheet.
	wp_enqueue_style( 'bakery-patisserie-shop-style', get_stylesheet_uri() );
	require get_parent_theme_file_path( '/tp-theme-color.php' );
	wp_add_inline_style( 'bakery-patisserie-shop-style',$bakery_patisserie_shop_tp_theme_css );
	wp_style_add_data('bakery-patisserie-shop-style', 'rtl', 'replace');
	require get_parent_theme_file_path( '/tp-body-width-layout.php' );
	wp_add_inline_style( 'bakery-patisserie-shop-style',$bakery_patisserie_shop_tp_theme_css );
	wp_style_add_data('bakery-patisserie-shop-style', 'rtl', 'replace');

	// Theme block stylesheet.
	wp_enqueue_style( 'bakery-patisserie-shop-block-style', get_theme_file_uri( '/assets/css/blocks.css' ), array( 'bakery-patisserie-shop-style' ), '1.0' );

	// Fontawesome
	wp_enqueue_style( 'fontawesome-css', get_theme_file_uri( '/assets/css/fontawesome-all.css' ) );
	

	wp_enqueue_script( 'bakery-patisserie-shop-custom-scripts', get_template_directory_uri() . '/assets/js/bakery-patisserie-shop-custom.js', array('jquery'), true );


	wp_enqueue_script( 'bootstrap-js', get_theme_file_uri( '/assets/js/bootstrap.js' ), array( 'jquery' ), true );

	wp_enqueue_script( 'owl-carousel-js', get_theme_file_uri( '/assets/js/owl.carousel.js' ), array( 'jquery' ), true );

	wp_enqueue_script( 'bakery-patisserie-shop-focus-nav', get_template_directory_uri() . '/assets/js/focus-nav.js', array('jquery'), true);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	$bakery_patisserie_shop_body_font_family = get_theme_mod('bakery_patisserie_shop_body_font_family', '');

	$bakery_patisserie_shop_heading_font_family = get_theme_mod('bakery_patisserie_shop_heading_font_family', '');

	$bakery_patisserie_shop_menu_font_family = get_theme_mod('bakery_patisserie_shop_menu_font_family', '');

	$bakery_patisserie_shop_tp_theme_css = '
		body, p.simplep, .more-btn a{
		    font-family: '.esc_html($bakery_patisserie_shop_body_font_family).';
		}
		h1,h2, h3, h4, h5, h6, .menubar,.logo h1, .logo p.site-title, p.simplep a, #main-slider p.slidertop-title, .more-btn a,.wc-block-checkout__actions_row .wc-block-components-checkout-place-order-button,.wc-block-cart__submit-container a,.woocommerce #respond input#submit, .woocommerce a.button, .woocommerce button.button, .woocommerce input.button,.woocommerce #respond input#submit.alt, .woocommerce a.button.alt, .woocommerce button.button.alt, .woocommerce input.button.alt, #theme-sidebar button[type="submit"],
#footer button[type="submit"]{
		    font-family: '.esc_html($bakery_patisserie_shop_heading_font_family).';
		}
	';
	wp_add_inline_style('bakery-patisserie-shop-style', $bakery_patisserie_shop_tp_theme_css);
}
add_action( 'wp_enqueue_scripts', 'bakery_patisserie_shop_scripts' );

/*radio button sanitization*/
function bakery_patisserie_shop_sanitize_choices( $input, $setting ) {
    global $wp_customize;
    $control = $wp_customize->get_control( $setting->id );
    if ( array_key_exists( $input, $control->choices ) ) {
        return $input;
    } else {
        return $setting->default;
    }
}

// Sanitize Sortable control.
function bakery_patisserie_shop_sanitize_sortable( $val, $setting ) {
	if ( is_string( $val ) || is_numeric( $val ) ) {
		return array(
			esc_attr( $val ),
		);
	}
	$sanitized_value = array();
	foreach ( $val as $item ) {
		if ( isset( $setting->manager->get_control( $setting->id )->choices[ $item ] ) ) {
			$sanitized_value[] = esc_attr( $item );
		}
	}
	return $sanitized_value;
}
/* Excerpt Limit Begin */
function bakery_patisserie_shop_excerpt_function($excerpt_count = 35) {
    $bakery_patisserie_shop_excerpt = get_the_excerpt();

    $BAKERY_PATISSERIE_SHOP_TEXT_excerpt = wp_strip_all_tags($bakery_patisserie_shop_excerpt);

    $bakery_patisserie_shop_excerpt_limit = esc_attr(get_theme_mod('bakery_patisserie_shop_excerpt_count', $excerpt_count));

    $bakery_patisserie_shop_theme_excerpt = implode(' ', array_slice(explode(' ', $BAKERY_PATISSERIE_SHOP_TEXT_excerpt), 0, $bakery_patisserie_shop_excerpt_limit));

    return $bakery_patisserie_shop_theme_excerpt;
}

// Change number or products per row to 3
add_filter('loop_shop_columns', 'bakery_patisserie_shop_loop_columns');
if (!function_exists('bakery_patisserie_shop_loop_columns')) {
	function bakery_patisserie_shop_loop_columns() {
		$columns = get_theme_mod( 'bakery_patisserie_shop_per_columns', 3 );
		return $columns;
	}
}

function bakery_patisserie_shop_sanitize_dropdown_pages( $page_id, $setting ) {
  // Ensure $input is an absolute integer.
  $page_id = absint( $page_id );
  // If $page_id is an ID of a published page, return it; otherwise, return the default.
  return ( 'publish' == get_post_status( $page_id ) ? $page_id : $setting->default );
}

// Category count 
function bakery_patisserie_shop_display_post_category_count() {
    $bakery_patisserie_shop_category = get_the_category();
    $bakery_patisserie_shop_category_count = ($bakery_patisserie_shop_category) ? count($bakery_patisserie_shop_category) : 0;
    $bakery_patisserie_shop_category_text = ($bakery_patisserie_shop_category_count === 1) ? 'category' : 'categories'; // Check for pluralization
    echo $bakery_patisserie_shop_category_count . ' ' . $bakery_patisserie_shop_category_text;
}

//post tag
function bakery_patisserie_shop_custom_tags_filter($bakery_patisserie_shop_tag_list) {
    // Replace the comma (,) with an empty string
    $bakery_patisserie_shop_tag_list = str_replace(', ', '', $bakery_patisserie_shop_tag_list);

    return $bakery_patisserie_shop_tag_list;
}
add_filter('the_tags', 'bakery_patisserie_shop_custom_tags_filter');

function bakery_patisserie_shop_custom_output_tags() {
    $bakery_patisserie_shop_tags = get_the_tags();

    if ($bakery_patisserie_shop_tags) {
        $bakery_patisserie_shop_tags_output = '<div class="post_tag">Tags: ';

        $bakery_patisserie_shop_first_tag = reset($bakery_patisserie_shop_tags);

        foreach ($bakery_patisserie_shop_tags as $tag) {
            $bakery_patisserie_shop_tags_output .= '<a href="' . esc_url(get_tag_link($tag)) . '" rel="tag" class="me-2">' . esc_html($tag->name) . '</a>';
            if ($tag !== $bakery_patisserie_shop_first_tag) {
                $bakery_patisserie_shop_tags_output .= ' ';
            }
        }

        $bakery_patisserie_shop_tags_output .= '</div>';

        echo $bakery_patisserie_shop_tags_output;
    }
}
//Change number of products that are displayed per page (shop page)
add_filter( 'loop_shop_per_page', 'bakery_patisserie_shop_per_page', 20 );
function bakery_patisserie_shop_per_page( $bakery_patisserie_shop_cols ) {
  	$bakery_patisserie_shop_cols = get_theme_mod( 'bakery_patisserie_shop_product_per_page', 9 );
	return $bakery_patisserie_shop_cols;
}

function bakery_patisserie_shop_sanitize_number_range( $number, $setting ) {

	// Ensure input is an absolute integer.
	$number = absint( $number );

	// Get the input attributes associated with the setting.
	$atts = $setting->manager->get_control( $setting->id )->input_attrs;

	// Get minimum number in the range.
	$min = ( isset( $atts['min'] ) ? $atts['min'] : $number );

	// Get maximum number in the range.
	$max = ( isset( $atts['max'] ) ? $atts['max'] : $number );

	// Get step.
	$step = ( isset( $atts['step'] ) ? $atts['step'] : 1 );

	// If the number is within the valid range, return it; otherwise, return the default
	return ( $min <= $number && $number <= $max && is_int( $number / $step ) ? $number : $setting->default );
}
function bakery_patisserie_shop_string_limit_words($string, $word_limit) {
    $string = wp_strip_all_tags($string); // Remove HTML tags
    $words = explode(' ', $string);
    if (count($words) > $word_limit) {
        return implode(' ', array_slice($words, 0, $word_limit)) . '...';
    }
    return implode(' ', $words);
}

function bakery_patisserie_shop_sanitize_checkbox( $input ) {
	// Boolean check
	return ( ( isset( $input ) && true == $input ) ? true : false );
}

function bakery_patisserie_shop_sanitize_number_absint( $number, $setting ) {
	// Ensure $number is an absolute integer (whole number, zero or greater).
	$number = absint( $number );

	// If the input is an absolute integer, return it; otherwise, return the default
	return ( $number ? $number : $setting->default );
}

function bakery_patisserie_shop_sanitize_select( $bakery_patisserie_shop_input, $bakery_patisserie_shop_setting ) {
  $bakery_patisserie_shop_input = sanitize_key( $bakery_patisserie_shop_input );
  $bakery_patisserie_shop_choices = $bakery_patisserie_shop_setting->manager->get_control( $bakery_patisserie_shop_setting->id )->choices;
  return ( array_key_exists( $bakery_patisserie_shop_input, $bakery_patisserie_shop_choices ) ? $bakery_patisserie_shop_input : $bakery_patisserie_shop_setting->default );
}

/**
 * Use front-page.php when Front page displays is set to a static page.
 */
function bakery_patisserie_shop_front_page_template( $template ) {
	return is_home() ? '' : $template;
}
add_filter( 'frontpage_template','bakery_patisserie_shop_front_page_template' );

// logo
function bakery_patisserie_shop_logo_width(){

	$bakery_patisserie_shop_logo_width   = get_theme_mod( 'bakery_patisserie_shop_logo_width', 80 );

	echo "<style type='text/css' media='all'>"; ?>
		img.custom-logo{
		    width: <?php echo absint( $bakery_patisserie_shop_logo_width ); ?>px;
		    max-width: 100%;
		}
	<?php echo "</style>";
}

add_action( 'wp_head', 'bakery_patisserie_shop_logo_width' );

function bakery_patisserie_shop_theme_setup() {

	define('BAKERY_PATISSERIE_SHOP_CREDIT',__('https://www.themespride.com/products/bakery-patisserie-shop','bakery-patisserie-shop') );
	if ( ! function_exists( 'bakery_patisserie_shop_credit' ) ) {
		function bakery_patisserie_shop_credit(){
			echo "<a href=".esc_url(BAKERY_PATISSERIE_SHOP_CREDIT)." target='_blank'>".esc_html__(get_theme_mod('bakery_patisserie_shop_footer_text',__('Bakery Patisserie Shop WordPress Theme','bakery-patisserie-shop')))."</a>";
		}
	}

	/**
	 * Custom template tags for this theme.
	 */
	require get_parent_theme_file_path( '/inc/template-tags.php' );

	/**
	 * Additional features to allow styling of the templates.
	 */
	require get_parent_theme_file_path( '/inc/template-functions.php' );

	/**
	 * Customizer additions.
	 */
	require get_parent_theme_file_path( '/inc/customizer.php' );

	/**
	 * Load Theme Web File
	 */
	require get_parent_theme_file_path('/inc/wptt-webfont-loader.php' );
	/**
	 * Load Theme Web File
	 */
	require get_parent_theme_file_path( '/inc/controls/customize-control-toggle.php' );
	/**
	 * load sortable file
	 */
	require get_parent_theme_file_path( '/inc/controls/sortable-control.php' );

	/**
	 * TGM Recommendation
	 */
	require get_parent_theme_file_path( '/inc/TGM/tgm.php' );

	/**
	 * About Theme Page
	 */
	require get_parent_theme_file_path( '/inc/about-theme.php' );

}
add_action( 'after_setup_theme', 'bakery_patisserie_shop_theme_setup' );


//Admin Enqueue for Admin
function bakery_patisserie_shop_admin_enqueue_scripts(){
	wp_enqueue_style('bakery-patisserie-shop-admin-style', get_template_directory_uri() . '/assets/css/admin.css');
	wp_register_script( 'bakery-patisserie-shop-admin-script', get_template_directory_uri() . '/assets/js/bakery-patisserie-shop-admin.js', array( 'jquery' ), '', true );

	wp_localize_script(
		'bakery-patisserie-shop-admin-script',
		'bakery_patisserie_shop',
		array(
			'admin_ajax'	=>	admin_url('admin-ajax.php'),
			'wpnonce'			=>	wp_create_nonce('bakery_patisserie_shop_dismissed_notice_nonce')
		)
	);
	wp_enqueue_script('bakery-patisserie-shop-admin-script');

    wp_localize_script( 'bakery-patisserie-shop-admin-script', 'bakery_patisserie_shop_ajax_object',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
    );
}
add_action( 'admin_enqueue_scripts', 'bakery_patisserie_shop_admin_enqueue_scripts' );

// get started
add_action( 'wp_ajax_bakery_patisserie_shop_dismissed_notice_handler', 'bakery_patisserie_shop_ajax_notice_handler' );

function bakery_patisserie_shop_ajax_notice_handler() {
	if (!wp_verify_nonce($_POST['wpnonce'], 'bakery_patisserie_shop_dismissed_notice_nonce')) {
		exit;
	}
    if ( isset( $_POST['type'] ) ) {
        $type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
        update_option( 'dismissed-' . $type, TRUE );
    }
}

function bakery_patisserie_shop_activation_notice() { 

	if ( ! get_option('dismissed-get_started', FALSE ) ) { ?>

    <div class="bakery-patisserie-shop-notice-wrapper updated notice notice-get-started-class is-dismissible" data-notice="get_started">
        <div class="bakery-patisserie-shop-getting-started-notice clearfix">
        	<div class="row-top">
	            <div class="bakery-patisserie-shop-theme-notice-content">
	                <h2 class="bakery-patisserie-shop-notice-h2">
	                    <?php
	                printf(
	                /* translators: 1: welcome page link starting html tag, 2: welcome page link ending html tag. */
	                    esc_html__( 'Install the Demo Import Plugin now to instantly set up your site like the live preview.', 'bakery-patisserie-shop' ), '<strong>'. wp_get_theme()->get('Name'). '</strong>' );
	                ?>
	                </h2>
	                <a class="bakery-patisserie-shop-btn-get-started button button-primary button-hero bakery-patisserie-shop-button-padding" href="<?php echo esc_url( admin_url( 'themes.php?page=bakery-patisserie-shop-about' )); ?>" ><?php esc_html_e( 'Get Started with Bakery Patisserie Shop Theme', 'bakery-patisserie-shop' ) ?></a>
	            </div>
	            <div class="image-box">
			    	<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/theme-notice.png' ); ?>" alt="<?php echo esc_attr__( 'Bakery Patisserie Shop', 'bakery-patisserie-shop' ); ?>" />
				</div>
	        </div>
        </div>
    </div>
<?php }

}
add_action( 'admin_notices', 'bakery_patisserie_shop_activation_notice' );

add_action('after_switch_theme', 'bakery_patisserie_shop_setup_options');
function bakery_patisserie_shop_setup_options () {
    update_option('dismissed-get_started', FALSE );
}

// Get Started Detail Notice - Dismiss permanently
function bakery_patisserie_shop_dismissed_get_started_detail_notice() {
    update_option( 'dismissed-get_started-detail', true );
    wp_send_json_success();
}
add_action( 'wp_ajax_bakery_patisserie_shop_dismissed_get_started_detail_notice', 'bakery_patisserie_shop_dismissed_get_started_detail_notice' );
add_action( 'wp_ajax_nopriv_bakery_patisserie_shop_dismissed_get_started_detail_notice', 'bakery_patisserie_shop_dismissed_get_started_detail_notice' );

// Reset on theme switch
add_action('after_switch_theme', 'bakery_patisserie_shop_setup_settings');
function bakery_patisserie_shop_setup_settings() {
    update_option('dismissed-get_started', false );
    update_option('dismissed-get_started-detail', false );
}

add_action( 'wp_ajax_bakery_patisserie_shop_popup_done', 'bakery_patisserie_shop_popup_done' );
function bakery_patisserie_shop_popup_done() {
	update_option( 'bakery_patisserie_shop_demo_popup_shown', true );
	wp_die();
}