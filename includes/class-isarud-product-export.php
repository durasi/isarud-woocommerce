<?php
/**
 * Isarud Product Export
 * WooCommerce ürünlerini pazar yerlerine yükler (yeni ürün oluşturma)
 */
if (!defined('ABSPATH')) exit;

class Isarud_Product_Export {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_export_products', [$this, 'ajax_export_products']);
        add_action('wp_ajax_isarud_export_single', [$this, 'ajax_export_single']);
    }

    /**
     * Export WooCommerce product to Trendyol
     */
    public function export_to_trendyol(\WC_Product $product): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        if (empty($seller_id)) return ['error' => 'Trendyol credentials not configured'];

        $barcode = get_post_meta($product->get_id(), '_isarud_barcode', true) ?: $product->get_sku();
        if (empty($barcode)) return ['error' => 'Ürünün barkodu veya SKU\'su yok'];

        // Get attribute mappings
        $attr_map = Isarud_Attribute_Map::instance();
        $category_id = $attr_map->get_mp_attribute('trendyol', 'category_id', $product->get_id());
        $brand_id = $attr_map->get_mp_attribute('trendyol', 'brand_id', $product->get_id());

        $image_url = wp_get_attachment_url($product->get_image_id());
        $images = [];
        if ($image_url) $images[] = ['url' => $image_url];

        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $gid) {
            $gurl = wp_get_attachment_url($gid);
            if ($gurl) $images[] = ['url' => $gurl];
        }

        $item = [
            'barcode' => $barcode,
            'title' => $product->get_name(),
            'productMainId' => $product->get_sku() ?: $barcode,
            'brandId' => (int)($brand_id ?: 0),
            'categoryId' => (int)($category_id ?: 0),
            'quantity' => $product->get_stock_quantity() ?? 0,
            'stockCode' => $product->get_sku() ?: $barcode,
            'dimensionalWeight' => max(1, (float)$product->get_weight()),
            'description' => wp_strip_all_tags($product->get_description()) ?: $product->get_name(),
            'currencyType' => 'TRY',
            'listPrice' => (float)($product->get_regular_price() ?: $product->get_price()),
            'salePrice' => $this->apply_margin((float)$product->get_price(), 'trendyol'),
            'vatRate' => 20,
            'cargoCompanyId' => 17, // Default: Yurtiçi Kargo
            'images' => $images,
        ];

        // Add mapped attributes
        $attributes = $attr_map->get_all_mp_attributes('trendyol', $product->get_id());
        if (!empty($attributes)) {
            $item['attributes'] = [];
            foreach ($attributes as $attr) {
                if ($attr['key'] !== 'category_id' && $attr['key'] !== 'brand_id') {
                    $item['attributes'][] = [
                        'attributeId' => (int)$attr['mp_id'],
                        'attributeValueId' => (int)$attr['mp_value_id'],
                    ];
                }
            }
        }

        $result = $this->marketplace_request('trendyol',
            "suppliers/{$seller_id}/products",
            'POST',
            ['items' => [$item]]
        );

        $this->log_export($product->get_id(), 'trendyol', $result);
        return $result;
    }

    /**
     * Export WooCommerce product to Hepsiburada
     */
    public function export_to_hepsiburada(\WC_Product $product): array {
        $merchant_id = $this->get_cred('hepsiburada', 'merchant_id');
        if (empty($merchant_id)) return ['error' => 'Hepsiburada credentials not configured'];

        $sku = $product->get_sku() ?: get_post_meta($product->get_id(), '_isarud_barcode', true);
        if (empty($sku)) return ['error' => 'Ürünün SKU\'su yok'];

        $attr_map = Isarud_Attribute_Map::instance();
        $category_id = $attr_map->get_mp_attribute('hepsiburada', 'category_id', $product->get_id());

        $image_url = wp_get_attachment_url($product->get_image_id());

        $item = [
            'categoryId' => (int)($category_id ?: 0),
            'merchant' => $merchant_id,
            'attributes' => [
                'merchantSku' => $sku,
                'VaryantGroupID' => $product->get_sku() ?: $sku,
                'Barcode' => get_post_meta($product->get_id(), '_isarud_barcode', true) ?: $sku,
                'UrunAdi' => $product->get_name(),
                'UrunAciklamasi' => wp_strip_all_tags($product->get_description()) ?: $product->get_name(),
                'Image1' => $image_url ?: '',
                'tax_vat_rate' => '20',
            ],
        ];

        // Add gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        $img_idx = 2;
        foreach ($gallery_ids as $gid) {
            if ($img_idx > 5) break;
            $gurl = wp_get_attachment_url($gid);
            if ($gurl) {
                $item['attributes']['Image' . $img_idx] = $gurl;
                $img_idx++;
            }
        }

        $result = $this->marketplace_request('hepsiburada',
            "product/api/products/import",
            'POST',
            ['products' => [$item]]
        );

        $this->log_export($product->get_id(), 'hepsiburada', $result);
        return $result;
    }

    /**
     * Export WooCommerce product to N11 (SOAP)
     */
    public function export_to_n11(\WC_Product $product): array {
        global $wpdb;
        $creds = $wpdb->get_var("SELECT credentials FROM {$wpdb->prefix}isarud_credentials WHERE marketplace='n11' AND is_active=1");
        if (!$creds) return ['error' => 'N11 credentials not configured'];
        $c = json_decode($creds, true);

        $sku = $product->get_sku() ?: get_post_meta($product->get_id(), '_isarud_barcode', true);
        if (empty($sku)) return ['error' => 'Ürünün SKU\'su yok'];

        $attr_map = Isarud_Attribute_Map::instance();
        $category_id = $attr_map->get_mp_attribute('n11', 'category_id', $product->get_id());
        $image_url = wp_get_attachment_url($product->get_image_id());
        $price = $this->apply_margin((float)$product->get_price(), 'n11');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sch="http://www.n11.com/ws/schemas">'
            . '<soapenv:Header><sch:Authentication>'
            . '<sch:appKey>' . esc_xml($c['api_key'] ?? '') . '</sch:appKey>'
            . '<sch:appSecret>' . esc_xml($c['api_secret'] ?? '') . '</sch:appSecret>'
            . '</sch:Authentication></soapenv:Header>'
            . '<soapenv:Body><sch:SaveProductRequest>'
            . '<sch:product>'
            . '<sch:productSellerCode>' . esc_xml($sku) . '</sch:productSellerCode>'
            . '<sch:title>' . esc_xml($product->get_name()) . '</sch:title>'
            . '<sch:subtitle>' . esc_xml(wp_trim_words($product->get_short_description(), 20, '')) . '</sch:subtitle>'
            . '<sch:description>' . esc_xml(wp_strip_all_tags($product->get_description()) ?: $product->get_name()) . '</sch:description>'
            . '<sch:category><sch:id>' . (int)($category_id ?: 0) . '</sch:id></sch:category>'
            . '<sch:price>' . $price . '</sch:price>'
            . '<sch:currencyType>1</sch:currencyType>'
            . '<sch:images><sch:image><sch:url>' . esc_xml($image_url ?: '') . '</sch:url><sch:order>1</sch:order></sch:image></sch:images>'
            . '<sch:stockItems><sch:stockItem>'
            . '<sch:sellerStockCode>' . esc_xml($sku) . '</sch:sellerStockCode>'
            . '<sch:quantity>' . ($product->get_stock_quantity() ?? 0) . '</sch:quantity>'
            . '<sch:optionPrice>' . $price . '</sch:optionPrice>'
            . '</sch:stockItem></sch:stockItems>'
            . '<sch:domestic>true</sch:domestic>'
            . '<sch:preparingDay>3</sch:preparingDay>'
            . '</sch:product>'
            . '</sch:SaveProductRequest></soapenv:Body></soapenv:Envelope>';

        $response = wp_remote_post('https://api.n11.com/ws/ProductService/', [
            'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
            'body' => $xml,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $result = ['error' => $response->get_error_message()];
        } else {
            $body = wp_remote_retrieve_body($response);
            $result = (strpos($body, 'errorCode') !== false && strpos($body, '<errorCode>0</errorCode>') === false)
                ? ['error' => 'N11 product upload error: ' . wp_strip_all_tags($body)]
                : ['success' => true];
        }

        $this->log_export($product->get_id(), 'n11', $result);
        return $result;
    }

    /**
     * Bulk export products
     */
    public function bulk_export(string $marketplace, array $product_ids): array {
        $exported = 0;
        $failed = 0;
        $errors = [];

        foreach ($product_ids as $pid) {
            $product = wc_get_product($pid);
            if (!$product) { $failed++; continue; }

            $result = match($marketplace) {
                'trendyol' => $this->export_to_trendyol($product),
                'hepsiburada' => $this->export_to_hepsiburada($product),
                'n11' => $this->export_to_n11($product),
                default => ['error' => 'Unsupported marketplace'],
            };

            if (isset($result['error'])) {
                $failed++;
                $errors[] = "#{$pid}: " . $result['error'];
            } else {
                $exported++;
            }
        }

        return ['success' => true, 'exported' => $exported, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * AJAX: Export products
     */
    public function ajax_export_products(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $product_ids = array_map('intval', $_POST['product_ids'] ?? []);

        if (empty($product_ids)) {
            // Export all published products
            $product_ids = wc_get_products([
                'status' => 'publish',
                'limit' => 500,
                'return' => 'ids',
            ]);
        }

        $result = $this->bulk_export($mp, $product_ids);
        wp_send_json_success($result);
    }

    /**
     * AJAX: Export single product
     */
    public function ajax_export_single(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $pid = intval($_POST['product_id'] ?? 0);
        $product = wc_get_product($pid);

        if (!$product) wp_send_json_error('Product not found');

        $result = match($mp) {
            'trendyol' => $this->export_to_trendyol($product),
            'hepsiburada' => $this->export_to_hepsiburada($product),
            'n11' => $this->export_to_n11($product),
            default => ['error' => 'Unsupported marketplace'],
        };

        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    private function log_export(int $product_id, string $mp, array $result): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'isarud_sync_log', [
            'product_id' => $product_id,
            'marketplace' => $mp,
            'action' => 'product_export',
            'status' => isset($result['error']) ? 'error' : 'success',
            'message' => isset($result['error']) ? $result['error'] : 'Exported',
            'created_at' => current_time('mysql'),
        ]);
    }

    private function apply_margin(float $price, string $mp): float {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT price_margin, price_margin_type FROM {$wpdb->prefix}isarud_credentials WHERE marketplace=%s", $mp
        ));
        if ($row && floatval($row->price_margin) != 0) {
            return $row->price_margin_type === 'percent'
                ? round($price * (1 + floatval($row->price_margin) / 100), 2)
                : round($price + floatval($row->price_margin), 2);
        }
        return $price;
    }

    private function marketplace_request(string $mp, string $endpoint, string $method = 'GET', $data = null): array {
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