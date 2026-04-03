<?php
	
	$bakery_patisserie_shop_tp_theme_css = '';

	// 1st color
	$bakery_patisserie_shop_tp_color_option_first = get_theme_mod('bakery_patisserie_shop_tp_color_option_first', '#D95942');
	if ($bakery_patisserie_shop_tp_color_option_first) {
		$bakery_patisserie_shop_tp_theme_css .= ':root {';
		$bakery_patisserie_shop_tp_theme_css .= '--color-primary1: ' . esc_attr($bakery_patisserie_shop_tp_color_option_first) . ';';
		$bakery_patisserie_shop_tp_theme_css .= '}';
	}

	// 2ndcolor
	$bakery_patisserie_shop_tp_color_option_sec = get_theme_mod('bakery_patisserie_shop_tp_color_option_sec', '#407D7E');
	if ($bakery_patisserie_shop_tp_color_option_sec) {
		$bakery_patisserie_shop_tp_theme_css .= ':root {';
		$bakery_patisserie_shop_tp_theme_css .= '--color-primary3: ' . esc_attr($bakery_patisserie_shop_tp_color_option_sec) . ';';
		$bakery_patisserie_shop_tp_theme_css .= '}';
	}

	// preloader
	$bakery_patisserie_shop_tp_preloader_color1_option = get_theme_mod('bakery_patisserie_shop_tp_preloader_color1_option');
	if($bakery_patisserie_shop_tp_preloader_color1_option != false){
	$bakery_patisserie_shop_tp_theme_css .='.center1{';
		$bakery_patisserie_shop_tp_theme_css .='border-color: '.esc_attr($bakery_patisserie_shop_tp_preloader_color1_option).' !important;';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}
	if($bakery_patisserie_shop_tp_preloader_color1_option != false){
	$bakery_patisserie_shop_tp_theme_css .='.center1 .ring::before{';
		$bakery_patisserie_shop_tp_theme_css .='background: '.esc_attr($bakery_patisserie_shop_tp_preloader_color1_option).' !important;';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	$bakery_patisserie_shop_tp_preloader_color2_option = get_theme_mod('bakery_patisserie_shop_tp_preloader_color2_option');

	if($bakery_patisserie_shop_tp_preloader_color2_option != false){
	$bakery_patisserie_shop_tp_theme_css .='.center2{';
		$bakery_patisserie_shop_tp_theme_css .='border-color: '.esc_attr($bakery_patisserie_shop_tp_preloader_color2_option).' !important;';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}
	if($bakery_patisserie_shop_tp_preloader_color2_option != false){
	$bakery_patisserie_shop_tp_theme_css .='.center2 .ring::before{';
		$bakery_patisserie_shop_tp_theme_css .='background: '.esc_attr($bakery_patisserie_shop_tp_preloader_color2_option).' !important;';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	$bakery_patisserie_shop_tp_preloader_bg_color_option = get_theme_mod('bakery_patisserie_shop_tp_preloader_bg_color_option');

	if($bakery_patisserie_shop_tp_preloader_bg_color_option != false){
	$bakery_patisserie_shop_tp_theme_css .='.loader{';
		$bakery_patisserie_shop_tp_theme_css .='background: '.esc_attr($bakery_patisserie_shop_tp_preloader_bg_color_option).';';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	$bakery_patisserie_shop_tp_footer_bg_color_option = get_theme_mod('bakery_patisserie_shop_tp_footer_bg_color_option');


	if($bakery_patisserie_shop_tp_footer_bg_color_option != false){
	$bakery_patisserie_shop_tp_theme_css .='#footer{';
		$bakery_patisserie_shop_tp_theme_css .='background: '.esc_attr($bakery_patisserie_shop_tp_footer_bg_color_option).';';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	// logo tagline color
	$bakery_patisserie_shop_site_tagline_color = get_theme_mod('bakery_patisserie_shop_site_tagline_color');

	if($bakery_patisserie_shop_site_tagline_color != false){
	$bakery_patisserie_shop_tp_theme_css .='.logo h1 a, .logo p a, .logo p.site-title a{';
	$bakery_patisserie_shop_tp_theme_css .='color: '.esc_attr($bakery_patisserie_shop_site_tagline_color).';';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	$bakery_patisserie_shop_logo_tagline_color = get_theme_mod('bakery_patisserie_shop_logo_tagline_color');
	if($bakery_patisserie_shop_logo_tagline_color != false){
	$bakery_patisserie_shop_tp_theme_css .='p.site-description{';
	$bakery_patisserie_shop_tp_theme_css .='color: '.esc_attr($bakery_patisserie_shop_logo_tagline_color).';';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	// footer widget title color
	$bakery_patisserie_shop_footer_widget_title_color = get_theme_mod('bakery_patisserie_shop_footer_widget_title_color');
	if($bakery_patisserie_shop_footer_widget_title_color != false){
	$bakery_patisserie_shop_tp_theme_css .='#footer h3, #footer h2.wp-block-heading{';
	$bakery_patisserie_shop_tp_theme_css .='color: '.esc_attr($bakery_patisserie_shop_footer_widget_title_color).';';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	// copyright text color
	$bakery_patisserie_shop_footer_copyright_text_color = get_theme_mod('bakery_patisserie_shop_footer_copyright_text_color');
	if($bakery_patisserie_shop_footer_copyright_text_color != false){
	$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p, #footer .site-info a {';
	$bakery_patisserie_shop_tp_theme_css .='color: '.esc_attr($bakery_patisserie_shop_footer_copyright_text_color).'!important;';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	// header image title color
	$bakery_patisserie_shop_header_image_title_text_color = get_theme_mod('bakery_patisserie_shop_header_image_title_text_color');
	if($bakery_patisserie_shop_header_image_title_text_color != false){
	$bakery_patisserie_shop_tp_theme_css .='.box-text h2{';
	$bakery_patisserie_shop_tp_theme_css .='color: '.esc_attr($bakery_patisserie_shop_header_image_title_text_color).';';
	$bakery_patisserie_shop_tp_theme_css .='}';
	}

	// menu color
	$bakery_patisserie_shop_menu_color = get_theme_mod('bakery_patisserie_shop_menu_color');
	if($bakery_patisserie_shop_menu_color != false){
	$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
	$bakery_patisserie_shop_tp_theme_css .='color: '.esc_attr($bakery_patisserie_shop_menu_color).';';
	$bakery_patisserie_shop_tp_theme_css .='}';
}

//Footer Font Weight
$bakery_patisserie_shop_footer_copyright_title_font_weight = get_theme_mod( 'bakery_patisserie_shop_footer_copyright_title_font_weight','');
if($bakery_patisserie_shop_footer_copyright_title_font_weight == '100'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 100;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '200'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 200;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '300'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 300;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '400'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 400;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '500'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 500;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '600'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 600;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '700'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 700;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '800'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 800;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_copyright_title_font_weight == '900'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 900;';
$bakery_patisserie_shop_tp_theme_css .='}';
}