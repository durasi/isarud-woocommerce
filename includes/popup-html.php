<?php
if (!defined('ABSPATH')) exit;
$popup = Isarud_Popup::instance();
$settings = $popup->get_settings();
$campaigns = $popup->get_campaigns();

if (isset($_POST['isarud_save_popup_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_popup_settings')) {
    $new = [
        'enabled' => !empty($_POST['enabled']),
        'hide_on_mobile' => !empty($_POST['hide_on_mobile']),
        'cookie_duration' => intval($_POST['cookie_duration'] ?? 7),
    ];
    $popup->save_settings($new);
    $settings = $new;
    echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'api-isarud') . '</p></div>';
}
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div>
            <div class="isd-title"><?php _e('Popup Kampanyalari', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Exit-intent, zamanli ve sepet bazli popup yonetimi', 'api-isarud'); ?></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px">
        <div>
            <div class="isd-activity" style="margin-bottom:16px">
                <h3><?php _e('Yeni Kampanya Olustur', 'api-isarud'); ?></h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px">
                    <div><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Baslik', 'api-isarud'); ?></label><input type="text" id="pp-title" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px" placeholder="<?php _e('Hosgeldin Indirimi!', 'api-isarud'); ?>"></div>
                    <div><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Kupon Kodu', 'api-isarud'); ?></label><input type="text" id="pp-coupon" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px" placeholder="HOSGELDIN10"></div>
                    <div style="grid-column:1/-1"><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Mesaj', 'api-isarud'); ?></label><textarea id="pp-message" rows="2" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px" placeholder="<?php _e('Ilk siparisinde %10 indirim!', 'api-isarud'); ?>"></textarea></div>
                    <div><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Buton Metni', 'api-isarud'); ?></label><input type="text" id="pp-btn-text" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px" placeholder="<?php _e('Alisverise Basla', 'api-isarud'); ?>"></div>
                    <div><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Buton URL', 'api-isarud'); ?></label><input type="text" id="pp-btn-url" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px" placeholder="/shop"></div>
                    <div><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Tetikleyici', 'api-isarud'); ?></label>
                        <select id="pp-trigger" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px">
                            <option value="exit_intent"><?php _e('Exit-Intent (sayfadan ayrilirken)', 'api-isarud'); ?></option>
                            <option value="timed"><?php _e('Zamanli (X saniye sonra)', 'api-isarud'); ?></option>
                            <option value="scroll"><?php _e('Scroll (%50 asagiya inince)', 'api-isarud'); ?></option>
                            <option value="add_to_cart"><?php _e('Sepete ekleyince', 'api-isarud'); ?></option>
                        </select></div>
                    <div><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Gecikme (sn)', 'api-isarud'); ?></label><input type="number" id="pp-delay" value="5" min="1" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px"></div>
                    <div><label style="font-weight:600;display:block;margin-bottom:4px"><?php _e('Buton Rengi', 'api-isarud'); ?></label><input type="color" id="pp-btn-color" value="#358a4f" style="width:50px;height:34px;border:1px solid #ddd;border-radius:6px"></div>
                    <div style="display:flex;align-items:end"><label><input type="checkbox" id="pp-active" checked> <?php _e('Aktif', 'api-isarud'); ?></label></div>
                </div>
                <p style="margin-top:12px"><button class="button-primary" id="isarud-save-popup"><?php _e('Kampanya Olustur', 'api-isarud'); ?></button></p>
                <div id="pp-result" style="margin-top:8px"></div>
            </div>

            <?php if (!empty($campaigns)): ?>
            <div class="isd-activity">
                <h3><?php _e('Mevcut Kampanyalar', 'api-isarud'); ?></h3>
                <table class="wp-list-table widefat fixed striped" style="font-size:12px">
                    <thead><tr>
                        <th><?php _e('Baslik', 'api-isarud'); ?></th><th style="width:80px"><?php _e('Tetikleyici', 'api-isarud'); ?></th>
                        <th style="width:70px"><?php _e('Kupon', 'api-isarud'); ?></th><th style="width:80px"><?php _e('Gosterim', 'api-isarud'); ?></th>
                        <th style="width:60px"><?php _e('Durum', 'api-isarud'); ?></th><th style="width:100px"><?php _e('Islem', 'api-isarud'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($campaigns as $id => $c): ?>
                    <tr>
                        <td><strong><?php echo esc_html($c['title'] ?: '—'); ?></strong></td>
                        <td><?php echo esc_html($c['trigger'] ?? 'exit_intent'); ?></td>
                        <td><?php echo esc_html($c['coupon'] ?: '—'); ?></td>
                        <td><?php echo (int)($c['impressions'] ?? 0); ?></td>
                        <td><span style="color:<?php echo !empty($c['active']) ? '#15803d' : '#888'; ?>"><?php echo !empty($c['active']) ? '● ' . __('Aktif', 'api-isarud') : '○ ' . __('Pasif', 'api-isarud'); ?></span></td>
                        <td>
                            <button class="button button-small isarud-toggle-popup" data-id="<?php echo esc_attr($id); ?>"><?php echo !empty($c['active']) ? __('Durdur', 'api-isarud') : __('Baslat', 'api-isarud'); ?></button>
                            <button class="button button-small isarud-delete-popup" data-id="<?php echo esc_attr($id); ?>" style="color:#dc2626">&times;</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="isd-activity">
                <h3><?php _e('Genel Ayarlar', 'api-isarud'); ?></h3>
                <form method="post">
                    <?php wp_nonce_field('isarud_popup_settings'); ?>
                    <table style="width:100%;font-size:13px">
                        <tr><td style="padding:8px 0"><label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> <?php _e('Popup modulunu etkinlestir', 'api-isarud'); ?></label></td></tr>
                        <tr><td style="padding:8px 0"><label><input type="checkbox" name="hide_on_mobile" value="1" <?php checked($settings['hide_on_mobile']); ?>> <?php _e('Mobilde gizle', 'api-isarud'); ?></label></td></tr>
                        <tr><td style="padding:8px 0"><label><?php _e('Cookie suresi (gun):', 'api-isarud'); ?></label> <input type="number" name="cookie_duration" value="<?php echo esc_attr($settings['cookie_duration']); ?>" min="1" style="width:60px"></td></tr>
                    </table>
                    <button type="submit" name="isarud_save_popup_settings" class="button-primary" style="width:100%;margin-top:8px"><?php _e('Kaydet', 'api-isarud'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
jQuery(function($){
    $('#isarud-save-popup').on('click', function(){
        var btn = $(this); btn.prop('disabled',true);
        $.post(isarud.ajax, {action:'isarud_save_campaign', nonce:isarud.nonce,
            popup_title:$('#pp-title').val(), popup_message:$('#pp-message').val(), popup_coupon:$('#pp-coupon').val(),
            button_text:$('#pp-btn-text').val(), button_url:$('#pp-btn-url').val(), button_color:$('#pp-btn-color').val(),
            popup_trigger:$('#pp-trigger').val(), popup_delay:$('#pp-delay').val(), popup_active:$('#pp-active').is(':checked')?1:0
        }, function(r){
            btn.prop('disabled',false);
            if(r.success){$('#pp-result').html('<span style="color:#15803d;font-size:12px"><?php _e('Kampanya olusturuldu!', 'api-isarud'); ?></span>');setTimeout(function(){location.reload();},1000);}
        });
    });
    $('.isarud-toggle-popup').on('click',function(){var id=$(this).data('id');$.post(isarud.ajax,{action:'isarud_toggle_campaign',nonce:isarud.nonce,campaign_id:id},function(){location.reload();});});
    $('.isarud-delete-popup').on('click',function(){if(!confirm('<?php _e('Silinsin mi?', 'api-isarud'); ?>'))return;var id=$(this).data('id');$.post(isarud.ajax,{action:'isarud_delete_campaign',nonce:isarud.nonce,campaign_id:id},function(){location.reload();});});
});
</script>
