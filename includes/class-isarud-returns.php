<?php
if (!defined('ABSPATH')) exit;

class Isarud_Returns {
    private static ?self $instance = null;
    public static function instance(): self { if (!self::$instance) self::$instance = new self(); return self::$instance; }

    public function __construct() {
        add_action('wp_ajax_isarud_fetch_returns', [$this, 'ajax_fetch_returns']);
        add_action('wp_ajax_isarud_approve_return', [$this, 'ajax_approve_return']);
        add_action('wp_ajax_isarud_reject_return', [$this, 'ajax_reject_return']);
    }

    /**
     * Fetch returns/claims from Trendyol
     */
    public function fetch_trendyol_returns(int $days = 30): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        if (!$seller_id) return ['error' => 'Trendyol not configured'];
        $start = strtotime("-{$days} days") * 1000;
        $end = time() * 1000;
        $result = $this->mp_request('trendyol', "suppliers/{$seller_id}/claims?startDate={$start}&endDate={$end}&size=100");
        if (isset($result['error'])) return $result;
        $returns = [];
        foreach ($result['content'] ?? [] as $claim) {
            $returns[] = [
                'id' => $claim['id'] ?? '',
                'order_number' => $claim['orderNumber'] ?? '',
                'status' => $claim['claimStatus'] ?? $claim['status'] ?? '',
                'reason' => $claim['reason'] ?? $claim['claimReason'] ?? '',
                'customer' => ($claim['customerFirstName'] ?? '') . ' ' . ($claim['customerLastName'] ?? ''),
                'items' => array_map(fn($i) => [
                    'barcode' => $i['barcode'] ?? '',
                    'name' => $i['productName'] ?? '',
                    'quantity' => $i['quantity'] ?? 1,
                ], $claim['items'] ?? $claim['claimItems'] ?? []),
                'created_at' => isset($claim['createdDate']) ? date('Y-m-d H:i', $claim['createdDate'] / 1000) : '',
                'marketplace' => 'trendyol',
            ];
        }
        return ['success' => true, 'returns' => $returns, 'total' => count($returns)];
    }

    /**
     * Fetch returns from Hepsiburada
     */
    public function fetch_hepsiburada_returns(): array {
        $merchant_id = $this->get_cred('hepsiburada', 'merchant_id');
        if (!$merchant_id) return ['error' => 'Hepsiburada not configured'];
        $result = $this->mp_request('hepsiburada', "claims/merchantid/{$merchant_id}?offset=0&limit=50");
        if (isset($result['error'])) return $result;
        $returns = [];
        foreach ($result['items'] ?? $result['claims'] ?? [] as $claim) {
            $returns[] = [
                'id' => $claim['claimId'] ?? $claim['id'] ?? '',
                'order_number' => $claim['orderNumber'] ?? '',
                'status' => $claim['status'] ?? '',
                'reason' => $claim['reason'] ?? '',
                'customer' => $claim['customerName'] ?? '',
                'items' => [],
                'created_at' => $claim['createdDate'] ?? '',
                'marketplace' => 'hepsiburada',
            ];
        }
        return ['success' => true, 'returns' => $returns, 'total' => count($returns)];
    }

    /**
     * Approve return on Trendyol
     */
    public function approve_trendyol_return(string $claim_id, array $item_ids): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        $items = array_map(fn($id) => ['claimLineItemId' => $id], $item_ids);
        return $this->mp_request('trendyol', "suppliers/{$seller_id}/claims/{$claim_id}/items/approve", 'PUT', ['claimLineItems' => $items]);
    }

    /**
     * Reject return on Trendyol (create issue)
     */
    public function reject_trendyol_return(string $claim_id, string $reason, array $item_ids): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        return $this->mp_request('trendyol', "suppliers/{$seller_id}/claims/{$claim_id}/issue", 'POST', [
            'claimIssueReasonId' => 1, 'description' => $reason,
            'claimLineItemIdList' => $item_ids,
        ]);
    }

    public function ajax_fetch_returns(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $mp = sanitize_text_field($_POST['marketplace'] ?? 'all');
        $results = [];
        if ($mp === 'all' || $mp === 'trendyol') { $r = $this->fetch_trendyol_returns(); if (!isset($r['error'])) $results = array_merge($results, $r['returns'] ?? []); }
        if ($mp === 'all' || $mp === 'hepsiburada') { $r = $this->fetch_hepsiburada_returns(); if (!isset($r['error'])) $results = array_merge($results, $r['returns'] ?? []); }
        wp_send_json_success(['returns' => $results, 'total' => count($results)]);
    }

    public function ajax_approve_return(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $claim_id = sanitize_text_field($_POST['claim_id'] ?? '');
        $item_ids = array_map('sanitize_text_field', $_POST['item_ids'] ?? []);
        if ($mp === 'trendyol') { wp_send_json_success($this->approve_trendyol_return($claim_id, $item_ids)); }
        wp_send_json_error('Not supported for ' . $mp);
    }

    public function ajax_reject_return(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $claim_id = sanitize_text_field($_POST['claim_id'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $item_ids = array_map('sanitize_text_field', $_POST['item_ids'] ?? []);
        if ($mp === 'trendyol') { wp_send_json_success($this->reject_trendyol_return($claim_id, $reason, $item_ids)); }
        wp_send_json_error('Not supported for ' . $mp);
    }

    private function mp_request(string $mp, string $ep, string $m = 'GET', $d = null): array {
        $p = Isarud_Plugin::instance(); $r = new \ReflectionMethod($p, 'marketplace_request'); $r->setAccessible(true); return $r->invoke($p, $mp, $ep, $m, $d);
    }
    private function get_cred(string $mp, string $k): string {
        $p = Isarud_Plugin::instance(); $r = new \ReflectionMethod($p, 'get_cred'); $r->setAccessible(true); return $r->invoke($p, $mp, $k);
    }
}
