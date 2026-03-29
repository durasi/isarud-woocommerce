<?php
if (!defined('ABSPATH')) exit;
$b2b = Isarud_B2B::instance();
$settings = $b2b->get_settings();

if (isset($_POST['isarud_save_b2b']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_b2b')) {
    $new = [
        'enabled' => !empty($_POST['enabled']),
        'b2b_role' => 'isarud_b2b',
        'min_order_amount' => floatval($_POST['min_order_amount'] ?? 0),
        'show_tax_field' => !empty($_POST['show_tax_field']),
        'show_company_field' => !empty($_POST['show_company_field']),
        'require_approval' => !empty($_POST['require_approval']),
        'hide_prices_guests' => !empty($_POST['hide_prices_guests']),
    ];
    $b2b->save_settings($new);
    $b2b->ensure_role();
    $settings = $new;
    echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'api-isarud') . '</p></div>';
}

$pending = $b2b->get_pending_applications();
$customers = $b2b->get_b2b_customers();
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="isd-title"><?php _e('B2B Toptan Satis', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Kurumsal musteri yonetimi ve toptan fiyatlandirma', 'api-isarud'); ?></div>
        </div>
    </div>

    <?php if (!empty($pending)): ?>
    <div class="isd-activity" style="margin-bottom:20px;border-left:3px solid #dba617;border-radius:0">
        <h3><?php _e('Bekleyen B2B Basvurulari', 'api-isarud'); ?> <span style="background:#fef3c7;color:#92400e;font-size:12px;padding:2px 10px;border-radius:12px;margin-left:8px"><?php echo count($pending); ?></span></h3>
        <table class="wp-list-table widefat fixed striped" style="font-size:13px">
            <thead><tr>
                <th><?php _e('Kullanici', 'api-isarud'); ?></th>
                <th><?php _e('E-posta', 'api-isarud'); ?></th>
                <th><?php _e('Firma', 'api-isarud'); ?></th>
                <th><?php _e('Vergi No', 'api-isarud'); ?></th>
                <th style="width:150px"><?php _e('Islem', 'api-isarud'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($pending as $u): ?>
            <tr>
                <td><strong><?php echo esc_html($u->display_name); ?></strong></td>
                <td><?php echo esc_html($u->user_email); ?></td>
                <td><?php echo esc_html(get_user_meta($u->ID, 'isarud_b2b_company', true) ?: '—'); ?></td>
                <td><?php echo esc_html(get_user_meta($u->ID, 'isarud_b2b_tax_number', true) ?: '—'); ?></td>
                <td>
                    <button class="button button-primary button-small isarud-b2b-action" data-user="<?php echo $u->ID; ?>" data-action="approve" style="margin-right:4px"><?php _e('Onayla', 'api-isarud'); ?></button>
                    <button class="button button-small isarud-b2b-action" data-user="<?php echo $u->ID; ?>" data-action="reject"><?php _e('Reddet', 'api-isarud'); ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="isd-activity">
            <h3><?php _e('B2B Ayarlari', 'api-isarud'); ?></h3>
            <form method="post">
                <?php wp_nonce_field('isarud_b2b'); ?>
                <table class="form-table" style="margin:0">
                    <tr>
                        <th style="padding:10px 0;width:180px"><label><?php _e('Aktif', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0"><label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> <?php _e('B2B toptan satis modulunu etkinlestir', 'api-isarud'); ?></label></td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Min. Siparis Tutari', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0">
                            <input type="number" name="min_order_amount" value="<?php echo esc_attr($settings['min_order_amount']); ?>" step="0.01" min="0" style="width:120px">
                            <span style="color:#888;font-size:12px"><?php echo get_woocommerce_currency_symbol(); ?></span>
                            <p class="description"><?php _e('B2B musteriler icin minimum siparis tutari (0 = sinir yok)', 'api-isarud'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Firma Alani', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0"><label><input type="checkbox" name="show_company_field" value="1" <?php checked($settings['show_company_field']); ?>> <?php _e('Odeme sayfasinda firma adi alani goster', 'api-isarud'); ?></label></td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Vergi Alanlari', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0"><label><input type="checkbox" name="show_tax_field" value="1" <?php checked($settings['show_tax_field']); ?>> <?php _e('Odeme sayfasinda vergi dairesi ve vergi no goster', 'api-isarud'); ?></label></td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Onay Gerektir', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0"><label><input type="checkbox" name="require_approval" value="1" <?php checked($settings['require_approval']); ?>> <?php _e('B2B basvurulari admin onayi gerektirir', 'api-isarud'); ?></label></td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Fiyat Gizleme', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0"><label><input type="checkbox" name="hide_prices_guests" value="1" <?php checked($settings['hide_prices_guests']); ?>> <?php _e('B2B fiyatlari giris yapmamis ziyaretcilerden gizle', 'api-isarud'); ?></label></td>
                    </tr>
                </table>
                <p style="margin-top:16px">
                    <button type="submit" name="isarud_save_b2b" class="button-primary"><?php _e('Ayarlari Kaydet', 'api-isarud'); ?></button>
                </p>
            </form>
        </div>

        <div>
            <div class="isd-activity" style="margin-bottom:16px">
                <h3><?php _e('B2B Istatistikler', 'api-isarud'); ?></h3>
                <div class="isd-activity-mini">
                    <div class="mini">
                        <div class="mini-val blue"><?php echo count($customers); ?></div>
                        <div class="mini-label"><?php _e('B2B Musteri', 'api-isarud'); ?></div>
                    </div>
                    <div class="mini">
                        <div class="mini-val amber"><?php echo count($pending); ?></div>
                        <div class="mini-label"><?php _e('Bekleyen', 'api-isarud'); ?></div>
                    </div>
                    <div class="mini">
                        <div class="mini-val green"><?php echo $settings['enabled'] ? '●' : '○'; ?></div>
                        <div class="mini-label"><?php echo $settings['enabled'] ? __('Aktif', 'api-isarud') : __('Pasif', 'api-isarud'); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($customers)): ?>
            <div class="isd-activity">
                <h3><?php _e('Onayli B2B Musteriler', 'api-isarud'); ?></h3>
                <table class="wp-list-table widefat fixed striped" style="font-size:12px">
                    <thead><tr>
                        <th><?php _e('Kullanici', 'api-isarud'); ?></th>
                        <th><?php _e('Firma', 'api-isarud'); ?></th>
                        <th><?php _e('Vergi No', 'api-isarud'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><a href="<?php echo get_edit_user_link($c->ID); ?>"><?php echo esc_html($c->display_name); ?></a></td>
                        <td><?php echo esc_html(get_user_meta($c->ID, 'isarud_b2b_company', true) ?: '—'); ?></td>
                        <td><?php echo esc_html(get_user_meta($c->ID, 'isarud_b2b_tax_number', true) ?: '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="isd-activity">
                <p class="isd-empty"><?php _e('Henuz onayli B2B musteri yok.', 'api-isarud'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
        <?php _e('Nasil calisir: Urun duzenle sayfasinda "B2B Toptan Fiyat" ve "B2B Min. Siparis Adedi" alanlarindan toptan fiyat belirleyin. Kullanici profilinden B2B durumunu "Onaylandi" yaparak musteriye ozel fiyatlari aktif edin.', 'api-isarud'); ?>
    </div>
</div>

<script>
jQuery(function($){
    $('.isarud-b2b-action').on('click', function(){
        var btn = $(this);
        var userId = btn.data('user');
        var actionType = btn.data('action');
        var label = actionType === 'approve' ? '<?php _e('onaylansin', 'api-isarud'); ?>' : '<?php _e('reddedilsin', 'api-isarud'); ?>';
        if(!confirm('<?php _e('Bu basvuru', 'api-isarud'); ?> ' + label + '?')) return;
        btn.prop('disabled', true);
        $.post(isarud.ajax, {action:'isarud_approve_b2b', nonce:isarud.nonce, user_id:userId, action_type:actionType}, function(r){
            if(r.success) {
                btn.closest('tr').fadeOut(300, function(){ $(this).remove(); });
            } else {
                btn.prop('disabled', false);
                alert(r.data);
            }
        });
    });
});
</script>
