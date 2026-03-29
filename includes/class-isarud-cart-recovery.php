<?php
if (!defined('ABSPATH')) exit;

class Isarud_Cart_Recovery {
    private static $inst = null;
    private $option_key = 'isarud_cart_recovery_settings';
    private $table_name;

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'isarud_abandoned_carts';

        if (!class_exists('WooCommerce')) return;
        $settings = $this->get_settings();
        if (!$settings['enabled']) return;

        add_action('woocommerce_cart_updated', [$this, 'capture_cart']);
        add_action('woocommerce_checkout_order_processed', [$this, 'mark_recovered'], 10, 1);
        add_action('isarud_send_cart_reminders', [$this, 'send_reminders']);
        add_action('wp_ajax_isarud_delete_cart', [$this, 'ajax_delete_cart']);
        add_action('wp_ajax_isarud_send_test_reminder', [$this, 'ajax_send_test']);

        if (!wp_next_scheduled('isarud_send_cart_reminders')) {
            wp_schedule_event(time(), 'hourly', 'isarud_send_cart_reminders');
        }
    }

    public function get_settings() {
        return wp_parse_args(get_option($this->option_key, []), [
            'enabled' => false,
            'abandon_timeout' => 60,
            'first_email_delay' => 1,
            'second_email_delay' => 24,
            'third_email_delay' => 72,
            'coupon_code' => '',
            'from_name' => get_bloginfo('name'),
            'from_email' => get_option('admin_email'),
            'subject_first' => __('Sepetinizde urunler var!', 'api-isarud'),
            'subject_second' => __('Sepetinizi unutmayin!', 'api-isarud'),
            'subject_third' => __('Son sans! Sepetiniz sizi bekliyor', 'api-isarud'),
            'enable_second' => true,
            'enable_third' => true,
            'capture_guests' => true,
        ]);
    }

    public function save_settings($data) {
        update_option($this->option_key, $data);
    }

    public function create_table() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT 0,
            email varchar(255) DEFAULT NULL,
            cart_data longtext NOT NULL,
            cart_total decimal(10,2) DEFAULT 0,
            item_count int DEFAULT 0,
            status varchar(20) DEFAULT 'abandoned',
            emails_sent int DEFAULT 0,
            last_email_at datetime DEFAULT NULL,
            recovered_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY email (email)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function capture_cart() {
        if (is_admin() || !WC()->cart || WC()->cart->is_empty()) return;

        $session_id = WC()->session ? WC()->session->get_customer_id() : '';
        if (empty($session_id)) return;

        $user_id = get_current_user_id();
        $email = $user_id ? wp_get_current_user()->user_email : (WC()->session ? WC()->session->get('billing_email') : '');

        $settings = $this->get_settings();
        if (!$settings['capture_guests'] && !$user_id) return;

        $cart_items = [];
        foreach (WC()->cart->get_cart() as $item) {
            $product = $item['data'];
            $cart_items[] = [
                'product_id' => $item['product_id'],
                'variation_id' => $item['variation_id'] ?? 0,
                'name' => $product->get_name(),
                'quantity' => $item['quantity'],
                'price' => $product->get_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'url' => $product->get_permalink(),
            ];
        }

        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE session_id = %s AND status = 'abandoned'",
            $session_id
        ));

        $data = [
            'session_id' => $session_id,
            'user_id' => $user_id,
            'email' => $email,
            'cart_data' => wp_json_encode($cart_items),
            'cart_total' => WC()->cart->get_total('edit'),
            'item_count' => WC()->cart->get_cart_contents_count(),
            'updated_at' => current_time('mysql'),
        ];

        if ($existing) {
            $wpdb->update($this->table_name, $data, ['id' => $existing->id]);
        } else {
            $data['status'] = 'abandoned';
            $data['emails_sent'] = 0;
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($this->table_name, $data);
        }
    }

    public function mark_recovered($order_id) {
        global $wpdb;
        $order = wc_get_order($order_id);
        if (!$order) return;

        $email = $order->get_billing_email();
        $user_id = $order->get_user_id();

        $where = $user_id
            ? $wpdb->prepare("(user_id = %d OR email = %s)", $user_id, $email)
            : $wpdb->prepare("email = %s", $email);

        $wpdb->query("UPDATE {$this->table_name} SET status = 'recovered', recovered_at = NOW() WHERE status = 'abandoned' AND {$where}");
    }

    public function send_reminders() {
        global $wpdb;
        $settings = $this->get_settings();
        if (!$settings['enabled']) return;

        $timeout = (int)$settings['abandon_timeout'];
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$timeout} minutes"));

        $carts = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE status = 'abandoned' AND email != '' AND email IS NOT NULL AND updated_at < '{$cutoff}' AND emails_sent < 3 ORDER BY updated_at ASC LIMIT 50"
        );

        foreach ($carts as $cart) {
            $delay_hours = 0;
            $subject = '';

            if ($cart->emails_sent == 0) {
                $delay_hours = (int)$settings['first_email_delay'];
                $subject = $settings['subject_first'];
            } elseif ($cart->emails_sent == 1 && $settings['enable_second']) {
                $delay_hours = (int)$settings['second_email_delay'];
                $subject = $settings['subject_second'];
            } elseif ($cart->emails_sent == 2 && $settings['enable_third']) {
                $delay_hours = (int)$settings['third_email_delay'];
                $subject = $settings['subject_third'];
            } else {
                continue;
            }

            $since_last = $cart->last_email_at
                ? (strtotime(current_time('mysql')) - strtotime($cart->last_email_at)) / 3600
                : (strtotime(current_time('mysql')) - strtotime($cart->updated_at)) / 3600;

            if ($since_last < $delay_hours) continue;

            $sent = $this->send_email($cart, $subject, $settings);
            if ($sent) {
                $wpdb->update($this->table_name, [
                    'emails_sent' => $cart->emails_sent + 1,
                    'last_email_at' => current_time('mysql'),
                ], ['id' => $cart->id]);
            }
        }
    }

    private function send_email($cart, $subject, $settings) {
        $items = json_decode($cart->cart_data, true);
        if (empty($items)) return false;

        $shop_url = wc_get_cart_url();
        $shop_name = get_bloginfo('name');

        $html = '<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif">';
        $html .= '<div style="background:#358a4f;padding:20px;text-align:center;border-radius:8px 8px 0 0">';
        $html .= '<h1 style="color:#fff;margin:0;font-size:20px">' . esc_html($shop_name) . '</h1></div>';
        $html .= '<div style="padding:24px;background:#fff;border:1px solid #e5e7eb">';
        $html .= '<p style="font-size:15px;color:#333">' . __('Merhaba,', 'api-isarud') . '</p>';
        $html .= '<p style="font-size:14px;color:#555">' . __('Sepetinizde asagidaki urunler bekliyor:', 'api-isarud') . '</p>';
        $html .= '<table style="width:100%;border-collapse:collapse;margin:16px 0">';

        foreach ($items as $item) {
            $html .= '<tr style="border-bottom:1px solid #f0f0f0">';
            if (!empty($item['image'])) {
                $html .= '<td style="padding:8px;width:60px"><img src="' . esc_url($item['image']) . '" width="50" height="50" style="border-radius:6px;object-fit:cover"></td>';
            }
            $html .= '<td style="padding:8px"><strong style="font-size:13px;color:#333">' . esc_html($item['name']) . '</strong><br><span style="font-size:12px;color:#888">' . __('Adet:', 'api-isarud') . ' ' . $item['quantity'] . '</span></td>';
            $html .= '<td style="padding:8px;text-align:right;font-size:13px;color:#333">' . wc_price($item['price'] * $item['quantity']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '<p style="font-size:15px;font-weight:bold;color:#333">' . __('Toplam:', 'api-isarud') . ' ' . wc_price($cart->cart_total) . '</p>';

        if (!empty($settings['coupon_code']) && $cart->emails_sent >= 1) {
            $html .= '<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:12px;margin:16px 0;text-align:center">';
            $html .= '<p style="margin:0;font-size:13px;color:#92400e">' . __('Ozel indirim kodunuz:', 'api-isarud') . '</p>';
            $html .= '<p style="margin:4px 0 0;font-size:18px;font-weight:bold;color:#92400e">' . esc_html($settings['coupon_code']) . '</p>';
            $html .= '</div>';
        }

        $html .= '<div style="text-align:center;margin:20px 0">';
        $html .= '<a href="' . esc_url($shop_url) . '" style="display:inline-block;background:#358a4f;color:#fff;padding:12px 32px;text-decoration:none;border-radius:8px;font-size:14px;font-weight:bold">' . __('Sepetime Don', 'api-isarud') . '</a>';
        $html .= '</div></div>';
        $html .= '<div style="padding:12px;text-align:center;font-size:11px;color:#aaa;border-radius:0 0 8px 8px">';
        $html .= esc_html($shop_name) . '</div></div>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>',
        ];

        return wp_mail($cart->email, $subject, $html, $headers);
    }

    public function get_stats() {
        global $wpdb;
        if (!$this->table_exists()) return ['abandoned' => 0, 'recovered' => 0, 'pending' => 0, 'revenue' => 0, 'rate' => 0];

        $abandoned = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'abandoned'");
        $recovered = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'recovered'");
        $revenue = (float)$wpdb->get_var("SELECT COALESCE(SUM(cart_total), 0) FROM {$this->table_name} WHERE status = 'recovered'");
        $total = $abandoned + $recovered;
        $rate = $total > 0 ? round($recovered / $total * 100, 1) : 0;

        return ['abandoned' => $abandoned, 'recovered' => $recovered, 'pending' => $abandoned, 'revenue' => $revenue, 'rate' => $rate];
    }

    public function get_carts($status = 'abandoned', $limit = 20) {
        global $wpdb;
        if (!$this->table_exists()) return [];
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE status = %s ORDER BY updated_at DESC LIMIT %d",
            $status, $limit
        ));
    }

    private function table_exists() {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
    }

    public function ajax_delete_cart() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $id = intval($_POST['cart_id'] ?? 0);
        $wpdb->delete($this->table_name, ['id' => $id]);
        wp_send_json_success();
    }

    public function ajax_send_test() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $email = sanitize_email($_POST['test_email'] ?? '');
        if (!$email) wp_send_json_error(__('Gecerli e-posta girin', 'api-isarud'));

        $settings = $this->get_settings();
        $cart = (object)[
            'email' => $email, 'cart_total' => 299.90, 'emails_sent' => 0,
            'cart_data' => wp_json_encode([['product_id' => 1, 'name' => 'Ornek Urun', 'quantity' => 2, 'price' => 149.95, 'image' => '', 'url' => '#']]),
        ];
        $sent = $this->send_email($cart, $settings['subject_first'], $settings);
        $sent ? wp_send_json_success(__('Test e-postasi gonderildi', 'api-isarud')) : wp_send_json_error(__('E-posta gonderilemedi', 'api-isarud'));
    }
}

Isarud_Cart_Recovery::instance();
