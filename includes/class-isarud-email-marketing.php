<?php
if (!defined('ABSPATH')) exit;

class Isarud_Email_Marketing {
    private static $inst = null;
    private $option_key = 'isarud_email_marketing_settings';
    private $log_key = 'isarud_email_log';

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        if (!class_exists('WooCommerce')) return;
        $settings = $this->get_settings();
        if (!$settings['enabled']) return;

        if ($settings['welcome_enabled']) {
            add_action('user_register', [$this, 'send_welcome'], 20);
        }
        if ($settings['post_purchase_enabled']) {
            add_action('woocommerce_order_status_completed', [$this, 'schedule_post_purchase'], 10, 1);
        }
        if ($settings['winback_enabled']) {
            add_action('isarud_winback_emails', [$this, 'send_winback_emails']);
            if (!wp_next_scheduled('isarud_winback_emails')) {
                wp_schedule_event(time(), 'daily', 'isarud_winback_emails');
            }
        }
        if ($settings['review_request_enabled']) {
            add_action('woocommerce_order_status_completed', [$this, 'schedule_review_request'], 20, 1);
        }
    }

    public function get_settings() {
        return wp_parse_args(get_option($this->option_key, []), [
            'enabled' => false,
            'from_name' => get_bloginfo('name'),
            'from_email' => get_option('admin_email'),
            'welcome_enabled' => true,
            'welcome_subject' => __('Hosgeldiniz! Ilk siparisine ozel indirim', 'api-isarud'),
            'welcome_coupon' => '',
            'welcome_delay' => 0,
            'post_purchase_enabled' => true,
            'post_purchase_subject' => __('Siparisini sevdin mi? Sana ozel oneriler', 'api-isarud'),
            'post_purchase_delay' => 3,
            'review_request_enabled' => true,
            'review_request_subject' => __('Urunumuzu degerlendirir misiniz?', 'api-isarud'),
            'review_request_delay' => 7,
            'winback_enabled' => true,
            'winback_subject' => __('Sizi ozledik! Geri donun', 'api-isarud'),
            'winback_days' => 60,
            'winback_coupon' => '',
        ]);
    }

    public function save_settings($data) { update_option($this->option_key, $data); }

    private function log_email($type, $email, $subject) {
        $log = get_option($this->log_key, []);
        array_unshift($log, [
            'type' => $type, 'email' => $email, 'subject' => $subject,
            'sent_at' => current_time('mysql'),
        ]);
        $log = array_slice($log, 0, 200);
        update_option($this->log_key, $log);
    }

    public function get_log($limit = 20) {
        return array_slice(get_option($this->log_key, []), 0, $limit);
    }

    public function get_stats() {
        $log = get_option($this->log_key, []);
        $stats = ['welcome' => 0, 'post_purchase' => 0, 'review' => 0, 'winback' => 0, 'total' => count($log)];
        foreach ($log as $entry) {
            if (isset($stats[$entry['type']])) $stats[$entry['type']]++;
        }
        return $stats;
    }

    public function send_welcome($user_id) {
        $user = get_userdata($user_id);
        if (!$user || !$user->user_email) return;
        $settings = $this->get_settings();

        $delay = (int)$settings['welcome_delay'];
        if ($delay > 0) {
            wp_schedule_single_event(time() + ($delay * 3600), 'isarud_send_welcome_delayed', [$user_id]);
            add_action('isarud_send_welcome_delayed', [$this, 'send_welcome_now']);
            return;
        }
        $this->send_welcome_now($user_id);
    }

    public function send_welcome_now($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        $settings = $this->get_settings();
        $shop_name = get_bloginfo('name');
        $shop_url = wc_get_page_permalink('shop');

        $html = $this->email_header($shop_name);
        $html .= '<h2 style="margin:0 0 12px;font-size:18px;color:#1a1a2e">' . sprintf(__('Hosgeldiniz %s!', 'api-isarud'), esc_html($user->display_name ?: $user->first_name)) . '</h2>';
        $html .= '<p style="font-size:14px;color:#555;line-height:1.6">' . sprintf(__('%s ailesine katildiginiz icin tesekkur ederiz. Sizin icin en iyi urunleri hazirladik.', 'api-isarud'), esc_html($shop_name)) . '</p>';

        if (!empty($settings['welcome_coupon'])) {
            $html .= $this->coupon_block($settings['welcome_coupon'], __('Ilk siparisine ozel indirim kodu:', 'api-isarud'));
        }

        $html .= $this->cta_button($shop_url, __('Alisverise Basla', 'api-isarud'));
        $html .= $this->email_footer($shop_name);

        $sent = $this->send($user->user_email, $settings['welcome_subject'], $html, $settings);
        if ($sent) $this->log_email('welcome', $user->user_email, $settings['welcome_subject']);
    }

    public function schedule_post_purchase($order_id) {
        $settings = $this->get_settings();
        $delay = max(1, (int)$settings['post_purchase_delay']) * 86400;
        wp_schedule_single_event(time() + $delay, 'isarud_post_purchase_email', [$order_id]);
        add_action('isarud_post_purchase_email', [$this, 'send_post_purchase']);
    }

    public function send_post_purchase($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $settings = $this->get_settings();
        $email = $order->get_billing_email();
        $name = $order->get_billing_first_name();
        $shop_name = get_bloginfo('name');

        $html = $this->email_header($shop_name);
        $html .= '<h2 style="margin:0 0 12px;font-size:18px;color:#1a1a2e">' . sprintf(__('Merhaba %s!', 'api-isarud'), esc_html($name)) . '</h2>';
        $html .= '<p style="font-size:14px;color:#555;line-height:1.6">' . __('Son siparisini umariz begendiniz! Sana ozel sectigimiz urunlere goz atmanizi oneririz.', 'api-isarud') . '</p>';

        $related = $this->get_related_products($order, 4);
        if (!empty($related)) {
            $html .= '<table style="width:100%;border-collapse:collapse;margin:16px 0">';
            foreach ($related as $p) {
                $html .= '<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:8px">';
                $img = wp_get_attachment_url($p->get_image_id());
                if ($img) $html .= '<img src="' . esc_url($img) . '" width="50" height="50" style="border-radius:6px;object-fit:cover;vertical-align:middle;margin-right:8px">';
                $html .= '<a href="' . esc_url($p->get_permalink()) . '" style="color:#333;text-decoration:none;font-size:13px;font-weight:600">' . esc_html($p->get_name()) . '</a>';
                $html .= '</td><td style="padding:8px;text-align:right;font-size:13px;color:#333">' . $p->get_price_html() . '</td></tr>';
            }
            $html .= '</table>';
        }

        $html .= $this->cta_button(wc_get_page_permalink('shop'), __('Daha Fazla Urun', 'api-isarud'));
        $html .= $this->email_footer($shop_name);

        $sent = $this->send($email, $settings['post_purchase_subject'], $html, $settings);
        if ($sent) $this->log_email('post_purchase', $email, $settings['post_purchase_subject']);
    }

    public function schedule_review_request($order_id) {
        $settings = $this->get_settings();
        $delay = max(1, (int)$settings['review_request_delay']) * 86400;
        wp_schedule_single_event(time() + $delay, 'isarud_review_request_email', [$order_id]);
        add_action('isarud_review_request_email', [$this, 'send_review_request']);
    }

    public function send_review_request($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $settings = $this->get_settings();
        $email = $order->get_billing_email();
        $name = $order->get_billing_first_name();
        $shop_name = get_bloginfo('name');

        $items = $order->get_items();
        $first_item = reset($items);
        $product = $first_item ? $first_item->get_product() : null;

        $html = $this->email_header($shop_name);
        $html .= '<h2 style="margin:0 0 12px;font-size:18px;color:#1a1a2e">' . sprintf(__('Merhaba %s!', 'api-isarud'), esc_html($name)) . '</h2>';
        $html .= '<p style="font-size:14px;color:#555;line-height:1.6">' . __('Son siparisinizdeki urunlerimizi nasil buldunuz? Degerli goruslerinizi paylasmaniz diger musterilerimize yardimci olacaktir.', 'api-isarud') . '</p>';

        if ($product) {
            $html .= $this->cta_button($product->get_permalink() . '#reviews', __('Yorum Yaz', 'api-isarud'));
        }

        $html .= $this->email_footer($shop_name);

        $sent = $this->send($email, $settings['review_request_subject'], $html, $settings);
        if ($sent) $this->log_email('review', $email, $settings['review_request_subject']);
    }

    public function send_winback_emails() {
        $settings = $this->get_settings();
        if (!$settings['winback_enabled']) return;
        $days = (int)$settings['winback_days'];
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));
        $shop_name = get_bloginfo('name');
        $shop_url = wc_get_page_permalink('shop');

        $customers = get_users([
            'role' => 'customer',
            'meta_query' => [['key' => 'last_update', 'value' => $cutoff, 'compare' => '<', 'type' => 'DATE']],
            'number' => 20,
        ]);

        foreach ($customers as $user) {
            $already = get_user_meta($user->ID, '_isarud_winback_sent', true);
            if ($already) continue;

            $html = $this->email_header($shop_name);
            $html .= '<h2 style="margin:0 0 12px;font-size:18px;color:#1a1a2e">' . sprintf(__('Sizi ozledik %s!', 'api-isarud'), esc_html($user->display_name ?: $user->first_name)) . '</h2>';
            $html .= '<p style="font-size:14px;color:#555;line-height:1.6">' . sprintf(__('Uzun zamandir %s\'i ziyaret etmediniz. Sizin icin yeni urunler ekledik!', 'api-isarud'), esc_html($shop_name)) . '</p>';

            if (!empty($settings['winback_coupon'])) {
                $html .= $this->coupon_block($settings['winback_coupon'], __('Size ozel geri donus indirimi:', 'api-isarud'));
            }

            $html .= $this->cta_button($shop_url, __('Magazayi Ziyaret Et', 'api-isarud'));
            $html .= $this->email_footer($shop_name);

            $sent = $this->send($user->user_email, $settings['winback_subject'], $html, $settings);
            if ($sent) {
                $this->log_email('winback', $user->user_email, $settings['winback_subject']);
                update_user_meta($user->ID, '_isarud_winback_sent', current_time('mysql'));
            }
        }
    }

    private function get_related_products($order, $limit = 4) {
        $cat_ids = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) $cat_ids = array_merge($cat_ids, $product->get_category_ids());
        }
        $cat_ids = array_unique($cat_ids);
        if (empty($cat_ids)) return [];

        $ordered_ids = [];
        foreach ($order->get_items() as $item) $ordered_ids[] = $item->get_product_id();

        return wc_get_products([
            'limit' => $limit, 'status' => 'publish', 'category' => $cat_ids,
            'exclude' => $ordered_ids, 'orderby' => 'rand',
        ]);
    }

    private function send($to, $subject, $html, $settings) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>',
        ];
        return wp_mail($to, $subject, $html, $headers);
    }

    private function email_header($shop_name) {
        return '<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif"><div style="background:#358a4f;padding:20px;text-align:center;border-radius:8px 8px 0 0"><h1 style="color:#fff;margin:0;font-size:20px">' . esc_html($shop_name) . '</h1></div><div style="padding:24px;background:#fff;border:1px solid #e5e7eb">';
    }

    private function email_footer($shop_name) {
        return '</div><div style="padding:12px;text-align:center;font-size:11px;color:#aaa;border-radius:0 0 8px 8px">' . esc_html($shop_name) . '</div></div>';
    }

    private function coupon_block($code, $label) {
        return '<div style="background:#f0fdf4;border:2px dashed #358a4f;border-radius:10px;padding:14px;text-align:center;margin:16px 0"><span style="font-size:11px;color:#358a4f">' . esc_html($label) . '</span><div style="font-size:24px;font-weight:800;color:#358a4f;margin-top:4px;letter-spacing:2px">' . esc_html($code) . '</div></div>';
    }

    private function cta_button($url, $text) {
        return '<div style="text-align:center;margin:20px 0"><a href="' . esc_url($url) . '" style="display:inline-block;background:#358a4f;color:#fff;padding:12px 32px;text-decoration:none;border-radius:8px;font-size:14px;font-weight:bold">' . esc_html($text) . '</a></div>';
    }
}

Isarud_Email_Marketing::instance();
