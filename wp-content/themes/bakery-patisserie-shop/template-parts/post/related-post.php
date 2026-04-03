<?php

$bakery_patisserie_shop_post_args = array(
    'posts_per_page'    => get_theme_mod( 'bakery_patisserie_shop_related_post_per_page', 3 ),
    'orderby'           => 'rand',
    'post__not_in'      => array( get_the_ID() ),
);

$bakery_patisserie_shop_number_of_post_columns = get_theme_mod('bakery_patisserie_shop_related_post_per_columns', 3);

$bakery_patisserie_shop_col_lg_post_class = 'col-lg-' . (12 / $bakery_patisserie_shop_number_of_post_columns);

$bakery_patisserie_shop_related = wp_get_post_terms( get_the_ID(), 'category' );
$bakery_patisserie_shop_ids = array();
foreach( $bakery_patisserie_shop_related as $term ) {
    $bakery_patisserie_shop_ids[] = $term->term_id;
}

$bakery_patisserie_shop_post_args['category__in'] = $bakery_patisserie_shop_ids; 

$bakery_patisserie_shop_related_posts = new WP_Query( $bakery_patisserie_shop_post_args );

if ( $bakery_patisserie_shop_related_posts->have_posts() ) : ?>
        <div class="related-post-block">
        <h3 class="text-center mb-3"><?php echo esc_html(get_theme_mod('bakery_patisserie_shop_related_post_heading',__('Related Posts','bakery-patisserie-shop')));?></h3>
        <div class="row">
            <?php while ( $bakery_patisserie_shop_related_posts->have_posts() ) : $bakery_patisserie_shop_related_posts->the_post(); ?>
                <div class="<?php echo esc_attr($bakery_patisserie_shop_col_lg_post_class); ?> col-md-6">
                    <div id="category-post">
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <div class="page-box">
                                <?php if(has_post_thumbnail()) { ?>
                                        <?php the_post_thumbnail();  ?>    
                                <?php } ?>
                                <div class="box-content text-start">
                                    <h4 class="text-start py-2"><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php the_title_attribute(); ?>"><?php the_title();?></a></h4>
                                    
                                    <p><?php echo wp_trim_words(get_the_content(), get_theme_mod('bakery_patisserie_shop_excerpt_count',10) );?></p>
                                    <?php if(get_theme_mod('bakery_patisserie_shop_remove_read_button',true) != ''){ ?>
                                    <div class="readmore-btn text-start mb-1">
                                        <a href="<?php echo esc_url( get_permalink() );?>" class="blogbutton-small" title="<?php esc_attr_e( 'Read More', 'bakery-patisserie-shop' ); ?>"><?php echo esc_html(get_theme_mod('bakery_patisserie_shop_read_more_text',__('Read More','bakery-patisserie-shop')));?></a>
                                    </div>
                                    <?php }?>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </article>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif;
wp_reset_postdata();