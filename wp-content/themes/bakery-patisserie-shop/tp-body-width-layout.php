<?php

$bakery_patisserie_shop_tp_theme_css = '';

$bakery_patisserie_shop_theme_lay = get_theme_mod( 'bakery_patisserie_shop_tp_body_layout_settings','Full');
if($bakery_patisserie_shop_theme_lay == 'Container'){
$bakery_patisserie_shop_tp_theme_css .='body{';
$bakery_patisserie_shop_tp_theme_css .='max-width: 1140px; width: 100%; padding-right: 15px; padding-left: 15px; margin-right: auto; margin-left: auto;';
$bakery_patisserie_shop_tp_theme_css .='}';
$bakery_patisserie_shop_tp_theme_css .='@media screen and (max-width:575px){';
$bakery_patisserie_shop_tp_theme_css .='body{';
	$bakery_patisserie_shop_tp_theme_css .='max-width: 100%; padding-right:0px; padding-left: 0px';
$bakery_patisserie_shop_tp_theme_css .='} }';
$bakery_patisserie_shop_tp_theme_css .='.scrolled{';
$bakery_patisserie_shop_tp_theme_css .='width: auto; left:0; right:0;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_theme_lay == 'Container Fluid'){
$bakery_patisserie_shop_tp_theme_css .='body{';
$bakery_patisserie_shop_tp_theme_css .='width: 100%;padding-right: 15px;padding-left: 15px;margin-right: auto;margin-left: auto;';
$bakery_patisserie_shop_tp_theme_css .='}';
$bakery_patisserie_shop_tp_theme_css .='@media screen and (max-width:575px){';
$bakery_patisserie_shop_tp_theme_css .='body{';
	$bakery_patisserie_shop_tp_theme_css .='max-width: 100%; padding-right:0px; padding-left:0px';
$bakery_patisserie_shop_tp_theme_css .='} }';
$bakery_patisserie_shop_tp_theme_css .='.scrolled{';
$bakery_patisserie_shop_tp_theme_css .='width: auto; left:0; right:0;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_theme_lay == 'Full'){
$bakery_patisserie_shop_tp_theme_css .='body{';
$bakery_patisserie_shop_tp_theme_css .='max-width: 100%;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_scroll_position = get_theme_mod( 'bakery_patisserie_shop_scroll_top_position','Right');
if($bakery_patisserie_shop_scroll_position == 'Right'){
$bakery_patisserie_shop_tp_theme_css .='#return-to-top{';
$bakery_patisserie_shop_tp_theme_css .='right: 20px;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_scroll_position == 'Left'){
$bakery_patisserie_shop_tp_theme_css .='#return-to-top{';
$bakery_patisserie_shop_tp_theme_css .='left: 20px;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_scroll_position == 'Center'){
$bakery_patisserie_shop_tp_theme_css .='#return-to-top{';
$bakery_patisserie_shop_tp_theme_css .='right: 50%;left: 50%;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

// related post
$bakery_patisserie_shop_related_post_mob = get_theme_mod('bakery_patisserie_shop_related_post_mob', true);
$bakery_patisserie_shop_related_post = get_theme_mod('bakery_patisserie_shop_remove_related_post', true);
$bakery_patisserie_shop_tp_theme_css .= '.related-post-block {';
if ($bakery_patisserie_shop_related_post == false) {
    $bakery_patisserie_shop_tp_theme_css .= 'display: none;';
}
$bakery_patisserie_shop_tp_theme_css .= '}';
$bakery_patisserie_shop_tp_theme_css .= '@media screen and (max-width: 575px) {';
if ($bakery_patisserie_shop_related_post == false || $bakery_patisserie_shop_related_post_mob == false) {
    $bakery_patisserie_shop_tp_theme_css .= '.related-post-block { display: none; }';
}
$bakery_patisserie_shop_tp_theme_css .= '}';

// slider btn
$bakery_patisserie_shop_slider_buttom_mob = get_theme_mod('bakery_patisserie_shop_slider_buttom_mob', true);
$bakery_patisserie_shop_slider_button = get_theme_mod('bakery_patisserie_shop_slider_button', true);
$bakery_patisserie_shop_tp_theme_css .= '#main-slider .more-btn {';
if ($bakery_patisserie_shop_slider_button == false) {
    $bakery_patisserie_shop_tp_theme_css .= 'display: none;';
}
$bakery_patisserie_shop_tp_theme_css .= '}';
$bakery_patisserie_shop_tp_theme_css .= '@media screen and (max-width: 575px) {';
if ($bakery_patisserie_shop_slider_button == false || $bakery_patisserie_shop_slider_buttom_mob == false) {
    $bakery_patisserie_shop_tp_theme_css .= '#main-slider .more-btn { display: none; }';
}
$bakery_patisserie_shop_tp_theme_css .= '}';

//return to header mobile               
$bakery_patisserie_shop_return_to_header_mob = get_theme_mod('bakery_patisserie_shop_return_to_header_mob', true);
$bakery_patisserie_shop_return_to_header = get_theme_mod('bakery_patisserie_shop_return_to_header', true);
$bakery_patisserie_shop_tp_theme_css .= '.return-to-header{';
if ($bakery_patisserie_shop_return_to_header == false) {
    $bakery_patisserie_shop_tp_theme_css .= 'display: none;';
}
$bakery_patisserie_shop_tp_theme_css .= '}';
$bakery_patisserie_shop_tp_theme_css .= '@media screen and (max-width: 575px) {';
if ($bakery_patisserie_shop_return_to_header == false || $bakery_patisserie_shop_return_to_header_mob == false) {
    $bakery_patisserie_shop_tp_theme_css .= '.return-to-header{ display: none; }';
}
$bakery_patisserie_shop_tp_theme_css .= '}';

//blog description              
$bakery_patisserie_shop_mobile_blog_description = get_theme_mod('bakery_patisserie_shop_mobile_blog_description', true);
$bakery_patisserie_shop_tp_theme_css .= '@media screen and (max-width: 575px) {';
if ($bakery_patisserie_shop_mobile_blog_description == false) {
    $bakery_patisserie_shop_tp_theme_css .= '.blog-description{ display: none; }';
}
$bakery_patisserie_shop_tp_theme_css .= '}';


$bakery_patisserie_shop_footer_widget_image = get_theme_mod('bakery_patisserie_shop_footer_widget_image');
if($bakery_patisserie_shop_footer_widget_image != false){
$bakery_patisserie_shop_tp_theme_css .='#footer{';
$bakery_patisserie_shop_tp_theme_css .='background: url('.esc_attr($bakery_patisserie_shop_footer_widget_image).');';
$bakery_patisserie_shop_tp_theme_css .='}';
}

//Social icon Font size
$bakery_patisserie_shop_social_icon_fontsize = get_theme_mod('bakery_patisserie_shop_social_icon_fontsize');
$bakery_patisserie_shop_tp_theme_css .='.social-media a i{';
$bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_social_icon_fontsize).'px;';
$bakery_patisserie_shop_tp_theme_css .='}';

// site title and tagline font size option
$bakery_patisserie_shop_site_title_font_size = get_theme_mod('bakery_patisserie_shop_site_title_font_size', ''); {
$bakery_patisserie_shop_tp_theme_css .='.logo h1 a, .logo p a{';
$bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_site_title_font_size).'px !important;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_site_tagline_font_size = get_theme_mod('bakery_patisserie_shop_site_tagline_font_size', '');{
$bakery_patisserie_shop_tp_theme_css .='.logo p{';
$bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_site_tagline_font_size).'px;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_related_product = get_theme_mod('bakery_patisserie_shop_related_product',true);
if($bakery_patisserie_shop_related_product == false){
$bakery_patisserie_shop_tp_theme_css .='.related.products{';
	$bakery_patisserie_shop_tp_theme_css .='display: none;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

//menu font size
$bakery_patisserie_shop_menu_font_size = get_theme_mod('bakery_patisserie_shop_menu_font_size', '');{
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a, .main-navigation li.page_item_has_children:after, .main-navigation li.menu-item-has-children:after{';
	$bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_menu_font_size).'px;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

// menu text transform
$bakery_patisserie_shop_menu_text_tranform = get_theme_mod( 'bakery_patisserie_shop_menu_text_tranform','');
if($bakery_patisserie_shop_menu_text_tranform == 'Uppercase'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a {';
	$bakery_patisserie_shop_tp_theme_css .='text-transform: uppercase;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_text_tranform == 'Lowercase'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a {';
	$bakery_patisserie_shop_tp_theme_css .='text-transform: lowercase;';
$bakery_patisserie_shop_tp_theme_css .='}';
}
else if($bakery_patisserie_shop_menu_text_tranform == 'Capitalize'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a {';
	$bakery_patisserie_shop_tp_theme_css .='text-transform: capitalize;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

//sale position
$bakery_patisserie_shop_scroll_position = get_theme_mod( 'bakery_patisserie_shop_sale_tag_position','right');
if($bakery_patisserie_shop_scroll_position == 'right'){
$bakery_patisserie_shop_tp_theme_css .='.woocommerce ul.products li.product .onsale{';
    $bakery_patisserie_shop_tp_theme_css .='right: 25px !important;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_scroll_position == 'left'){
$bakery_patisserie_shop_tp_theme_css .='.woocommerce ul.products li.product .onsale{';
    $bakery_patisserie_shop_tp_theme_css .='left: 25px !important; right: auto !important;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_woocommerce_sale_font_size = get_theme_mod('bakery_patisserie_shop_woocommerce_sale_font_size');
if($bakery_patisserie_shop_woocommerce_sale_font_size != false){
    $bakery_patisserie_shop_tp_theme_css .='.woocommerce ul.products li.product .onsale, .woocommerce span.onsale{';
        $bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_woocommerce_sale_font_size).'px;';
    $bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_woocommerce_sale_padding_top_bottom = get_theme_mod('bakery_patisserie_shop_woocommerce_sale_padding_top_bottom');
if($bakery_patisserie_shop_woocommerce_sale_padding_top_bottom != false){
    $bakery_patisserie_shop_tp_theme_css .='.woocommerce ul.products li.product .onsale, .woocommerce span.onsale{';
        $bakery_patisserie_shop_tp_theme_css .='padding-top: '.esc_attr($bakery_patisserie_shop_woocommerce_sale_padding_top_bottom).'px; padding-bottom: '.esc_attr($bakery_patisserie_shop_woocommerce_sale_padding_top_bottom).'px;';
    $bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_woocommerce_sale_padding_left_right = get_theme_mod('bakery_patisserie_shop_woocommerce_sale_padding_left_right');
if($bakery_patisserie_shop_woocommerce_sale_padding_left_right != false){
    $bakery_patisserie_shop_tp_theme_css .='.woocommerce ul.products li.product .onsale, .woocommerce span.onsale{';
        $bakery_patisserie_shop_tp_theme_css .='padding-left: '.esc_attr($bakery_patisserie_shop_woocommerce_sale_padding_left_right).'px !Important; padding-right: '.esc_attr($bakery_patisserie_shop_woocommerce_sale_padding_left_right).'px !important;';
    $bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_woocommerce_sale_border_radius = get_theme_mod('bakery_patisserie_shop_woocommerce_sale_border_radius', 100);
if($bakery_patisserie_shop_woocommerce_sale_border_radius != false){
    $bakery_patisserie_shop_tp_theme_css .='.woocommerce ul.products li.product .onsale, .woocommerce span.onsale{';
        $bakery_patisserie_shop_tp_theme_css .='border-radius: '.esc_attr($bakery_patisserie_shop_woocommerce_sale_border_radius).'% !important;';
    $bakery_patisserie_shop_tp_theme_css .='}';
}

//Font Weight
$bakery_patisserie_shop_menu_font_weight = get_theme_mod( 'bakery_patisserie_shop_menu_font_weight','');
if($bakery_patisserie_shop_menu_font_weight == '100'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 100;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '200'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 200;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '300'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 300;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '400'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 400;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '500'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 500;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '600'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 600;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '700'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 700;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '800'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 800;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_menu_font_weight == '900'){
$bakery_patisserie_shop_tp_theme_css .='.main-navigation a{';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 900;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

/*------------- Blog Page------------------*/
$bakery_patisserie_shop_post_image_round = get_theme_mod('bakery_patisserie_shop_post_image_round', 0);
if($bakery_patisserie_shop_post_image_round != false){
    $bakery_patisserie_shop_tp_theme_css .='.blog .box-image img{';
        $bakery_patisserie_shop_tp_theme_css .='border-radius: '.esc_attr($bakery_patisserie_shop_post_image_round).'px;';
    $bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_post_image_width = get_theme_mod('bakery_patisserie_shop_post_image_width', '');
if($bakery_patisserie_shop_post_image_width != false){
    $bakery_patisserie_shop_tp_theme_css .='.blog .box-image img{';
        $bakery_patisserie_shop_tp_theme_css .='Width: '.esc_attr($bakery_patisserie_shop_post_image_width).'px;';
    $bakery_patisserie_shop_tp_theme_css .='}';
}

$bakery_patisserie_shop_post_image_length = get_theme_mod('bakery_patisserie_shop_post_image_length', '');
if($bakery_patisserie_shop_post_image_length != false){
    $bakery_patisserie_shop_tp_theme_css .='.blog .box-image img{';
        $bakery_patisserie_shop_tp_theme_css .='height: '.esc_attr($bakery_patisserie_shop_post_image_length).'px;';
    $bakery_patisserie_shop_tp_theme_css .='}';
}

// footer widget title font size
$bakery_patisserie_shop_footer_widget_title_font_size = get_theme_mod('bakery_patisserie_shop_footer_widget_title_font_size', '');{
$bakery_patisserie_shop_tp_theme_css .='#footer h3, #footer h2.wp-block-heading{';
    $bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_footer_widget_title_font_size).'px;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

// Copyright text font size
$bakery_patisserie_shop_footer_copyright_font_size = get_theme_mod('bakery_patisserie_shop_footer_copyright_font_size', '');{
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p{';
    $bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_footer_copyright_font_size).'px;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

// copyright padding
$bakery_patisserie_shop_footer_copyright_top_bottom_padding = get_theme_mod('bakery_patisserie_shop_footer_copyright_top_bottom_padding', '');
if ($bakery_patisserie_shop_footer_copyright_top_bottom_padding !== '') { 
    $bakery_patisserie_shop_tp_theme_css .= '.site-info {';
    $bakery_patisserie_shop_tp_theme_css .= 'padding-top: ' . esc_attr($bakery_patisserie_shop_footer_copyright_top_bottom_padding) . 'px;';
    $bakery_patisserie_shop_tp_theme_css .= 'padding-bottom: ' . esc_attr($bakery_patisserie_shop_footer_copyright_top_bottom_padding) . 'px;';
    $bakery_patisserie_shop_tp_theme_css .= '}';
}

// copyright position
$bakery_patisserie_shop_copyright_text_position = get_theme_mod( 'bakery_patisserie_shop_copyright_text_position','Center');
if($bakery_patisserie_shop_copyright_text_position == 'Center'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p{';
$bakery_patisserie_shop_tp_theme_css .='text-align:center;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_copyright_text_position == 'Left'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p{';
$bakery_patisserie_shop_tp_theme_css .='text-align:left;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_copyright_text_position == 'Right'){
$bakery_patisserie_shop_tp_theme_css .='#footer .site-info p{';
$bakery_patisserie_shop_tp_theme_css .='text-align:right;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

// Header Image title font size
$bakery_patisserie_shop_header_image_title_font_size = get_theme_mod('bakery_patisserie_shop_header_image_title_font_size', '40');{
$bakery_patisserie_shop_tp_theme_css .='.box-text h2{';
    $bakery_patisserie_shop_tp_theme_css .='font-size: '.esc_attr($bakery_patisserie_shop_header_image_title_font_size).'px;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

/*--------------------------- banner image Opacity -------------------*/
    $bakery_patisserie_shop_theme_lay = get_theme_mod( 'bakery_patisserie_shop_header_banner_opacity_color','0.5');
        if($bakery_patisserie_shop_theme_lay == '0'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.1'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.1';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.2'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.2';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.3'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.3';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.4'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.4';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.5'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.5';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.6'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.6';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.7'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.7';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.8'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.8';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '0.9'){
            $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:0.9';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }else if($bakery_patisserie_shop_theme_lay == '1'){
            $bakery_patisserie_shop_tp_theme_css .='#main-slider img{';
                $bakery_patisserie_shop_tp_theme_css .='opacity:1';
            $bakery_patisserie_shop_tp_theme_css .='}';
        }

    $bakery_patisserie_shop_header_banner_image_overlay = get_theme_mod('bakery_patisserie_shop_header_banner_image_overlay', true);
    if($bakery_patisserie_shop_header_banner_image_overlay == false){
        $bakery_patisserie_shop_tp_theme_css .='.single-page-img, .featured-image{';
            $bakery_patisserie_shop_tp_theme_css .='opacity:1;';
        $bakery_patisserie_shop_tp_theme_css .='}';
    }

    $bakery_patisserie_shop_header_banner_image_ooverlay_color = get_theme_mod('bakery_patisserie_shop_header_banner_image_ooverlay_color', true);
    if($bakery_patisserie_shop_header_banner_image_ooverlay_color != false){
        $bakery_patisserie_shop_tp_theme_css .='.box-image-page{';
            $bakery_patisserie_shop_tp_theme_css .='background-color: '.esc_attr($bakery_patisserie_shop_header_banner_image_ooverlay_color).';';
        $bakery_patisserie_shop_tp_theme_css .='}';
    }

    // Slider Height
    $bakery_patisserie_shop_slider_img_height      = get_theme_mod('bakery_patisserie_shop_slider_img_height');
    $bakery_patisserie_shop_slider_img_height_resp = get_theme_mod('bakery_patisserie_shop_slider_img_height_responsive');

    // Desktop height
    $bakery_patisserie_shop_tp_theme_css .= '@media screen and (min-width: 768px) {';
    $bakery_patisserie_shop_tp_theme_css .= '#slider {';
    if ( $bakery_patisserie_shop_slider_img_height ) {
        $bakery_patisserie_shop_tp_theme_css .= 'height: ' . esc_attr( $bakery_patisserie_shop_slider_img_height ) . ';';
    }
    $bakery_patisserie_shop_tp_theme_css .= 'width: 100%; object-fit: cover;';
    $bakery_patisserie_shop_tp_theme_css .= '}';
    $bakery_patisserie_shop_tp_theme_css .= '}';

    // Mobile height
    $bakery_patisserie_shop_tp_theme_css .= '@media screen and (max-width: 767px) {';
    $bakery_patisserie_shop_tp_theme_css .= '#slider {';
    if ( $bakery_patisserie_shop_slider_img_height_resp ) {
        $bakery_patisserie_shop_tp_theme_css .= 'height: ' . esc_attr( $bakery_patisserie_shop_slider_img_height_resp ) . ' !important;';
    }
    $bakery_patisserie_shop_tp_theme_css .= 'width: 100%; object-fit: cover;';
    $bakery_patisserie_shop_tp_theme_css .= '}';
    $bakery_patisserie_shop_tp_theme_css .= '}';

      // header
    $bakery_patisserie_shop_slider_arrows = get_theme_mod('bakery_patisserie_shop_slider_arrows', true);
    if($bakery_patisserie_shop_slider_arrows == false){
    $bakery_patisserie_shop_tp_theme_css .='.page-template-front-page .menubox{';
        $bakery_patisserie_shop_tp_theme_css .='position:static; padding-bottom: 4rem;';
    $bakery_patisserie_shop_tp_theme_css .='}';
    }

    /*-----------------------------Responsive Setting --------------------*/
    $bakery_patisserie_shop_stickyheader = get_theme_mod( 'bakery_patisserie_shop_responsive_sticky_header',false);
    if($bakery_patisserie_shop_stickyheader == true && get_theme_mod( 'bakery_patisserie_shop_sticky_header') == false){
        $bakery_patisserie_shop_tp_theme_css .='.fixed-header{';
            $bakery_patisserie_shop_tp_theme_css .='position:static;';
        $bakery_patisserie_shop_tp_theme_css .='} ';
    }

    
// footer widget letter case
$bakery_patisserie_shop_footer_widget_title_text_tranform = get_theme_mod( 'bakery_patisserie_shop_footer_widget_title_text_tranform','');
if($bakery_patisserie_shop_footer_widget_title_text_tranform == 'Uppercase'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='text-transform: uppercase;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_text_tranform == 'Lowercase'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='text-transform: lowercase;';
$bakery_patisserie_shop_tp_theme_css .='}';
}
else if($bakery_patisserie_shop_footer_widget_title_text_tranform == 'Capitalize'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='text-transform: capitalize;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

//Footer Font Weight
$bakery_patisserie_shop_footer_widget_title_font_weight = get_theme_mod( 'bakery_patisserie_shop_footer_widget_title_font_weight','');
if($bakery_patisserie_shop_footer_widget_title_font_weight == '100'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 100;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '200'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 200;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '300'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 300;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '400'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 400;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '500'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 500;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '600'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 600;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '700'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 700;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '800'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 800;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_font_weight == '900'){
$bakery_patisserie_shop_tp_theme_css .='#footer h2, #footer h3, #footer h1.wp-block-heading, #footer h2.wp-block-heading, #footer h3.wp-block-heading, #footer h4.wp-block-heading, #footer h5.wp-block-heading, #footer h6.wp-block-heading {';
    $bakery_patisserie_shop_tp_theme_css .='font-weight: 900;';
$bakery_patisserie_shop_tp_theme_css .='}';
}

// footer widget position
$bakery_patisserie_shop_footer_widget_title_position = get_theme_mod( 'bakery_patisserie_shop_footer_widget_title_position','');
if($bakery_patisserie_shop_footer_widget_title_position == 'Right'){
$bakery_patisserie_shop_tp_theme_css .='#footer aside.widget-area{';
$bakery_patisserie_shop_tp_theme_css .='text-align: right;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_position == 'Left'){
$bakery_patisserie_shop_tp_theme_css .='#footer aside.widget-area{';
$bakery_patisserie_shop_tp_theme_css .='text-align: left;';
$bakery_patisserie_shop_tp_theme_css .='}';
}else if($bakery_patisserie_shop_footer_widget_title_position == 'Center'){
$bakery_patisserie_shop_tp_theme_css .='#footer aside.widget-area{';
$bakery_patisserie_shop_tp_theme_css .='text-align: center;';
$bakery_patisserie_shop_tp_theme_css .='}';
}