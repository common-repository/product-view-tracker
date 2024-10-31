<?php

if (!class_exists('Pvtracker_ProductViewTracker_Class')) {
    class Pvtracker_ProductViewTracker_Class {
                
        /**
         * __construct
         *
         * 
         * 
         * 
         * 
         * @return void
         */
        public function __construct() {
            add_action('wp', array($this, 'track_product_views'));
            add_action('admin_menu', array($this, 'register_most_viewed_products_menu'));
            add_action('woocommerce_add_to_cart', array($this, 'track_add_to_cart'));
        }
                        
        /**
         * track_product_views
         * track product view by product_id
         * @return void
         */
        public function track_product_views() {
            if (is_singular('product')) {
                global $post;
                $product_id = $post->ID;
        
                if (current_user_can('administrator')) {
                    return; // Do not count views for admin users
                }
        
                // Get current timestamp
                $current_time = current_time('timestamp');
                
                // Retrieve the current view timestamps from the post meta
                $view_timestamps = get_post_meta($product_id, '_view_timestamps', true);
        
                if (!is_array($view_timestamps)) {
                    $view_timestamps = array();
                }
        
                // Add the current time to the array
                $view_timestamps[] = $current_time;
                update_post_meta($product_id, '_view_timestamps', $view_timestamps);
            }


              



        }
        

        /**
         * track_add_to_cart
         * Track add to cart by product_id
         * @param string $cart_item_key
         * @return void
         */
        public function track_add_to_cart($cart_item_key) {
            $cart = WC()->cart->get_cart();
            if (isset($cart[$cart_item_key])) {
                $product_id = $cart[$cart_item_key]['product_id'];
                $add_to_cart_count = get_post_meta($product_id, '_add_to_cart_count', true);
                $add_to_cart_count = $add_to_cart_count ? intval($add_to_cart_count) + 1 : 1;
                update_post_meta($product_id, '_add_to_cart_count', $add_to_cart_count);
            }
        }
                
        /**
         * register_most_viewed_products_menu
         * register admin menu
         * @return void
         */
        public function register_most_viewed_products_menu() {
            add_menu_page(
                __('Most Viewed Products', 'pvt'),
                __('Most Viewed Products', 'pvt'),
                'manage_options',
                'most-viewed-products',
                array($this, 'display_most_viewed_products'),
                'dashicons-visibility',
                6
            );
        
        }
        
                
        /**
         * display_most_viewed_products
         * Product view and add to cart show in table
         * @return void
         */
        public function display_most_viewed_products() {
            global $wpdb;
        
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $items_per_page = 10;
        
            $categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ));

            // Cache key
            $cache_key_total_items = 'pvt_total_items';
            $total_items = wp_cache_get($cache_key_total_items, 'pvt');
            if ($total_items === false) {
                $base_sql = "
                    SELECT COUNT(*) FROM {$wpdb->posts} p
                    WHERE p.post_type = %s AND p.post_status = %s
                ";
                $total_items = $wpdb->get_var($wpdb->prepare($base_sql, 'product', 'publish'));
                wp_cache_set($cache_key_total_items, $total_items, 'pvt', 3600); // Cache for 1 hour
            }

            $total_pages = ceil($total_items / $items_per_page);
            $offset = ($current_page - 1) * $items_per_page;

            // Prepare SQL query with placeholders
            $sql = $wpdb->prepare("
                SELECT p.ID as product_id, p.post_title,
                COALESCE(pm1.meta_value, 0) as view_count,
                COALESCE(pm2.meta_value, 0) as add_to_cart_count
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
                LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
                WHERE p.post_type = %s AND p.post_status = %s
            ", '_view_count', '_add_to_cart_count', 'product', 'publish');

            // Check if nonce exists and is valid
            if (isset($_POST['pvt_search_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pvt_search_nonce'])), 'pvt_search_action')) {
                if (!empty($_POST['product_search'])) {
                    $search_query = sanitize_text_field($_POST['product_search']);
                    $sql .= $wpdb->prepare(" AND p.post_title LIKE %s", '%' . $wpdb->esc_like($search_query) . '%');
                }
        
                if (!empty($_POST['product_category'])) {
                    $category_id = intval($_POST['product_category']);
                    $sql .= $wpdb->prepare(" AND p.ID IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d)", $category_id);
                }
            }
        
            $sql .= $wpdb->prepare(" ORDER BY pm1.meta_value+0 DESC, pm2.meta_value+0 DESC LIMIT %d OFFSET %d", $items_per_page, $offset);
        
            $results = $wpdb->get_results($sql);

            // Cache key for category views
            $cache_key_category_views = 'pvt_category_views';
            $category_views = wp_cache_get($cache_key_category_views, 'pvt');
            if ($category_views === false) {
                $category_views_sql = "
                    SELECT tt.term_id, tt.name, SUM(pm.meta_value+0) as total_views
                    FROM {$wpdb->terms} tt
                    LEFT JOIN {$wpdb->term_taxonomy} ttt ON tt.term_id = ttt.term_id
                    LEFT JOIN {$wpdb->term_relationships} tr ON ttt.term_taxonomy_id = tr.term_taxonomy_id
                    LEFT JOIN {$wpdb->postmeta} pm ON tr.object_id = pm.post_id AND pm.meta_key = %s
                    WHERE ttt.taxonomy = %s
                    GROUP BY tt.term_id
                    ORDER BY total_views DESC
                    LIMIT 10
                ";
                $category_views = $wpdb->get_results($wpdb->prepare($category_views_sql, '_view_count', 'product_cat'));
                wp_cache_set($cache_key_category_views, $category_views, 'pvt', 3600); // Cache for 1 hour
            }
        
            echo '<div class="wrap plugin-table-wrapper">';
            echo '<h1>' . esc_html(__('Most Viewed Products', 'pvt')) . '</h1>';
            echo '<form method="post" action="">';
            wp_nonce_field('pvt_search_action', 'pvt_search_nonce');
            echo '<input type="text" name="product_search" placeholder="' . esc_attr(__('Search Product', 'pvt')) . '" />';
            echo '<select name="product_category">';
            echo '<option value="">' . esc_html(__('Select Category', 'pvt')) . '</option>';
            foreach ($categories as $category) {
                echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
            }
            echo '</select>';
            echo '<input type="submit" value="' . esc_attr(__('Search', 'pvt')) . '" class="button" />';
            echo '</form>';
        
            echo '<table class="widefat fixed dataTable" cellspacing="0">';
            echo '<caption>' . esc_html(__('Product Views and Add to Cart', 'pvt')) . '</caption>';
            echo '<thead><tr><th class="product-name-column">' . esc_html(__('Product', 'pvt')) . '</th><th class="count-column">' . esc_html(__('Views', 'pvt')) . '</th><th class="count-column">' . esc_html(__('Add to Cart', 'pvt')) . '</th></tr></thead><tbody>';

            if (!empty($results)) {
                foreach ($results as $row) {
                    $product_link = get_permalink($row->product_id);
                    $product_name = esc_html($row->post_title);
                    echo '<tr><td class="product-name-column"><a href="' . esc_url($product_link) . '">' . esc_html($product_name) . '</a></td><td class="count-column">' . intval($row->view_count) . '</td><td class="count-column">' . intval($row->add_to_cart_count) . '</td></tr>';
                }
            } else {
                echo '<tr><td colspan="3">' . esc_html(__('No products found.', 'pvt')) . '</td></tr>';
            }
        
            echo '</tbody></table>';

            /**
             * Table pagination count
             */
            if ($total_pages > 1) {
                echo '<div class="tablenav">';
                echo '<div class="tablenav-pages">';
                echo paginate_links(array(
                    'base' => esc_url(add_query_arg('paged', '%#%')),
                    'format' => '',
                    'prev_text' => esc_html(__('&laquo; Previous', 'pvt')),
                    'next_text' => esc_html(__('Next &raquo;', 'pvt')),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'type' => 'list',
                    'mid_size' => 2,
                ));
                echo '</div>';
                echo '</div>';
            }
        }

    

   
        
          
    










    }











    
}
