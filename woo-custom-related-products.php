<?php
/**
 * Plugin Name:       Woo Custom Related Products
 * Plugin URI:        http://www.wpcodelibrary.com/
 * Description:       Woo Custom Related Products for WooCommerce allows you to choose related products for the particular product.
 * Version:           1.0.0
 * Author:            WPCodelibrary
 * Author URI:        http://www.wpcodelibrary.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-custom-related-products
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


if (!class_exists('Woo_Custom_Related_Products')) {

    /**
     * Plugin main class.
     *
     * @package Woo_Custom_Related_Products
     */
    class Woo_Custom_Related_Products {

        /**
         * Plugin version.
         *
         * @var string
         */
        const VERSION = '1.0.0';

        /**
         * Instance of this class.
         *
         * @var object
         */
        protected static $instance = null;

        /**
         * Initialize the plugin public actions.
         */
        private function __construct() {
            add_action('init', array($this, 'wcrp_load_plugin_textdomain'));
            add_filter('woocommerce_related_products_args', array($this, 'wcrp_filter_related_products'));
            add_action('woocommerce_process_product_meta', array($this, 'wcrp_save_related_products'), 10, 2);
            add_action('woocommerce_product_options_related', array($this, 'wcrp_select_related_products'));
        }

        /**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance() {
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Load the plugin text domain for translation.
         */
        public function wcrp_load_plugin_textdomain() {
            load_plugin_textdomain('woo-custom-related-products', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        function wcrp_filter_related_products($args) {
            global $post;
            $related = get_post_meta($post->ID, '_wcrp_related_ids', true);
            if (isset($related) && !empty($related)) { // remove category based filtering
                $args['post__in'] = $related;
            } elseif (get_option('wcrp_empty_behavior') == 'none') { // don't show any products
                $args['post__in'] = array(0);
            }

            return $args;
        }

        /**
         * Save related products on product edit screen
         */
        function wcrp_save_related_products($post_id, $post) {
            global $woocommerce;
            if (isset($_POST['wcrp_related_ids'])) {
                if ($woocommerce->version >= '2.3') {
                    $related = isset($_POST['wcrp_related_ids']) ? array_filter(array_map('intval', explode(',', $_POST['wcrp_related_ids']))) : array();
                } else {
                    $related = array();
                    $ids = $_POST['wcrp_related_ids'];
                    foreach ($ids as $id) {
                        if ($id && $id > 0) {
                            $related[] = $id;
                        }
                    }
                }
                update_post_meta($post_id, '_wcrp_related_ids', $related);
            } else {
                delete_post_meta($post_id, '_wcrp_related_ids');
            }
        }
        /**
         * Add relatd products selector to edit product section
         */
       
        function wcrp_select_related_products() {
            global $post, $woocommerce;
            $product_ids = array_filter(array_map('absint', (array) get_post_meta($post->ID, '_wcrp_related_ids', true)));
            ?>
            <div class="options_group">
                <?php if ($woocommerce->version >= '2.3') : ?>
                    <p class="form-field"><label for="related_ids"><?php _e('Custom Related Products', 'woocommerce'); ?></label>
                        <input type="hidden" class="wc-product-search" style="width: 50%;" id="wcrp_related_ids" name="wcrp_related_ids" data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-selected="<?php
                $json_ids = array();
                foreach ($product_ids as $product_id) {
                    $product = wc_get_product($product_id);
                    if (is_object($product) && is_callable(array($product, 'get_formatted_name'))) {
                        $json_ids[$product_id] = wp_kses_post($product->get_formatted_name());
                    }
                }
                echo esc_attr(json_encode($json_ids));
                    ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" /> <img class="help_tip" data-tip='<?php _e('Related products are displayed on the product detail page.', 'woocommerce') ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </p>
                <?php else: ?>
                    <p class="form-field"><label for="related_ids"><?php _e('Related Products', 'woocommerce'); ?></label>
                        <select id="related_ids" name="wcrp_related_ids[]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>">
                            <?php
                            foreach ($product_ids as $product_id) {

                                $product = get_product($product_id);

                                if ($product)
                                    echo '<option value="' . esc_attr($product_id) . '" selected="selected">' . esc_html($product->get_formatted_name()) . '</option>';
                            }
                            ?>
                        </select> <img class="help_tip" data-tip='<?php _e('Related products are displayed on the product detail page.', 'woocommerce') ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </p>
                <?php endif; ?>
            </div>
            <?php
        }

    }
    add_action('plugins_loaded', array('Woo_Custom_Related_Products', 'get_instance'));
}
