<?php
/**
 * The template for displaying the footer
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

?>

<footer id="footer" class="site-footer" role="contentinfo">
	<?php
		get_template_part( 'template-parts/footer/footer', 'widgets' );

		get_template_part( 'template-parts/footer/site', 'info' );
	?>
			<div class="return-to-header">
				<a href="javascript:" id="return-to-top"><i class="<?php echo esc_attr(get_theme_mod('bakery_patisserie_shop_return_icon','fas fa-arrow-up')); ?>"></i></a>
			</div>
</footer>
<?php wp_footer(); ?>

</body>
</html>
