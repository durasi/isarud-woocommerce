<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="isd-title">Isarud Trade Compliance</div>
            <div class="isd-version">v<?php echo ISARUD_VERSION; ?></div>
        </div>
    </div>

    <?php if (!$has_api_key && !$cloud_connected): ?>
    <div class="isd-welcome">
        <h2><?php _e('Isarud\'a Hoş Geldiniz!', 'api-isarud'); ?></h2>
        <p><?php _e('Yaptırım taraması, pazar yeri entegrasyonu ve ticaret uyum araçlarını ücretsiz kullanmaya başlayın.', 'api-isarud'); ?></p>
        <div class="isd-welcome-btns">
            <a href="<?php echo admin_url('admin.php?page=isarud-cloud'); ?>" class="btn-primary"><?php _e('isarud.com Hesabı Bağla', 'api-isarud'); ?> →</a>
            <?php if (!$has_woo): ?>
            <a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'); ?>" class="btn-ghost"><?php _e('WooCommerce Kur (Opsiyonel)', 'api-isarud'); ?> →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="isd-status">
        <div class="isd-status-card">
            <div class="isd-status-dot">
                <span class="dot <?php echo ($has_api_key || $cloud_connected) ? 'ok' : 'warn'; ?>"></span>
                <span class="name"><?php _e('isarud.com', 'api-isarud'); ?></span>
            </div>
            <p class="isd-status-sub"><?php echo ($has_api_key || $cloud_connected) ? __('Bağlı', 'api-isarud') : __('Henüz bağlanmadı', 'api-isarud'); ?></p>
        </div>
        <div class="isd-status-card">
            <div class="isd-status-dot">
                <span class="dot <?php echo $has_woo ? 'ok' : 'off'; ?>"></span>
                <span class="name">WooCommerce</span>
            </div>
            <p class="isd-status-sub"><?php echo $has_woo ? __('Aktif', 'api-isarud') : __('Opsiyonel', 'api-isarud'); ?></p>
        </div>
        <div class="isd-status-card">
            <div class="isd-status-dot">
                <span class="dot <?php echo $has_creds ? 'ok' : 'off'; ?>"></span>
                <span class="name"><?php _e('Pazar Yeri API', 'api-isarud'); ?></span>
            </div>
            <p class="isd-status-sub"><?php echo $has_creds ? count($creds) . ' ' . __('platform bağlı', 'api-isarud') : __('WooCommerce gerektirir', 'api-isarud'); ?></p>
        </div>
        <div class="isd-status-card">
            <div class="isd-status-dot">
                <span class="dot <?php echo $cloud_connected ? 'ok' : 'off'; ?>"></span>
                <span class="name"><?php _e('Cloud Sync', 'api-isarud'); ?></span>
            </div>
            <p class="isd-status-sub"><?php echo $cloud_connected ? __('Aktif', 'api-isarud') : __('Opsiyonel', 'api-isarud'); ?></p>
        </div>
    </div>

    <?php if ($screenings > 0 || $syncs > 0): ?>
    <div class="isd-metrics">
        <div class="isd-metric">
            <div class="isd-metric-val blue"><?php echo number_format_i18n($screenings); ?></div>
            <div class="isd-metric-label"><?php _e('Taranan Siparişler', 'api-isarud'); ?></div>
        </div>
        <div class="isd-metric">
            <div class="isd-metric-val <?php echo $matches > 0 ? 'red' : 'green'; ?>"><?php echo number_format_i18n($matches); ?></div>
            <div class="isd-metric-label"><?php _e('Eşleşmeler', 'api-isarud'); ?></div>
        </div>
        <div class="isd-metric">
            <div class="isd-metric-val green"><?php echo number_format_i18n($syncs); ?></div>
            <div class="isd-metric-label"><?php _e('Başarılı Sync', 'api-isarud'); ?></div>
        </div>
        <div class="isd-metric">
            <div class="isd-metric-val <?php echo $errors > 0 ? 'amber' : 'muted'; ?>"><?php echo number_format_i18n($errors); ?></div>
            <div class="isd-metric-label"><?php _e('Sync Hataları', 'api-isarud'); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="isd-features">
        <div class="isd-feature-card">
            <h3><?php _e('Her Zaman Ücretsiz', 'api-isarud'); ?></h3>
            <p class="subtitle"><?php _e('WooCommerce olmadan da kullanılabilir', 'api-isarud'); ?></p>
            <ul class="isd-feat-list">
                <li><span class="check green"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Yaptırım taraması (8 liste, 32K+ kayıt)', 'api-isarud'); ?></li>
                <li><span class="check green"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('AI Risk Brief (OSINT istihbaratı)', 'api-isarud'); ?></li>
                <li><span class="check green"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('HS Kod Arama (6.938 kod)', 'api-isarud'); ?></li>
                <li><span class="check green"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('ABD İhracat Kontrol Listesi', 'api-isarud'); ?></li>
                <li><span class="check green"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Çift Kullanımlı Ürün Kontrolü', 'api-isarud'); ?></li>
                <li><span class="check green"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Cloud Sync — tüm cihazlardan erişim', 'api-isarud'); ?></li>
                <li><span class="check green"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('PDF rapor, portföy, uyarılar', 'api-isarud'); ?></li>
            </ul>
            <p style="margin:14px 0 0"><a href="https://isarud.com" target="_blank" class="button"><?php _e('isarud.com\'da Tüm Özellikler', 'api-isarud'); ?> →</a></p>
        </div>
        <div class="isd-feature-card">
            <h3><?php _e('WooCommerce ile Ücretsiz', 'api-isarud'); ?></h3>
            <p class="subtitle"><?php _e('WooCommerce kuruluysa ek olarak', 'api-isarud'); ?></p>
            <ul class="isd-feat-list">
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('6 pazar yeri (Trendyol, HB, N11, Amazon, Pazarama, Etsy)', 'api-isarud'); ?></li>
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Çift yönlü stok sync + Webhook', 'api-isarud'); ?></li>
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Ürün yükleme + çekme + sipariş aktarma', 'api-isarud'); ?></li>
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Varyasyonlu ürün desteği (beden, renk)', 'api-isarud'); ?></li>
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Kategori ve attribute eşleştirme', 'api-isarud'); ?></li>
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('CSV import/export', 'api-isarud'); ?></li>
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Fiyat margin + toplu sync + dropshipping', 'api-isarud'); ?></li>
                <li><span class="check blue"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span><?php _e('Otomatik yaptırım taraması', 'api-isarud'); ?></li>
            </ul>
            <?php if (!$has_woo): ?>
            <p style="margin:14px 0 0"><a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'); ?>" class="button-primary"><?php _e('WooCommerce Kur', 'api-isarud'); ?> →</a></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="isd-steps">
        <div class="isd-step">
            <div class="isd-step-num">1</div>
            <div class="isd-step-title"><?php _e('isarud.com Hesabı Açın', 'api-isarud'); ?></div>
            <p class="isd-step-desc"><?php _e('Ücretsiz hesap — aylık 10 tarama.', 'api-isarud'); ?></p>
            <a href="https://isarud.com/register" target="_blank"><?php _e('Hesap Aç', 'api-isarud'); ?> →</a>
        </div>
        <div class="isd-step">
            <div class="isd-step-num">2</div>
            <div class="isd-step-title"><?php _e('Cloud Sync ile Bağlayın', 'api-isarud'); ?></div>
            <p class="isd-step-desc"><?php _e('API key alın ve buraya yapıştırın.', 'api-isarud'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=isarud-cloud'); ?>"><?php _e('Cloud Sync', 'api-isarud'); ?> →</a>
        </div>
        <div class="isd-step">
            <div class="isd-step-num">3</div>
            <div class="isd-step-title"><?php _e('Pazar Yeri API Ekleyin', 'api-isarud'); ?></div>
            <p class="isd-step-desc"><?php _e('Trendyol, HB, N11 API bilgileri. WooCommerce gerekir.', 'api-isarud'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>"><?php _e('Pazar Yerleri', 'api-isarud'); ?> →</a>
        </div>
        <div class="isd-step">
            <div class="isd-step-num">4</div>
            <div class="isd-step-title"><?php _e('Webhook Kurun', 'api-isarud'); ?></div>
            <p class="isd-step-desc"><?php _e('Çift yönlü stok sync için.', 'api-isarud'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=isarud-webhooks'); ?>"><?php _e('Webhooks', 'api-isarud'); ?> →</a>
        </div>
    </div>

    <?php
    $since_24h = date("Y-m-d H:i:s", strtotime("-24 hours"));
    $recent_screenings = $wpdb->get_results($wpdb->prepare("SELECT entity_name, has_match, created_at FROM {$wpdb->prefix}isarud_screening_log WHERE created_at >= %s ORDER BY created_at DESC LIMIT 10", $since_24h));
    $recent_syncs = $wpdb->get_results($wpdb->prepare("SELECT marketplace, status, message, created_at FROM {$wpdb->prefix}isarud_sync_log WHERE created_at >= %s ORDER BY created_at DESC LIMIT 10", $since_24h));
    $screen_24h = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}isarud_screening_log WHERE created_at >= %s", $since_24h));
    $sync_24h = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}isarud_sync_log WHERE created_at >= %s AND status='success'", $since_24h));
    $err_24h = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}isarud_sync_log WHERE created_at >= %s AND status='error'", $since_24h));
    ?>

    <div class="isd-activity">
        <h3><?php _e('Son 24 Saat', 'api-isarud'); ?></h3>
        <div class="isd-activity-mini">
            <div class="mini">
                <div class="mini-val blue"><?php echo $screen_24h; ?></div>
                <div class="mini-label"><?php _e('Tarama', 'api-isarud'); ?></div>
            </div>
            <div class="mini">
                <div class="mini-val green"><?php echo $sync_24h; ?></div>
                <div class="mini-label"><?php _e('Başarılı Sync', 'api-isarud'); ?></div>
            </div>
            <div class="mini">
                <div class="mini-val <?php echo $err_24h > 0 ? 'amber' : 'muted'; ?>"><?php echo $err_24h; ?></div>
                <div class="mini-label"><?php _e('Hata', 'api-isarud'); ?></div>
            </div>
        </div>

        <?php
        $activities = [];
        foreach ($recent_screenings as $s) {
            $activities[] = ['time' => $s->created_at, 'type' => 'screening', 'detail' => $s->entity_name, 'status' => $s->has_match ? 'match' : 'clean'];
        }
        foreach ($recent_syncs as $s) {
            $activities[] = ['time' => $s->created_at, 'type' => 'sync', 'detail' => ucfirst($s->marketplace) . ($s->message ? ': ' . $s->message : ''), 'status' => $s->status];
        }
        usort($activities, function($a, $b) { return strtotime($b['time']) - strtotime($a['time']); });
        $activities = array_slice($activities, 0, 15);
        ?>

        <?php if (!empty($activities)): ?>
        <div class="isd-timeline">
            <?php foreach ($activities as $act):
                $badge_class = match($act['status']) {
                    'match' => 'match', 'clean' => 'clean', 'success' => 'success', 'error' => 'error', default => 'clean'
                };
                $badge_label = match($act['status']) {
                    'match' => __('Eşleşme', 'api-isarud'), 'clean' => __('Temiz', 'api-isarud'), 'success' => __('Başarılı', 'api-isarud'), 'error' => __('Hata', 'api-isarud'), default => $act['status']
                };
            ?>
            <div class="isd-timeline-row">
                <div class="isd-timeline-left">
                    <?php if ($act['type'] === 'screening'): ?>
                    <svg viewBox="0 0 24 24" stroke="#185fa5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <?php else: ?>
                    <svg viewBox="0 0 24 24" stroke="#00a32a"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                    <?php endif; ?>
                    <span class="detail"><?php echo esc_html(mb_substr($act['detail'], 0, 55)); ?></span>
                </div>
                <div class="isd-timeline-right">
                    <span class="isd-badge <?php echo $badge_class; ?>"><?php echo $badge_label; ?></span>
                    <span class="isd-timeline-time"><?php echo esc_html(human_time_diff(strtotime($act['time']))) . ' ' . __('önce', 'api-isarud'); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($activities) >= 15): ?>
        <p style="margin:12px 0 0;text-align:right"><a href="<?php echo admin_url('admin.php?page=isarud-statistics'); ?>" class="button"><?php _e('Tüm Aktiviteleri Gör', 'api-isarud'); ?> →</a></p>
        <?php endif; ?>
        <?php else: ?>
        <p class="isd-empty"><?php _e('Son 24 saatte aktivite yok.', 'api-isarud'); ?></p>
        <?php endif; ?>
    </div>

    <?php
    $eco_checks = [
        'payment' => [
            'title' => __('Ödeme Sistemi', 'api-isarud'),
            'desc' => __('Sanal POS ile online ödeme alın', 'api-isarud'),
            'plugins' => [
                ['slug' => 'woocommerce-iyzico', 'name' => 'iyzico', 'file' => 'woocommerce-iyzico/iyzico-for-woocommerce.php', 'install' => 'iyzico+woocommerce'],
                ['slug' => 'flavor-flavor-payment-gateway', 'name' => 'PayTR (Flavor)', 'file' => 'flavor-flavor-payment-gateway/flavor-flavor-payment-gateway.php', 'install' => 'flavor+payment'],
                ['slug' => 'param-sanal-pos', 'name' => 'Param', 'file' => 'param-sanal-pos/param-sanal-pos.php', 'install' => 'param+sanal+pos'],
            ],
            'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        ],
        'shipping' => [
            'title' => __('Kargo Entegrasyonu', 'api-isarud'),
            'desc' => __('Kargo firmalarıyla otomatik entegrasyon', 'api-isarud'),
            'plugins' => [
                ['slug' => 'aras-kargo-woo', 'name' => 'Aras Kargo', 'file' => 'aras-kargo-woo/aras-kargo-woo.php', 'install' => 'aras+kargo'],
                ['slug' => 'yurtici-kargo-woo', 'name' => 'Yurtiçi Kargo', 'file' => 'yurtici-kargo-woo/yurtici-kargo-woo.php', 'install' => 'yurtici+kargo'],
                ['slug' => 'mng-kargo-woo', 'name' => 'MNG Kargo', 'file' => 'mng-kargo-woo/mng-kargo-woo.php', 'install' => 'mng+kargo'],
            ],
            'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        ],
        'seo' => [
            'title' => __('SEO Yönetimi', 'api-isarud'),
            'desc' => __('Arama motorlarında üst sıralara çıkın', 'api-isarud'),
            'plugins' => [
                ['slug' => 'wordpress-seo', 'name' => 'Yoast SEO', 'file' => 'wordpress-seo/wp-seo.php', 'install' => 'yoast+seo'],
                ['slug' => 'seo-by-rank-math', 'name' => 'Rank Math', 'file' => 'seo-by-rank-math/rank-math.php', 'install' => 'rank+math+seo'],
            ],
            'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        ],
        'marketing' => [
            'title' => __('Pazarlama Otomasyonu', 'api-isarud'),
            'desc' => __('Sepet hatırlatma, e-posta ve kampanyalar', 'api-isarud'),
            'plugins' => [
                ['slug' => 'woo-cart-abandonment-recovery', 'name' => __('Sepet Hatırlatma', 'api-isarud'), 'file' => 'woo-cart-abandonment-recovery/woo-cart-abandonment-recovery.php', 'install' => 'cart+abandonment+recovery'],
                ['slug' => 'mailchimp-for-woocommerce', 'name' => 'Mailchimp', 'file' => 'mailchimp-for-woocommerce/mailchimp-woocommerce.php', 'install' => 'mailchimp+woocommerce'],
                ['slug' => 'kadence-woocommerce-email-designer', 'name' => __('E-posta Tasarım', 'api-isarud'), 'file' => 'kadence-woocommerce-email-designer/kadence-woocommerce-email-designer.php', 'install' => 'kadence+email+designer'],
            ],
            'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        ],
        'analytics' => [
            'title' => __('Analitik ve Raporlama', 'api-isarud'),
            'desc' => __('Ziyaretçi ve satış analitiği', 'api-isarud'),
            'plugins' => [
                ['slug' => 'google-site-kit', 'name' => 'Google Site Kit', 'file' => 'google-site-kit/google-site-kit.php', 'install' => 'google+site+kit'],
                ['slug' => 'woocommerce-google-analytics-integration', 'name' => 'WC Google Analytics', 'file' => 'woocommerce-google-analytics-integration/woocommerce-google-analytics-integration.php', 'install' => 'woocommerce+google+analytics'],
            ],
            'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        ],
        'security' => [
            'title' => __('Güvenlik', 'api-isarud'),
            'desc' => __('Site güvenliği ve spam koruması', 'api-isarud'),
            'plugins' => [
                ['slug' => 'wordfence', 'name' => 'Wordfence', 'file' => 'wordfence/wordfence.php', 'install' => 'wordfence'],
                ['slug' => 'akismet', 'name' => 'Akismet Anti-Spam', 'file' => 'akismet/akismet.php', 'install' => 'akismet'],
            ],
            'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>',
        ],
    ];

    $total_categories = count($eco_checks);
    $completed_categories = 0;
    foreach ($eco_checks as $key => &$cat) {
        $cat['has_any'] = false;
        foreach ($cat['plugins'] as &$p) {
            $p['active'] = is_plugin_active($p['file']);
            $p['installed'] = file_exists(WP_PLUGIN_DIR . '/' . $p['file']);
            if ($p['active']) $cat['has_any'] = true;
        }
        unset($p);
        if ($cat['has_any']) $completed_categories++;
    }
    unset($cat);
    $eco_percent = $total_categories > 0 ? round(($completed_categories / $total_categories) * 100) : 0;
    ?>

    <div class="isd-activity">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h3 style="margin:0"><?php _e('E-ticaret Altyapı Durumu', 'api-isarud'); ?></h3>
            <span style="font-size:13px;color:var(--color-text-secondary, #888)"><?php echo $completed_categories; ?>/<?php echo $total_categories; ?> <?php _e('tamamlandı', 'api-isarud'); ?></span>
        </div>
        <div style="background:#f0f1f2;border-radius:6px;height:8px;margin-bottom:20px;overflow:hidden">
            <div style="background:#00a32a;height:100%;border-radius:6px;width:<?php echo $eco_percent; ?>%;transition:width .3s"></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px">
        <?php foreach ($eco_checks as $key => $cat): ?>
            <div class="isd-status-card" style="padding:18px">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <div style="width:32px;height:32px;border-radius:8px;background:<?php echo $cat['has_any'] ? '#f0fdf4' : '#f8f9fa'; ?>;display:flex;align-items:center;justify-content:center;color:<?php echo $cat['has_any'] ? '#00a32a' : '#888'; ?>;flex-shrink:0">
                        <?php echo $cat['icon']; ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14px;font-weight:600;color:#1d2327"><?php echo $cat['title']; ?></div>
                        <div style="font-size:11px;color:#888"><?php echo $cat['desc']; ?></div>
                    </div>
                    <?php if ($cat['has_any']): ?>
                    <span style="font-size:11px;padding:2px 10px;border-radius:12px;background:#f0fdf4;color:#15803d;font-weight:600;white-space:nowrap"><?php _e('Hazır', 'api-isarud'); ?></span>
                    <?php else: ?>
                    <span style="font-size:11px;padding:2px 10px;border-radius:12px;background:#fef3c7;color:#92400e;font-weight:600;white-space:nowrap"><?php _e('Eksik', 'api-isarud'); ?></span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                <?php foreach ($cat['plugins'] as $p): ?>
                    <?php if ($p['active']): ?>
                    <span style="font-size:11px;padding:3px 10px;border-radius:10px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0"><?php echo esc_html($p['name']); ?></span>
                    <?php elseif ($p['installed']): ?>
                    <a href="<?php echo admin_url('plugins.php'); ?>" style="font-size:11px;padding:3px 10px;border-radius:10px;background:#e6f1fb;color:#185fa5;border:1px solid #b5d4f4;text-decoration:none" title="<?php _e('Kurulu ama aktif değil — tıkla', 'api-isarud'); ?>"><?php echo esc_html($p['name']); ?></a>
                    <?php else: ?>
                    <a href="<?php echo admin_url('plugin-install.php?s=' . urlencode($p['install']) . '&tab=search&type=term'); ?>" style="font-size:11px;padding:3px 10px;border-radius:10px;background:#f8f9fa;color:#666;border:1px solid #e2e4e7;text-decoration:none" title="<?php _e('Tıkla ve kur', 'api-isarud'); ?>"><?php echo esc_html($p['name']); ?> →</a>
                    <?php endif; ?>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="background:linear-gradient(135deg,#1d2327 0%,#2271b1 100%);border-radius:10px;padding:18px 24px;margin-top:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div style="color:#fff">
                <div style="font-size:14px;font-weight:600;margin-bottom:2px"><?php _e('E-ticaret Altyapinizi Tamamlayin', 'api-isarud'); ?></div>
                <div style="font-size:12px;opacity:0.8"><?php _e('Odeme, kargo, SEO, pazarlama ve guvenlik kurulum rehberi', 'api-isarud'); ?></div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=isarud-ecosystem'); ?>" style="background:#fff;color:#1d2327;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;white-space:nowrap"><?php _e('Kurulum Rehberi', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>
