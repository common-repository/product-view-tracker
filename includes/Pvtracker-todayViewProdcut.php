<?php

if (!class_exists('Pvtracker_todayViewProdcut_Class')) {
    class Pvtracker_todayViewProdcut_Class {

        public function __construct() {
            add_action('admin_menu', array($this, 'register_view_handling_menu'));
        }


        public function register_view_handling_menu() {
            add_submenu_page(
                'most-viewed-products',
                __('Today\'s Viewed Products', 'pvt'),
                __('Today\'s Viewed Products', 'pvt'),
                'manage_options',
                'today-viewed-products',
                array($this, 'display_today_viewed_products')
            );
        }

                 /*
      *Today product view show table
      *version 1.2.4
     */
        public function display_today_viewed_products() {
            global $wpdb;

            $products_per_page = 10;
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($current_page - 1) * $products_per_page;
            $today_start = strtotime('today midnight', current_time('timestamp'));
            $today_end = strtotime('tomorrow midnight', current_time('timestamp')) - 1;

            $sql_total = "
                SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key = '_view_timestamps'
            ";

            $total_products = $wpdb->get_var($wpdb->prepare($sql_total, 'product', 'publish'));

            $sql = "
                SELECT p.ID as product_id, p.post_title
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key = '_view_timestamps'
                GROUP BY p.ID
                LIMIT %d OFFSET %d
            ";

            $results = $wpdb->get_results($wpdb->prepare($sql, 'product', 'publish', $products_per_page, $offset));

            echo '<div class="wrap plugin-table-wrapper">';
            echo '<h1>' . esc_html(__('Today\'s Viewed Products', 'pvt')) . '</h1>';
            echo '<table class="widefat fixed dataTable" cellspacing="0">';
            echo '<thead><tr><th class="product-name-column">' . esc_html(__('Product', 'pvt')) . '</th><th class="count-column">' . esc_html(__('Views Today', 'pvt')) . '</th></tr></thead><tbody>';

            $found = false;
            foreach ($results as $row) {
                $view_timestamps = get_post_meta($row->product_id, '_view_timestamps', true);

                if (is_array($view_timestamps)) {
                    $today_views = array_filter($view_timestamps, function($timestamp) use ($today_start, $today_end) {
                        return $timestamp >= $today_start && $timestamp <= $today_end;
                    });

                    $today_view_count = count($today_views);

                    if ($today_view_count > 0) {
                        $found = true;
                        $product_link = get_permalink($row->product_id);
                        $product_name = esc_html($row->post_title);

                        echo '<tr><td class="product-name-column"><a href="' . esc_url($product_link) . '">' . esc_html($product_name) . '</a></td><td class="count-column">' . intval($today_view_count) . '</td></tr>';
                    }
                }
            }

            if (!$found) {
                echo '<tr><td colspan="2">' . esc_html(__('No products viewed today.', 'pvt')) . '</td></tr>';
            }

            echo '</tbody></table>';

            // Pagination
            $total_pages = ceil($total_products / $products_per_page);

            if ($total_pages > 1) {
                echo '<div class="tablenav">';
                echo '<div class="tablenav-pages">';
                echo '<span class="pagination-links">';
                
                if ($current_page > 1) {
                    echo '<a class="prev-page" href="?paged=' . ($current_page - 1) . '">&laquo; Previous</a>';
                }

                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i === $current_page) {
                        echo '<span class="current-page">' . $i . '</span>';
                    } else {
                        echo '<a class="page-number" href="?paged=' . $i . '">' . $i . '</a>';
                    }
                }

                if ($current_page < $total_pages) {
                    echo '<a class="next-page" href="?paged=' . ($current_page + 1) . '">Next &raquo;</a>';
                }

                echo '</span>';
                echo '</div>';
                echo '</div>';
            }

            echo '</div>'; // Closing wrap div
        }


           /**
             * Product remove after 24 hours
             * version 1.2.4
             */

        public function cleanup_old_views() {
            global $wpdb;

            $cutoff_time = current_time('timestamp') - 86400;

            $sql = "
                SELECT p.ID as product_id
                FROM {$wpdb->posts} p
                WHERE p.post_type = %s AND p.post_status = %s
            ";

            $results = $wpdb->get_results($wpdb->prepare($sql, 'product', 'publish'));

            foreach ($results as $row) {
                $view_timestamps = get_post_meta($row->product_id, '_view_timestamps', true);

                if (is_array($view_timestamps)) {
                    $new_view_timestamps = array_filter($view_timestamps, function($timestamp) use ($cutoff_time) {
                        return $timestamp >= $cutoff_time;
                    });

                    update_post_meta($row->product_id, '_view_timestamps', $new_view_timestamps);
                }
            }
        }
    }
}

