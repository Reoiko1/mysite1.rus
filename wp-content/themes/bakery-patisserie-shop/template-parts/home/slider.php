<?php
/**
 * Template part for displaying slider section
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

$bakery_patisserie_shop_static_image = get_template_directory_uri() . '/assets/images/slider-img.png';
$bakery_patisserie_shop_show_slider = get_theme_mod('bakery_patisserie_shop_slider_arrows', true);

if ($bakery_patisserie_shop_show_slider) : ?>
  <section id="slider">

    <?php
    $bakery_patisserie_shop_bg1 = get_theme_mod('bakery_patisserie_shop_about_bg1');
    ?>
    <div class="image-wrapper1 <?php echo empty($bakery_patisserie_shop_bg1) ? 'no-image1' : ''; ?>">
      <?php if (!empty($bakery_patisserie_shop_bg1)) : ?>
        <img src="<?php echo esc_url($bakery_patisserie_shop_bg1); ?>" alt="<?php esc_attr_e('Background Image 1', 'bakery-patisserie-shop'); ?>" />
      <?php endif; ?>
    </div>

    <?php
    $bakery_patisserie_shop_bg2 = get_theme_mod('bakery_patisserie_shop_about_bg2');
    ?>
    <div class="image-wrapper2 <?php echo empty($bakery_patisserie_shop_bg2) ? 'no-image2' : ''; ?>">
      <?php if (!empty($bakery_patisserie_shop_bg2)) : ?>
        <img src="<?php echo esc_url($bakery_patisserie_shop_bg2); ?>" alt="<?php esc_attr_e('Background Image 2', 'bakery-patisserie-shop'); ?>" />
      <?php endif; ?>
    </div>
    <?php
    $bakery_patisserie_shop_bg3 = get_theme_mod('bakery_patisserie_shop_about_bg3');
    ?>
    <div class="image-wrapper3 <?php echo empty($bakery_patisserie_shop_bg3) ? 'no-image3' : ''; ?>">
      <?php if (!empty($bakery_patisserie_shop_bg3)) : ?>
        <img src="<?php echo esc_url($bakery_patisserie_shop_bg3); ?>" alt="<?php esc_attr_e('Background Image 3', 'bakery-patisserie-shop'); ?>" />
      <?php endif; ?>
    </div>
    <?php
    $bakery_patisserie_shop_bg4 = get_theme_mod('bakery_patisserie_shop_about_bg4');
    ?>
    <div class="image-wrapper4 <?php echo empty($bakery_patisserie_shop_bg4) ? 'no-image4' : ''; ?>">
      <?php if (!empty($bakery_patisserie_shop_bg4)) : ?>
        <img src="<?php echo esc_url($bakery_patisserie_shop_bg4); ?>" alt="<?php esc_attr_e('Background Image 4', 'bakery-patisserie-shop'); ?>" />
      <?php endif; ?>
    </div>
    <div class="container">
      <div class="owl-carousel owl-theme">
        <?php 
        $bakery_patisserie_shop_slide_pages = array();
        for ($bakery_patisserie_shop_i = 1; $bakery_patisserie_shop_i <= 4; $bakery_patisserie_shop_i++) {
          $page_id = absint(get_theme_mod('bakery_patisserie_shop_slider_page' . $bakery_patisserie_shop_i));
          if ($page_id) {
            $bakery_patisserie_shop_slide_pages[] = $page_id;
          }
        }

        if ($bakery_patisserie_shop_slide_pages) :
          $bakery_patisserie_shop_query = new WP_Query(array(
            'post_type' => 'page',
            'post__in' => $bakery_patisserie_shop_slide_pages,
            'orderby' => 'post__in'
          ));
          if ($bakery_patisserie_shop_query->have_posts()) :
            while ($bakery_patisserie_shop_query->have_posts()) : $bakery_patisserie_shop_query->the_post(); ?>
              <div class="item">
                <div class="carousel-caption">
                  <div class="inner_carousel">
                    <div class="slider-img mb-2">
                      <?php if (has_post_thumbnail()) : ?>
                        <img src="<?php the_post_thumbnail_url('full'); ?>" alt="<?php esc_attr_e('Slider Image', 'bakery-patisserie-shop'); ?>" />
                      <?php else : ?>
                        <img src="<?php echo esc_url($bakery_patisserie_shop_static_image); ?>" alt="<?php esc_attr_e('Slider Image', 'bakery-patisserie-shop'); ?>" />
                      <?php endif; ?>
                    </div>
                    <?php if (get_theme_mod('bakery_patisserie_shop_show_slider_title', true)) : 
                      $bakery_patisserie_shop_title = get_the_title();
                      $bakery_patisserie_shop_words = explode(' ', $bakery_patisserie_shop_title);
                      $bakery_patisserie_shop_highlighted_words = [];
                      foreach ($bakery_patisserie_shop_words as $bakery_patisserie_shop_index => $bakery_patisserie_shop_word) {
                        
                        if ($bakery_patisserie_shop_index >= 3 && $bakery_patisserie_shop_index <= 5) {
                          $bakery_patisserie_shop_highlighted_words[] = '<span class="highlighted-word">' . esc_html($bakery_patisserie_shop_word) . '</span>';
                        } else {
                          $bakery_patisserie_shop_highlighted_words[] = esc_html($bakery_patisserie_shop_word);
                        }
                      }
                      $bakery_patisserie_shop_final_title = implode(' ', $bakery_patisserie_shop_highlighted_words);
                    ?>
                      <h1 class="mb-md-2 mb-0 mt-md-4 my-3"><a href="<?php the_permalink(); ?>"><?php echo $bakery_patisserie_shop_final_title; ?></a></h1>
                    <?php endif; ?>
                    <?php if (get_theme_mod('bakery_patisserie_shop_show_slider_content', true)) : ?>
                      <p class="mb-0 slide-content">
                        <?php $bakery_patisserie_shop_excerpt = get_the_excerpt(); echo esc_html( bakery_patisserie_shop_string_limit_words( $bakery_patisserie_shop_excerpt, esc_attr(get_theme_mod('bakery_patisserie_shop_slider_excerpt_length','53')))); ?>
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endwhile;
            wp_reset_postdata();
          else : ?>
            <div class="no-postfound"><?php esc_html_e('No posts found', 'bakery-patisserie-shop'); ?></div>
          <?php endif;
        endif; ?>
      </div>
    </div>
    <div class="clearfix"></div>
  </section>
<?php endif; ?>