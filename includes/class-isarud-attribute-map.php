<?php
/**
 * Isarud Attribute Map
 * WooCommerce attribute'ları ↔ Pazar yeri attribute'ları eşleştirme
 * Renk, beden, marka vb. attribute mapping
 */
if (!defined('ABSPATH')) exit;

class Isarud_Attribute_Map {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_save_attribute_map', [$this, 'ajax_save_map']);
        add_action('wp_ajax_isarud_delete_attribute_map', [$this, 'ajax_delete_map']);
        add_action('wp_ajax_isarud_fetch_mp_attributes', [$this, 'ajax_fetch_attributes']);
    }

    /**
     * Get marketplace attribute value for a product
     */
    public function get_mp_attribute(string $marketplace, string $attr_key, int $product_id): ?string {
        // First check product-level override
        $product_val = get_post_meta($product_id, "_isarud_{$marketplace}_{$attr_key}", true);
        if (!empty($product_val)) return $product_val;

        // Then check global mapping
        $mappings = get_option("isarud_attr_map_{$marketplace}", []);
        
        // Try to match WC attribute
        $product = wc_get_product($product_id);
        if (!$product) return null;

        foreach ($mappings as $map) {
            $wc_attr = $map['wc_attribute'] ?? '';
            $wc_value = $map['wc_value'] ?? '';
            $mp_value = $map['mp_value'] ?? '';

            if (empty($wc_attr) || empty($mp_value)) continue;

            // Check if product has this attribute with this value
            $product_attr_value = $product->get_attribute($wc_attr);
            if ($product_attr_value && ($wc_value === '*' || stripos($product_attr_value, $wc_value) !== false)) {
                return $mp_value;
            }
        }

        return null;
    }

    /**
     * Get all mapped attributes for a product
     */
    public function get_all_mp_attributes(string $marketplace, int $product_id): array {
        $mappings = get_option("isarud_attr_map_{$marketplace}", []);
        $result = [];
        $product = wc_get_product($product_id);
        if (!$product) return $result;

        foreach ($mappings as $map) {
            $wc_attr = $map['wc_attribute'] ?? '';
            $wc_value = $map['wc_value'] ?? '';

            if (empty($wc_attr)) continue;

            $product_attr_value = $product->get_attribute($wc_attr);
            if ($product_attr_value && ($wc_value === '*' || stripos($product_attr_value, $wc_value) !== false)) {
                $result[] = [
                    'key' => $map['mp_attribute'] ?? '',
                    'mp_id' => $map['mp_id'] ?? '',
                    'mp_value_id' => $map['mp_value_id'] ?? '',
                    'value' => $map['mp_value'] ?? '',
                ];
            }
        }

        return $result;
    }

    /**
     * Save attribute mapping
     */
    public function save_mapping(string $marketplace, array $mapping): void {
        $mappings = get_option("isarud_attr_map_{$marketplace}", []);
        $mappings[] = $mapping;
        update_option("isarud_attr_map_{$marketplace}", $mappings);
    }

    /**
     * Delete attribute mapping by index
     */
    public function delete_mapping(string $marketplace, int $index): void {
        $mappings = get_option("isarud_attr_map_{$marketplace}", []);
        if (isset($mappings[$index])) {
            array_splice($mappings, $index, 1);
            update_option("isarud_attr_map_{$marketplace}", $mappings);
        }
    }

    /**
     * Render attribute mapping UI
     */
    public function render_mapping_ui(): void {
        $marketplaces = ['trendyol', 'hepsiburada', 'n11'];
        $wc_attributes = wc_get_attribute_taxonomies();
        ?>
        <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0">
            <h2 style="margin-top:0"><?php _e('Attribute Eşleştirme', 'api-isarud'); ?></h2>
            <p style="color:#666"><?php _e('WooCommerce ürün attribute\'larını (renk, beden, marka vb.) pazar yeri attribute\'larıyla eşleştirin. Ürün yüklemede kullanılır.', 'api-isarud'); ?></p>

            <?php foreach ($marketplaces as $mp):
                $mappings = get_option("isarud_attr_map_{$mp}", []);
            ?>
            <div style="margin:15px 0;border:1px solid #eee;border-radius:8px;padding:15px">
                <h3 style="margin-top:0"><?php echo esc_html(ucfirst($mp)); ?></h3>

                <?php if (!empty($mappings)): ?>
                <table class="wp-list-table widefat fixed striped" style="margin-bottom:10px">
                    <thead><tr>
                        <th>WC Attribute</th>
                        <th>WC Değer</th>
                        <th>MP Attribute</th>
                        <th>MP ID</th>
                        <th>MP Değer</th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($mappings as $idx => $map): ?>
                    <tr>
                        <td><?php echo esc_html($map['wc_attribute'] ?? ''); ?></td>
                        <td><?php echo esc_html($map['wc_value'] ?? '*'); ?></td>
                        <td><?php echo esc_html($map['mp_attribute'] ?? ''); ?></td>
                        <td><code><?php echo esc_html($map['mp_id'] ?? ''); ?></code></td>
                        <td><?php echo esc_html($map['mp_value'] ?? ''); ?></td>
                        <td><button type="button" class="button button-small isarud-del-attr" data-mp="<?php echo esc_attr($mp); ?>" data-idx="<?php echo $idx; ?>">✗</button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <select class="isarud-attr-wc-attr" data-mp="<?php echo esc_attr($mp); ?>">
                        <option value="">WC Attribute</option>
                        <?php foreach ($wc_attributes as $attr): ?>
                        <option value="<?php echo esc_attr($attr->attribute_label); ?>"><?php echo esc_html($attr->attribute_label); ?></option>
                        <?php endforeach; ?>
                        <option value="category">Kategori</option>
                        <option value="brand">Marka</option>
                    </select>
                    <input type="text" class="isarud-attr-wc-val" data-mp="<?php echo esc_attr($mp); ?>" placeholder="WC Değer (* = tümü)" style="width:100px">
                    <input type="text" class="isarud-attr-mp-attr" data-mp="<?php echo esc_attr($mp); ?>" placeholder="MP Attribute Adı" style="width:120px">
                    <input type="text" class="isarud-attr-mp-id" data-mp="<?php echo esc_attr($mp); ?>" placeholder="MP Attribute ID" style="width:100px">
                    <input type="text" class="isarud-attr-mp-val-id" data-mp="<?php echo esc_attr($mp); ?>" placeholder="MP Value ID" style="width:100px">
                    <input type="text" class="isarud-attr-mp-val" data-mp="<?php echo esc_attr($mp); ?>" placeholder="MP Değer" style="width:100px">
                    <button type="button" class="button isarud-add-attr" data-mp="<?php echo esc_attr($mp); ?>"><?php _e('Ekle', 'api-isarud'); ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <script>
        jQuery(function($) {
            $('.isarud-add-attr').on('click', function() {
                var mp = $(this).data('mp');
                $.post(isarud.ajax, {
                    action: 'isarud_save_attribute_map',
                    nonce: isarud.nonce,
                    marketplace: mp,
                    wc_attribute: $('.isarud-attr-wc-attr[data-mp="'+mp+'"]').val(),
                    wc_value: $('.isarud-attr-wc-val[data-mp="'+mp+'"]').val() || '*',
                    mp_attribute: $('.isarud-attr-mp-attr[data-mp="'+mp+'"]').val(),
                    mp_id: $('.isarud-attr-mp-id[data-mp="'+mp+'"]').val(),
                    mp_value_id: $('.isarud-attr-mp-val-id[data-mp="'+mp+'"]').val(),
                    mp_value: $('.isarud-attr-mp-val[data-mp="'+mp+'"]').val()
                }, function(r) {
                    if (r.success) location.reload();
                    else alert(r.data);
                });
            });

            $('.isarud-del-attr').on('click', function() {
                if (!confirm('Silmek istediğinize emin misiniz?')) return;
                $.post(isarud.ajax, {
                    action: 'isarud_delete_attribute_map',
                    nonce: isarud.nonce,
                    marketplace: $(this).data('mp'),
                    index: $(this).data('idx')
                }, function(r) { if (r.success) location.reload(); });
            });
        });
        </script>
        <?php
    }

    public function ajax_save_map(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        if (empty($mp)) wp_send_json_error('Marketplace required');

        $this->save_mapping($mp, [
            'wc_attribute' => sanitize_text_field($_POST['wc_attribute'] ?? ''),
            'wc_value' => sanitize_text_field($_POST['wc_value'] ?? '*'),
            'mp_attribute' => sanitize_text_field($_POST['mp_attribute'] ?? ''),
            'mp_id' => sanitize_text_field($_POST['mp_id'] ?? ''),
            'mp_value_id' => sanitize_text_field($_POST['mp_value_id'] ?? ''),
            'mp_value' => sanitize_text_field($_POST['mp_value'] ?? ''),
        ]);

        wp_send_json_success('Saved');
    }

    public function ajax_delete_map(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $idx = intval($_POST['index'] ?? -1);

        $this->delete_mapping($mp, $idx);
        wp_send_json_success('Deleted');
    }

    public function ajax_fetch_attributes(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);

        if ($mp === 'trendyol' && $category_id) {
            $plugin = Isarud_Plugin::instance();
            $ref = new \ReflectionMethod($plugin, 'marketplace_request');
            $ref->setAccessible(true);
            $result = $ref->invoke($plugin, 'trendyol', "product-categories/{$category_id}/attributes");
            wp_send_json_success($result);
        }

        wp_send_json_error('Not supported');
    }
}