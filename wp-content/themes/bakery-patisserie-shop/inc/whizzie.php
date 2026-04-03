<?php 
if (isset($_GET['import-demo']) && $_GET['import-demo'] == true) {
 
    
    // Function to install and activate plugins
    function bakery_patisserie_shop_import_demo_content() {

         // Display the preloader only for plugin installation
        echo '<div id="plugin-loader" style="display: flex; align-items: center; justify-content: center; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 9999;">
                <img src="' . esc_url(get_template_directory_uri()) . '/assets/images/loader.png" alt="Loading..." width="60" height="60" />
              </div>';

        // Define the plugins you want to install and activate
        $plugins = array(
            array(
                'slug' => 'woocommerce',
                'file' => 'woocommerce/woocommerce.php',
                'url'  => 'https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip'
            ),
             array(
                'slug' => 'yith-woocommerce-wishlist',
                'file' => 'yith-woocommerce-wishlist/init.php',
                'url'  => 'https://downloads.wordpress.org/plugin/yith-woocommerce-wishlist.latest-stable.zip'
            ),
            array(
                'slug' => 'advanced-appointment-booking-scheduling',
                'file' => 'advanced-appointment-booking-scheduling/advanced-appointment-booking.php',
                'url'  => 'https://downloads.wordpress.org/plugin/advanced-appointment-booking-scheduling.zip'
            ),
        );

        // Include required files for plugin installation
        include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        include_once(ABSPATH . 'wp-admin/includes/file.php');
        include_once(ABSPATH . 'wp-admin/includes/misc.php');
        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

        // Loop through each plugin
        foreach ($plugins as $plugin) {
            $plugin_file = WP_PLUGIN_DIR . '/' . $plugin['file'];

            // Check if the plugin is installed
            if (!file_exists($plugin_file)) {
                // If the plugin is not installed, download and install it
                $upgrader = new Plugin_Upgrader();
                $result = $upgrader->install($plugin['url']);

                // Check for installation errors
                if (is_wp_error($result)) {
                    error_log('Plugin installation failed: ' . $plugin['slug'] . ' - ' . $result->get_error_message());
                    echo 'Error installing plugin: ' . esc_html($plugin['slug']) . ' - ' . esc_html($result->get_error_message());
                    continue;
                }
            }

            // If the plugin exists but is not active, activate it
            if (file_exists($plugin_file) && !is_plugin_active($plugin['file'])) {
                $result = activate_plugin($plugin['file']);

                // Check for activation errors
                if (is_wp_error($result)) {
                    error_log('Plugin activation failed: ' . $plugin['slug'] . ' - ' . $result->get_error_message());
                    echo 'Error activating plugin: ' . esc_html($plugin['slug']) . ' - ' . esc_html($result->get_error_message());
                }
            }
        }

        // Hide the preloader after the process is complete
        echo '<script type="text/javascript">
                document.getElementById("plugin-loader").style.display = "none";
              </script>';

        // Add filter to skip WooCommerce setup wizard after activation
        add_filter('woocommerce_prevent_automatic_wizard_redirect', '__return_true');
    }

    // Call the import function
    bakery_patisserie_shop_import_demo_content();


    // ------- Create Nav Menu --------
$bakery_patisserie_shop_menuname = 'Main Menus';
$bakery_patisserie_shop_bpmenulocation = 'primary-menu';
$bakery_patisserie_shop_menu_exists = wp_get_nav_menu_object($bakery_patisserie_shop_menuname);

if (!$bakery_patisserie_shop_menu_exists) {
    $bakery_patisserie_shop_menu_id = wp_create_nav_menu($bakery_patisserie_shop_menuname);

    // Create Home Page
    $bakery_patisserie_shop_home_title = 'Home';
    $bakery_patisserie_shop_home = array(
        'post_type' => 'page',
        'post_title' => $bakery_patisserie_shop_home_title,
        'post_content' => '',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_slug' => 'home'
    );
    $bakery_patisserie_shop_home_id = wp_insert_post($bakery_patisserie_shop_home);

    // Assign Home Page Template
    add_post_meta($bakery_patisserie_shop_home_id, '_wp_page_template', 'page-template/front-page.php');

    // Update options to set Home Page as the front page
    update_option('page_on_front', $bakery_patisserie_shop_home_id);
    update_option('show_on_front', 'page');

    // Add Home Page to Menu
    wp_update_nav_menu_item($bakery_patisserie_shop_menu_id, 0, array(
        'menu-item-title' => __('Home', 'bakery-patisserie-shop'),
        'menu-item-classes' => 'home',
        'menu-item-url' => home_url('/'),
        'menu-item-status' => 'publish',
        'menu-item-object-id' => $bakery_patisserie_shop_home_id,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type'
    ));

    // Create About Us Page with Dummy Content
    $bakery_patisserie_shop_about_title = 'About Us';
    $bakery_patisserie_shop_about_content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...<br>

             Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960 with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br> 

                There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which dont look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isnt anything embarrassing hidden in the middle of text.<br> 

                All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.';
    $bakery_patisserie_shop_about = array(
        'post_type' => 'page',
        'post_title' => $bakery_patisserie_shop_about_title,
        'post_content' => $bakery_patisserie_shop_about_content,
        'post_status' => 'publish',
        'post_author' => 1,
        'post_slug' => 'about-us'
    );
    $bakery_patisserie_shop_about_id = wp_insert_post($bakery_patisserie_shop_about);

    // Add About Us Page to Menu
    wp_update_nav_menu_item($bakery_patisserie_shop_menu_id, 0, array(
        'menu-item-title' => __('About Us', 'bakery-patisserie-shop'),
        'menu-item-classes' => 'about-us',
        'menu-item-url' => home_url('/about-us/'),
        'menu-item-status' => 'publish',
        'menu-item-object-id' => $bakery_patisserie_shop_about_id,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type'
    ));

    // Create Services Page with Dummy Content
    $bakery_patisserie_shop_services_title = 'Services';
    $bakery_patisserie_shop_services_content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...<br>

             Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960 with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br> 

                There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which dont look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isnt anything embarrassing hidden in the middle of text.<br> 

                All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.';
    $bakery_patisserie_shop_services = array(
        'post_type' => 'page',
        'post_title' => $bakery_patisserie_shop_services_title,
        'post_content' => $bakery_patisserie_shop_services_content,
        'post_status' => 'publish',
        'post_author' => 1,
        'post_slug' => 'services'
    );
    $bakery_patisserie_shop_services_id = wp_insert_post($bakery_patisserie_shop_services);

    // Add Services Page to Menu
    wp_update_nav_menu_item($bakery_patisserie_shop_menu_id, 0, array(
        'menu-item-title' => __('Services', 'bakery-patisserie-shop'),
        'menu-item-classes' => 'services',
        'menu-item-url' => home_url('/services/'),
        'menu-item-status' => 'publish',
        'menu-item-object-id' => $bakery_patisserie_shop_services_id,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type'
    ));

    // Create Pages Page with Dummy Content
    $bakery_patisserie_shop_pages_title = 'Pages';
    $bakery_patisserie_shop_pages_content = '<h2>Our Pages</h2>
    <p>Explore all the pages we have on our website. Find information about our services, company, and more.</p>';
    $bakery_patisserie_shop_pages = array(
        'post_type' => 'page',
        'post_title' => $bakery_patisserie_shop_pages_title,
        'post_content' => $bakery_patisserie_shop_pages_content,
        'post_status' => 'publish',
        'post_author' => 1,
        'post_slug' => 'pages'
    );
    $bakery_patisserie_shop_pages_id = wp_insert_post($bakery_patisserie_shop_pages);

    // Add Pages Page to Menu
    wp_update_nav_menu_item($bakery_patisserie_shop_menu_id, 0, array(
        'menu-item-title' => __('Pages', 'bakery-patisserie-shop'),
        'menu-item-classes' => 'pages',
        'menu-item-url' => home_url('/pages/'),
        'menu-item-status' => 'publish',
        'menu-item-object-id' => $bakery_patisserie_shop_pages_id,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type'
    ));

    // Create Contact Page with Dummy Content
    $bakery_patisserie_shop_contact_title = 'Contact';
    $bakery_patisserie_shop_contact_content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...<br>

             Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960 with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br> 

                There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which dont look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isnt anything embarrassing hidden in the middle of text.<br> 

                All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.';
    $bakery_patisserie_shop_contact = array(
        'post_type' => 'page',
        'post_title' => $bakery_patisserie_shop_contact_title,
        'post_content' => $bakery_patisserie_shop_contact_content,
        'post_status' => 'publish',
        'post_author' => 1,
        'post_slug' => 'contact'
    );
    $bakery_patisserie_shop_contact_id = wp_insert_post($bakery_patisserie_shop_contact);

    // Add Contact Page to Menu
    wp_update_nav_menu_item($bakery_patisserie_shop_menu_id, 0, array(
        'menu-item-title' => __('Contact', 'bakery-patisserie-shop'),
        'menu-item-classes' => 'contact',
        'menu-item-url' => home_url('/contact/'),
        'menu-item-status' => 'publish',
        'menu-item-object-id' => $bakery_patisserie_shop_contact_id,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type'
    ));

    // Set the menu location if it's not already set
    if (!has_nav_menu($bakery_patisserie_shop_bpmenulocation)) {
        $locations = get_theme_mod('nav_menu_locations'); // Use 'nav_menu_locations' to get locations array
        if (empty($locations)) {
            $locations = array();
        }
        $locations[$bakery_patisserie_shop_bpmenulocation] = $bakery_patisserie_shop_menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
}

        
         // Slider Section
        set_theme_mod('bakery_patisserie_shop_slider_arrows', true);
        set_theme_mod('bakery_patisserie_shop_about_bg1', get_template_directory_uri().'/assets/images/slider-side1.png');
        set_theme_mod('bakery_patisserie_shop_about_bg2', get_template_directory_uri().'/assets/images/slider-side2.png');
        set_theme_mod('bakery_patisserie_shop_about_bg3', get_template_directory_uri().'/assets/images/slider-side3.png');
        set_theme_mod('bakery_patisserie_shop_about_bg4', get_template_directory_uri().'/assets/images/slider-side4.png');


        for ($i = 1; $i <= 3; $i++) {
            $bakery_patisserie_shop_title = 'Baked With Love, Served With Joy From Our Oven to Your Heart.';
            $bakery_patisserie_shop_content = 'Welcome to a world of freshly baked perfection where every treat is crafted with passion, tradition, and the finest ingredients. At our bakery, we believe that good food warms the soul, and that’s exactly what we aim to deliver in every bite. Whether you are craving a buttery croissant, a hearty loaf of bread.';

            // Create post object
            $my_post = array(
                'post_title'    => wp_strip_all_tags($bakery_patisserie_shop_title),
                'post_content'  => $bakery_patisserie_shop_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
            );

            /// Insert the post into the database
            $post_id = wp_insert_post($my_post);

            if ($post_id) {
                // Set the theme mod for the slider page
                set_theme_mod('bakery_patisserie_shop_slider_page' . $i, $post_id);

                $image_url = get_template_directory_uri() . '/assets/images/slider-img.png';
                $image_id = media_sideload_image($image_url, $post_id, null, 'id');

                if (!is_wp_error($image_id)) {
                    // Set the downloaded image as the post's featured image
                    set_post_thumbnail($post_id, $image_id);
                }
            }
        }

        // Best Seller Section Demo Import Example
        set_theme_mod('bakery_patisserie_shop_our_products_show_hide_section', true);
        set_theme_mod('bakery_patisserie_shop_our_products_heading_section', 'Bestseller Products');
        set_theme_mod('bakery_patisserie_shop_our_products_content', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec et lacus fringilla, imperdiet dolor at, porttitor sapien.');
        set_theme_mod('bakery_patisserie_shop_our_product_product_category', 'productcategory1');
        set_theme_mod('bakery_patisserie_shop_product_section_bg_image', get_template_directory_uri() . '/assets/images/product-bg-cover.png');

        // Define product category names and product titles
        $bakery_patisserie_shop_category_names = array('productcategory1');
        $bakery_patisserie_shop_title_array = array(
            array("Choco Charm", "Choco Charm", "Choco Charm"),
        );

        // Product tags array for each category group
        $bakery_patisserie_shop_tags_array = array(
            array('Cocoa', 'Cream', 'Vanilla'),  // tags for products in productcategory1
        );

        $bakery_patisserie_shop_content = "The top choice among all our customers, delicious, healthy and a part of an amazing breakfast!";

        foreach ($bakery_patisserie_shop_category_names as $bakery_patisserie_shop_index => $bakery_patisserie_shop_category_name) {
            // Create or retrieve the product category term ID
            $bakery_patisserie_shop_term = term_exists($bakery_patisserie_shop_category_name, 'product_cat');
            if ($bakery_patisserie_shop_term === 0 || $bakery_patisserie_shop_term === null) {
                $bakery_patisserie_shop_term = wp_insert_term($bakery_patisserie_shop_category_name, 'product_cat');
            }

            if (is_wp_error($bakery_patisserie_shop_term)) {
                error_log('Error creating category: ' . $bakery_patisserie_shop_term->get_error_message());
                continue; // Skip to next category if error
            }

            $bakery_patisserie_shop_term_id = is_array($bakery_patisserie_shop_term) ? $bakery_patisserie_shop_term['term_id'] : $bakery_patisserie_shop_term;

            // Loop to create 3 products for each category (adjust count to match titles count)
            for ($bakery_patisserie_shop_i = 0; $bakery_patisserie_shop_i < count($bakery_patisserie_shop_title_array[$bakery_patisserie_shop_index]); $bakery_patisserie_shop_i++) {

                $bakery_patisserie_shop_title = $bakery_patisserie_shop_title_array[$bakery_patisserie_shop_index][$bakery_patisserie_shop_i];

                // Create product post object
                $bakery_patisserie_shop_my_post = array(
                    'post_title'   => wp_strip_all_tags($bakery_patisserie_shop_title),
                    'post_content' => $bakery_patisserie_shop_content,
                    'post_status'  => 'publish',
                    'post_type'    => 'product',
                );

                // Insert the product into the database
                $bakery_patisserie_shop_post_id = wp_insert_post($bakery_patisserie_shop_my_post);

                if (is_wp_error($bakery_patisserie_shop_post_id)) {
                    error_log('Error creating product: ' . $bakery_patisserie_shop_post_id->get_error_message());
                    continue; // Skip this product if error
                }

                // Assign category to product
                wp_set_object_terms($bakery_patisserie_shop_post_id, (int)$bakery_patisserie_shop_term_id, 'product_cat');

                // Assign product tags
                $product_tags = isset($bakery_patisserie_shop_tags_array[$bakery_patisserie_shop_index]) 
                                ? $bakery_patisserie_shop_tags_array[$bakery_patisserie_shop_index] 
                                : array();
                wp_set_object_terms($bakery_patisserie_shop_post_id, $product_tags, 'product_tag');

                // Add product meta prices (remove $ symbol, must be numeric)
                update_post_meta($bakery_patisserie_shop_post_id, '_regular_price', 15);
                update_post_meta($bakery_patisserie_shop_post_id, '_sale_price', 15);
                update_post_meta($bakery_patisserie_shop_post_id, '_price', 15);

                // Handle featured image using media sideload
                $bakery_patisserie_shop_image_url = get_template_directory_uri() . '/assets/images/product-img' . ($bakery_patisserie_shop_i + 1) . '.png';
                $bakery_patisserie_shop_image_id = media_sideload_image($bakery_patisserie_shop_image_url, $bakery_patisserie_shop_post_id, null, 'id');

                if (is_wp_error($bakery_patisserie_shop_image_id)) {
                    error_log('Error downloading image: ' . $bakery_patisserie_shop_image_id->get_error_message());
                    continue; // Skip this product if image error
                }

                // Assign featured image to product
                set_post_thumbnail($bakery_patisserie_shop_post_id, $bakery_patisserie_shop_image_id);
            }
        }

    }
?>