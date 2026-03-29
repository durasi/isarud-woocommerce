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
            'Isarud: %s sipariş durumu güncellendi → %s %s',
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

    private function update_hepsiburada_status(string $package_id, string $status, $order): array {
        $merchant_id = $this->get_cred('hepsiburada', 'merchant_id');
        $tracking = $order ? $order->get_meta('_tracking_number') : '';

        if ($status === 'Shipped' && $tracking) {
            return $this->mp_request('hepsiburada',
                "packages/merchantid/{$merchant_id}/shipment",
                'POST',
                ['packageNumber' => $package_id, 'trackingNumber' => $tracking]
            );
        }

        return $this->mp_request('hepsiburada',
            "packages/merchantid/{$merchant_id}/{$package_id}/status",
            'PUT', ['status' => $status]
        );
    }

    private function update_n11_status(string $order_id, string $status): array {
        global $wpdb;
        $creds = $wpdb->get_var("SELECT credentials FROM {$wpdb->prefix}isarud_credentials WHERE marketplace='n11' AND is_active=1");
        if (!$creds) return ['error' => 'N11 not configured'];
        $c = json_decode($creds, true);

        $action = match($status) {
            'Shipped' => 'MakeOrderPackageStatusUpdate',
            'Cancelled' => 'OrderItemReject',
            default => null,
        };
        if (!$action) return ['error' => 'Unsupported N11 status'];

        $xml = '<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sch="http://www.n11.com/ws/schemas"><soapenv:Header><sch:Authentication><sch:appKey>' . esc_xml($c['api_key'] ?? '') . '</sch:appKey><sch:appSecret>' . esc_xml($c['api_secret'] ?? '') . '</sch:appSecret></sch:Authentication></soapenv:Header><soapenv:Body><sch:' . $action . 'Request><sch:orderItemList><sch:orderItem><sch:id>' . esc_xml($order_id) . '</sch:id></sch:orderItem></sch:orderItemList></sch:' . $action . 'Request></soapenv:Body></soapenv:Envelope>';

        $r = wp_remote_post('https://api.n11.com/ws/OrderService/', [
            'headers' => ['Content-Type' => 'text/xml; charset=utf-8'], 'body' => $xml, 'timeout' => 30,
        ]);

        return is_wp_error($r) ? ['error' => $r->get_error_message()] : ['success' => true];
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
