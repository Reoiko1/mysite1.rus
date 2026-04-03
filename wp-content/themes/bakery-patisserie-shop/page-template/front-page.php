<?php
/**
 * Template Name: Custom Home Page
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

get_header(); ?>

<main id="tp_content" role="main">
	<?php do_action( 'bakery_patisserie_shop_before_slider' ); ?>

	<?php get_template_part( 'template-parts/home/slider' ); ?>
	<?php do_action( 'bakery_patisserie_shop_after_slider' ); ?>

	<?php get_template_part( 'template-parts/home/product-offer' ); ?>
	<?php do_action( 'bakery_patisserie_shop_after_product-offer' ); ?>

	<?php get_template_part( 'template-parts/home/home-content' ); ?>
	<?php do_action( 'bakery_patisserie_shop_after_home_content' ); ?>
</main>

<?php get_footer();