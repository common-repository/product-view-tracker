<?php
/*
* Plugin Name: Product View Tracker
* Description: Shop can track his product view and know people interest.
* Plugin URI:  https://innovativeorbit.com/
* Author:      wahid sadik
* Version: 1.2.5
* Text Domain:product view tracker
* Domain Path: /languages
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/



/*
 *Security: Only allow admin access
*/


if (!defined('ABSPATH')) {


 /*
 *Exit if accessed directly
 */
    exit; 
}


include_once(plugin_dir_path(__FILE__) . 'includes/Pvtracker-productViewTracker-class.php');
include_once(plugin_dir_path(__FILE__) . 'includes/Pvtracker-todayViewProdcut.php');
include_once(plugin_dir_path(__FILE__) . 'includes/PVtracker-productViewMarketChannel.php');
include_once(plugin_dir_path(__FILE__) . 'includes/PVtracker-settings.php');




function pvt_enqueue_styles() {
    // Define the path to your stylesheet
    $style_url = plugin_dir_url(__FILE__) . 'css/style.css';
    
    // Define the version number (e.g., file modification time)
    $style_version = filemtime(plugin_dir_path(__FILE__) . 'css/style.css');
    
    // Enqueue the stylesheet with versioning
    wp_enqueue_style('pvt-styles', $style_url, array(), $style_version);
}
add_action('admin_enqueue_scripts', 'pvt_enqueue_styles');

/*
* Deactivation hook
*/
function pvt_deactivate($network_wide) {
    if (function_exists('is_multisite') && is_multisite()) {
        // Check if it is a network deactivation
        if ($network_wide) {
            // Get all blog ids
            global $wpdb;
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                
                restore_current_blog();
            }
        } 
    } 
}


register_deactivation_hook(__FILE__, 'pvt_deactivate');

/*
* translate multi language
* version 1.1.1
*/

function load_product_view_tracker_textdomain() {
    load_plugin_textdomain('pvt', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'load_product_view_tracker_textdomain');



/*
* Instantiate the tracker class to ensure hooks are added
*/
new Pvtracker_ProductViewTracker_Class();
new Pvtracker_todayViewProdcut_Class(); 
new PVtracker_productViewMarketChannel_Class();
new Pvtracker_settings_Class();