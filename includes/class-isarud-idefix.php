<?php
/**
 * class-isarud-idefix.php — WP Plugin Idefix Bridge Client (v7.1)
 *
 * Ciceksepeti bridge_request deseninin klonu. Auth: X-Marketplace-Key.
 * Sunucu sozlesmesi (isarud.com routes/api.php idefix blogu, script-1732):
 *   status, categories, orders (page/limit/state/since), sync/stock
 *   (listings/products/returns/questions → 501 not_supported_yet, v7.2'de)
 *
 * GUVENLIK: bu dosyada secret yok; Idefix API kimligi SUNUCUDA kalir.
 */

if (!defined('ABSPATH')) exit;

class Isarud_Idefix {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_idefix_status',     [$this, 'ajax_status']);
        add_action('wp_ajax_isarud_idefix_categories', [$this, 'ajax_categories']);
        add_action('wp_ajax_isarud_idefix_orders',     [$this, 'ajax_orders']);
        add_action('wp_ajax_isarud_idefix_sync_stock', [$this, 'ajax_sync_stock']);
    }

    // ===============================================================
    // BRIDGE REQUEST (Ciceksepeti paritesi)
    // ===============================================================
    private function bridge_request(string $endpoint, array $data = [], string $method = 'GET'): array {
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            return ['success' => false, 'message' => __('No Cloud Sync API key. Please connect from the Cloud Sync page first.', 'api-isarud'), 'data' => null];
        }

        $url = $this->api_base . ltrim($endpoint, '/');
        $args = [
            'method' => strtoupper($method),
            'timeout' => 60,
            'headers' => ['X-Marketplace-Key' => $api_key, 'Accept' => 'application/json'],
            'sslverify' => true,
        ];

        if (in_array($args['method'], ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $args['headers']['Content-Type'] = 'application/json';
            if (!empty($data)) $args['body'] = wp_json_encode($data);
        } elseif ($args['method'] === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'HTTP error: ' . $response->get_error_message(), 'data' => null];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $json = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 400 || (is_array($json) && isset($json['success']) && $json['success'] === false)) {
            $msg = is_array($json) ? ($json['message'] ?? $json['error'] ?? "HTTP $code") : "HTTP $code";
            return ['success' => false, 'message' => (string) $msg, 'code' => $code, 'data' => $json];
        }

        return ['success' => true, 'code' => $code, 'data' => $json];
    }

    private function guard(): bool {
        check_ajax_referer('isarud_idefix', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'api-isarud')]);
            return false;
        }
        return true;
    }

    // ===============================================================
    // AJAX HANDLERS
    // ===============================================================
    public function ajax_status(): void {
        if (!$this->guard()) return;
        wp_send_json($this->bridge_request('idefix/status'));
    }

    public function ajax_categories(): void {
        if (!$this->guard()) return;
        wp_send_json($this->bridge_request('idefix/categories'));
    }

    public function ajax_orders(): void {
        if (!$this->guard()) return;
        $q = [
            'page'  => max(1, (int) ($_POST['page'] ?? 1)),
            'limit' => min(200, max(1, (int) ($_POST['limit'] ?? 25))),
        ];
        if (!empty($_POST['state'])) $q['state'] = sanitize_text_field(wp_unslash($_POST['state']));
        if (!empty($_POST['since'])) $q['since'] = sanitize_text_field(wp_unslash($_POST['since']));
        wp_send_json($this->bridge_request('idefix/orders', $q));
    }

    public function ajax_sync_stock(): void {
        if (!$this->guard()) return;
        $raw = (string) wp_unslash($_POST['items'] ?? '');
        $items = [];
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            $parts = array_map('trim', explode(',', $line));
            if (count($parts) < 3 || $parts[0] === '') continue;
            $items[] = ['barcode' => $parts[0], 'price' => (float) $parts[1], 'stock' => (int) $parts[2]];
        }
        if (empty($items)) {
            wp_send_json(['success' => false, 'message' => __('No valid lines. Format: barcode,price,stock (one per line).', 'api-isarud')]);
            return;
        }
        wp_send_json($this->bridge_request('idefix/sync/stock', ['items' => $items], 'POST'));
    }
}
