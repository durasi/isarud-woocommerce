<?php
if (!defined('ABSPATH')) exit;

class Isarud_Upsell {
    private static $inst = null;
    private $option_key = 'isarud_upsell_settings';

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        if (!class_exists('WooCommerce')) return;
        $settings = $this->get_settings();
        if (!$settings['enabled']) return;

        if ($settings['fbt_enabled']) {
            add_action('woocommerce_after_single_product_summary', [$this, 'render_fbt'], 15);
        }
        if ($settings['order_bump_enabled']) {
            add_action('woocommerce_review_order_before_submit', [$this, 'render_order_bump']);
            add_action('woocommerce_checkout_create_order_line_item', [$this, 'tag_bump_item'], 10, 4);
        }
        if ($settings['cart_upsell_enabled']) {
            add_action('woocommerce_after_cart_table', [$this, 'render_cart_upsell']);
        }
        if ($settings['thankyou_enabled']) {
            add_action('woocommerce_thankyou', [$this, 'render_thankyou_upsell'], 5);
        }

        add_action('wp_ajax_isarud_add_bump', [$this, 'ajax_add_bump']);
        add_action('wp_ajax_nopriv_isarud_add_bump', [$this, 'ajax_add_bump']);
        add_action('wp_ajax_isarud_save_upsell_rules', [$this, 'ajax_save_rules']);
        add_action('wp_ajax_isarud_delete_upsell_rule', [$this, 'ajax_delete_rule']);

        add_action('woocommerce_product_options_related', [$this, 'product_fbt_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_fbt']);
    }

    public function get_settings() {
        return wp_parse_args(get_option($this->option_key, []), [
            'enabled' => false,
            'fbt_enabled' => true,
            'fbt_title' => __('Birlikte Satin Alinanlar', 'api-isarud'),
            'fbt_auto' => true,
            'fbt_limit' => 4,
            'fbt_discount' => 0,
            'order_bump_enabled' => true,
            'order_bump_title' => __('Bunu da ekleyin!', 'api-isarud'),
            'cart_upsell_enabled' => true,
            'cart_upsell_title' => __('Bunlari da begenebilirsiniz', 'api-isarud'),
            'thankyou_enabled' => true,
            'thankyou_title' => __('Siparisini tamamladiniz! Bunlar da ilginizi cekebilir', 'api-isarud'),
            'thankyou_coupon' => '',
        ]);
    }

    public function save_settings($data) { update_option($this->option_key, $data); }

    public function get_rules() { return get_option('isarud_upsell_rules', []); }
    public function save_rules($rules) { update_option('isarud_upsell_rules', $rules); }

    public function product_fbt_field() {
        global $post;
        $ids = get_post_meta($post->ID, '_isarud_fbt_products', true);
        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="isarud_fbt_products"><?php _e('Birlikte Satin Alinan Urunler', 'api-isarud'); ?></label>
                <select class="wc-product-search" multiple="multiple" style="width:50%" id="isarud_fbt_products" name="isarud_fbt_products[]" data-placeholder="<?php esc_attr_e('Urun ara...', 'api-isarud'); ?>" data-action="woocommerce_json_search_products">
                    <?php if ($ids) { foreach ((array)$ids as $id) { $p = wc_get_product($id); if ($p) echo '<option value="' . esc_attr($id) . '" selected>' . esc_html($p->get_name()) . '</option>'; } } ?>
                </select>
                <?php echo wc_help_tip(__('Bu urunle birlikte onerilen urunleri secin', 'api-isarud')); ?>
            </p>
        </div>
        <?php
    }

    public function save_product_fbt($post_id) {
        if (isset($_POST['isarud_fbt_products'])) {
            update_post_meta($post_id, '_isarud_fbt_products', array_map('absint', $_POST['isarud_fbt_products']));
        } else {
            delete_post_meta($post_id, '_isarud_fbt_products');
        }
    }

    private function get_fbt_products($product_id, $limit = 4) {
        $manual = get_post_meta($product_id, '_isarud_fbt_products', true);
        if (!empty($manual)) {
            $products = [];
            foreach ((array)$manual as $id) {
                $p = wc_get_product($id);
                if ($p && $p->is_purchasable() && $p->is_in_stock()) $products[] = $p;
            }
            return array_slice($products, 0, $limit);
        }

        $settings = $this->get_settings();
        if (!$settings['fbt_auto']) return [];

        global $wpdb;
        $results = $wpdb->get_col($wpdb->prepare("
            SELECT oi2.product_id FROM {$wpdb->prefix}wc_order_product_lookup oi1
            JOIN {$wpdb->prefix}wc_order_product_lookup oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id != oi2.product_id
            WHERE oi1.product_id = %d
            GROUP BY oi2.product_id ORDER BY COUNT(*) DESC LIMIT %d
        ", $product_id, $limit));

        if (empty($results)) {
            $product = wc_get_product($product_id);
            if (!$product) return [];
            $cat_ids = $product->get_category_ids();
            if (empty($cat_ids)) return [];
            return wc_get_products(['limit' => $limit, 'status' => 'publish', 'exclude' => [$product_id], 'category' => $cat_ids, 'orderby' => 'rand']);
        }

        $products = [];
        foreach ($results as $id) {
            $p = wc_get_product($id);
            if ($p && $p->is_purchasable() && $p->is_in_stock()) $products[] = $p;
        }
        return $products;
    }

    public function render_fbt() {
        global $product;
        if (!$product) return;
        $settings = $this->get_settings();
        $fbt = $this->get_fbt_products($product->get_id(), (int)$settings['fbt_limit']);
        if (empty($fbt)) return;
        ?>
        <div class="isarud-fbt" style="margin:30px 0;padding:20px;background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb">
            <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;color:#1a1a2e"><?php echo esc_html($settings['fbt_title']); ?></h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
                <?php foreach ($fbt as $p): ?>
                <div style="background:#fff;border-radius:8px;padding:12px;border:1px solid #e5e7eb;text-align:center">
                    <?php $img = wp_get_attachment_url($p->get_image_id()); if ($img): ?>
                    <a href="<?php echo esc_url($p->get_permalink()); ?>"><img src="<?php echo esc_url($img); ?>" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px"></a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($p->get_permalink()); ?>" style="font-size:13px;color:#333;text-decoration:none;font-weight:500;display:block;margin-bottom:4px"><?php echo esc_html($p->get_name()); ?></a>
                    <span style="font-size:14px;font-weight:600;color:#358a4f"><?php echo $p->get_price_html(); ?></span>
                    <div style="margin-top:8px">
                        <a href="<?php echo esc_url($p->add_to_cart_url()); ?>" class="button" style="font-size:12px;padding:6px 12px"><?php _e('Sepete Ekle', 'api-isarud'); ?></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function render_order_bump() {
        $settings = $this->get_settings();
        $rules = $this->get_rules();
        $bump_product = null;

        foreach (WC()->cart->get_cart() as $item) {
            foreach ($rules as $rule) {
                if ($rule['type'] === 'order_bump' && in_array($item['product_id'], (array)($rule['trigger_products'] ?? []))) {
                    $p = wc_get_product($rule['offer_product']);
                    if ($p && $p->is_purchasable() && $p->is_in_stock()) { $bump_product = $p; $bump_rule = $rule; break 2; }
                }
            }
        }

        if (!$bump_product) {
            $categories = [];
            foreach (WC()->cart->get_cart() as $item) {
                $p = wc_get_product($item['product_id']);
                if ($p) $categories = array_merge($categories, $p->get_category_ids());
            }
            if (!empty($categories)) {
                $cart_ids = array_column(WC()->cart->get_cart(), 'product_id');
                $suggestions = wc_get_products(['limit' => 1, 'status' => 'publish', 'exclude' => $cart_ids, 'category' => array_unique($categories), 'orderby' => 'rand']);
                if (!empty($suggestions)) $bump_product = $suggestions[0];
            }
        }

        if (!$bump_product) return;

        $discount = isset($bump_rule['discount']) ? (float)$bump_rule['discount'] : 0;
        $price = $bump_product->get_price();
        $final_price = $discount > 0 ? $price * (1 - $discount / 100) : $price;
        ?>
        <div class="isarud-order-bump" style="background:#fffbeb;border:2px solid #f59e0b;border-radius:10px;padding:16px;margin:16px 0">
            <div style="display:flex;align-items:center;gap:12px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex:1">
                    <input type="checkbox" name="isarud_bump_product" value="<?php echo esc_attr($bump_product->get_id()); ?>" style="width:18px;height:18px">
                    <div>
                        <strong style="font-size:14px;color:#92400e"><?php echo esc_html($settings['order_bump_title']); ?></strong>
                        <p style="margin:2px 0 0;font-size:13px;color:#78350f"><?php echo esc_html($bump_product->get_name()); ?>
                        <?php if ($discount > 0): ?> — <s><?php echo wc_price($price); ?></s> <strong><?php echo wc_price($final_price); ?></strong> <span style="background:#dc2626;color:#fff;font-size:10px;padding:1px 6px;border-radius:10px">-%<?php echo (int)$discount; ?></span>
                        <?php else: ?> — <?php echo wc_price($price); ?>
                        <?php endif; ?></p>
                    </div>
                </label>
            </div>
        </div>
        <script>
        jQuery('[name="isarud_bump_product"]').on('change', function(){
            var id = jQuery(this).val(), checked = jQuery(this).is(':checked');
            jQuery.post(wc_checkout_params.ajax_url, {action:'isarud_add_bump', product_id:id, add:checked?1:0, nonce:'<?php echo wp_create_nonce("isarud_bump"); ?>'}, function(){
                jQuery('body').trigger('update_checkout');
            });
        });
        </script>
        <?php
    }

    public function ajax_add_bump() {
        check_ajax_referer('isarud_bump', 'nonce');
        $product_id = intval($_POST['product_id'] ?? 0);
        $add = intval($_POST['add'] ?? 0);
        if (!$product_id) wp_send_json_error();

        if ($add) {
            WC()->cart->add_to_cart($product_id, 1, 0, [], ['isarud_bump' => true]);
        } else {
            foreach (WC()->cart->get_cart() as $key => $item) {
                if ($item['product_id'] == $product_id && !empty($item['isarud_bump'])) {
                    WC()->cart->remove_cart_item($key); break;
                }
            }
        }
        wp_send_json_success();
    }

    public function tag_bump_item($item, $cart_item_key, $values, $order) {
        if (!empty($values['isarud_bump'])) $item->add_meta_data('_isarud_bump', 'yes', true);
    }

    public function render_cart_upsell() {
        $settings = $this->get_settings();
        $cart_ids = array_column(WC()->cart->get_cart(), 'product_id');
        $categories = [];
        foreach (WC()->cart->get_cart() as $item) {
            $p = wc_get_product($item['product_id']);
            if ($p) $categories = array_merge($categories, $p->get_category_ids());
        }
        if (empty($categories)) return;

        $suggestions = wc_get_products(['limit' => 4, 'status' => 'publish', 'exclude' => $cart_ids, 'category' => array_unique($categories), 'orderby' => 'rand']);
        if (empty($suggestions)) return;
        ?>
        <div class="isarud-cart-upsell" style="margin:20px 0">
            <h3 style="font-size:16px;font-weight:600;color:#1a1a2e;margin-bottom:12px"><?php echo esc_html($settings['cart_upsell_title']); ?></h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px">
                <?php foreach ($suggestions as $p): ?>
                <div style="background:#f9fafb;border-radius:8px;padding:10px;text-align:center;border:1px solid #e5e7eb">
                    <a href="<?php echo esc_url($p->get_permalink()); ?>" style="font-size:12px;color:#333;text-decoration:none;font-weight:500"><?php echo esc_html($p->get_name()); ?></a>
                    <div style="margin-top:4px;font-size:13px;font-weight:600;color:#358a4f"><?php echo $p->get_price_html(); ?></div>
                    <a href="<?php echo esc_url($p->add_to_cart_url()); ?>" class="button" style="font-size:11px;padding:4px 10px;margin-top:6px"><?php _e('Ekle', 'api-isarud'); ?></a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function render_thankyou_upsell($order_id) {
        $settings = $this->get_settings();
        $order = wc_get_order($order_id);
        if (!$order) return;

        $ordered_ids = [];
        $categories = [];
        foreach ($order->get_items() as $item) {
            $ordered_ids[] = $item->get_product_id();
            $p = $item->get_product();
            if ($p) $categories = array_merge($categories, $p->get_category_ids());
        }
        if (empty($categories)) return;

        $suggestions = wc_get_products(['limit' => 4, 'status' => 'publish', 'exclude' => $ordered_ids, 'category' => array_unique($categories), 'orderby' => 'rand']);
        if (empty($suggestions)) return;
        ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;margin:20px 0">
            <h3 style="margin:0 0 12px;font-size:16px;font-weight:600;color:#15803d"><?php echo esc_html($settings['thankyou_title']); ?></h3>
            <?php if (!empty($settings['thankyou_coupon'])): ?>
            <div style="background:#fff;border:2px dashed #358a4f;border-radius:8px;padding:10px;text-align:center;margin-bottom:12px">
                <span style="font-size:11px;color:#358a4f"><?php _e('Sonraki siparisinde kullan:', 'api-isarud'); ?></span>
                <strong style="font-size:18px;color:#358a4f;margin-left:8px;letter-spacing:2px"><?php echo esc_html($settings['thankyou_coupon']); ?></strong>
            </div>
            <?php endif; ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px">
                <?php foreach ($suggestions as $p): ?>
                <div style="background:#fff;border-radius:8px;padding:10px;text-align:center;border:1px solid #e5e7eb">
                    <a href="<?php echo esc_url($p->get_permalink()); ?>" style="font-size:12px;color:#333;text-decoration:none;font-weight:500"><?php echo esc_html($p->get_name()); ?></a>
                    <div style="margin-top:4px;font-size:13px;font-weight:600;color:#358a4f"><?php echo $p->get_price_html(); ?></div>
                    <a href="<?php echo esc_url($p->get_permalink()); ?>" style="font-size:11px;color:#358a4f"><?php _e('Incele', 'api-isarud'); ?> &rarr;</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function ajax_save_rules() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $id = sanitize_text_field($_POST['rule_id'] ?? '') ?: 'rule_' . wp_generate_password(8, false);
        $rules = $this->get_rules();
        $rules[$id] = [
            'type' => sanitize_text_field($_POST['rule_type'] ?? 'order_bump'),
            'trigger_products' => array_map('absint', (array)($_POST['trigger_products'] ?? [])),
            'offer_product' => absint($_POST['offer_product'] ?? 0),
            'discount' => floatval($_POST['discount'] ?? 0),
            'active' => !empty($_POST['active']),
        ];
        $this->save_rules($rules);
        wp_send_json_success(['id' => $id]);
    }

    public function ajax_delete_rule() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $id = sanitize_text_field($_POST['rule_id'] ?? '');
        $rules = $this->get_rules();
        unset($rules[$id]);
        $this->save_rules($rules);
        wp_send_json_success();
    }
}

Isarud_Upsell::instance();
