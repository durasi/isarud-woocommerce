<?php
/**
 * Isarud eBay — isarud.com Cloud Bridge (API v2)
 *
 * eBay OAuth (19-scope) isarud.com tarafinda yonetilir. WP plugin sadece
 * Cloud Sync API key ile bridge endpoint'lerinden veri ceker.
 * Kullanici eBay'i bir kez isarud.com'da baglar; WP buradan okur.
 *
 * Bridge endpoint'leri: api/v2/marketplace/ebay/*
 *   status, listings, orders, sync/stock, identity, finances, analytics
 */

if (!defined('ABSPATH')) exit;

class Isarud_Ebay {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_ebay_status',     [$this, 'ajax_status']);
        add_action('wp_ajax_isarud_ebay_listings',   [$this, 'ajax_listings']);
        add_action('wp_ajax_isarud_ebay_orders',     [$this, 'ajax_orders']);
        add_action('wp_ajax_isarud_ebay_sync_stock', [$this, 'ajax_sync_stock']);
        add_action('wp_ajax_isarud_ebay_identity',   [$this, 'ajax_identity']);
        add_action('wp_ajax_isarud_ebay_finances',   [$this, 'ajax_finances']);
        add_action('wp_ajax_isarud_ebay_analytics',  [$this, 'ajax_analytics']);
    }

    /**
     * HTTP bridge — isarud.com/api/v2/marketplace/ebay/* endpoint'lerine istek atar
     */
    private function bridge_request(string $endpoint, array $data = [], string $method = 'GET'): array {
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => __('Cloud Sync API key missing. Please connect from the Cloud Sync page first.', 'api-isarud'),
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
    // BRIDGE METHODS
    // ═══════════════════════════════════════════════════════════════

    public function get_status(): array {
        return $this->bridge_request('ebay/status');
    }

    public function get_listings(int $page = 0, int $size = 50): array {
        return $this->bridge_request('ebay/listings', [
            'page' => $page,
            'size' => $size,
        ]);
    }

    public function get_orders(int $days = 30, int $page = 0, int $size = 50): array {
        return $this->bridge_request('ebay/orders', [
            'days' => $days,
            'page' => $page,
            'size' => $size,
        ]);
    }

    public function sync_stock_bulk(array $items): array {
        return $this->bridge_request('ebay/sync/stock', ['items' => $items], 'POST');
    }

    public function get_identity(): array {
        return $this->bridge_request('ebay/identity');
    }

    public function get_finances(): array {
        return $this->bridge_request('ebay/finances');
    }

    public function get_analytics(): array {
        return $this->bridge_request('ebay/analytics');
    }

    // ═══════════════════════════════════════════════════════════════
    // AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════

    private function check_ajax(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Yetki yok'], 403);
        }
        check_ajax_referer('isarud_ebay_nonce', 'nonce');
    }

    public function ajax_status(): void {
        $this->check_ajax();
        wp_send_json($this->get_status());
    }

    public function ajax_listings(): void {
        $this->check_ajax();
        $page = (int) ($_POST['page'] ?? 0);
        $size = (int) ($_POST['size'] ?? 50);
        wp_send_json($this->get_listings($page, $size));
    }

    public function ajax_orders(): void {
        $this->check_ajax();
        $days = (int) ($_POST['days'] ?? 30);
        $page = (int) ($_POST['page'] ?? 0);
        $size = (int) ($_POST['size'] ?? 50);
        wp_send_json($this->get_orders($days, $page, $size));
    }

    public function ajax_sync_stock(): void {
        $this->check_ajax();
        $items = $_POST['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            wp_send_json_error(['message' => 'items array gerekli']);
        }
        wp_send_json($this->sync_stock_bulk($items));
    }

    public function ajax_identity(): void {
        $this->check_ajax();
        wp_send_json($this->get_identity());
    }

    public function ajax_finances(): void {
        $this->check_ajax();
        wp_send_json($this->get_finances());
    }

    public function ajax_analytics(): void {
        $this->check_ajax();
        wp_send_json($this->get_analytics());
    }
}
