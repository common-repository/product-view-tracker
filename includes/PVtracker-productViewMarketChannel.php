<?php

if (!class_exists('PVtracker_productViewMarketChannel_Class')) {
    class PVtracker_productViewMarketChannel_Class {

        public function __construct() {
            add_action('admin_menu', array($this, 'pvtracker_add_submenu_page'));
            add_action('wp', array($this, 'pvtracker_track_product_views'));
       
        }

        function pvtracker_add_submenu_page() {
            add_submenu_page(
                'most-viewed-products',    // Parent slug 
                'View from Channel',      // Page title
                'View from Channel',      // Menu title
                'manage_options',         // Capability
                'view_from_channel',      // Menu slug
                array($this, 'pvtracker_view_from_channel_page')  // Callback function
            );

 }

        function pvtracker_track_product_views() {
            if (is_singular('product')) {
                global $post;
                $product_id = $post->ID;

                $utm_source = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : 'organic';

                $product_views = get_post_meta($product_id, '_pvtracker_views', true);
                if (!$product_views) {
                    $product_views = [];
                }

                if (isset($product_views[$utm_source])) {
                    $product_views[$utm_source]++;
                } else {
                    $product_views[$utm_source] = 1;
                }

                update_post_meta($product_id, '_pvtracker_views', $product_views);
            }
        }

       

        function pvtracker_view_from_channel_page() {
            $utm_sources_to_track = explode(',', get_option('pvtracker_utm_sources', ''));

            $args = array('post_type' => 'product', 'posts_per_page' => -1);
            $products = get_posts($args);

            ?>
            <div class="wrap">
                <h1>View from Channel</h1>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Channel Source</th>
                            <th>View Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $product_views = get_post_meta($product->ID, '_pvtracker_views', true);
                            if ($product_views) {
                                foreach ($product_views as $utm_source => $view_count) {
                                    if (in_array($utm_source, $utm_sources_to_track)) {
                                        echo '<tr>';
                                        echo '<td>' . esc_html(get_the_title($product)) . '</td>';
                                        echo '<td>' . esc_html($utm_source) . '</td>';
                                        echo '<td>' . esc_html($view_count) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                            }
                            ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

    }
}


