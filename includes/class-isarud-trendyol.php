<?php
/**
 * class-isarud-trendyol.php — WP Plugin Trendyol Bridge Client
 * v6.6.0 - Trendyol entegrasyonu (Etsy clone, 21 endpoint)
 *
 * Mevcut Isarud_Cloud_Sync::api_request() pattern'ini takip eder.
 * Auth: X-Marketplace-Key header (Cloud Sync'te zaten çalışıyor)
 *
 * 8 phase, 21 endpoint:
 *   Phase 1: status + listing CRUD (5)
 *   Phase 2: stock & price sync (2)
 *   Phase 3: brand & category (2)
 *   Phase 4: orders + package status (3)
 *   Phase 5: claims (2)
 *   Phase 6: customer questions (2)
 *   Phase 7: invoice (1)
 *   Phase 8: webhooks (4)
 *
 * GÜVENLİK:
 * - Bu dosyada hiçbir secret yok, sadece HTTP client kodu
 * - Trendyol API key/secret ASLA WP'ye gelmez (sunucuda kalır)
 * - Tüm istekler isarud.com'a gider, Cloud Sync key ile auth
 */

if (!defined('ABSPATH')) exit;

class Isarud_Trendyol {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        // Phase 1 - Status + Listing CRUD
        add_action('wp_ajax_isarud_trendyol_status',              [$this, 'ajax_status']);
        add_action('wp_ajax_isarud_trendyol_listings',            [$this, 'ajax_listings']);
        add_action('wp_ajax_isarud_trendyol_listing_activate',    [$this, 'ajax_listing_activate']);
        add_action('wp_ajax_isarud_trendyol_listing_deactivate',  [$this, 'ajax_listing_deactivate']);
        add_action('wp_ajax_isarud_trendyol_listing_delete',      [$this, 'ajax_listing_delete']);

        // Phase 2 - Stock & Price
        add_action('wp_ajax_isarud_trendyol_sync_stock',          [$this, 'ajax_sync_stock']);
        add_action('wp_ajax_isarud_trendyol_sync_single',         [$this, 'ajax_sync_single']);

        // Phase 3 - Brand & Category
        add_action('wp_ajax_isarud_trendyol_brands',              [$this, 'ajax_brands']);
        add_action('wp_ajax_isarud_trendyol_categories',          [$this, 'ajax_categories']);

        // Phase 4 - Orders / Shipments
        add_action('wp_ajax_isarud_trendyol_orders',              [$this, 'ajax_orders']);
        add_action('wp_ajax_isarud_trendyol_package_status',      [$this, 'ajax_package_status']);
        add_action('wp_ajax_isarud_trendyol_package_tracking',    [$this, 'ajax_package_tracking']);

        // Phase 5 - Claims
        add_action('wp_ajax_isarud_trendyol_claims',              [$this, 'ajax_claims']);
        add_action('wp_ajax_isarud_trendyol_claim_approve',       [$this, 'ajax_claim_approve']);

        // Phase 6 - Customer Questions
        add_action('wp_ajax_isarud_trendyol_questions',           [$this, 'ajax_questions']);
        add_action('wp_ajax_isarud_trendyol_question_answer',     [$this, 'ajax_question_answer']);

        // Phase 7 - Invoice
        add_action('wp_ajax_isarud_trendyol_invoice_send',        [$this, 'ajax_invoice_send']);

        // Connect flow (Etsy paritesi)
        add_action('wp_ajax_isarud_trendyol_stores',              [$this, 'ajax_stores']);
        add_action('wp_ajax_isarud_trendyol_connect_url',         [$this, 'ajax_connect_url']);
        add_action('admin_notices',                               [$this, 'maybe_show_callback_notice']);

        // Phase 8 - Webhooks
        add_action('wp_ajax_isarud_trendyol_webhooks_list',       [$this, 'ajax_webhooks_list']);
        add_action('wp_ajax_isarud_trendyol_webhook_create',      [$this, 'ajax_webhook_create']);
        add_action('wp_ajax_isarud_trendyol_webhook_update',      [$this, 'ajax_webhook_update']);
        add_action('wp_ajax_isarud_trendyol_webhook_delete',      [$this, 'ajax_webhook_delete']);
    }

    /**
     * HTTP bridge — isarud.com/api/v2/marketplace/trendyol/* endpoint'lerine istek atar
     */
    private function bridge_request(string $endpoint, array $data = [], string $method = 'GET'): array {
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'Cloud Sync API key yok. Önce Cloud Sync sayfasından bağlanın.',
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
    // PHASE 1 - STATUS + LISTING CRUD
    // ═══════════════════════════════════════════════════════════════

    public function get_status(): array {
        return $this->bridge_request('trendyol/status');
    }

    public function get_listings(int $page = 0, int $size = 50, string $filter = 'all'): array {
        return $this->bridge_request('trendyol/listings', [
            'page' => $page,
            'size' => $size,
            'filter' => $filter,
        ]);
    }

    public function activate_listing(string $barcode): array {
        return $this->bridge_request("trendyol/listings/{$barcode}/activate", [], 'POST');
    }

    public function deactivate_listing(string $barcode): array {
        return $this->bridge_request("trendyol/listings/{$barcode}/deactivate", [], 'POST');
    }

    public function delete_listing(string $barcode): array {
        return $this->bridge_request("trendyol/listings/{$barcode}", [], 'DELETE');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2 - STOCK & PRICE
    // ═══════════════════════════════════════════════════════════════

    public function sync_stock_bulk(array $items): array {
        return $this->bridge_request('trendyol/sync/stock', ['items' => $items], 'POST');
    }

    public function sync_stock_single(string $barcode, int $quantity, float $salePrice, ?float $listPrice = null): array {
        $data = [
            'barcode' => $barcode,
            'quantity' => $quantity,
            'salePrice' => $salePrice,
        ];
        if ($listPrice !== null) $data['listPrice'] = $listPrice;
        return $this->bridge_request('trendyol/sync/single', $data, 'POST');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 3 - BRAND & CATEGORY
    // ═══════════════════════════════════════════════════════════════

    public function search_brands(string $query): array {
        return $this->bridge_request('trendyol/brands', ['q' => $query]);
    }

    public function get_categories(): array {
        return $this->bridge_request('trendyol/categories');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 4 - ORDERS / SHIPMENTS
    // ═══════════════════════════════════════════════════════════════

    public function get_orders(?string $status = null, int $days = 7, int $page = 0, int $size = 50): array {
        $params = ['days' => $days, 'page' => $page, 'size' => $size];
        if ($status) $params['status'] = $status;
        return $this->bridge_request('trendyol/orders', $params);
    }

    public function update_package_status(string $packageId, string $status, array $lines = []): array {
        return $this->bridge_request("trendyol/orders/{$packageId}/status", [
            'status' => $status,
            'lines' => $lines,
        ], 'POST');
    }

    public function submit_tracking(string $packageId, string $trackingNumber): array {
        return $this->bridge_request("trendyol/orders/{$packageId}/tracking", [
            'tracking_number' => $trackingNumber,
        ], 'POST');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 5 - CLAIMS
    // ═══════════════════════════════════════════════════════════════

    public function get_claims(int $days = 30, int $page = 0, int $size = 50): array {
        return $this->bridge_request('trendyol/claims', [
            'days' => $days, 'page' => $page, 'size' => $size,
        ]);
    }

    public function approve_claim(string $claimId, array $lineIds): array {
        return $this->bridge_request("trendyol/claims/{$claimId}/approve", [
            'line_ids' => $lineIds,
        ], 'POST');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 6 - CUSTOMER QUESTIONS
    // ═══════════════════════════════════════════════════════════════

    public function get_questions(string $status = 'WAITING_FOR_ANSWER', int $page = 0, int $size = 50): array {
        return $this->bridge_request('trendyol/questions', [
            'status' => $status, 'page' => $page, 'size' => $size,
        ]);
    }

    public function answer_question(string $questionId, string $text): array {
        return $this->bridge_request("trendyol/questions/{$questionId}/answer", [
            'text' => $text,
        ], 'POST');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 7 - INVOICE
    // ═══════════════════════════════════════════════════════════════

    public function send_invoice(string $shipmentPackageId, string $invoiceLink, string $invoiceNumber, ?string $invoiceDateTime = null): array {
        $data = [
            'shipment_package_id' => $shipmentPackageId,
            'invoice_link' => $invoiceLink,
            'invoice_number' => $invoiceNumber,
        ];
        if ($invoiceDateTime) $data['invoice_date_time'] = $invoiceDateTime;
        return $this->bridge_request('trendyol/invoices', $data, 'POST');
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 8 - WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    public function list_webhooks(): array {
        return $this->bridge_request('trendyol/webhooks');
    }

    public function create_webhook(string $url, array $eventTypes, ?string $username = null, ?string $password = null): array {
        $data = ['url' => $url, 'event_types' => $eventTypes];
        if ($username) $data['username'] = $username;
        if ($password) $data['password'] = $password;
        return $this->bridge_request('trendyol/webhooks', $data, 'POST');
    }

    public function update_webhook(string $webhookId, array $updates): array {
        return $this->bridge_request("trendyol/webhooks/{$webhookId}", $updates, 'PUT');
    }

    public function delete_webhook(string $webhookId): array {
        return $this->bridge_request("trendyol/webhooks/{$webhookId}", [], 'DELETE');
    }

    public function toggle_webhook(string $webhookId, bool $active): array {
        return $this->update_webhook($webhookId, ['active' => $active]);
    }

    // ═══════════════════════════════════════════════════════════════
    // AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════

    private function check_ajax(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Yetki yok'], 403);
        }
        check_ajax_referer('isarud_trendyol_nonce', 'nonce');
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

    public function ajax_listing_activate(): void {
        $this->check_ajax();
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        if (!$barcode) wp_send_json_error(['message' => 'Barcode gerekli']);
        wp_send_json($this->activate_listing($barcode));
    }

    public function ajax_listing_deactivate(): void {
        $this->check_ajax();
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        if (!$barcode) wp_send_json_error(['message' => 'Barcode gerekli']);
        wp_send_json($this->deactivate_listing($barcode));
    }

    public function ajax_listing_delete(): void {
        $this->check_ajax();
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        if (!$barcode) wp_send_json_error(['message' => 'Barcode gerekli']);
        wp_send_json($this->delete_listing($barcode));
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
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $salePrice = (float) ($_POST['salePrice'] ?? 0);
        $listPrice = isset($_POST['listPrice']) ? (float) $_POST['listPrice'] : null;
        if (!$barcode) wp_send_json_error(['message' => 'Barcode gerekli']);
        wp_send_json($this->sync_stock_single($barcode, $quantity, $salePrice, $listPrice));
    }

    public function ajax_brands(): void {
        $this->check_ajax();
        $query = sanitize_text_field($_POST['query'] ?? '');
        wp_send_json($this->search_brands($query));
    }

    public function ajax_categories(): void {
        $this->check_ajax();
        wp_send_json($this->get_categories());
    }

    public function ajax_orders(): void {
        $this->check_ajax();
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
        $days = (int) ($_POST['days'] ?? 7);
        $page = (int) ($_POST['page'] ?? 0);
        $size = (int) ($_POST['size'] ?? 50);
        wp_send_json($this->get_orders($status, $days, $page, $size));
    }

    public function ajax_package_status(): void {
        $this->check_ajax();
        $packageId = sanitize_text_field($_POST['package_id'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $lines = $_POST['lines'] ?? [];
        if (!$packageId || !$status) wp_send_json_error(['message' => 'package_id ve status gerekli']);
        wp_send_json($this->update_package_status($packageId, $status, is_array($lines) ? $lines : []));
    }

    public function ajax_package_tracking(): void {
        $this->check_ajax();
        $packageId = sanitize_text_field($_POST['package_id'] ?? '');
        $tracking = sanitize_text_field($_POST['tracking_number'] ?? '');
        if (!$packageId || !$tracking) wp_send_json_error(['message' => 'package_id ve tracking_number gerekli']);
        wp_send_json($this->submit_tracking($packageId, $tracking));
    }

    public function ajax_claims(): void {
        $this->check_ajax();
        $days = (int) ($_POST['days'] ?? 30);
        $page = (int) ($_POST['page'] ?? 0);
        wp_send_json($this->get_claims($days, $page));
    }

    public function ajax_claim_approve(): void {
        $this->check_ajax();
        $claimId = sanitize_text_field($_POST['claim_id'] ?? '');
        $lineIds = $_POST['line_ids'] ?? [];
        if (!$claimId || empty($lineIds)) wp_send_json_error(['message' => 'claim_id ve line_ids gerekli']);
        wp_send_json($this->approve_claim($claimId, array_map('sanitize_text_field', (array) $lineIds)));
    }

    public function ajax_questions(): void {
        $this->check_ajax();
        $status = sanitize_text_field($_POST['status'] ?? 'WAITING_FOR_ANSWER');
        $page = (int) ($_POST['page'] ?? 0);
        wp_send_json($this->get_questions($status, $page));
    }

    public function ajax_question_answer(): void {
        $this->check_ajax();
        $questionId = sanitize_text_field($_POST['question_id'] ?? '');
        $text = wp_kses_post($_POST['text'] ?? '');
        if (!$questionId || !$text) wp_send_json_error(['message' => 'question_id ve text gerekli']);
        if (mb_strlen($text) < 10) wp_send_json_error(['message' => 'Cevap en az 10 karakter olmalı']);
        wp_send_json($this->answer_question($questionId, $text));
    }

    public function ajax_invoice_send(): void {
        $this->check_ajax();
        $shipmentPackageId = sanitize_text_field($_POST['shipment_package_id'] ?? '');
        $invoiceLink = esc_url_raw($_POST['invoice_link'] ?? '');
        $invoiceNumber = sanitize_text_field($_POST['invoice_number'] ?? '');
        $invoiceDateTime = isset($_POST['invoice_date_time']) ? sanitize_text_field($_POST['invoice_date_time']) : null;
        if (!$shipmentPackageId || !$invoiceLink || !$invoiceNumber) {
            wp_send_json_error(['message' => 'shipment_package_id, invoice_link, invoice_number gerekli']);
        }
        wp_send_json($this->send_invoice($shipmentPackageId, $invoiceLink, $invoiceNumber, $invoiceDateTime));
    }

    public function ajax_webhooks_list(): void {
        $this->check_ajax();
        wp_send_json($this->list_webhooks());
    }

    public function ajax_webhook_create(): void {
        $this->check_ajax();
        $url = esc_url_raw($_POST['url'] ?? '');
        $eventTypes = $_POST['event_types'] ?? [];
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : null;
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : null;
        if (!$url || empty($eventTypes)) wp_send_json_error(['message' => 'url ve event_types gerekli']);
        wp_send_json($this->create_webhook(
            $url,
            array_map('sanitize_text_field', (array) $eventTypes),
            $username,
            $password
        ));
    }

    public function ajax_webhook_update(): void {
        $this->check_ajax();
        $webhookId = sanitize_text_field($_POST['webhook_id'] ?? '');
        if (!$webhookId) wp_send_json_error(['message' => 'webhook_id gerekli']);
        // Toggle active mi?
        if (isset($_POST['active'])) {
            wp_send_json($this->toggle_webhook($webhookId, (bool) $_POST['active']));
        }
        // Generic update
        $updates = [];
        if (isset($_POST['url'])) $updates['url'] = esc_url_raw($_POST['url']);
        wp_send_json($this->update_webhook($webhookId, $updates));
    }

    public function ajax_webhook_delete(): void {
        $this->check_ajax();
        $webhookId = sanitize_text_field($_POST['webhook_id'] ?? '');
        if (!$webhookId) wp_send_json_error(['message' => 'webhook_id gerekli']);
        wp_send_json($this->delete_webhook($webhookId));
    }

    // ═══════════════════════════════════════════════════════════════
    // CONNECT FLOW (Etsy paritesi)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get user's stores — same endpoint as Etsy
     * AJAX: isarud_trendyol_stores
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
     * Get Trendyol connect URL for a specific store
     * AJAX: isarud_trendyol_connect_url
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

        // Return URL: WP plugin trendyol sayfası
        $return_url = admin_url('admin.php?page=isarud-trendyol');

        $response = wp_remote_post('https://isarud.com/api/v2/marketplace/trendyol/connect-url', [
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
     * Show admin notice when ?isarud_trendyol_connected=1 query param exists
     * Hook: admin_notices
     */
    public function maybe_show_callback_notice(): void {
        if (!isset($_GET['isarud_trendyol_connected']) || $_GET['isarud_trendyol_connected'] != '1') {
            return;
        }
        $store_id = sanitize_text_field($_GET['store_id'] ?? '');
        $seller_id = sanitize_text_field($_GET['seller_id'] ?? '');
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>✅ <?php _e('Trendyol connected!', 'api-isarud'); ?></strong>
                <?php if ($seller_id): ?>
                    <?php printf(esc_html__('Seller ID: %s', 'api-isarud'), '<code>' . esc_html($seller_id) . '</code>'); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}