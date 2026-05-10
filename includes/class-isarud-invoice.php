<?php
if (!defined('ABSPATH')) exit;

class Isarud_Invoice {
    private static ?self $instance = null;
    public static function instance(): self { if (!self::$instance) self::$instance = new self(); return self::$instance; }

    public function __construct() {
        add_action('wp_ajax_isarud_send_invoice', [$this, 'ajax_send_invoice']);
    }

    /**
     * Send invoice link to Trendyol
     */
    public function send_trendyol_invoice(string $shipment_package_id, string $invoice_url): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        return $this->mp_request('trendyol', "suppliers/{$seller_id}/invoices/links", 'POST', [
            'invoiceLink' => $invoice_url, 'shipmentPackageId' => $shipment_package_id,
        ]);
    }

    /**
     * Send invoice to Hepsiburada
     */
    public function send_hepsiburada_invoice(string $package_number, string $invoice_url): array {
        $merchant_id = $this->get_cred('hepsiburada', 'merchant_id');
        return $this->mp_request('hepsiburada', "invoices/merchantid/{$merchant_id}", 'POST', [
            'packageNumber' => $package_number, 'invoiceUrl' => $invoice_url,
        ]);
    }

    /**
     * Auto-send invoice when WC order completed (if invoice URL exists)
     */
    public function auto_send_invoice(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $mp = $order->get_meta('_isarud_marketplace');
        $ext_id = $order->get_meta('_isarud_external_order_id');
        $invoice_url = $order->get_meta('_isarud_invoice_url');
        if (!$mp || !$ext_id || !$invoice_url) return;

        $result = match($mp) {
            'trendyol' => $this->send_trendyol_invoice($ext_id, $invoice_url),
            'hepsiburada' => $this->send_hepsiburada_invoice($ext_id, $invoice_url),
            default => null,
        };

        if ($result) {
            $order->add_order_note('Isarud: Fatura linki ' . ucfirst($mp) . '\'a gönderildi' . (isset($result['error']) ? ' (Hata: ' . $result['error'] . ')' : ' ✓'));
        }
    }

    public function ajax_send_invoice(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $order = wc_get_order(intval($_POST['order_id'] ?? 0));
        if (!$order) wp_send_json_error('Order not found');
        $mp = $order->get_meta('_isarud_marketplace');
        $ext_id = $order->get_meta('_isarud_external_order_id');
        $invoice_url = sanitize_url($_POST['invoice_url'] ?? '');
        if (!$mp || !$ext_id || !$invoice_url) wp_send_json_error('Missing data');
        $order->update_meta_data('_isarud_invoice_url', $invoice_url);
        $order->save();
        $result = match($mp) {
            'trendyol' => $this->send_trendyol_invoice($ext_id, $invoice_url),
            'hepsiburada' => $this->send_hepsiburada_invoice($ext_id, $invoice_url),
            default => ['error' => 'Not supported'],
        };
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    private function mp_request(string $mp, string $ep, string $m = 'GET', $d = null): array {
        $p = Isarud_Plugin::instance(); $r = new \ReflectionMethod($p, 'marketplace_request'); $r->setAccessible(true); return $r->invoke($p, $mp, $ep, $m, $d);
    }
    private function get_cred(string $mp, string $k): string {
        $p = Isarud_Plugin::instance(); $r = new \ReflectionMethod($p, 'get_cred'); $r->setAccessible(true); return $r->invoke($p, $mp, $k);
    }
}
