<?php

if (!class_exists('Pvtracker_settings_Class')) {
    class Pvtracker_settings_Class {

        public function __construct() {
            add_action('admin_init', array($this, 'pvtracker_register_settings'));
            add_action('admin_menu', array($this, 'pvtracker_add_settings_page'));
        }

        function pvtracker_add_settings_page() {
            add_submenu_page(
                'most-viewed-products', 
                'PV Tracker Settings', 
                'PV Tracker Settings', 
                'manage_options', 
                'pvtracker-settings', 
                 [$this, 'pvtracker_settings_page']
            );
        }

        function pvtracker_register_settings() {
            register_setting('pvtracker_settings_group', 'pvtracker_utm_sources');
        }

        function pvtracker_settings_page() {
            ?>
            <div class="wrap">
                <h1>Product View Tracker - UTM Sources</h1>
                <form method="post" action="options.php">
                    <?php settings_fields('pvtracker_settings_group'); ?>
                    <?php do_settings_sections('pvtracker_settings_group'); ?>

                    <table class="form-table">
                        <tr valign="top">
                        <p scope="row"><b>Example:<b>google_shopping,facebook_shopping,google,facebook,googlsShop</p>
                            <th scope="row">UTM Sources to Track (comma separated)</th>
                           
                            <td><input type="text" name="pvtracker_utm_sources" value="<?php echo esc_attr(get_option('pvtracker_utm_sources')); ?>" /></td>
                          
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        // Remove cleanup_old_views from here if not relevant to settings
    }
}

