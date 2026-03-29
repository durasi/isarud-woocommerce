<?php
/**
 * Isarud Variation Sync
 * Varyasyonlu ürünleri (beden, renk) pazar yerleriyle senkronize eder
 */
if (!defined('ABSPATH')) exit;

class Isarud_Variation_Sync {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        // Add variation-level sync fields
        add_action('woocommerce_variation_options', [$this, 'variation_fields'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_variation_fields'], 10, 2);
        add_action('wp_ajax_isarud_sync_variations', [$this, 'ajax_sync_variations']);
    }

    /**
     * Add barcode field to each variation
     */
    public function variation_fields($loop, $variation_data, $variation): void {
        $variation_id = $variation->ID;
        ?>
        <div class="isarud-variation-fields" style="border-top:1px solid #eee;padding-top:8px;margin-top:8px">
            <p class="form-row form-row-first">
                <label style="color:#2271b1;font-weight:bold">Isarud Barkod</label>
                <input type="text" name="isarud_var_barcode[<?php echo $loop; ?>]"
                       value="<?php echo esc_attr(get_post_meta($variation_id, '_isarud_barcode', true)); ?>"
                       placeholder="Pazar yeri barkodu">
            </p>
        </div>
        <?php
    }

    /**
     * Save variation barcode
     */
    public function save_variation_fields(int $variation_id, int $loop): void {
        if (isset($_POST['isarud_var_barcode'][$loop])) {
            update_post_meta($variation_id, '_isarud_barcode',
                sanitize_text_field($_POST['isarud_var_barcode'][$loop]));
        }
    }

    /**
     * Sync all variations of a variable product to marketplace
     */
    public function sync_variable_product(\WC_Product_Variable $product, string $mp): array {
        $variations = $product->get_available_variations();
        $results = ['synced' => 0, 'failed' => 0, 'items' => []];

        foreach ($variations as $var_data) {
            $variation = wc_get_product($var_data['variation_id']);
            if (!$variation || !$variation->managing_stock()) continue;

            $barcode = get_post_meta($variation->get_id(), '_isarud_barcode', true);
            if (empty($barcode)) {
                $barcode = $variation->get_sku();
            }
            if (empty($barcode)) {
                $results['failed']++;
                $results['items'][] = ['id' => $variation->get_id(), 'error' => 'No barcode/SKU'];
                continue;
            }

            $stock = $variation->get_stock_quantity() ?? 0;
            $price = $this->apply_margin((float)$variation->get_price(), $mp);

            $sync_result = $this->sync_to_marketplace($mp, $barcode, $stock, $price);

            if (!isset($sync_result['error'])) {
                $results['synced']++;
                $results['items'][] = ['id' => $variation->get_id(), 'barcode' => $barcode, 'stock' => $stock, 'price' => $price];
            } else {
                $results['failed']++;
                $results['items'][] = ['id' => $variation->get_id(), 'error' => $sync_result['error']];
            }

            // Log
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'isarud_sync_log', [
                'product_id' => $variation->get_id(),
                'marketplace' => $mp,
                'action' => 'variation_sync',
                'status' => isset($sync_result['error']) ? 'error' : 'success',
                'message' => isset($sync_result['error']) ? $sync_result['error'] : "Barcode: {$barcode}, Stock: {$stock}, Price: {$price}",
                'created_at' => current_time('mysql'),
            ]);
        }

        return $results;
    }

    /**
     * Sync single item to marketplace
     */
    private function sync_to_marketplace(string $mp, string $barcode, int $stock, float $price): array {
        $plugin = Isarud_Plugin::instance();
        $method = new \ReflectionMethod($plugin, 'marketplace_request');
        $method->setAccessible(true);

        return match($mp) {
            'trendyol' => $method->invoke($plugin, 'trendyol',
                'suppliers/' . $this->get_cred('trendyol', 'seller_id') . '/products/price-and-inventory',
                'PUT',
                ['items' => [['barcode' => $barcode, 'quantity' => $stock, 'salePrice' => $price, 'listPrice' => $price]]]
            ),
            'hepsiburada' => $method->invoke($plugin, 'hepsiburada',
                'listings/merchantid/' . $this->get_cred('hepsiburada', 'merchant_id') . '/stock-uploads',
                'POST',
                ['listings' => [['merchantSku' => $barcode, 'availableStock' => $stock, 'price' => $price]]]
            ),
            'pazarama' => $method->invoke($plugin, 'pazarama',
                'product/products/price-and-inventory',
                'PUT',
                ['items' => [['barcode' => $barcode, 'quantity' => $stock, 'salePrice' => $price]]]
            ),
            default => ['error' => 'Variation sync not supported for ' . $mp],
        };
    }

    /**
     * AJAX handler
     */
    public function ajax_sync_variations(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $product_id = intval($_POST['product_id'] ?? 0);
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');

        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            wp_send_json_error('Not a variable product');
        }

        $result = $this->sync_variable_product($product, $mp);
        wp_send_json_success($result);
    }

    private function apply_margin(float $price, string $mp): float {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT price_margin, price_margin_type FROM {$wpdb->prefix}isarud_credentials WHERE marketplace=%s", $mp
        ));
        if ($row && floatval($row->price_margin) != 0) {
            return $row->price_margin_type === 'percent'
                ? round($price * (1 + floatval($row->price_margin) / 100), 2)
                : round($price + floatval($row->price_margin), 2);
        }
        return $price;
    }

    private function get_cred(string $mp, string $key): string {
        $plugin = Isarud_Plugin::instance();
        $method = new \ReflectionMethod($plugin, 'get_cred');
        $method->setAccessible(true);
        return $method->invoke($plugin, $mp, $key);
    }
}