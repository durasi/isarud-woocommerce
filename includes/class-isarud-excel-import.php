<?php
/**
 * Isarud Excel/CSV Import
 * CSV veya Excel dosyasından toplu ürün import/sync
 */
if (!defined('ABSPATH')) exit;

class Isarud_Excel_Import {

    private static ?self $instance = null;
    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_isarud_csv_import', [$this, 'ajax_csv_import']);
        add_action('wp_ajax_isarud_csv_export', [$this, 'ajax_csv_export']);
    }

    /**
     * Import products from CSV
     * Expected columns: sku, title, price, stock, barcode, category, description, image_url, marketplace
     */
    public function import_from_csv(string $file_path, array $options = []): array {
        if (!file_exists($file_path)) return ['error' => 'File not found'];

        $handle = fopen($file_path, 'r');
        if (!$handle) return ['error' => 'Cannot open file'];

        // Read header
        $header = fgetcsv($handle, 0, $options['delimiter'] ?? ',');
        if (!$header) {
            fclose($handle);
            return ['error' => 'Empty file or invalid CSV'];
        }

        // Normalize header
        $header = array_map(function($h) {
            return strtolower(trim(str_replace(["\xEF\xBB\xBF", '"'], '', $h)));
        }, $header);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $row_num = 1;

        while (($row = fgetcsv($handle, 0, $options['delimiter'] ?? ',')) !== false) {
            $row_num++;

            if (count($row) !== count($header)) {
                $errors[] = "Satır {$row_num}: Sütun sayısı uyuşmuyor";
                $skipped++;
                continue;
            }

            $data = array_combine($header, $row);
            $sku = trim($data['sku'] ?? $data['barcode'] ?? $data['barkod'] ?? '');

            if (empty($sku)) {
                $skipped++;
                continue;
            }

            try {
                $existing_id = wc_get_product_id_by_sku($sku);

                if ($existing_id) {
                    if (!($options['update_existing'] ?? false)) {
                        $skipped++;
                        continue;
                    }

                    $product = wc_get_product($existing_id);
                    if (isset($data['title']) || isset($data['urun_adi'])) {
                        $product->set_name($data['title'] ?? $data['urun_adi']);
                    }
                    if (isset($data['price']) || isset($data['fiyat'])) {
                        $product->set_regular_price((float)($data['price'] ?? $data['fiyat']));
                    }
                    if (isset($data['stock']) || isset($data['stok'])) {
                        $product->set_manage_stock(true);
                        $product->set_stock_quantity((int)($data['stock'] ?? $data['stok']));
                    }
                    $product->save();
                    $updated++;
                } else {
                    $product = new \WC_Product_Simple();
                    $product->set_name($data['title'] ?? $data['urun_adi'] ?? 'Imported Product');
                    $product->set_sku($sku);
                    $product->set_regular_price((float)($data['price'] ?? $data['fiyat'] ?? 0));
                    $product->set_manage_stock(true);
                    $product->set_stock_quantity((int)($data['stock'] ?? $data['stok'] ?? 0));
                    $product->set_status($options['status'] ?? 'draft');

                    if (!empty($data['description']) || !empty($data['aciklama'])) {
                        $product->set_description($data['description'] ?? $data['aciklama']);
                    }

                    // Set category
                    if (!empty($data['category']) || !empty($data['kategori'])) {
                        $cat_name = $data['category'] ?? $data['kategori'];
                        $term = get_term_by('name', $cat_name, 'product_cat');
                        if (!$term) {
                            $term_arr = wp_insert_term($cat_name, 'product_cat');
                            if (!is_wp_error($term_arr)) {
                                $product->set_category_ids([$term_arr['term_id']]);
                            }
                        } else {
                            $product->set_category_ids([$term->term_id]);
                        }
                    }

                    $product->save();

                    // Set barcode meta
                    $barcode = $data['barcode'] ?? $data['barkod'] ?? $sku;
                    update_post_meta($product->get_id(), '_isarud_barcode', $barcode);

                    if (!empty($data['marketplace']) || !empty($data['pazaryeri'])) {
                        update_post_meta($product->get_id(), '_isarud_source_marketplace', $data['marketplace'] ?? $data['pazaryeri']);
                    }

                    // Download image
                    if (!empty($data['image_url']) || !empty($data['gorsel'])) {
                        $this->set_product_image($product->get_id(), $data['image_url'] ?? $data['gorsel']);
                    }

                    $imported++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Satır {$row_num}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_rows' => $row_num - 1,
        ];
    }

    /**
     * Export WooCommerce products to CSV
     */
    public function export_to_csv(array $options = []): string {
        $products = wc_get_products([
            'status' => $options['status'] ?? 'publish',
            'limit' => $options['limit'] ?? 1000,
        ]);

        $upload_dir = wp_upload_dir();
        $filename = 'isarud-export-' . date('Y-m-d-His') . '.csv';
        $filepath = $upload_dir['basedir'] . '/' . $filename;

        $handle = fopen($filepath, 'w');
        // BOM for Excel UTF-8
        fwrite($handle, "\xEF\xBB\xBF");

        // Header
        fputcsv($handle, ['sku', 'barcode', 'title', 'price', 'stock', 'category', 'description', 'status']);

        foreach ($products as $product) {
            $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
            fputcsv($handle, [
                $product->get_sku(),
                get_post_meta($product->get_id(), '_isarud_barcode', true),
                $product->get_name(),
                $product->get_price(),
                $product->get_stock_quantity() ?? 0,
                implode(', ', $categories),
                wp_trim_words(wp_strip_all_tags($product->get_description()), 30, '...'),
                $product->get_status(),
            ]);
        }

        fclose($handle);
        return $upload_dir['baseurl'] . '/' . $filename;
    }

    /**
     * AJAX: CSV Import
     */
    public function ajax_csv_import(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        if (empty($_FILES['csv_file'])) wp_send_json_error('Dosya yüklenmedi');

        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) wp_send_json_error('Yükleme hatası: ' . $file['error']);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt', 'tsv'])) wp_send_json_error('Sadece CSV/TSV dosyaları desteklenir');

        $result = $this->import_from_csv($file['tmp_name'], [
            'delimiter' => $ext === 'tsv' ? "\t" : ',',
            'update_existing' => ($_POST['update_existing'] ?? '0') === '1',
            'status' => sanitize_text_field($_POST['product_status'] ?? 'draft'),
        ]);

        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    /**
     * AJAX: CSV Export
     */
    public function ajax_csv_export(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $url = $this->export_to_csv();
        wp_send_json_success(['url' => $url]);
    }

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
}