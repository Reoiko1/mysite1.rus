<?php
/**
 * The template for displaying comments
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php
	// You can start editing here -- including this comment!
	if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			$bakery_patisserie_shop_comments_number = get_comments_number();

			if ( '1' === $bakery_patisserie_shop_comments_number ) {
				/* translators: %s: Post title. */
				printf(
					esc_html__( 'One Reply to &ldquo;%s&rdquo;', 'bakery-patisserie-shop' ),
					'<span>' . esc_html( get_the_title() ) . '</span>'
				);
			} else {
				/* translators: 1: Number of comments, 2: Post title. */
				printf(
					esc_html(
						_nx(
							'%1$s Reply to &ldquo;%2$s&rdquo;',
							'%1$s Replies to &ldquo;%2$s&rdquo;',
							$bakery_patisserie_shop_comments_number,
							'comments title',
							'bakery-patisserie-shop'
						)
					),
					number_format_i18n( $bakery_patisserie_shop_comments_number ),
					'<span>' . esc_html( get_the_title() ) . '</span>'
				);
			}
			?>
		</h2>
		<ol class="comment-list">
			<?php
				wp_list_comments( array(
					'avatar_size' => 100,
					'style'       => 'ol',
					'short_ping'  => true,
				) );
			?>
		</ol>

		<?php the_comments_pagination( array(
			'prev_text' => '<span class="screen-reader-text">' . __( 'Previous', 'bakery-patisserie-shop' ) . '</span>',
			'next_text' => '<span class="screen-reader-text">' . __( 'Next', 'bakery-patisserie-shop' ) . '</span>',
		) );

	endif; // Check for have_comments().

	// If comments are closed and there are comments, let's leave a little note, shall we?
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>

		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'bakery-patisserie-shop' ); ?></p>
	<?php
	endif;

	comment_form();
	?>

</div>
