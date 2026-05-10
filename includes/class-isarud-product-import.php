<?php
/**
 * Isarud Product Import
 * Trendyol, Hepsiburada, N11 ürünlerini WooCommerce'e aktarır
 */
if (!defined('ABSPATH')) exit;

class Isarud_Product_Import {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_import_products', [$this, 'ajax_import_products']);
        add_action('wp_ajax_isarud_fetch_mp_products', [$this, 'ajax_fetch_products']);
    }

    /**
     * Fetch products from Trendyol (list only, no import)
     */
    public function fetch_trendyol_products(int $page = 0, int $size = 50): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        if (empty($seller_id)) return ['error' => 'Trendyol credentials not configured'];

        $result = $this->marketplace_request('trendyol',
            "suppliers/{$seller_id}/products?page={$page}&size={$size}"
        );

        if (isset($result['error'])) return $result;

        $products = [];
        foreach ($result['content'] ?? [] as $p) {
            $products[] = [
                'external_id' => $p['productCode'] ?? $p['barcode'] ?? '',
                'barcode' => $p['barcode'] ?? '',
                'title' => $p['title'] ?? '',
                'price' => $p['salePrice'] ?? $p['listPrice'] ?? 0,
                'stock' => $p['quantity'] ?? 0,
                'image' => !empty($p['images']) ? $p['images'][0]['url'] ?? '' : '',
                'category' => $p['categoryName'] ?? '',
                'brand' => $p['brand'] ?? '',
                'color' => $p['color'] ?? '',
                'size' => $p['size'] ?? '',
                'marketplace' => 'trendyol',
            ];
        }

        return [
            'success' => true,
            'products' => $products,
            'total' => $result['totalElements'] ?? count($products),
            'page' => $page,
            'pages' => $result['totalPages'] ?? 1,
        ];
    }

    /**
     * Fetch products from Hepsiburada
     */
    public function fetch_hepsiburada_products(int $page = 0, int $size = 50): array {
        $merchant_id = $this->get_cred('hepsiburada', 'merchant_id');
        if (empty($merchant_id)) return ['error' => 'Hepsiburada credentials not configured'];

        $result = $this->marketplace_request('hepsiburada',
            "listings/merchantid/{$merchant_id}?offset={$page}&limit={$size}"
        );

        if (isset($result['error'])) return $result;

        $products = [];
        foreach ($result['listings'] ?? $result['items'] ?? [] as $p) {
            $products[] = [
                'external_id' => $p['hepsiburadaSku'] ?? $p['merchantSku'] ?? '',
                'barcode' => $p['merchantSku'] ?? '',
                'title' => $p['productName'] ?? '',
                'price' => $p['price'] ?? 0,
                'stock' => $p['availableStock'] ?? 0,
                'image' => '',
                'category' => '',
                'brand' => '',
                'marketplace' => 'hepsiburada',
            ];
        }

        return [
            'success' => true,
            'products' => $products,
            'total' => $result['totalCount'] ?? count($products),
            'page' => $page,
        ];
    }

    /**
     * Fetch products from N11 (SOAP)
     */
    public function fetch_n11_products(int $page = 0, int $size = 50): array {
        global $wpdb;
        $creds = $wpdb->get_var("SELECT credentials FROM {$wpdb->prefix}isarud_credentials WHERE marketplace='n11' AND is_active=1");
        if (!$creds) return ['error' => 'N11 credentials not configured'];
        $c = json_decode($creds, true);

        $xml = '<?xml version="1.0"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sch="http://www.n11.com/ws/schemas">'
            . '<soapenv:Header><sch:Authentication>'
            . '<sch:appKey>' . esc_xml($c['api_key'] ?? '') . '</sch:appKey>'
            . '<sch:appSecret>' . esc_xml($c['api_secret'] ?? '') . '</sch:appSecret>'
            . '</sch:Authentication></soapenv:Header>'
            . '<soapenv:Body><sch:GetProductListRequest>'
            . '<sch:pagingData><sch:currentPage>' . $page . '</sch:currentPage>'
            . '<sch:pageSize>' . $size . '</sch:pageSize></sch:pagingData>'
            . '</sch:GetProductListRequest></soapenv:Body></soapenv:Envelope>';

        $response = wp_remote_post('https://api.n11.com/ws/ProductService/', [
            'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
            'body' => $xml,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) return ['error' => $response->get_error_message()];

        $body = wp_remote_retrieve_body($response);
        $body = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$2_$3', $body);
        $xml_obj = @simplexml_load_string($body);

        $products = [];
        $nodes = $xml_obj ? ($xml_obj->xpath('//products/product') ?: []) : [];

        foreach ($nodes as $p) {
            $products[] = [
                'external_id' => (string)($p->id ?? ''),
                'barcode' => (string)($p->stockCode ?? $p->sellerCode ?? ''),
                'title' => (string)($p->title ?? ''),
                'price' => (float)($p->displayPrice ?? $p->price ?? 0),
                'stock' => (int)($p->stockQuantity ?? 0),
                'image' => (string)($p->picture ?? ''),
                'category' => (string)($p->category->name ?? ''),
                'marketplace' => 'n11',
            ];
        }

        return ['success' => true, 'products' => $products, 'page' => $page];
    }

    /**
     * Import products into WooCommerce
     */
    public function import_products(array $mp_products, array $options = []): array {
        if (!class_exists('WooCommerce')) return ['error' => 'WooCommerce not active'];

        $imported = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($mp_products as $mp_product) {
            $sku = $mp_product['barcode'] ?? $mp_product['external_id'] ?? '';
            if (empty($sku)) { $skipped++; continue; }

            // Check if product exists
            $existing_id = wc_get_product_id_by_sku($sku);

            if ($existing_id && !($options['update_existing'] ?? false)) {
                $skipped++;
                continue;
            }

            if ($existing_id) {
                // Update existing product
                $product = wc_get_product($existing_id);
                if ($options['update_stock'] ?? true) {
                    $product->set_stock_quantity($mp_product['stock'] ?? 0);
                }
                if ($options['update_price'] ?? true) {
                    $product->set_regular_price($mp_product['price'] ?? 0);
                }
                $product->save();
                $updated++;
            } else {
                // Create new product
                $product = new \WC_Product_Simple();
                $product->set_name($mp_product['title'] ?? 'Imported Product');
                $product->set_sku($sku);
                $product->set_regular_price($mp_product['price'] ?? 0);
                $product->set_manage_stock(true);
                $product->set_stock_quantity($mp_product['stock'] ?? 0);
                $product->set_status($options['status'] ?? 'draft');

                // Set category if mapping exists
                if (!empty($mp_product['category'])) {
                    $cat_id = Isarud_Category_Map::instance()->get_wc_category($mp_product['marketplace'] ?? '', $mp_product['category']);
                    if ($cat_id) {
                        $product->set_category_ids([$cat_id]);
                    }
                }

                $product->save();

                // Set barcode meta
                update_post_meta($product->get_id(), '_isarud_barcode', $sku);
                update_post_meta($product->get_id(), '_isarud_source_marketplace', $mp_product['marketplace'] ?? '');

                // Download and set image
                if (!empty($mp_product['image'])) {
                    $this->set_product_image($product->get_id(), $mp_product['image']);
                }

                $imported++;
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Download and set product featured image
     */
    private function set_product_image(int $product_id, string $image_url): void {
        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attachment_id = media_sideload_image($image_url, $product_id, '', 'id');
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }

    /**
     * AJAX: Fetch products from marketplace (preview)
     */
    public function ajax_fetch_products(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $page = intval($_POST['page'] ?? 0);

        $result = match($mp) {
            'trendyol' => $this->fetch_trendyol_products($page),
            'hepsiburada' => $this->fetch_hepsiburada_products($page),
            'n11' => $this->fetch_n11_products($page),
            default => ['error' => 'Unknown marketplace'],
        };

        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    /**
     * AJAX: Import products
     */
    public function ajax_import_products(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $options = [
            'update_existing' => ($_POST['update_existing'] ?? '0') === '1',
            'update_stock' => ($_POST['update_stock'] ?? '1') === '1',
            'update_price' => ($_POST['update_price'] ?? '1') === '1',
            'status' => sanitize_text_field($_POST['product_status'] ?? 'draft'),
        ];

        // Fetch all products first
        $all_products = [];
        $page = 0;
        do {
            $result = match($mp) {
                'trendyol' => $this->fetch_trendyol_products($page, 100),
                'hepsiburada' => $this->fetch_hepsiburada_products($page, 100),
                'n11' => $this->fetch_n11_products($page, 100),
                default => ['error' => 'Unknown'],
            };
            if (isset($result['error'])) wp_send_json_error($result);
            $all_products = array_merge($all_products, $result['products'] ?? []);
            $page++;
        } while (count($result['products'] ?? []) >= 100 && $page < 10); // Max 1000 products

        $import_result = $this->import_products($all_products, $options);
        wp_send_json_success($import_result);
    }

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