<?php
/**
 * Plugin Name: API Isarud Tüm Pazaryerleri Ticaret Entegrasyonu
 * Plugin URI: https://isarud.com/integrations
 * Description: Yaptırım tarama + Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy API entegrasyonu + sipariş yönetimi + iade + fatura + müşteri soruları + marka arama. %100 ücretsiz.
 * Version: 6.2.3
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * Author: Isarud
 * Author URI: https://isarud.com
 * License: GPL v2 or later
 * Text Domain: api-isarud
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) exit;

define('ISARUD_VERSION', '6.2.3');
define('ISARUD_DIR', plugin_dir_path(__FILE__));
define('ISARUD_URL', plugin_dir_url(__FILE__));

// Cloud Sync
require_once ISARUD_DIR . 'isarud-cloud-sync.php';
// Advanced Marketplace Modules
require_once ISARUD_DIR . 'includes/class-isarud-webhook.php';
require_once ISARUD_DIR . 'includes/class-isarud-order-import.php';
require_once ISARUD_DIR . 'includes/class-isarud-product-import.php';
require_once ISARUD_DIR . 'includes/class-isarud-variation-sync.php';
require_once ISARUD_DIR . 'includes/class-isarud-category-map.php';
require_once ISARUD_DIR . 'includes/class-isarud-product-export.php';
require_once ISARUD_DIR . 'includes/class-isarud-attribute-map.php';
require_once ISARUD_DIR . 'includes/class-isarud-excel-import.php';
require_once ISARUD_DIR . 'includes/class-isarud-order-management.php';
require_once ISARUD_DIR . 'includes/class-isarud-returns.php';
require_once ISARUD_DIR . 'includes/class-isarud-invoice.php';
require_once ISARUD_DIR . 'includes/class-isarud-customer-questions.php';
require_once ISARUD_DIR . 'includes/class-isarud-brand-lookup.php';
require_once ISARUD_DIR . 'includes/class-isarud-currency.php';
require_once ISARUD_DIR . 'includes/class-isarud-b2b.php';
require_once ISARUD_DIR . 'includes/class-isarud-segments.php';
require_once ISARUD_DIR . 'includes/class-isarud-cart-recovery.php';
require_once ISARUD_DIR . 'includes/class-isarud-popup.php';
require_once ISARUD_DIR . 'includes/class-isarud-email-marketing.php';
require_once ISARUD_DIR . 'includes/class-isarud-einvoice.php';
require_once ISARUD_DIR . 'includes/class-isarud-upsell.php';
require_once ISARUD_DIR . 'includes/class-isarud-welcome.php';


// HPOS (High-Performance Order Storage) compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_storage', __FILE__, true);
    }
});

// Load translations
add_action('init', function() {
    load_plugin_textdomain('api-isarud', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// ─── ACTIVATION ─────────────────────────────────
register_activation_hook(__FILE__, function() {
    global $wpdb;
    Isarud_Welcome::on_activate();
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
    wp_clear_scheduled_hook('isarud_cloud_sync_hook');
    wp_clear_scheduled_hook('isarud_order_import_cron');
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
            $plugin->sync_product_smart($product, $mp->marketplace);
        }
        $wpdb->update($wpdb->prefix . 'isarud_credentials', ['last_sync' => current_time('mysql')], ['marketplace' => $mp->marketplace]);
    }
});

// ─── MAIN PLUGIN CLASS ──────────────────────────
add_action('plugins_loaded', function() {
    Isarud_Plugin::instance();
    Isarud_Cloud_Sync::instance();
    Isarud_Webhook::instance();
    Isarud_Order_Import::instance();
    Isarud_Product_Import::instance();
    Isarud_Variation_Sync::instance();
    Isarud_Category_Map::instance();
    Isarud_Product_Export::instance();
    Isarud_Attribute_Map::instance();
    Isarud_Excel_Import::instance();
    Isarud_Order_Management::instance();
    Isarud_Returns::instance();
    Isarud_Invoice::instance();
    Isarud_Customer_Questions::instance();
    Isarud_Brand_Lookup::instance();
});

class Isarud_Plugin {
    private static $inst = null;
    public static function instance() { if (!self::$inst) self::$inst = new self(); return self::$inst; }

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);

        // WP Dashboard Widget
        add_action('wp_dashboard_setup', function() {
            wp_add_dashboard_widget('isarud_dashboard_widget', 'Isarud Trade Compliance', function() {
                global $wpdb;
                $t = $wpdb->prefix . 'isarud_';
                $since = date("Y-m-d H:i:s", strtotime("-24 hours"));
                $screenings = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}screening_log");
                $screen_24h = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}screening_log WHERE created_at >= %s", $since));
                $matches_24h = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}screening_log WHERE has_match=1 AND created_at >= %s", $since));
                $syncs_24h = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}sync_log WHERE status='success' AND created_at >= %s", $since));
                $errors_24h = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}sync_log WHERE status='error' AND created_at >= %s", $since));
                $active_mp = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}credentials WHERE is_active=1");
                ?>
                <div class="isd-widget-metrics">
                    <div class="isd-widget-metric">
                        <div class="val blue"><?php echo $screen_24h; ?></div>
                        <div class="lbl"><?php _e('Tarama (24s)', 'api-isarud'); ?></div>
                    </div>
                    <div class="isd-widget-metric">
                        <div class="val green"><?php echo $syncs_24h; ?></div>
                        <div class="lbl"><?php _e('Sync (24s)', 'api-isarud'); ?></div>
                    </div>
                    <div class="isd-widget-metric">
                        <div class="val <?php echo $errors_24h > 0 ? 'amber' : 'muted'; ?>"><?php echo $errors_24h; ?></div>
                        <div class="lbl"><?php _e('Hata (24s)', 'api-isarud'); ?></div>
                    </div>
                </div>
                <div>
                    <div class="isd-widget-row"><span style="color:#666"><?php _e('Toplam tarama', 'api-isarud'); ?></span><strong><?php echo number_format_i18n($screenings); ?></strong></div>
                    <?php if ($matches_24h > 0): ?><div class="isd-widget-row"><span style="color:#d63638"><?php _e('Eşleşme (24s)', 'api-isarud'); ?></span><strong style="color:#d63638"><?php echo $matches_24h; ?></strong></div><?php endif; ?>
                    <div class="isd-widget-row"><span style="color:#666"><?php _e('Aktif pazar yeri', 'api-isarud'); ?></span><strong><?php echo $active_mp; ?></strong></div>
                </div>
                <?php
                $recent = $wpdb->get_results($wpdb->prepare(
                    "(SELECT 'screening' as type, entity_name as detail, has_match as status_val, created_at FROM {$t}screening_log WHERE created_at >= %s ORDER BY created_at DESC LIMIT 5)
                     UNION ALL
                     (SELECT 'sync' as type, CONCAT(marketplace, ': ', COALESCE(message,'')) as detail, IF(status='success',1,0) as status_val, created_at FROM {$t}sync_log WHERE created_at >= %s ORDER BY created_at DESC LIMIT 5)
                     ORDER BY created_at DESC LIMIT 5", $since, $since
                ));
                if (!empty($recent)): ?>
                <div class="isd-widget-activity">
                    <div class="isd-widget-activity-title"><?php _e('Son Aktivite', 'api-isarud'); ?></div>
                    <?php foreach ($recent as $r): ?>
                    <div class="isd-widget-act-row">
                        <span><?php echo ($r->type === 'screening' ? '<svg style="width:12px;height:12px;vertical-align:-1px;margin-right:4px" viewBox="0 0 24 24" fill="none" stroke="#185fa5" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' : '<svg style="width:12px;height:12px;vertical-align:-1px;margin-right:4px" viewBox="0 0 24 24" fill="none" stroke="#00a32a" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>') . esc_html(mb_substr($r->detail, 0, 30)); ?></span>
                        <span style="color:#aaa"><?php echo human_time_diff(strtotime($r->created_at)) . ' ' . __('önce', 'api-isarud'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <p style="margin:12px 0 0;text-align:center">
                    <a href="<?php echo admin_url('admin.php?page=isarud'); ?>" class="button button-small"><?php _e('Dashboard', 'api-isarud'); ?></a>
                    <a href="<?php echo admin_url('admin.php?page=isarud-statistics'); ?>" class="button button-small"><?php _e('İstatistikler', 'api-isarud'); ?></a>
                </p>
                <?php
            });
        });
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
        add_action('wp_ajax_isarud_import_orders', [Isarud_Order_Import::instance(), 'ajax_import_orders']);
        add_action('wp_ajax_isarud_import_products', [Isarud_Product_Import::instance(), 'ajax_import_products']);
        add_action('wp_ajax_isarud_fetch_mp_products', [Isarud_Product_Import::instance(), 'ajax_fetch_products']);
        add_action('wp_ajax_isarud_sync_variations', [Isarud_Variation_Sync::instance(), 'ajax_sync_variations']);
        add_action('wp_ajax_isarud_save_category_map', [Isarud_Category_Map::instance(), 'ajax_save_map']);
        add_action('wp_ajax_isarud_fetch_mp_categories', [Isarud_Category_Map::instance(), 'ajax_fetch_categories']);
        add_action('wp_ajax_isarud_export_products', [Isarud_Product_Export::instance(), 'ajax_export_products']);
        add_action('wp_ajax_isarud_export_single', [Isarud_Product_Export::instance(), 'ajax_export_single']);
        add_action('wp_ajax_isarud_save_attribute_map', [Isarud_Attribute_Map::instance(), 'ajax_save_map']);
        add_action('wp_ajax_isarud_delete_attribute_map', [Isarud_Attribute_Map::instance(), 'ajax_delete_map']);
        add_action('wp_ajax_isarud_fetch_mp_attributes', [Isarud_Attribute_Map::instance(), 'ajax_fetch_attributes']);
        add_action('wp_ajax_isarud_csv_import', [Isarud_Excel_Import::instance(), 'ajax_csv_import']);
        add_action('wp_ajax_isarud_csv_export', [Isarud_Excel_Import::instance(), 'ajax_csv_export']);
        add_action('wp_ajax_isarud_cloud_connect', [$this, 'ajax_cloud_connect']);
        add_action('wp_ajax_isarud_cloud_sync_now', [$this, 'ajax_cloud_sync_now']);
        add_action('wp_ajax_isarud_cloud_disconnect', [$this, 'ajax_cloud_disconnect']);

        // WooCommerce hooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_new_order', [$this, 'auto_screen_order'], 10, 1);
            add_action('woocommerce_new_order', [$this, 'track_affiliate'], 20, 1);
            add_action('woocommerce_new_order', [$this, 'forward_to_supplier'], 30, 1);
            add_action('woocommerce_order_status_completed', [Isarud_Invoice::instance(), 'auto_send_invoice'], 10, 1);
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
        // WooCommerce-dependent menus
        if (class_exists('WooCommerce')) {
        add_submenu_page('isarud', 'Marketplaces', __('Pazar Yeri API', 'api-isarud'), 'manage_options', 'isarud-marketplaces', [$this, 'page_marketplaces']);
        add_submenu_page('isarud', 'Bulk Sync', __('Toplu Senkronizasyon', 'api-isarud'), 'manage_options', 'isarud-bulk', [$this, 'page_bulk_sync']);
        add_submenu_page('isarud', 'Dropshipping', __('Dropshipping', 'api-isarud'), 'manage_options', 'isarud-dropship', [$this, 'page_dropshipping']);
        add_submenu_page('isarud', 'Affiliates', __('Affiliate', 'api-isarud'), 'manage_options', 'isarud-affiliates', [$this, 'page_affiliates']);
        } // end WooCommerce menus
        add_submenu_page('isarud', 'Cloud Sync', __('Cloud Sync', 'api-isarud'), 'manage_options', 'isarud-cloud', [$this, 'page_cloud_sync']);
        add_submenu_page('isarud', 'İstatistikler', __('İstatistikler', 'api-isarud'), 'manage_options', 'isarud-statistics', [$this, 'page_statistics']);
        add_submenu_page('isarud', 'Webhooks', __('Webhooks', 'api-isarud'), 'manage_options', 'isarud-webhooks', [$this, 'page_webhooks']);
        add_submenu_page('isarud', 'Sync Log', __('Günlük', 'api-isarud'), 'manage_options', 'isarud-log', [$this, 'page_log']);
        add_submenu_page('isarud', 'E-ticaret Rehberi', __('E-ticaret Rehberi', 'api-isarud'), 'manage_options', 'isarud-ecosystem', [$this, 'page_ecosystem']);
        add_submenu_page('isarud', 'TCMB Doviz Kuru', __('TCMB Doviz Kuru', 'api-isarud'), 'manage_options', 'isarud-currency', [$this, 'page_currency']);
        add_submenu_page('isarud', 'B2B Toptan Satis', __('B2B Toptan Satis', 'api-isarud'), 'manage_options', 'isarud-b2b', [$this, 'page_b2b']);
        add_submenu_page('isarud', 'Musteri Segmentasyonu', __('Musteri Segmentasyonu', 'api-isarud'), 'manage_options', 'isarud-segments', [$this, 'page_segments']);
        add_submenu_page('isarud', 'Sepet Hatirlatma', __('Sepet Hatirlatma', 'api-isarud'), 'manage_options', 'isarud-cart-recovery', [$this, 'page_cart_recovery']);
        add_submenu_page('isarud', 'Popup Kampanyalari', __('Popup Kampanyalari', 'api-isarud'), 'manage_options', 'isarud-popup', [$this, 'page_popup']);
        add_submenu_page('isarud', 'E-posta Pazarlama', __('E-posta Pazarlama', 'api-isarud'), 'manage_options', 'isarud-email-marketing', [$this, 'page_email_marketing']);
        add_submenu_page('isarud', 'E-Fatura', __('E-Fatura', 'api-isarud'), 'manage_options', 'isarud-einvoice', [$this, 'page_einvoice']);
        add_submenu_page('isarud', 'Cross-sell & Upsell', __('Cross-sell & Upsell', 'api-isarud'), 'manage_options', 'isarud-upsell', [$this, 'page_upsell']);
        add_submenu_page('isarud', 'Hosgeldiniz', __('Hosgeldiniz', 'api-isarud'), 'manage_options', 'isarud-welcome', [$this, 'page_welcome']);
    }

    public function register_settings() {
        register_setting('isarud_general', 'isarud_api_key');
        register_setting('isarud_general', 'isarud_auto_screen');
        register_setting('isarud_general', 'isarud_block_match');
        register_setting('isarud_general', 'isarud_alert_email');
    }

    public function admin_scripts($hook) {
        if ($hook === 'index.php') {
            wp_enqueue_style('isarud-admin', ISARUD_URL . 'assets/css/admin.css', [], ISARUD_VERSION);
            return;
        }
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
        $has_woo = class_exists('WooCommerce');
        $has_api_key = !empty(get_option('isarud_api_key'));
        $cloud_connected = class_exists('Isarud_Cloud_Sync') && Isarud_Cloud_Sync::instance()->is_enabled();
        $has_creds = !empty($creds);
        ?>
        <?php include ISARUD_DIR . 'includes/dashboard-html.php'; ?>

            <div class="isd-mp-section">
                <h2><?php _e('Bağlı Pazaryerleri', 'api-isarud'); ?></h2>
                <table class="wp-list-table widefat fixed striped"><thead><tr><th><?php _e('Pazar Yeri', 'api-isarud'); ?></th><th><?php _e('Durum', 'api-isarud'); ?></th><th><?php _e('Oto-Sync', 'api-isarud'); ?></th><th><?php _e('Fiyat Margin', 'api-isarud'); ?></th><th><?php _e('Son Sync', 'api-isarud'); ?></th><th>Test</th></tr></thead><tbody>
                <?php foreach ($creds as $c): ?>
                <tr>
                    <td><strong><?php echo esc_html(ucfirst($c->marketplace)); ?></strong></td>
                    <td><?php echo $c->is_active ? '<span style="color:#00a32a">● ' . __('Aktif', 'api-isarud') . '</span>' : '<span style="color:#999">○ ' . __('Pasif', 'api-isarud') . '</span>'; ?></td>
                    <td><?php echo $c->auto_sync ? '<span style="color:#2271b1">' . esc_html($c->sync_interval) . '</span>' : '<span style="color:#999">—</span>'; ?></td>
                    <td><?php echo $c->price_margin != 0 ? ($c->price_margin_type === 'percent' ? '%' . $c->price_margin : '$' . $c->price_margin) : '—'; ?></td>
                    <td><?php echo $c->last_sync ? esc_html(human_time_diff(strtotime($c->last_sync))) . ' ' . __('önce', 'api-isarud') : '—'; ?></td>
                    <td><?php echo $c->test_status === 'success' ? '<span style="color:#00a32a">✓</span>' : ($c->test_status === 'error' ? '<span style="color:#d63638">✗</span>' : '—'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($creds)): ?><tr><td colspan="6" style="text-align:center;color:#999"><?php _e('Yapılandırılmış pazar yeri yok.', 'api-isarud'); ?> <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>"><?php _e('Ekle', 'api-isarud'); ?> →</a></td></tr><?php endif; ?>
                </tbody></table>
            </div>

            <?php
            $cloud = Isarud_Cloud_Sync::instance();
            $cloudStatus = $cloud->get_status();
            ?>
            <div class="isd-cloud">
                <h2><?php _e('Cloud Sync', 'api-isarud'); ?></h2>
                <?php if ($cloudStatus['enabled']): ?>
                    <p class="status-connected"><?php _e('Bağlı', 'api-isarud'); ?></p>
                    <p class="sync-time"><?php _e('Son sync:', 'api-isarud'); ?> <?php echo $cloudStatus['last_sync'] ?: '—'; ?></p>
                    <a href="<?php echo admin_url('admin.php?page=isarud-cloud'); ?>" class="button"><?php _e('Cloud Sync Ayarları', 'api-isarud'); ?> →</a>
                <?php else: ?>
                    <p class="status-off"><?php _e('Cloud sync aktif değil.', 'api-isarud'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=isarud-cloud'); ?>" class="button-primary"><?php _e('Şimdi Bağlan', 'api-isarud'); ?> →</a>
                <?php endif; ?>
            </div>

            <div class="isd-cloud" style="margin-top:20px">
                <h2><?php _e('Çoklu Platform', 'api-isarud'); ?></h2>
                <p style="color:#555;margin-bottom:12px"><?php _e('Verilerinizi tüm cihazlarınızdan takip edin. Cloud Sync ile WooCommerce verileriniz tüm platformlarda güncel kalır.', 'api-isarud'); ?></p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px">
                    <a href="https://apps.apple.com/tr/app/isarud-e-commerce-tools/id6761309959" target="_blank" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;text-align:center;text-decoration:none;transition:box-shadow .2s">
                        <span style="font-size:24px;display:block;margin-bottom:4px">📱</span>
                        <strong style="color:#111;font-size:13px">iOS / macOS</strong>
                        <span style="display:block;font-size:11px;color:#16a34a;margin-top:2px">App Store ↗</span>
                    </a>
                    <a href="https://www.microsoft.com/store/apps/9PM1Z57C4GT3" target="_blank" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;text-align:center;text-decoration:none;transition:box-shadow .2s">
                        <span style="font-size:24px;display:block;margin-bottom:4px">🖥️</span>
                        <strong style="color:#111;font-size:13px">Windows</strong>
                        <span style="display:block;font-size:11px;color:#16a34a;margin-top:2px">Microsoft Store ↗</span>
                    </a>
                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px;text-align:center">
                        <span style="font-size:24px;display:block;margin-bottom:4px">🤖</span>
                        <strong style="color:#111;font-size:13px">Android</strong>
                        <span style="display:block;font-size:11px;color:#d97706;margin-top:2px"><?php _e('Yakında', 'api-isarud'); ?></span>
                    </div>
                    <a href="https://isarud.com" target="_blank" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;text-align:center;text-decoration:none;transition:box-shadow .2s">
                        <span style="font-size:24px;display:block;margin-bottom:4px">🌐</span>
                        <strong style="color:#111;font-size:13px">Web</strong>
                        <span style="display:block;font-size:11px;color:#16a34a;margin-top:2px">isarud.com ↗</span>
                    </a>
                </div>
            </div>

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
                    <?php $cloud_connected = Isarud_Cloud_Sync::instance()->is_enabled(); ?>
                    <?php if ($cloud_connected): ?>
                    <tr><th>Isarud API Key</th><td><span style="color:#00a32a;font-weight:bold">&#x1f7e2; <?php _e('Cloud Sync ile bağlı', 'api-isarud'); ?></span><p class="description"><a href="<?php echo admin_url('admin.php?page=isarud-cloud'); ?>"><?php _e('Cloud Sync Ayarları', 'api-isarud'); ?> &rarr;</a></p></td></tr>
                    <?php else: ?>
                    <tr><th>Isarud API Key</th><td><input type="password" name="isarud_api_key" value="<?php echo esc_attr(get_option('isarud_api_key')); ?>" class="regular-text"><p class="description"><a href="https://isarud.com/account/api-keys" target="_blank"><?php _e('isarud.com\'dan API anahtarı alın', 'api-isarud'); ?> →</a> | <a href="<?php echo admin_url('admin.php?page=isarud-cloud'); ?>"><?php _e('veya Cloud Sync ile bağlan', 'api-isarud'); ?> &rarr;</a></p></td></tr>
                    <?php endif; ?>
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
        include ISARUD_DIR . 'includes/marketplaces-html.php';
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
            'trendyol' => ['name' => 'Trendyol', 'docs' => 'partner.trendyol.com → Hesap Bilgilerim → Entegrasyon Bilgileri', 'base_url' => 'https://api.trendyol.com/sapigw/', 'auth_type' => 'basic',
                'fields' => ['api_key' => ['label' => 'API Key', 'type' => 'text'], 'api_secret' => ['label' => 'API Secret', 'type' => 'password'], 'seller_id' => ['label' => 'Supplier ID', 'type' => 'text']]],
            'hepsiburada' => ['name' => 'Hepsiburada', 'docs' => 'merchant.hepsiburada.com → Bilgilerim → Entegrasyon → Entegratör Bilgileri', 'base_url' => 'https://listing-external.hepsiburada.com/', 'auth_type' => 'basic',
                'fields' => ['merchant_id' => ['label' => 'Merchant ID', 'type' => 'text'], 'service_key' => ['label' => 'Servis Anahtarı (Service Key)', 'type' => 'password']]],
            'n11' => ['name' => 'N11', 'docs' => 'uye.n11.com → Mağazam → API Ayarları', 'base_url' => 'https://api.n11.com/ws/', 'auth_type' => 'soap',
                'fields' => ['api_key' => ['label' => 'API Key', 'type' => 'text'], 'api_secret' => ['label' => 'API Secret', 'type' => 'password']]],
            'amazon' => ['name' => 'Amazon SP-API', 'docs' => 'sellercentral.amazon.com → Apps & Services → Develop Apps', 'base_url' => 'https://sellingpartnerapi-eu.amazon.com/', 'auth_type' => 'sp-api',
                'fields' => ['client_id' => ['label' => 'Client ID', 'type' => 'text'], 'client_secret' => ['label' => 'Client Secret', 'type' => 'password'], 'refresh_token' => ['label' => 'Refresh Token', 'type' => 'password'], 'marketplace_id' => ['label' => 'Marketplace ID', 'type' => 'text']]],
            'pazarama' => ['name' => 'Pazarama', 'docs' => 'merchant.pazarama.com → Entegrasyon Bilgileri', 'base_url' => 'https://api.pazarama.com/', 'auth_type' => 'bearer',
                'fields' => ['api_key' => ['label' => 'API Key', 'type' => 'text'], 'api_secret' => ['label' => 'API Secret', 'type' => 'password'], 'seller_id' => ['label' => 'Seller ID', 'type' => 'text']]],
            'etsy' => ['name' => 'Etsy', 'docs' => 'etsy.com/developers → Manage Your Apps → Create App', 'base_url' => 'https://openapi.etsy.com/v3/', 'auth_type' => 'oauth2',
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
        $headers = ['Content-Type' => 'application/json', 'User-Agent' => ($mp === 'trendyol' ? ($creds['seller_id'] ?? '') . ' - Isarud/' . ISARUD_VERSION : 'Isarud/' . ISARUD_VERSION)];
        switch ($config['auth_type']) {
            case 'basic': $headers['Authorization'] = 'Basic ' . base64_encode(($creds['api_key'] ?? $creds['merchant_id'] ?? '') . ':' . ($creds['api_secret'] ?? $creds['service_key'] ?? '')); break;
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


    // ─── VARIATION AWARE SYNC ───────────────────
    public function sync_product_smart($product, string $mp): array {
        if ($product->is_type('variable')) {
            return Isarud_Variation_Sync::instance()->sync_variable_product($product, $mp);
        }
        return $this->sync_stock($product, $mp);
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
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Unauthorized');
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $p = wc_get_product(intval($_POST['product_id'] ?? 0));
        if (!$p || !$mp) wp_send_json_error('Invalid');
        $r = $this->sync_stock($p, $mp);
        isset($r['error']) ? wp_send_json_error($r['error']) : wp_send_json_success($r);
    }

    public function ajax_bulk_sync() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Unauthorized');
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
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Unauthorized');
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



    // ─── PRODUCT EXPORT PAGE ────────────────────
    public function page_product_export() {
        global $wpdb;
        $mps = $wpdb->get_results("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active=1");
        $products = wc_get_products(['status' => 'publish', 'limit' => 100]);
        ?>
        <div class="wrap">
            <h1><?php _e('Ürün Yükleme (WooCommerce → Pazar Yeri)', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('WooCommerce ürünlerinizi pazar yerlerine yükleyin. Yeni ürün oluşturur (stok/fiyat güncelleme değil).', 'api-isarud'); ?></p>

            <?php if (empty($mps)): ?>
                <p style="color:#999"><?php _e('Aktif pazar yeri yok.', 'api-isarud'); ?></p>
            <?php else: ?>
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;max-width:700px">
                <table class="form-table" style="margin:0">
                    <tr><th><?php _e('Pazar Yeri', 'api-isarud'); ?></th><td>
                        <select id="isarud-export-mp">
                            <?php foreach ($mps as $m): ?><option value="<?php echo esc_attr($m->marketplace); ?>"><?php echo esc_html(ucfirst($m->marketplace)); ?></option><?php endforeach; ?>
                        </select>
                    </td></tr>
                </table>
                <p style="margin-top:15px">
                    <button type="button" class="button-primary" id="isarud-export-all"><?php _e('Tüm Ürünleri Yükle', 'api-isarud'); ?></button>
                </p>
                <div id="isarud-export-result" style="margin-top:10px"></div>

                <h3 style="margin-top:20px"><?php _e('Veya tek ürün seç:', 'api-isarud'); ?></h3>
                <table class="wp-list-table widefat fixed striped" style="max-height:400px;overflow-y:auto">
                    <thead><tr><th style="width:40px"></th><th>SKU</th><th><?php _e('Ürün', 'api-isarud'); ?></th><th><?php _e('Fiyat', 'api-isarud'); ?></th><th><?php _e('Stok', 'api-isarud'); ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><input type="checkbox" class="isarud-export-check" value="<?php echo $p->get_id(); ?>"></td>
                        <td><code><?php echo esc_html($p->get_sku() ?: '-'); ?></code></td>
                        <td><?php echo esc_html($p->get_name()); ?></td>
                        <td><?php echo wc_price($p->get_price()); ?></td>
                        <td><?php echo $p->get_stock_quantity() ?? '-'; ?></td>
                        <td><button type="button" class="button button-small isarud-export-one" data-id="<?php echo $p->get_id(); ?>"><?php _e('Yükle', 'api-isarud'); ?></button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery(function($) {
            $('#isarud-export-all').on('click', function() {
                if (!confirm('Tüm yayında ürünleri yüklemek istediğinize emin misiniz?')) return;
                var btn = $(this).prop('disabled', true).text('Yükleniyor...');
                $.post(isarud.ajax, {
                    action: 'isarud_export_products',
                    nonce: isarud.nonce,
                    marketplace: $('#isarud-export-mp').val()
                }, function(r) {
                    btn.prop('disabled', false).text('<?php _e('Tüm Ürünleri Yükle', 'api-isarud'); ?>');
                    if (r.success) {
                        var d = r.data;
                        var html = '<div class="notice notice-success"><p>✅ ' + d.exported + ' yüklendi, ' + d.failed + ' hata</p>';
                        if (d.errors && d.errors.length) html += '<p style="color:#d63638">' + d.errors.join('<br>') + '</p>';
                        html += '</div>';
                        $('#isarud-export-result').html(html);
                    } else {
                        $('#isarud-export-result').html('<div class="notice notice-error"><p>' + (r.data?.error || 'Hata') + '</p></div>');
                    }
                });
            });

            $('.isarud-export-one').on('click', function() {
                var btn = $(this).prop('disabled', true).text('...');
                $.post(isarud.ajax, {
                    action: 'isarud_export_single',
                    nonce: isarud.nonce,
                    marketplace: $('#isarud-export-mp').val(),
                    product_id: btn.data('id')
                }, function(r) {
                    btn.prop('disabled', false);
                    if (r.success) btn.text('✓').css('color', '#00a32a');
                    else { btn.text('✗').css('color', '#d63638'); alert(r.data?.error || 'Hata'); }
                    setTimeout(function() { btn.text('<?php _e('Yükle', 'api-isarud'); ?>').css('color', ''); }, 3000);
                });
            });
        });
        </script>
        <?php
    }

    // ─── ATTRIBUTES PAGE ────────────────────────
    public function page_attributes() {
        ?>
        <div class="wrap">
            <h1><?php _e('Attribute Eşleştirme', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('WooCommerce attribute\'larını pazar yeri attribute\'larıyla eşleştirin. Ürün yükleme ve varyasyon sync için kullanılır.', 'api-isarud'); ?></p>
            <?php Isarud_Attribute_Map::instance()->render_mapping_ui(); ?>
        </div>
        <?php
    }

    // ─── CSV PAGE ───────────────────────────────
    public function page_csv() {
        ?>
        <div class="wrap">
            <h1><?php _e('CSV İşlemleri', 'api-isarud'); ?></h1>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:900px">
                <!-- Import -->
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px">
                    <h2 style="margin-top:0"><?php _e('CSV\'den Ürün Aktar', 'api-isarud'); ?></h2>
                    <p style="color:#666;font-size:12px"><?php _e('Desteklenen sütunlar: sku, barcode/barkod, title/urun_adi, price/fiyat, stock/stok, category/kategori, description/aciklama, image_url/gorsel, marketplace/pazaryeri', 'api-isarud'); ?></p>
                    <form id="isarud-csv-form" enctype="multipart/form-data">
                        <p><input type="file" name="csv_file" id="isarud-csv-file" accept=".csv,.tsv,.txt"></p>
                        <p>
                            <select name="product_status">
                                <option value="draft"><?php _e('Taslak', 'api-isarud'); ?></option>
                                <option value="publish"><?php _e('Yayında', 'api-isarud'); ?></option>
                            </select>
                        </p>
                        <p><label><input type="checkbox" name="update_existing" value="1"> <?php _e('Mevcut ürünleri güncelle', 'api-isarud'); ?></label></p>
                        <p><button type="submit" class="button-primary" id="isarud-csv-import-btn"><?php _e('Aktar', 'api-isarud'); ?></button></p>
                    </form>
                    <div id="isarud-csv-result" style="margin-top:10px"></div>
                </div>

                <!-- Export -->
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px">
                    <h2 style="margin-top:0"><?php _e('Ürünleri CSV\'ye Aktar', 'api-isarud'); ?></h2>
                    <p style="color:#666;font-size:12px"><?php _e('Tüm yayında WooCommerce ürünlerini CSV olarak indirin. Excel uyumlu (UTF-8 BOM).', 'api-isarud'); ?></p>
                    <p><button type="button" class="button-primary" id="isarud-csv-export-btn"><?php _e('CSV İndir', 'api-isarud'); ?></button></p>
                    <div id="isarud-csv-export-result" style="margin-top:10px"></div>
                </div>
            </div>
        </div>
        <script>
        jQuery(function($) {
            $('#isarud-csv-form').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('action', 'isarud_csv_import');
                formData.append('nonce', isarud.nonce);
                var btn = $('#isarud-csv-import-btn').prop('disabled', true).text('Aktarılıyor...');
                $.ajax({
                    url: isarud.ajax, type: 'POST', data: formData,
                    processData: false, contentType: false,
                    success: function(r) {
                        btn.prop('disabled', false).text('<?php _e('Aktar', 'api-isarud'); ?>');
                        if (r.success) {
                            var d = r.data;
                            var html = '<div class="notice notice-success"><p>✅ ' + d.imported + ' aktarıldı, ' + d.updated + ' güncellendi, ' + d.skipped + ' atlandı (' + d.total_rows + ' satır)</p>';
                            if (d.errors && d.errors.length) html += '<p style="color:#d63638;font-size:12px">' + d.errors.join('<br>') + '</p>';
                            html += '</div>';
                            $('#isarud-csv-result').html(html);
                        } else {
                            $('#isarud-csv-result').html('<div class="notice notice-error"><p>' + (r.data?.error || r.data) + '</p></div>');
                        }
                    }
                });
            });

            $('#isarud-csv-export-btn').on('click', function() {
                var btn = $(this).prop('disabled', true).text('Oluşturuluyor...');
                $.post(isarud.ajax, {action: 'isarud_csv_export', nonce: isarud.nonce}, function(r) {
                    btn.prop('disabled', false).text('<?php _e('CSV İndir', 'api-isarud'); ?>');
                    if (r.success) {
                        $('#isarud-csv-export-result').html('<div class="notice notice-success"><p>✅ <a href="' + r.data.url + '" target="_blank"><?php _e('CSV\'yi İndir', 'api-isarud'); ?> →</a></p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }




    // ─── STATISTICS PAGE ────────────────────────
    public function page_statistics() {
        global $wpdb;
        $t = $wpdb->prefix . 'isarud_';
        $period = sanitize_text_field($_GET['period'] ?? '24h');
        $since = match($period) {
            '1h' => date("Y-m-d H:i:s", strtotime("-1 hour")),
            '24h' => date("Y-m-d H:i:s", strtotime("-24 hours")),
            '7d' => date("Y-m-d H:i:s", strtotime("-7 days")),
            '30d' => date("Y-m-d H:i:s", strtotime("-30 days")),
            'all' => '2000-01-01 00:00:00',
            default => date("Y-m-d H:i:s", strtotime("-24 hours")),
        };
        $screen_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}screening_log WHERE created_at >= %s", $since));
        $match_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}screening_log WHERE has_match=1 AND created_at >= %s", $since));
        $sync_ok = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}sync_log WHERE status='success' AND created_at >= %s", $since));
        $sync_err = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t}sync_log WHERE status='error' AND created_at >= %s", $since));
        $active_mp = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}credentials WHERE is_active=1");

        // Birleşik aktivite listesi
        $all_activities = $wpdb->get_results($wpdb->prepare(
            "(SELECT 'screening' as type, entity_name as detail, IF(has_match=1,'match','clean') as status, created_at FROM {$t}screening_log WHERE created_at >= %s)
             UNION ALL
             (SELECT 'sync' as type, CONCAT(marketplace, ': ', COALESCE(message,'')) as detail, status, created_at FROM {$t}sync_log WHERE created_at >= %s)
             ORDER BY created_at DESC LIMIT 100", $since, $since
        ));
        $page_url = admin_url('admin.php?page=isarud-statistics');
        ?>
        <div class="wrap" style="max-width:960px">
            <h1 style="display:flex;align-items:center;gap:10px">
                <span class="dashicons dashicons-chart-area" style="font-size:24px;color:#2271b1"></span>
                <?php _e('İstatistikler & Aktivite', 'api-isarud'); ?>
            </h1>

            <div style="margin:15px 0;display:flex;gap:6px">
                <?php foreach (['1h' => __( '1 Saat', 'api-isarud'), '24h' => __( '24 Saat', 'api-isarud'), '7d' => __( '7 Gün', 'api-isarud'), '30d' => __( '30 Gün', 'api-isarud'), 'all' => __( 'Tümü', 'api-isarud')] as $k => $label): ?>
                <a href="<?php echo esc_url(add_query_arg('period', $k, $page_url)); ?>" class="button <?php echo $period === $k ? 'button-primary' : ''; ?>"><?php echo $label; ?></a>
                <?php endforeach; ?>
            </div>

            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin:20px 0">
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;text-align:center">
                    <div style="font-size:26px;font-weight:bold;color:#2271b1"><?php echo $screen_count; ?></div>
                    <small style="color:#999"><?php _e('Tarama', 'api-isarud'); ?></small>
                </div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;text-align:center">
                    <div style="font-size:26px;font-weight:bold;color:<?php echo $match_count > 0 ? '#d63638' : '#00a32a'; ?>"><?php echo $match_count; ?></div>
                    <small style="color:#999"><?php _e('Eşleşme', 'api-isarud'); ?></small>
                </div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;text-align:center">
                    <div style="font-size:26px;font-weight:bold;color:#00a32a"><?php echo $sync_ok; ?></div>
                    <small style="color:#999"><?php _e('Başarılı Sync', 'api-isarud'); ?></small>
                </div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;text-align:center">
                    <div style="font-size:26px;font-weight:bold;color:<?php echo $sync_err > 0 ? '#dba617' : '#999'; ?>"><?php echo $sync_err; ?></div>
                    <small style="color:#999"><?php _e('Sync Hataları', 'api-isarud'); ?></small>
                </div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;text-align:center">
                    <div style="font-size:26px;font-weight:bold;color:#7c3aed"><?php echo $active_mp; ?></div>
                    <small style="color:#999"><?php _e('Aktif Platform', 'api-isarud'); ?></small>
                </div>
            </div>

            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:20px 0">
                <h3 style="margin:0 0 15px 0"><?php _e('Aktivite Günlüğü', 'api-isarud'); ?></h3>
                <?php if (empty($all_activities)): ?>
                <p style="color:#999;text-align:center;padding:20px"><?php _e('Bu dönemde aktivite bulunamadı.', 'api-isarud'); ?></p>
                <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr>
                        <th style="width:160px"><?php _e('Tarih/Saat', 'api-isarud'); ?></th>
                        <th style="width:90px"><?php _e('Tür', 'api-isarud'); ?></th>
                        <th><?php _e('Detay', 'api-isarud'); ?></th>
                        <th style="width:100px"><?php _e('Durum', 'api-isarud'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($all_activities as $act):
                        $type_icon = $act->type === 'screening' ? '🛡️' : '🔄';
                        $type_label = $act->type === 'screening' ? __( 'Tarama', 'api-isarud') : __( 'Sync', 'api-isarud');
                        $sc = match($act->status) { 'match' => '#d63638', 'error' => '#dba617', 'clean','success' => '#00a32a', default => '#999' };
                        $sl = match($act->status) { 'match' => '⚠️ ' . __( 'Eşleşme', 'api-isarud'), 'clean' => '✓ ' . __( 'Temiz', 'api-isarud'), 'success' => '✓ ' . __( 'Başarılı', 'api-isarud'), 'error' => '✗ ' . __( 'Hata', 'api-isarud'), default => $act->status };
                    ?>
                    <tr>
                        <td style="font-size:12px">
                            <span style="color:#333"><?php echo date('d.m.Y H:i', strtotime($act->created_at)); ?></span>
                            <br><small style="color:#999"><?php echo human_time_diff(strtotime($act->created_at)) . ' ' . __( 'önce', 'api-isarud'); ?></small>
                        </td>
                        <td style="font-size:12px"><?php echo $type_icon . ' ' . $type_label; ?></td>
                        <td style="font-size:12px"><?php echo esc_html(mb_substr($act->detail, 0, 80)); ?></td>
                        <td><span style="color:<?php echo $sc; ?>;font-weight:600;font-size:12px"><?php echo $sl; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin:10px 0 0;color:#999;font-size:11px;text-align:right"><?php echo count($all_activities); ?> <?php _e('kayıt gösteriliyor (maks. 100)', 'api-isarud'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // ─── RETURNS PAGE ───────────────────────────
    public function page_returns() {
        ?>
        <div class="wrap">
            <h1><?php _e('İade Yönetimi', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('Pazar yerlerinden gelen iade taleplerini görüntüleyin ve yönetin.', 'api-isarud'); ?></p>
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;max-width:900px">
                <div style="display:flex;gap:10px;margin-bottom:15px">
                    <select id="isarud-return-mp"><option value="all"><?php _e('Tümü', 'api-isarud'); ?></option><option value="trendyol">Trendyol</option><option value="hepsiburada">Hepsiburada</option></select>
                    <button type="button" class="button-primary" id="isarud-fetch-returns"><?php _e('İadeleri Getir', 'api-isarud'); ?></button>
                </div>
                <div id="isarud-returns-list"></div>
            </div>
        </div>
        <script>
        jQuery(function($) {
            $('#isarud-fetch-returns').on('click', function() {
                var btn = $(this).prop('disabled', true).text('Yükleniyor...');
                $.post(isarud.ajax, {action:'isarud_fetch_returns', nonce:isarud.nonce, marketplace:$('#isarud-return-mp').val()}, function(r) {
                    btn.prop('disabled', false).text('<?php _e('İadeleri Getir', 'api-isarud'); ?>');
                    if (r.success && r.data.returns) {
                        var html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th><?php _e('Platform', 'api-isarud'); ?></th><th><?php _e('Sipariş', 'api-isarud'); ?></th><th><?php _e('Müşteri', 'api-isarud'); ?></th><th><?php _e('Durum', 'api-isarud'); ?></th><th><?php _e('Sebep', 'api-isarud'); ?></th><th><?php _e('Tarih', 'api-isarud'); ?></th></tr></thead><tbody>';
                        if (r.data.returns.length === 0) html += '<tr><td colspan="6" style="text-align:center;color:#999"><?php _e('İade bulunamadı.', 'api-isarud'); ?></td></tr>';
                        $.each(r.data.returns, function(i, ret) {
                            html += '<tr><td>' + ret.marketplace + '</td><td>' + ret.order_number + '</td><td>' + ret.customer + '</td><td>' + ret.status + '</td><td>' + (ret.reason || '-') + '</td><td>' + ret.created_at + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        $('#isarud-returns-list').html(html);
                    }
                });
            });
        });
        </script>
        <?php
    }

    // ─── CUSTOMER QUESTIONS PAGE ────────────────
    public function page_questions() {
        ?>
        <div class="wrap">
            <h1><?php _e('Müşteri Soruları', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('Trendyol\'dan gelen müşteri sorularını görüntüleyin ve yanıtlayın.', 'api-isarud'); ?></p>
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;max-width:900px">
                <div style="display:flex;gap:10px;margin-bottom:15px">
                    <select id="isarud-q-status"><option value=""><?php _e('Tümü', 'api-isarud'); ?></option><option value="WAITING_FOR_ANSWER"><?php _e('Yanıt Bekliyor', 'api-isarud'); ?></option><option value="ANSWERED"><?php _e('Yanıtlandı', 'api-isarud'); ?></option></select>
                    <button type="button" class="button-primary" id="isarud-fetch-questions"><?php _e('Soruları Getir', 'api-isarud'); ?></button>
                </div>
                <div id="isarud-questions-list"></div>
            </div>
        </div>
        <script>
        jQuery(function($) {
            $('#isarud-fetch-questions').on('click', function() {
                var btn = $(this).prop('disabled', true).text('Yükleniyor...');
                $.post(isarud.ajax, {action:'isarud_fetch_questions', nonce:isarud.nonce, marketplace:'trendyol', status:$('#isarud-q-status').val()}, function(r) {
                    btn.prop('disabled', false).text('<?php _e('Soruları Getir', 'api-isarud'); ?>');
                    if (r.success && r.data.questions) {
                        var html = '';
                        if (r.data.questions.length === 0) { html = '<p style="color:#999"><?php _e('Soru bulunamadı.', 'api-isarud'); ?></p>'; }
                        $.each(r.data.questions, function(i, q) {
                            html += '<div style="border:1px solid #eee;border-radius:8px;padding:15px;margin-bottom:10px">';
                            html += '<div style="display:flex;justify-content:space-between"><strong>' + (q.product_name || '') + '</strong><small style="color:#999">' + q.created_at + '</small></div>';
                            html += '<p style="margin:8px 0"><strong>' + (q.customer || '') + ':</strong> ' + q.question + '</p>';
                            if (q.answer) { html += '<p style="color:#00a32a;margin:4px 0">✓ ' + q.answer + '</p>'; }
                            else {
                                html += '<div style="display:flex;gap:8px;margin-top:8px"><input type="text" class="regular-text isarud-answer-input" data-qid="' + q.id + '" placeholder="<?php _e('Yanıtınız...', 'api-isarud'); ?>"><button type="button" class="button isarud-answer-btn" data-qid="' + q.id + '"><?php _e('Yanıtla', 'api-isarud'); ?></button></div>';
                            }
                            html += '</div>';
                        });
                        $('#isarud-questions-list').html(html);
                    }
                });
            });
            $(document).on('click', '.isarud-answer-btn', function() {
                var qid = $(this).data('qid'), answer = $('.isarud-answer-input[data-qid="'+qid+'"]').val();
                if (!answer) return;
                var btn = $(this).prop('disabled', true).text('...');
                $.post(isarud.ajax, {action:'isarud_answer_question', nonce:isarud.nonce, marketplace:'trendyol', question_id:qid, answer:answer}, function(r) {
                    btn.prop('disabled', false);
                    if (r.success) { btn.text('✓').css('color','#00a32a'); } else { btn.text('✗').css('color','#d63638'); alert(r.data?.error || 'Hata'); }
                });
            });
        });
        </script>
        <?php
    }

    // ─── ORDER IMPORT PAGE ──────────────────────
    public function page_order_import() {
        global $wpdb;
        $mps = $wpdb->get_results("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active=1");
        ?>
        <div class="wrap">
            <h1><?php _e('Sipariş Aktarımı', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('Pazar yerlerinden siparişleri WooCommerce\'e aktarın.', 'api-isarud'); ?></p>

            <?php if (empty($mps)): ?>
                <p style="color:#999"><?php _e('Aktif pazar yeri yok.', 'api-isarud'); ?> <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>"><?php _e('Ekle', 'api-isarud'); ?> →</a></p>
            <?php else: ?>
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;max-width:600px">
                <table class="form-table" style="margin:0">
                    <tr><th><?php _e('Pazar Yeri', 'api-isarud'); ?></th><td>
                        <select id="isarud-import-mp">
                            <option value="all"><?php _e('Tümü', 'api-isarud'); ?></option>
                            <?php foreach ($mps as $m): ?><option value="<?php echo esc_attr($m->marketplace); ?>"><?php echo esc_html(ucfirst($m->marketplace)); ?></option><?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th><?php _e('Son kaç gün?', 'api-isarud'); ?></th><td>
                        <select id="isarud-import-days">
                            <option value="1">1 gün</option>
                            <option value="3">3 gün</option>
                            <option value="7" selected>7 gün</option>
                            <option value="14">14 gün</option>
                            <option value="30">30 gün</option>
                        </select>
                    </td></tr>
                    <tr><th><?php _e('Otomatik aktarım', 'api-isarud'); ?></th><td>
                        <label><input type="checkbox" id="isarud-auto-import" <?php checked(get_option('isarud_auto_import_orders'), 'yes'); ?>> <?php _e('Saatlik otomatik sipariş çekme', 'api-isarud'); ?></label>
                    </td></tr>
                </table>
                <p style="margin-top:15px">
                    <button type="button" class="button-primary" id="isarud-import-orders-btn"><?php _e('Siparişleri Aktar', 'api-isarud'); ?></button>
                </p>
                <div id="isarud-import-result" style="margin-top:10px"></div>
            </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery(function($) {
            $('#isarud-import-orders-btn').on('click', function() {
                var btn = $(this).prop('disabled', true).text('Aktarılıyor...');
                $.post(isarud.ajax, {
                    action: 'isarud_import_orders',
                    nonce: isarud.nonce,
                    marketplace: $('#isarud-import-mp').val(),
                    days: $('#isarud-import-days').val()
                }, function(r) {
                    btn.prop('disabled', false).text('<?php _e('Siparişleri Aktar', 'api-isarud'); ?>');
                    if (r.success) {
                        var d = r.data;
                        var html = '<div class="notice notice-success"><p>✅ ';
                        if (d.results) {
                            $.each(d.results, function(mp, res) {
                                html += ucfirst(mp) + ': ' + (res.imported || 0) + ' aktarıldı, ' + (res.skipped || 0) + ' atlandı<br>';
                            });
                        } else {
                            html += (d.imported || 0) + ' aktarıldı, ' + (d.skipped || 0) + ' atlandı';
                        }
                        html += '</p></div>';
                        $('#isarud-import-result').html(html);
                    } else {
                        $('#isarud-import-result').html('<div class="notice notice-error"><p>' + (r.data?.error || r.data?.message || 'Hata') + '</p></div>');
                    }
                });
            });
            function ucfirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
        });
        </script>
        <?php
    }

    // ─── PRODUCT IMPORT PAGE ────────────────────
    public function page_product_import() {
        global $wpdb;
        $mps = $wpdb->get_results("SELECT marketplace FROM {$wpdb->prefix}isarud_credentials WHERE is_active=1");
        ?>
        <div class="wrap">
            <h1><?php _e('Ürün Aktarımı', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('Pazar yerlerinden ürünleri WooCommerce\'e aktarın.', 'api-isarud'); ?></p>

            <?php if (empty($mps)): ?>
                <p style="color:#999"><?php _e('Aktif pazar yeri yok.', 'api-isarud'); ?></p>
            <?php else: ?>
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;max-width:600px">
                <table class="form-table" style="margin:0">
                    <tr><th><?php _e('Pazar Yeri', 'api-isarud'); ?></th><td>
                        <select id="isarud-product-mp">
                            <?php foreach ($mps as $m): ?><option value="<?php echo esc_attr($m->marketplace); ?>"><?php echo esc_html(ucfirst($m->marketplace)); ?></option><?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th><?php _e('Ürün durumu', 'api-isarud'); ?></th><td>
                        <select id="isarud-product-status">
                            <option value="draft"><?php _e('Taslak', 'api-isarud'); ?></option>
                            <option value="publish"><?php _e('Yayında', 'api-isarud'); ?></option>
                            <option value="private"><?php _e('Özel', 'api-isarud'); ?></option>
                        </select>
                    </td></tr>
                    <tr><th><?php _e('Seçenekler', 'api-isarud'); ?></th><td>
                        <label><input type="checkbox" id="isarud-update-existing" value="1"> <?php _e('Mevcut ürünleri güncelle', 'api-isarud'); ?></label><br>
                        <label><input type="checkbox" id="isarud-update-stock" value="1" checked> <?php _e('Stok güncelle', 'api-isarud'); ?></label><br>
                        <label><input type="checkbox" id="isarud-update-price" value="1" checked> <?php _e('Fiyat güncelle', 'api-isarud'); ?></label>
                    </td></tr>
                </table>
                <p style="margin-top:15px">
                    <button type="button" class="button" id="isarud-preview-products"><?php _e('Önizle', 'api-isarud'); ?></button>
                    <button type="button" class="button-primary" id="isarud-import-products-btn"><?php _e('Ürünleri Aktar', 'api-isarud'); ?></button>
                </p>
                <div id="isarud-product-result" style="margin-top:10px"></div>
            </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery(function($) {
            $('#isarud-preview-products').on('click', function() {
                var btn = $(this).prop('disabled', true).text('Yükleniyor...');
                $.post(isarud.ajax, {
                    action: 'isarud_fetch_mp_products',
                    nonce: isarud.nonce,
                    marketplace: $('#isarud-product-mp').val(),
                    page: 0
                }, function(r) {
                    btn.prop('disabled', false).text('<?php _e('Önizle', 'api-isarud'); ?>');
                    if (r.success && r.data.products) {
                        var html = '<table class="wp-list-table widefat fixed striped" style="margin-top:10px"><thead><tr><th>SKU</th><th>Ürün</th><th>Fiyat</th><th>Stok</th></tr></thead><tbody>';
                        $.each(r.data.products.slice(0, 20), function(i, p) {
                            html += '<tr><td>' + (p.barcode || '-') + '</td><td>' + (p.title || '-') + '</td><td>' + (p.price || 0) + '</td><td>' + (p.stock || 0) + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        if (r.data.total > 20) html += '<p style="color:#999">' + r.data.total + ' üründen ilk 20 gösteriliyor</p>';
                        $('#isarud-product-result').html(html);
                    } else {
                        $('#isarud-product-result').html('<div class="notice notice-error"><p>' + (r.data?.error || 'Hata') + '</p></div>');
                    }
                });
            });

            $('#isarud-import-products-btn').on('click', function() {
                if (!confirm('<?php _e('Tüm ürünleri aktarmak istediğinize emin misiniz?', 'api-isarud'); ?>')) return;
                var btn = $(this).prop('disabled', true).text('Aktarılıyor...');
                $.post(isarud.ajax, {
                    action: 'isarud_import_products',
                    nonce: isarud.nonce,
                    marketplace: $('#isarud-product-mp').val(),
                    product_status: $('#isarud-product-status').val(),
                    update_existing: $('#isarud-update-existing').is(':checked') ? '1' : '0',
                    update_stock: $('#isarud-update-stock').is(':checked') ? '1' : '0',
                    update_price: $('#isarud-update-price').is(':checked') ? '1' : '0'
                }, function(r) {
                    btn.prop('disabled', false).text('<?php _e('Ürünleri Aktar', 'api-isarud'); ?>');
                    if (r.success) {
                        $('#isarud-product-result').html('<div class="notice notice-success"><p>✅ ' + (r.data.imported || 0) + ' aktarıldı, ' + (r.data.updated || 0) + ' güncellendi, ' + (r.data.skipped || 0) + ' atlandı</p></div>');
                    } else {
                        $('#isarud-product-result').html('<div class="notice notice-error"><p>' + (r.data?.error || 'Hata') + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    // ─── CATEGORIES PAGE ────────────────────────
    public function page_categories() {
        ?>
        <div class="wrap">
            <h1><?php _e('Kategori Eşleştirme', 'api-isarud'); ?></h1>
            <?php Isarud_Category_Map::instance()->render_mapping_ui(); ?>
        </div>
        <?php
    }

    // ─── WEBHOOKS PAGE ──────────────────────────
    public function page_webhooks() {
        if (isset($_POST['isarud_save_webhook']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_webhook')) {
            $secret = sanitize_text_field($_POST['webhook_secret'] ?? '');
            if (empty($secret)) $secret = wp_generate_password(32, false);
            update_option('isarud_webhook_secret', $secret);
            echo '<div class="notice notice-success"><p>' . __('Kaydedildi.', 'api-isarud') . '</p></div>';
        }
        $urls = Isarud_Webhook::get_webhook_urls();
        $secret = get_option('isarud_webhook_secret', '');
        ?>
        <div class="wrap">
            <h1><?php _e('Webhook Ayarları', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('Pazar yerlerinden gelen stok ve sipariş bildirimlerini almak için aşağıdaki URL\'leri ilgili pazar yeri panellerine ekleyin.', 'api-isarud'); ?></p>

            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0;max-width:700px">
                <h2 style="margin-top:0"><?php _e('Webhook URL\'leri', 'api-isarud'); ?></h2>
                <table class="form-table" style="margin:0">
                    <tr><th>Trendyol</th><td><code style="font-size:12px;word-break:break-all"><?php echo esc_html($urls['trendyol']); ?></code><br><small style="color:#999">partner.trendyol.com → Entegrasyon → Webhook Ayarları</small></td></tr>
                    <tr><th>Hepsiburada</th><td><code style="font-size:12px;word-break:break-all"><?php echo esc_html($urls['hepsiburada']); ?></code><br><small style="color:#999">merchant.hepsiburada.com → Entegrasyon → Webhook</small></td></tr>
                    <tr><th>N11</th><td><code style="font-size:12px;word-break:break-all"><?php echo esc_html($urls['n11']); ?></code><br><small style="color:#999">uye.n11.com → API Ayarları → Webhook</small></td></tr>
                    <tr><th>Generic</th><td><code style="font-size:12px;word-break:break-all"><?php echo esc_html($urls['generic']); ?></code><br><small style="color:#999"><?php _e('Özel entegrasyonlar için. Header: X-Isarud-Webhook-Key gerekir', 'api-isarud'); ?></small></td></tr>
                </table>
            </div>

            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0;max-width:700px">
                <h2 style="margin-top:0"><?php _e('Webhook Güvenlik', 'api-isarud'); ?></h2>
                <form method="post"><?php wp_nonce_field('isarud_webhook'); ?>
                    <table class="form-table" style="margin:0">
                        <tr><th>Webhook Secret</th><td>
                            <input type="text" name="webhook_secret" value="<?php echo esc_attr($secret); ?>" class="regular-text" placeholder="<?php _e('Otomatik oluşturulur', 'api-isarud'); ?>">
                            <p class="description"><?php _e('Generic webhook endpoint için gerekli. Pazar yeri webhook\'ları için gerekmez.', 'api-isarud'); ?></p>
                        </td></tr>
                    </table>
                    <p class="submit"><input type="submit" name="isarud_save_webhook" class="button-primary" value="<?php _e('Kaydet', 'api-isarud'); ?>"></p>
                </form>
            </div>

            <div style="background:#f0f6fc;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0;max-width:700px">
                <h3 style="margin-top:0"><?php _e('Nasıl Çalışır?', 'api-isarud'); ?></h3>
                <ul style="list-style:disc;padding-left:20px;color:#666">
                    <li><?php _e('Pazar yerinden sipariş geldiğinde WooCommerce stoğu otomatik düşer', 'api-isarud'); ?></li>
                    <li><?php _e('Pazar yerinde stok değiştiğinde WooCommerce da güncellenir', 'api-isarud'); ?></li>
                    <li><?php _e('Çift yönlü senkronizasyon — stok her zaman güncel', 'api-isarud'); ?></li>
                    <li><?php _e('Tüm değişiklikler Senkronizasyon Günlüğü\'nde kaydedilir', 'api-isarud'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    // ─── CLOUD SYNC PAGE ────────────────────────
    public function page_cloud_sync() {
        $cloud = Isarud_Cloud_Sync::instance();
        $status = $cloud->get_status();

        if (isset($_POST['isarud_save_cloud']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_cloud')) {
            $enabled = sanitize_text_field($_POST['cloud_enabled'] ?? 'no');
            update_option('isarud_cloud_enabled', $enabled);

            if ($enabled === 'no') {
                $cloud->disconnect();
                echo '<div class="notice notice-info"><p>' . __('Cloud sync devre dışı bırakıldı.', 'api-isarud') . '</p></div>';
            }

            $status = $cloud->get_status();
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Isarud Cloud Sync', 'api-isarud'); ?></h1>
            <p style="color:#666"><?php _e('WooCommerce verilerinizi isarud.com hesabınıza senkronize edin. Ürünlerinizi ve siparişlerinizi web, mobil ve masaüstü uygulamalarında görün.', 'api-isarud'); ?></p>

            <?php if ($status['enabled']): ?>
                <!-- Connected State -->
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0;max-width:700px">
                    <h2 style="margin-top:0;color:#00a32a">🟢 <?php _e('Bağlı', 'api-isarud'); ?></h2>
                    <table class="form-table" style="margin:0">
                        <tr><th>API Key</th><td><code><?php echo esc_html($status['cloud_key']); ?></code></td></tr>
                        <tr><th><?php _e('Son Sync', 'api-isarud'); ?></th><td><?php echo $status['last_sync'] ? esc_html($status['last_sync']) : '—'; ?></td></tr>
                        <tr><th><?php _e('Son Ürün Sync', 'api-isarud'); ?></th><td><?php echo $status['last_product_sync'] ? esc_html($status['last_product_sync']) : '—'; ?></td></tr>
                        <tr><th><?php _e('Son Sipariş Sync', 'api-isarud'); ?></th><td><?php echo $status['last_order_sync'] ? esc_html($status['last_order_sync']) : '—'; ?></td></tr>
                    </table>
                    <p style="margin-top:15px">
                        <button type="button" class="button-primary" id="isarud-cloud-sync-now"><?php _e('Şimdi Senkronize Et', 'api-isarud'); ?></button>
                        <button type="button" class="button" id="isarud-cloud-disconnect" style="color:#d63638;border-color:#d63638"><?php _e('Bağlantıyı Kes', 'api-isarud'); ?></button>
                    </p>
                    <div id="isarud-cloud-result" style="margin-top:10px"></div>
                </div>
            <?php else: ?>
                <!-- Setup State -->
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0;max-width:700px">
                    <h2 style="margin-top:0"><?php _e('isarud.com\'a Bağlan', 'api-isarud'); ?></h2>
                    <p><?php _e('isarud.com hesabınızla bu WordPress sitesini bağlayın.', 'api-isarud'); ?></p>
                    <ol>
                        <li><?php _e('<a href="https://isarud.com/login" target="_blank">isarud.com\'a giriş yapın</a>', 'api-isarud'); ?></li>
                        <li><?php _e('Account → API Keys sayfasından Bearer Token kopyalayın', 'api-isarud'); ?></li>
                        <li><?php _e('Aşağıya yapıştırın ve "Bağlan" butonuna tıklayın', 'api-isarud'); ?></li>
                    </ol>
                    <p>
                        <input type="text" id="isarud-cloud-token" class="regular-text" placeholder="Bearer Token (isarud.com API key)">
                        <button type="button" class="button-primary" id="isarud-cloud-connect"><?php _e('Bağlan', 'api-isarud'); ?></button>
                    </p>
                    <div id="isarud-cloud-result" style="margin-top:10px"></div>
                </div>
            <?php endif; ?>

            <div style="background:#f0f6fc;border:1px solid #c3c4c7;border-radius:8px;padding:20px;margin:15px 0;max-width:700px">
                <h3 style="margin-top:0"><?php _e('Cloud Sync Nedir?', 'api-isarud'); ?></h3>
                <ul style="list-style:disc;padding-left:20px;color:#666">
                    <li><?php _e('WooCommerce ürünleriniz ve siparişleriniz isarud.com\'da görünür', 'api-isarud'); ?></li>
                    <li><?php _e('Tüm pazar yeri verileriniz tek panelde birleşir', 'api-isarud'); ?></li>
                    <li><?php _e('iOS, Android, Windows, macOS uygulamalarından erişim', 'api-isarud'); ?></li>
                    <li><?php _e('Otomatik saatlik senkronizasyon', 'api-isarud'); ?></li>
                    <li><?php _e('Sipariş müşterileri otomatik yaptırım taramasından geçer', 'api-isarud'); ?></li>
                </ul>
            </div>
        </div>

        <script>
        jQuery(function($) {
            // Connect
            $('#isarud-cloud-connect').on('click', function() {
                var token = $('#isarud-cloud-token').val();
                if (!token) { alert('Token gerekli'); return; }
                var btn = $(this).prop('disabled', true).text('Bağlanıyor...');
                $.post(isarud.ajax, {
                    action: 'isarud_cloud_connect',
                    nonce: isarud.nonce,
                    token: token
                }, function(r) {
                    btn.prop('disabled', false).text('<?php _e('Bağlan', 'api-isarud'); ?>');
                    if (r.success) {
                        $('#isarud-cloud-result').html('<div class="notice notice-success"><p>' + r.data.message + '</p></div>');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        $('#isarud-cloud-result').html('<div class="notice notice-error"><p>' + (r.data?.message || r.data) + '</p></div>');
                    }
                });
            });

            // Sync Now
            $('#isarud-cloud-sync-now').on('click', function() {
                var btn = $(this).prop('disabled', true).text('Senkronize ediliyor...');
                $.post(isarud.ajax, {
                    action: 'isarud_cloud_sync_now',
                    nonce: isarud.nonce
                }, function(r) {
                    btn.prop('disabled', false).text('<?php _e('Şimdi Senkronize Et', 'api-isarud'); ?>');
                    if (r.success) {
                        var d = r.data;
                        $('#isarud-cloud-result').html(
                            '<div class="notice notice-success"><p>✅ Ürünler: ' + (d.products?.synced || 0) + ' sync, ' + (d.products?.failed || 0) + ' hata<br>' +
                            'Siparişler: ' + (d.orders?.synced || 0) + ' sync, ' + (d.orders?.failed || 0) + ' hata</p></div>'
                        );
                    } else {
                        $('#isarud-cloud-result').html('<div class="notice notice-error"><p>' + (r.data?.message || r.data) + '</p></div>');
                    }
                });
            });

            // Disconnect
            $('#isarud-cloud-disconnect').on('click', function() {
                if (!confirm('<?php _e('Bağlantıyı kesmek istediğinize emin misiniz?', 'api-isarud'); ?>')) return;
                $.post(isarud.ajax, {
                    action: 'isarud_cloud_disconnect',
                    nonce: isarud.nonce
                }, function(r) {
                    if (r.success) { location.reload(); }
                });
            });
        });
        </script>
        <?php
    }

    // ─── CLOUD AJAX HANDLERS ────────────────────
    public function ajax_cloud_connect() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $token = sanitize_text_field($_POST['token'] ?? '');
        if (empty($token)) wp_send_json_error(['message' => 'Token gerekli']);

        $cloud = Isarud_Cloud_Sync::instance();
        $result = $cloud->connect_site($token);

        if (!empty($result['success'])) {
            wp_send_json_success(['message' => 'isarud.com\'a bağlandı! Sayfa yenileniyor...']);
        } else {
            wp_send_json_error(['message' => $result['error'] ?? 'Bağlantı başarısız']);
        }
    }

    public function ajax_cloud_sync_now() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $cloud = Isarud_Cloud_Sync::instance();
        if (!$cloud->is_enabled()) wp_send_json_error(['message' => 'Cloud sync aktif değil']);

        $products = $cloud->sync_products();
        $orders = $cloud->sync_orders();

        wp_send_json_success([
            'products' => $products,
            'orders' => $orders,
        ]);
    }

    public function ajax_cloud_disconnect() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        Isarud_Cloud_Sync::instance()->disconnect();
        wp_send_json_success('Disconnected');
    }


    public function page_ecosystem() {
        include ISARUD_DIR . 'includes/ecosystem-html.php';
    }

    public function page_currency() {
        include ISARUD_DIR . 'includes/currency-html.php';
    }

    public function page_b2b() {
        include ISARUD_DIR . 'includes/b2b-html.php';
    }

    public function page_segments() {
        include ISARUD_DIR . 'includes/segments-html.php';
    }

    public function page_cart_recovery() {
        include ISARUD_DIR . 'includes/cart-recovery-html.php';
    }

    public function page_popup() {
        include ISARUD_DIR . 'includes/popup-html.php';
    }

    public function page_email_marketing() {
        include ISARUD_DIR . 'includes/email-marketing-html.php';
    }

    public function page_einvoice() {
        include ISARUD_DIR . 'includes/einvoice-html.php';
    }

    public function page_upsell() {
        include ISARUD_DIR . 'includes/upsell-html.php';
    }

    public function page_welcome() {
        include ISARUD_DIR . 'includes/welcome-html.php';
    }
}

if (!function_exists('esc_xml')) { function esc_xml(string $s): string { return htmlspecialchars($s, ENT_XML1, 'UTF-8'); } }
