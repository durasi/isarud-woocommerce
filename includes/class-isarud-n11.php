<?php
/**
 * class-isarud-n11.php — WP Plugin N11 Bridge Client
 * v6.7.0 - N11 entegrasyonu (Trendyol clone, modern REST API)
 *
 * Mevcut Isarud_Cloud_Sync::api_request() pattern'ini takip eder.
 * Auth: X-Marketplace-Key header
 *
 * Endpoint'ler (12 adet):
 *   - status, listings, product CRUD (4)
 *   - stock & price sync (2)
 *   - categories & attributes (2)
 *   - orders + update (2)
 *   - tasks (async durum sorgu - N11'e özel) (1)
 *   - sync stock single (1)
 *
 * GÜVENLİK:
 * - Bu dosyada hiçbir secret yok, sadece HTTP client kodu
 * - N11 API key/secret ASLA WP'ye gelmez (sunucuda kalır)
 * - Tüm istekler isarud.com'a gider, Cloud Sync key ile auth
 */

if (!defined('ABSPATH')) exit;

class Isarud_N11 {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        // Status + Listings
        add_action('wp_ajax_isarud_n11_status',              [$this, 'ajax_status']);
        add_action('wp_ajax_isarud_n11_listings',            [$this, 'ajax_listings']);
        add_action('wp_ajax_isarud_n11_product_delete',      [$this, 'ajax_product_delete']);

        // Product create (async)
        add_action('wp_ajax_isarud_n11_product_create',      [$this, 'ajax_product_create']);

        // Stock & Price
        add_action('wp_ajax_isarud_n11_sync_stock',          [$this, 'ajax_sync_stock']);
        add_action('wp_ajax_isarud_n11_sync_single',         [$this, 'ajax_sync_single']);

        // Categories & Attributes
        add_action('wp_ajax_isarud_n11_categories',          [$this, 'ajax_categories']);
        add_action('wp_ajax_isarud_n11_category_attributes', [$this, 'ajax_category_attributes']);

        // Orders
        add_action('wp_ajax_isarud_n11_orders',              [$this, 'ajax_orders']);
        add_action('wp_ajax_isarud_n11_order_update',        [$this, 'ajax_order_update']);

        // Tasks (N11'e özel async durum sorgu)
        add_action('wp_ajax_isarud_n11_task_status',         [$this, 'ajax_task_status']);

        // Connect flow (Cloud Sync key + isarud.com'da N11 connection)
        add_action('wp_ajax_isarud_n11_stores',               [$this, 'ajax_stores']);
        add_action('wp_ajax_isarud_n11_connect_url',         [$this, 'ajax_connect_url']);
        add_action('wp_ajax_isarud_n11_disconnect',          [$this, 'ajax_disconnect']);
    }

    // ═══════════════════════════════════════════════════════════════
    // BRIDGE REQUEST — sunucu Cloud Sync API'sine HTTP istek
    // ═══════════════════════════════════════════════════════════════

    /**
     * isarud.com/api/v2/marketplace/n11/* endpoint'ine istek
     */
    private function bridge_request(string $endpoint, array $data = [], string $method = 'GET'): array {
        $api_key = get_option('isarud_cloud_api_key', '');
        if (!$api_key) {
            return ['success' => false, 'message' => 'Cloud Sync bağlantısı yok. Önce isarud.com hesabınızı bağlayın.'];
        }

        $url = $this->api_base . ltrim($endpoint, '/');
        $args = [
            'method' => strtoupper($method),
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Marketplace-Key' => $api_key,
            ],
        ];

        if (in_array($args['method'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $args['body'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        } elseif ($args['method'] === 'GET' && !empty($data)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        }

        $resp = wp_remote_request($url, $args);

        if (is_wp_error($resp)) {
            return ['success' => false, 'message' => 'Bağlantı hatası: ' . $resp->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return ['success' => false, 'message' => "Geçersiz yanıt (HTTP $code)", 'raw' => substr($body, 0, 200)];
        }

        return $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // PUBLIC METHODS — istek yapan kişi tarafından çağrılır
    // ═══════════════════════════════════════════════════════════════

    public function get_status(): array {
        return $this->bridge_request('n11/status', [], 'GET');
    }

    public function get_listings(int $page = 0, int $size = 50): array {
        return $this->bridge_request('n11/listings', ['page' => $page, 'size' => $size], 'GET');
    }

    public function delete_product(string $stockCode): array {
        return $this->bridge_request('n11/products/' . urlencode($stockCode), [], 'DELETE');
    }

    public function submit_product_batch(array $items): array {
        return $this->bridge_request('n11/products/create', ['items' => $items], 'POST');
    }

    public function get_task_status(string $taskId, int $page = 0, int $size = 100): array {
        return $this->bridge_request('n11/tasks/' . urlencode($taskId), ['page' => $page, 'size' => $size], 'GET');
    }

    public function sync_stock_bulk(array $items): array {
        return $this->bridge_request('n11/sync/stock', ['items' => $items], 'POST');
    }

    public function sync_stock_single(string $stockCode, int $quantity, float $price): array {
        return $this->sync_stock_bulk([[
            'stockCode' => $stockCode,
            'quantity' => $quantity,
            'price' => $price,
            'currencyType' => 'TL',
        ]]);
    }

    public function get_categories(?int $parentId = null): array {
        $params = [];
        if ($parentId !== null) $params['parent_id'] = $parentId;
        return $this->bridge_request('n11/categories', $params, 'GET');
    }

    public function get_category_attributes(int $categoryId): array {
        return $this->bridge_request("n11/categories/$categoryId/attributes", [], 'GET');
    }

    public function get_orders(int $page = 0, int $size = 50, ?string $since = null): array {
        $params = ['page' => $page, 'size' => $size];
        if ($since) $params['since'] = $since;
        return $this->bridge_request('n11/orders', $params, 'GET');
    }

    public function update_order(string $packageId, array $updates): array {
        return $this->bridge_request("n11/orders/$packageId/update", ['updates' => $updates], 'POST');
    }

    // ═══════════════════════════════════════════════════════════════
    // AJAX HANDLERS — WP admin paneli AJAX endpoint'leri
    // ═══════════════════════════════════════════════════════════════

    private function check_ajax(): void {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Yetki yok'], 403);
        if (!check_ajax_referer('isarud_n11_nonce', 'nonce', false)) wp_send_json_error(['message' => 'Geçersiz nonce'], 400);
    }

    public function ajax_status(): void {
        $this->check_ajax();
        wp_send_json($this->get_status());
    }

    public function ajax_listings(): void {
        $this->check_ajax();
        $page = (int)($_POST['page'] ?? 0);
        $size = (int)($_POST['size'] ?? 50);
        wp_send_json($this->get_listings($page, $size));
    }

    public function ajax_product_delete(): void {
        $this->check_ajax();
        $stockCode = sanitize_text_field($_POST['stock_code'] ?? '');
        if (!$stockCode) wp_send_json_error(['message' => 'stock_code zorunlu'], 422);
        wp_send_json($this->delete_product($stockCode));
    }

    public function ajax_product_create(): void {
        $this->check_ajax();
        $items_raw = $_POST['items'] ?? '[]';
        $items = is_string($items_raw) ? json_decode(stripslashes($items_raw), true) : $items_raw;
        if (!is_array($items)) wp_send_json_error(['message' => 'items JSON olmalı'], 422);
        wp_send_json($this->submit_product_batch($items));
    }

    public function ajax_sync_stock(): void {
        $this->check_ajax();
        $items_raw = $_POST['items'] ?? '[]';
        $items = is_string($items_raw) ? json_decode(stripslashes($items_raw), true) : $items_raw;
        if (!is_array($items)) wp_send_json_error(['message' => 'items JSON olmalı'], 422);
        wp_send_json($this->sync_stock_bulk($items));
    }

    public function ajax_sync_single(): void {
        $this->check_ajax();
        $code = sanitize_text_field($_POST['stock_code'] ?? '');
        $qty = (int)($_POST['quantity'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        if (!$code) wp_send_json_error(['message' => 'stock_code zorunlu'], 422);
        wp_send_json($this->sync_stock_single($code, $qty, $price));
    }

    public function ajax_categories(): void {
        $this->check_ajax();
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        wp_send_json($this->get_categories($parentId));
    }

    public function ajax_category_attributes(): void {
        $this->check_ajax();
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if (!$categoryId) wp_send_json_error(['message' => 'category_id zorunlu'], 422);
        wp_send_json($this->get_category_attributes($categoryId));
    }

    public function ajax_orders(): void {
        $this->check_ajax();
        $page = (int)($_POST['page'] ?? 0);
        $size = (int)($_POST['size'] ?? 50);
        $since = sanitize_text_field($_POST['since'] ?? '');
        wp_send_json($this->get_orders($page, $size, $since ?: null));
    }

    public function ajax_order_update(): void {
        $this->check_ajax();
        $packageId = sanitize_text_field($_POST['package_id'] ?? '');
        $updates_raw = $_POST['updates'] ?? '{}';
        $updates = is_string($updates_raw) ? json_decode(stripslashes($updates_raw), true) : $updates_raw;
        if (!$packageId) wp_send_json_error(['message' => 'package_id zorunlu'], 422);
        if (!is_array($updates)) wp_send_json_error(['message' => 'updates JSON olmalı'], 422);
        wp_send_json($this->update_order($packageId, $updates));
    }

    public function ajax_task_status(): void {
        $this->check_ajax();
        $taskId = sanitize_text_field($_POST['task_id'] ?? '');
        if (!$taskId) wp_send_json_error(['message' => 'task_id zorunlu'], 422);
        $page = (int)($_POST['page'] ?? 0);
        $size = (int)($_POST['size'] ?? 100);
        wp_send_json($this->get_task_status($taskId, $page, $size));
    }

    // ═══════════════════════════════════════════════════════════════
    // CONNECT FLOW — store seçim modal + connect URL + disconnect
    // (Trendyol/Etsy paterninde, generic /api/v2/cloud-sync/stores)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get user's stores — Trendyol/Etsy paritesi (generic endpoint)
     * AJAX: isarud_n11_stores
     */
    public function ajax_stores(): void {
        $this->check_ajax();

        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'Cloud Sync API key yok. Önce Cloud Sync sayfasından bağlanın.']);
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
     * Connect URL — Trendyol pattern: store_id ile birlikte isarud.com URL'si döner
     * AJAX: isarud_n11_connect_url
     */
    /**
     * Connect URL — Trendyol pattern: sunucuya POST, wp_state token alma
     * AJAX: isarud_n11_connect_url
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

        $return_url = admin_url('admin.php?page=isarud-n11');

        $response = wp_remote_post('https://isarud.com/api/v2/marketplace/n11/connect-url', [
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

    public function ajax_disconnect(): void {
        $this->check_ajax();
        $resp = $this->bridge_request("n11/disconnect", [], 'POST');
        wp_send_json($resp);
    }
}