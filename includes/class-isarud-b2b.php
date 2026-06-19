<?php
if (!defined('ABSPATH')) exit;

class Isarud_B2B {
    private static $inst = null;
    private $option_key = 'isarud_b2b_settings';

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        $settings = $this->get_settings();
        if (!$settings['enabled'] || !class_exists('WooCommerce')) return;

        add_action('woocommerce_product_options_pricing', [$this, 'product_pricing_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_fields']);
        add_action('woocommerce_variation_options_pricing', [$this, 'variation_pricing_fields'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_variation_fields'], 10, 2);

        add_filter('woocommerce_product_get_price', [$this, 'apply_b2b_price'], 10, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'apply_b2b_price'], 10, 2);
        add_filter('woocommerce_product_variation_get_price', [$this, 'apply_b2b_price'], 10, 2);
        add_filter('woocommerce_product_variation_get_regular_price', [$this, 'apply_b2b_price'], 10, 2);

        add_action('woocommerce_after_checkout_billing_form', [$this, 'checkout_fields']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_checkout_fields']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_order_fields']);

        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_min_quantity'], 10, 3);

        add_action('show_user_profile', [$this, 'user_profile_fields']);
        add_action('edit_user_profile', [$this, 'user_profile_fields']);
        add_action('personal_options_update', [$this, 'save_user_profile']);
        add_action('edit_user_profile_update', [$this, 'save_user_profile']);

        add_action('wp_ajax_isarud_approve_b2b', [$this, 'ajax_approve_b2b']);
    }

    public function get_settings() {
        return wp_parse_args(get_option($this->option_key, []), [
            'enabled' => false,
            'b2b_role' => 'isarud_b2b',
            'min_order_amount' => 0,
            'show_tax_field' => true,
            'show_company_field' => true,
            'require_approval' => true,
            'hide_prices_guests' => false,
        ]);
    }

    public function save_settings($data) {
        update_option($this->option_key, $data);
    }

    public function ensure_role() {
        if (!get_role('isarud_b2b')) {
            add_role('isarud_b2b', __('B2B Wholesale Customer', 'api-isarud'), get_role('customer')->capabilities);
        }
    }

    public function product_pricing_fields() {
        woocommerce_wp_text_input([
            'id' => '_isarud_b2b_price',
            'label' => __('B2B Wholesale Price', 'api-isarud') . ' (' . get_woocommerce_currency_symbol() . ')',
            'desc_tip' => true,
            'description' => __('Price to display for customers with B2B role', 'api-isarud'),
            'type' => 'text',
            'data_type' => 'price',
        ]);
        woocommerce_wp_text_input([
            'id' => '_isarud_b2b_min_qty',
            'label' => __('B2B Minimum Order Quantity', 'api-isarud'),
            'desc_tip' => true,
            'description' => __('Minimum order quantity for wholesale customers', 'api-isarud'),
            'type' => 'number',
            'custom_attributes' => ['min' => '1', 'step' => '1'],
        ]);
    }

    public function save_product_fields($post_id) {
        if (isset($_POST['_isarud_b2b_price'])) {
            update_post_meta($post_id, '_isarud_b2b_price', wc_format_decimal($_POST['_isarud_b2b_price']));
        }
        if (isset($_POST['_isarud_b2b_min_qty'])) {
            update_post_meta($post_id, '_isarud_b2b_min_qty', absint($_POST['_isarud_b2b_min_qty']));
        }
    }

    public function variation_pricing_fields($loop, $variation_data, $variation) {
        woocommerce_wp_text_input([
            'id' => "_isarud_b2b_price_var_{$loop}",
            'name' => "_isarud_b2b_price_var[{$loop}]",
            'label' => __('B2B Wholesale Price', 'api-isarud') . ' (' . get_woocommerce_currency_symbol() . ')',
            'value' => get_post_meta($variation->ID, '_isarud_b2b_price', true),
            'type' => 'text',
            'data_type' => 'price',
            'wrapper_class' => 'form-row form-row-first',
        ]);
    }

    public function save_variation_fields($variation_id, $i) {
        if (isset($_POST['_isarud_b2b_price_var'][$i])) {
            update_post_meta($variation_id, '_isarud_b2b_price', wc_format_decimal($_POST['_isarud_b2b_price_var'][$i]));
        }
    }

    public function apply_b2b_price($price, $product) {
        if (!is_user_logged_in()) return $price;
        $user = wp_get_current_user();
        if (!in_array('isarud_b2b', $user->roles)) return $price;

        $b2b_price = get_post_meta($product->get_id(), '_isarud_b2b_price', true);
        if (!empty($b2b_price) && (float)$b2b_price > 0) {
            return $b2b_price;
        }
        return $price;
    }

    public function validate_min_quantity($passed, $product_id, $quantity) {
        if (!is_user_logged_in()) return $passed;
        $user = wp_get_current_user();
        if (!in_array('isarud_b2b', $user->roles)) return $passed;

        $min_qty = (int)get_post_meta($product_id, '_isarud_b2b_min_qty', true);
        if ($min_qty > 0 && $quantity < $min_qty) {
            $product = wc_get_product($product_id);
            wc_add_notice(
                sprintf(__('Minimum order quantity for "%s" is %d units.', 'api-isarud'), $product->get_name(), $min_qty),
                'error'
            );
            return false;
        }
        return $passed;
    }

    public function checkout_fields($checkout) {
        $settings = $this->get_settings();

        if ($settings['show_company_field']) {
            woocommerce_form_field('isarud_company_name', [
                'type' => 'text',
                'label' => __('Company Name', 'api-isarud'),
                'placeholder' => __('Enter your company name', 'api-isarud'),
                'class' => ['form-row-wide'],
                'required' => false,
            ], $checkout->get_value('isarud_company_name'));
        }

        if ($settings['show_tax_field']) {
            woocommerce_form_field('isarud_tax_office', [
                'type' => 'text',
                'label' => __('Tax Authority', 'api-isarud'),
                'placeholder' => __('Enter your tax authority', 'api-isarud'),
                'class' => ['form-row-first'],
                'required' => false,
            ], $checkout->get_value('isarud_tax_office'));

            woocommerce_form_field('isarud_tax_number', [
                'type' => 'text',
                'label' => __('Tax ID / National ID', 'api-isarud'),
                'placeholder' => __('Your tax ID or national ID number', 'api-isarud'),
                'class' => ['form-row-last'],
                'required' => false,
            ], $checkout->get_value('isarud_tax_number'));
        }
    }

    public function save_checkout_fields($order_id) {
        $fields = ['isarud_company_name', 'isarud_tax_office', 'isarud_tax_number'];
        foreach ($fields as $field) {
            if (!empty($_POST[$field])) {
                update_post_meta($order_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function display_order_fields($order) {
        $company = $order->get_meta('isarud_company_name');
        $tax_office = $order->get_meta('isarud_tax_office');
        $tax_number = $order->get_meta('isarud_tax_number');

        if ($company || $tax_office || $tax_number) {
            echo '<div style="margin-top:12px;padding:10px;background:#f8f9fa;border-radius:6px">';
            echo '<h4 style="margin:0 0 8px;font-size:13px;color:#1d2327">' . __('B2B Information', 'api-isarud') . '</h4>';
            if ($company) echo '<p style="margin:2px 0;font-size:12px"><strong>' . __('Company:', 'api-isarud') . '</strong> ' . esc_html($company) . '</p>';
            if ($tax_office) echo '<p style="margin:2px 0;font-size:12px"><strong>' . __('Tax Authority:', 'api-isarud') . '</strong> ' . esc_html($tax_office) . '</p>';
            if ($tax_number) echo '<p style="margin:2px 0;font-size:12px"><strong>' . __('Tax ID:', 'api-isarud') . '</strong> ' . esc_html($tax_number) . '</p>';
            echo '</div>';
        }
    }

    public function user_profile_fields($user) {
        ?>
        <h3><?php _e('B2B Wholesale Sales Information', 'api-isarud'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e('B2B Status', 'api-isarud'); ?></label></th>
                <td>
                    <select name="isarud_b2b_status" style="min-width:200px">
                        <option value=""><?php _e('Regular Customer', 'api-isarud'); ?></option>
                        <option value="pending" <?php selected(get_user_meta($user->ID, 'isarud_b2b_status', true), 'pending'); ?>><?php _e('Pending Application', 'api-isarud'); ?></option>
                        <option value="approved" <?php selected(get_user_meta($user->ID, 'isarud_b2b_status', true), 'approved'); ?>><?php _e('Approved', 'api-isarud'); ?></option>
                        <option value="rejected" <?php selected(get_user_meta($user->ID, 'isarud_b2b_status', true), 'rejected'); ?>><?php _e('Rejected', 'api-isarud'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Company Name', 'api-isarud'); ?></label></th>
                <td><input type="text" name="isarud_b2b_company" value="<?php echo esc_attr(get_user_meta($user->ID, 'isarud_b2b_company', true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label><?php _e('Tax Authority', 'api-isarud'); ?></label></th>
                <td><input type="text" name="isarud_b2b_tax_office" value="<?php echo esc_attr(get_user_meta($user->ID, 'isarud_b2b_tax_office', true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label><?php _e('Tax ID', 'api-isarud'); ?></label></th>
                <td><input type="text" name="isarud_b2b_tax_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'isarud_b2b_tax_number', true)); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    public function save_user_profile($user_id) {
        if (!current_user_can('edit_user', $user_id)) return;

        $status = sanitize_text_field($_POST['isarud_b2b_status'] ?? '');
        update_user_meta($user_id, 'isarud_b2b_status', $status);
        update_user_meta($user_id, 'isarud_b2b_company', sanitize_text_field($_POST['isarud_b2b_company'] ?? ''));
        update_user_meta($user_id, 'isarud_b2b_tax_office', sanitize_text_field($_POST['isarud_b2b_tax_office'] ?? ''));
        update_user_meta($user_id, 'isarud_b2b_tax_number', sanitize_text_field($_POST['isarud_b2b_tax_number'] ?? ''));

        $user = new WP_User($user_id);
        if ($status === 'approved') {
            $this->ensure_role();
            $user->add_role('isarud_b2b');
        } else {
            $user->remove_role('isarud_b2b');
        }
    }

    public function ajax_approve_b2b() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $user_id = intval($_POST['user_id'] ?? 0);
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');

        if (!$user_id || !in_array($action_type, ['approve', 'reject'])) {
            wp_send_json_error(__('Invalid request', 'api-isarud'));
        }

        $user = new WP_User($user_id);
        if ($action_type === 'approve') {
            $this->ensure_role();
            $user->add_role('isarud_b2b');
            update_user_meta($user_id, 'isarud_b2b_status', 'approved');
            wp_send_json_success(__('User approved as B2B', 'api-isarud'));
        } else {
            $user->remove_role('isarud_b2b');
            update_user_meta($user_id, 'isarud_b2b_status', 'rejected');
            wp_send_json_success(__('Application rejected', 'api-isarud'));
        }
    }

    public function get_pending_applications() {
        return get_users([
            'meta_key' => 'isarud_b2b_status',
            'meta_value' => 'pending',
        ]);
    }

    public function get_b2b_customers() {
        return get_users([
            'meta_key' => 'isarud_b2b_status',
            'meta_value' => 'approved',
        ]);
    }
}

Isarud_B2B::instance();
