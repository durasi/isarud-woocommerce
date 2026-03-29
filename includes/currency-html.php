<?php
if (!defined('ABSPATH')) exit;
$currency = Isarud_Currency::instance();
$settings = $currency->get_settings();
$cached = $currency->get_cached_rates();

if (isset($_POST['isarud_save_currency']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_currency')) {
    $new = [
        'enabled' => !empty($_POST['enabled']),
        'base_currency' => sanitize_text_field($_POST['base_currency'] ?? 'USD'),
        'target_currency' => sanitize_text_field($_POST['target_currency'] ?? 'TRY'),
        'rate_type' => sanitize_text_field($_POST['rate_type'] ?? 'forex_selling'),
        'margin_type' => sanitize_text_field($_POST['margin_type'] ?? 'percent'),
        'margin_value' => floatval($_POST['margin_value'] ?? 0),
        'auto_update' => !empty($_POST['auto_update']),
        'update_interval' => sanitize_text_field($_POST['update_interval'] ?? 'daily'),
        'round_to' => intval($_POST['round_to'] ?? 2),
        'last_update' => $settings['last_update'],
        'last_rate' => $settings['last_rate'],
    ];
    $currency->save_settings($new);
    $settings = $new;

    if ($new['auto_update']) {
        wp_clear_scheduled_hook('isarud_currency_update');
        wp_schedule_event(time(), $new['update_interval'], 'isarud_currency_update');
    } else {
        wp_clear_scheduled_hook('isarud_currency_update');
    }

    echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'api-isarud') . '</p></div>';
}

$currencies = [
    'USD' => 'ABD Dolari (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'Ingiliz Sterlini (GBP)',
    'CHF' => 'Isvicre Frangi (CHF)', 'JPY' => 'Japon Yeni (JPY)', 'CAD' => 'Kanada Dolari (CAD)',
    'AUD' => 'Avustralya Dolari (AUD)', 'SAR' => 'Suudi Riyali (SAR)', 'TRY' => 'Turk Lirasi (TRY)',
    'RUB' => 'Rus Rublesi (RUB)', 'CNY' => 'Cin Yuani (CNY)', 'KWD' => 'Kuveyt Dinari (KWD)',
];
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="isd-title"><?php _e('TCMB Doviz Kuru', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Dinamik fiyatlandirma — Merkez Bankasi kurlariyla', 'api-isarud'); ?></div>
        </div>
    </div>

    <?php if (!empty($cached['rates'])): ?>
    <div class="isd-metrics" style="margin-bottom:20px">
        <?php
        $show = ['USD', 'EUR', 'GBP'];
        foreach ($show as $code):
            if (!isset($cached['rates'][$code])) continue;
            $r = $cached['rates'][$code];
        ?>
        <div class="isd-metric">
            <div class="isd-metric-val blue"><?php echo number_format($r['forex_selling'], 4, ',', '.'); ?></div>
            <div class="isd-metric-label"><?php echo esc_html($code); ?> / TRY</div>
        </div>
        <?php endforeach; ?>
        <div class="isd-metric">
            <div class="isd-metric-val green" style="font-size:14px"><?php echo esc_html($cached['date'] ?? '—'); ?></div>
            <div class="isd-metric-label"><?php _e('TCMB Tarih', 'api-isarud'); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="isd-activity">
            <h3><?php _e('Kur Ayarlari', 'api-isarud'); ?></h3>
            <form method="post">
                <?php wp_nonce_field('isarud_currency'); ?>
                <table class="form-table" style="margin:0">
                    <tr>
                        <th style="padding:10px 0;width:160px"><label><?php _e('Aktif', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0"><label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> <?php _e('Kur bazli fiyatlandirmayi etkinlestir', 'api-isarud'); ?></label></td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Baz Para Birimi', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0">
                            <select name="base_currency" style="min-width:200px">
                                <?php foreach ($currencies as $code => $name): ?>
                                <option value="<?php echo $code; ?>" <?php selected($settings['base_currency'], $code); ?>><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Urunlerinizin baz fiyat para birimi', 'api-isarud'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Hedef Para Birimi', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0">
                            <select name="target_currency" style="min-width:200px">
                                <?php foreach ($currencies as $code => $name): ?>
                                <option value="<?php echo $code; ?>" <?php selected($settings['target_currency'], $code); ?>><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Magaza satis para birimi', 'api-isarud'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Kur Tipi', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0">
                            <select name="rate_type" style="min-width:200px">
                                <option value="forex_buying" <?php selected($settings['rate_type'], 'forex_buying'); ?>><?php _e('Doviz Alis', 'api-isarud'); ?></option>
                                <option value="forex_selling" <?php selected($settings['rate_type'], 'forex_selling'); ?>><?php _e('Doviz Satis', 'api-isarud'); ?></option>
                                <option value="banknote_buying" <?php selected($settings['rate_type'], 'banknote_buying'); ?>><?php _e('Efektif Alis', 'api-isarud'); ?></option>
                                <option value="banknote_selling" <?php selected($settings['rate_type'], 'banknote_selling'); ?>><?php _e('Efektif Satis', 'api-isarud'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Fiyat Margin', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0">
                            <div style="display:flex;gap:8px;align-items:center">
                                <select name="margin_type" style="width:100px">
                                    <option value="percent" <?php selected($settings['margin_type'], 'percent'); ?>>%</option>
                                    <option value="fixed" <?php selected($settings['margin_type'], 'fixed'); ?>><?php _e('Sabit', 'api-isarud'); ?></option>
                                </select>
                                <input type="number" name="margin_value" value="<?php echo esc_attr($settings['margin_value']); ?>" step="0.01" style="width:100px">
                            </div>
                            <p class="description"><?php _e('Kur uzerine eklenecek kar marji', 'api-isarud'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Yuvarlama', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0">
                            <select name="round_to" style="width:100px">
                                <option value="0" <?php selected($settings['round_to'], 0); ?>>0 (tam sayi)</option>
                                <option value="1" <?php selected($settings['round_to'], 1); ?>>1</option>
                                <option value="2" <?php selected($settings['round_to'], 2); ?>>2</option>
                                <option value="4" <?php selected($settings['round_to'], 4); ?>>4</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 0"><label><?php _e('Otomatik Guncelleme', 'api-isarud'); ?></label></th>
                        <td style="padding:10px 0">
                            <label><input type="checkbox" name="auto_update" value="1" <?php checked($settings['auto_update']); ?>> <?php _e('WP Cron ile otomatik fiyat guncelle', 'api-isarud'); ?></label>
                            <div style="margin-top:8px">
                                <select name="update_interval" style="min-width:150px">
                                    <option value="hourly" <?php selected($settings['update_interval'], 'hourly'); ?>><?php _e('Saatlik', 'api-isarud'); ?></option>
                                    <option value="twicedaily" <?php selected($settings['update_interval'], 'twicedaily'); ?>><?php _e('Gunde 2 kez', 'api-isarud'); ?></option>
                                    <option value="daily" <?php selected($settings['update_interval'], 'daily'); ?>><?php _e('Gunluk', 'api-isarud'); ?></option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </table>
                <p style="margin-top:16px">
                    <button type="submit" name="isarud_save_currency" class="button-primary"><?php _e('Ayarlari Kaydet', 'api-isarud'); ?></button>
                </p>
            </form>
        </div>

        <div>
            <div class="isd-activity" style="margin-bottom:16px">
                <h3><?php _e('Islemler', 'api-isarud'); ?></h3>
                <?php if ($settings['last_update']): ?>
                <div style="background:#f0fdf4;border-radius:8px;padding:12px;margin-bottom:12px;font-size:13px;color:#15803d">
                    <?php _e('Son guncelleme:', 'api-isarud'); ?> <?php echo esc_html($settings['last_update']); ?><br>
                    <?php _e('Uygulanan kur:', 'api-isarud'); ?> <?php echo number_format((float)$settings['last_rate'], 4, ',', '.'); ?>
                </div>
                <?php endif; ?>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <button type="button" class="button" id="isarud-fetch-rates"><?php _e('TCMB Kurlarini Cek', 'api-isarud'); ?></button>
                    <button type="button" class="button-primary" id="isarud-apply-rates"><?php _e('Fiyatlari Simdi Guncelle', 'api-isarud'); ?></button>
                </div>
                <div id="isarud-currency-result" style="margin-top:12px"></div>
            </div>

            <?php if (!empty($cached['rates'])): ?>
            <div class="isd-activity">
                <h3><?php _e('Guncel TCMB Kurlari', 'api-isarud'); ?></h3>
                <div style="max-height:300px;overflow-y:auto">
                    <table class="wp-list-table widefat fixed striped" style="font-size:12px">
                        <thead><tr><th><?php _e('Kod', 'api-isarud'); ?></th><th><?php _e('Para Birimi', 'api-isarud'); ?></th><th><?php _e('Alis', 'api-isarud'); ?></th><th><?php _e('Satis', 'api-isarud'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($cached['rates'] as $r):
                            if ($r['code'] === 'TRY') continue;
                            if ($r['forex_selling'] <= 0) continue;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($r['code']); ?></strong></td>
                            <td><?php echo esc_html($r['name_tr']); ?></td>
                            <td><?php echo number_format($r['forex_buying'], 4, ',', '.'); ?></td>
                            <td><?php echo number_format($r['forex_selling'], 4, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p style="margin:8px 0 0;font-size:11px;color:#888"><?php _e('Kaynak: tcmb.gov.tr', 'api-isarud'); ?> | <?php _e('Guncelleme:', 'api-isarud'); ?> <?php echo esc_html($cached['fetched_at'] ?? '—'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
        <?php _e('Nasil calisir: Urunlerinizin baz fiyatlarini (ornegin USD cinsinden) girin. Isarud, TCMB gunluk kurunu cekip WooCommerce fiyatlarini otomatik gunceller. Ilk uygulamada mevcut fiyatlar baz fiyat olarak kaydedilir.', 'api-isarud'); ?>
    </div>
</div>

<script>
jQuery(function($){
    $('#isarud-fetch-rates').on('click', function(){
        var btn = $(this);
        btn.prop('disabled',true).text('<?php _e('Cekilliyor...', 'api-isarud'); ?>');
        $.post(isarud.ajax, {action:'isarud_fetch_rates',nonce:isarud.nonce}, function(r){
            btn.prop('disabled',false).text('<?php _e('TCMB Kurlarini Cek', 'api-isarud'); ?>');
            if(r.success){
                $('#isarud-currency-result').html('<div style="background:#f0fdf4;border-radius:6px;padding:8px 12px;color:#15803d;font-size:12px"><?php _e('Kurlar basariyla cekildi. Tarih:', 'api-isarud'); ?> '+r.data.date+'</div>');
                setTimeout(function(){ location.reload(); }, 1500);
            } else {
                $('#isarud-currency-result').html('<div style="background:#fef2f2;border-radius:6px;padding:8px 12px;color:#dc2626;font-size:12px"><?php _e('Hata:', 'api-isarud'); ?> '+r.data+'</div>');
            }
        });
    });
    $('#isarud-apply-rates').on('click', function(){
        var btn = $(this);
        if(!confirm('<?php _e('Tum urun fiyatlari guncel TCMB kuruna gore guncellenecek. Devam edilsin mi?', 'api-isarud'); ?>')) return;
        btn.prop('disabled',true).text('<?php _e('Guncelleniyor...', 'api-isarud'); ?>');
        $.post(isarud.ajax, {action:'isarud_apply_rates',nonce:isarud.nonce}, function(r){
            btn.prop('disabled',false).text('<?php _e('Fiyatlari Simdi Guncelle', 'api-isarud'); ?>');
            if(r.success){
                $('#isarud-currency-result').html('<div style="background:#f0fdf4;border-radius:6px;padding:8px 12px;color:#15803d;font-size:12px">'+r.data.updated+' <?php _e('urun guncellendi. Kur:', 'api-isarud'); ?> '+parseFloat(r.data.rate).toFixed(4)+'</div>');
            } else {
                $('#isarud-currency-result').html('<div style="background:#fef2f2;border-radius:6px;padding:8px 12px;color:#dc2626;font-size:12px"><?php _e('Hata:', 'api-isarud'); ?> '+r.data+'</div>');
            }
        });
    });
});
</script>
