<?php
/**
 * Template part for displaying posts
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="box-info">
        <?php 
        $bakery_patisserie_shop_blog_archive_ordering = get_theme_mod('bakery_patisserie_shop_single_blog_meta_order', array('date', 'author', 'comment', 'category'));

        foreach ($bakery_patisserie_shop_blog_archive_ordering as $bakery_patisserie_shop_blog_data_order) : 
            if ('date' === $bakery_patisserie_shop_blog_data_order) : ?>
                <i class="<?php echo esc_attr(get_theme_mod('bakery_patisserie_shop_single_post_date_icon','far fa-calendar-alt mb-1 me-2')); ?>"></i>
                <span class="entry-date me-3"><?php echo get_the_date('j F, Y'); ?></span>
            <?php elseif ('author' === $bakery_patisserie_shop_blog_data_order) : ?>
                <i class="<?php echo esc_attr(get_theme_mod('bakery_patisserie_shop_single_post_author_icon','fas fa-user mb-1 me-2')); ?>"></i>
                <span class="entry-author me-3"><?php the_author(); ?></span>
            <?php elseif ('comment' === $bakery_patisserie_shop_blog_data_order) : ?>
                <i class="<?php echo esc_attr(get_theme_mod('bakery_patisserie_shop_single_post_comment_icon','fas fa-comments mb-1 me-2')); ?>"></i>
                <span class="entry-comments me-3"><?php comments_number(__('0 Comments', 'bakery-patisserie-shop'), __('0 Comments', 'bakery-patisserie-shop'), __('% Comments', 'bakery-patisserie-shop')); ?></span>
            <?php elseif ('category' === $bakery_patisserie_shop_blog_data_order) : ?>
                <i class="<?php echo esc_attr(get_theme_mod('bakery_patisserie_shop_single_post_category_icon','fas fa-list mb-1 me-2')); ?>"></i>
                <span class="entry-category me-3"><?php bakery_patisserie_shop_display_post_category_count(); ?></span>
            <?php elseif ('time' === $bakery_patisserie_shop_blog_data_order) : ?>
                <i class="<?php echo esc_attr(get_theme_mod('bakery_patisserie_shop_single_post_time_icon','fas fa-clock mb-1 me-2')); ?>"></i>
                <span class="entry-time me-3"><?php echo get_the_time('g:i a'); ?></span>

            <?php endif;
        endforeach; 
        ?>
    </div>
    <hr>
    <div class="box-content">
        <?php the_content(); ?>
        <?php if(get_theme_mod('bakery_patisserie_shop_remove_tags',true) != ''){ 
            $tags = get_the_tags(); // Retrieve the post's tags
             bakery_patisserie_shop_custom_output_tags(); 
        }?>

        <?php if(get_theme_mod('bakery_patisserie_shop_remove_category',true) != ''){ 
            if(has_category()){ 
                echo '<div class="post_category mt-3"> Category: ';
                the_category(', ');
                echo '</div>';
            }
        }?>
        <?php if( get_theme_mod( 'bakery_patisserie_shop_remove_comment',true) != ''){ 
        // If comments are open or we have at least one comment, load up the comment template
        if ( comments_open() || '0' != get_comments_number() )
        comments_template();
        }

        if ( is_singular( 'attachment' ) ) {
            // Parent post navigation.
            the_post_navigation( array(
                'prev_text' => _x( '<span class="meta-nav">Published in</span><span class="post-title">%title</span>', 'Parent post link', 'bakery-patisserie-shop' ),
            ) );
        } elseif ( is_singular( 'post' ) ) {
            // Previous/next post navigation.
            the_post_navigation( array(
                'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next:', 'bakery-patisserie-shop' ) . '</span> ' .
                    '<span class="post-title">%title</span>',
                'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous:', 'bakery-patisserie-shop' ) . '</span> ' .
                    '<span class="post-title">%title</span>',
            ) );
        } ?>
        <div class="clearfix"></div>
    </div>
      <div class="my-5"><?php get_template_part( 'template-parts/post/related-post'); ?></div>
</article>