<?php
/**
 * Isarud Webhook Handler
 * Trendyol, Hepsiburada, N11 webhook'larını dinler
 * WooCommerce stoğunu çift yönlü günceller
 */
if (!defined('ABSPATH')) exit;

class Isarud_Webhook {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints(): void {
        // Trendyol webhook
        register_rest_route('isarud/v1', '/webhook/trendyol', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_trendyol'],
            'permission_callback' => '__return_true',
        ]);

        // Hepsiburada webhook
        register_rest_route('isarud/v1', '/webhook/hepsiburada', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_hepsiburada'],
            'permission_callback' => '__return_true',
        ]);

        // N11 webhook
        register_rest_route('isarud/v1', '/webhook/n11', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_n11'],
            'permission_callback' => '__return_true',
        ]);

        // Generic stock update endpoint
        register_rest_route('isarud/v1', '/webhook/stock', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_stock_update'],
            'permission_callback' => [$this, 'verify_webhook_key'],
        ]);
    }

    public function verify_webhook_key(\WP_REST_Request $request): bool {
        $key = $request->get_header('X-Isarud-Webhook-Key');
        $stored = get_option('isarud_webhook_secret', '');
        return !empty($stored) && hash_equals($stored, $key ?? '');
    }

    /**
     * Trendyol webhook — sipariş/stok güncellemesi
     */
    public function handle_trendyol(\WP_REST_Request $request): \WP_REST_Response {
        $data = $request->get_json_params();
        $this->log_webhook('trendyol', $data);

        if (empty($data)) {
            return new \WP_REST_Response(['error' => 'Empty payload'], 400);
        }

        $updated = 0;
        $errors = 0;

        // Trendyol sends order/stock notifications
        // Format varies: could be order status change or stock update
        $items = $data['items'] ?? $data['content'] ?? [$data];

        foreach ($items as $item) {
            $barcode = $item['barcode'] ?? $item['productBarcode'] ?? null;
            $quantity = $item['quantity'] ?? $item['stockQuantity'] ?? null;

            if ($barcode && $quantity !== null) {
                $result = $this->update_wc_stock_by_barcode($barcode, (int)$quantity, 'trendyol');
                $result ? $updated++ : $errors++;
            }

            // Handle order notification
            if (isset($item['orderNumber']) || isset($item['shipmentPackageId'])) {
                $this->process_trendyol_order($item);
            }
        }

        return new \WP_REST_Response([
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
        ], 200);
    }

    /**
     * Hepsiburada webhook — sipariş/stok güncellemesi
     */
    public function handle_hepsiburada(\WP_REST_Request $request): \WP_REST_Response {
        $data = $request->get_json_params();
        $this->log_webhook('hepsiburada', $data);

        if (empty($data)) {
            return new \WP_REST_Response(['error' => 'Empty payload'], 400);
        }

        $updated = 0;
        $errors = 0;

        $items = $data['listings'] ?? $data['items'] ?? [$data];

        foreach ($items as $item) {
            $sku = $item['merchantSku'] ?? $item['hepsiburadaSku'] ?? null;
            $stock = $item['availableStock'] ?? $item['stock'] ?? null;

            if ($sku && $stock !== null) {
                $result = $this->update_wc_stock_by_sku($sku, (int)$stock, 'hepsiburada');
                $result ? $updated++ : $errors++;
            }

            // Handle order notification
            if (isset($item['orderId']) || isset($item['packageNumber'])) {
                $this->process_hepsiburada_order($item);
            }
        }

        return new \WP_REST_Response([
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
        ], 200);
    }

    /**
     * N11 webhook — stok/sipariş güncellemesi
     */
    public function handle_n11(\WP_REST_Request $request): \WP_REST_Response {
        $data = $request->get_json_params();
        $this->log_webhook('n11', $data);

        if (empty($data)) {
            return new \WP_REST_Response(['error' => 'Empty payload'], 400);
        }

        $updated = 0;
        $errors = 0;

        $items = $data['products'] ?? $data['items'] ?? [$data];

        foreach ($items as $item) {
            $sku = $item['sellerCode'] ?? $item['stockCode'] ?? null;
            $stock = $item['stockQuantity'] ?? $item['quantity'] ?? null;

            if ($sku && $stock !== null) {
                $result = $this->update_wc_stock_by_sku($sku, (int)$stock, 'n11');
                $result ? $updated++ : $errors++;
            }
        }

        return new \WP_REST_Response([
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
        ], 200);
    }

    /**
     * Generic stock update handler
     */
    public function handle_stock_update(\WP_REST_Request $request): \WP_REST_Response {
        $data = $request->get_json_params();
        $marketplace = sanitize_text_field($data['marketplace'] ?? 'unknown');
        $items = $data['items'] ?? [];
        $updated = 0;

        foreach ($items as $item) {
            $sku = $item['sku'] ?? $item['barcode'] ?? null;
            $stock = $item['stock'] ?? $item['quantity'] ?? null;
            if ($sku && $stock !== null) {
                if ($this->update_wc_stock_by_sku($sku, (int)$stock, $marketplace)) {
                    $updated++;
                }
            }
        }

        return new \WP_REST_Response(['success' => true, 'updated' => $updated], 200);
    }

    /**
     * Update WooCommerce stock by SKU
     */
    private function update_wc_stock_by_sku(string $sku, int $quantity, string $source): bool {
        $product_id = wc_get_product_id_by_sku($sku);
        if (!$product_id) return false;

        $product = wc_get_product($product_id);
        if (!$product || !$product->managing_stock()) return false;

        $old_stock = $product->get_stock_quantity();
        $product->set_stock_quantity($quantity);
        $product->save();

        $this->log_stock_update($product_id, $sku, $old_stock, $quantity, $source);
        return true;
    }

    /**
     * Update WooCommerce stock by barcode (custom meta)
     */
    private function update_wc_stock_by_barcode(string $barcode, int $quantity, string $source): bool {
        // First try SKU
        if ($this->update_wc_stock_by_sku($barcode, $quantity, $source)) {
            return true;
        }

        // Then try barcode meta
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_isarud_barcode' AND meta_value=%s LIMIT 1",
            $barcode
        ));

        if (!$product_id) return false;

        $product = wc_get_product($product_id);
        if (!$product || !$product->managing_stock()) return false;

        $old_stock = $product->get_stock_quantity();
        $product->set_stock_quantity($quantity);
        $product->save();

        $this->log_stock_update($product_id, $barcode, $old_stock, $quantity, $source);
        return true;
    }

    private function process_trendyol_order(array $item): void {
        do_action('isarud_trendyol_order_received', $item);
    }

    private function process_hepsiburada_order(array $item): void {
        do_action('isarud_hepsiburada_order_received', $item);
    }

    private function log_webhook(string $marketplace, $data): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'isarud_sync_log', [
            'product_id' => 0,
            'marketplace' => $marketplace,
            'action' => 'webhook_received',
            'status' => 'success',
            'message' => wp_json_encode($data),
            'created_at' => current_time('mysql'),
        ]);
    }

    private function log_stock_update(int $product_id, string $sku, ?int $old, int $new, string $source): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'isarud_sync_log', [
            'product_id' => $product_id,
            'marketplace' => $source,
            'action' => 'stock_update_incoming',
            'status' => 'success',
            'message' => "SKU: {$sku} | {$old} → {$new}",
            'created_at' => current_time('mysql'),
        ]);
    }

    /**
     * Get webhook URLs for display
     */
    public static function get_webhook_urls(): array {
        return [
            'trendyol' => rest_url('isarud/v1/webhook/trendyol'),
            'hepsiburada' => rest_url('isarud/v1/webhook/hepsiburada'),
            'n11' => rest_url('isarud/v1/webhook/n11'),
            'generic' => rest_url('isarud/v1/webhook/stock'),
        ];
    }
}