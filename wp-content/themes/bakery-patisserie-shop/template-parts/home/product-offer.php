<?php
/**
 * Template part for displaying slider section
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */

$bakery_patisserie_shop_aboutus = get_theme_mod('bakery_patisserie_shop_our_products_show_hide_section', true);

$bakery_patisserie_shop_default_bg = get_template_directory_uri() . '/assets/images/product-bg-cover.png'; // Replace with your default image path
$bakery_patisserie_shop_bg_image = get_theme_mod('bakery_patisserie_shop_product_section_bg_image', $bakery_patisserie_shop_default_bg);
$bakery_patisserie_shop_bg_style = 'style="background-image: url(' . esc_url($bakery_patisserie_shop_bg_image) . '); background-size: cover; background-position: center;"';

if ($bakery_patisserie_shop_aboutus == '1') :
?>
<section id="product-section" <?php echo $bakery_patisserie_shop_bg_style; ?>>
  <div class="container">
    <div class="sec-titles text-center">
      <?php
      $bakery_patisserie_shop_prod_heading = get_theme_mod('bakery_patisserie_shop_our_products_heading_section');
      if (!empty($bakery_patisserie_shop_prod_heading)) :
        $bakery_patisserie_shop_words = explode(' ', $bakery_patisserie_shop_prod_heading);
        if (count($bakery_patisserie_shop_words) >= 2) {
          $bakery_patisserie_shop_words[1] = '<span class="highlighted-word">' . esc_html($bakery_patisserie_shop_words[1]) . '</span>';
        }
        $bakery_patisserie_shop_final_heading = implode(' ', $bakery_patisserie_shop_words);
      ?>
        <h2 class="text-center product-heading">
          <?php echo wp_kses_post($bakery_patisserie_shop_final_heading); ?>
        </h2>
      <?php endif; ?>

      <?php
      $bakery_patisserie_shop_product_content = get_theme_mod('bakery_patisserie_shop_our_products_content');
      if (!empty($bakery_patisserie_shop_product_content)) : ?>
        <p class="text-center product-content">
          <?php echo esc_html($bakery_patisserie_shop_product_content); ?>
        </p>
      <?php endif; ?>
    </div>
    
    <?php if (class_exists('WooCommerce')) : ?>
    <!-- Owl Carousel products wrapper -->
    <div class="product-carousel owl-carousel owl-theme">
      <?php
      $bakery_patisserie_shop_selected_category = get_theme_mod('bakery_patisserie_shop_our_product_product_category', 'product_cat1');
      if ($bakery_patisserie_shop_selected_category) {
        $bakery_patisserie_shop_args = array(
          'post_type'      => 'product',
          'posts_per_page' => 50,
          'product_cat'    => sanitize_text_field($bakery_patisserie_shop_selected_category),
          'order'          => 'ASC'
        );
        $bakery_patisserie_shop_loop = new WP_Query($bakery_patisserie_shop_args);
        if ($bakery_patisserie_shop_loop->have_posts()) : 
          while ($bakery_patisserie_shop_loop->have_posts()) : $bakery_patisserie_shop_loop->the_post();
            $product = wc_get_product(get_the_ID());
      ?>
        <div class="item">
            <div class="product-image position-relative">
              <?php echo wp_kses_post($product->get_image()); ?>
            </div>
          <div class="product-box">
            <div class="product-content text-start">
              <h3 class="my-3"><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()); ?></a></h3>
              <div class="serv-tags">
                <?php
                  $bakery_patisserie_shop_product_tags = wp_get_post_terms(get_the_ID(), 'product_tag');
                  if ($bakery_patisserie_shop_product_tags && !is_wp_error($bakery_patisserie_shop_product_tags)) {
                    $bakery_patisserie_shop_display_tags = array_slice($bakery_patisserie_shop_product_tags, 0, 3);
                    echo '<div class="produ-tags">';
                    foreach ($bakery_patisserie_shop_display_tags as $bakery_patisserie_shop_i => $tag) {
                      echo '<a href="' . esc_url(get_term_link($tag->term_id, 'product_tag')) . '" class="me-2 mb-3 tag-link tag-link' . ($bakery_patisserie_shop_i + 1) . '">' . esc_html($tag->name) . '</a>';
                    }
                    echo '</div>';
                  }
                ?>
              </div>
              <p class="product-description">
                <?php echo esc_html( bakery_patisserie_shop_string_limit_words($product->get_description(), 20) ); ?>
              </p>

                <div class="bottom-cart">
                  <p class="my-3 product-price" style="color: <?php echo esc_attr($product->is_on_sale() ? 'black' : 'gray'); ?>">
                      <?php echo wp_kses_post($product->get_price_html()); ?>
                    </p>
                  <div class="cart-button">
                    <?php if ($product->is_type('simple')) { woocommerce_template_loop_add_to_cart(); } ?>
                  </div>
                </div>
            </div>
          </div>
        </div>
      <?php
          endwhile;
          wp_reset_postdata();
        endif;
      }
      ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>