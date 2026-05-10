<?php
/**
 * Isarud Order Import
 * Trendyol, Hepsiburada, N11 siparişlerini WooCommerce'e aktarır
 */
if (!defined('ABSPATH')) exit;

class Isarud_Order_Import {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_import_orders', [$this, 'ajax_import_orders']);
        add_action('isarud_trendyol_order_received', [$this, 'import_trendyol_order']);
        add_action('isarud_hepsiburada_order_received', [$this, 'import_hepsiburada_order']);

        // Cron for periodic order fetch
        add_action('isarud_order_import_cron', [$this, 'cron_import_orders']);
        if (get_option('isarud_auto_import_orders') === 'yes') {
            if (!wp_next_scheduled('isarud_order_import_cron')) {
                wp_schedule_event(time(), 'hourly', 'isarud_order_import_cron');
            }
        }
    }

    /**
     * Import orders from Trendyol API
     */
    public function import_trendyol_orders(int $days = 7): array {
        $plugin = Isarud_Plugin::instance();
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        if (empty($seller_id)) return ['error' => 'Trendyol credentials not configured'];

        $start = date('Y-m-d', strtotime("-{$days} days"));
        $end = date('Y-m-d');

        $result = $this->marketplace_request('trendyol',
            "suppliers/{$seller_id}/orders?startDate={$start}&endDate={$end}&size=200&orderByField=CreatedDate&orderByDirection=DESC"
        );

        if (isset($result['error'])) return $result;

        $orders = $result['content'] ?? [];
        $imported = 0;
        $skipped = 0;

        foreach ($orders as $order_data) {
            $order_number = $order_data['orderNumber'] ?? '';
            if (empty($order_number)) continue;

            // Check if already imported
            if ($this->order_exists('trendyol', $order_number)) {
                $skipped++;
                continue;
            }

            $wc_order = $this->create_wc_order([
                'marketplace' => 'trendyol',
                'external_id' => $order_number,
                'customer_name' => trim(($order_data['shipmentAddress']['firstName'] ?? '') . ' ' . ($order_data['shipmentAddress']['lastName'] ?? '')),
                'customer_email' => $order_data['customerEmail'] ?? '',
                'address' => [
                    'first_name' => $order_data['shipmentAddress']['firstName'] ?? '',
                    'last_name' => $order_data['shipmentAddress']['lastName'] ?? '',
                    'address_1' => $order_data['shipmentAddress']['address1'] ?? ($order_data['shipmentAddress']['fullAddress'] ?? ''),
                    'city' => $order_data['shipmentAddress']['city'] ?? '',
                    'state' => $order_data['shipmentAddress']['district'] ?? '',
                    'postcode' => $order_data['shipmentAddress']['postalCode'] ?? '',
                    'country' => $order_data['shipmentAddress']['countryCode'] ?? 'TR',
                    'phone' => $order_data['shipmentAddress']['phone'] ?? ($order_data['shipmentAddress']['fullName'] ?? ''),
                ],
                'items' => array_map(function($line) {
                    return [
                        'sku' => $line['barcode'] ?? $line['merchantSku'] ?? '',
                        'name' => $line['productName'] ?? 'Trendyol Product',
                        'quantity' => $line['quantity'] ?? 1,
                        'price' => $line['price'] ?? $line['amount'] ?? 0,
                    ];
                }, $order_data['lines'] ?? []),
                'total' => $order_data['totalPrice'] ?? 0,
                'currency' => 'TRY',
                'status' => $this->map_trendyol_status($order_data['status'] ?? ''),
                'date' => isset($order_data['orderDate']) ? date('Y-m-d H:i:s', $order_data['orderDate'] / 1000) : current_time('mysql'),
            ]);

            if ($wc_order) $imported++;
        }

        return ['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'total' => count($orders)];
    }

    /**
     * Import orders from Hepsiburada API
     */
    public function import_amazon_orders(int $days = 7): array {
        if (!class_exists('Isarud_Amazon')) {
            return ['error' => 'Amazon modülü yüklü değil'];
        }
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) return ['error' => 'Cloud Sync bağlantısı yok'];

        $result = Isarud_Amazon::instance()->get_orders(null, $days, 0, 50);
        if (isset($result['error'])) return $result;

        $orders = $result['items'] ?? [];
        $imported = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $amzOrderId = $order['AmazonOrderId'] ?? '';
            if (empty($amzOrderId)) continue;

            if ($this->order_exists('amazon', $amzOrderId)) {
                $skipped++;
                continue;
            }

            // Amazon order details ayrı API call gerek - şimdilik basic info
            $wc_order = $this->create_wc_order([
                'marketplace' => 'amazon',
                'external_id' => $amzOrderId,
                'customer_name' => $order['BuyerInfo']['BuyerName'] ?? 'Amazon Buyer',
                'customer_email' => $order['BuyerInfo']['BuyerEmail'] ?? '',
                'address' => [
                    'first_name' => $order['ShippingAddress']['Name'] ?? '',
                    'last_name' => '',
                    'address_1' => $order['ShippingAddress']['AddressLine1'] ?? '',
                    'city' => $order['ShippingAddress']['City'] ?? '',
                    'state' => $order['ShippingAddress']['StateOrRegion'] ?? '',
                    'postcode' => $order['ShippingAddress']['PostalCode'] ?? '',
                    'country' => $order['ShippingAddress']['CountryCode'] ?? 'US',
                ],
                'items' => [],  // OrderItems ayrı API call
                'total' => floatval($order['OrderTotal']['Amount'] ?? 0),
                'currency' => $order['OrderTotal']['CurrencyCode'] ?? 'USD',
                'status' => 'processing',
                'date' => $order['PurchaseDate'] ?? current_time('mysql'),
            ]);
            if ($wc_order) $imported++;
        }

        return ['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'total' => count($orders)];
    }

    public function import_hepsiburada_orders(int $days = 7): array {
        $merchant_id = $this->get_cred('hepsiburada', 'merchant_id');
        if (empty($merchant_id)) return ['error' => 'Hepsiburada credentials not configured'];

        // Bridge to Isarud_Hepsiburada (v6.6.6+ - server-side credentials)
        if (!class_exists('Isarud_Hepsiburada')) {
            return ['error' => 'Hepsiburada modülü yüklü değil'];
        }
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) return ['error' => 'Cloud Sync bağlantısı yok'];

        $result = Isarud_Hepsiburada::instance()->get_orders(null, $days, 0, 50);

        if (isset($result['error'])) return $result;

        $orders = $result['items'] ?? $result['packages'] ?? $result['orders'] ?? [];
        $imported = 0;
        $skipped = 0;

        foreach ($orders as $pkg) {
            $order_id = $pkg['packageNumber'] ?? $pkg['orderId'] ?? '';
            if (empty($order_id)) continue;

            if ($this->order_exists('hepsiburada', $order_id)) {
                $skipped++;
                continue;
            }

            $wc_order = $this->create_wc_order([
                'marketplace' => 'hepsiburada',
                'external_id' => $order_id,
                'customer_name' => $pkg['customerName'] ?? ($pkg['recipientName'] ?? ''),
                'customer_email' => $pkg['customerEmail'] ?? '',
                'address' => [
                    'first_name' => $pkg['recipientName'] ?? '',
                    'last_name' => '',
                    'address_1' => $pkg['shippingAddress'] ?? ($pkg['address'] ?? ''),
                    'city' => $pkg['city'] ?? '',
                    'state' => $pkg['district'] ?? '',
                    'postcode' => $pkg['postalCode'] ?? '',
                    'country' => 'TR',
                    'phone' => $pkg['phone'] ?? '',
                ],
                'items' => array_map(function($item) {
                    return [
                        'sku' => $item['merchantSku'] ?? $item['hepsiburadaSku'] ?? '',
                        'name' => $item['productName'] ?? 'HB Product',
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? $item['unitPrice'] ?? 0,
                    ];
                }, $pkg['items'] ?? $pkg['orderItems'] ?? []),
                'total' => $pkg['totalPrice'] ?? 0,
                'currency' => 'TRY',
                'status' => 'processing',
                'date' => $pkg['orderDate'] ?? current_time('mysql'),
            ]);

            if ($wc_order) $imported++;
        }

        return ['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'total' => count($orders)];
    }

    /**
     * Import orders from N11 API (SOAP)
     */
    public function import_n11_orders(int $days = 7): array {
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) return ['error' => 'Cloud Sync bağlantısı yok'];

        if (!class_exists('Isarud_N11')) {
            return ['error' => 'N11 modülü yüklü değil'];
        }

        // Modern REST: Isarud_N11 bridge → isarud.com → N11
        $result = Isarud_N11::instance()->get_orders(0, 50);

        if (!($result['success'] ?? false)) {
            return ['error' => $result['message'] ?? 'N11 sipariş çekme başarısız'];
        }

        $orders = $result['orders'] ?? $result['items'] ?? [];
        $imported = 0;
        $skipped = 0;

        foreach ($orders as $order_data) {
            $order_id = $order_data['orderNumber'] ?? $order_data['id'] ?? '';
            if (empty($order_id)) continue;

            if ($this->order_exists('n11', $order_id)) {
                $skipped++;
                continue;
            }

            // Items mapping (N11 modern REST format)
            $items = [];
            $itemsList = $order_data['items'] ?? $order_data['orderItems'] ?? [];
            foreach ($itemsList as $line) {
                $items[] = [
                    'sku' => $line['sellerCode'] ?? $line['productSellerCode'] ?? $line['merchantSku'] ?? '',
                    'name' => $line['productName'] ?? $line['title'] ?? 'N11 Product',
                    'quantity' => (int) ($line['quantity'] ?? 1),
                    'price' => (float) ($line['unitPrice'] ?? $line['price'] ?? 0),
                ];
            }

            // Shipping address
            $shipping = $order_data['shippingAddress'] ?? [];

            $wc_order = $this->create_wc_order([
                'marketplace' => 'n11',
                'external_id' => $order_id,
                'customer_name' => $order_data['customerfullName'] ?? ($shipping['fullName'] ?? ''),
                'customer_email' => $order_data['buyer']['email'] ?? '',
                'address' => [
                    'first_name' => $shipping['fullName'] ?? '',
                    'last_name' => '',
                    'address_1' => $shipping['address'] ?? '',
                    'city' => $shipping['city']['name'] ?? $shipping['city'] ?? '',
                    'state' => $shipping['district']['name'] ?? $shipping['district'] ?? '',
                    'postcode' => $shipping['postalCode'] ?? '',
                    'country' => 'TR',
                    'phone' => $shipping['gsm'] ?? $shipping['phone'] ?? '',
                ],
                'items' => $items,
                'total' => (float) ($order_data['totalDiscountedPrice'] ?? $order_data['totalAmount'] ?? 0),
                'currency' => 'TRY',
                'status' => 'processing',
                'date' => $order_data['createDate'] ?? current_time('mysql'),
            ]);

            if ($wc_order) $imported++;
        }

        return ['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'total' => count($orders)];
    }

    /**
     * Import single Trendyol order from webhook
     */
    public function import_trendyol_order(array $item): void {
        $order_number = $item['orderNumber'] ?? '';
        if (empty($order_number) || $this->order_exists('trendyol', $order_number)) return;

        // Minimal order creation from webhook data
        $this->create_wc_order([
            'marketplace' => 'trendyol',
            'external_id' => $order_number,
            'customer_name' => $item['customerName'] ?? 'Trendyol Customer',
            'customer_email' => '',
            'address' => ['first_name' => $item['customerName'] ?? '', 'last_name' => '', 'address_1' => '', 'city' => '', 'state' => '', 'postcode' => '', 'country' => 'TR', 'phone' => ''],
            'items' => [],
            'total' => $item['totalPrice'] ?? 0,
            'currency' => 'TRY',
            'status' => 'processing',
            'date' => current_time('mysql'),
        ]);
    }

    /**
     * Import single HB order from webhook
     */
    public function import_hepsiburada_order(array $item): void {
        $order_id = $item['packageNumber'] ?? $item['orderId'] ?? '';
        if (empty($order_id) || $this->order_exists('hepsiburada', $order_id)) return;

        $this->create_wc_order([
            'marketplace' => 'hepsiburada',
            'external_id' => $order_id,
            'customer_name' => $item['customerName'] ?? 'HB Customer',
            'customer_email' => '',
            'address' => ['first_name' => $item['customerName'] ?? '', 'last_name' => '', 'address_1' => '', 'city' => '', 'state' => '', 'postcode' => '', 'country' => 'TR', 'phone' => ''],
            'items' => [],
            'total' => $item['totalPrice'] ?? 0,
            'currency' => 'TRY',
            'status' => 'processing',
            'date' => current_time('mysql'),
        ]);
    }

    /**
     * Create WooCommerce order from marketplace data
     */
    private function create_wc_order(array $data): ?\WC_Order {
        if (!class_exists('WooCommerce')) return null;

        try {
            $order = wc_create_order(['status' => $data['status'] ?? 'processing']);

            // Set address
            $address = $data['address'] ?? [];
            $order->set_billing_first_name($address['first_name'] ?? '');
            $order->set_billing_last_name($address['last_name'] ?? '');
            $order->set_billing_address_1($address['address_1'] ?? '');
            $order->set_billing_city($address['city'] ?? '');
            $order->set_billing_state($address['state'] ?? '');
            $order->set_billing_postcode($address['postcode'] ?? '');
            $order->set_billing_country($address['country'] ?? 'TR');
            $order->set_billing_phone($address['phone'] ?? '');
            if (!empty($data['customer_email'])) {
                $order->set_billing_email($data['customer_email']);
            }

            // Copy to shipping
            $order->set_shipping_first_name($address['first_name'] ?? '');
            $order->set_shipping_last_name($address['last_name'] ?? '');
            $order->set_shipping_address_1($address['address_1'] ?? '');
            $order->set_shipping_city($address['city'] ?? '');
            $order->set_shipping_state($address['state'] ?? '');
            $order->set_shipping_postcode($address['postcode'] ?? '');
            $order->set_shipping_country($address['country'] ?? 'TR');

            // Add line items
            foreach ($data['items'] ?? [] as $item) {
                $product = null;
                if (!empty($item['sku'])) {
                    $pid = wc_get_product_id_by_sku($item['sku']);
                    if ($pid) $product = wc_get_product($pid);
                }

                if ($product) {
                    $order->add_product($product, $item['quantity'] ?? 1);
                } else {
                    // Add as fee/line if product not found
                    $fee = new \WC_Order_Item_Fee();
                    $fee->set_name($item['name'] ?? 'Marketplace Product');
                    $fee->set_amount($item['price'] ?? 0);
                    $fee->set_total(($item['price'] ?? 0) * ($item['quantity'] ?? 1));
                    $order->add_item($fee);
                }
            }

            $order->set_currency($data['currency'] ?? 'TRY');
            $order->calculate_totals();

            // Set meta
            $order->update_meta_data('_isarud_marketplace', $data['marketplace']);
            $order->update_meta_data('_isarud_external_order_id', $data['external_id']);
            $order->update_meta_data('_isarud_imported_at', current_time('mysql'));

            if (!empty($data['date'])) {
                $order->set_date_created($data['date']);
            }

            $order->add_order_note(sprintf(
                'Isarud: %s siparişi aktarıldı (ID: %s)',
                ucfirst($data['marketplace']),
                $data['external_id']
            ));

            $order->save();

            // Log
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'isarud_sync_log', [
                'product_id' => 0,
                'marketplace' => $data['marketplace'],
                'action' => 'order_import',
                'status' => 'success',
                'message' => "Order #{$order->get_id()} ← {$data['marketplace']}:{$data['external_id']}",
                'created_at' => current_time('mysql'),
            ]);

            return $order;
        } catch (\Throwable $e) {
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'isarud_sync_log', [
                'product_id' => 0,
                'marketplace' => $data['marketplace'] ?? 'unknown',
                'action' => 'order_import',
                'status' => 'error',
                'message' => $e->getMessage(),
                'created_at' => current_time('mysql'),
            ]);
            return null;
        }
    }

    /**
     * Check if marketplace order already imported
     */
    private function order_exists(string $marketplace, string $external_id): bool {
        global $wpdb;

        // HPOS compatible query
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_enabled()) {
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key='_isarud_external_order_id' AND meta_value=%s LIMIT 1",
                $external_id
            ));
        } else {
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_isarud_external_order_id' AND meta_value=%s LIMIT 1",
                $external_id
            ));
        }

        return !empty($order_id);
    }

    private function map_trendyol_status(string $status): string {
        return match(strtolower($status)) {
            'created', 'picking' => 'processing',
            'shipped' => 'completed',
            'cancelled' => 'cancelled',
            'delivered' => 'completed',
            default => 'processing',
        };
    }

    /**
     * Cron handler
     */
    public function cron_import_orders(): void {
        global $wpdb;
        $active = $wpdb->get_col("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active=1");

        foreach ($active as $mp) {
            match($mp) {
                'trendyol' => $this->import_trendyol_orders(1),
                'hepsiburada' => $this->import_hepsiburada_orders(1),
                'n11' => $this->import_n11_orders(1),
                default => null,
            };
        }
    }

    /**
     * AJAX handler
     */
    public function ajax_import_orders(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $days = intval($_POST['days'] ?? 7);

        $result = match($mp) {
            'trendyol' => $this->import_trendyol_orders($days),
            'hepsiburada' => $this->import_hepsiburada_orders($days),
            'n11' => $this->import_n11_orders($days),
            'all' => $this->import_all($days),
            default => ['error' => 'Unknown marketplace'],
        };

        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    private function import_all(int $days): array {
        $results = [];
        global $wpdb;
        $active = $wpdb->get_col("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active=1");

        foreach ($active as $mp) {
            $results[$mp] = match($mp) {
                'trendyol' => $this->import_trendyol_orders($days),
                'hepsiburada' => $this->import_hepsiburada_orders($days),
                'n11' => $this->import_n11_orders($days),
                default => ['skipped' => true],
            };
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Delegate to main plugin for API requests
     */
    private function marketplace_request(string $mp, string $endpoint): array {
        $plugin = Isarud_Plugin::instance();
        $method = new \ReflectionMethod($plugin, 'marketplace_request');
        $method->setAccessible(true);
        return $method->invoke($plugin, $mp, $endpoint);
    }

    private function get_cred(string $mp, string $key): string {
        $plugin = Isarud_Plugin::instance();
        $method = new \ReflectionMethod($plugin, 'get_cred');
        $method->setAccessible(true);
        return $method->invoke($plugin, $mp, $key);
    }
}