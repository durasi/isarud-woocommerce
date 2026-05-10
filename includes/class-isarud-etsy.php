<?php
/**
 * class-isarud-etsy.php — WP Plugin Etsy Bridge Client
 * v6.4.0 - OAuth flow eklendi
 *
 * Mevcut Isarud_Cloud_Sync::api_request() pattern'ini takip eder.
 * Auth: X-Marketplace-Key header (Cloud Sync'te zaten çalışıyor)
 *
 * Phase 1: Listing CRUD (read/activate/deactivate/delete)
 * + OAuth flow (Etsy bağlantısı için Isarud sunucusu üzerinden)
 *
 * GÜVENLİK:
 * - Bu dosyada hiçbir secret yok, sadece HTTP client kodu
 * - API key WP options'tan okunur (Cloud Sync ile aynı)
 * - Etsy OAuth tokens ASLA WP'ye gelmez (sunucuda kalır)
 * - return_url Isarud sunucusunda whitelist edilir (WP user'ın kayıtlı site_url'i)
 */

if (!defined('ABSPATH')) exit;

class Isarud_Etsy {

    private string $api_base = 'https://isarud.com/api/v2/marketplace/';
    private static ?self $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        // AJAX handlers - Phase 1
        add_action('wp_ajax_isarud_etsy_status',          [$this, 'ajax_status']);
        add_action('wp_ajax_isarud_etsy_listings',        [$this, 'ajax_listings']);
        add_action('wp_ajax_isarud_etsy_listing_get',     [$this, 'ajax_listing_get']);
        add_action('wp_ajax_isarud_etsy_listing_activate',[$this, 'ajax_listing_activate']);
        add_action('wp_ajax_isarud_etsy_listing_deactivate',[$this, 'ajax_listing_deactivate']);
        add_action('wp_ajax_isarud_etsy_listing_delete',  [$this, 'ajax_listing_delete']);

        // AJAX handlers - OAuth (v6.4.0)
        add_action('wp_ajax_isarud_etsy_stores',          [$this, 'ajax_stores']);
        add_action('wp_ajax_isarud_etsy_authorize_url',   [$this, 'ajax_authorize_url']);

        // Phase 2 - Image management
        add_action('wp_ajax_isarud_etsy_images_list',     [$this, 'ajax_images_list']);
        add_action('wp_ajax_isarud_etsy_image_upload',    [$this, 'ajax_image_upload']);
        add_action('wp_ajax_isarud_etsy_image_delete',    [$this, 'ajax_image_delete']);
        add_action('wp_ajax_isarud_etsy_images_reorder',  [$this, 'ajax_images_reorder']);

        // Phase 3 - Sections + Personalization
        add_action('wp_ajax_isarud_etsy_sections_list',         [$this, 'ajax_sections_list']);
        add_action('wp_ajax_isarud_etsy_section_create',        [$this, 'ajax_section_create']);
        add_action('wp_ajax_isarud_etsy_section_update',        [$this, 'ajax_section_update']);
        add_action('wp_ajax_isarud_etsy_section_delete',        [$this, 'ajax_section_delete']);
        add_action('wp_ajax_isarud_etsy_assign_section',        [$this, 'ajax_assign_section']);
        add_action('wp_ajax_isarud_etsy_personalization',       [$this, 'ajax_personalization']);
        add_action('wp_ajax_isarud_etsy_production_partners',   [$this, 'ajax_production_partners']);
        add_action('wp_ajax_isarud_etsy_listing_variations',    [$this, 'ajax_listing_variations']);
        add_action('wp_ajax_isarud_etsy_listing_update',        [$this, 'ajax_listing_update']);

        // Phase 4 - Translations
        add_action('wp_ajax_isarud_etsy_translations_all',  [$this, 'ajax_translations_all']);
        add_action('wp_ajax_isarud_etsy_translation_get',   [$this, 'ajax_translation_get']);
        add_action('wp_ajax_isarud_etsy_translation_update',[$this, 'ajax_translation_update']);

        // Phase 5 - Shipping profiles
        add_action('wp_ajax_isarud_etsy_shipping_list',     [$this, 'ajax_shipping_list']);
        add_action('wp_ajax_isarud_etsy_shipping_create',   [$this, 'ajax_shipping_create']);
        add_action('wp_ajax_isarud_etsy_shipping_update',   [$this, 'ajax_shipping_update']);
        add_action('wp_ajax_isarud_etsy_shipping_delete',   [$this, 'ajax_shipping_delete']);

        // Phase 6 - Shop + Tracking
        add_action('wp_ajax_isarud_etsy_shop_fetch',        [$this, 'ajax_shop_fetch']);
        add_action('wp_ajax_isarud_etsy_shop_update',       [$this, 'ajax_shop_update']);
        add_action('wp_ajax_isarud_etsy_receipt_fetch',     [$this, 'ajax_receipt_fetch']);
        add_action('wp_ajax_isarud_etsy_receipt_ship',      [$this, 'ajax_receipt_ship']);

        // Phase 7 - Inventory + Stats + Sold
        add_action('wp_ajax_isarud_etsy_inventory_fetch',   [$this, 'ajax_inventory_fetch']);
        add_action('wp_ajax_isarud_etsy_listing_stats',     [$this, 'ajax_listing_stats']);
        add_action('wp_ajax_isarud_etsy_sold_listings',     [$this, 'ajax_sold_listings']);

        // Phase 8 - Returns
        add_action('wp_ajax_isarud_etsy_returns_list',      [$this, 'ajax_returns_list']);
        add_action('wp_ajax_isarud_etsy_returns_create',    [$this, 'ajax_returns_create']);
        add_action('wp_ajax_isarud_etsy_returns_update',    [$this, 'ajax_returns_update']);
        add_action('wp_ajax_isarud_etsy_returns_delete',    [$this, 'ajax_returns_delete']);

        // Callback success notice
        add_action('admin_notices',                       [$this, 'maybe_show_callback_notice']);
    }

    /**
     * Bridge HTTP request - mevcut Cloud Sync pattern
     */
    private function bridge_request(string $endpoint, array $data = [], string $method = 'GET'): array {
        $key = get_option('isarud_cloud_api_key', '');
        if (empty($key)) {
            return ['error' => __('Cloud API anahtarı yapılandırılmamış. Önce Cloud Sync sayfasından bağlantı kurun.', 'api-isarud')];
        }

        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Marketplace-Key' => $key,
                'Accept' => 'application/json',
                'User-Agent' => 'IsarudWP-Etsy/' . (defined('ISARUD_VERSION') ? ISARUD_VERSION : '1.0'),
            ],
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = wp_json_encode($data);
        }

        $url = $this->api_base . ltrim($endpoint, '/');
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true) ?: [];

        if ($code >= 400) {
            return [
                'error' => $body['error'] ?? "HTTP {$code}",
                'message' => $body['message'] ?? '',
                'http_code' => $code,
            ];
        }

        return $body;
    }

    // ─── Public API methods ───────────────────────────

    public function get_status(): array {
        return $this->bridge_request('etsy/status', [], 'GET');
    }

    public function get_listings(int $page = 0, int $size = 25): array {
        $endpoint = "etsy/listings?page={$page}&size={$size}";
        return $this->bridge_request($endpoint, [], 'GET');
    }

    public function get_listing(int $id): array {
        return $this->bridge_request("etsy/listings/{$id}", [], 'GET');
    }

    public function activate_listing(int $id): array {
        return $this->bridge_request("etsy/listings/{$id}/activate", [], 'POST');
    }

    public function deactivate_listing(int $id): array {
        return $this->bridge_request("etsy/listings/{$id}/deactivate", [], 'POST');
    }

    public function delete_listing(int $id): array {
        return $this->bridge_request("etsy/listings/{$id}", [], 'DELETE');
    }

    // ─── OAuth methods (v6.4.0) ──────────────────────

    /**
     * Isarud kullanıcısının store listesi (multi-store case için)
     */
    public function get_stores(): array {
        return $this->bridge_request('stores', [], 'GET');
    }

    /**
     * Etsy OAuth authorize URL (kullanıcı bu URL'e yönlendirilir)
     *
     * @param int $store_id Hangi Isarud store'a bağlanacak
     * @param string $return_url Etsy onay sonrası dönülecek WP admin URL
     */
    public function get_etsy_authorize_url(int $store_id, string $return_url): array {
        return $this->bridge_request('etsy/authorize-url', [
            'store_id' => $store_id,
            'return_url' => $return_url,
        ], 'POST');
    }

    // ─── AJAX handlers ────────────────────────────────

    private function check_ajax(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Yetkisiz erişim', 'api-isarud')], 403);
        }
        check_ajax_referer('isarud_etsy_nonce', 'nonce');
    }

    public function ajax_status(): void {
        $this->check_ajax();
        $r = $this->get_status();
        wp_send_json($r);
    }

    public function ajax_listings(): void {
        $this->check_ajax();
        $page = isset($_GET['page']) ? max(0, (int) $_GET['page']) : 0;
        $size = isset($_GET['size']) ? min(100, max(1, (int) $_GET['size'])) : 25;
        $r = $this->get_listings($page, $size);
        wp_send_json($r);
    }

    public function ajax_listing_get(): void {
        $this->check_ajax();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid id'], 400);
        $r = $this->get_listing($id);
        wp_send_json($r);
    }

    public function ajax_listing_activate(): void {
        $this->check_ajax();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid id'], 400);
        $r = $this->activate_listing($id);
        wp_send_json($r);
    }

    public function ajax_listing_deactivate(): void {
        $this->check_ajax();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid id'], 400);
        $r = $this->deactivate_listing($id);
        wp_send_json($r);
    }

    public function ajax_listing_delete(): void {
        $this->check_ajax();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid id'], 400);
        $r = $this->delete_listing($id);
        wp_send_json($r);
    }

    // ─── OAuth AJAX (v6.4.0) ─────────────────────────

    public function ajax_stores(): void {
        $this->check_ajax();
        $r = $this->get_stores();
        wp_send_json($r);
    }

    public function ajax_authorize_url(): void {
        $this->check_ajax();

        $store_id = isset($_POST['store_id']) ? (int) $_POST['store_id'] : 0;
        if ($store_id <= 0) {
            wp_send_json_error(['message' => 'Invalid store_id'], 400);
        }

        // Return URL: WP admin Etsy sayfası (callback için)
        $return_url = admin_url('admin.php?page=isarud-etsy');

        // HTTPS zorunlu (Isarud sunucusu da kontrol eder)
        if (strpos($return_url, 'https://') !== 0) {
            wp_send_json_error([
                'message' => __('OAuth bağlantısı için sitenizin HTTPS kullanması gerekir.', 'api-isarud')
            ], 400);
        }

        $r = $this->get_etsy_authorize_url($store_id, $return_url);
        wp_send_json($r);
    }

    /**
     * Etsy OAuth callback geldiğinde admin notice göster
     * URL: ?page=isarud-etsy&isarud_etsy_connected=1&shop=xxx&store_id=N
     */
    public function maybe_show_callback_notice(): void {
        if (!isset($_GET['page']) || $_GET['page'] !== 'isarud-etsy') return;
        if (!isset($_GET['isarud_etsy_connected'])) return;
        if (!current_user_can('manage_options')) return;

        $shop = isset($_GET['shop']) ? sanitize_text_field((string)$_GET['shop']) : '';
        $store_id = isset($_GET['store_id']) ? (int) $_GET['store_id'] : 0;

        ?>
        <div class="notice notice-success is-dismissible" style="margin:15px 20px;">
            <p style="font-size:14px;">
                ✅ <strong><?php esc_html_e('Etsy başarıyla bağlandı!', 'api-isarud'); ?></strong>
                <?php if ($shop): ?>
                    — <?php esc_html_e('Mağaza:', 'api-isarud'); ?> <strong><?php echo esc_html($shop); ?></strong>
                <?php endif; ?>
                <?php if ($store_id): ?>
                    (Store ID: <?php echo esc_html((string)$store_id); ?>)
                <?php endif; ?>
            </p>
        </div>
        <?php
    }

    // ═══════════════════════════════════════════════════
    // PHASE 2 - IMAGE MANAGEMENT
    // ═══════════════════════════════════════════════════

    public function get_images(int $listing_id): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/images", [], 'GET');
    }

    public function upload_image(int $listing_id, string $image_url, int $rank = 1): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/images", [
            'image_url' => $image_url,
            'rank' => $rank,
        ], 'POST');
    }

    public function delete_image(int $listing_id, int $image_id): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/images/{$image_id}", [], 'DELETE');
    }

    public function reorder_images(int $listing_id, array $ordered_ids): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/images/reorder", [
            'ordered_ids' => array_map('intval', $ordered_ids),
        ], 'POST');
    }

    public function ajax_images_list(): void {
        $this->check_ajax();
        $id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid listing_id'], 400);
        wp_send_json($this->get_images($id));
    }

    public function ajax_image_upload(): void {
        $this->check_ajax();
        $id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
        $rank = isset($_POST['rank']) ? (int) $_POST['rank'] : 1;
        if ($id <= 0 || empty($url)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->upload_image($id, $url, $rank));
    }

    public function ajax_image_delete(): void {
        $this->check_ajax();
        $id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $img = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;
        if ($id <= 0 || $img <= 0) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->delete_image($id, $img));
    }

    public function ajax_images_reorder(): void {
        $this->check_ajax();
        $id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $ordered = isset($_POST['ordered_ids']) ? (array) $_POST['ordered_ids'] : [];
        if ($id <= 0 || empty($ordered)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->reorder_images($id, $ordered));
    }

    // ═══════════════════════════════════════════════════
    // PHASE 3 - SECTIONS + PERSONALIZATION
    // ═══════════════════════════════════════════════════

    public function get_sections(): array {
        return $this->bridge_request('etsy/shop/sections', [], 'GET');
    }

    public function create_section(string $title): array {
        return $this->bridge_request('etsy/shop/sections', ['title' => $title], 'POST');
    }

    public function update_section(int $id, string $title): array {
        return $this->bridge_request("etsy/shop/sections/{$id}", ['title' => $title], 'PATCH');
    }

    public function delete_section(int $id): array {
        return $this->bridge_request("etsy/shop/sections/{$id}", [], 'DELETE');
    }

    public function assign_section_to_listing(int $listing_id, ?int $section_id): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/section", [
            'section_id' => $section_id,
        ], 'POST');
    }

    public function update_personalization(int $listing_id, array $config): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/personalization", [
            'config' => $config,
        ], 'POST');
    }

    public function get_production_partners(): array {
        return $this->bridge_request('etsy/production-partners', [], 'GET');
    }

    public function update_listing_variations(int $listing_id, array $variations, float $base_price, int $base_stock): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/variations", [
            'variations' => $variations,
            'base_price' => $base_price,
            'base_stock' => $base_stock,
        ], 'POST');
    }

    public function update_listing_generic(int $listing_id, array $updates): array {
        return $this->bridge_request("etsy/listings/{$listing_id}", ['updates' => $updates], 'PATCH');
    }

    public function ajax_sections_list(): void {
        $this->check_ajax();
        wp_send_json($this->get_sections());
    }

    public function ajax_section_create(): void {
        $this->check_ajax();
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        if (empty($title)) wp_send_json_error(['message' => 'Missing title'], 400);
        wp_send_json($this->create_section($title));
    }

    public function ajax_section_update(): void {
        $this->check_ajax();
        $id = isset($_POST['section_id']) ? (int) $_POST['section_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        if ($id <= 0 || empty($title)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->update_section($id, $title));
    }

    public function ajax_section_delete(): void {
        $this->check_ajax();
        $id = isset($_POST['section_id']) ? (int) $_POST['section_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid section_id'], 400);
        wp_send_json($this->delete_section($id));
    }

    public function ajax_assign_section(): void {
        $this->check_ajax();
        $lid = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $sid = isset($_POST['section_id']) && $_POST['section_id'] !== '' ? (int) $_POST['section_id'] : null;
        if ($lid <= 0) wp_send_json_error(['message' => 'Invalid listing_id'], 400);
        wp_send_json($this->assign_section_to_listing($lid, $sid));
    }

    public function ajax_personalization(): void {
        $this->check_ajax();
        $id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $config = isset($_POST['config']) && is_array($_POST['config']) ? $_POST['config'] : [];
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid listing_id'], 400);
        wp_send_json($this->update_personalization($id, $config));
    }

    public function ajax_production_partners(): void {
        $this->check_ajax();
        wp_send_json($this->get_production_partners());
    }

    public function ajax_listing_variations(): void {
        $this->check_ajax();
        $id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $variations = isset($_POST['variations']) && is_array($_POST['variations']) ? $_POST['variations'] : [];
        $price = isset($_POST['base_price']) ? (float) $_POST['base_price'] : 0;
        $stock = isset($_POST['base_stock']) ? (int) $_POST['base_stock'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid listing_id'], 400);
        wp_send_json($this->update_listing_variations($id, $variations, $price, $stock));
    }

    public function ajax_listing_update(): void {
        $this->check_ajax();
        $id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $updates = isset($_POST['updates']) && is_array($_POST['updates']) ? $_POST['updates'] : [];
        if ($id <= 0 || empty($updates)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->update_listing_generic($id, $updates));
    }

    // ═══════════════════════════════════════════════════
    // PHASE 4 - TRANSLATIONS
    // ═══════════════════════════════════════════════════

    public function get_translations_all(int $listing_id): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/translations", [], 'GET');
    }

    public function get_translation(int $listing_id, string $locale): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/translations/{$locale}", [], 'GET');
    }

    public function update_translation(int $listing_id, string $locale, array $fields): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/translations/{$locale}", [
            'fields' => $fields,
        ], 'POST');
    }

    public function ajax_translations_all(): void {
        $this->check_ajax();
        $id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid listing_id'], 400);
        wp_send_json($this->get_translations_all($id));
    }

    public function ajax_translation_get(): void {
        $this->check_ajax();
        $id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        $locale = isset($_GET['locale']) ? sanitize_text_field($_GET['locale']) : '';
        if ($id <= 0 || empty($locale)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->get_translation($id, $locale));
    }

    public function ajax_translation_update(): void {
        $this->check_ajax();
        $id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $locale = isset($_POST['locale']) ? sanitize_text_field($_POST['locale']) : '';
        $fields = isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : [];
        if ($id <= 0 || empty($locale) || empty($fields)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->update_translation($id, $locale, $fields));
    }

    // ═══════════════════════════════════════════════════
    // PHASE 5 - SHIPPING PROFILES
    // ═══════════════════════════════════════════════════

    public function get_shipping_profiles(): array {
        return $this->bridge_request('etsy/shipping/profiles', [], 'GET');
    }

    public function create_shipping_profile(array $data): array {
        return $this->bridge_request('etsy/shipping/profiles', ['data' => $data], 'POST');
    }

    public function update_shipping_profile(int $id, array $updates): array {
        return $this->bridge_request("etsy/shipping/profiles/{$id}", ['updates' => $updates], 'PATCH');
    }

    public function delete_shipping_profile(int $id): array {
        return $this->bridge_request("etsy/shipping/profiles/{$id}", [], 'DELETE');
    }

    public function ajax_shipping_list(): void {
        $this->check_ajax();
        wp_send_json($this->get_shipping_profiles());
    }

    public function ajax_shipping_create(): void {
        $this->check_ajax();
        $data = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
        if (empty($data)) wp_send_json_error(['message' => 'Invalid data'], 400);
        wp_send_json($this->create_shipping_profile($data));
    }

    public function ajax_shipping_update(): void {
        $this->check_ajax();
        $id = isset($_POST['profile_id']) ? (int) $_POST['profile_id'] : 0;
        $updates = isset($_POST['updates']) && is_array($_POST['updates']) ? $_POST['updates'] : [];
        if ($id <= 0 || empty($updates)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->update_shipping_profile($id, $updates));
    }

    public function ajax_shipping_delete(): void {
        $this->check_ajax();
        $id = isset($_POST['profile_id']) ? (int) $_POST['profile_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid profile_id'], 400);
        wp_send_json($this->delete_shipping_profile($id));
    }

    // ═══════════════════════════════════════════════════
    // PHASE 6 - SHOP + TRACKING
    // ═══════════════════════════════════════════════════

    public function get_shop(): array {
        return $this->bridge_request('etsy/shop', [], 'GET');
    }

    public function update_shop(array $updates): array {
        return $this->bridge_request('etsy/shop', ['updates' => $updates], 'PATCH');
    }

    public function get_receipt(int $id): array {
        return $this->bridge_request("etsy/receipts/{$id}", [], 'GET');
    }

    public function ship_receipt(int $id, string $tracking, string $carrier, ?string $note = null): array {
        $payload = [
            'tracking_code' => $tracking,
            'carrier_name' => $carrier,
        ];
        if ($note !== null) $payload['note'] = $note;
        return $this->bridge_request("etsy/receipts/{$id}/ship", $payload, 'POST');
    }

    public function ajax_shop_fetch(): void {
        $this->check_ajax();
        wp_send_json($this->get_shop());
    }

    public function ajax_shop_update(): void {
        $this->check_ajax();
        $updates = isset($_POST['updates']) && is_array($_POST['updates']) ? $_POST['updates'] : [];
        if (empty($updates)) wp_send_json_error(['message' => 'Invalid updates'], 400);
        wp_send_json($this->update_shop($updates));
    }

    public function ajax_receipt_fetch(): void {
        $this->check_ajax();
        $id = isset($_GET['receipt_id']) ? (int) $_GET['receipt_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid receipt_id'], 400);
        wp_send_json($this->get_receipt($id));
    }

    public function ajax_receipt_ship(): void {
        $this->check_ajax();
        $id = isset($_POST['receipt_id']) ? (int) $_POST['receipt_id'] : 0;
        $tracking = isset($_POST['tracking_code']) ? sanitize_text_field($_POST['tracking_code']) : '';
        $carrier = isset($_POST['carrier_name']) ? sanitize_text_field($_POST['carrier_name']) : '';
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : null;
        if ($id <= 0 || empty($tracking) || empty($carrier)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->ship_receipt($id, $tracking, $carrier, $note));
    }

    // ═══════════════════════════════════════════════════
    // PHASE 7 - INVENTORY + STATS + SOLD
    // ═══════════════════════════════════════════════════

    public function get_inventory(int $listing_id): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/inventory", [], 'GET');
    }

    public function get_listing_stats(int $listing_id): array {
        return $this->bridge_request("etsy/listings/{$listing_id}/stats", [], 'GET');
    }

    public function get_sold_listings(int $limit = 25, int $offset = 0): array {
        return $this->bridge_request("etsy/sold?limit={$limit}&offset={$offset}", [], 'GET');
    }

    public function ajax_inventory_fetch(): void {
        $this->check_ajax();
        $id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid listing_id'], 400);
        wp_send_json($this->get_inventory($id));
    }

    public function ajax_listing_stats(): void {
        $this->check_ajax();
        $id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid listing_id'], 400);
        wp_send_json($this->get_listing_stats($id));
    }

    public function ajax_sold_listings(): void {
        $this->check_ajax();
        $limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 25;
        $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;
        wp_send_json($this->get_sold_listings($limit, $offset));
    }

    // ═══════════════════════════════════════════════════
    // PHASE 8 - RETURNS
    // ═══════════════════════════════════════════════════

    public function get_return_policies(): array {
        return $this->bridge_request('etsy/return-policies', [], 'GET');
    }

    public function create_return_policy(array $data): array {
        return $this->bridge_request('etsy/return-policies', ['data' => $data], 'POST');
    }

    public function update_return_policy(int $id, array $updates): array {
        return $this->bridge_request("etsy/return-policies/{$id}", ['updates' => $updates], 'PATCH');
    }

    public function delete_return_policy(int $id): array {
        return $this->bridge_request("etsy/return-policies/{$id}", [], 'DELETE');
    }

    public function ajax_returns_list(): void {
        $this->check_ajax();
        wp_send_json($this->get_return_policies());
    }

    public function ajax_returns_create(): void {
        $this->check_ajax();
        $data = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
        if (empty($data)) wp_send_json_error(['message' => 'Invalid data'], 400);
        wp_send_json($this->create_return_policy($data));
    }

    public function ajax_returns_update(): void {
        $this->check_ajax();
        $id = isset($_POST['policy_id']) ? (int) $_POST['policy_id'] : 0;
        $updates = isset($_POST['updates']) && is_array($_POST['updates']) ? $_POST['updates'] : [];
        if ($id <= 0 || empty($updates)) wp_send_json_error(['message' => 'Invalid params'], 400);
        wp_send_json($this->update_return_policy($id, $updates));
    }

    public function ajax_returns_delete(): void {
        $this->check_ajax();
        $id = isset($_POST['policy_id']) ? (int) $_POST['policy_id'] : 0;
        if ($id <= 0) wp_send_json_error(['message' => 'Invalid policy_id'], 400);
        wp_send_json($this->delete_return_policy($id));
    }


}

// Singleton init
Isarud_Etsy::instance();
