<?php
if (!defined('ABSPATH')) exit;

class Isarud_Order_Management {
    private static ?self $instance = null;
    public static function instance(): self { if (!self::$instance) self::$instance = new self(); return self::$instance; }

    public function __construct() {
        add_action('wp_ajax_isarud_update_mp_order_status', [$this, 'ajax_update_status']);
        add_action('wp_ajax_isarud_assign_cargo', [$this, 'ajax_assign_cargo']);
        add_action('wp_ajax_isarud_get_cargo_companies', [$this, 'ajax_get_cargo_companies']);
        // Auto-update marketplace when WC order status changes
        add_action('woocommerce_order_status_changed', [$this, 'on_wc_status_change'], 10, 4);
    }

    /**
     * When WC order status changes, update marketplace order too
     */
    public function on_wc_status_change(int $order_id, string $old, string $new, $order): void {
        $mp = $order->get_meta('_isarud_marketplace');
        $ext_id = $order->get_meta('_isarud_external_order_id');
        if (!$mp || !$ext_id) return;

        $mp_status = $this->map_wc_to_mp_status($new, $mp);
        if (!$mp_status) return;

        $result = $this->update_marketplace_status($mp, $ext_id, $mp_status, $order);
        $order->add_order_note(sprintf(
            __('Isarud: order %s status updated → %s %s','api-isarud'),
            ucfirst($mp), $mp_status,
            isset($result['error']) ? '(Hata: ' . $result['error'] . ')' : '✓'
        ));
    }

    /**
     * Update order status on marketplace
     */
    public function update_marketplace_status(string $mp, string $ext_id, string $status, $order = null): array {
        return match($mp) {
            'trendyol' => $this->update_trendyol_status($ext_id, $status, $order),
            'hepsiburada' => $this->update_hepsiburada_status($ext_id, $status, $order),
                'pazarama' => $this->update_pazarama_status($ext_id, $status, $order),
            'n11' => $this->update_n11_status($ext_id, $status),
            default => ['error' => 'Unsupported marketplace'],
        };
    }

    private function update_trendyol_status(string $package_id, string $status, $order): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        $tracking = $order ? $order->get_meta('_tracking_number') : '';

        $data = match($status) {
            'Picking' => ['lines' => $this->get_trendyol_lines($order), 'params' => []],
            'Shipped' => ['trackingNumber' => $tracking, 'status' => 'Shipped'],
            'Cancelled' => ['lines' => $this->get_trendyol_lines($order), 'params' => []],
            default => ['status' => $status],
        };

        return $this->mp_request('trendyol',
            "suppliers/{$seller_id}/shipment-packages/{$package_id}",
            'PUT', $data
        );
    }

    private function update_amazon_status(string $order_id, string $status, $order): array {
        if (!class_exists('Isarud_Amazon')) {
            return ['error' => __('Amazon module not loaded','api-isarud')];
        }
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) return ['error' => __('No Cloud Sync connection','api-isarud')];

        // Amazon shipment confirmation Feeds API ile yapılır - şimdilik stub
        return [
            'success' => false,
            'message' => __('Amazon order update coming soon - will be active after Feeds API integration.','api-isarud'),
            'order_id' => $order_id,
        ];
    }

    private function update_pazarama_status(string $order_id, string $status, $order): array {
        if (!class_exists('Isarud_Pazarama')) {
            return ['success' => false, 'message' => __('Pazarama module not loaded','api-isarud')];
        }

        $svc = \Isarud_Pazarama::instance();
        if (!method_exists($svc, 'update_package_status')) {
            return ['success' => false, 'message' => 'update_package_status metodu yok'];
        }

        // Pazarama statüleri: Shipped, Delivered, Cancelled
        $paz_status = match($status) {
            'processing' => 'Approved',
            'shipped' => 'Shipped',
            'completed' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => 'Approved',
        };

        return $svc->update_package_status($order_id, $paz_status);
    }

    private function update_hepsiburada_status(string $package_id, string $status, $order): array {
        // Bridge to Isarud_Hepsiburada (v6.6.6+ - server-side credentials)
        if (!class_exists('Isarud_Hepsiburada')) {
            return ['error' => __('Hepsiburada module not loaded','api-isarud')];
        }
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) return ['error' => __('No Cloud Sync connection','api-isarud')];

        $tracking = $order ? $order->get_meta('_tracking_number') : '';

        if ($status === 'Shipped' && $tracking) {
            return Isarud_Hepsiburada::instance()->submit_tracking($package_id, $tracking);
        }

        return Isarud_Hepsiburada::instance()->update_package_status($package_id, $status);
    }

    private function update_n11_status(string $order_id, string $status): array {
        if (!class_exists('Isarud_N11')) {
            return ['error' => __('N11 module not loaded','api-isarud')];
        }

        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) return ['error' => __('No Cloud Sync connection','api-isarud')];

        // N11 status mapping → modern REST
        $updates = match ($status) {
            'Shipped' => ['status' => 'PROCESSED', 'action' => 'ship'],
            'Cancelled' => ['status' => 'CANCELLED', 'action' => 'reject'],
            'Picking' => ['status' => 'PROCESSING', 'action' => 'pack'],
            default => ['status' => $status],
        };

        // Bridge: WP plugin → isarud.com → N11 (modern REST)
        return Isarud_N11::instance()->update_order($order_id, $updates);
    }

    /**
     * Assign cargo company to Trendyol order
     */
    public function assign_cargo_trendyol(string $package_id, int $cargo_company_id, string $tracking_number): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        return $this->mp_request('trendyol',
            "suppliers/{$seller_id}/shipment-packages/{$package_id}",
            'PUT',
            ['trackingNumber' => $tracking_number, 'cargoCompanyId' => $cargo_company_id, 'status' => 'Shipped']
        );
    }

    /**
     * Get Trendyol cargo companies
     */
    public function get_cargo_companies(): array {
        return $this->mp_request('trendyol', 'shipment-providers');
    }

    private function get_trendyol_lines($order): array {
        if (!$order) return [];
        $lines = [];
        foreach ($order->get_items() as $item) {
            $lines[] = ['lineId' => $item->get_id(), 'quantity' => $item->get_quantity()];
        }
        return $lines;
    }

    private function map_wc_to_mp_status(string $wc_status, string $mp): ?string {
        return match($mp) {
            'trendyol' => match($wc_status) {
                'processing' => 'Picking', 'completed' => 'Shipped', 'cancelled' => 'Cancelled', default => null,
            },
            'hepsiburada' => match($wc_status) {
                'processing' => 'Picking', 'completed' => 'Shipped', 'cancelled' => 'Cancelled', default => null,
            },
            'n11' => match($wc_status) {
                'completed' => 'Shipped', 'cancelled' => 'Cancelled', default => null,
            },
            default => null,
        };
    }

    public function ajax_update_status(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $order = wc_get_order(intval($_POST['order_id'] ?? 0));
        if (!$order) wp_send_json_error('Order not found');
        $mp = $order->get_meta('_isarud_marketplace');
        $ext_id = $order->get_meta('_isarud_external_order_id');
        $status = sanitize_text_field($_POST['mp_status'] ?? '');
        if (!$mp || !$ext_id || !$status) wp_send_json_error('Missing data');
        $result = $this->update_marketplace_status($mp, $ext_id, $status, $order);
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    public function ajax_assign_cargo(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $package_id = sanitize_text_field($_POST['package_id'] ?? '');
        $cargo_id = intval($_POST['cargo_company_id'] ?? 0);
        $tracking = sanitize_text_field($_POST['tracking_number'] ?? '');
        if (!$package_id || !$cargo_id || !$tracking) wp_send_json_error('Missing data');
        $result = $this->assign_cargo_trendyol($package_id, $cargo_id, $tracking);
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    public function ajax_get_cargo_companies(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        wp_send_json_success($this->get_cargo_companies());
    }

    private function mp_request(string $mp, string $endpoint, string $method = 'GET', $data = null): array {
        $plugin = Isarud_Plugin::instance();
        $ref = new \ReflectionMethod($plugin, 'marketplace_request');
        $ref->setAccessible(true);
        return $ref->invoke($plugin, $mp, $endpoint, $method, $data);
    }
    private function get_cred(string $mp, string $key): string {
        $plugin = Isarud_Plugin::instance();
        $ref = new \ReflectionMethod($plugin, 'get_cred');
        $ref->setAccessible(true);
        return $ref->invoke($plugin, $mp, $key);
    }
}
