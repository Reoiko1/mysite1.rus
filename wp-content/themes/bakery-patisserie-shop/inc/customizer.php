<?php
/**
 * Bakery Patisserie Shop: Customizer
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function Bakery_Patisserie_Shop_Customize_register( $wp_customize ) {

	// Pro Version
    class bakery_patisserie_shop_Customize_Pro_Version extends WP_Customize_Control {
        public $type = 'pro_options';

        public function render_content() {
            echo '<span>Unlock Premium <strong>'. esc_html( $this->label ) .'</strong>? </span>';
            echo '<a href="'. esc_url($this->description) .'" target="_blank">';
                echo '<span class="dashicons dashicons-info"></span>';
                echo '<strong> '. esc_html( BAKERY_PATISSERIE_SHOP_BUY_TEXT,'bakery-patisserie-shop' ) .'<strong></a>';
            echo '</a>';
        }
    }

    // Custom Controls
    function bakery_patisserie_shop_sanitize_custom_control( $input ) {
        return $input;
    }

	require get_parent_theme_file_path('/inc/controls/range-slider-control.php');

	require get_parent_theme_file_path('/inc/controls/icon-changer.php');
	
	// Register the custom control type.
	$wp_customize->register_control_type( 'Bakery_Patisserie_Shop_Toggle_Control' );
	
	//Register the sortable control type.
	$wp_customize->register_control_type( 'Bakery_Patisserie_Shop_Control_Sortable' );

	//add home page setting pannel
	$wp_customize->add_panel( 'bakery_patisserie_shop_panel_id', array(
	    'priority' => 10,
	    'capability' => 'edit_theme_options',
	    'theme_supports' => '',
	    'title' => __( 'Custom Home page', 'bakery-patisserie-shop' ),
	    'description' => __( 'Description of what this panel does.', 'bakery-patisserie-shop' ),
	) );
	
	//TP GENRAL OPTION
	$wp_customize->add_section('bakery_patisserie_shop_tp_general_settings',array(
        'title' => __('TP General Option', 'bakery-patisserie-shop'),
        'priority' => 1,
        'panel' => 'bakery_patisserie_shop_panel_id'
    ) );

    $wp_customize->add_setting('bakery_patisserie_shop_tp_body_layout_settings',array(
        'default' => 'Full',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
    $wp_customize->add_control('bakery_patisserie_shop_tp_body_layout_settings',array(
        'type' => 'radio',
        'label'     => __('Body Layout Setting', 'bakery-patisserie-shop'),
        'description'   => __('This option work for complete body, if you want to set the complete website in container.', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_tp_general_settings',
        'choices' => array(
            'Full' => __('Full','bakery-patisserie-shop'),
            'Container' => __('Container','bakery-patisserie-shop'),
            'Container Fluid' => __('Container Fluid','bakery-patisserie-shop')
        ),
	) );

    // Add Settings and Controls for Post Layout
	$wp_customize->add_setting('bakery_patisserie_shop_sidebar_post_layout',array(
        'default' => 'right',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_sidebar_post_layout',array(
        'type' => 'radio',
        'label'     => __('Post Sidebar Position', 'bakery-patisserie-shop'),
        'description'   => __('This option work for blog page, blog single page, archive page and search page.', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_tp_general_settings',
        'choices' => array(
            'full' => __('Full','bakery-patisserie-shop'),
            'left' => __('Left','bakery-patisserie-shop'),
            'right' => __('Right','bakery-patisserie-shop'),
            'three-column' => __('Three Columns','bakery-patisserie-shop'),
            'four-column' => __('Four Columns','bakery-patisserie-shop'),
            'grid' => __('Grid Layout','bakery-patisserie-shop')
        ),
	) );

	// Add Settings and Controls for post sidebar Layout
	$wp_customize->add_setting('bakery_patisserie_shop_sidebar_single_post_layout',array(
        'default' => 'right',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_sidebar_single_post_layout',array(
        'type' => 'radio',
        'label'     => __('Single Post Sidebar Position', 'bakery-patisserie-shop'),
        'description'   => __('This option work for single blog page', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_tp_general_settings',
        'choices' => array(
            'full' => __('Full','bakery-patisserie-shop'),
            'left' => __('Left','bakery-patisserie-shop'),
            'right' => __('Right','bakery-patisserie-shop'),
        ),
	) );

	// Add Settings and Controls for Page Layout
	$wp_customize->add_setting('bakery_patisserie_shop_sidebar_page_layout',array(
        'default' => 'right',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_sidebar_page_layout',array(
        'type' => 'radio',
        'label'     => __('Page Sidebar Position', 'bakery-patisserie-shop'),
        'description'   => __('This option work for pages.', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_tp_general_settings',
        'choices' => array(
            'full' => __('Full','bakery-patisserie-shop'),
            'left' => __('Left','bakery-patisserie-shop'),
            'right' => __('Right','bakery-patisserie-shop')
        ),
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_sticky', array(
		'default'           => false,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_sticky', array(
		'label'       => esc_html__( 'Show Sticky Header', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_tp_general_settings',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_sticky',
	) ) );

	//tp typography option
	$bakery_patisserie_shop_font_array = array(
		''                       => 'No Fonts',
		'Abril Fatface'          => 'Abril Fatface',
		'Acme'                   => 'Acme',
		'Anton'                  => 'Anton',
		'Architects Daughter'    => 'Architects Daughter',
		'Arimo'                  => 'Arimo',
		'Arsenal'                => 'Arsenal',
		'Arvo'                   => 'Arvo',
		'Alegreya'               => 'Alegreya',
		'Alfa Slab One'          => 'Alfa Slab One',
		'Averia Serif Libre'     => 'Averia Serif Libre',
		'Bangers'                => 'Bangers',
		'Boogaloo'               => 'Boogaloo',
		'Bad Script'             => 'Bad Script',
		'Bitter'                 => 'Bitter',
		'Bree Serif'             => 'Bree Serif',
		'BenchNine'              => 'BenchNine',
		'Cabin'                  => 'Cabin',
		'Cardo'                  => 'Cardo',
		'Courgette'              => 'Courgette',
		'Cherry Swash'           => 'Cherry Swash',
		'Cormorant Garamond'     => 'Cormorant Garamond',
		'Crimson Text'           => 'Crimson Text',
		'Cuprum'                 => 'Cuprum',
		'Cookie'                 => 'Cookie',
		'Chewy'                  => 'Chewy',
		'Days One'               => 'Days One',
		'Dosis'                  => 'Dosis',
		'Droid Sans'             => 'Droid Sans',
		'Economica'              => 'Economica',
		'Fredoka One'            => 'Fredoka One',
		'Fjalla One'             => 'Fjalla One',
		'Francois One'           => 'Francois One',
		'Frank Ruhl Libre'       => 'Frank Ruhl Libre',
		'Gloria Hallelujah'      => 'Gloria Hallelujah',
		'Great Vibes'            => 'Great Vibes',
		'Handlee'                => 'Handlee',
		'Hammersmith One'        => 'Hammersmith One',
		'Inconsolata'            => 'Inconsolata',
		'Indie Flower'           => 'Indie Flower',
		'Inter'                  => 'Inter',
		'IM Fell English SC'     => 'IM Fell English SC',
		'Julius Sans One'        => 'Julius Sans One',
		'Josefin Slab'           => 'Josefin Slab',
		'Josefin Sans'           => 'Josefin Sans',
		'Kanit'                  => 'Kanit',
		'Karla'                  => 'Karla',
		'Lobster'                => 'Lobster',
		'Lato'                   => 'Lato',
		'Lora'                   => 'Lora',
		'Libre Baskerville'      => 'Libre Baskerville',
		'Lobster Two'            => 'Lobster Two',
		'Manrope'           	 => 'Manrope',
		'Merriweather'           => 'Merriweather',
		'Monda'                  => 'Monda',
		'Montserrat'             => 'Montserrat',
		'Muli'                   => 'Muli',
		'Marck Script'           => 'Marck Script',
		'Noto Serif'             => 'Noto Serif',
		'Open Sans'              => 'Open Sans',
		'Overpass'               => 'Overpass',
		'Overpass Mono'          => 'Overpass Mono',
		'Oxygen'                 => 'Oxygen',
		'Oxanium'                => 'Oxanium',
		'Orbitron'               => 'Orbitron',
		'Patua One'              => 'Patua One',
		'Pacifico'               => 'Pacifico',
		'Padauk'                 => 'Padauk',
		'Playball'               => 'Playball',
		'Playfair Display'       => 'Playfair Display',
		'PT Sans'                => 'PT Sans',
		'Philosopher'            => 'Philosopher',
		'Permanent Marker'       => 'Permanent Marker',
		'Poiret One'             => 'Poiret One',
		'Quicksand'              => 'Quicksand',
		'Quattrocento Sans'      => 'Quattrocento Sans',
		'Raleway'                => 'Raleway',
		'Rubik'                  => 'Rubik',
		'Rokkitt'                => 'Rokkitt',
		'Roboto Serif'           => 'Roboto Serif',
		'Russo One'              => 'Russo One',
		'Righteous'              => 'Righteous',
		'Satisfy'                => 'Satisfy',
		'Slabo'                  => 'Slabo',
		'Source Sans Pro'        => 'Source Sans Pro',
		'Shadows Into Light Two' => 'Shadows Into Light Two',
		'Shadows Into Light'     => 'Shadows Into Light',
		'Sacramento'             => 'Sacramento',
		'Shrikhand'              => 'Shrikhand',
		'Tangerine'              => 'Tangerine',
		'Ubuntu'                 => 'Ubuntu',
		'VT323'                  => 'VT323',
		'Varela Round'           => 'Varela Round',
		'Vampiro One'            => 'Vampiro One',
		'Vollkorn'               => 'Vollkorn',
		'Volkhov'                => 'Volkhov',
		'Yanone Kaffeesatz'      => 'Yanone Kaffeesatz'
	);

	$wp_customize->add_section('bakery_patisserie_shop_typography_option',array(
		'title'         => __('TP Typography Option', 'bakery-patisserie-shop'),
		'priority' => 1,
		'panel' => 'bakery_patisserie_shop_panel_id'
   	));

   	$wp_customize->add_setting('bakery_patisserie_shop_heading_font_family', array(
		'default'           => '',
		'capability'        => 'edit_theme_options',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices',
	));
	$wp_customize->add_control(	'bakery_patisserie_shop_heading_font_family', array(
		'section' => 'bakery_patisserie_shop_typography_option',
		'label'   => __('heading Fonts', 'bakery-patisserie-shop'),
		'type'    => 'select',
		'choices' => $bakery_patisserie_shop_font_array,
	));

	$wp_customize->add_setting('bakery_patisserie_shop_body_font_family', array(
		'default'           => '',
		'capability'        => 'edit_theme_options',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices',
	));
	$wp_customize->add_control(	'bakery_patisserie_shop_body_font_family', array(
		'section' => 'bakery_patisserie_shop_typography_option',
		'label'   => __('Body Fonts', 'bakery-patisserie-shop'),
		'type'    => 'select',
		'choices' => $bakery_patisserie_shop_font_array,
	));

	//TP Preloader Option
	$wp_customize->add_section('bakery_patisserie_shop_prelaoder_option',array(
		'title'         => __('TP Preloader Option', 'bakery-patisserie-shop'),
		'priority' => 1,
		'panel' => 'bakery_patisserie_shop_panel_id'
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_preloader_show_hide', array(
		'default'           => false,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_preloader_show_hide', array(
		'label'       => esc_html__( 'Show / Hide Preloader Option', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_prelaoder_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_preloader_show_hide',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_tp_preloader_color1_option', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_tp_preloader_color1_option', array(
			'label'     => __('Preloader First Ring Color', 'bakery-patisserie-shop'),
	    'description' => __('It will change the complete theme preloader ring 1 color in one click.', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_prelaoder_option',
	    'settings' => 'bakery_patisserie_shop_tp_preloader_color1_option',
  	)));

  	$wp_customize->add_setting( 'bakery_patisserie_shop_tp_preloader_color2_option', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_tp_preloader_color2_option', array(
			'label'     => __('Preloader Second Ring Color', 'bakery-patisserie-shop'),
	    'description' => __('It will change the complete theme preloader ring 2 color in one click.', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_prelaoder_option',
	    'settings' => 'bakery_patisserie_shop_tp_preloader_color2_option',
  	)));

  	$wp_customize->add_setting( 'bakery_patisserie_shop_tp_preloader_bg_color_option', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_tp_preloader_bg_color_option', array(
			'label'     => __('Preloader Background Color', 'bakery-patisserie-shop'),
	    'description' => __('It will change the complete theme preloader bg color in one click.', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_prelaoder_option',
	    'settings' => 'bakery_patisserie_shop_tp_preloader_bg_color_option',
  	)));

  	// Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_preloader_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_preloader_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_prelaoder_option',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
        'priority'    => 100
    )));

	//TP Color Option
	$wp_customize->add_section('bakery_patisserie_shop_color_option',array(
     'title'         => __('TP Color Option', 'bakery-patisserie-shop'),
     'priority' => 1,
     'panel' => 'bakery_patisserie_shop_panel_id'
    ) );
    
	$wp_customize->add_setting( 'bakery_patisserie_shop_tp_color_option_first', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_tp_color_option_first', array(
			'label'     => __('Theme First Color', 'bakery-patisserie-shop'),
	    'description' => __('It will change the complete theme color in one click.', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_color_option',
	    'settings' => 'bakery_patisserie_shop_tp_color_option_first',
  	)));

  	$wp_customize->add_setting( 'bakery_patisserie_shop_tp_color_option_sec', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_tp_color_option_sec', array(
			'label'     => __('Theme Second Color', 'bakery-patisserie-shop'),
	    'description' => __('It will change the complete theme color in one click.', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_color_option',
	    'settings' => 'bakery_patisserie_shop_tp_color_option_sec',
  	)));

	//TP Blog Option
	$wp_customize->add_section('bakery_patisserie_shop_blog_option',array(
        'title' => __('TP Blog Option', 'bakery-patisserie-shop'),
        'priority' => 1,
        'panel' => 'bakery_patisserie_shop_panel_id'
    ) );

    $wp_customize->add_setting('bakery_patisserie_shop_edit_blog_page_title',array(
		'default'=> __('Home','bakery-patisserie-shop'),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('bakery_patisserie_shop_edit_blog_page_title',array(
		'label'	=> __('Change Blog Page Title','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_blog_option',
		'type'=> 'text'
	));

	$wp_customize->add_setting('bakery_patisserie_shop_edit_blog_page_description',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('bakery_patisserie_shop_edit_blog_page_description',array(
		'label'	=> __('Add Blog Page Description','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_blog_option',
		'type'=> 'text'
	));

	/** Meta Order */
    $wp_customize->add_setting('blog_meta_order', array(
        'default' => array('date', 'author', 'comment','category', 'time'),
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_sortable',
    ));
    $wp_customize->add_control(new Bakery_Patisserie_Shop_Control_Sortable($wp_customize, 'blog_meta_order', array(
    	'label' => esc_html__('Meta Order', 'bakery-patisserie-shop'),
        'description' => __('Drag & Drop post items to re-arrange the order and also hide and show items as per the need by clicking on the eye icon.', 'bakery-patisserie-shop') ,
        'section' => 'bakery_patisserie_shop_blog_option',
        'choices' => array(
            'date' => __('date', 'bakery-patisserie-shop') ,
            'author' => __('author', 'bakery-patisserie-shop') ,
            'comment' => __('comment', 'bakery-patisserie-shop') ,
            'category' => __('category', 'bakery-patisserie-shop') ,
            'time' => __('time', 'bakery-patisserie-shop') ,
        ) ,
    )));

    $wp_customize->add_setting( 'bakery_patisserie_shop_excerpt_count', array(
		'default'              => 35,
		'type'                 => 'theme_mod',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'bakery_patisserie_shop_sanitize_number_range',
		'sanitize_js_callback' => 'absint',
	) );
	$wp_customize->add_control( 'bakery_patisserie_shop_excerpt_count', array(
		'label'       => esc_html__( 'Edit Excerpt Limit','bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_blog_option',
		'type'        => 'number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 50,
		),
	) );

    $wp_customize->add_setting('bakery_patisserie_shop_read_more_text',array(
		'default'=> __('Read More','bakery-patisserie-shop'),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('bakery_patisserie_shop_read_more_text',array(
		'label'	=> __('Edit Button Text','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_blog_option',
		'type'=> 'text'
	));

	$wp_customize->add_setting('bakery_patisserie_shop_post_image_round', array(
	  'default' => '0',
      'sanitize_callback' => 'bakery_patisserie_shop_sanitize_number_range',
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Range_Slider($wp_customize, 'bakery_patisserie_shop_post_image_round', array(
       'section' => 'bakery_patisserie_shop_blog_option',
      'label' => esc_html__('Edit Post Image Border Radius', 'bakery-patisserie-shop'),
      'input_attrs' => array(
        'min' => 0,
        'max' => 180,
        'step' => 1
    )
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_post_image_width', array(
	  'default' => '',
      'sanitize_callback' => 'bakery_patisserie_shop_sanitize_number_range',
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Range_Slider($wp_customize, 'bakery_patisserie_shop_post_image_width', array(
       'section' => 'bakery_patisserie_shop_blog_option',
      'label' => esc_html__('Edit Post Image Width', 'bakery-patisserie-shop'),
      'input_attrs' => array(
        'min' => 0,
        'max' => 367,
        'step' => 1
    )
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_post_image_length', array(
	  'default' => '',
      'sanitize_callback' => 'bakery_patisserie_shop_sanitize_number_range',
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Range_Slider($wp_customize, 'bakery_patisserie_shop_post_image_length', array(
       'section' => 'bakery_patisserie_shop_blog_option',
      'label' => esc_html__('Edit Post Image height', 'bakery-patisserie-shop'),
      'input_attrs' => array(
        'min' => 0,
        'max' => 900,
        'step' => 1
    )
	)));
	
	$wp_customize->add_setting( 'bakery_patisserie_shop_remove_read_button', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_remove_read_button', array(
		'label'       => esc_html__( 'Show / Hide Read More Button', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_blog_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_remove_read_button',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_remove_tags', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_remove_tags', array(
		'label'       => esc_html__( 'Show / Hide Tags Option', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_blog_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_remove_tags',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_remove_category', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_remove_category', array(
		'label'       => esc_html__( 'Show / Hide Category Option', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_blog_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_remove_category',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_remove_comment', array(
	 'default'           => true,
	 'transport'         => 'refresh',
	 'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
 	) );

	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_remove_comment', array(
	 'label'       => esc_html__( 'Show / Hide Comment Form', 'bakery-patisserie-shop' ),
	 'section'     => 'bakery_patisserie_shop_blog_option',
	 'type'        => 'toggle',
	 'settings'    => 'bakery_patisserie_shop_remove_comment',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_remove_related_post', array(
	 'default'           => true,
	 'transport'         => 'refresh',
	 'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
 	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_remove_related_post', array(
	 'label'       => esc_html__( 'Show / Hide Related Post', 'bakery-patisserie-shop' ),
	 'section'     => 'bakery_patisserie_shop_blog_option',
	 'type'        => 'toggle',
	 'settings'    => 'bakery_patisserie_shop_remove_related_post',
	) ) );

	$wp_customize->add_setting('bakery_patisserie_shop_related_post_heading',array(
		'default'=> __('Related Posts','bakery-patisserie-shop'),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('bakery_patisserie_shop_related_post_heading',array(
		'label'	=> __('Edit Section Title','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_blog_option',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'bakery_patisserie_shop_related_post_per_page', array(
		'default'              => 3,
		'type'                 => 'theme_mod',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'bakery_patisserie_shop_sanitize_number_range',
		'sanitize_js_callback' => 'absint',
	) );
	$wp_customize->add_control( 'bakery_patisserie_shop_related_post_per_page', array(
		'label'       => esc_html__( 'Related Post Per Page','bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_blog_option',
		'type'        => 'number',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 3,
			'max'              => 9,
		),
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_related_post_per_columns', array(
		'default'              => 3,
		'type'                 => 'theme_mod',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'bakery_patisserie_shop_sanitize_number_range',
		'sanitize_js_callback' => 'absint',
	) );
	$wp_customize->add_control( 'bakery_patisserie_shop_related_post_per_columns', array(
		'label'       => esc_html__( 'Related Post Per Row','bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_blog_option',
		'type'        => 'number',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 4,
		),
	) );

	$wp_customize->add_setting('bakery_patisserie_shop_post_layout',array(
        'default' => 'image-content',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_post_layout',array(
        'type' => 'radio',
        'label'     => __('Post Layout', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_blog_option',
        'choices' => array(
            'image-content' => __('Media-Content','bakery-patisserie-shop'),
            'content-image' => __('Content-Media','bakery-patisserie-shop'),
        ),
	) );

		//TP Single Blog Option
	$wp_customize->add_section('bakery_patisserie_shop_single_blog_option',array(
        'title' => __('Single Post Option', 'bakery-patisserie-shop'),
        'priority' => 1,
        'panel' => 'bakery_patisserie_shop_panel_id'
    ) );

    /** Meta Order */
    $wp_customize->add_setting('bakery_patisserie_shop_single_blog_meta_order', array(
        'default' => array('date', 'author', 'comment','category', 'time'),
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_sortable',
    ));
    $wp_customize->add_control(new bakery_patisserie_shop_Control_Sortable($wp_customize, 'bakery_patisserie_shop_single_blog_meta_order', array(
    	'label' => esc_html__('Meta Order', 'bakery-patisserie-shop'),
        'description' => __('Drag & Drop post items to re-arrange the order and also hide and show items as per the need by clicking on the eye icon.', 'bakery-patisserie-shop') ,
        'section' => 'bakery_patisserie_shop_single_blog_option',
        'choices' => array(
            'date' => __('date', 'bakery-patisserie-shop') ,
            'author' => __('author', 'bakery-patisserie-shop') ,
            'comment' => __('comment', 'bakery-patisserie-shop') ,
            'category' => __('category', 'bakery-patisserie-shop') ,
            'time' => __('time', 'bakery-patisserie-shop') ,
        ) ,
    )));

    $wp_customize->add_setting('bakery_patisserie_shop_single_post_date_icon',array(
		'default'	=> 'far fa-calendar-alt',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Icon_Changer(
       $wp_customize,'bakery_patisserie_shop_single_post_date_icon',array(
		'label'	=> __('Change Date Icon','bakery-patisserie-shop'),
		'transport' => 'refresh',
		'section'	=> 'bakery_patisserie_shop_single_blog_option',
		'type'		=> 'bakery-patisserie-shop-icon'
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_single_post_author_icon',array(
		'default'	=> 'fas fa-user',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Icon_Changer(
       $wp_customize,'bakery_patisserie_shop_single_post_author_icon',array(
		'label'	=> __('Change Author Icon','bakery-patisserie-shop'),
		'transport' => 'refresh',
		'section'	=> 'bakery_patisserie_shop_single_blog_option',
		'type'		=> 'bakery-patisserie-shop-icon'
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_single_post_comment_icon',array(
		'default'	=> 'fas fa-comments',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Icon_Changer(
       $wp_customize,'bakery_patisserie_shop_single_post_comment_icon',array(
		'label'	=> __('Change Comment Icon','bakery-patisserie-shop'),
		'transport' => 'refresh',
		'section'	=> 'bakery_patisserie_shop_single_blog_option',
		'type'		=> 'bakery-patisserie-shop-icon'
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_single_post_category_icon',array(
		'default'	=> 'fas fa-list',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Icon_Changer(
       $wp_customize,'bakery_patisserie_shop_single_post_category_icon',array(
		'label'	=> __('Change Category Icon','bakery-patisserie-shop'),
		'transport' => 'refresh',
		'section'	=> 'bakery_patisserie_shop_single_blog_option',
		'type'		=> 'bakery-patisserie-shop-icon'
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_single_post_time_icon',array(
		'default'	=> 'fas fa-clock',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Icon_Changer(
       $wp_customize,'bakery_patisserie_shop_single_post_time_icon',array(
		'label'	=> __('Change Time Icon','bakery-patisserie-shop'),
		'transport' => 'refresh',
		'section'	=> 'bakery_patisserie_shop_single_blog_option',
		'type'		=> 'bakery-patisserie-shop-icon'
	)));

	//MENU TYPOGRAPHY
	$wp_customize->add_section( 'bakery_patisserie_shop_menu_typography', array(
    	'title'      => __( 'Menu Typography', 'bakery-patisserie-shop' ),
    	'priority' => 2,
		'panel' => 'bakery_patisserie_shop_panel_id'
	) );

	$wp_customize->add_setting('bakery_patisserie_shop_menu_font_weight',array(
        'default' => '',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_menu_font_weight',array(
     'type' => 'radio',
     'label'     => __('Font Weight', 'bakery-patisserie-shop'),
     'section' => 'bakery_patisserie_shop_menu_typography',
     'type' => 'select',
     'choices' => array(
         '100' => __('100','bakery-patisserie-shop'),
         '200' => __('200','bakery-patisserie-shop'),
         '300' => __('300','bakery-patisserie-shop'),
         '400' => __('400','bakery-patisserie-shop'),
         '500' => __('500','bakery-patisserie-shop'),
         '600' => __('600','bakery-patisserie-shop'),
         '700' => __('700','bakery-patisserie-shop'),
         '800' => __('800','bakery-patisserie-shop'),
         '900' => __('900','bakery-patisserie-shop')
     ),
	) );

	$wp_customize->add_setting('bakery_patisserie_shop_menu_text_tranform',array(
		'default' => '',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
 	));
 	$wp_customize->add_control('bakery_patisserie_shop_menu_text_tranform',array(
		'type' => 'select',
		'label' => __('Menu Text Transform','bakery-patisserie-shop'),
		'section' => 'bakery_patisserie_shop_menu_typography',
		'choices' => array(
		   'Uppercase' => __('Uppercase','bakery-patisserie-shop'),
		   'Lowercase' => __('Lowercase','bakery-patisserie-shop'),
		   'Capitalize' => __('Capitalize','bakery-patisserie-shop'),
		),
	) );
	$wp_customize->add_setting('bakery_patisserie_shop_menu_font_size', array(
	  'default' => '',
      'sanitize_callback' => 'bakery_patisserie_shop_sanitize_number_range',
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Range_Slider($wp_customize, 'bakery_patisserie_shop_menu_font_size', array(
        'section' => 'bakery_patisserie_shop_menu_typography',
        'label' => esc_html__('Font Size', 'bakery-patisserie-shop'),
        'input_attrs' => array(
          'min' => 0,
          'max' => 20,
          'step' => 1
    )
	)));

	$wp_customize->add_setting( 'bakery_patisserie_shop_menu_color', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_menu_color', array(
			'label'     => __('Change Menu Color', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_menu_typography',
	    'settings' => 'bakery_patisserie_shop_menu_color',
  	)));

  	// Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_menu_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_menu_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_menu_typography',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
        'priority'    => 100
    )));

	// Header detail
	$wp_customize->add_section( 'bakery_patisserie_shop_header_sec', array(
    	'title'      => __( 'Header Details', 'bakery-patisserie-shop' ),
    	'description' => __( 'Add your Header details here', 'bakery-patisserie-shop' ),
		'panel' => 'bakery_patisserie_shop_panel_id',
      'priority' => 2,
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_sticky_header',array(
		'default' => true,
      	'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_checkbox'
    ) );
    $wp_customize->add_control('bakery_patisserie_shop_sticky_header',array(
    	'type' => 'checkbox',
        'label' => __( 'Show / Hide Sticky Header','bakery-patisserie-shop' ),
        'section' => 'bakery_patisserie_shop_header_sec'
    ));

    // Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_header_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_header_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_header_sec',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
        'priority'    => 100
    )));

	//home page slider
	$wp_customize->add_section( 'bakery_patisserie_shop_slider_section' , array(
    	'title'      => __( 'Slider Section', 'bakery-patisserie-shop' ),
    	'priority' => 2,
		'panel' => 'bakery_patisserie_shop_panel_id'
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_slider_arrows', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_slider_arrows', array(
		'label'       => esc_html__( 'Show / Hide slider', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_slider_section',
		'priority' => 1,
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_slider_arrows',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_show_slider_title', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_show_slider_title', array(
		'label'       => esc_html__( 'Show / Hide Slider Heading', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_slider_section',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_show_slider_title',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_show_slider_content', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_show_slider_content', array(
		'label'       => esc_html__( 'Show / Hide Slider Content', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_slider_section',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_show_slider_content',
	) ) );

	for ( $bakery_patisserie_shop_count = 1; $bakery_patisserie_shop_count <= 4; $bakery_patisserie_shop_count++ ) {
		$wp_customize->add_setting( 'bakery_patisserie_shop_slider_page' . $bakery_patisserie_shop_count, array(
			'default'           => '',
			'sanitize_callback' => 'bakery_patisserie_shop_sanitize_dropdown_pages'
		) );

		$wp_customize->add_control( 'bakery_patisserie_shop_slider_page' . $bakery_patisserie_shop_count, array(
			'label'    => __( 'Select Slide Image Page', 'bakery-patisserie-shop' ),
			'section'  => 'bakery_patisserie_shop_slider_section',
			'type'     => 'dropdown-pages'
		) );
	}

	//Slider excerpt
	$wp_customize->add_setting( 'bakery_patisserie_shop_slider_excerpt_length', array(
		'default'              => 53,
		'sanitize_callback'	=> 'absint',
	) );
	$wp_customize->add_control( 'bakery_patisserie_shop_slider_excerpt_length', array(
		'label'       => esc_html__( 'Slider Content length','bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_slider_section',
		'type'        => 'number',
		'settings'    => 'bakery_patisserie_shop_slider_excerpt_length',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 0,
			'max'              => 100,
		),
	) );


	$wp_customize->add_setting('bakery_patisserie_shop_about_bg1',array(
		'default'	=> get_template_directory_uri() . '/assets/images/slider-side1.png',
		'sanitize_callback'	=> 'esc_url_raw',
	));
	$wp_customize->add_control( new WP_Customize_Image_Control($wp_customize,'bakery_patisserie_shop_about_bg1',array(
	    'label' => __('Select Slider Top Left Imgae','bakery-patisserie-shop'),
	     'section' => 'bakery_patisserie_shop_slider_section'
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_about_bg2',array(
		'default'	=> get_template_directory_uri() . '/assets/images/slider-side2.png',
		'sanitize_callback'	=> 'esc_url_raw',
	));
	$wp_customize->add_control( new WP_Customize_Image_Control($wp_customize,'bakery_patisserie_shop_about_bg2',array(
	    'label' => __('Select Slider Top Right Image','bakery-patisserie-shop'),
	     'section' => 'bakery_patisserie_shop_slider_section'
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_about_bg3',array(
		'default'	=> get_template_directory_uri() . '/assets/images/slider-side3.png',
		'sanitize_callback'	=> 'esc_url_raw',
	));
	$wp_customize->add_control( new WP_Customize_Image_Control($wp_customize,'bakery_patisserie_shop_about_bg3',array(
	    'label' => __('Select Slider Bottom Left Image','bakery-patisserie-shop'),
	     'section' => 'bakery_patisserie_shop_slider_section'
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_about_bg4',array(
		'default'	=> get_template_directory_uri() . '/assets/images/slider-side4.png',
		'sanitize_callback'	=> 'esc_url_raw',
	));
	$wp_customize->add_control( new WP_Customize_Image_Control($wp_customize,'bakery_patisserie_shop_about_bg4',array(
	    'label' => __('Select Slider Bottom Right Image','bakery-patisserie-shop'),
	     'section' => 'bakery_patisserie_shop_slider_section'
	)));

	// Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_slider_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_slider_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_slider_section',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
        'priority'    => 100
    )));

	/*=========================================
	product Section
	=========================================*/
	$wp_customize->add_section(
		'bakery_patisserie_shop_our_products_section', array(
			'title' => esc_html__( 'Best Seller Product Section', 'bakery-patisserie-shop' ),
			'priority' => 3,
			'panel' => 'bakery_patisserie_shop_panel_id',
		)
	);

	$wp_customize->add_setting( 'bakery_patisserie_shop_our_products_show_hide_section', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_our_products_show_hide_section', array(
		'label'       => esc_html__( 'Show / Hide Section', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_our_products_section',
		'priority' => 1,
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_our_products_show_hide_section',
	) ) );

	$wp_customize->add_setting('bakery_patisserie_shop_product_section_bg_image',array(
		'default'	=> get_template_directory_uri() . '/assets/images/product-bg-cover.png',
		'sanitize_callback'	=> 'esc_url_raw',
	));
	$wp_customize->add_control( new WP_Customize_Image_Control($wp_customize,'bakery_patisserie_shop_product_section_bg_image',array(
	    'label' => __('Select Section Background Image','bakery-patisserie-shop'),
	     'section' => 'bakery_patisserie_shop_our_products_section'
	)));

	// product Heading
	$wp_customize->add_setting( 
    	'bakery_patisserie_shop_our_products_heading_section',
    	array(
			'capability'     	=> 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);	
	$wp_customize->add_control( 
		'bakery_patisserie_shop_our_products_heading_section',
		array(
		    'label'   		=> __('Add Heading','bakery-patisserie-shop'),
		    'section'		=> 'bakery_patisserie_shop_our_products_section',
			'type' 			=> 'text',
		)
	);

	// product content
	$wp_customize->add_setting( 
    	'bakery_patisserie_shop_our_products_content',
    	array(
			'capability'     	=> 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);	
	$wp_customize->add_control( 
		'bakery_patisserie_shop_our_products_content',
		array(
		    'label'   		=> __('Add Content','bakery-patisserie-shop'),
		    'section'		=> 'bakery_patisserie_shop_our_products_section',
			'type' 			=> 'text',
		)
	);

	$bakery_patisserie_shop_args = array(
	    'type'           => 'product',
	    'child_of'       => 0,
	    'parent'         => '',
	    'orderby'        => 'term_group',
	    'order'          => 'ASC',
	    'hide_empty'     => false,
	    'hierarchical'   => 1,
	    'number'         => '',
	    'taxonomy'       => 'product_cat',
	    'pad_counts'     => false
	);
	$categories = get_categories($bakery_patisserie_shop_args);
	$bakery_patisserie_shop_cats = array();
	$i = 0;
	foreach ($categories as $category) {
	    if ($i == 0) {
	        $default = $category->slug;
	        $i++;
	    }
	    $bakery_patisserie_shop_cats[$category->slug] = $category->name;
	}

	// Set the default value to "none"
	$bakery_patisserie_shop_default_value = 'product_cat1';

	$wp_customize->add_setting(
	    'bakery_patisserie_shop_our_product_product_category',
	    array(
	        'default'           => '',
	        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_select',
	    )
	);
	$bakery_patisserie_shop_cats_with_none = array_merge(array('none' => 'None'), $bakery_patisserie_shop_cats);

	$wp_customize->add_control(
	    'bakery_patisserie_shop_our_product_product_category',
	    array(
	        'type'    => 'select',
	        'choices' => $bakery_patisserie_shop_cats_with_none,
	        'label'   => __('Select Trending Products Category', 'bakery-patisserie-shop'),
	        'section' => 'bakery_patisserie_shop_our_products_section',
	    )
	);

	// Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_about_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_about_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_our_products_section',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
    )));


	//----------------------------footer------------------------
	$wp_customize->add_section('bakery_patisserie_shop_footer_section',array(
		'title'	=> __('Footer Widget Settings','bakery-patisserie-shop'),
		'panel' => 'bakery_patisserie_shop_panel_id',
		'priority' => 4,
	));

	$wp_customize->add_setting('bakery_patisserie_shop_footer_columns',array(
		'default'	=> 4,
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_columns',array(
		'label'	=> __('Footer Widget Columns','bakery-patisserie-shop'),
		'section'	=> 'bakery_patisserie_shop_footer_section',
		'setting'	=> 'bakery_patisserie_shop_footer_columns',
		'type'	=> 'number',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 4,
		),
	));
	$wp_customize->add_setting( 'bakery_patisserie_shop_tp_footer_bg_color_option', array(
		'default' => '#151515',
		'sanitize_callback' => 'sanitize_hex_color'
	));
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_tp_footer_bg_color_option', array(
		'label'     => __('Footer Widget Background Color', 'bakery-patisserie-shop'),
		'description' => __('It will change the complete footer widget backgorund color.', 'bakery-patisserie-shop'),
		'section' => 'bakery_patisserie_shop_footer_section',
		'settings' => 'bakery_patisserie_shop_tp_footer_bg_color_option',
	)));

	$wp_customize->add_setting('bakery_patisserie_shop_footer_widget_image',array(
		'default'	=> '',
		'sanitize_callback'	=> 'esc_url_raw',
	));
	$wp_customize->add_control( new WP_Customize_Image_Control($wp_customize,'bakery_patisserie_shop_footer_widget_image',array(
       'label' => __('Footer Widget Background Image','bakery-patisserie-shop'),
       'section' => 'bakery_patisserie_shop_footer_section'
	)));

	//footer widget title font size
	$wp_customize->add_setting('bakery_patisserie_shop_footer_widget_title_font_size',array(
		'default'	=> '',
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_widget_title_font_size',array(
		'label'	=> __('Change Footer Widget Title Font Size in PX','bakery-patisserie-shop'),
		'section'	=> 'bakery_patisserie_shop_footer_section',
	    'setting'	=> 'bakery_patisserie_shop_footer_widget_title_font_size',
		'type'	=> 'number',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 0,
			'max'              => 50,
		),
	));

	$wp_customize->add_setting( 'bakery_patisserie_shop_footer_widget_title_color', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_footer_widget_title_color', array(
			'label'     => __('Change Footer Widget Title Color', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_footer_section',
	    'settings' => 'bakery_patisserie_shop_footer_widget_title_color',
  	)));

  	$wp_customize->add_setting('bakery_patisserie_shop_footer_widget_title_font_weight',array(
        'default' => '',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_widget_title_font_weight',array(
     'type' => 'radio',
     'label'     => __('Change Footer Widget Title Font Weight', 'bakery-patisserie-shop'),
     'section' => 'bakery_patisserie_shop_footer_section',
     'type' => 'select',
     'choices' => array(
         '100' => __('100','bakery-patisserie-shop'),
         '200' => __('200','bakery-patisserie-shop'),
         '300' => __('300','bakery-patisserie-shop'),
         '400' => __('400','bakery-patisserie-shop'),
         '500' => __('500','bakery-patisserie-shop'),
         '600' => __('600','bakery-patisserie-shop'),
         '700' => __('700','bakery-patisserie-shop'),
         '800' => __('800','bakery-patisserie-shop'),
         '900' => __('900','bakery-patisserie-shop')
     ),
	) );

	$wp_customize->add_setting('bakery_patisserie_shop_footer_widget_title_text_tranform',array(
		'default' => '',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
 	));
 	$wp_customize->add_control('bakery_patisserie_shop_footer_widget_title_text_tranform',array(
		'type' => 'select',
		'label' => __('Change Footer Widget Title Letter Case','bakery-patisserie-shop'),
		'section' => 'bakery_patisserie_shop_footer_section',
		'choices' => array(
		   'Uppercase' => __('Uppercase','bakery-patisserie-shop'),
		   'Lowercase' => __('Lowercase','bakery-patisserie-shop'),
		   'Capitalize' => __('Capitalize','bakery-patisserie-shop'),
		),
	) );

	// Add Settings and Controls for position
	$wp_customize->add_setting('bakery_patisserie_shop_footer_widget_title_position',array(
        'default' => '',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_widget_title_position',array(
        'type' => 'radio',
        'label'     => __('Change Footer Widget Position', 'bakery-patisserie-shop'),
        'description'   => __('This option work for Footer Widget', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_footer_section',
        'choices' => array(
            'Right' => __('Right','bakery-patisserie-shop'),
            'Left' => __('Left','bakery-patisserie-shop'),
            'Center' => __('Center','bakery-patisserie-shop')
        ),
	) );
  	
	$wp_customize->add_setting( 'bakery_patisserie_shop_return_to_header', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_return_to_header', array(
		'label'       => esc_html__( 'Show / Hide Return to header', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_footer_section',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_return_to_header',
	) ) );

	$wp_customize->add_setting('bakery_patisserie_shop_return_icon',array(
		'default'	=> 'fas fa-arrow-up',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control(new Bakery_Patisserie_Shop_Icon_Changer(
       $wp_customize,'bakery_patisserie_shop_return_icon',array(
		'label'	=> __('Return to header Icon','bakery-patisserie-shop'),
		'transport' => 'refresh',
		'section'	=> 'bakery_patisserie_shop_footer_section',
		'type'		=> 'bakery-patisserie-shop-icon'
	)));


    // Add Settings and Controls for Scroll top
	$wp_customize->add_setting('bakery_patisserie_shop_scroll_top_position',array(
        'default' => 'Right',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_scroll_top_position',array(
        'type' => 'radio',
        'label'     => __('Scroll to top Position', 'bakery-patisserie-shop'),
        'description'   => __('This option work for scroll to top', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_footer_section',
        'choices' => array(
            'Right' => __('Right','bakery-patisserie-shop'),
            'Left' => __('Left','bakery-patisserie-shop'),
            'Center' => __('Center','bakery-patisserie-shop')
        ),
	) );

	// Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_footer_widget_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_footer_widget_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_footer_section',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
        'priority'    => 100
    )));

	//footer
	$wp_customize->add_section('bakery_patisserie_shop_footer_copyright_section',array(
		'title'	=> __('Footer Copyright Settings','bakery-patisserie-shop'),
		'description'	=> __('Add copyright text.','bakery-patisserie-shop'),
		'panel' => 'bakery_patisserie_shop_panel_id',
		'priority' => 5,
	));

	$wp_customize->add_setting('bakery_patisserie_shop_footer_text',array(
		'default' => __( 'Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_text',array(
		'label'	=> __('Copyright Text','bakery-patisserie-shop'),
		'section'	=> 'bakery_patisserie_shop_footer_copyright_section',
		'type'		=> 'text'
	));

	$wp_customize->add_setting('bakery_patisserie_shop_footer_copyright_font_size',array(
		'default'	=> '',
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_copyright_font_size',array(
		'label'	=> __('Change Footer Copyright Font Size in PX','bakery-patisserie-shop'),
		'section'	=> 'bakery_patisserie_shop_footer_copyright_section',
	    'setting'	=> 'bakery_patisserie_shop_footer_copyright_font_size',
		'type'	=> 'number',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 0,
			'max'              => 50,
		),
	));

	$wp_customize->add_setting('bakery_patisserie_shop_footer_copyright_title_font_weight',array(
        'default' => '',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_copyright_title_font_weight',array(
     'type' => 'radio',
     'label'     => __('Change Footer Copyright Text Font Weight', 'bakery-patisserie-shop'),
     'section' => 'bakery_patisserie_shop_footer_copyright_section',
     'type' => 'select',
     'choices' => array(
         '100' => __('100','bakery-patisserie-shop'),
         '200' => __('200','bakery-patisserie-shop'),
         '300' => __('300','bakery-patisserie-shop'),
         '400' => __('400','bakery-patisserie-shop'),
         '500' => __('500','bakery-patisserie-shop'),
         '600' => __('600','bakery-patisserie-shop'),
         '700' => __('700','bakery-patisserie-shop'),
         '800' => __('800','bakery-patisserie-shop'),
         '900' => __('900','bakery-patisserie-shop')
     ),
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_footer_copyright_text_color', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_footer_copyright_text_color', array(
			'label'     => __('Change Footer Copyright Text Color', 'bakery-patisserie-shop'),
	    'section' => 'bakery_patisserie_shop_footer_copyright_section',
	    'settings' => 'bakery_patisserie_shop_footer_copyright_text_color',
  	)));

  	$wp_customize->add_setting('bakery_patisserie_shop_footer_copyright_top_bottom_padding',array(
		'default'	=> '',
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_footer_copyright_top_bottom_padding',array(
		'label'	=> __('Change Footer Copyright Padding in PX','bakery-patisserie-shop'),
		'section'	=> 'bakery_patisserie_shop_footer_copyright_section',
	    'setting'	=> 'bakery_patisserie_shop_footer_copyright_top_bottom_padding',
		'type'	=> 'number',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 0,
			'max'              => 50,
		),
	));

	// Add Settings and Controls for Scroll top
	$wp_customize->add_setting('bakery_patisserie_shop_copyright_text_position',array(
        'default' => 'Center',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_copyright_text_position',array(
        'type' => 'radio',
        'label'     => __('Copyright Text Position', 'bakery-patisserie-shop'),
        'description'   => __('This option work for Copyright', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_footer_copyright_section',
        'choices' => array(
            'Right' => __('Right','bakery-patisserie-shop'),
            'Left' => __('Left','bakery-patisserie-shop'),
            'Center' => __('Center','bakery-patisserie-shop')
        ),
	) );

	// Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_copyright_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_copyright_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_footer_copyright_section',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
        'priority'    => 100
    )));

	//Mobile resposnsive
	$wp_customize->add_section('bakery_patisserie_shop_mobile_media_option',array(
		'title'         => __('Mobile Responsive media', 'bakery-patisserie-shop'),
		'description' => __('Control will not function if the toggle in the main settings is off.', 'bakery-patisserie-shop'),
		'priority' => 5,
		'panel' => 'bakery_patisserie_shop_panel_id'
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_mobile_blog_description', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_mobile_blog_description', array(
		'label'       => esc_html__( 'Show / Hide Blog Page Description', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_mobile_media_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_mobile_blog_description',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_return_to_header_mob', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_return_to_header_mob', array(
		'label'       => esc_html__( 'Show / Hide Return to header', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_mobile_media_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_return_to_header_mob',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_slider_buttom_mob', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_slider_buttom_mob', array(
		'label'       => esc_html__( 'Show / Hide Slider Button', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_mobile_media_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_slider_buttom_mob',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_related_post_mob', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_related_post_mob', array(
		'label'       => esc_html__( 'Show / Hide Related Post', 'bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_mobile_media_option',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_related_post_mob',
	) ) );

	//Slider height
    $wp_customize->add_setting('bakery_patisserie_shop_slider_img_height_responsive',array(
        'default'=> '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('bakery_patisserie_shop_slider_img_height_responsive',array(
        'label' => __('Slider Height','bakery-patisserie-shop'),
        'description'   => __('Add slider height in px(eg. 700px).','bakery-patisserie-shop'),
        'section'=> 'bakery_patisserie_shop_mobile_media_option',
        'type'=> 'text'
    ));

    // Pro Version
    $wp_customize->add_setting( 'bakery_patisserie_shop_responsive_pro_version_logo', array(
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_custom_control'
    ));
    $wp_customize->add_control( new bakery_patisserie_shop_Customize_Pro_Version ( $wp_customize,'bakery_patisserie_shop_responsive_pro_version_logo', array(
        'section'     => 'bakery_patisserie_shop_mobile_media_option',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Features ', 'bakery-patisserie-shop' ),
        'description' => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ),
        'priority'    => 100
    )));

	$wp_customize->get_setting( 'blogname' )->transport          = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport   = 'postMessage';

	//site Title
	$wp_customize->selective_refresh->add_partial( 'blogname', array(
		'selector' => '.site-title a',
		'render_callback' => 'Bakery_Patisserie_Shop_Customize_partial_blogname',
	) );

	$wp_customize->selective_refresh->add_partial( 'blogdescription', array(
		'selector' => '.site-description',
		'render_callback' => 'Bakery_Patisserie_Shop_Customize_partial_blogdescription',
	) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_site_title', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_site_title', array(
		'label'       => esc_html__( 'Show / Hide Site Title', 'bakery-patisserie-shop' ),
		'section'     => 'title_tagline',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_site_title',
	) ) );

	// logo site title size
	$wp_customize->add_setting('bakery_patisserie_shop_site_title_font_size',array(
		'default'	=> '',
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_site_title_font_size',array(
		'label'	=> __('Site Title Font Size in PX','bakery-patisserie-shop'),
		'section'	=> 'title_tagline',
		'setting'	=> 'bakery_patisserie_shop_site_title_font_size',
		'type'	=> 'number',
		'input_attrs' => array(
		    'step'             => 1,
			'min'              => 0,
			'max'              => 30,
			),
	));

	$wp_customize->add_setting( 'bakery_patisserie_shop_site_tagline_color', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_site_tagline_color', array(
			'label'     => __('Change Site Title Color', 'bakery-patisserie-shop'),
	    'section' => 'title_tagline',
	    'settings' => 'bakery_patisserie_shop_site_tagline_color',
  	)));

	$wp_customize->add_setting( 'bakery_patisserie_shop_site_tagline', array(
		'default'           => false,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_site_tagline', array(
		'label'       => esc_html__( 'Show / Hide Site Tagline', 'bakery-patisserie-shop' ),
		'section'     => 'title_tagline',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_site_tagline',
	) ) );

	// logo site tagline size
	$wp_customize->add_setting('bakery_patisserie_shop_site_tagline_font_size',array(
		'default'	=> '',
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_site_tagline_font_size',array(
		'label'	=> __('Site Tagline Font Size in PX','bakery-patisserie-shop'),
		'section'	=> 'title_tagline',
		'setting'	=> 'bakery_patisserie_shop_site_tagline_font_size',
		'type'	=> 'number',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 0,
			'max'              => 30,
		),
	));

	$wp_customize->add_setting( 'bakery_patisserie_shop_logo_tagline_color', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_logo_tagline_color', array(
			'label'     => __('Change Site Tagline Color', 'bakery-patisserie-shop'),
	    'section' => 'title_tagline',
	    'settings' => 'bakery_patisserie_shop_logo_tagline_color',
  	)));

    $wp_customize->add_setting('bakery_patisserie_shop_logo_width',array(
	   'default' => 80,
	   'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_logo_width',array(
		'label'	=> esc_html__('Here You Can Customize Your Logo Size','bakery-patisserie-shop'),
		'section'	=> 'title_tagline',
		'type'		=> 'number'
	));

	$wp_customize->add_setting('bakery_patisserie_shop_per_columns',array(
		'default'=> 3,
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_per_columns',array(
		'label'	=> __('Product Per Row','bakery-patisserie-shop'),
		'section'=> 'woocommerce_product_catalog',
		'type'=> 'number'
	));

	$wp_customize->add_setting('bakery_patisserie_shop_product_per_page',array(
		'default'=> 9,
		'sanitize_callback'	=> 'bakery_patisserie_shop_sanitize_number_absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_product_per_page',array(
		'label'	=> __('Product Per Page','bakery-patisserie-shop'),
		'section'=> 'woocommerce_product_catalog',
		'type'=> 'number'
	));

	$wp_customize->add_setting( 'bakery_patisserie_shop_product_sidebar', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_product_sidebar', array(
		'label'       => esc_html__( 'Show / Hide Shop Page Sidebar', 'bakery-patisserie-shop' ),
		'section'     => 'woocommerce_product_catalog',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_product_sidebar',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_single_product_sidebar', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_single_product_sidebar', array(
		'label'       => esc_html__( 'Show / Hide Product Page Sidebar', 'bakery-patisserie-shop' ),
		'section'     => 'woocommerce_product_catalog',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_single_product_sidebar',
	) ) );

	$wp_customize->add_setting( 'bakery_patisserie_shop_related_product', array(
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	) );
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_related_product', array(
		'label'       => esc_html__( 'Show / Hide related product', 'bakery-patisserie-shop' ),
		'section'     => 'woocommerce_product_catalog',
		'type'        => 'toggle',
		'settings'    => 'bakery_patisserie_shop_related_product',
	) ) );

	
	//Page template settings
	$wp_customize->add_panel( 'bakery_patisserie_shop_page_panel_id', array(
	    'priority' => 10,
	    'capability' => 'edit_theme_options',
	    'theme_supports' => '',
	    'title' => __( 'Page Template Settings', 'bakery-patisserie-shop' ),
	    'description' => __( 'Description of what this panel does.', 'bakery-patisserie-shop' ),
	) );

	// 404 PAGE
	$wp_customize->add_section('bakery_patisserie_shop_404_page_section',array(
		'title'         => __('404 Page', 'bakery-patisserie-shop'),
		'description'   => __('Here you can customize 404 Page content.', 'bakery-patisserie-shop'),
		'panel' => 'bakery_patisserie_shop_page_panel_id'
	) );

	$wp_customize->add_setting('bakery_patisserie_shop_edit_404_title',array(
		'default'=> __('Oops! That page cant be found.','bakery-patisserie-shop'),
		'sanitize_callback'	=> 'sanitize_text_field',
	));
	$wp_customize->add_control('bakery_patisserie_shop_edit_404_title',array(
		'label'	=> __('Edit Title','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_404_page_section',
		'type'=> 'text',
	));

	$wp_customize->add_setting('bakery_patisserie_shop_edit_404_text',array(
		'default'=> __('It looks like nothing was found at this location. Maybe try a search?','bakery-patisserie-shop'),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('bakery_patisserie_shop_edit_404_text',array(
		'label'	=> __('Edit Text','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_404_page_section',
		'type'=> 'text'
	));

	// Search Results
	$wp_customize->add_section('bakery_patisserie_shop_no_result_section',array(
		'title'         => __('Search Results', 'bakery-patisserie-shop'),
		'description'  => __('Here you can customize Search Result content.', 'bakery-patisserie-shop'),
		'panel' => 'bakery_patisserie_shop_page_panel_id'
	) );

	$wp_customize->add_setting('bakery_patisserie_shop_edit_no_result_title',array(
		'default'=> __('Nothing Found','bakery-patisserie-shop'),
		'sanitize_callback'	=> 'sanitize_text_field',
	));
	$wp_customize->add_control('bakery_patisserie_shop_edit_no_result_title',array(
		'label'	=> __('Edit Title','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_no_result_section',
		'type'=> 'text',
	));

	$wp_customize->add_setting('bakery_patisserie_shop_edit_no_result_text',array(
		'default'=> __('Sorry, but nothing matched your search terms. Please try again with some different keywords.','bakery-patisserie-shop'),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('bakery_patisserie_shop_edit_no_result_text',array(
		'label'	=> __('Edit Text','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_no_result_section',
		'type'=> 'text'
	));

	 // Header Image Height
    $wp_customize->add_setting(
        'bakery_patisserie_shop_header_image_height',
        array(
            'default'           => 500,
            'sanitize_callback' => 'absint',
        )
    );
    $wp_customize->add_control(
        'bakery_patisserie_shop_header_image_height',
        array(
            'label'       => esc_html__( 'Header Image Height', 'bakery-patisserie-shop' ),
            'section'     => 'header_image',
            'type'        => 'number',
            'description' => esc_html__( 'Control the height of the header image. Default is 350px.', 'bakery-patisserie-shop' ),
            'input_attrs' => array(
                'min'  => 220,
                'max'  => 1000,
                'step' => 1,
            ),
        )
    );

    // Header Background Position
    $wp_customize->add_setting(
        'bakery_patisserie_shop_header_background_position',
        array(
            'default'           => 'center',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    $wp_customize->add_control(
        'bakery_patisserie_shop_header_background_position',
        array(
            'label'       => esc_html__( 'Header Background Position', 'bakery-patisserie-shop' ),
            'section'     => 'header_image',
            'type'        => 'select',
            'choices'     => array(
                'top'    => esc_html__( 'Top', 'bakery-patisserie-shop' ),
                'center' => esc_html__( 'Center', 'bakery-patisserie-shop' ),
                'bottom' => esc_html__( 'Bottom', 'bakery-patisserie-shop' ),
            ),
            'description' => esc_html__( 'Choose how you want to position the header image.', 'bakery-patisserie-shop' ),
        )
    );

    // Header Image Parallax Effect
    $wp_customize->add_setting(
        'bakery_patisserie_shop_header_background_attachment',
        array(
            'default'           => 1,
            'sanitize_callback' => 'absint',
        )
    );
    $wp_customize->add_control(
        'bakery_patisserie_shop_header_background_attachment',
        array(
            'label'       => esc_html__( 'Header Image Parallax', 'bakery-patisserie-shop' ),
            'section'     => 'header_image',
            'type'        => 'checkbox',
            'description' => esc_html__( 'Add a parallax effect on page scroll.', 'bakery-patisserie-shop' ),
        )
    );

        //Opacity
	$wp_customize->add_setting('bakery_patisserie_shop_header_banner_opacity_color',array(
       'default'              => '0.5',
       'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
    $wp_customize->add_control( 'bakery_patisserie_shop_header_banner_opacity_color', array(
		'label'       => esc_html__( 'Header Image Opacity','bakery-patisserie-shop' ),
		'section'     => 'header_image',
		'type'        => 'select',
		'settings'    => 'bakery_patisserie_shop_header_banner_opacity_color',
		'choices' => array(
           '0' =>  esc_attr(__('0','bakery-patisserie-shop')),
           '0.1' =>  esc_attr(__('0.1','bakery-patisserie-shop')),
           '0.2' =>  esc_attr(__('0.2','bakery-patisserie-shop')),
           '0.3' =>  esc_attr(__('0.3','bakery-patisserie-shop')),
           '0.4' =>  esc_attr(__('0.4','bakery-patisserie-shop')),
           '0.5' =>  esc_attr(__('0.5','bakery-patisserie-shop')),
           '0.6' =>  esc_attr(__('0.6','bakery-patisserie-shop')),
           '0.7' =>  esc_attr(__('0.7','bakery-patisserie-shop')),
           '0.8' =>  esc_attr(__('0.8','bakery-patisserie-shop')),
           '0.9' =>  esc_attr(__('0.9','bakery-patisserie-shop'))
		), 
	) );

   $wp_customize->add_setting( 'bakery_patisserie_shop_header_banner_image_overlay', array(
	    'default'   => true,
	    'transport' => 'refresh',
	    'sanitize_callback' => 'bakery_patisserie_shop_sanitize_checkbox',
	));
	$wp_customize->add_control( new Bakery_Patisserie_Shop_Toggle_Control( $wp_customize, 'bakery_patisserie_shop_header_banner_image_overlay', array(
	    'label'   => esc_html__( 'Show / Hide Header Image Overlay', 'bakery-patisserie-shop' ),
	    'section' => 'header_image',
	)));

    $wp_customize->add_setting('bakery_patisserie_shop_header_banner_image_ooverlay_color', array(
		'default'           => '#000',
		'sanitize_callback' => 'sanitize_hex_color',
	));
	$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'bakery_patisserie_shop_header_banner_image_ooverlay_color', array(
		'label'    => __('Header Image Overlay Color', 'bakery-patisserie-shop'),
		'section'  => 'header_image',
	)));

    $wp_customize->add_setting(
        'bakery_patisserie_shop_header_image_title_font_size',
        array(
            'default'           => 40,
            'sanitize_callback' => 'absint',
        )
    );
    $wp_customize->add_control(
        'bakery_patisserie_shop_header_image_title_font_size',
        array(
            'label'       => esc_html__( 'Change Header Image Title Font Size', 'bakery-patisserie-shop' ),
            'section'     => 'header_image',
            'type'        => 'number',
            'description' => esc_html__( 'Control the font Size of the header image title. Default is 40px.', 'bakery-patisserie-shop' ),
            'input_attrs' => array(
                'min'  => 10,
                'max'  => 200,
                'step' => 1,
            ),
        )
    );

	$wp_customize->add_setting( 'bakery_patisserie_shop_header_image_title_text_color', array(
	    'default' => '',
	    'sanitize_callback' => 'sanitize_hex_color'
  	));
  	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bakery_patisserie_shop_header_image_title_text_color', array(
			'label'     => __('Change Header Image Title Color', 'bakery-patisserie-shop'),
	    'section' => 'header_image',
	    'settings' => 'bakery_patisserie_shop_header_image_title_text_color',
  	)));

  	//Woocommerce settings
	$wp_customize->add_section('bakery_patisserie_shop_woocommerce_section', array(
		'title'    => __('WooCommerce Options', 'bakery-patisserie-shop'),
		'priority' => null,
		'panel'    => 'woocommerce',
	));

	$wp_customize->add_setting('bakery_patisserie_shop_sale_tag_position',array(
        'default' => 'right',
        'sanitize_callback' => 'bakery_patisserie_shop_sanitize_choices'
	));
	$wp_customize->add_control('bakery_patisserie_shop_sale_tag_position',array(
        'type' => 'radio',
        'label'     => __('Sale Badge Position', 'bakery-patisserie-shop'),
        'description'   => __('This option work for Archieve Products', 'bakery-patisserie-shop'),
        'section' => 'bakery_patisserie_shop_woocommerce_section',
        'choices' => array(
            'left' => __('Left','bakery-patisserie-shop'),
            'right' => __('Right','bakery-patisserie-shop'),
        ),
	) );

  	$wp_customize->add_setting('bakery_patisserie_shop_woocommerce_sale_font_size',array(
		'default'=> '',
		'sanitize_callback'	=> 'absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_woocommerce_sale_font_size',array(
		'label'	=> __('Sale Font Size','bakery-patisserie-shop'),

		'section'=> 'bakery_patisserie_shop_woocommerce_section',
		'settings'    => 'bakery_patisserie_shop_woocommerce_sale_font_size',
		'type'        => 'number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 100,
		),
	));

	$wp_customize->add_setting('bakery_patisserie_shop_woocommerce_sale_padding_top_bottom',array(
		'default'=> '',
		'sanitize_callback'	=> 'absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_woocommerce_sale_padding_top_bottom',array(
		'label'	=> __('Sale Padding Top Bottom','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_woocommerce_section',
		'type'        => 'number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 100,
		),
	));

	$wp_customize->add_setting('bakery_patisserie_shop_woocommerce_sale_padding_left_right',array(
		'default'=> '',
		'sanitize_callback'	=> 'absint'
	));
	$wp_customize->add_control('bakery_patisserie_shop_woocommerce_sale_padding_left_right',array(
		'label'	=> __('Sale Padding Left Right','bakery-patisserie-shop'),
		'section'=> 'bakery_patisserie_shop_woocommerce_section',
		'type'        => 'number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 100,
		),
	));

	$wp_customize->add_setting( 'bakery_patisserie_shop_woocommerce_sale_border_radius', array(
		'default'              => '100',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'absint'
	) );
	$wp_customize->add_control( 'bakery_patisserie_shop_woocommerce_sale_border_radius', array(
		'label'       => esc_html__( 'Sale Border Radius','bakery-patisserie-shop' ),
		'section'     => 'bakery_patisserie_shop_woocommerce_section',
		'type'        => 'number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 100,
		),
	) );

}
add_action( 'customize_register', 'Bakery_Patisserie_Shop_Customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @since Bakery Patisserie Shop 1.0
 * @see Bakery_Patisserie_Shop_Customize_register()
 *
 * @return void
 */
function Bakery_Patisserie_Shop_Customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @since Bakery Patisserie Shop 1.0
 * @see Bakery_Patisserie_Shop_Customize_register()
 *
 * @return void
 */
function Bakery_Patisserie_Shop_Customize_partial_blogdescription() {
	bloginfo( 'description' );
}

if ( ! defined( 'BAKERY_PATISSERIE_SHOP_PRO_THEME_NAME' ) ) {
	define( 'BAKERY_PATISSERIE_SHOP_PRO_THEME_NAME', esc_html__( 'Bakery Patisserie Pro', 'bakery-patisserie-shop'));
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_PRO_THEME_URL' ) ) {
	define( 'BAKERY_PATISSERIE_SHOP_PRO_THEME_URL', esc_url('https://www.themespride.com/products/bakery-shop-wordpress-theme', 'bakery-patisserie-shop'));
}

if ( ! defined( 'BAKERY_PATISSERIE_SHOP_DOCS_URL' ) ) {
	define( 'BAKERY_PATISSERIE_SHOP_DOCS_URL', esc_url('https://page.themespride.com/demo/docs/bakery-and-patisserie-shop/'));
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_TEXT' ) ) {
    define( 'BAKERY_PATISSERIE_SHOP_TEXT', __( 'Bakery Patisserie Shop Pro','bakery-patisserie-shop' ));
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_BUY_TEXT' ) ) {
    define( 'BAKERY_PATISSERIE_SHOP_BUY_TEXT', __( 'Upgrade Pro','bakery-patisserie-shop' ));
}

add_action( 'customize_register', function( $manager ) {

// Load custom sections.
load_template( trailingslashit( get_template_directory() ) . '/inc/section-pro.php' );

    $manager->register_section_type( bakery_patisserie_shop_Button::class );

    $manager->add_section(
        new bakery_patisserie_shop_Button( $manager, 'bakery_patisserie_shop_pro', [
            'title'       => esc_html( BAKERY_PATISSERIE_SHOP_TEXT,'bakery-patisserie-shop' ),
            'priority'    => 0,
            'button_text' => __( 'GET PREMIUM', 'bakery-patisserie-shop' ),
            'button_url'  => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL )
        ] )
    );

    // Register sections.
	$manager->add_section(
		new bakery_patisserie_shop_Customize_Section_Pro(
			$manager,
			'bakery_patisserie_shop_documentation',
			array(
				'priority'   => 500,
				'title'    => esc_html__( 'Theme Documentation', 'bakery-patisserie-shop' ),
				'pro_text' => esc_html__( 'Click Here', 'bakery-patisserie-shop' ),
				'pro_url'  => esc_url( BAKERY_PATISSERIE_SHOP_DOCS_URL, 'bakery-patisserie-shop'),
			)
		)
	);

} );
/**
 * Singleton class for handling the theme's customizer integration.
 *
 * @since  1.0.0
 * @access public
 */
final class Bakery_Patisserie_Shop_Customize {

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Sets up initial actions.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function setup_actions() {

		// Register panels, sections, settings, controls, and partials.
		add_action( 'customize_register', array( $this, 'sections' ) );

		// Register scripts and styles for the controls.
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_control_scripts' ), 0 );
	}

	/**
	 * Sets up the customizer sections.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @return void
	 */
	public function sections( $manager ) {

		// Load custom sections.
		load_template( trailingslashit( get_template_directory() ) . '/inc/section-pro.php' );

		// Register custom section types.
		$manager->register_section_type( 'Bakery_Patisserie_Shop_Customize_Section_Pro' );

		// Register sections.
		$manager->add_section(
			new Bakery_Patisserie_Shop_Customize_Section_Pro(
				$manager,
				'bakery_patisserie_shop_section_pro',
				array(
					'priority'   => 9,
					'title'    => BAKERY_PATISSERIE_SHOP_PRO_THEME_NAME,
					'pro_text' => esc_html__( 'Upgrade Pro', 'bakery-patisserie-shop' ),
					'pro_url'  => esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL, 'bakery-patisserie-shop' ),
				)
			)
		);

	}
	/**
	 * Loads theme customizer CSS.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue_control_scripts() {

		wp_enqueue_script( 'bakery-patisserie-shop-customize-controls', trailingslashit( esc_url( get_template_directory_uri() ) ) . '/assets/js/customize-controls.js', array( 'customize-controls' ) );

		wp_enqueue_style( 'bakery-patisserie-shop-customize-controls', trailingslashit( esc_url( get_template_directory_uri() ) ) . '/assets/css/customize-controls.css' );
	}
}

// Doing this customizer thang!
Bakery_Patisserie_Shop_Customize::get_instance();