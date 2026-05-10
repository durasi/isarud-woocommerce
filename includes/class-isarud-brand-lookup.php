<?php
if (!defined('ABSPATH')) exit;

class Isarud_Brand_Lookup {
    private static ?self $instance = null;
    public static function instance(): self { if (!self::$instance) self::$instance = new self(); return self::$instance; }

    public function __construct() {
        add_action('wp_ajax_isarud_search_brands', [$this, 'ajax_search_brands']);
        add_action('wp_ajax_isarud_fetch_categories_tree', [$this, 'ajax_fetch_categories']);
        add_action('wp_ajax_isarud_fetch_category_attributes', [$this, 'ajax_fetch_category_attrs']);
    }

    /**
     * Search Trendyol brands by name
     */
    public function search_trendyol_brands(string $name): array {
        $result = $this->mp_request('trendyol', "brands/by-name?name=" . urlencode($name));
        if (isset($result['error'])) return $result;
        $brands = [];
        foreach ($result as $b) {
            $brands[] = ['id' => $b['id'] ?? '', 'name' => $b['name'] ?? ''];
        }
        return ['success' => true, 'brands' => $brands];
    }

    /**
     * Get all Trendyol categories
     */
    public function get_trendyol_categories(): array {
        $result = $this->mp_request('trendyol', 'product-categories');
        if (isset($result['error'])) return $result;
        return ['success' => true, 'categories' => $this->flatten_categories($result['categories'] ?? [])];
    }

    /**
     * Get Trendyol category attributes (required for product upload)
     */
    public function get_trendyol_category_attributes(int $category_id): array {
        $result = $this->mp_request('trendyol', "product-categories/{$category_id}/attributes");
        if (isset($result['error'])) return $result;
        $attrs = [];
        foreach ($result['categoryAttributes'] ?? [] as $a) {
            $values = [];
            foreach ($a['attributeValues'] ?? [] as $v) {
                $values[] = ['id' => $v['id'] ?? '', 'name' => $v['name'] ?? ''];
            }
            $attrs[] = [
                'id' => $a['attribute']['id'] ?? '',
                'name' => $a['attribute']['name'] ?? '',
                'required' => $a['required'] ?? false,
                'allowCustom' => $a['allowCustom'] ?? false,
                'values' => $values,
            ];
        }
        return ['success' => true, 'attributes' => $attrs, 'category_id' => $category_id];
    }

    private function flatten_categories(array $cats, string $prefix = ''): array {
        $flat = [];
        foreach ($cats as $c) {
            $name = $prefix ? $prefix . ' > ' . ($c['name'] ?? '') : ($c['name'] ?? '');
            $flat[] = ['id' => $c['id'] ?? '', 'name' => $name];
            if (!empty($c['subCategories'])) {
                $flat = array_merge($flat, $this->flatten_categories($c['subCategories'], $name));
            }
        }
        return $flat;
    }

    public function ajax_search_brands(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        $name = sanitize_text_field($_POST['brand_name'] ?? '');
        if (strlen($name) < 2) wp_send_json_error('Min 2 karakter');
        $result = $this->search_trendyol_brands($name);
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    public function ajax_fetch_categories(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        $result = $this->get_trendyol_categories();
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    public function ajax_fetch_category_attrs(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        $cat_id = intval($_POST['category_id'] ?? 0);
        if (!$cat_id) wp_send_json_error('Category ID required');
        $result = $this->get_trendyol_category_attributes($cat_id);
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    private function mp_request(string $mp, string $ep, string $m = 'GET', $d = null): array {
        $p = Isarud_Plugin::instance(); $r = new \ReflectionMethod($p, 'marketplace_request'); $r->setAccessible(true); return $r->invoke($p, $mp, $ep, $m, $d);
    }
}
