<?php
/**
 * class-isarud-ciceksepeti.php — WP Plugin Ciceksepeti Bridge Client
 * v6.8.0 - Ciceksepeti entegrasyonu (Pazarama clone + CS sozlesme farklari)
 *
 * Mevcut Isarud_Cloud_Sync::api_request() pattern'ini takip eder.
 * Auth: X-Marketplace-Key header (Cloud Sync'te zaten calisiyor)
 *
 * Sunucu sozlesmesi (isarud.com routes/api.php ciceksepeti bloku):
 *   status, listings (page 1-tabanli), products create/update (asenkron batchId),
 *   products/{code} DELETE (CS API silme sunmaz -> sunucu 501 + aciklama),
 *   sync/stock (asenkron batchId), batch-status/{batchId},
 *   categories (+{id}/attributes), orders (max 2 hafta),
 *   orders/{orderId} PUT {updates:{orderItemStatusId zorunlu, cargoBusinessId?, shipmentNumber?}},
 *   returns, returns/{returnId} PUT {action: approve|reject},
 *   returns/{orderItemId}/received POST,
 *   questions, questions/{questionId}/answer POST {text}
 *
 * GUVENLIK:
 * - Bu dosyada hicbir secret yok, sadece HTTP client kodu
 * - Ciceksepeti API key / Seller ID ASLA WP'ye gelmez (sunucuda kalir)
 * - Tum istekler isarud.com'a gider, Cloud Sync key ile auth
 */

if (!defined('ABSPATH')) exit;

class Isarud_Ciceksepeti {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        // Status + Listings + Delete
        add_action('wp_ajax_isarud_ciceksepeti_status',              [$this, 'ajax_status']);
        add_action('wp_ajax_isarud_ciceksepeti_listings',            [$this, 'ajax_listings']);
        add_action('wp_ajax_isarud_ciceksepeti_listing_delete',      [$this, 'ajax_listing_delete']);

        // Stock & Price (asenkron batch)
        add_action('wp_ajax_isarud_ciceksepeti_sync_stock',          [$this, 'ajax_sync_stock']);
        add_action('wp_ajax_isarud_ciceksepeti_sync_single',         [$this, 'ajax_sync_single']);
        add_action('wp_ajax_isarud_ciceksepeti_batch_status',        [$this, 'ajax_batch_status']);

        // Categories
        add_action('wp_ajax_isarud_ciceksepeti_categories',          [$this, 'ajax_categories']);
        add_action('wp_ajax_isarud_ciceksepeti_category_attributes', [$this, 'ajax_category_attributes']);

        // Orders
        add_action('wp_ajax_isarud_ciceksepeti_orders',              [$this, 'ajax_orders']);
        add_action('wp_ajax_isarud_ciceksepeti_package_status',      [$this, 'ajax_package_status']);
        add_action('wp_ajax_isarud_ciceksepeti_package_tracking',    [$this, 'ajax_package_tracking']);

        // Returns
        add_action('wp_ajax_isarud_ciceksepeti_returns',             [$this, 'ajax_returns']);
        add_action('wp_ajax_isarud_ciceksepeti_return_approve',      [$this, 'ajax_return_approve']);
        add_action('wp_ajax_isarud_ciceksepeti_return_reject',       [$this, 'ajax_return_reject']);
        add_action('wp_ajax_isarud_ciceksepeti_return_received',     [$this, 'ajax_return_received']);

        // Customer Questions (CS API destekliyor — cevap yayinlama dahil)
        add_action('wp_ajax_isarud_ciceksepeti_questions',           [$this, 'ajax_questions']);
        add_action('wp_ajax_isarud_ciceksepeti_question_answer',     [$this, 'ajax_question_answer']);

        // Connect Flow
        add_action('wp_ajax_isarud_ciceksepeti_stores',              [$this, 'ajax_stores']);
        add_action('wp_ajax_isarud_ciceksepeti_connect_url',         [$this, 'ajax_connect_url']);
        add_action('admin_notices',                                     [$this, 'maybe_show_callback_notice']);
    }

    // ===============================================================
    // BRIDGE REQUEST (HTTP client — Pazarama pattern paritesi)
    // ===============================================================

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

    // ===============================================================
    // STATUS + LISTINGS + DELETE
    // ===============================================================

    public function get_status(): array {
        return $this->bridge_request('ciceksepeti/status');
    }

    /** CS listeleme sayfasi 1-tabanlidir (Pazarama 0-tabanliydi). */
    public function get_listings(int $page = 1, int $size = 50, string $filter = 'all'): array {
        return $this->bridge_request('ciceksepeti/listings', [
            'page' => max(1, $page),
            'size' => $size,
            'filter' => $filter,
        ]);
    }

    /** CS API urun silme sunmaz; sunucu 501 + isActive=false onerisi doner, mesaj UI'da gosterilir. */
    public function delete_listing(string $code): array {
        return $this->bridge_request("ciceksepeti/products/{$code}", [], 'DELETE');
    }

    // ===============================================================
    // STOCK & PRICE (ASENKRON — batchId doner, max 4 saat islenir)
    // ===============================================================

    /** items: [{stockCode, stockQuantity?, salesPrice?, listPrice?}] — listPrice tek basina gonderilemez (CS kurali). */
    public function sync_stock_bulk(array $items): array {
        return $this->bridge_request('ciceksepeti/sync/stock', ['items' => $items], 'POST');
    }

    public function sync_stock_single(string $code, int $quantity, float $price, ?float $listPrice = null): array {
        $item = [
            'stockCode' => $code,
            'stockQuantity' => $quantity,
        ];
        if ($price > 0) {
            $item['salesPrice'] = $price;
        }
        if ($listPrice !== null && $price > 0) {
            $item['listPrice'] = $listPrice;
        }
        return $this->sync_stock_bulk([$item]);
    }

    public function get_batch_status(string $batchId): array {
        return $this->bridge_request('ciceksepeti/batch-status/' . rawurlencode($batchId));
    }

    // ===============================================================
    // CATEGORIES
    // ===============================================================

    public function get_categories(?int $parentId = null): array {
        $params = [];
        if ($parentId !== null) {
            $params['parentId'] = $parentId;
        }
        return $this->bridge_request('ciceksepeti/categories', $params);
    }

    public function get_category_attributes(int $categoryId): array {
        return $this->bridge_request("ciceksepeti/categories/{$categoryId}/attributes");
    }

    // ===============================================================
    // ORDERS (CS siparis listeleme araligi MAX 2 HAFTA — sunucu da sinirlar)
    // ===============================================================

    public function get_orders(?string $status = null, int $days = 7, int $page = 0, int $size = 50): array {
        $params = [
            'days' => min(14, max(1, $days)),
            'page' => $page,
            'size' => $size,
        ];
        if ($status !== null) {
            $params['status'] = $status;
        }
        return $this->bridge_request('ciceksepeti/orders', $params);
    }

    /**
     * CS siparis guncelleme sozlesmesi: PUT orders/{orderId}
     * Body: {updates: {orderItemStatusId (zorunlu, int), cargoBusinessId?, shipmentNumber?}}
     */
    public function update_order(string $orderId, array $updates): array {
        return $this->bridge_request("ciceksepeti/orders/{$orderId}", ['updates' => $updates], 'PUT');
    }

    public function update_package_status(string $orderId, int $orderItemStatusId): array {
        return $this->update_order($orderId, ['orderItemStatusId' => $orderItemStatusId]);
    }

    public function submit_tracking(string $orderId, int $orderItemStatusId, string $shipmentNumber, ?int $cargoBusinessId = null): array {
        $updates = [
            'orderItemStatusId' => $orderItemStatusId,
            'shipmentNumber' => $shipmentNumber,
        ];
        if ($cargoBusinessId !== null) {
            $updates['cargoBusinessId'] = $cargoBusinessId;
        }
        return $this->update_order($orderId, $updates);
    }

    // ===============================================================
    // RETURNS — CS: PUT returns/{returnId} {action: approve|reject}
    //           (sunucu action -> CS process 1|3 cevirir; "iade tedarikcide" sarti)
    // ===============================================================

    public function get_returns(int $page = 0, int $size = 50, ?int $statusId = null): array {
        $params = [
            'page' => $page,
            'size' => $size,
        ];
        if ($statusId !== null) {
            $params['status_id'] = $statusId;
        }
        return $this->bridge_request('ciceksepeti/returns', $params);
    }

    public function approve_return(string $returnId): array {
        return $this->bridge_request("ciceksepeti/returns/{$returnId}", ['action' => 'approve'], 'PUT');
    }

    public function reject_return(string $returnId): array {
        return $this->bridge_request("ciceksepeti/returns/{$returnId}", ['action' => 'reject'], 'PUT');
    }

    /** "Iade Sureci Baslatildi" -> "Iade Tedarikcide" gecisi. */
    public function return_received(string $orderItemId): array {
        return $this->bridge_request("ciceksepeti/returns/{$orderItemId}/received", [], 'POST');
    }

    // ===============================================================
    // CUSTOMER QUESTIONS (CS: cekme + cevap yayinlama, branchActionId=1 sunucuda)
    // ===============================================================

    public function get_questions(int $page = 1, ?string $startDate = null, ?string $endDate = null): array {
        $params = ['page' => max(1, $page)];
        if ($startDate !== null) $params['start_date'] = $startDate;
        if ($endDate !== null)   $params['end_date'] = $endDate;
        return $this->bridge_request('ciceksepeti/questions', $params);
    }

    public function answer_question(string $questionId, string $text): array {
        return $this->bridge_request("ciceksepeti/questions/{$questionId}/answer", ['text' => $text], 'POST');
    }

    // ===============================================================
    // AJAX HANDLERS
    // ===============================================================

    private function check_ajax(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions','api-isarud')], 403);
        }
        check_ajax_referer('isarud_ciceksepeti_nonce', 'nonce');
    }

    public function ajax_status(): void {
        $this->check_ajax();
        wp_send_json($this->get_status());
    }

    public function ajax_listings(): void {
        $this->check_ajax();
        $page = (int) ($_POST['page'] ?? 1);
        $size = (int) ($_POST['size'] ?? 50);
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        wp_send_json($this->get_listings($page, $size, $filter));
    }

    public function ajax_listing_delete(): void {
        $this->check_ajax();
        $code = sanitize_text_field($_POST['code'] ?? '');
        if (!$code) wp_send_json_error(['message' => 'code required']);
        wp_send_json($this->delete_listing($code));
    }

    public function ajax_sync_stock(): void {
        $this->check_ajax();
        $items = $_POST['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            wp_send_json_error(['message' => 'items array required']);
        }
        wp_send_json($this->sync_stock_bulk($items));
    }

    public function ajax_sync_single(): void {
        $this->check_ajax();
        $code = sanitize_text_field($_POST['code'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $listPrice = isset($_POST['list_price']) ? (float) $_POST['list_price'] : null;
        if (!$code) wp_send_json_error(['message' => 'code required']);
        wp_send_json($this->sync_stock_single($code, $quantity, $price, $listPrice));
    }

    public function ajax_batch_status(): void {
        $this->check_ajax();
        $batchId = sanitize_text_field($_POST['batch_id'] ?? '');
        if (!$batchId) wp_send_json_error(['message' => 'batch_id required']);
        wp_send_json($this->get_batch_status($batchId));
    }

    public function ajax_categories(): void {
        $this->check_ajax();
        $parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        wp_send_json($this->get_categories($parentId));
    }

    public function ajax_category_attributes(): void {
        $this->check_ajax();
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if (!$categoryId) wp_send_json_error(['message' => 'category_id required']);
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

    public function ajax_package_status(): void {
        $this->check_ajax();
        $orderId = sanitize_text_field($_POST['order_id'] ?? '');
        $statusId = (int) ($_POST['status'] ?? ($_POST['order_item_status_id'] ?? 0));
        if (!$orderId || !$statusId) {
            wp_send_json_error(['message' => 'order_id and numeric status (orderItemStatusId) required']);
        }
        wp_send_json($this->update_package_status($orderId, $statusId));
    }

    public function ajax_package_tracking(): void {
        $this->check_ajax();
        $orderId = sanitize_text_field($_POST['order_id'] ?? '');
        $trackingNumber = sanitize_text_field($_POST['tracking_number'] ?? '');
        $statusId = (int) ($_POST['order_item_status_id'] ?? 0);
        $cargoBusinessId = isset($_POST['cargo_business_id']) ? (int) $_POST['cargo_business_id'] : null;
        if (!$orderId || !$trackingNumber || !$statusId) {
            wp_send_json_error(['message' => 'order_id, tracking_number and order_item_status_id required']);
        }
        wp_send_json($this->submit_tracking($orderId, $statusId, $trackingNumber, $cargoBusinessId));
    }

    public function ajax_returns(): void {
        $this->check_ajax();
        $page = (int) ($_POST['page'] ?? 0);
        $size = (int) ($_POST['size'] ?? 50);
        $statusId = isset($_POST['status_id']) ? (int) $_POST['status_id'] : null;
        wp_send_json($this->get_returns($page, $size, $statusId));
    }

    public function ajax_return_approve(): void {
        $this->check_ajax();
        $returnId = sanitize_text_field($_POST['return_id'] ?? '');
        if (!$returnId) wp_send_json_error(['message' => 'return_id required']);
        wp_send_json($this->approve_return($returnId));
    }

    public function ajax_return_reject(): void {
        $this->check_ajax();
        $returnId = sanitize_text_field($_POST['return_id'] ?? '');
        if (!$returnId) wp_send_json_error(['message' => 'return_id required']);
        wp_send_json($this->reject_return($returnId));
    }

    public function ajax_return_received(): void {
        $this->check_ajax();
        $orderItemId = sanitize_text_field($_POST['order_item_id'] ?? '');
        if (!$orderItemId) wp_send_json_error(['message' => 'order_item_id required']);
        wp_send_json($this->return_received($orderItemId));
    }

    public function ajax_questions(): void {
        $this->check_ajax();
        $page = (int) ($_POST['page'] ?? 1);
        $startDate = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $endDate   = isset($_POST['end_date'])   ? sanitize_text_field($_POST['end_date'])   : null;
        wp_send_json($this->get_questions($page, $startDate, $endDate));
    }

    public function ajax_question_answer(): void {
        $this->check_ajax();
        $questionId = sanitize_text_field($_POST['question_id'] ?? '');
        $text = sanitize_textarea_field($_POST['text'] ?? '');
        if (!$questionId || !$text) wp_send_json_error(['message' => 'question_id and text required']);
        wp_send_json($this->answer_question($questionId, $text));
    }

    // ===============================================================
    // CONNECT FLOW (Pazarama paritesi)
    // ===============================================================

    public function ajax_stores(): void {
        $this->check_ajax();

        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('No Cloud Sync API key. Please connect from the Cloud Sync page first.','api-isarud')]);
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

    public function ajax_connect_url(): void {
        $this->check_ajax();

        $store_id = (int) ($_POST['store_id'] ?? 0);
        if (!$store_id) {
            wp_send_json_error(['message' => 'store_id required']);
        }

        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('No Cloud Sync API key. Please connect from the Cloud Sync page first.','api-isarud')]);
        }

        $return_url = admin_url('admin.php?page=isarud-ciceksepeti');

        $response = wp_remote_post('https://isarud.com/api/v2/marketplace/ciceksepeti/connect-url', [
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

    public function maybe_show_callback_notice(): void {
        if (!isset($_GET['isarud_ciceksepeti_connected']) || $_GET['isarud_ciceksepeti_connected'] != '1') {
            return;
        }
        $seller_id = sanitize_text_field($_GET['seller_id'] ?? '');
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>&#9989; <?php _e('Ciceksepeti connected!', 'api-isarud'); ?></strong>
                <?php if ($seller_id): ?>
                    <?php printf(esc_html__('Seller ID: %s', 'api-isarud'), '<code>' . esc_html($seller_id) . '</code>'); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}
