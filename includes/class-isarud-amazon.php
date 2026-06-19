<?php
/**
 * class-isarud-amazon.php — WP Plugin Amazon SP-API Bridge Client
 * v6.6.3 - Amazon SP-API entegrasyonu (Trendyol clone, 17 endpoint)
 *
 * Mevcut Isarud_Cloud_Sync::api_request() pattern'ini takip eder.
 * Auth: X-Marketplace-Key header (Cloud Sync'te zaten çalışıyor)
 *
 * 6 phase, 17 endpoint:
 *   Phase 1: status + listings + listing_delete (3)
 *   Phase 2: sync_stock_bulk + sync_single (2)
 *   Phase 3: categories + category_attributes (2)
 *   Phase 4: orders + order_pull + package_status + tracking (4)
 *   Phase 5: returns + approve + reject (3)
 *   Phase 6: stores + connect_url + admin_notices (3)
 *
 * GÜVENLİK:
 * - Bu dosyada hiçbir secret yok, sadece HTTP client kodu
 * - Amazon Merchant ID/Username/Password ASLA WP'ye gelmez (sunucuda kalır)
 * - Tüm istekler isarud.com'a gider, Cloud Sync key ile auth
 */

if (!defined('ABSPATH')) exit;

class Isarud_Amazon {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        // Phase 1 - Status + Listings + Delete
        add_action('wp_ajax_isarud_amazon_status',              [$this, 'ajax_status']);
        add_action('wp_ajax_isarud_amazon_listings',            [$this, 'ajax_listings']);
        add_action('wp_ajax_isarud_amazon_listing_delete',      [$this, 'ajax_listing_delete']);

        // Phase 2 - Stock & Price
        add_action('wp_ajax_isarud_amazon_sync_stock',          [$this, 'ajax_sync_stock']);
        add_action('wp_ajax_isarud_amazon_sync_single',         [$this, 'ajax_sync_single']);

        // Phase 3 - Categories
        add_action('wp_ajax_isarud_amazon_categories',          [$this, 'ajax_categories']);
        add_action('wp_ajax_isarud_amazon_category_attributes', [$this, 'ajax_category_attributes']);

        // Phase 4 - Orders
        add_action('wp_ajax_isarud_amazon_orders',              [$this, 'ajax_orders']);
        add_action('wp_ajax_isarud_amazon_order_pull',          [$this, 'ajax_order_pull']);
        add_action('wp_ajax_isarud_amazon_package_status',      [$this, 'ajax_package_status']);
        add_action('wp_ajax_isarud_amazon_package_tracking',    [$this, 'ajax_package_tracking']);

        // Phase 5 - Returns
        add_action('wp_ajax_isarud_amazon_returns',             [$this, 'ajax_returns']);
        add_action('wp_ajax_isarud_amazon_return_approve',      [$this, 'ajax_return_approve']);
        add_action('wp_ajax_isarud_amazon_return_reject',       [$this, 'ajax_return_reject']);

        // Phase 6 - Connect Flow
        add_action('wp_ajax_isarud_amazon_stores',              [$this, 'ajax_stores']);
        add_action('wp_ajax_isarud_amazon_connect_url',         [$this, 'ajax_connect_url']);
        add_action('admin_notices',                                  [$this, 'maybe_show_callback_notice']);
    }

    // ═══════════════════════════════════════════════════════════════
    // BRIDGE REQUEST (HTTP client - Trendyol pattern paritesi)
    // ═══════════════════════════════════════════════════════════════

    private function bridge_request(string $endpoint, array $data = [], string $method = 'GET'): array {
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => __('No Cloud Sync API key. Please connect from the Cloud Sync page first.','api-isarud'),
                'data' => null,
            ];
        }

        $url = $this->api_base . ltrim($endpoint, '/');

        $args = [
            'method' => strtoupper($method),
            'timeout' => 60,
            'headers' => [
                'X-Marketplace-Key' => $api_key,
                'Accept' => 'application/json',
            ],
            'sslverify' => true,
        ];

        if (in_array($args['method'], ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $args['headers']['Content-Type'] = 'application/json';
            if (!empty($data)) {
                $args['body'] = wp_json_encode($data);
            }
        } elseif ($args['method'] === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'HTTP error: ' . $response->get_error_message(),
                'data' => null,
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code >= 400) {
            $msg = $decoded['message'] ?? "HTTP $code";
            return [
                'success' => false,
                'message' => $msg,
                'http_code' => $code,
                'data' => $decoded,
            ];
        }

        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Invalid JSON response',
                'data' => null,
            ];
        }

        return $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 1 - STATUS + LISTINGS + DELETE
    // ═══════════════════════════════════════════════════════════════

    public function get_status(): array {
        return $this->bridge_request('amazon/status');
    }

    public function get_listings(int $page = 0, int $size = 50, string $filter = 'all'): array {
        return $this->bridge_request('amazon/listings', [
            'page' => $page,
            'size' => $size,
            'filter' => $filter,
        ]);
    }

    public function delete_listing(string $sku): array {
        return $this->bridge_request("amazon/products/{$sku}", [], 'DELETE');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2 - STOCK & PRICE
    // ═══════════════════════════════════════════════════════════════

    public function sync_stock_bulk(array $items): array {
        return $this->bridge_request('amazon/sync/stock', ['items' => $items], 'POST');
    }

    public function sync_stock_single(string $sku, int $quantity, float $price, ?float $listPrice = null): array {
        $item = [
            'sku' => $sku,
            'availableStock' => $quantity,
            'price' => $price,
        ];
        if ($listPrice !== null) {
            $item['listPrice'] = $listPrice;
        }
        return $this->sync_stock_bulk([$item]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 3 - CATEGORIES
    // ═══════════════════════════════════════════════════════════════

    public function get_categories(?int $parentId = null): array {
        $params = [];
        if ($parentId !== null) {
            $params['parentId'] = $parentId;
        }
        return $this->bridge_request('amazon/categories', $params);
    }

    public function get_category_attributes(int $categoryId): array {
        return $this->bridge_request("amazon/categories/{$categoryId}/attributes");
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 4 - ORDERS
    // ═══════════════════════════════════════════════════════════════

    public function get_orders(?string $status = null, int $days = 7, int $page = 0, int $size = 50): array {
        $params = [
            'days' => $days,
            'page' => $page,
            'size' => $size,
        ];
        if ($status !== null) {
            $params['status'] = $status;
        }
        return $this->bridge_request('amazon/orders', $params);
    }

    public function pull_orders(int $days = 7): array {
        return $this->bridge_request('amazon/orders/pull', ['days' => $days], 'POST');
    }

    public function update_package_status(string $orderId, string $status): array {
        return $this->bridge_request("amazon/orders/{$orderId}", ['status' => $status], 'PUT');
    }

    public function submit_tracking(string $orderId, string $trackingNumber, ?string $cargoCompany = null): array {
        $data = ['trackingNumber' => $trackingNumber];
        if ($cargoCompany !== null) {
            $data['cargoCompany'] = $cargoCompany;
        }
        return $this->bridge_request("amazon/orders/{$orderId}", $data, 'PUT');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 5 - RETURNS (Claims)
    // ═══════════════════════════════════════════════════════════════

    public function get_returns(int $page = 0, int $size = 50): array {
        return $this->bridge_request('amazon/returns', [
            'page' => $page,
            'size' => $size,
        ]);
    }

    public function approve_return(string $claimId): array {
        return $this->bridge_request("amazon/returns/{$claimId}", ['action' => 'approve'], 'POST');
    }

    public function reject_return(string $claimId, ?string $reason = null): array {
        $data = ['action' => 'reject'];
        if ($reason !== null) {
            $data['reason'] = $reason;
        }
        return $this->bridge_request("amazon/returns/{$claimId}", $data, 'POST');
    }

    // ═══════════════════════════════════════════════════════════════
    // AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════

    private function check_ajax(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Yetki yok'], 403);
        }
        check_ajax_referer('isarud_amazon_nonce', 'nonce');
    }

    public function ajax_status(): void {
        $this->check_ajax();
        wp_send_json($this->get_status());
    }

    public function ajax_listings(): void {
        $this->check_ajax();
        $page = (int) ($_POST['page'] ?? 0);
        $size = (int) ($_POST['size'] ?? 50);
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        wp_send_json($this->get_listings($page, $size, $filter));
    }

    public function ajax_listing_delete(): void {
        $this->check_ajax();
        $sku = sanitize_text_field($_POST['sku'] ?? '');
        if (!$sku) wp_send_json_error(['message' => 'sku gerekli']);
        wp_send_json($this->delete_listing($sku));
    }

    public function ajax_sync_stock(): void {
        $this->check_ajax();
        $items = $_POST['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            wp_send_json_error(['message' => 'items array gerekli']);
        }
        wp_send_json($this->sync_stock_bulk($items));
    }

    public function ajax_sync_single(): void {
        $this->check_ajax();
        $sku = sanitize_text_field($_POST['sku'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $listPrice = isset($_POST['list_price']) ? (float) $_POST['list_price'] : null;
        if (!$sku) wp_send_json_error(['message' => 'sku gerekli']);
        wp_send_json($this->sync_stock_single($sku, $quantity, $price, $listPrice));
    }

    public function ajax_categories(): void {
        $this->check_ajax();
        $parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        wp_send_json($this->get_categories($parentId));
    }

    public function ajax_category_attributes(): void {
        $this->check_ajax();
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if (!$categoryId) wp_send_json_error(['message' => 'category_id gerekli']);
        wp_send_json($this->get_category_attributes($categoryId));
    }

    public function ajax_orders(): void {
        $this->check_ajax();
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
        $days = (int) ($_POST['days'] ?? 7);
        $page = (int) ($_POST['page'] ?? 0);
        $size = (int) ($_POST['size'] ?? 50);
        wp_send_json($this->get_orders($status, $days, $page, $size));
    }

    public function ajax_order_pull(): void {
        $this->check_ajax();
        $days = (int) ($_POST['days'] ?? 7);
        wp_send_json($this->pull_orders($days));
    }

    public function ajax_package_status(): void {
        $this->check_ajax();
        $orderId = sanitize_text_field($_POST['order_id'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        if (!$orderId || !$status) wp_send_json_error(['message' => 'order_id ve status gerekli']);
        wp_send_json($this->update_package_status($orderId, $status));
    }

    public function ajax_package_tracking(): void {
        $this->check_ajax();
        $orderId = sanitize_text_field($_POST['order_id'] ?? '');
        $trackingNumber = sanitize_text_field($_POST['tracking_number'] ?? '');
        $cargoCompany = isset($_POST['cargo_company']) ? sanitize_text_field($_POST['cargo_company']) : null;
        if (!$orderId || !$trackingNumber) wp_send_json_error(['message' => 'order_id ve tracking_number gerekli']);
        wp_send_json($this->submit_tracking($orderId, $trackingNumber, $cargoCompany));
    }

    public function ajax_returns(): void {
        $this->check_ajax();
        $page = (int) ($_POST['page'] ?? 0);
        $size = (int) ($_POST['size'] ?? 50);
        wp_send_json($this->get_returns($page, $size));
    }

    public function ajax_return_approve(): void {
        $this->check_ajax();
        $claimId = sanitize_text_field($_POST['claim_id'] ?? '');
        if (!$claimId) wp_send_json_error(['message' => 'claim_id gerekli']);
        wp_send_json($this->approve_return($claimId));
    }

    public function ajax_return_reject(): void {
        $this->check_ajax();
        $claimId = sanitize_text_field($_POST['claim_id'] ?? '');
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : null;
        if (!$claimId) wp_send_json_error(['message' => 'claim_id gerekli']);
        wp_send_json($this->reject_return($claimId, $reason));
    }

    // ═══════════════════════════════════════════════════════════════
    // CONNECT FLOW (Trendyol paritesi)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get user's stores
     * AJAX: isarud_amazon_stores
     */
    public function ajax_stores(): void {
        $this->check_ajax();

        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'Cloud Sync API key yok']);
        }

        $response = wp_remote_get('https://isarud.com/api/v2/marketplace/stores', [
            'timeout' => 30,
            'headers' => ['X-Marketplace-Key' => $api_key, 'Accept' => 'application/json'],
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'HTTP error: ' . $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        wp_send_json($data ?? ['success' => false, 'message' => 'Invalid response']);
    }

    /**
     * Get Amazon connect URL for a specific store
     * AJAX: isarud_amazon_connect_url
     */
    public function ajax_connect_url(): void {
        $this->check_ajax();

        $store_id = (int) ($_POST['store_id'] ?? 0);
        if (!$store_id) {
            wp_send_json_error(['message' => 'store_id gerekli']);
        }

        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'Cloud Sync API key yok']);
        }

        // Return URL: WP plugin hepsiburada sayfası
        $return_url = admin_url('admin.php?page=isarud-amazon');

        $response = wp_remote_post('https://isarud.com/api/v2/marketplace/amazon/connect-url', [
            'timeout' => 30,
            'headers' => [
                'X-Marketplace-Key' => $api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'store_id' => $store_id,
                'return_url' => $return_url,
            ]),
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'HTTP error: ' . $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        wp_send_json($data ?? ['success' => false, 'message' => 'Invalid response']);
    }

    /**
     * Show admin notice when ?isarud_amazon_connected=1 query param exists
     * Hook: admin_notices
     */
    public function maybe_show_callback_notice(): void {
        if (!isset($_GET['isarud_amazon_connected']) || $_GET['isarud_amazon_connected'] != '1') {
            return;
        }
        $store_id = sanitize_text_field($_GET['store_id'] ?? '');
        $merchant_id = sanitize_text_field($_GET['merchant_id'] ?? '');
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>✅ <?php _e('Amazon connected!', 'api-isarud'); ?></strong>
                <?php if ($merchant_id): ?>
                    <?php printf(esc_html__('Merchant ID: %s', 'api-isarud'), '<code>' . esc_html($merchant_id) . '</code>'); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}