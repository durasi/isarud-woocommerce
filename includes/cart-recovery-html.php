<?php
if (!defined('ABSPATH')) exit;
$cr = Isarud_Cart_Recovery::instance();
$settings = $cr->get_settings();

if (isset($_POST['isarud_save_cart_recovery']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_cart_recovery')) {
    $new = [
        'enabled' => !empty($_POST['enabled']),
        'abandon_timeout' => intval($_POST['abandon_timeout'] ?? 60),
        'first_email_delay' => intval($_POST['first_email_delay'] ?? 1),
        'second_email_delay' => intval($_POST['second_email_delay'] ?? 24),
        'third_email_delay' => intval($_POST['third_email_delay'] ?? 72),
        'coupon_code' => sanitize_text_field($_POST['coupon_code'] ?? ''),
        'from_name' => sanitize_text_field($_POST['from_name'] ?? get_bloginfo('name')),
        'from_email' => sanitize_email($_POST['from_email'] ?? get_option('admin_email')),
        'subject_first' => sanitize_text_field($_POST['subject_first'] ?? ''),
        'subject_second' => sanitize_text_field($_POST['subject_second'] ?? ''),
        'subject_third' => sanitize_text_field($_POST['subject_third'] ?? ''),
        'enable_second' => !empty($_POST['enable_second']),
        'enable_third' => !empty($_POST['enable_third']),
        'capture_guests' => !empty($_POST['capture_guests']),
    ];
    $cr->save_settings($new);
    $cr->create_table();
    $settings = $new;
    echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'api-isarud') . '</p></div>';
}

$stats = $cr->get_stats();
$carts = $cr->get_carts('abandoned', 15);
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div>
            <div class="isd-title"><?php _e('Sepet Hatirlatma', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Terk edilen sepet kurtarma otomasyonu', 'api-isarud'); ?></div>
        </div>
    </div>

    <div class="isd-metrics" style="margin-bottom:20px">
        <div class="isd-metric"><div class="isd-metric-val amber"><?php echo $stats['abandoned']; ?></div><div class="isd-metric-label"><?php _e('Terk Edilen', 'api-isarud'); ?></div></div>
        <div class="isd-metric"><div class="isd-metric-val green"><?php echo $stats['recovered']; ?></div><div class="isd-metric-label"><?php _e('Kurtarilan', 'api-isarud'); ?></div></div>
        <div class="isd-metric"><div class="isd-metric-val blue"><?php echo wc_price($stats['revenue']); ?></div><div class="isd-metric-label"><?php _e('Kurtarilan Gelir', 'api-isarud'); ?></div></div>
        <div class="isd-metric"><div class="isd-metric-val blue"><?php echo $stats['rate']; ?>%</div><div class="isd-metric-label"><?php _e('Kurtarma Orani', 'api-isarud'); ?></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="isd-activity">
            <h3><?php _e('E-posta Ayarlari', 'api-isarud'); ?></h3>
            <form method="post">
                <?php wp_nonce_field('isarud_cart_recovery'); ?>
                <table class="form-table" style="margin:0">
                    <tr><th style="padding:8px 0;width:160px"><?php _e('Aktif', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> <?php _e('Sepet hatirlatma modulunu etkinlestir', 'api-isarud'); ?></label></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('Bekleme Suresi', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><input type="number" name="abandon_timeout" value="<?php echo esc_attr($settings['abandon_timeout']); ?>" min="15" style="width:80px"> <?php _e('dakika (sepet terk sayilma suresi)', 'api-isarud'); ?></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('1. E-posta', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><input type="number" name="first_email_delay" value="<?php echo esc_attr($settings['first_email_delay']); ?>" min="1" style="width:60px"> <?php _e('saat sonra', 'api-isarud'); ?>
                            <br><input type="text" name="subject_first" value="<?php echo esc_attr($settings['subject_first']); ?>" style="width:100%;margin-top:4px" placeholder="<?php _e('Konu', 'api-isarud'); ?>"></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('2. E-posta', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><label><input type="checkbox" name="enable_second" value="1" <?php checked($settings['enable_second']); ?>> <?php _e('Aktif', 'api-isarud'); ?></label>
                            <input type="number" name="second_email_delay" value="<?php echo esc_attr($settings['second_email_delay']); ?>" min="1" style="width:60px;margin-left:8px"> <?php _e('saat sonra', 'api-isarud'); ?>
                            <br><input type="text" name="subject_second" value="<?php echo esc_attr($settings['subject_second']); ?>" style="width:100%;margin-top:4px"></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('3. E-posta', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><label><input type="checkbox" name="enable_third" value="1" <?php checked($settings['enable_third']); ?>> <?php _e('Aktif', 'api-isarud'); ?></label>
                            <input type="number" name="third_email_delay" value="<?php echo esc_attr($settings['third_email_delay']); ?>" min="1" style="width:60px;margin-left:8px"> <?php _e('saat sonra', 'api-isarud'); ?>
                            <br><input type="text" name="subject_third" value="<?php echo esc_attr($settings['subject_third']); ?>" style="width:100%;margin-top:4px"></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('Kupon Kodu', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><input type="text" name="coupon_code" value="<?php echo esc_attr($settings['coupon_code']); ?>" style="width:200px" placeholder="SEPET10">
                            <p class="description"><?php _e('2. ve 3. e-postalarda gosterilir (bos birakirsaniz gosterilmez)', 'api-isarud'); ?></p></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('Misafir Sepetleri', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><label><input type="checkbox" name="capture_guests" value="1" <?php checked($settings['capture_guests']); ?>> <?php _e('Giris yapmamis ziyaretcilerin sepetlerini de yakala', 'api-isarud'); ?></label></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('Gonderen', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><input type="text" name="from_name" value="<?php echo esc_attr($settings['from_name']); ?>" style="width:48%"> <input type="email" name="from_email" value="<?php echo esc_attr($settings['from_email']); ?>" style="width:48%"></td></tr>
                </table>
                <p style="margin-top:12px"><button type="submit" name="isarud_save_cart_recovery" class="button-primary"><?php _e('Kaydet', 'api-isarud'); ?></button></p>
            </form>
        </div>

        <div>
            <div class="isd-activity" style="margin-bottom:16px">
                <h3><?php _e('Test E-postasi', 'api-isarud'); ?></h3>
                <div style="display:flex;gap:8px">
                    <input type="email" id="isarud-test-email" placeholder="test@ornek.com" style="flex:1;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px">
                    <button class="button" id="isarud-send-test"><?php _e('Gonder', 'api-isarud'); ?></button>
                </div>
                <div id="isarud-test-result" style="margin-top:8px"></div>
            </div>

            <?php if (!empty($carts)): ?>
            <div class="isd-activity">
                <h3><?php _e('Terk Edilen Sepetler', 'api-isarud'); ?></h3>
                <div style="max-height:300px;overflow-y:auto">
                    <table class="wp-list-table widefat fixed striped" style="font-size:12px">
                        <thead><tr><th><?php _e('E-posta', 'api-isarud'); ?></th><th style="width:70px"><?php _e('Tutar', 'api-isarud'); ?></th><th style="width:50px"><?php _e('Mail', 'api-isarud'); ?></th><th style="width:70px"><?php _e('Zaman', 'api-isarud'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($carts as $c): ?>
                        <tr>
                            <td><?php echo esc_html($c->email ?: '—'); ?></td>
                            <td><?php echo wc_price($c->cart_total); ?></td>
                            <td><?php echo $c->emails_sent; ?>/3</td>
                            <td style="color:#888"><?php echo human_time_diff(strtotime($c->updated_at)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="isd-activity"><p class="isd-empty"><?php _e('Henuz terk edilen sepet yok.', 'api-isarud'); ?></p></div>
            <?php endif; ?>
        </div>
    </div>

    <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
        <?php _e('Nasil calisir: Ziyaretci sepete urun ekledikten sonra belirtilen sure icinde odeme yapmazsa sepet "terk edilmis" olarak isaretlenir. Otomatik e-postalar WP Cron ile saatlik kontrol edilerek gonderilir. Musteri odeme yaparsa sepet "kurtarildi" olarak guncellenir.', 'api-isarud'); ?>
    </div>
</div>
<script>
jQuery(function($){
    $('#isarud-send-test').on('click', function(){
        var btn = $(this), email = $('#isarud-test-email').val();
        btn.prop('disabled',true);
        $.post(isarud.ajax, {action:'isarud_send_test_reminder', nonce:isarud.nonce, test_email:email}, function(r){
            btn.prop('disabled',false);
            var cls = r.success ? 'color:#15803d' : 'color:#dc2626';
            $('#isarud-test-result').html('<span style="font-size:12px;'+cls+'">'+r.data+'</span>');
        });
    });
});
</script>
