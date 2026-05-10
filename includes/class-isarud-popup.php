<?php
if (!defined('ABSPATH')) exit;

class Isarud_Popup {
    private static $inst = null;
    private $option_key = 'isarud_popup_settings';
    private $campaigns_key = 'isarud_popup_campaigns';

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        if (!class_exists('WooCommerce')) return;
        $settings = $this->get_settings();
        if (!$settings['enabled']) return;

        add_action('wp_footer', [$this, 'render_popups']);
        add_action('wp_ajax_isarud_popup_impression', [$this, 'track_impression']);
        add_action('wp_ajax_nopriv_isarud_popup_impression', [$this, 'track_impression']);
        add_action('wp_ajax_isarud_save_campaign', [$this, 'ajax_save_campaign']);
        add_action('wp_ajax_isarud_delete_campaign', [$this, 'ajax_delete_campaign']);
        add_action('wp_ajax_isarud_toggle_campaign', [$this, 'ajax_toggle_campaign']);
    }

    public function get_settings() {
        return wp_parse_args(get_option($this->option_key, []), [
            'enabled' => false,
            'hide_on_mobile' => false,
            'cookie_duration' => 7,
        ]);
    }

    public function save_settings($data) { update_option($this->option_key, $data); }

    public function get_campaigns() {
        return get_option($this->campaigns_key, []);
    }

    public function save_campaigns($campaigns) {
        update_option($this->campaigns_key, $campaigns);
    }

    public function render_popups() {
        if (is_admin()) return;
        $campaigns = $this->get_campaigns();
        $settings = $this->get_settings();
        $active = array_filter($campaigns, fn($c) => !empty($c['active']));
        if (empty($active)) return;

        foreach ($active as $id => $c) {
            $trigger = $c['trigger'] ?? 'exit_intent';
            $delay = (int)($c['delay'] ?? 5);
            $cookie_name = 'isarud_popup_' . $id;
            ?>
            <div id="isarud-popup-<?php echo esc_attr($id); ?>" class="isarud-popup-overlay" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center" data-trigger="<?php echo esc_attr($trigger); ?>" data-delay="<?php echo $delay; ?>" data-cookie="<?php echo esc_attr($cookie_name); ?>" data-cookie-days="<?php echo (int)$settings['cookie_duration']; ?>">
                <div style="background:#fff;border-radius:16px;max-width:480px;width:90%;padding:32px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:isarudPopIn 0.3s ease">
                    <button onclick="isarudClosePopup('<?php echo esc_attr($id); ?>')" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:24px;color:#999;cursor:pointer">&times;</button>
                    <?php if (!empty($c['title'])): ?>
                    <h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1a1a2e"><?php echo esc_html($c['title']); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($c['message'])): ?>
                    <p style="margin:0 0 16px;font-size:14px;color:#555;line-height:1.5"><?php echo esc_html($c['message']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($c['coupon'])): ?>
                    <div style="background:#f0fdf4;border:2px dashed #358a4f;border-radius:10px;padding:14px;text-align:center;margin-bottom:16px">
                        <span style="font-size:11px;color:#358a4f;text-transform:uppercase;letter-spacing:1px"><?php _e('Indirim Kodunuz', 'api-isarud'); ?></span>
                        <div style="font-size:24px;font-weight:800;color:#358a4f;margin-top:4px;letter-spacing:2px"><?php echo esc_html($c['coupon']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($c['button_text']) && !empty($c['button_url'])): ?>
                    <a href="<?php echo esc_url($c['button_url']); ?>" style="display:block;text-align:center;background:<?php echo esc_attr($c['button_color'] ?? '#358a4f'); ?>;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600"><?php echo esc_html($c['button_text']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
        ?>
        <style>
        @keyframes isarudPopIn { from { opacity:0;transform:scale(0.9); } to { opacity:1;transform:scale(1); } }
        </style>
        <script>
        (function(){
            function getCookie(n){return(document.cookie.match('(^|;)\\s*'+n+'\\s*=\\s*([^;]+)')||[])[2]||'';}
            function setCookie(n,v,d){var e=new Date();e.setTime(e.getTime()+d*864e5);document.cookie=n+"="+v+";expires="+e.toUTCString()+";path=/";}
            document.querySelectorAll('.isarud-popup-overlay').forEach(function(el){
                var id=el.id.replace('isarud-popup-',''), t=el.dataset.trigger, d=parseInt(el.dataset.delay)*1000, ck=el.dataset.cookie, cd=parseInt(el.dataset.cookieDays);
                if(getCookie(ck)) return;
                <?php if ($settings['hide_on_mobile']): ?>if(window.innerWidth<768) return;<?php endif; ?>
                function show(){el.style.display='flex';setCookie(ck,'1',cd);try{fetch(isarud.ajax+'?action=isarud_popup_impression&nonce='+isarud.nonce+'&campaign_id='+id)}catch(e){}}
                if(t==='exit_intent'){document.addEventListener('mouseout',function(e){if(e.clientY<0){show();document.removeEventListener('mouseout',arguments.callee);}});}
                else if(t==='timed'){setTimeout(show,d);}
                else if(t==='scroll'){var fired=false;window.addEventListener('scroll',function(){if(!fired&&window.scrollY>document.body.scrollHeight*0.5){fired=true;show();}});}
                else if(t==='add_to_cart'){jQuery&&jQuery(document.body).on('added_to_cart',function(){show();});}
            });
            window.isarudClosePopup=function(id){document.getElementById('isarud-popup-'+id).style.display='none';};
        })();
        </script>
        <?php
    }

    public function track_impression() {
        $id = sanitize_text_field($_GET['campaign_id'] ?? '');
        if (!$id) wp_die();
        $campaigns = $this->get_campaigns();
        if (isset($campaigns[$id])) {
            $campaigns[$id]['impressions'] = ($campaigns[$id]['impressions'] ?? 0) + 1;
            $this->save_campaigns($campaigns);
        }
        wp_die();
    }

    public function ajax_save_campaign() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $id = sanitize_text_field($_POST['campaign_id'] ?? '') ?: 'camp_' . wp_generate_password(8, false);
        $campaigns = $this->get_campaigns();
        $campaigns[$id] = [
            'title' => sanitize_text_field($_POST['popup_title'] ?? ''),
            'message' => sanitize_textarea_field($_POST['popup_message'] ?? ''),
            'coupon' => sanitize_text_field($_POST['popup_coupon'] ?? ''),
            'button_text' => sanitize_text_field($_POST['button_text'] ?? ''),
            'button_url' => esc_url_raw($_POST['button_url'] ?? ''),
            'button_color' => sanitize_hex_color($_POST['button_color'] ?? '#358a4f'),
            'trigger' => sanitize_text_field($_POST['popup_trigger'] ?? 'exit_intent'),
            'delay' => intval($_POST['popup_delay'] ?? 5),
            'active' => !empty($_POST['popup_active']),
            'impressions' => $campaigns[$id]['impressions'] ?? 0,
            'created_at' => $campaigns[$id]['created_at'] ?? current_time('mysql'),
        ];
        $this->save_campaigns($campaigns);
        wp_send_json_success(['id' => $id]);
    }

    public function ajax_delete_campaign() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $id = sanitize_text_field($_POST['campaign_id'] ?? '');
        $campaigns = $this->get_campaigns();
        unset($campaigns[$id]);
        $this->save_campaigns($campaigns);
        wp_send_json_success();
    }

    public function ajax_toggle_campaign() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $id = sanitize_text_field($_POST['campaign_id'] ?? '');
        $campaigns = $this->get_campaigns();
        if (isset($campaigns[$id])) {
            $campaigns[$id]['active'] = !$campaigns[$id]['active'];
            $this->save_campaigns($campaigns);
        }
        wp_send_json_success();
    }
}

Isarud_Popup::instance();
