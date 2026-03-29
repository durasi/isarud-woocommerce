<?php
/**
 * Isarud E-Fatura Admin Sayfası
 *
 * @package Isarud
 * @since 7.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Settings save
if (isset($_POST['isarud_einvoice_save_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_einvoice_settings')) {
    $einvoice = new Isarud_EInvoice();
    $fields = [
        'enabled', 'test_mode', 'gib_user', 'gib_pass', 'auto_create', 'auto_sign', 'auto_email',
        'company_name', 'company_vkn', 'company_tax_office', 'company_address', 'company_city',
        'company_district', 'company_phone', 'company_email', 'default_kdv_rate', 'invoice_note', 'invoice_prefix',
    ];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = isset($_POST[$f]) ? sanitize_text_field($_POST[$f]) : '';
    }
    // Checkbox'lar
    $data['enabled']     = isset($_POST['enabled']) ? 'yes' : 'no';
    $data['test_mode']   = isset($_POST['test_mode']) ? 'yes' : 'no';
    $data['auto_create'] = isset($_POST['auto_create']) ? 'yes' : 'no';
    $data['auto_sign']   = isset($_POST['auto_sign']) ? 'yes' : 'no';
    $data['auto_email']  = isset($_POST['auto_email']) ? 'yes' : 'no';
    $einvoice->save_settings($data);
    echo '<div class="notice notice-success"><p>' . esc_html__('Ayarlar kaydedildi.', 'api-isarud') . '</p></div>';
}

$einvoice = new Isarud_EInvoice();
$settings = $einvoice->get_settings();
$stats = $einvoice->get_stats();

// Tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Fatura listesi
$page_num = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$invoices_data = $einvoice->get_all_invoices($page_num, 20, $filter_status, $search);
?>

<div class="wrap isarud-admin-wrap">
    <h1 style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:28px;">🧾</span>
        <?php esc_html_e('E-Arşiv Fatura', 'api-isarud'); ?>
        <?php if ($settings['test_mode'] === 'yes'): ?>
            <span style="background:#fff3cd;color:#856404;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:normal;">
                ⚠️ <?php esc_html_e('Test Modu', 'api-isarud'); ?>
            </span>
        <?php endif; ?>
    </h1>

    <!-- Tabs -->
    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a href="?page=isarud-einvoice&tab=dashboard" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
            📊 <?php esc_html_e('Özet', 'api-isarud'); ?>
        </a>
        <a href="?page=isarud-einvoice&tab=invoices" class="nav-tab <?php echo $active_tab === 'invoices' ? 'nav-tab-active' : ''; ?>">
            📋 <?php esc_html_e('Faturalar', 'api-isarud'); ?>
        </a>
        <a href="?page=isarud-einvoice&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            ⚙️ <?php esc_html_e('Ayarlar', 'api-isarud'); ?>
        </a>
    </nav>

    <?php if ($active_tab === 'dashboard'): ?>
    <!-- ==================== DASHBOARD ==================== -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:25px;">
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;text-align:center;">
            <div style="font-size:32px;font-weight:bold;color:#1d2327;"><?php echo esc_html($stats['total']); ?></div>
            <div style="color:#666;margin-top:5px;"><?php esc_html_e('Toplam Fatura', 'api-isarud'); ?></div>
        </div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;text-align:center;">
            <div style="font-size:32px;font-weight:bold;color:#dba617;"><?php echo esc_html($stats['taslak']); ?></div>
            <div style="color:#666;margin-top:5px;"><?php esc_html_e('Taslak', 'api-isarud'); ?></div>
        </div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;text-align:center;">
            <div style="font-size:32px;font-weight:bold;color:#00a32a;"><?php echo esc_html($stats['imzali']); ?></div>
            <div style="color:#666;margin-top:5px;"><?php esc_html_e('İmzalandı', 'api-isarud'); ?></div>
        </div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;text-align:center;">
            <div style="font-size:32px;font-weight:bold;color:#d63638;"><?php echo esc_html($stats['iptal']); ?></div>
            <div style="color:#666;margin-top:5px;"><?php esc_html_e('İptal', 'api-isarud'); ?></div>
        </div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;text-align:center;">
            <div style="font-size:32px;font-weight:bold;color:#2271b1;"><?php echo wc_price($stats['toplam_ciro']); ?></div>
            <div style="color:#666;margin-top:5px;"><?php esc_html_e('Toplam Ciro', 'api-isarud'); ?></div>
        </div>
    </div>

    <!-- Son faturalar -->
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;">
        <h3 style="margin-top:0;">📋 <?php esc_html_e('Son Faturalar', 'api-isarud'); ?></h3>
        <?php
        $recent = $einvoice->get_all_invoices(1, 10);
        if (empty($recent['items'])): ?>
            <p style="color:#999;"><?php esc_html_e('Henüz fatura oluşturulmadı.', 'api-isarud'); ?></p>
        <?php else: ?>
            <table class="widefat striped" style="border-radius:6px;overflow:hidden;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Sipariş', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Alıcı', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Tutar', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Durum', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Tarih', 'api-isarud'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent['items'] as $inv): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $inv->order_id . '&action=edit')); ?>">#<?php echo esc_html($inv->order_id); ?></a></td>
                        <td><?php echo esc_html($inv->alici_unvan); ?></td>
                        <td><?php echo wc_price($inv->toplam_tutar); ?></td>
                        <td>
                            <?php
                            $badges = [
                                'TASLAK'    => '<span style="background:#fff3cd;color:#856404;padding:2px 8px;border-radius:3px;font-size:11px;">🟡 Taslak</span>',
                                'IMZALANDI' => '<span style="background:#d1e7dd;color:#0f5132;padding:2px 8px;border-radius:3px;font-size:11px;">🟢 İmzalandı</span>',
                                'IPTAL'     => '<span style="background:#f8d7da;color:#842029;padding:2px 8px;border-radius:3px;font-size:11px;">🔴 İptal</span>',
                            ];
                            echo isset($badges[$inv->durum]) ? $badges[$inv->durum] : esc_html($inv->durum);
                            ?>
                        </td>
                        <td><?php echo esc_html($inv->gib_tarih); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php elseif ($active_tab === 'invoices'): ?>
    <!-- ==================== FATURA LİSTESİ ==================== -->
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;">
        <!-- Filtre bar -->
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:15px;flex-wrap:wrap;">
            <form method="get" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="page" value="isarud-einvoice">
                <input type="hidden" name="tab" value="invoices">
                <select name="status" style="min-width:120px;">
                    <option value=""><?php esc_html_e('Tüm Durumlar', 'api-isarud'); ?></option>
                    <option value="TASLAK" <?php selected($filter_status, 'TASLAK'); ?>><?php esc_html_e('Taslak', 'api-isarud'); ?></option>
                    <option value="IMZALANDI" <?php selected($filter_status, 'IMZALANDI'); ?>><?php esc_html_e('İmzalandı', 'api-isarud'); ?></option>
                    <option value="IPTAL" <?php selected($filter_status, 'IPTAL'); ?>><?php esc_html_e('İptal', 'api-isarud'); ?></option>
                </select>
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Ara (sipariş no, alıcı, UUID)...', 'api-isarud'); ?>" style="min-width:200px;">
                <button type="submit" class="button"><?php esc_html_e('Filtrele', 'api-isarud'); ?></button>
            </form>
        </div>

        <?php if (empty($invoices_data['items'])): ?>
            <p style="color:#999;text-align:center;padding:30px;"><?php esc_html_e('Fatura bulunamadı.', 'api-isarud'); ?></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Sipariş', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('UUID', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Alıcı', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('VKN/TCKN', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Tutar', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('KDV', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Durum', 'api-isarud'); ?></th>
                        <th><?php esc_html_e('Tarih', 'api-isarud'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices_data['items'] as $inv): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $inv->order_id . '&action=edit')); ?>">#<?php echo esc_html($inv->order_id); ?></a></td>
                        <td><code style="font-size:10px;"><?php echo esc_html(substr($inv->uuid, 0, 8)); ?>...</code></td>
                        <td><?php echo esc_html($inv->alici_unvan); ?></td>
                        <td><?php echo esc_html($inv->alici_vkn); ?></td>
                        <td><?php echo wc_price($inv->toplam_tutar); ?></td>
                        <td><?php echo wc_price($inv->kdv_tutar); ?></td>
                        <td>
                            <?php
                            $badges = [
                                'TASLAK'    => '<span style="background:#fff3cd;color:#856404;padding:2px 8px;border-radius:3px;font-size:11px;">Taslak</span>',
                                'IMZALANDI' => '<span style="background:#d1e7dd;color:#0f5132;padding:2px 8px;border-radius:3px;font-size:11px;">İmzalandı</span>',
                                'IPTAL'     => '<span style="background:#f8d7da;color:#842029;padding:2px 8px;border-radius:3px;font-size:11px;">İptal</span>',
                            ];
                            echo isset($badges[$inv->durum]) ? $badges[$inv->durum] : esc_html($inv->durum);
                            ?>
                        </td>
                        <td><?php echo esc_html($inv->gib_tarih); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($invoices_data['pages'] > 1): ?>
            <div style="margin-top:15px;text-align:center;">
                <?php
                echo paginate_links([
                    'base'    => add_query_arg('paged', '%#%'),
                    'format'  => '',
                    'current' => $invoices_data['current'],
                    'total'   => $invoices_data['pages'],
                ]);
                ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php elseif ($active_tab === 'settings'): ?>
    <!-- ==================== AYARLAR ==================== -->
    <form method="post">
        <?php wp_nonce_field('isarud_einvoice_settings'); ?>
        <input type="hidden" name="isarud_einvoice_save_settings" value="1">

        <!-- Genel Ayarlar -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;margin-bottom:20px;">
            <h3 style="margin-top:0;">🔧 <?php esc_html_e('Genel Ayarlar', 'api-isarud'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e('E-Fatura Modülü', 'api-isarud'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enabled" value="yes" <?php checked($settings['enabled'], 'yes'); ?>>
                            <?php esc_html_e('E-Fatura modülünü aktifleştir', 'api-isarud'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Test Modu', 'api-isarud'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="test_mode" value="yes" <?php checked($settings['test_mode'], 'yes'); ?>>
                            <?php esc_html_e('GİB test ortamını kullan (gerçek fatura kesilmez)', 'api-isarud'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Otomatik Fatura', 'api-isarud'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_create" value="yes" <?php checked($settings['auto_create'], 'yes'); ?>>
                            <?php esc_html_e('Sipariş tamamlandığında otomatik fatura oluştur', 'api-isarud'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Otomatik İmzalama', 'api-isarud'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_sign" value="yes" <?php checked($settings['auto_sign'], 'yes'); ?>>
                            <?php esc_html_e('Oluşturulan faturayı otomatik imzala', 'api-isarud'); ?>
                        </label>
                        <p class="description" style="color:#d63638;">⚠️ <?php esc_html_e('Dikkat: İmzalanan fatura mali kayıt oluşturur!', 'api-isarud'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Otomatik E-posta', 'api-isarud'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_email" value="yes" <?php checked($settings['auto_email'], 'yes'); ?>>
                            <?php esc_html_e('Fatura oluşturulunca müşteriye e-posta gönder', 'api-isarud'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- GİB Giriş Bilgileri -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;margin-bottom:20px;">
            <h3 style="margin-top:0;">🔐 <?php esc_html_e('GİB e-Arşiv Portal Giriş', 'api-isarud'); ?></h3>
            <p class="description"><?php esc_html_e('Kullanıcı kodu ve parolayı muhasebecinizden veya GİB İnteraktif Vergi Dairesi\'nden edinebilirsiniz.', 'api-isarud'); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="gib_user"><?php esc_html_e('Kullanıcı Kodu', 'api-isarud'); ?></label></th>
                    <td>
                        <input type="text" name="gib_user" id="gib_user" value="<?php echo esc_attr($settings['gib_user']); ?>" class="regular-text" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <th><label for="gib_pass"><?php esc_html_e('Parola', 'api-isarud'); ?></label></th>
                    <td>
                        <input type="password" name="gib_pass" id="gib_pass" value="<?php echo esc_attr($settings['gib_pass']); ?>" class="regular-text" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                        <button type="button" class="button" id="isarud-test-gib-connection">
                            🔌 <?php esc_html_e('Bağlantıyı Test Et', 'api-isarud'); ?>
                        </button>
                        <span id="isarud-test-result" style="margin-left:10px;"></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Firma Bilgileri -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;margin-bottom:20px;">
            <h3 style="margin-top:0;">🏢 <?php esc_html_e('Firma Bilgileri', 'api-isarud'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="company_name"><?php esc_html_e('Firma / Şahıs Adı', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_name" id="company_name" value="<?php echo esc_attr($settings['company_name']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="company_vkn"><?php esc_html_e('VKN / TCKN', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_vkn" id="company_vkn" value="<?php echo esc_attr($settings['company_vkn']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="company_tax_office"><?php esc_html_e('Vergi Dairesi', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_tax_office" id="company_tax_office" value="<?php echo esc_attr($settings['company_tax_office']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="company_address"><?php esc_html_e('Adres', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_address" id="company_address" value="<?php echo esc_attr($settings['company_address']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="company_city"><?php esc_html_e('Şehir', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_city" id="company_city" value="<?php echo esc_attr($settings['company_city']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="company_district"><?php esc_html_e('İlçe', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_district" id="company_district" value="<?php echo esc_attr($settings['company_district']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="company_phone"><?php esc_html_e('Telefon', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_phone" id="company_phone" value="<?php echo esc_attr($settings['company_phone']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="company_email"><?php esc_html_e('E-posta', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="company_email" id="company_email" value="<?php echo esc_attr($settings['company_email']); ?>" class="regular-text"></td>
                </tr>
            </table>
        </div>

        <!-- Fatura Ayarları -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;margin-bottom:20px;">
            <h3 style="margin-top:0;">📄 <?php esc_html_e('Fatura Ayarları', 'api-isarud'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="default_kdv_rate"><?php esc_html_e('Varsayılan KDV Oranı (%)', 'api-isarud'); ?></label></th>
                    <td>
                        <select name="default_kdv_rate" id="default_kdv_rate">
                            <option value="0" <?php selected($settings['default_kdv_rate'], '0'); ?>>%0</option>
                            <option value="1" <?php selected($settings['default_kdv_rate'], '1'); ?>>%1</option>
                            <option value="10" <?php selected($settings['default_kdv_rate'], '10'); ?>>%10</option>
                            <option value="20" <?php selected($settings['default_kdv_rate'], '20'); ?>>%20</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="invoice_prefix"><?php esc_html_e('Fatura Ön Eki', 'api-isarud'); ?></label></th>
                    <td><input type="text" name="invoice_prefix" id="invoice_prefix" value="<?php echo esc_attr($settings['invoice_prefix']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="invoice_note"><?php esc_html_e('Fatura Notu', 'api-isarud'); ?></label></th>
                    <td>
                        <textarea name="invoice_note" id="invoice_note" rows="3" class="large-text"><?php echo esc_textarea($settings['invoice_note']); ?></textarea>
                        <p class="description"><?php esc_html_e('Tüm faturalara eklenecek not. Boş bırakırsanız sipariş numarası yazılır.', 'api-isarud'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-hero">
                💾 <?php esc_html_e('Ayarları Kaydet', 'api-isarud'); ?>
            </button>
        </p>
    </form>

    <script>
    jQuery(function($) {
        $('#isarud-test-gib-connection').on('click', function() {
            var btn = $(this);
            var resultEl = $('#isarud-test-result');
            btn.prop('disabled', true);
            resultEl.html('<span style="color:#666;">⏳ <?php echo esc_js(__('Test ediliyor...', 'api-isarud')); ?></span>');

            $.post(ajaxurl, {
                action: 'isarud_test_connection',
                nonce: '<?php echo wp_create_nonce('isarud_einvoice_nonce'); ?>'
            }, function(resp) {
                if (resp.success) {
                    resultEl.html('<span style="color:#00a32a;">✅ ' + resp.data.message + '</span>');
                } else {
                    resultEl.html('<span style="color:#d63638;">❌ ' + (resp.data || 'Hata') + '</span>');
                }
                btn.prop('disabled', false);
            }).fail(function() {
                resultEl.html('<span style="color:#d63638;">❌ <?php echo esc_js(__('Bağlantı hatası', 'api-isarud')); ?></span>');
                btn.prop('disabled', false);
            });
        });
    });
    </script>
    <?php endif; ?>
</div>
