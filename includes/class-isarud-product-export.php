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
        add_action('wp_ajax_isarud_toggle_auto_export', [$this, 'ajax_toggle_auto_export']);
        // Auto-export: WC product save_post hook'u (option açıksa tetiklenir)
        add_action('woocommerce_update_product', [$this, 'maybe_auto_export'], 20, 1);
        add_action('woocommerce_new_product', [$this, 'maybe_auto_export'], 20, 1);
    }

    /**
     * Export WooCommerce product to Trendyol
     */
    public function export_to_trendyol(\WC_Product $product): array {
        // Cloud Sync key kontrolü
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            return ['error' => 'Cloud Sync bağlantısı yok. Önce Cloud Sync sayfasından bağlanın.'];
        }

        $barcode = get_post_meta($product->get_id(), '_isarud_barcode', true) ?: $product->get_sku();
        if (empty($barcode)) return ['error' => 'Ürünün barkodu veya SKU\'su yok'];

        // Attribute mappings
        $attr_map = Isarud_Attribute_Map::instance();
        $category_id = $attr_map->get_mp_attribute('trendyol', 'category_id', $product->get_id());
        $brand_id = $attr_map->get_mp_attribute('trendyol', 'brand_id', $product->get_id());

        if (empty($category_id) || empty($brand_id)) {
            return ['error' => 'Trendyol kategori ve marka eşleştirmesi gerekli (Trendyol > Marka & Kategori sekmesi)'];
        }

        // Görseller
        $image_url = wp_get_attachment_url($product->get_image_id());
        $images = [];
        if ($image_url) $images[] = ['url' => $image_url];
        foreach ($product->get_gallery_image_ids() as $gid) {
            $gurl = wp_get_attachment_url($gid);
            if ($gurl) $images[] = ['url' => $gurl];
        }

        // Trendyol product item payload
        $item = [
            'barcode' => $barcode,
            'title' => $product->get_name(),
            'productMainId' => $product->get_sku() ?: $barcode,
            'brandId' => (int) $brand_id,
            'categoryId' => (int) $category_id,
            'quantity' => $product->get_stock_quantity() ?? 0,
            'stockCode' => $product->get_sku() ?: $barcode,
            'dimensionalWeight' => max(1, (float) $product->get_weight()),
            'description' => wp_strip_all_tags($product->get_description()) ?: $product->get_name(),
            'currencyType' => 'TRY',
            'listPrice' => (float) ($product->get_regular_price() ?: $product->get_price()),
            'salePrice' => $this->apply_margin((float) $product->get_price(), 'trendyol'),
            'vatRate' => 20,
            'cargoCompanyId' => 17, // Default Yurtiçi Kargo
            'images' => $images,
        ];

        // Mapped attributes (kategori/brand HARİÇ)
        $attributes = $attr_map->get_all_mp_attributes('trendyol', $product->get_id());
        if (!empty($attributes)) {
            $item['attributes'] = [];
            foreach ($attributes as $attr) {
                if ($attr['key'] !== 'category_id' && $attr['key'] !== 'brand_id') {
                    $item['attributes'][] = [
                        'attributeId' => (int) $attr['mp_id'],
                        'attributeValueId' => (int) $attr['mp_value_id'],
                    ];
                }
            }
        }

        // YENI BRIDGE: isarud.com'a HTTP POST (eski sapigw direkt değil)
        $response = wp_remote_post('https://isarud.com/api/v2/marketplace/trendyol/products/create', [
            'timeout' => 60,
            'headers' => [
                'X-Marketplace-Key' => $api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode(['items' => [$item]]),
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            $result = ['error' => 'HTTP error: ' . $response->get_error_message()];
        } else {
            $body = wp_remote_retrieve_body($response);
            $code = wp_remote_retrieve_response_code($response);
            $decoded = json_decode($body, true);
            if ($code >= 400) {
                $result = ['error' => $decoded['message'] ?? "HTTP $code", 'data' => $decoded];
            } else {
                $result = $decoded ?? ['error' => 'Geçersiz JSON yanıt'];
            }
        }

        $this->log_export($product->get_id(), 'trendyol', $result);
        return $result;
    }

    /**
     * Export WooCommerce product to Amazon (bridge to Isarud_Amazon)
     * Stub: AmazonService::createProduct eklendiğinde aktif olacak.
     */
    public function export_to_amazon(\WC_Product $product): array {
        if (!class_exists('Isarud_Amazon')) {
            return ['error' => 'Amazon modülü yüklü değil'];
        }
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            return ['error' => 'Cloud Sync bağlantısı yok'];
        }
        // Amazon SP-API listing creation stub - test mağaza geldiğinde aktif
        return ['error' => 'Amazon listing oluşturma henüz aktif değil (test mağaza bekleniyor)'];
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

        // Bridge to Isarud_Hepsiburada (v6.6.6+ - server-side credentials)
        if (!class_exists('Isarud_Hepsiburada')) {
            $result = ['error' => 'Hepsiburada modülü yüklü değil'];
        } else {
            $api_key = get_option('isarud_cloud_api_key', '');
            if (empty($api_key)) {
                $result = ['error' => 'Cloud Sync bağlantısı yok'];
            } else {
                // Hepsiburada submit via bridge (sunucu credentials kullanır)
                $result = Isarud_Hepsiburada::instance()->sync_stock_bulk([$item]);
            }
        }

        $this->log_export($product->get_id(), 'hepsiburada', $result);
        return $result;
    }

    /**
     * Export WooCommerce product to N11 (SOAP)
     */
    public function export_to_n11(\WC_Product $product): array {
        // Cloud Sync key kontrolü
        $api_key = get_option('isarud_cloud_api_key', '');
        if (empty($api_key)) {
            return ['error' => 'Cloud Sync bağlantısı yok. Önce Cloud Sync sayfasından bağlanın.'];
        }

        $sku = $product->get_sku() ?: get_post_meta($product->get_id(), '_isarud_barcode', true);
        if (empty($sku)) return ['error' => 'Ürünün SKU\'su yok'];

        // Attribute mappings
        $attr_map = Isarud_Attribute_Map::instance();
        $category_id = $attr_map->get_mp_attribute('n11', 'category_id', $product->get_id());

        if (empty($category_id)) {
            return ['error' => 'N11 kategori eşleştirmesi gerekli (N11 > Kategoriler sekmesi)'];
        }

        // Görseller
        $image_url = wp_get_attachment_url($product->get_image_id());
        $images = [];
        if ($image_url) $images[] = $image_url;
        foreach ($product->get_gallery_image_ids() as $gid) {
            $gurl = wp_get_attachment_url($gid);
            if ($gurl) $images[] = $gurl;
        }

        $price = $this->apply_margin((float) $product->get_price(), 'n11');

        // N11 modern REST item payload (isarud.com bridge → N11Service::submitProductBatch)
        $item = [
            'stockCode' => $sku,
            'productSellerCode' => $sku,
            'title' => $product->get_name(),
            'subtitle' => wp_trim_words($product->get_short_description(), 20, ''),
            'description' => wp_strip_all_tags($product->get_description()) ?: $product->get_name(),
            'categoryId' => (int) $category_id,
            'price' => $price,
            'salePrice' => $price,
            'currencyType' => 1, // TRY
            'quantity' => $product->get_stock_quantity() ?? 0,
            'preparingDay' => 3,
            'images' => $images,
            'domestic' => true,
        ];

        // Mapped attributes
        $attributes = $attr_map->get_all_mp_attributes('n11', $product->get_id());
        if (!empty($attributes)) {
            $item['attributes'] = [];
            foreach ($attributes as $attr) {
                if ($attr['key'] !== 'category_id') {
                    $item['attributes'][] = [
                        'attributeId' => (int) $attr['mp_id'],
                        'attributeValueId' => (int) $attr['mp_value_id'],
                    ];
                }
            }
        }

        // Bridge: WP plugin → isarud.com → N11 (modern REST)
        if (!class_exists('Isarud_N11')) {
            return ['error' => 'N11 modülü yüklü değil'];
        }

        $result = Isarud_N11::instance()->submit_product_batch([$item]);

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


    /**
     * AUTO-EXPORT: WC ürün kaydedildiğinde tüm aktif marketplaces'e gönder
     * (Sadece isarud_auto_export_enabled option'ı açıksa tetiklenir)
     */
    public function maybe_auto_export($product_id): void {
        if (!get_option('isarud_auto_export_enabled', false)) return;
        if (wp_is_post_revision($product_id)) return;

        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') return;

        // Skip variations - sadece parent ürün
        if ($product->get_type() === 'variation') return;

        // Aktif marketplace'ler
        global $wpdb;
        $active = $wpdb->get_col("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active = 1");
        if (empty($active)) return;

        // Her aktif marketplace'e gönder
        foreach ($active as $mp) {
            $method = "export_to_{$mp}";
            if (method_exists($this, $method)) {
                $this->{$method}($product);
            }
        }
    }

    /**
     * AJAX: Auto-export açma/kapama
     */
    public function ajax_toggle_auto_export(): void {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Yetki yok'], 403);
        check_ajax_referer('isarud_nonce', 'nonce');

        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';
        update_option('isarud_auto_export_enabled', $enabled);

        wp_send_json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled ? 'Otomatik gönderim AÇIK' : 'Otomatik gönderim KAPALI',
        ]);
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