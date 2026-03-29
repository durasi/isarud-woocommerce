<?php
if (!defined('ABSPATH')) exit;

class Isarud_Segments {
    private static $inst = null;
    private $option_key = 'isarud_segment_settings';

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        if (!class_exists('WooCommerce')) return;
        add_action('wp_ajax_isarud_refresh_segments', [$this, 'ajax_refresh']);
    }

    public function get_settings() {
        return wp_parse_args(get_option($this->option_key, []), [
            'enabled' => true,
            'vip_threshold' => 5,
            'vip_amount' => 5000,
            'at_risk_days' => 90,
            'lost_days' => 180,
            'last_refresh' => '',
        ]);
    }

    public function save_settings($data) {
        update_option($this->option_key, $data);
    }

    public function analyze() {
        global $wpdb;
        $settings = $this->get_settings();
        $now = current_time('timestamp');

        $customers_raw = $wpdb->get_results("
            SELECT
                pm.meta_value as email,
                COUNT(p.ID) as order_count,
                SUM(pm2.meta_value) as total_spent,
                MAX(p.post_date) as last_order,
                MIN(p.post_date) as first_order
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_billing_email'
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed','wc-processing')
            GROUP BY pm.meta_value
            ORDER BY total_spent DESC
        ");

        if (empty($customers_raw)) {
            $customers_raw = $wpdb->get_results("
                SELECT
                    om.email as email,
                    COUNT(o.id) as order_count,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.date_created_gmt) as last_order,
                    MIN(o.date_created_gmt) as first_order
                FROM {$wpdb->prefix}wc_orders o
                JOIN {$wpdb->prefix}wc_order_addresses om ON o.id = om.order_id AND om.address_type = 'billing'
                WHERE o.type = 'shop_order'
                AND o.status IN ('wc-completed','wc-processing')
                GROUP BY om.email
                ORDER BY total_spent DESC
            ");
        }

        $segments = [
            'vip' => ['label' => __('VIP Musteriler', 'api-isarud'), 'color' => '#185fa5', 'customers' => []],
            'loyal' => ['label' => __('Sadik Musteriler', 'api-isarud'), 'color' => '#0f6e56', 'customers' => []],
            'new' => ['label' => __('Yeni Musteriler', 'api-isarud'), 'color' => '#534ab7', 'customers' => []],
            'at_risk' => ['label' => __('Risk Altinda', 'api-isarud'), 'color' => '#ba7517', 'customers' => []],
            'lost' => ['label' => __('Kaybedilen', 'api-isarud'), 'color' => '#a32d2d', 'customers' => []],
            'one_time' => ['label' => __('Tek Seferlik', 'api-isarud'), 'color' => '#888780', 'customers' => []],
        ];

        $total_customers = count($customers_raw);
        $total_revenue = 0;

        foreach ($customers_raw as $c) {
            $days_since = ($now - strtotime($c->last_order)) / 86400;
            $days_since_first = ($now - strtotime($c->first_order)) / 86400;
            $order_count = (int)$c->order_count;
            $total_spent = (float)$c->total_spent;
            $total_revenue += $total_spent;
            $avg_order = $order_count > 0 ? $total_spent / $order_count : 0;

            $customer = [
                'email' => $c->email,
                'orders' => $order_count,
                'spent' => $total_spent,
                'avg_order' => $avg_order,
                'last_order' => $c->last_order,
                'days_since' => round($days_since),
            ];

            if ($order_count >= $settings['vip_threshold'] && $total_spent >= $settings['vip_amount']) {
                $segments['vip']['customers'][] = $customer;
            } elseif ($days_since > $settings['lost_days']) {
                $segments['lost']['customers'][] = $customer;
            } elseif ($days_since > $settings['at_risk_days']) {
                $segments['at_risk']['customers'][] = $customer;
            } elseif ($order_count >= 3) {
                $segments['loyal']['customers'][] = $customer;
            } elseif ($days_since_first < 30 && $order_count <= 2) {
                $segments['new']['customers'][] = $customer;
            } elseif ($order_count === 1) {
                $segments['one_time']['customers'][] = $customer;
            } else {
                $segments['loyal']['customers'][] = $customer;
            }
        }

        $settings['last_refresh'] = current_time('mysql');
        $this->save_settings($settings);

        return [
            'segments' => $segments,
            'total_customers' => $total_customers,
            'total_revenue' => $total_revenue,
            'avg_order_value' => $total_customers > 0 ? $total_revenue / array_sum(array_map(function($s) { return count($s['customers']); }, $segments)) : 0,
        ];
    }

    public function ajax_refresh() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $result = $this->analyze();
        wp_send_json_success($result);
    }
}

Isarud_Segments::instance();
