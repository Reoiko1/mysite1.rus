<?php
/*
* Display Logo and header details
*/
?>
<div class="main-header">
  <div class="menubox py-md-3 py-3<?php 
      if( get_theme_mod( 'bakery_patisserie_shop_sticky_header', true) != '' ) { 
          echo ' sticky-header'; 
      } else { 
          echo ' close-sticky'; 
      } 
    ?>">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-5 logo-col align-self-center">
            <div class="logo">
              <?php if( has_custom_logo() ) bakery_patisserie_shop_the_custom_logo(); ?>
              <?php if(get_theme_mod('bakery_patisserie_shop_site_title',true) == 1){ ?>
                <?php if (is_front_page() && is_home()) : ?>
                  <h1 class="text-capitalize">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
                  </h1> 
                <?php else : ?>
                  <p class="text-capitalize site-title mb-1">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
                  </p>
                <?php endif; ?>
              <?php }?>
              <?php $bakery_patisserie_shop_description = get_bloginfo( 'description', 'display' );
              if ( $bakery_patisserie_shop_description || is_customize_preview() ) : ?>
                <?php if(get_theme_mod('bakery_patisserie_shop_site_tagline',false)){ ?>
                  <p class="site-description mb-0"><?php echo esc_html($bakery_patisserie_shop_description); ?></p>
                <?php }?>
              <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5 col-md-2 align-self-center">
            <?php get_template_part('template-parts/navigation/site-nav'); ?>
        </div>
        <div class="col-lg-3 col-md-5 align-self-center btn-col">
            <div class="header-admin">
              <p class="mb-0">
                <?php if (class_exists('woocommerce')) : ?>
                  <span class="product-cart">
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" title="<?php esc_attr_e('Shopping Cart', 'bakery-patisserie-shop'); ?>" aria-label="<?php esc_attr_e('My Cart', 'bakery-patisserie-shop'); ?>">
                        <i class="fas fa-shopping-cart"></i>
                      <?php 
                      $bakery_patisserie_shop_cart_count = WC()->cart->get_cart_contents_count(); 
                      if ($bakery_patisserie_shop_cart_count > 0) : ?>
                          <span class="cart-count"><?php echo esc_html($bakery_patisserie_shop_cart_count); ?></span>
                      <?php endif; ?>
                    </a>
                  </span>
                <?php endif; ?>
              </p>
              <p class="mb-0">
                <?php if (class_exists('WooCommerce')): ?>
                    <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>">
                        <i class="<?php echo is_user_logged_in() ? 'fas' : 'far'; ?> fa-user" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
              </p>
             </div> 
            </div>
        </div>
      </div>
    </div>
  </div>
</div>