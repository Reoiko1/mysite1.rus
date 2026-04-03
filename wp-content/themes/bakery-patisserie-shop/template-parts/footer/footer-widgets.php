<?php
/**
 * Displays footer widgets if assigned
 *
 * @package Bakery Patisserie Shop
 * @subpackage bakery_patisserie_shop
 */
?>
<?php

// Determine the number of columns dynamically for the footer (you can replace this with your logic).
$bakery_patisserie_shop_no_of_footer_col = get_theme_mod('bakery_patisserie_shop_footer_columns', 4); // Change this value as needed.

// Calculate the Bootstrap class for large screens (col-lg-X) for footer.
$bakery_patisserie_shop_col_lg_footer_class = 'col-lg-' . (12 / $bakery_patisserie_shop_no_of_footer_col);

// Calculate the Bootstrap class for medium screens (col-md-X) for footer.
$bakery_patisserie_shop_col_md_footer_class = 'col-md-' . (12 / $bakery_patisserie_shop_no_of_footer_col);
?>
<div class="container">
    <aside class="widget-area row py-2 pt-3" role="complementary" aria-label="<?php esc_attr_e( 'Footer', 'bakery-patisserie-shop' ); ?>">
        <?php
        $bakery_patisserie_shop_default_widgets = array(
            1 => 'search',
            2 => 'archives',
            3 => 'meta',
            4 => 'categories'
        );

        for ($bakery_patisserie_shop_i = 1; $bakery_patisserie_shop_i <= $bakery_patisserie_shop_no_of_footer_col; $bakery_patisserie_shop_i++) :
            $bakery_patisserie_shop_lg_class = esc_attr($bakery_patisserie_shop_col_lg_footer_class);
            $bakery_patisserie_shop_md_class = esc_attr($bakery_patisserie_shop_col_md_footer_class);
            echo '<div class="col-12 ' . $bakery_patisserie_shop_lg_class . ' ' . $bakery_patisserie_shop_md_class . '">';

            if (is_active_sidebar('footer-' . $bakery_patisserie_shop_i)) {
                dynamic_sidebar('footer-' . $bakery_patisserie_shop_i);
            } else {
                // Display default widget content if not active.
                switch ($bakery_patisserie_shop_default_widgets[$bakery_patisserie_shop_i] ?? '') {
                    case 'search':
                        ?>
                        <aside class="widget" role="complementary" aria-label="<?php esc_attr_e('Search', 'bakery-patisserie-shop'); ?>">
                            <h3 class="widget-title"><?php esc_html_e('Search', 'bakery-patisserie-shop'); ?></h3>
                            <?php get_search_form(); ?>
                        </aside>
                        <?php
                        break;

                    case 'archives':
                        ?>
                        <aside class="widget" role="complementary" aria-label="<?php esc_attr_e('Archives', 'bakery-patisserie-shop'); ?>">
                            <h3 class="widget-title"><?php esc_html_e('Archives', 'bakery-patisserie-shop'); ?></h3>
                            <ul><?php wp_get_archives(['type' => 'monthly']); ?></ul>
                        </aside>
                        <?php
                        break;

                    case 'meta':
                        ?>
                        <aside class="widget" role="complementary" aria-label="<?php esc_attr_e('Meta', 'bakery-patisserie-shop'); ?>">
                            <h3 class="widget-title"><?php esc_html_e('Meta', 'bakery-patisserie-shop'); ?></h3>
                            <ul>
                                <?php wp_register(); ?>
                                <li><?php wp_loginout(); ?></li>
                                <?php wp_meta(); ?>
                            </ul>
                        </aside>
                        <?php
                        break;

                    case 'categories':
                        ?>
                        <aside class="widget" role="complementary" aria-label="<?php esc_attr_e('Categories', 'bakery-patisserie-shop'); ?>">
                            <h3 class="widget-title"><?php esc_html_e('Categories', 'bakery-patisserie-shop'); ?></h3>
                            <ul><?php wp_list_categories(['title_li' => '']); ?></ul>
                        </aside>
                        <?php
                        break;
                }
            }

            echo '</div>';
        endfor;
        ?>
    </aside>
</div>