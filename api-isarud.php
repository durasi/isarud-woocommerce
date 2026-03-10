<?php
/**
 * Plugin Name: API Isarud Trade Compliance
 * Plugin URI: https://isarud.com/integrations
 * Description: Sanctions screening + marketplace stock sync (Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy). Auto-sync, price margins, bulk operations.
 * Version: 2.2.0
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * Author: Isarud
 * Author URI: https://isarud.com
 * License: GPL v2 or later
 * Text Domain: api-isarud
 */
if (!defined('ABSPATH')) exit;

define('ISARUD_VERSION', '2.2.0');
define('ISARUD_DIR', plugin_dir_path(__FILE__));
define('ISARUD_URL', plugin_dir_url(__FILE__));

// Load translations
add_action('init', function() {
    load_plugin_textdomain('api-isarud', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// ─── ACTIVATION ─────────────────────────────────
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$wpdb->prefix}isarud_credentials (
        id int(11) NOT NULL AUTO_INCREMENT,
        marketplace varchar(50) NOT NULL,
        credentials text NOT NULL,
        is_active tinyint(1) DEFAULT 1,
        price_margin decimal(5,2) DEFAULT 0,
        price_margin_type enum('percent','fixed') DEFAULT 'percent',
        auto_sync tinyint(1) DEFAULT 0,
        sync_interval varchar(20) DEFAULT 'daily',
        last_test datetime DEFAULT NULL,
        test_status varchar(20) DEFAULT NULL,
        last_sync datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY marketplace (marketplace)
    ) {$charset};");

    dbDelta("CREATE TABLE {$wpdb->prefix}isarud_sync_log (
        id int(11) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        marketplace varchar(50) NOT NULL,
        action varchar(50) NOT NULL,
        status varchar(20) NOT NULL,
        message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_marketplace (product_id, marketplace)
    ) {$charset};");

    dbDelta("CREATE TABLE {$wpdb->prefix}isarud_screening_log (
        id int(11) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        names_screened text,
        has_match tinyint(1) DEFAULT 0,
        results text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id)
    ) {$charset};");

    dbDelta("CREATE TABLE {$wpdb->prefix}isarud_dropship_suppliers (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255),
        api_url varchar(500),
        api_key varchar(255),
        auto_forward tinyint(1) DEFAULT 0,
        commission_rate decimal(5,2) DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset};");

    dbDelta("CREATE TABLE {$wpdb->prefix}isarud_affiliates (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        code varchar(50) NOT NULL,
        commission_rate decimal(5,2) DEFAULT 10,
        total_sales decimal(14,2) DEFAULT 0,
        total_commission decimal(14,2) DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY code (code)
    ) {$charset};");

    dbDelta("CREATE TABLE {$wpdb->prefix}isarud_affiliate_log (
        id int(11) NOT NULL AUTO_INCREMENT,
        affiliate_id int(11) NOT NULL,
        order_id bigint(20) NOT NULL,
        order_total decimal(14,2) DEFAULT 0,
        commission decimal(14,2) DEFAULT 0,
        status enum('pending','approved','paid') DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY affiliate_id (affiliate_id)
    ) {$charset};");

    // Schedule cron
    if (!wp_next_scheduled('isarud_auto_sync_hook')) {
        wp_schedule_event(time(), 'hourly', 'isarud_auto_sync_hook');
    }
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('isarud_auto_sync_hook');
});

// ─── CRON INTERVALS ─────────────────────────────
add_filter('cron_schedules', function($schedules) {
    $schedules['every_15min'] = ['interval' => 900, 'display' => 'Every 15 Minutes'];
    $schedules['every_6hours'] = ['interval' => 21600, 'display' => 'Every 6 Hours'];
    return $schedules;
});

// ─── AUTO SYNC CRON HANDLER ─────────────────────
add_action('isarud_auto_sync_hook', function() {
    global $wpdb;
    $mps = $wpdb->get_results("SELECT marketplace, sync_interval, last_sync FROM {$wpdb->prefix}isarud_credentials WHERE auto_sync=1 AND is_active=1");
    if (!$mps || !class_exists('WooCommerce')) return;

    foreach ($mps as $mp) {
        $interval_seconds = match($mp->sync_interval) {
            '15min' => 900, 'hourly' => 3600, '6hours' => 21600, 'daily' => 86400, default => 86400,
        };
        if ($mp->last_sync && (time() - strtotime($mp->last_sync)) < $interval_seconds) continue;

        // Sync all published products with stock management
        $products = wc_get_products(['status' => 'publish', 'manage_stock' => true, 'limit' => 100]);
        $plugin = Isarud_Plugin::instance();
        foreach ($products as $product) {
            $plugin->sync_stock_public($product, $mp->marketplace);
        }
        $wpdb->update($wpdb->prefix . 'isarud_credentials', ['last_sync' => current_time('mysql')], ['marketplace' => $mp->marketplace]);
    }
});

// ─── MAIN PLUGIN CLASS ──────────────────────────
add_action('plugins_loaded', function() {
    Isarud_Plugin::instance();
});

class Isarud_Plugin {
    private static $inst = null;
    public static function instance() { if (!self::$inst) self::$inst = new self(); return self::$inst; }

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

        // AJAX
        add_action('wp_ajax_isarud_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_isarud_sync_product', [$this, 'ajax_sync_product']);
        add_action('wp_ajax_isarud_screen_order', [$this, 'ajax_screen_order']);
        add_action('wp_ajax_isarud_bulk_sync', [$this, 'ajax_bulk_sync']);
        add_action('wp_ajax_isarud_save_margin', [$this, 'ajax_save_margin']);
        add_action('wp_ajax_isarud_save_auto_sync', [$this, 'ajax_save_auto_sync']);
        add_action('wp_ajax_isarud_save_supplier', [$this, 'ajax_save_supplier']);
        add_action('wp_ajax_isarud_save_affiliate', [$this, 'ajax_save_affiliate']);

        // WooCommerce hooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_new_order', [$this, 'auto_screen_order'], 10, 1);
            add_action('woocommerce_new_order', [$this, 'track_affiliate'], 20, 1);
            add_action('woocommerce_new_order', [$this, 'forward_to_supplier'], 30, 1);
            add_action('woocommerce_product_options_general_product_data', [$this, 'product_fields']);
            add_action('woocommerce_process_product_meta', [$this, 'save_product_fields']);
            add_action('add_meta_boxes', [$this, 'order_meta_box']);
        }

        // Affiliate cookie
        add_action('init', [$this, 'track_affiliate_click']);
    }

    // ─── ADMIN MENU ─────────────────────────────
    public function admin_menu() {
        add_menu_page('Isarud', 'Isarud', 'manage_options', 'isarud', [$this, 'page_dashboard'], 'dashicons-shield', 56);
        add_submenu_page('isarud', 'Dashboard', 'Dashboard', 'manage_options', 'isarud', [$this, 'page_dashboard']);
        add_submenu_page('isarud', 'Sanctions', __('Yaptırım Taraması', 'api-isarud'), 'manage_options', 'isarud-sanctions', [$this, 'page_sanctions']);
        add_submenu_page('isarud', 'Marketplaces', __('Pazar Yeri API', 'api-isarud'), 'manage_options', 'isarud-marketplaces', [$this, 'page_marketplaces']);
        add_submenu_page('isarud', 'Bulk Sync', __('Toplu Senkronizasyon', 'api-isarud'), 'manage_options', 'isarud-bulk', [$this, 'page_bulk_sync']);
        add_submenu_page('isarud', 'Dropshipping', __('Dropshipping', 'api-isarud'), 'manage_options', 'isarud-dropship', [$this, 'page_dropshipping']);
        add_submenu_page('isarud', 'Affiliates', __('Affiliate', 'api-isarud'), 'manage_options', 'isarud-affiliates', [$this, 'page_affiliates']);
        add_submenu_page('isarud', 'Sync Log', __('Günlük', 'api-isarud'), 'manage_options', 'isarud-log', [$this, 'page_log']);
    }

    public function register_settings() {
        register_setting('isarud_general', 'isarud_api_key');
        register_setting('isarud_general', 'isarud_auto_screen');
        register_setting('isarud_general', 'isarud_block_match');
        register_setting('isarud_general', 'isarud_alert_email');
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'isarud') === false) return;
        wp_enqueue_style('isarud-admin', ISARUD_URL . 'assets/css/admin.css', [], ISARUD_VERSION);
        wp_enqueue_script('isarud-admin', ISARUD_URL . 'assets/js/admin.js', ['jquery'], ISARUD_VERSION, true);
        wp_localize_script('isarud-admin', 'isarud', ['ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('isarud_nonce')]);
    }

    // ─── DASHBOARD ──────────────────────────────
    public function page_dashboard() {
        global $wpdb;
        $screenings = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}isarud_screening_log");
        $matches = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}isarud_screening_log WHERE has_match=1");
        $syncs = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}isarud_sync_log WHERE status='success'");
        $errors = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}isarud_sync_log WHERE status='error'");
        $creds = $wpdb->get_results("SELECT marketplace, is_active, last_test, test_status, auto_sync, sync_interval, last_sync, price_margin, price_margin_type FROM {$wpdb->prefix}isarud_credentials");
        ?>
        <div class="wrap">
            <h1>Isarud Trade Compliance <small style="color:#999">v<?php echo ISARUD_VERSION; ?></small></h1>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin:20px 0">
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;text-align:center"><div style="font-size:28px;font-weight:bold;color:#2271b1"><?php echo $screenings; ?></div><div style="color:#999;font-size:12px"><?php _e('Taranan Siparişler', 'api-isarud'); ?></div></div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;text-align:center"><div style="font-size:28px;font-weight:bold;color:<?php echo $matches > 0 ? '#d63638' : '#00a32a'; ?>"><?php echo $matches; ?></div><div style="color:#999;font-size:12px"><?php _e('Eşleşmeler', 'api-isarud'); ?></div></div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;text-align:center"><div style="font-size:28px;font-weight:bold;color:#00a32a"><?php echo $syncs; ?></div><div style="color:#999;font-size:12px"><?php _e('Başarılı Sync', 'api-isarud'); ?></div></div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;text-align:center"><div style="font-size:28px;font-weight:bold;color:<?php echo $errors > 0 ? '#dba617' : '#999'; ?>"><?php echo $errors; ?></div><div style="color:#999;font-size:12px"><?php _e('Sync Hataları', 'api-isarud'); ?></div></div>
            </div>
            <h2><?php _e('Bağlı Pazar Yerleri', 'api-isarud'); ?></h2>
            <table class="wp-list-table widefat fixed striped"><thead><tr><th><?php _e('Pazar Yeri', 'api-isarud'); ?></th><th><?php _e('Durum', 'api-isarud'); ?></th><th><?php _e('Oto-Sync', 'api-isarud'); ?></th><th><?php _e('Fiyat Margin', 'api-isarud'); ?></th><th><?php _e('Son Sync', 'api-isarud'); ?></th><th>Test</th></tr></thead><tbody>
            <?php foreach ($creds as $c): ?>
            <tr>
                <td><strong><?php echo esc_html(ucfirst($c->marketplace)); ?></strong></td>
                <td><?php echo $c->is_active ? '<span style="color:#00a32a">● Aktif</span>' : '<span style="color:#999">○ Pasif</span>'; ?></td>
                <td><?php echo $c->auto_sync ? '<span style="color:#2271b1">⏱ ' . esc_html($c->sync_interval) . '</span>' : '<span style="color:#999">—</span>'; ?></td>
                <td><?php echo $c->price_margin != 0 ? ($c->price_margin_type === 'percent' ? '%' . $c->price_margin : '$' . $c->price_margin) : '—'; ?></td>
                <td><?php echo $c->last_sync ? esc_html(human_time_diff(strtotime($c->last_sync))) . ' önce' : '—'; ?></td>
                <td><?php echo $c->test_status === 'success' ? '<span style="color:#00a32a">✓</span>' : ($c->test_status === 'error' ? '<span style="color:#d63638">✗</span>' : '—'); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($creds)): ?><tr><td colspan="6" style="text-align:center;color:#999"><?php _e('Yapılandırılmış pazar yeri yok.', 'api-isarud'); ?> <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>"><?php _e('Ekle', 'api-isarud'); ?> →</a></td></tr><?php endif; ?>
            </tbody></table>
        </div>
        <?php
    }

    // ─── SANCTIONS PAGE ─────────────────────────
    public function page_sanctions() {
        if (isset($_POST['isarud_save_sanctions']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_sanctions')) {
            update_option('isarud_api_key', sanitize_text_field($_POST['isarud_api_key'] ?? ''));
            update_option('isarud_auto_screen', sanitize_text_field($_POST['isarud_auto_screen'] ?? 'yes'));
            update_option('isarud_block_match', sanitize_text_field($_POST['isarud_block_match'] ?? 'no'));
            update_option('isarud_alert_email', sanitize_email($_POST['isarud_alert_email'] ?? get_option('admin_email')));
            echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'api-isarud') . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Yaptırım Taraması', 'api-isarud'); ?></h1>
            <form method="post"><?php wp_nonce_field('isarud_sanctions'); ?>
                <table class="form-table">
                    <tr><th>Isarud API Key</th><td><input type="password" name="isarud_api_key" value="<?php echo esc_attr(get_option('isarud_api_key')); ?>" class="regular-text"><p class="description"><a href="https://isarud.com/account/api-keys" target="_blank"><?php _e('isarud.com\'dan API anahtarı alın', 'api-isarud'); ?> →</a></p></td></tr>
                    <tr><th><?php _e('Otomatik tarama', 'api-isarud'); ?></th><td><select name="isarud_auto_screen"><option value="yes" <?php selected(get_option('isarud_auto_screen','yes'),'yes'); ?>><?php _e('Evet', 'api-isarud'); ?></option><option value="no" <?php selected(get_option('isarud_auto_screen','yes'),'no'); ?>><?php _e('Hayır', 'api-isarud'); ?></option></select></td></tr>
                    <tr><th><?php _e('Eşleşmede beklet', 'api-isarud'); ?></th><td><select name="isarud_block_match"><option value="no" <?php selected(get_option('isarud_block_match','no'),'no'); ?>><?php _e('Hayır — sadece işaretle', 'api-isarud'); ?></option><option value="yes" <?php selected(get_option('isarud_block_match','no'),'yes'); ?>><?php _e('Evet — beklemeye al', 'api-isarud'); ?></option></select></td></tr>
                    <tr><th><?php _e('Uyarı e-postası', 'api-isarud'); ?></th><td><input type="email" name="isarud_alert_email" value="<?php echo esc_attr(get_option('isarud_alert_email', get_option('admin_email'))); ?>" class="regular-text"></td></tr>
                </table>
                <p class="submit"><input type="submit" name="isarud_save_sanctions" class="button-primary" value="<?php _e('Kaydet', 'api-isarud'); ?>"></p>
            </form>
        </div>
        <?php
    }

    // ─── MARKETPLACES PAGE (with margin + auto-sync) ────
    public function page_marketplaces() {
        global $wpdb;
        $table = $wpdb->prefix . 'isarud_credentials';

        if (isset($_POST['isarud_save_marketplace']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_mp')) {
            $mp = sanitize_text_field($_POST['marketplace']);
            $creds = [];
            foreach ($_POST['cred'] ?? [] as $k => $v) $creds[sanitize_text_field($k)] = sanitize_text_field($v);
            $margin = floatval($_POST['price_margin'] ?? 0);
            $margin_type = in_array($_POST['price_margin_type'] ?? '', ['percent','fixed']) ? $_POST['price_margin_type'] : 'percent';
            $auto_sync = intval($_POST['auto_sync'] ?? 0);
            $sync_interval = sanitize_text_field($_POST['sync_interval'] ?? 'daily');

            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE marketplace=%s", $mp));
            $data = ['credentials' => wp_json_encode($creds), 'is_active' => 1, 'price_margin' => $margin, 'price_margin_type' => $margin_type, 'auto_sync' => $auto_sync, 'sync_interval' => $sync_interval];
            if ($exists) { $wpdb->update($table, $data, ['marketplace' => $mp]); }
            else { $data['marketplace'] = $mp; $wpdb->insert($table, $data); }
            echo '<div class="notice notice-success"><p>' . esc_html(ucfirst($mp)) . ' ' . __('kaydedildi.', 'api-isarud') . '</p></div>';
        }

        $saved = [];
        foreach ($wpdb->get_results("SELECT * FROM {$table}") as $r) $saved[$r->marketplace] = $r;
        $marketplaces = $this->get_marketplace_config();
        ?>
        <div class="wrap">
            <h1><?php _e('Pazar Yeri API Ayarları', 'api-isarud'); ?></h1>
            <?php foreach ($marketplaces as $key => $mp): $row = $saved[$key] ?? null; $data = $row ? json_decode($row->credentials, true) : []; ?>
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0">
                <h2 style="margin-top:0"><?php echo esc_html($mp['name']); ?>
                    <?php if ($row && $row->test_status === 'success'): ?><span style="color:#00a32a;font-size:14px">● <?php _e('Bağlandı', 'api-isarud'); ?></span><?php endif; ?>
                </h2>
                <?php if (!empty($mp['docs'])): ?><p style="color:#999;font-size:12px"><?php echo esc_html($mp['docs']); ?></p><?php endif; ?>
                <form method="post"><?php wp_nonce_field('isarud_mp'); ?>
                    <input type="hidden" name="marketplace" value="<?php echo esc_attr($key); ?>">
                    <table class="form-table" style="margin:0">
                        <?php foreach ($mp['fields'] as $fk => $field): ?>
                        <tr><th style="width:150px"><?php echo esc_html($field['label']); ?></th><td><input type="<?php echo $field['type'] === 'password' ? 'password' : 'text'; ?>" name="cred[<?php echo esc_attr($fk); ?>]" value="<?php echo esc_attr($data[$fk] ?? ''); ?>" class="regular-text"></td></tr>
                        <?php endforeach; ?>
                        <tr><th><?php _e('Fiyat Margin', 'api-isarud'); ?></th><td>
                            <input type="number" step="0.01" name="price_margin" value="<?php echo esc_attr($row->price_margin ?? 0); ?>" style="width:80px">
                            <select name="price_margin_type"><option value="percent" <?php selected($row->price_margin_type ?? 'percent', 'percent'); ?>>%</option><option value="fixed" <?php selected($row->price_margin_type ?? '', 'fixed'); ?>>₺ / $</option></select>
                            <p class="description"><?php _e('Pazar yerine gönderirken fiyata eklenecek tutar. Örn: %15 = WooCommerce fiyatı × 1.15', 'api-isarud'); ?></p>
                        </td></tr>
                        <tr><th><?php _e('Oto-Sync', 'api-isarud'); ?></th><td>
                            <select name="auto_sync"><option value="0" <?php selected($row->auto_sync ?? 0, 0); ?>><?php _e('Kapalı', 'api-isarud'); ?></option><option value="1" <?php selected($row->auto_sync ?? 0, 1); ?>><?php _e('Açık', 'api-isarud'); ?></option></select>
                            <select name="sync_interval"><option value="15min" <?php selected($row->sync_interval ?? 'daily', '15min'); ?>>15 dk</option><option value="hourly" <?php selected($row->sync_interval ?? 'daily', 'hourly'); ?>>1 saat</option><option value="6hours" <?php selected($row->sync_interval ?? 'daily', '6hours'); ?>>6 saat</option><option value="daily" <?php selected($row->sync_interval ?? 'daily', 'daily'); ?>>Günlük</option></select>
                        </td></tr>
                    </table>
                    <p><input type="submit" name="isarud_save_marketplace" class="button-primary" value="<?php _e('Kaydet', 'api-isarud'); ?>">
                    <button type="button" class="button isarud-test-btn" data-marketplace="<?php echo esc_attr($key); ?>"><?php _e('Bağlantıyı Test Et', 'api-isarud'); ?></button>
                    <span class="isarud-test-result" data-marketplace="<?php echo esc_attr($key); ?>"></span></p>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    // ─── BULK SYNC PAGE ─────────────────────────
    public function page_bulk_sync() {
        global $wpdb;
        $mps = $wpdb->get_results("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active=1");
        ?>
        <div class="wrap">
            <h1><?php _e('Toplu Senkronizasyon', 'api-isarud'); ?></h1>
            <p><?php _e('Tüm WooCommerce ürünlerini seçili pazar yerine senkronize edin.', 'api-isarud'); ?></p>
            <?php if (empty($mps)): ?>
                <p style="color:#999"><?php _e('Aktif pazar yeri yok.', 'api-isarud'); ?> <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>"><?php _e('Ekle', 'api-isarud'); ?> →</a></p>
            <?php else: ?>
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;max-width:500px">
                    <select id="isarud-bulk-mp" class="regular-text">
                        <?php foreach ($mps as $m): ?><option value="<?php echo esc_attr($m->marketplace); ?>"><?php echo esc_html(ucfirst($m->marketplace)); ?></option><?php endforeach; ?>
                    </select>
                    <br><br>
                    <button type="button" id="isarud-bulk-start" class="button-primary"><?php _e('Tümünü Senkronize Et', 'api-isarud'); ?></button>
                    <div id="isarud-bulk-progress" style="margin-top:15px;display:none">
                        <div style="background:#f0f0f0;border-radius:4px;height:20px;overflow:hidden"><div id="isarud-bulk-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s"></div></div>
                        <p id="isarud-bulk-status" style="font-size:12px;color:#999;margin-top:5px"></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ─── DROPSHIPPING PAGE ──────────────────────
    public function page_dropshipping() {
        global $wpdb;
        if (isset($_POST['isarud_save_supplier']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_ds')) {
            $wpdb->insert($wpdb->prefix . 'isarud_dropship_suppliers', [
                'name' => sanitize_text_field($_POST['name']),
                'email' => sanitize_email($_POST['email']),
                'api_url' => esc_url_raw($_POST['api_url'] ?? ''),
                'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
                'auto_forward' => intval($_POST['auto_forward'] ?? 0),
                'commission_rate' => floatval($_POST['commission_rate'] ?? 0),
            ]);
            echo '<div class="notice notice-success"><p>' . __('Tedarikçi eklendi.', 'api-isarud') . '</p></div>';
        }
        $suppliers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}isarud_dropship_suppliers ORDER BY id DESC");
        ?>
        <div class="wrap">
            <h1><?php _e('Dropshipping Tedarikçileri', 'api-isarud'); ?></h1>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div>
                    <h2><?php _e('Tedarikçiler', 'api-isarud'); ?></h2>
                    <table class="wp-list-table widefat fixed striped"><thead><tr><th><?php _e('Ad', 'api-isarud'); ?></th><th>E-posta</th><th><?php _e('Oto-İlet', 'api-isarud'); ?></th><th><?php _e('Komisyon', 'api-isarud'); ?></th></tr></thead><tbody>
                    <?php foreach ($suppliers as $s): ?>
                    <tr><td><?php echo esc_html($s->name); ?></td><td><?php echo esc_html($s->email); ?></td><td><?php echo $s->auto_forward ? '✓' : '—'; ?></td><td>%<?php echo esc_html($s->commission_rate); ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($suppliers)): ?><tr><td colspan="4" style="text-align:center;color:#999"><?php _e('Henüz tedarikçi yok.', 'api-isarud'); ?></td></tr><?php endif; ?>
                    </tbody></table>
                </div>
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px">
                    <h2><?php _e('Yeni Tedarikçi Ekle', 'api-isarud'); ?></h2>
                    <form method="post"><?php wp_nonce_field('isarud_ds'); ?>
                        <p><label><?php _e('Tedarikçi Adı', 'api-isarud'); ?></label><br><input type="text" name="name" class="regular-text" required></p>
                        <p><label>E-posta</label><br><input type="email" name="email" class="regular-text"></p>
                        <p><label>API URL (<?php _e('opsiyonel', 'api-isarud'); ?>)</label><br><input type="url" name="api_url" class="regular-text"></p>
                        <p><label>API Key (<?php _e('opsiyonel', 'api-isarud'); ?>)</label><br><input type="text" name="api_key" class="regular-text"></p>
                        <p><label><?php _e('Komisyon Oranı', 'api-isarud'); ?> (%)</label><br><input type="number" step="0.01" name="commission_rate" value="0" style="width:80px"></p>
                        <p><label><input type="checkbox" name="auto_forward" value="1"> <?php _e('Siparişleri otomatik ilet', 'api-isarud'); ?></label></p>
                        <p><input type="submit" name="isarud_save_supplier" class="button-primary" value="<?php _e('Tedarikçi Ekle', 'api-isarud'); ?>"></p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── AFFILIATES PAGE ────────────────────────
    public function page_affiliates() {
        global $wpdb;
        if (isset($_POST['isarud_save_aff']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_aff')) {
            $code = sanitize_text_field($_POST['code'] ?: wp_generate_password(8, false));
            $wpdb->insert($wpdb->prefix . 'isarud_affiliates', [
                'name' => sanitize_text_field($_POST['name']),
                'email' => sanitize_email($_POST['email']),
                'code' => $code,
                'commission_rate' => floatval($_POST['commission_rate'] ?? 10),
            ]);
            echo '<div class="notice notice-success"><p>' . __('Affiliate eklendi. Kod:', 'api-isarud') . ' <strong>' . esc_html($code) . '</strong></p></div>';
        }
        $affiliates = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}isarud_affiliates ORDER BY total_commission DESC");
        ?>
        <div class="wrap">
            <h1><?php _e('Affiliate Yönetimi', 'api-isarud'); ?></h1>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
                <div>
                    <h2><?php _e('Affiliate\'ler', 'api-isarud'); ?></h2>
                    <table class="wp-list-table widefat fixed striped"><thead><tr><th><?php _e('Ad', 'api-isarud'); ?></th><th>Kod</th><th><?php _e('Komisyon', 'api-isarud'); ?></th><th><?php _e('Satışlar', 'api-isarud'); ?></th><th><?php _e('Kazanç', 'api-isarud'); ?></th><th>Link</th></tr></thead><tbody>
                    <?php foreach ($affiliates as $a): ?>
                    <tr><td><?php echo esc_html($a->name); ?></td><td><code><?php echo esc_html($a->code); ?></code></td><td>%<?php echo esc_html($a->commission_rate); ?></td><td>$<?php echo number_format($a->total_sales, 2); ?></td><td>$<?php echo number_format($a->total_commission, 2); ?></td><td><code style="font-size:10px"><?php echo esc_url(home_url('?ref=' . $a->code)); ?></code></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($affiliates)): ?><tr><td colspan="6" style="text-align:center;color:#999"><?php _e('Henüz affiliate yok.', 'api-isarud'); ?></td></tr><?php endif; ?>
                    </tbody></table>
                </div>
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px">
                    <h2><?php _e('Yeni Affiliate Ekle', 'api-isarud'); ?></h2>
                    <form method="post"><?php wp_nonce_field('isarud_aff'); ?>
                        <p><label><?php _e('Ad Soyad', 'api-isarud'); ?></label><br><input type="text" name="name" class="regular-text" required></p>
                        <p><label>E-posta</label><br><input type="email" name="email" class="regular-text" required></p>
                        <p><label><?php _e('Referans Kodu', 'api-isarud'); ?> (<?php _e('boş bırakılırsa otomatik', 'api-isarud'); ?>)</label><br><input type="text" name="code" class="regular-text" placeholder="auto"></p>
                        <p><label><?php _e('Komisyon Oranı', 'api-isarud'); ?> (%)</label><br><input type="number" step="0.01" name="commission_rate" value="10" style="width:80px"></p>
                        <p><input type="submit" name="isarud_save_aff" class="button-primary" value="<?php _e('Affiliate Ekle', 'api-isarud'); ?>"></p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── LOG PAGE ───────────────────────────────
    public function page_log() {
        global $wpdb;
        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}isarud_sync_log ORDER BY created_at DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1><?php _e('Senkronizasyon Günlüğü', 'api-isarud'); ?></h1>
            <table class="wp-list-table widefat fixed striped"><thead><tr><th><?php _e('Tarih', 'api-isarud'); ?></th><th><?php _e('Ürün', 'api-isarud'); ?></th><th><?php _e('Pazar Yeri', 'api-isarud'); ?></th><th><?php _e('İşlem', 'api-isarud'); ?></th><th><?php _e('Durum', 'api-isarud'); ?></th><th><?php _e('Mesaj', 'api-isarud'); ?></th></tr></thead><tbody>
            <?php foreach ($logs as $l): ?>
            <tr><td><?php echo esc_html($l->created_at); ?></td><td>#<?php echo esc_html($l->product_id); ?></td><td><?php echo esc_html(ucfirst($l->marketplace)); ?></td><td><?php echo esc_html($l->action); ?></td><td style="color:<?php echo $l->status === 'success' ? '#00a32a' : '#d63638'; ?>"><?php echo esc_html($l->status); ?></td><td><?php echo esc_html(wp_trim_words($l->message, 15)); ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?><tr><td colspan="6" style="text-align:center;color:#999"><?php _e('Henüz etkinlik yok.', 'api-isarud'); ?></td></tr><?php endif; ?>
            </tbody></table>
        </div>
        <?php
    }

    // ─── MARKETPLACE CONFIG ─────────────────────
    private function get_marketplace_config(): array {
        return [
            'trendyol' => ['name' => 'Trendyol', 'docs' => 'Seller Panel → Entegrasyon → API Bilgileri', 'base_url' => 'https://api.trendyol.com/sapigw/', 'auth_type' => 'basic',
                'fields' => ['api_key' => ['label' => 'API Key', 'type' => 'text'], 'api_secret' => ['label' => 'API Secret', 'type' => 'password'], 'seller_id' => ['label' => 'Supplier ID', 'type' => 'text']]],
            'hepsiburada' => ['name' => 'Hepsiburada', 'docs' => 'Merchant Portal → Entegrasyon → API Bilgileri', 'base_url' => 'https://listing-external.hepsiburada.com/', 'auth_type' => 'basic',
                'fields' => ['username' => ['label' => 'Username', 'type' => 'text'], 'password' => ['label' => 'Password', 'type' => 'password'], 'merchant_id' => ['label' => 'Merchant ID', 'type' => 'text']]],
            'n11' => ['name' => 'N11', 'docs' => 'Mağaza Panel → API Ayarları', 'base_url' => 'https://api.n11.com/ws/', 'auth_type' => 'soap',
                'fields' => ['api_key' => ['label' => 'API Key', 'type' => 'text'], 'api_secret' => ['label' => 'API Secret', 'type' => 'password']]],
            'amazon' => ['name' => 'Amazon SP-API', 'docs' => 'Seller Central → Apps → Develop Apps', 'base_url' => 'https://sellingpartnerapi-eu.amazon.com/', 'auth_type' => 'sp-api',
                'fields' => ['client_id' => ['label' => 'Client ID', 'type' => 'text'], 'client_secret' => ['label' => 'Client Secret', 'type' => 'password'], 'refresh_token' => ['label' => 'Refresh Token', 'type' => 'password'], 'marketplace_id' => ['label' => 'Marketplace ID', 'type' => 'text']]],
            'pazarama' => ['name' => 'Pazarama', 'docs' => 'Satıcı Panel → Entegrasyon Bilgileri', 'base_url' => 'https://api.pazarama.com/', 'auth_type' => 'bearer',
                'fields' => ['api_key' => ['label' => 'API Key', 'type' => 'text'], 'api_secret' => ['label' => 'API Secret', 'type' => 'password'], 'seller_id' => ['label' => 'Seller ID', 'type' => 'text']]],
            'etsy' => ['name' => 'Etsy', 'docs' => 'etsy.com/developers → Apps', 'base_url' => 'https://openapi.etsy.com/v3/', 'auth_type' => 'oauth2',
                'fields' => ['api_key' => ['label' => 'API Key', 'type' => 'text'], 'access_token' => ['label' => 'Access Token', 'type' => 'password'], 'shop_id' => ['label' => 'Shop ID', 'type' => 'text']]],
        ];
    }

    // ─── API HELPER ─────────────────────────────
    private function marketplace_request(string $mp, string $endpoint, string $method = 'GET', $data = null): array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT credentials FROM {$wpdb->prefix}isarud_credentials WHERE marketplace=%s AND is_active=1", $mp));
        if (!$row) return ['error' => $mp . ' credentials not found'];
        $creds = json_decode($row->credentials, true);
        $config = $this->get_marketplace_config()[$mp] ?? null;
        if (!$config) return ['error' => 'Unknown marketplace'];
        $url = $config['base_url'] . $endpoint;
        $headers = ['Content-Type' => 'application/json', 'User-Agent' => 'Isarud/' . ISARUD_VERSION];
        switch ($config['auth_type']) {
            case 'basic': $headers['Authorization'] = 'Basic ' . base64_encode(($creds['api_key'] ?? $creds['username'] ?? '') . ':' . ($creds['api_secret'] ?? $creds['password'] ?? '')); break;
            case 'bearer': $headers['Authorization'] = 'Bearer ' . ($creds['access_token'] ?? $creds['api_key'] ?? ''); break;
            case 'oauth2': $headers['x-api-key'] = $creds['api_key'] ?? ''; $headers['Authorization'] = 'Bearer ' . ($creds['access_token'] ?? ''); break;
            case 'sp-api': $token = $this->amazon_token($creds); if (isset($token['error'])) return $token; $headers['x-amz-access-token'] = $token['access_token']; break;
        }
        $args = ['headers' => $headers, 'method' => $method, 'timeout' => 30, 'sslverify' => true];
        if ($data && in_array($method, ['POST', 'PUT'])) $args['body'] = wp_json_encode($data);
        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) return ['error' => $response->get_error_message()];
        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 400) return ['error' => "HTTP {$code}: " . wp_remote_retrieve_body($response)];
        return array_merge(json_decode(wp_remote_retrieve_body($response), true) ?: [], ['http_code' => $code]);
    }

    private function amazon_token(array $c): array {
        $r = wp_remote_post('https://api.amazon.com/auth/o2/token', ['body' => ['grant_type' => 'refresh_token', 'refresh_token' => $c['refresh_token'] ?? '', 'client_id' => $c['client_id'] ?? '', 'client_secret' => $c['client_secret'] ?? '']]);
        if (is_wp_error($r)) return ['error' => $r->get_error_message()];
        $d = json_decode(wp_remote_retrieve_body($r), true);
        return empty($d['access_token']) ? ['error' => 'Amazon auth failed'] : $d;
    }

    private function get_cred(string $mp, string $key): string {
        global $wpdb;
        $r = $wpdb->get_var($wpdb->prepare("SELECT credentials FROM {$wpdb->prefix}isarud_credentials WHERE marketplace=%s", $mp));
        return $r ? (json_decode($r, true)[$key] ?? '') : '';
    }

    private function get_margin(string $mp): array {
        global $wpdb;
        $r = $wpdb->get_row($wpdb->prepare("SELECT price_margin, price_margin_type FROM {$wpdb->prefix}isarud_credentials WHERE marketplace=%s", $mp));
        return ['amount' => floatval($r->price_margin ?? 0), 'type' => $r->price_margin_type ?? 'percent'];
    }

    private function apply_margin(float $price, string $mp): float {
        $m = $this->get_margin($mp);
        if ($m['amount'] == 0) return $price;
        return $m['type'] === 'percent' ? round($price * (1 + $m['amount'] / 100), 2) : round($price + $m['amount'], 2);
    }

    // ─── AJAX HANDLERS ──────────────────────────
    public function ajax_test_connection() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $result = match($mp) {
            'trendyol' => $this->marketplace_request('trendyol', 'suppliers/' . $this->get_cred($mp, 'seller_id') . '/products?page=0&size=1'),
            'hepsiburada' => $this->marketplace_request('hepsiburada', 'listings/merchantid/' . $this->get_cred($mp, 'merchant_id') . '?limit=1'),
            'n11' => $this->test_n11(),
            'amazon' => $this->marketplace_request('amazon', 'sellers/v1/marketplaceParticipations'),
            'pazarama' => $this->marketplace_request('pazarama', 'product/products?page=0&size=1'),
            'etsy' => $this->marketplace_request('etsy', 'application/shops/' . $this->get_cred($mp, 'shop_id')),
            default => ['error' => 'Unknown'],
        };
        $ok = !isset($result['error']);
        $wpdb->update($wpdb->prefix . 'isarud_credentials', ['last_test' => current_time('mysql'), 'test_status' => $ok ? 'success' : 'error'], ['marketplace' => $mp]);
        $ok ? wp_send_json_success(['message' => ucfirst($mp) . ' bağlantı başarılı!']) : wp_send_json_error(['message' => $result['error']]);
    }

    private function test_n11(): array {
        global $wpdb;
        $c = json_decode($wpdb->get_var("SELECT credentials FROM {$wpdb->prefix}isarud_credentials WHERE marketplace='n11'"), true);
        if (!$c) return ['error' => 'N11 credentials not found'];
        $xml = '<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sch="http://www.n11.com/ws/schemas"><soapenv:Header><sch:Authentication><sch:appKey>' . esc_xml($c['api_key'] ?? '') . '</sch:appKey><sch:appSecret>' . esc_xml($c['api_secret'] ?? '') . '</sch:appSecret></sch:Authentication></soapenv:Header><soapenv:Body><sch:GetProductListRequest><sch:pagingData><sch:currentPage>0</sch:currentPage><sch:pageSize>1</sch:pageSize></sch:pagingData></sch:GetProductListRequest></soapenv:Body></soapenv:Envelope>';
        $r = wp_remote_post('https://api.n11.com/ws/ProductService/', ['headers' => ['Content-Type' => 'text/xml; charset=utf-8'], 'body' => $xml, 'timeout' => 30]);
        if (is_wp_error($r)) return ['error' => $r->get_error_message()];
        return ['success' => true];
    }

    // ─── STOCK SYNC (public for cron) ───────────
    public function sync_stock_public($product, string $mp): array { return $this->sync_stock($product, $mp); }

    private function sync_stock($product, string $mp): array {
        $sku = $product->get_sku();
        $stock = $product->get_stock_quantity() ?? 0;
        $price = $this->apply_margin((float)$product->get_price(), $mp);
        $barcode = get_post_meta($product->get_id(), '_isarud_barcode', true) ?: $sku;
        if (empty($barcode)) return ['error' => 'No barcode/SKU'];

        $result = match($mp) {
            'trendyol' => $this->marketplace_request('trendyol', 'suppliers/' . $this->get_cred('trendyol', 'seller_id') . '/products/price-and-inventory', 'PUT', ['items' => [['barcode' => $barcode, 'quantity' => $stock, 'salePrice' => $price, 'listPrice' => $price]]]),
            'hepsiburada' => $this->marketplace_request('hepsiburada', 'listings/merchantid/' . $this->get_cred('hepsiburada', 'merchant_id') . '/stock-uploads', 'POST', ['listings' => [['hepsiburadaSku' => $barcode, 'merchantSku' => $barcode, 'availableStock' => $stock, 'price' => $price]]]),
            'pazarama' => $this->marketplace_request('pazarama', 'product/products/price-and-inventory', 'PUT', ['items' => [['barcode' => $barcode, 'quantity' => $stock, 'salePrice' => $price]]]),
            default => ['error' => 'Not implemented for ' . $mp],
        };

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'isarud_sync_log', ['product_id' => $product->get_id(), 'marketplace' => $mp, 'action' => 'stock_price_update', 'status' => isset($result['error']) ? 'error' : 'success', 'message' => $result['error'] ?? "Stock: {$stock}, Price: {$price}", 'created_at' => current_time('mysql')]);
        return $result;
    }

    public function ajax_sync_product() {
        check_ajax_referer('isarud_nonce', 'nonce');
        $p = wc_get_product(intval($_POST['product_id'] ?? 0));
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        if (!$p || !$mp) wp_send_json_error('Invalid');
        $r = $this->sync_stock($p, $mp);
        isset($r['error']) ? wp_send_json_error($r['error']) : wp_send_json_success($r);
    }

    public function ajax_bulk_sync() {
        check_ajax_referer('isarud_nonce', 'nonce');
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $page = intval($_POST['page'] ?? 0);
        $products = wc_get_products(['status' => 'publish', 'manage_stock' => true, 'limit' => 10, 'offset' => $page * 10]);
        $total = wc_get_products(['status' => 'publish', 'manage_stock' => true, 'limit' => 1, 'return' => 'ids', 'paginate' => true])->total ?? 0;
        $ok = 0; $err = 0;
        foreach ($products as $p) { $r = $this->sync_stock($p, $mp); isset($r['error']) ? $err++ : $ok++; }
        wp_send_json_success(['ok' => $ok, 'err' => $err, 'total' => $total, 'done' => count($products) < 10]);
    }

    // ─── SANCTIONS SCREENING ────────────────────
    public function auto_screen_order(int $oid): void {
        if (get_option('isarud_auto_screen', 'yes') !== 'yes') return;
        $key = get_option('isarud_api_key'); if (!$key) return;
        $o = wc_get_order($oid); if (!$o) return;
        $names = array_filter([trim($o->get_billing_first_name() . ' ' . $o->get_billing_last_name()), $o->get_billing_company(), trim($o->get_shipping_first_name() . ' ' . $o->get_shipping_last_name())]);
        $names = array_unique(array_filter($names));
        $results = []; $match = false;
        foreach ($names as $n) {
            $r = wp_remote_post('https://isarud.com/api/v1/screen', ['timeout' => 15, 'headers' => ['Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json'], 'body' => wp_json_encode(['name' => $n])]);
            $d = is_wp_error($r) ? ['status' => 'error'] : (json_decode(wp_remote_retrieve_body($r), true) ?: ['status' => 'error']);
            $results[] = array_merge($d, ['query' => $n]);
            if (in_array($d['status'] ?? '', ['match', 'possible_match'])) $match = true;
        }
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'isarud_screening_log', ['order_id' => $oid, 'names_screened' => wp_json_encode($names), 'has_match' => $match ? 1 : 0, 'results' => wp_json_encode($results), 'created_at' => current_time('mysql')]);
        $o->add_order_note($match ? 'Isarud: YAPTIRIM EŞLEŞMESİ: ' . implode(', ', $names) : 'Isarud: Temiz — eşleşme yok');
        if ($match) {
            wp_mail(get_option('isarud_alert_email', get_option('admin_email')), '[Isarud] Eşleşme — Sipariş #' . $oid, 'Eşleşme: ' . implode(', ', $names));
            if (get_option('isarud_block_match', 'no') === 'yes') $o->update_status('on-hold', 'Isarud tarafından bekletildi');
        }
    }

    public function ajax_screen_order() {
        check_ajax_referer('isarud_nonce', 'nonce');
        $this->auto_screen_order(intval($_POST['order_id'] ?? 0));
        wp_send_json_success('Tarandı');
    }

    // ─── DROPSHIPPING AUTO-FORWARD ──────────────
    public function forward_to_supplier(int $oid): void {
        global $wpdb;
        $o = wc_get_order($oid); if (!$o) return;
        $suppliers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}isarud_dropship_suppliers WHERE auto_forward=1 AND is_active=1");
        foreach ($suppliers as $s) {
            if (!empty($s->api_url)) {
                wp_remote_post($s->api_url, ['timeout' => 15, 'headers' => ['Authorization' => 'Bearer ' . $s->api_key, 'Content-Type' => 'application/json'], 'body' => wp_json_encode(['order_id' => $oid, 'items' => $o->get_items(), 'shipping' => $o->get_address('shipping')])]);
            }
            if (!empty($s->email)) {
                wp_mail($s->email, 'Yeni Sipariş #' . $oid, 'Sipariş detayları: ' . $o->get_edit_order_url());
            }
            $o->add_order_note('Isarud: Tedarikçiye iletildi → ' . $s->name);
        }
    }

    // ─── AFFILIATE TRACKING ─────────────────────
    public function track_affiliate_click(): void {
        if (isset($_GET['ref'])) {
            $code = sanitize_text_field($_GET['ref']);
            setcookie('isarud_ref', $code, time() + (30 * 86400), '/');
        }
    }

    public function track_affiliate(int $oid): void {
        $code = $_COOKIE['isarud_ref'] ?? ''; if (!$code) return;
        global $wpdb;
        $aff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}isarud_affiliates WHERE code=%s AND is_active=1", $code));
        if (!$aff) return;
        $o = wc_get_order($oid); if (!$o) return;
        $total = (float)$o->get_total();
        $commission = round($total * $aff->commission_rate / 100, 2);
        $wpdb->insert($wpdb->prefix . 'isarud_affiliate_log', ['affiliate_id' => $aff->id, 'order_id' => $oid, 'order_total' => $total, 'commission' => $commission]);
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}isarud_affiliates SET total_sales=total_sales+%f, total_commission=total_commission+%f WHERE id=%d", $total, $commission, $aff->id));
        $o->add_order_note("Isarud Affiliate: {$aff->name} ({$aff->code}) — Komisyon: \${$commission}");
    }

    // ─── ORDER META BOX ─────────────────────────
    public function order_meta_box() {
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') ? wc_get_page_screen_id('shop-order') : 'shop_order';
        add_meta_box('isarud_compliance', 'Isarud', [$this, 'render_order_box'], $screen, 'side', 'high');
    }

    public function render_order_box($p) {
        $o = ($p instanceof \WP_Post) ? wc_get_order($p->ID) : $p; if (!$o) return;
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}isarud_screening_log WHERE order_id=%d ORDER BY id DESC LIMIT 1", $o->get_id()));
        if (!$log) { echo '<p style="color:#999">' . __('Taranmadı', 'api-isarud') . '</p><button type="button" class="button isarud-screen-order" data-order="' . $o->get_id() . '">' . __('Şimdi Tara', 'api-isarud') . '</button>'; return; }
        echo $log->has_match ? '<p style="color:#d63638;font-weight:bold">🔴 ' . __('Eşleşme Bulundu', 'api-isarud') . '</p>' : '<p style="color:#00a32a;font-weight:bold">🟢 ' . __('Temiz', 'api-isarud') . '</p>';
    }

    // ─── PRODUCT FIELDS ─────────────────────────
    public function product_fields() {
        echo '<div class="options_group" style="border-top:1px solid #eee;padding-top:10px"><p style="padding-left:12px;font-weight:bold;color:#2271b1">Isarud</p>';
        woocommerce_wp_text_input(['id' => '_isarud_barcode', 'label' => 'Barkod / EAN']);
        woocommerce_wp_text_input(['id' => '_isarud_n11_id', 'label' => 'N11 Ürün ID']);
        woocommerce_wp_text_input(['id' => '_isarud_etsy_listing_id', 'label' => 'Etsy Listing ID']);
        woocommerce_wp_text_input(['id' => '_isarud_supplier_id', 'label' => __('Tedarikçi ID', 'api-isarud')]);
        echo '</div>';
    }

    public function save_product_fields(int $id): void {
        foreach (['_isarud_barcode', '_isarud_n11_id', '_isarud_etsy_listing_id', '_isarud_supplier_id'] as $f) {
            if (isset($_POST[$f])) update_post_meta($id, $f, sanitize_text_field($_POST[$f]));
        }
    }
}

if (!function_exists('esc_xml')) { function esc_xml(string $s): string { return htmlspecialchars($s, ENT_XML1, 'UTF-8'); } }
