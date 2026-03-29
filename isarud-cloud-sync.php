<?php
/**
 * Isarud Cloud Sync — isarud.com API v2 entegrasyonu
 * WP Plugin v6.2.0
 */
if (!defined('ABSPATH')) exit;

class Isarud_Cloud_Sync {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('isarud_cloud_sync_hook', [$this, 'run_cloud_sync']);
        add_action('woocommerce_new_order', [$this, 'sync_order_to_cloud'], 40, 1);
        if (get_option('isarud_cloud_enabled') === 'yes') {
            if (!wp_next_scheduled('isarud_cloud_sync_hook')) {
                wp_schedule_event(time(), 'hourly', 'isarud_cloud_sync_hook');
            }
        }
    }

    private function get_cloud_key(): string { return get_option('isarud_cloud_api_key', ''); }
    public function is_enabled(): bool { return get_option('isarud_cloud_enabled') === 'yes' && !empty($this->get_cloud_key()); }

    private function api_request(string $endpoint, array $data = [], string $method = 'POST'): array {
        $key = $this->get_cloud_key();
        if (empty($key)) return ['error' => 'Cloud API key not configured'];
        $args = ['method' => $method, 'timeout' => 30, 'headers' => ['Content-Type' => 'application/json', 'X-Marketplace-Key' => $key, 'User-Agent' => 'IsarudWP/' . ISARUD_VERSION]];
        if (!empty($data) && in_array($method, ['POST', 'PUT'])) $args['body'] = wp_json_encode($data);
        $response = wp_remote_request($this->api_base . $endpoint, $args);
        if (is_wp_error($response)) return ['error' => $response->get_error_message()];
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true) ?: [];
        if ($code >= 400) return ['error' => $body['error'] ?? "HTTP {$code}"];
        return $body;
    }

    public function connect_site(string $bearer_token): array {
        $response = wp_remote_post('https://isarud.com/api/v2/marketplace/connect', ['timeout' => 30, 'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $bearer_token], 'body' => wp_json_encode(['platform' => 'wordpress', 'site_url' => home_url(), 'site_name' => get_bloginfo('name')])]);
        if (is_wp_error($response)) return ['error' => $response->get_error_message()];
        $body = json_decode(wp_remote_retrieve_body($response), true) ?: [];
        if (!empty($body['success']) && !empty($body['platform']['api_key'])) {
            update_option('isarud_cloud_api_key', $body['platform']['api_key']);
            update_option('isarud_api_key', $bearer_token);
            update_option('isarud_cloud_enabled', 'yes');
            update_option('isarud_cloud_platform_id', $body['platform']['id']);
            return ['success' => true, 'message' => 'Connected to isarud.com'];
        }
        if (isset($body['error']) && strpos($body['error'], 'already connected') !== false) return ['success' => true, 'message' => 'Already connected'];
        return ['error' => $body['error'] ?? 'Connection failed'];
    }

    public function sync_products(): array {
        if (!$this->is_enabled() || !class_exists('WooCommerce')) return ['error' => 'Cloud sync not enabled or WooCommerce not active'];
        global $wpdb;
        $marketplaces = $wpdb->get_results("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active=1");
        $products_data = [];
        $wc_products = wc_get_products(['status' => 'publish', 'limit' => 500]);
        foreach ($wc_products as $product) {
            $products_data[] = ['external_id' => (string)$product->get_id(), 'marketplace' => 'woocommerce', 'title' => $product->get_name(), 'sku' => $product->get_sku(), 'price' => (float)$product->get_price(), 'stock' => $product->get_stock_quantity() ?? 0, 'status' => $product->get_status(), 'image_url' => wp_get_attachment_url($product->get_image_id()) ?: null, 'meta' => ['barcode' => get_post_meta($product->get_id(), '_isarud_barcode', true), 'permalink' => $product->get_permalink()]];
            foreach ($marketplaces as $mp) {
                $barcode = get_post_meta($product->get_id(), '_isarud_barcode', true) ?: $product->get_sku();
                if (empty($barcode)) continue;
                $products_data[] = ['external_id' => $barcode, 'marketplace' => $mp->marketplace, 'title' => $product->get_name(), 'sku' => $product->get_sku(), 'price' => $this->apply_margin((float)$product->get_price(), $mp->marketplace), 'stock' => $product->get_stock_quantity() ?? 0, 'status' => $product->get_status(), 'image_url' => wp_get_attachment_url($product->get_image_id()) ?: null];
            }
        }
        if (empty($products_data)) return ['success' => true, 'synced' => 0, 'message' => 'No products to sync'];
        $total_synced = 0; $total_failed = 0;
        foreach (array_chunk($products_data, 100) as $chunk) {
            $result = $this->api_request('products/sync', ['products' => $chunk]);
            if (isset($result['error'])) $total_failed += count($chunk); else { $total_synced += $result['synced'] ?? 0; $total_failed += $result['failed'] ?? 0; }
        }
        update_option('isarud_cloud_last_product_sync', current_time('mysql'));
        return ['success' => true, 'synced' => $total_synced, 'failed' => $total_failed];
    }

    public function sync_orders(): array {
        if (!$this->is_enabled() || !class_exists('WooCommerce')) return ['error' => 'Cloud sync not enabled'];
        $last_sync = get_option('isarud_cloud_last_order_sync', '2000-01-01 00:00:00');
        $orders = wc_get_orders(['date_created' => '>' . strtotime($last_sync), 'limit' => 500, 'orderby' => 'date', 'order' => 'ASC']);
        if (empty($orders)) return ['success' => true, 'synced' => 0, 'message' => 'No new orders'];
        $orders_data = [];
        foreach ($orders as $order) $orders_data[] = $this->format_order($order);
        $total_synced = 0; $total_failed = 0;
        foreach (array_chunk($orders_data, 100) as $chunk) {
            $result = $this->api_request('orders/sync', ['orders' => $chunk]);
            if (isset($result['error'])) $total_failed += count($chunk); else { $total_synced += $result['synced'] ?? 0; $total_failed += $result['failed'] ?? 0; }
        }
        update_option('isarud_cloud_last_order_sync', current_time('mysql'));
        return ['success' => true, 'synced' => $total_synced, 'failed' => $total_failed];
    }

    public function sync_order_to_cloud(int $order_id): void {
        if (!$this->is_enabled()) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
        $this->api_request('orders/sync', ['orders' => [$this->format_order($order)]]);
    }

    private function format_order($order): array {
        return ['external_id' => (string)$order->get_id(), 'marketplace' => 'woocommerce', 'customer_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()), 'customer_email' => $order->get_billing_email(), 'total' => (float)$order->get_total(), 'currency' => $order->get_currency(), 'status' => $order->get_status(), 'order_date' => $order->get_date_created()?->format('Y-m-d H:i:s'), 'meta' => ['billing_country' => $order->get_billing_country(), 'shipping_country' => $order->get_shipping_country(), 'payment_method' => $order->get_payment_method(), 'items_count' => $order->get_item_count()]];
    }

    public function sync_extended_data(): array {
        if (!$this->is_enabled()) return ['error' => 'Cloud sync not enabled'];
        $results = [];
        $results['settings'] = $this->sync_settings();
        $results['customers'] = $this->sync_customers();
        $results['abandoned_carts'] = $this->sync_abandoned_carts();
        $results['einvoices'] = $this->sync_einvoices();
        $results['credentials'] = $this->sync_credentials();
        $results['screening_logs'] = $this->sync_screening_logs();
        $results['sync_logs'] = $this->sync_sync_logs();
        $results['suppliers'] = $this->sync_suppliers();
        $results['affiliates'] = $this->sync_affiliates();
        update_option('isarud_cloud_last_extended_sync', current_time('mysql'));
        return $results;
    }

    private function sync_settings(): array {
        $option_keys = ['isarud_b2b_settings', 'isarud_currency_settings', 'isarud_tcmb_rates', 'isarud_segment_settings', 'isarud_einvoice_settings', 'isarud_popup_settings', 'isarud_popup_campaigns', 'isarud_cart_recovery_settings', 'isarud_email_marketing_settings', 'isarud_email_log', 'isarud_upsell_settings', 'isarud_upsell_rules', 'isarud_api_key', 'isarud_auto_screen', 'isarud_block_match', 'isarud_alert_email', 'isarud_webhook_secret', 'isarud_auto_import_orders', 'isarud_category_mappings'];
        $settings = [];
        foreach ($option_keys as $key) {
            $value = get_option($key, null);
            if ($value !== null && $value !== false) {
                $settings[] = ['key' => $key, 'value' => is_array($value) ? $value : (is_string($value) ? (json_decode($value, true) ?? $value) : $value)];
            }
        }
        if (empty($settings)) return ['success' => true, 'synced' => 0, 'message' => 'No settings to sync'];
        return $this->api_request('settings/sync', ['settings' => $settings]);
    }

    private function sync_customers(): array {
        if (!class_exists('WooCommerce')) return ['success' => true, 'synced' => 0];
        $segment_settings = get_option('isarud_segment_settings', []);
        $b2b_settings = get_option('isarud_b2b_settings', []);
        $wc_customers = (new \WC_Customer_Query(['limit' => 500, 'orderby' => 'date_created', 'order' => 'DESC']))->get_customers();
        if (empty($wc_customers)) return ['success' => true, 'synced' => 0];
        $customers_data = [];
        foreach ($wc_customers as $customer) {
            $cid = $customer->get_id(); $oc = (int)$customer->get_order_count(); $ts = (float)$customer->get_total_spent();
            $b2b_discount = (!empty($b2b_settings['enabled']) && $b2b_settings['enabled'] === 'yes') ? (float)get_user_meta($cid, '_isarud_b2b_discount', true) : 0;
            $customers_data[] = ['external_id' => $cid, 'email' => $customer->get_email(), 'name' => trim($customer->get_first_name() . ' ' . $customer->get_last_name()), 'company' => $customer->get_billing_company() ?: null, 'segment' => $this->determine_segment($cid, $oc, $ts, $segment_settings), 'discount_rate' => $b2b_discount, 'total_orders' => $oc, 'total_spent' => $ts, 'meta' => ['billing_country' => $customer->get_billing_country(), 'billing_city' => $customer->get_billing_city(), 'phone' => $customer->get_billing_phone(), 'date_created' => $customer->get_date_created() ? $customer->get_date_created()->format('Y-m-d H:i:s') : null]];
        }
        $ts2 = 0; $tf2 = 0;
        foreach (array_chunk($customers_data, 100) as $chunk) { $r = $this->api_request('customers/sync', ['customers' => $chunk]); if (isset($r['error'])) $tf2 += count($chunk); else { $ts2 += $r['synced'] ?? 0; $tf2 += $r['failed'] ?? 0; } }
        return ['success' => true, 'synced' => $ts2, 'failed' => $tf2];
    }

    private function determine_segment(int $cid, int $oc, float $ts, array $s): string {
        $vip = (float)($s['vip_min_spent'] ?? 5000); $loyal = (int)($s['loyal_min_orders'] ?? 5); $risk = (int)($s['risk_inactive_days'] ?? 180);
        if ($ts >= $vip) return 'vip'; if ($oc >= $loyal) return 'loyal'; if ($oc === 1) return 'one_time'; if ($oc === 0) return 'new';
        $ld = get_user_meta($cid, '_last_order_date', true);
        if ($ld) { $ds = (int)((time() - strtotime($ld)) / 86400); if ($ds > $risk) return 'churned'; if ($ds > ($risk / 2)) return 'at_risk'; }
        return 'regular';
    }

    private function sync_abandoned_carts(): array {
        global $wpdb; $t = $wpdb->prefix . 'isarud_abandoned_carts';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$t}'") !== $t) return ['success' => true, 'synced' => 0, 'message' => 'Table not found'];
        $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY updated_at DESC LIMIT 500");
        if (empty($rows)) return ['success' => true, 'synced' => 0];
        $data = [];
        foreach ($rows as $r) { $ci = maybe_unserialize($r->cart_contents ?? ''); if (is_string($ci)) $ci = json_decode($ci, true); $data[] = ['external_id' => (int)$r->id, 'session_id' => $r->session_id ?? null, 'email' => $r->email ?? null, 'customer_name' => $r->customer_name ?? null, 'cart_total' => (float)($r->cart_total ?? 0), 'cart_items' => is_array($ci) ? $ci : null, 'status' => $r->status ?? 'abandoned', 'emails_sent' => (int)($r->emails_sent ?? 0), 'last_email_at' => $r->last_email_at ?? null, 'recovered_at' => $r->recovered_at ?? null, 'cart_date' => $r->created_at ?? $r->updated_at ?? null]; }
        $ts = 0; $tf = 0;
        foreach (array_chunk($data, 100) as $chunk) { $res = $this->api_request('abandoned-carts/sync', ['carts' => $chunk]); if (isset($res['error'])) $tf += count($chunk); else { $ts += $res['synced'] ?? 0; $tf += $res['failed'] ?? 0; } }
        return ['success' => true, 'synced' => $ts, 'failed' => $tf];
    }

    private function sync_einvoices(): array {
        global $wpdb; $t = $wpdb->prefix . 'isarud_einvoices';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$t}'") !== $t) return ['success' => true, 'synced' => 0, 'message' => 'Table not found'];
        $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY created_at DESC LIMIT 500");
        if (empty($rows)) return ['success' => true, 'synced' => 0];
        $data = [];
        foreach ($rows as $r) { $gr = $r->gib_response ?? null; if (is_string($gr)) $gr = json_decode($gr, true); $data[] = ['external_id' => (int)$r->id, 'order_id' => (int)($r->order_id ?? 0), 'invoice_number' => $r->invoice_number ?? null, 'invoice_type' => $r->invoice_type ?? 'einvoice', 'customer_name' => $r->customer_name ?? null, 'customer_tax_id' => $r->tax_id ?? $r->customer_tax_id ?? null, 'total' => (float)($r->total ?? 0), 'currency' => $r->currency ?? 'TRY', 'status' => $r->status ?? 'draft', 'gib_response' => is_array($gr) ? $gr : null, 'invoice_date' => $r->invoice_date ?? $r->created_at ?? null]; }
        $ts = 0; $tf = 0;
        foreach (array_chunk($data, 100) as $chunk) { $res = $this->api_request('einvoices/sync', ['einvoices' => $chunk]); if (isset($res['error'])) $tf += count($chunk); else { $ts += $res['synced'] ?? 0; $tf += $res['failed'] ?? 0; } }
        return ['success' => true, 'synced' => $ts, 'failed' => $tf];
    }

    private function sync_credentials(): array {
        global $wpdb; $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}isarud_credentials ORDER BY id ASC");
        if (empty($rows)) return ['success' => true, 'synced' => 0];
        $data = [];
        foreach ($rows as $r) $data[] = ['marketplace' => $r->marketplace, 'is_active' => (bool)$r->is_active, 'price_margin' => (float)$r->price_margin, 'price_margin_type' => $r->price_margin_type ?? 'percent', 'auto_sync' => (bool)$r->auto_sync, 'sync_interval' => $r->sync_interval ?? 'daily', 'last_test' => $r->last_test, 'test_status' => $r->test_status, 'last_sync' => $r->last_sync];
        return $this->api_request('credentials/sync', ['credentials' => $data]);
    }

    private function sync_screening_logs(): array {
        global $wpdb; $t = $wpdb->prefix . 'isarud_screening_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$t}'") !== $t) return ['success' => true, 'synced' => 0];
        $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY created_at DESC LIMIT 500");
        if (empty($rows)) return ['success' => true, 'synced' => 0];
        $data = [];
        foreach ($rows as $r) { $ns = is_string($r->names_screened) ? json_decode($r->names_screened, true) : $r->names_screened; $rs = is_string($r->results) ? json_decode($r->results, true) : $r->results; $data[] = ['external_id' => (int)$r->id, 'order_id' => (int)($r->order_id ?? 0), 'names_screened' => is_array($ns) ? $ns : null, 'has_match' => (bool)($r->has_match ?? false), 'results' => is_array($rs) ? $rs : null, 'created_at' => $r->created_at]; }
        $ts = 0; $tf = 0;
        foreach (array_chunk($data, 100) as $chunk) { $res = $this->api_request('screening-logs/sync', ['logs' => $chunk]); if (isset($res['error'])) $tf += count($chunk); else { $ts += $res['synced'] ?? 0; $tf += $res['failed'] ?? 0; } }
        return ['success' => true, 'synced' => $ts, 'failed' => $tf];
    }

    private function sync_sync_logs(): array {
        global $wpdb; $t = $wpdb->prefix . 'isarud_sync_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$t}'") !== $t) return ['success' => true, 'synced' => 0];
        $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY created_at DESC LIMIT 500");
        if (empty($rows)) return ['success' => true, 'synced' => 0];
        $data = [];
        foreach ($rows as $r) $data[] = ['external_id' => (int)$r->id, 'product_id' => (int)($r->product_id ?? 0), 'marketplace' => $r->marketplace ?? null, 'action' => $r->action ?? null, 'status' => $r->status ?? null, 'message' => $r->message ?? null, 'created_at' => $r->created_at];
        $ts = 0; $tf = 0;
        foreach (array_chunk($data, 100) as $chunk) { $res = $this->api_request('sync-logs/sync', ['logs' => $chunk]); if (isset($res['error'])) $tf += count($chunk); else { $ts += $res['synced'] ?? 0; $tf += $res['failed'] ?? 0; } }
        return ['success' => true, 'synced' => $ts, 'failed' => $tf];
    }

    private function sync_suppliers(): array {
        global $wpdb; $t = $wpdb->prefix . 'isarud_dropship_suppliers';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$t}'") !== $t) return ['success' => true, 'synced' => 0];
        $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY id ASC");
        if (empty($rows)) return ['success' => true, 'synced' => 0];
        $data = [];
        foreach ($rows as $r) $data[] = ['external_id' => (int)$r->id, 'name' => $r->name, 'email' => $r->email ?? null, 'api_url' => $r->api_url ?? null, 'auto_forward' => (bool)($r->auto_forward ?? false), 'commission_rate' => (float)($r->commission_rate ?? 0), 'is_active' => (bool)($r->is_active ?? true)];
        return $this->api_request('suppliers/sync', ['suppliers' => $data]);
    }

    private function sync_affiliates(): array {
        global $wpdb; $t = $wpdb->prefix . 'isarud_affiliates';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$t}'") !== $t) return ['success' => true, 'synced' => 0];
        $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY id ASC");
        if (empty($rows)) return ['success' => true, 'synced' => 0];
        $data = [];
        foreach ($rows as $r) $data[] = ['external_id' => (int)$r->id, 'name' => $r->name, 'email' => $r->email ?? null, 'code' => $r->code ?? null, 'commission_rate' => (float)($r->commission_rate ?? 10), 'total_sales' => (float)($r->total_sales ?? 0), 'total_commission' => (float)($r->total_commission ?? 0), 'is_active' => (bool)($r->is_active ?? true)];
        return $this->api_request('affiliates/sync', ['affiliates' => $data]);
    }

    /* ─────────────────────────────────────────────
     * REVERSE SYNC — Pull changes from isarud.com
     * iOS/Web'den yapılan değişiklikleri WP'ye çek
     * ───────────────────────────────────────────── */

    public function pull_reverse_changes(): array {
        if (!$this->is_enabled()) return ['error' => 'Cloud sync not enabled'];

        $last_reverse_sync = get_option('isarud_last_reverse_sync', '2026-01-01T00:00:00Z');

        $result = $this->api_request('reverse-sync/changes?since=' . urlencode($last_reverse_sync), [], 'GET');

        if (isset($result['error'])) {
            return ['error' => 'Reverse sync failed: ' . $result['error']];
        }

        if (($result['total_changes'] ?? 0) === 0) {
            update_option('isarud_last_reverse_sync', gmdate('c'));
            return ['success' => true, 'applied' => 0, 'message' => 'No changes'];
        }

        $changes = $result['changes'] ?? [];
        $acked = [];
        $applied = 0;
        $failed = 0;

        // Settings
        if (!empty($changes['settings'])) {
            foreach ($changes['settings'] as $setting) {
                $key = $setting['setting_key'] ?? '';
                $value = $setting['setting_value'] ?? null;
                if (!empty($key)) {
                    update_option($key, $value);
                    $acked['settings'][] = $setting['id'];
                    $applied++;
                }
            }
        }

        // Customers (segment, discount_rate, company)
        if (!empty($changes['customers']) && class_exists('WC_Customer')) {
            foreach ($changes['customers'] as $customer) {
                $ext_id = $customer['external_customer_id'] ?? null;
                if ($ext_id) {
                    try {
                        $wc_customer = new \WC_Customer($ext_id);
                        if ($wc_customer->get_id()) {
                            if (isset($customer['company'])) {
                                $wc_customer->set_billing_company($customer['company']);
                            }
                            if (isset($customer['segment'])) {
                                update_user_meta($ext_id, 'isarud_segment', $customer['segment']);
                            }
                            if (isset($customer['discount_rate'])) {
                                update_user_meta($ext_id, '_isarud_b2b_discount', $customer['discount_rate']);
                            }
                            $wc_customer->save();
                            $acked['customers'][] = $customer['id'];
                            $applied++;
                        } else {
                            $failed++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                    }
                }
            }
        }

        // Abandoned Carts (status update)
        if (!empty($changes['abandoned_carts'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'isarud_abandoned_carts';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                foreach ($changes['abandoned_carts'] as $cart) {
                    $ext_id = $cart['external_cart_id'] ?? null;
                    if ($ext_id !== null) {
                        $update_data = ['status' => $cart['status'] ?? 'abandoned'];
                        if (!empty($cart['recovered_at'])) {
                            $update_data['recovered_at'] = $cart['recovered_at'];
                        }
                        $updated = $wpdb->update($table, $update_data, ['id' => $ext_id]);
                        if ($updated !== false) {
                            $acked['abandoned_carts'][] = $cart['id'];
                            $applied++;
                        } else {
                            $failed++;
                        }
                    }
                }
            }
        }

        // E-Invoices (status, customer_name, invoice_number)
        if (!empty($changes['einvoices'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'isarud_einvoices';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                foreach ($changes['einvoices'] as $einvoice) {
                    $ext_id = $einvoice['external_invoice_id'] ?? null;
                    if ($ext_id !== null) {
                        $update_data = [];
                        if (isset($einvoice['status'])) $update_data['status'] = $einvoice['status'];
                        if (isset($einvoice['customer_name'])) $update_data['customer_name'] = $einvoice['customer_name'];
                        if (isset($einvoice['invoice_number'])) $update_data['invoice_number'] = $einvoice['invoice_number'];
                        if (!empty($update_data)) {
                            $updated = $wpdb->update($table, $update_data, ['id' => $ext_id]);
                            if ($updated !== false) {
                                $acked['einvoices'][] = $einvoice['id'];
                                $applied++;
                            } else {
                                $failed++;
                            }
                        }
                    }
                }
            }
        }

        // Credentials (is_active, auto_sync, price_margin, sync_interval)
        if (!empty($changes['credentials'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'isarud_credentials';
            foreach ($changes['credentials'] as $cred) {
                $marketplace = $cred['marketplace'] ?? '';
                if (!empty($marketplace)) {
                    $update_data = [];
                    if (isset($cred['is_active'])) $update_data['is_active'] = $cred['is_active'] ? 1 : 0;
                    if (isset($cred['auto_sync'])) $update_data['auto_sync'] = $cred['auto_sync'] ? 1 : 0;
                    if (isset($cred['price_margin'])) $update_data['price_margin'] = $cred['price_margin'];
                    if (isset($cred['price_margin_type'])) $update_data['price_margin_type'] = $cred['price_margin_type'];
                    if (isset($cred['sync_interval'])) $update_data['sync_interval'] = $cred['sync_interval'];
                    if (!empty($update_data)) {
                        $wpdb->update($table, $update_data, ['marketplace' => $marketplace]);
                        $acked['credentials'][] = $cred['id'];
                        $applied++;
                    }
                }
            }
        }

        // Suppliers
        if (!empty($changes['suppliers'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'isarud_dropship_suppliers';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                foreach ($changes['suppliers'] as $supplier) {
                    $ext_id = $supplier['external_supplier_id'] ?? null;
                    if ($ext_id !== null) {
                        $update_data = [];
                        if (isset($supplier['name'])) $update_data['name'] = $supplier['name'];
                        if (isset($supplier['email'])) $update_data['email'] = $supplier['email'];
                        if (isset($supplier['auto_forward'])) $update_data['auto_forward'] = $supplier['auto_forward'] ? 1 : 0;
                        if (isset($supplier['commission_rate'])) $update_data['commission_rate'] = $supplier['commission_rate'];
                        if (isset($supplier['is_active'])) $update_data['is_active'] = $supplier['is_active'] ? 1 : 0;
                        if (!empty($update_data)) {
                            $wpdb->update($table, $update_data, ['id' => $ext_id]);
                            $acked['suppliers'][] = $supplier['id'];
                            $applied++;
                        }
                    }
                }
            }
        }

        // Affiliates
        if (!empty($changes['affiliates'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'isarud_affiliates';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                foreach ($changes['affiliates'] as $affiliate) {
                    $ext_id = $affiliate['external_affiliate_id'] ?? null;
                    if ($ext_id !== null) {
                        $update_data = [];
                        if (isset($affiliate['name'])) $update_data['name'] = $affiliate['name'];
                        if (isset($affiliate['email'])) $update_data['email'] = $affiliate['email'];
                        if (isset($affiliate['commission_rate'])) $update_data['commission_rate'] = $affiliate['commission_rate'];
                        if (isset($affiliate['is_active'])) $update_data['is_active'] = $affiliate['is_active'] ? 1 : 0;
                        if (!empty($update_data)) {
                            $wpdb->update($table, $update_data, ['id' => $ext_id]);
                            $acked['affiliates'][] = $affiliate['id'];
                            $applied++;
                        }
                    }
                }
            }
        }

        // ACK gönder
        if (!empty($acked)) {
            $this->api_request('reverse-sync/ack', ['acked' => $acked]);
        }

        update_option('isarud_last_reverse_sync', gmdate('c'));

        return ['success' => true, 'applied' => $applied, 'failed' => $failed];
    }

    /* ───────────────────────────────────────────── */

    public function run_cloud_sync(): void {
        if (!$this->is_enabled()) return;
        $this->sync_products();
        $this->sync_orders();
        $this->sync_extended_data();
        $this->pull_reverse_changes();
        update_option('isarud_cloud_last_sync', current_time('mysql'));
    }

    public function run_extended_sync_now(): array {
        if (!$this->is_enabled()) return ['error' => 'Cloud sync not enabled'];
        return $this->sync_extended_data();
    }

    private function apply_margin(float $price, string $mp): float {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT price_margin, price_margin_type FROM {$wpdb->prefix}isarud_credentials WHERE marketplace=%s", $mp));
        if ($row && floatval($row->price_margin) != 0) return $row->price_margin_type === 'percent' ? round($price * (1 + floatval($row->price_margin) / 100), 2) : round($price + floatval($row->price_margin), 2);
        return $price;
    }

    public function get_status(): array {
        return ['enabled' => $this->is_enabled(), 'cloud_key' => !empty($this->get_cloud_key()) ? '••••' . substr($this->get_cloud_key(), -8) : '', 'last_sync' => get_option('isarud_cloud_last_sync', ''), 'last_product_sync' => get_option('isarud_cloud_last_product_sync', ''), 'last_order_sync' => get_option('isarud_cloud_last_order_sync', ''), 'last_extended_sync' => get_option('isarud_cloud_last_extended_sync', ''), 'last_reverse_sync' => get_option('isarud_last_reverse_sync', '')];
    }

    public function disconnect(): void {
        delete_option('isarud_cloud_api_key'); delete_option('isarud_cloud_enabled'); delete_option('isarud_cloud_platform_id');
        delete_option('isarud_cloud_last_sync'); delete_option('isarud_cloud_last_product_sync'); delete_option('isarud_cloud_last_order_sync'); delete_option('isarud_cloud_last_extended_sync');
        delete_option('isarud_last_reverse_sync');
        wp_clear_scheduled_hook('isarud_cloud_sync_hook');
    }
}
