<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

get_header(); ?>

<div class="box-image-page">
  	<div class="single-page-img"></div>
  	 <div class="box-text">
    	<h2><?php esc_html_e( '404', 'bakery-patisserie-shop' ); ?></h2>  
    </div> 
</div>

<div class="container">
	<main id="tp_content" role="main">
		<div id="primary" class="content-area">
			<section class="error-404 not-found">
				<h1 class="page-title text-center pt-3"><?php echo esc_html(get_theme_mod('bakery_patisserie_shop_edit_404_title',__('Oops! That page can&rsquo;t be found.','bakery-patisserie-shop')));?></h1>
				<div class="page-content text-center">
					<p class="py-3"><?php echo esc_html(get_theme_mod('bakery_patisserie_shop_edit_404_text',__('It looks like nothing was found at this location. Maybe try a search?','bakery-patisserie-shop')));?></p>
					<?php get_search_form(); ?>
				</div>
			</section>
		</div>
	</main>
</div>

<?php get_footer();