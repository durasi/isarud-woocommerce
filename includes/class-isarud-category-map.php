<?php
/**
 * Isarud Category Map
 * WooCommerce kategorileri ↔ Pazar yeri kategorileri eşleştirme
 */
if (!defined('ABSPATH')) exit;

class Isarud_Category_Map {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_save_category_map', [$this, 'ajax_save_map']);
        add_action('wp_ajax_isarud_fetch_mp_categories', [$this, 'ajax_fetch_categories']);
    }

    /**
     * Get WC category ID from marketplace category name
     */
    public function get_wc_category(string $marketplace, string $mp_category): ?int {
        $mappings = get_option('isarud_category_mappings', []);
        $key = $marketplace . ':' . $mp_category;
        return $mappings[$key] ?? null;
    }

    /**
     * Save category mapping
     */
    public function save_mapping(string $marketplace, string $mp_category, int $wc_category_id): void {
        $mappings = get_option('isarud_category_mappings', []);
        $key = $marketplace . ':' . $mp_category;
        $mappings[$key] = $wc_category_id;
        update_option('isarud_category_mappings', $mappings);
    }

    /**
     * Get all mappings
     */
    public function get_all_mappings(): array {
        return get_option('isarud_category_mappings', []);
    }

    /**
     * Fetch marketplace categories
     */
    public function fetch_trendyol_categories(): array {
        $plugin = Isarud_Plugin::instance();
        $method = new \ReflectionMethod($plugin, 'marketplace_request');
        $method->setAccessible(true);
        $result = $method->invoke($plugin, 'trendyol', 'product-categories');
        if (isset($result['error'])) return $result;

        $categories = [];
        foreach ($result['categories'] ?? [] as $cat) {
            $categories[] = ['id' => $cat['id'] ?? '', 'name' => $cat['name'] ?? ''];
            foreach ($cat['subCategories'] ?? [] as $sub) {
                $categories[] = ['id' => $sub['id'] ?? '', 'name' => ($cat['name'] ?? '') . ' > ' . ($sub['name'] ?? '')];
            }
        }
        return ['success' => true, 'categories' => $categories];
    }

    /**
     * Render category mapping UI (called from admin page)
     */
    public function render_mapping_ui(): void {
        $mappings = $this->get_all_mappings();
        $wc_categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        ?>
        <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0">
            <h2 style="margin-top:0"><?php _e('Kategori Eşleştirme', 'api-isarud'); ?></h2>
            <p style="color:#666"><?php _e('Pazar yeri kategorilerini WooCommerce kategorileriyle eşleştirin. Ürün aktarımında kullanılır.', 'api-isarud'); ?></p>

            <div id="isarud-category-mappings">
                <?php if (empty($mappings)): ?>
                    <p style="color:#999"><?php _e('Henüz eşleştirme yok. Ürün aktarımı sırasında otomatik oluşturulur veya aşağıdan ekleyin.', 'api-isarud'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr>
                            <th><?php _e('Pazar Yeri Kategorisi', 'api-isarud'); ?></th>
                            <th><?php _e('WooCommerce Kategorisi', 'api-isarud'); ?></th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($mappings as $key => $wc_cat_id):
                            $parts = explode(':', $key, 2);
                            $mp = $parts[0] ?? '';
                            $mp_cat = $parts[1] ?? '';
                            $wc_term = get_term($wc_cat_id, 'product_cat');
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst($mp)); ?>:</strong> <?php echo esc_html($mp_cat); ?></td>
                            <td><?php echo $wc_term ? esc_html($wc_term->name) : '<em>Silinmiş</em>'; ?></td>
                            <td><button type="button" class="button button-small isarud-remove-mapping" data-key="<?php echo esc_attr($key); ?>">✗</button></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div style="margin-top:15px;display:flex;gap:10px;align-items:center">
                <select id="isarud-map-mp">
                    <option value="trendyol">Trendyol</option>
                    <option value="hepsiburada">Hepsiburada</option>
                    <option value="n11">N11</option>
                </select>
                <input type="text" id="isarud-map-mp-cat" placeholder="<?php _e('Pazar yeri kategori adı', 'api-isarud'); ?>" class="regular-text">
                <select id="isarud-map-wc-cat">
                    <?php foreach ($wc_categories as $cat): ?>
                    <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button-primary" id="isarud-add-mapping"><?php _e('Ekle', 'api-isarud'); ?></button>
            </div>
        </div>

        <script>
        jQuery(function($) {
            $('#isarud-add-mapping').on('click', function() {
                $.post(isarud.ajax, {
                    action: 'isarud_save_category_map',
                    nonce: isarud.nonce,
                    marketplace: $('#isarud-map-mp').val(),
                    mp_category: $('#isarud-map-mp-cat').val(),
                    wc_category: $('#isarud-map-wc-cat').val()
                }, function(r) {
                    if (r.success) location.reload();
                    else alert(r.data);
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_save_map(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $mp_cat = sanitize_text_field($_POST['mp_category'] ?? '');
        $wc_cat = intval($_POST['wc_category'] ?? 0);

        if (empty($mp) || empty($mp_cat) || !$wc_cat) {
            wp_send_json_error('Tüm alanları doldurun');
        }

        $this->save_mapping($mp, $mp_cat, $wc_cat);
        wp_send_json_success('Saved');
    }

    public function ajax_fetch_categories(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');

        $result = match($mp) {
            'trendyol' => $this->fetch_trendyol_categories(),
            default => ['error' => 'Category fetch not supported for ' . $mp],
        };

        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }
}