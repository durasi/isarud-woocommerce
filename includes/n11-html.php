<?php
/**
 * n11-html.php — WP Plugin N11 Admin UI
 * v6.7.0 — 6-tab modern interface
 *
 * AJAX endpoint'leri Isarud_N11 class'ına bağlıdır.
 * Auth: isarud_cloud_api_key WP option (Cloud Sync key)
 */

if (!defined('ABSPATH')) exit;
?>

<style>
.isarud-n11-loading { padding:40px;text-align:center;color:#999; }
.isarud-n11-card { background:#fff;border:1px solid #ddd;border-radius:10px;padding:18px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,0.04); }
.isarud-n11-card h3 { margin:0 0 12px 0;font-size:15px;font-weight:600;color:#222; }
.isarud-n11-card-purple { background:linear-gradient(135deg,#faf5ff 0%,#f3e8ff 100%);border-color:#d8b4fe; }
.isarud-n11-section { display:none; }
.isarud-n11-section.active { display:block; }

.isarud-n11-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600; }
.isarud-n11-badge-green { background:#d1fae5;color:#065f46; }
.isarud-n11-badge-red { background:#fee2e2;color:#991b1b; }
.isarud-n11-badge-gray { background:#f3f4f6;color:#4b5563; }
.isarud-n11-badge-orange { background:#fed7aa;color:#9a3412; }
.isarud-n11-badge-blue { background:#dbeafe;color:#1e40af; }
.isarud-n11-badge-purple { background:#f3e8ff;color:#6b21a8; }

.isarud-n11-table { width:100%;font-size:13px;border-collapse:collapse;background:#fff; }
.isarud-n11-table th { text-align:left;padding:8px 10px;background:#f9fafb;font-weight:600;color:#4b5563;border-bottom:1px solid #e5e7eb; }
.isarud-n11-table td { padding:8px 10px;border-bottom:1px solid #f3f4f6; }
.isarud-n11-table tr:hover td { background:#faf5ff; }

.isarud-n11-btn { padding:7px 13px;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;border:1px solid transparent;text-decoration:none;display:inline-block; }
.isarud-n11-btn-primary { background:#7b2b8e;color:white;border-color:#7b2b8e; }
.isarud-n11-btn-primary:hover { background:#6a2580;color:white; }
.isarud-n11-btn-secondary { background:#fff;color:#4b5563;border-color:#d1d5db; }
.isarud-n11-btn-danger { background:#fff;color:#dc2626;border-color:#fca5a5; }
.isarud-n11-btn-danger:hover { background:#fef2f2; }

.isarud-n11-status-bar { display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:18px; }
.isarud-n11-modal { display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center; }
.isarud-n11-modal-box { background:#fff;border-radius:14px;padding:25px;max-width:520px;width:90%;max-height:80vh;overflow-y:auto; }
.isarud-n11-store-item { padding:12px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:8px;cursor:pointer;display:flex;align-items:center;gap:12px; }
.isarud-n11-store-item:hover { border-color:#7b2b8e;background:#faf5ff; }
.isarud-n11-store-item img { width:32px;height:32px;border-radius:6px;background:#f3f4f6;object-fit:cover; }
</style>

<div class="wrap" id="isarud-n11-app">
    <h1 style="display:flex;align-items:center;gap:12px;">
        <img src="<?php echo ISARUD_URL; ?>assets/n11-icon.png" alt="N11" style="width:40px;height:40px;border-radius:8px;background:#fff;padding:3px;border:1px solid #e9d5ff;" onerror="this.style.display='none'">
        <span><?php _e('N11 Yönetimi', 'api-isarud'); ?></span>
        <span style="background:#7b2b8e;color:white;padding:3px 10px;border-radius:12px;font-size:11px;">v6.7.0</span>
    </h1>

    <?php
    $cloud_key = get_option('isarud_cloud_api_key', '');
    if (empty($cloud_key)):
    ?>
    <div class="notice notice-warning">
        <p><strong><?php _e('Cloud Sync kurulmamış.', 'api-isarud'); ?></strong>
        <?php _e('Önce', 'api-isarud'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=isarud-cloud')); ?>"><?php _e('Cloud Sync sayfasına gidin', 'api-isarud'); ?></a>
        <?php _e('ve isarud.com hesabınıza bağlanın.', 'api-isarud'); ?></p>
    </div>
    <?php else: ?>

    <!-- Connect Modal -->
    <div id="isarud-n11-connect-modal" class="isarud-n11-modal">
        <div class="isarud-n11-modal-box">
            <h3 style="margin-top:0;"><?php _e('N11 ile Bağlan', 'api-isarud'); ?></h3>
            <p style="color:#6b7280;font-size:13px;"><?php _e('Bağlantı kuracağınız mağazayı seçin:', 'api-isarud'); ?></p>
            <div id="isarud-n11-stores-list" style="margin:20px 0;">
                <p style="text-align:center;color:#999;"><?php echo esc_js(__('Mağazalarınız yükleniyor...', 'api-isarud')); ?></p>
            </div>
            <div style="text-align:right;">
                <button type="button" class="button" id="isarud-n11-modal-close"><?php _e('Vazgeç', 'api-isarud'); ?></button>
            </div>
        </div>
    </div>

    <!-- Status bar -->
    <div class="isarud-n11-status-bar">
        <div style="display:flex;align-items:center;gap:14px;">
            <span id="isarud-n11-status-badge" class="isarud-n11-badge isarud-n11-badge-gray"><?php _e('Kontrol ediliyor...', 'api-isarud'); ?></span>
            <div id="isarud-n11-account-info" style="font-size:13px;color:#6b7280;"></div>
        </div>
        <div style="display:flex;gap:8px;">
            <button id="isarud-n11-connect-btn" class="isarud-n11-btn isarud-n11-btn-primary" style="display:none;"><?php _e('N11 ile Bağlan', 'api-isarud'); ?></button>
            <button id="isarud-n11-refresh-btn" class="isarud-n11-btn isarud-n11-btn-secondary">⟳ <?php _e('Yenile', 'api-isarud'); ?></button>
        </div>
    </div>

    <!-- Not connected CTA -->
    <div id="isarud-n11-not-connected-card" style="display:none;background:linear-gradient(135deg,#faf5ff 0%,#f3e8ff 100%);border:1px solid #d8b4fe;border-radius:14px;padding:40px 30px;text-align:center;margin-bottom:24px;">
        <div style="font-size:48px;margin-bottom:12px;">🔌</div>
        <h2 style="margin:0 0 10px 0;color:#7b2b8e;font-size:22px;"><?php _e('N11 mağazanızı bağlayın', 'api-isarud'); ?></h2>
        <p style="color:#6b7280;font-size:14px;line-height:1.6;max-width:480px;margin:0 auto 20px;">
            <?php _e('WordPress\'tan N11\'e ürün gönderme, sipariş çekme, stok senkronizasyonu için isarud.com hesabınızdan N11 mağazanızı bağlayın.', 'api-isarud'); ?>
        </p>
        <button id="isarud-n11-cta-connect" class="isarud-n11-btn isarud-n11-btn-primary" style="font-size:15px;padding:12px 24px;">
            <?php _e('N11 ile Bağlan', 'api-isarud'); ?>
        </button>
    </div>

    <!-- Tab navigation -->
    <h2 class="nav-tab-wrapper" id="isarud-n11-tabs" style="margin-bottom:0;display:none;">
        <a href="#listings"   class="nav-tab nav-tab-active" data-tab="listings">📦 <?php _e('Ürünler', 'api-isarud'); ?></a>
        <a href="#categories" class="nav-tab" data-tab="categories">📂 <?php _e('Kategoriler', 'api-isarud'); ?></a>
        <a href="#stock"      class="nav-tab" data-tab="stock">💰 <?php _e('Stok & Fiyat', 'api-isarud'); ?></a>
        <a href="#orders"     class="nav-tab" data-tab="orders">🛒 <?php _e('Siparişler', 'api-isarud'); ?></a>
        <a href="#tasks"      class="nav-tab" data-tab="tasks">⏱️ <?php _e('İşlemler', 'api-isarud'); ?></a>
        <a href="#settings"   class="nav-tab" data-tab="settings">⚙️ <?php _e('Ayarlar', 'api-isarud'); ?></a>
    </h2>

    <div id="isarud-n11-content-wrap" style="margin-top:24px;display:none;">

        <!-- TAB: LISTINGS -->
        <div class="isarud-n11-section active" id="section-listings">
            <div class="isarud-n11-card">
                <h3>📦 <?php _e('N11 Ürünleri', 'api-isarud'); ?></h3>
                <p style="color:#6b7280;font-size:13px;"><?php _e('N11 mağazanızdaki ürünleri görüntüleyin ve yönetin.', 'api-isarud'); ?></p>
                <button id="isarud-n11-load-listings" class="isarud-n11-btn isarud-n11-btn-primary"><?php _e('Ürünleri Yükle', 'api-isarud'); ?></button>
                <span id="isarud-n11-listings-count" style="color:#6b7280;font-size:12px;margin-left:8px;"></span>
                <div id="isarud-n11-listings-table" style="margin-top:15px;"></div>
            </div>
        </div>

        <!-- TAB: CATEGORIES -->
        <div class="isarud-n11-section" id="section-categories">
            <div class="isarud-n11-card">
                <h3>📂 <?php _e('N11 Kategori Ağacı', 'api-isarud'); ?></h3>
                <p style="color:#6b7280;font-size:13px;"><?php _e('N11 kategori listesini görüntüleyin. Ürün yüklemek için kategori ID gereklidir.', 'api-isarud'); ?></p>
                <button id="isarud-n11-load-categories" class="isarud-n11-btn isarud-n11-btn-primary"><?php _e('Kategorileri Yükle', 'api-isarud'); ?></button>
                <div id="isarud-n11-categories-tree" style="margin-top:15px;"></div>
            </div>

            <div class="isarud-n11-card">
                <h3>🔍 <?php _e('Kategori Öznitelikleri', 'api-isarud'); ?></h3>
                <p style="color:#6b7280;font-size:13px;"><?php _e('Bir kategorinin zorunlu/opsiyonel özniteliklerini sorgulayın.', 'api-isarud'); ?></p>
                <input type="number" id="isarud-n11-attr-cat-id" placeholder="<?php esc_attr_e('Kategori ID', 'api-isarud'); ?>" class="regular-text" style="width:200px;margin-right:8px;">
                <button id="isarud-n11-load-attributes" class="isarud-n11-btn isarud-n11-btn-primary"><?php _e('Özellikleri Yükle', 'api-isarud'); ?></button>
                <div id="isarud-n11-attributes-result" style="margin-top:15px;"></div>
            </div>
        </div>

        <!-- TAB: STOCK & PRICE -->
        <div class="isarud-n11-section" id="section-stock">
            <div class="isarud-n11-card isarud-n11-card-purple">
                <h3 style="color:#7b2b8e;">🚀 <?php _e('Otomatik Ürün Gönderimi (Auto-Export)', 'api-isarud'); ?></h3>
                <p style="color:#444;font-size:13px;line-height:1.6;"><?php _e('WooCommerce\'de yeni ürün eklediğinizde veya güncellediğinizde, ürün otomatik olarak N11\'e (ve diğer aktif pazaryerlerine) gönderilebilir.', 'api-isarud'); ?></p>

                <label style="display:flex;align-items:center;gap:12px;background:#fff;padding:12px 15px;border-radius:8px;border:1px solid #ddd;cursor:pointer;">
                    <input type="checkbox" id="isarud-n11-auto-export" <?php echo get_option('isarud_n11_auto_export', '0') === '1' ? 'checked' : ''; ?>>
                    <span style="flex:1;font-weight:600;font-size:15px;"><?php _e('Otomatik Gönderim', 'api-isarud'); ?></span>
                    <span id="isarud-n11-auto-status" class="isarud-n11-badge <?php echo get_option('isarud_n11_auto_export', '0') === '1' ? 'isarud-n11-badge-green' : 'isarud-n11-badge-gray'; ?>">
                        <?php echo get_option('isarud_n11_auto_export', '0') === '1' ? __('Aktif', 'api-isarud') : __('Kapalı', 'api-isarud'); ?>
                    </span>
                </label>

                <p style="color:#6b7280;font-size:12px;margin-top:12px;">⚠️ <?php _e('Auto-Export için ürünün N11 kategorisi ve zorunlu öznitelikleri eşleştirilmelidir.', 'api-isarud'); ?></p>
            </div>

            <div class="isarud-n11-card">
                <h3>📊 <?php _e('Toplu Stok/Fiyat Güncelleme', 'api-isarud'); ?></h3>
                <p style="color:#6b7280;font-size:13px;"><?php _e('Tüm WooCommerce ürünlerinin stok/fiyat bilgisini N11\'e gönderir. Maks. 1000 ürün/batch (async, taskId döner).', 'api-isarud'); ?></p>
                <button id="isarud-n11-bulk-sync" class="isarud-n11-btn isarud-n11-btn-secondary"><?php _e('Toplu Senkronizasyon Başlat', 'api-isarud'); ?></button>
                <div id="isarud-n11-bulk-result" style="margin-top:15px;"></div>
                <p style="color:#6b7280;font-size:12px;margin-top:12px;">💡 <?php _e('Sonucu "İşlemler" sekmesinden taskId ile takip edebilirsiniz.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB: ORDERS -->
        <div class="isarud-n11-section" id="section-orders">
            <div class="isarud-n11-card">
                <h3>🛒 <?php _e('N11 Siparişleri', 'api-isarud'); ?></h3>
                <p style="color:#6b7280;font-size:13px;"><?php _e('N11\'den gelen son sipariş paketlerini görüntüleyin.', 'api-isarud'); ?></p>
                <button id="isarud-n11-load-orders" class="isarud-n11-btn isarud-n11-btn-primary"><?php _e('Siparişleri Yükle', 'api-isarud'); ?></button>
                <div id="isarud-n11-orders-table" style="margin-top:15px;"></div>
            </div>
        </div>

        <!-- TAB: TASKS -->
        <div class="isarud-n11-section" id="section-tasks">
            <div class="isarud-n11-card">
                <h3>⏱️ <?php _e('Async İşlem Takibi', 'api-isarud'); ?></h3>
                <p style="color:#6b7280;font-size:13px;line-height:1.6;"><?php _e('N11 ürün/stok/fiyat güncellemeleri async (asenkron) çalışır. Her işlem için bir taskId döner. Bu sekmeden taskId\'nin durumunu sorgulayabilirsiniz.', 'api-isarud'); ?></p>

                <div style="display:flex;gap:8px;margin-bottom:15px;">
                    <input type="text" id="isarud-n11-task-id" placeholder="<?php esc_attr_e('Task ID (örn: 12345678)', 'api-isarud'); ?>" class="regular-text" style="flex:1;font-family:monospace;">
                    <button id="isarud-n11-check-task" class="isarud-n11-btn isarud-n11-btn-primary"><?php _e('Durumu Sorgula', 'api-isarud'); ?></button>
                </div>

                <div id="isarud-n11-task-result"></div>

                <p style="color:#6b7280;font-size:12px;margin-top:15px;">
                    💡 <?php _e('PROCESSED = İşlem tamamlandı | IN_QUEUE = Sırada bekliyor | REJECT = Reddedildi', 'api-isarud'); ?>
                </p>
            </div>
        </div>

        <!-- TAB: SETTINGS -->
        <div class="isarud-n11-section" id="section-settings">
            <div class="isarud-n11-card">
                <h3>⚙️ <?php _e('Bağlantı Ayarları', 'api-isarud'); ?></h3>
                <p style="color:#6b7280;font-size:13px;"><?php _e('N11 bağlantınızı test edin veya kaldırın.', 'api-isarud'); ?></p>
                <div style="display:flex;gap:8px;">
                    <button id="isarud-n11-retest" class="isarud-n11-btn isarud-n11-btn-secondary"><?php _e('Bağlantıyı Test Et', 'api-isarud'); ?></button>
                    <button id="isarud-n11-disconnect" class="isarud-n11-btn isarud-n11-btn-danger"><?php _e('Bağlantıyı Kaldır', 'api-isarud'); ?></button>
                </div>
                <div id="isarud-n11-settings-result" style="margin-top:15px;"></div>
            </div>
        </div>

    </div>

    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var NONCE = '<?php echo wp_create_nonce("isarud_n11_nonce"); ?>';
    var STORE_ID = 0; // Connect sırasında set edilir
    var T = {
        loading: '<?php echo esc_js(__("Yükleniyor...", "api-isarud")); ?>',
        error: '<?php echo esc_js(__("Hata", "api-isarud")); ?>',
        connected: '✓ <?php echo esc_js(__("Bağlı", "api-isarud")); ?>',
        not_connected: '<?php echo esc_js(__("Bağlı değil", "api-isarud")); ?>',
        no_data: '<?php echo esc_js(__("Veri yok", "api-isarud")); ?>',
        delete_confirm: '<?php echo esc_js(__("Bu ürünü N11'den silmek istediğinize emin misiniz?", "api-isarud")); ?>',
        disconnect_confirm: '<?php echo esc_js(__("N11 bağlantısını kaldırmak istediğinize emin misiniz?", "api-isarud")); ?>',
        bulk_confirm: '<?php echo esc_js(__("Tüm WC ürünleri N11'e gönderilecek. Devam edilsin mi?", "api-isarud")); ?>',
    };

    // Tab switching
    $('#isarud-n11-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        $('#isarud-n11-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.isarud-n11-section').removeClass('active');
        $('#section-' + $(this).data('tab')).addClass('active');
    });

    // ─── STATUS ───
    function loadStatus() {
        $('#isarud-n11-status-badge').text(T.loading).removeClass().addClass('isarud-n11-badge isarud-n11-badge-gray');
        $.post(ajaxurl, { action: 'isarud_n11_status', nonce: NONCE }, function(r) {
            if (r && r.connected) {
                // ✓ Bağlı: tüm UI göster
                $('#isarud-n11-status-badge').text(T.connected).removeClass().addClass('isarud-n11-badge isarud-n11-badge-green');
                if (r.store) STORE_ID = r.store.id;
                var info = (r.store && r.store.name ? r.store.name : '') + (r.account_name ? ' · ' + r.account_name : '');
                $('#isarud-n11-account-info').text(info);
                $('#isarud-n11-connect-btn').hide();
                $('#isarud-n11-not-connected-card').hide();
                $('#isarud-n11-tabs').show();
                $('#isarud-n11-content-wrap').show();
            } else {
                // ✗ Bağlı değil: sadece CTA göster, tabs ve content gizle
                $('#isarud-n11-status-badge').text(T.not_connected).removeClass().addClass('isarud-n11-badge isarud-n11-badge-red');
                $('#isarud-n11-account-info').text(r && r.message ? r.message : '');
                $('#isarud-n11-connect-btn').show();
                $('#isarud-n11-not-connected-card').show();
                $('#isarud-n11-tabs').hide();
                $('#isarud-n11-content-wrap').hide();
            }
        }).fail(function() {
            $('#isarud-n11-status-badge').text(T.error).removeClass().addClass('isarud-n11-badge isarud-n11-badge-red');
            $('#isarud-n11-not-connected-card').show();
            $('#isarud-n11-tabs').hide();
            $('#isarud-n11-content-wrap').hide();
        });
    }

    // CTA "Bağlan" butonu üstteki Connect butonu ile aynı işi yapsın
    $(document).on('click', '#isarud-n11-cta-connect', function() {
        $('#isarud-n11-connect-btn').click();
    });

    $('#isarud-n11-refresh-btn').on('click', loadStatus);

    // ─── CONNECT FLOW ───
    $('#isarud-n11-connect-btn').on('click', function() {
        $('#isarud-n11-connect-modal').css('display','flex');
        $('#isarud-n11-stores-list').html('<p style="text-align:center;color:#999;">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_n11_stores', nonce: NONCE }, function(r) {
            if (!r || !r.success || !r.stores || r.stores.length === 0) {
                $('#isarud-n11-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__("Mağaza bulunamadı veya yüklenemedi.", "api-isarud")); ?></p>');
                return;
            }
            var html = '';
            r.stores.forEach(function(s) {
                html += '<div class="isarud-n11-store-item" data-store-id="' + s.id + '">';
                if (s.logo_url) html += '<img src="' + s.logo_url + '" alt="">';
                html += '<div style="flex:1;"><strong>' + (s.name || '—') + '</strong><br><span style="color:#6b7280;font-size:12px;">' + (s.slug || '') + '</span></div>';
                html += '</div>';
            });
            $('#isarud-n11-stores-list').html(html);
        }).fail(function() {
            $('#isarud-n11-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__("İstek başarısız.", "api-isarud")); ?></p>');
        });
    });

    $('#isarud-n11-modal-close').on('click', function() {
        $('#isarud-n11-connect-modal').hide();
    });

    $(document).on('click', '.isarud-n11-store-item', function() {
        var sid = $(this).data('store-id');
        // Connect URL al ve yeni tab'da aç (Trendyol pattern)
        $.post(ajaxurl, { action: 'isarud_n11_connect_url', nonce: NONCE, store_id: sid }, function(r) {
            if (r && r.success && r.connect_url) {
                $('#isarud-n11-connect-modal').hide();
                window.open(r.connect_url, '_blank');
            } else {
                alert((r && r.message) || T.error);
            }
        }).fail(function() {
            alert(T.error);
        });
    });

    // ─── LISTINGS ───
    $('#isarud-n11-load-listings').on('click', function() {
        var $w = $('#isarud-n11-listings-table');
        $w.html('<p style="color:#999">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_n11_listings', nonce: NONCE, page: 0, size: 50 }, function(r) {
            if (!r || !r.success) { $w.html('<p style="color:#d32f2f">' + (r && r.message ? r.message : T.error) + '</p>'); return; }
            var items = r.items || [];
            $('#isarud-n11-listings-count').text(items.length + ' / ' + (r.totalElements || items.length));
            if (items.length === 0) { $w.html('<p style="color:#999">' + T.no_data + '</p>'); return; }
            var html = '<table class="isarud-n11-table"><thead><tr><th>SKU</th><th><?php echo esc_js(__("Başlık", "api-isarud")); ?></th><th><?php echo esc_js(__("Fiyat", "api-isarud")); ?></th><th><?php echo esc_js(__("Stok", "api-isarud")); ?></th><th><?php echo esc_js(__("Durum", "api-isarud")); ?></th><th></th></tr></thead><tbody>';
            items.forEach(function(item) {
                var sku = item.stockCode || item.productSellerCode || '—';
                var title = (item.title || item.productName || '—').substring(0, 50);
                var price = item.salePrice || item.price || '—';
                var stock = item.quantity || item.stockAmount || 0;
                var status = item.saleStatus || item.status || '—';
                var sclass = (status === 'Active') ? 'isarud-n11-badge-green' : 'isarud-n11-badge-gray';
                html += '<tr>';
                html += '<td style="font-family:monospace;font-size:12px;">' + sku + '</td>';
                html += '<td>' + title + '</td>';
                html += '<td>' + price + ' ₺</td>';
                html += '<td>' + stock + '</td>';
                html += '<td><span class="isarud-n11-badge ' + sclass + '">' + status + '</span></td>';
                html += '<td><button class="isarud-n11-btn isarud-n11-btn-danger" data-sku="' + sku + '"><?php echo esc_js(__("Sil", "api-isarud")); ?></button></td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $w.html(html);
        });
    });

    $(document).on('click', '#isarud-n11-listings-table .isarud-n11-btn-danger', function() {
        var sku = $(this).data('sku');
        if (!confirm(T.delete_confirm + '\n\nSKU: ' + sku)) return;
        $.post(ajaxurl, { action: 'isarud_n11_product_delete', nonce: NONCE, stock_code: sku }, function(r) {
            alert(r && r.message ? r.message : (r && r.success ? 'OK' : T.error));
            if (r && r.success) $('#isarud-n11-load-listings').click();
        });
    });

    // ─── CATEGORIES ───
    $('#isarud-n11-load-categories').on('click', function() {
        var $w = $('#isarud-n11-categories-tree');
        $w.html('<p style="color:#999">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_n11_categories', nonce: NONCE }, function(r) {
            if (!r || !r.success) { $w.html('<p style="color:#d32f2f">' + (r && r.message ? r.message : T.error) + '</p>'); return; }
            var cats = r.categories || [];
            if (cats.length === 0) { $w.html('<p style="color:#999">' + T.no_data + '</p>'); return; }
            var html = '<div style="max-height:400px;overflow-y:auto;border:1px solid #ddd;border-radius:8px;padding:10px;background:#fff;">';
            cats.forEach(function(c) {
                var id = c.id || c.categoryId;
                var name = c.name || c.categoryName || '—';
                html += '<div style="padding:6px 8px;border-bottom:1px solid #f3f4f6;font-size:13px;"><span style="color:#7b2b8e;font-family:monospace;font-size:11px;margin-right:8px;">[' + id + ']</span>' + name + '</div>';
            });
            html += '</div>';
            $w.html(html);
        });
    });

    $('#isarud-n11-load-attributes').on('click', function() {
        var catId = $('#isarud-n11-attr-cat-id').val();
        if (!catId) { alert(T.error); return; }
        var $w = $('#isarud-n11-attributes-result');
        $w.html('<p style="color:#999">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_n11_category_attributes', nonce: NONCE, category_id: catId }, function(r) {
            if (!r || !r.success) { $w.html('<p style="color:#d32f2f">' + (r && r.message ? r.message : T.error) + '</p>'); return; }
            var attrs = r.attributes || [];
            if (attrs.length === 0) { $w.html('<p style="color:#999">' + T.no_data + '</p>'); return; }
            var html = '<table class="isarud-n11-table"><thead><tr><th>ID</th><th><?php echo esc_js(__("Özellik Adı", "api-isarud")); ?></th><th><?php echo esc_js(__("Zorunlu", "api-isarud")); ?></th><th><?php echo esc_js(__("Varyant", "api-isarud")); ?></th></tr></thead><tbody>';
            attrs.forEach(function(a) {
                html += '<tr>';
                html += '<td style="font-family:monospace;font-size:12px;">' + (a.id || '—') + '</td>';
                html += '<td>' + (a.name || '—') + '</td>';
                html += '<td>' + (a.mandatory ? '✓' : '—') + '</td>';
                html += '<td>' + (a.variant ? '✓' : '—') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $w.html(html);
        });
    });

    // ─── STOCK & PRICE ───
    $('#isarud-n11-auto-export').on('change', function() {
        var enabled = $(this).is(':checked') ? '1' : '0';
        $.post(ajaxurl, { action: 'isarud_set_option', nonce: NONCE, key: 'isarud_n11_auto_export', value: enabled });
        $('#isarud-n11-auto-status').text(enabled === '1' ? '<?php echo esc_js(__("Aktif", "api-isarud")); ?>' : '<?php echo esc_js(__("Kapalı", "api-isarud")); ?>')
            .removeClass('isarud-n11-badge-green isarud-n11-badge-gray')
            .addClass(enabled === '1' ? 'isarud-n11-badge-green' : 'isarud-n11-badge-gray');
    });

    $('#isarud-n11-bulk-sync').on('click', function() {
        if (!confirm(T.bulk_confirm)) return;
        var $w = $('#isarud-n11-bulk-result');
        $w.html('<p style="color:#999">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_bulk_sync_to_marketplace', nonce: NONCE, marketplace: 'n11' }, function(r) {
            $w.html('<pre style="background:#f9fafb;padding:10px;border-radius:6px;font-size:12px;max-height:300px;overflow-y:auto;">' + JSON.stringify(r, null, 2) + '</pre>');
        });
    });

    // ─── ORDERS ───
    $('#isarud-n11-load-orders').on('click', function() {
        var $w = $('#isarud-n11-orders-table');
        $w.html('<p style="color:#999">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_n11_orders', nonce: NONCE, page: 0, size: 30 }, function(r) {
            if (!r || !r.success) { $w.html('<p style="color:#d32f2f">' + (r && r.message ? r.message : T.error) + '</p>'); return; }
            var orders = r.orders || [];
            if (orders.length === 0) { $w.html('<p style="color:#999">' + T.no_data + '</p>'); return; }
            var html = '<table class="isarud-n11-table"><thead><tr><th><?php echo esc_js(__("Sipariş No", "api-isarud")); ?></th><th><?php echo esc_js(__("Müşteri", "api-isarud")); ?></th><th><?php echo esc_js(__("Tutar", "api-isarud")); ?></th><th><?php echo esc_js(__("Durum", "api-isarud")); ?></th><th><?php echo esc_js(__("Kargo", "api-isarud")); ?></th></tr></thead><tbody>';
            orders.forEach(function(o) {
                html += '<tr>';
                html += '<td style="font-family:monospace;font-size:12px;">' + (o.orderNumber || '—') + '</td>';
                html += '<td>' + (o.customerfullName || (o.shippingAddress && o.shippingAddress.fullName) || '—') + '</td>';
                html += '<td>' + (o.totalDiscountedPrice || '—') + ' ₺</td>';
                html += '<td><span class="isarud-n11-badge isarud-n11-badge-blue">' + (o.status || '—') + '</span></td>';
                html += '<td>' + (o.cargoTrackingNumber || '—') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $w.html(html);
        });
    });

    // ─── TASKS ───
    $('#isarud-n11-check-task').on('click', function() {
        var taskId = $.trim($('#isarud-n11-task-id').val());
        if (!taskId) { alert(T.error); return; }
        var $w = $('#isarud-n11-task-result');
        $w.html('<p style="color:#999">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_n11_task_status', nonce: NONCE, task_id: taskId }, function(r) {
            if (!r || !r.success) { $w.html('<p style="color:#d32f2f">' + (r && r.message ? r.message : T.error) + '</p>'); return; }
            var sclass = 'isarud-n11-badge-gray';
            if (r.status === 'PROCESSED') sclass = 'isarud-n11-badge-green';
            else if (r.status === 'IN_QUEUE') sclass = 'isarud-n11-badge-orange';
            else if (r.status === 'REJECT') sclass = 'isarud-n11-badge-red';

            var html = '<div style="background:#f9fafb;padding:15px;border-radius:8px;border:1px solid #e5e7eb;">';
            html += '<div style="margin-bottom:10px;"><strong>Task ID:</strong> <span style="font-family:monospace;font-size:12px;">' + r.task_id + '</span></div>';
            html += '<div style="margin-bottom:10px;"><strong><?php echo esc_js(__("Durum", "api-isarud")); ?>:</strong> <span class="isarud-n11-badge ' + sclass + '">' + r.status + '</span></div>';
            html += '<div style="margin-bottom:10px;"><strong><?php echo esc_js(__("Toplam Öğe", "api-isarud")); ?>:</strong> ' + (r.totalElements || 0) + '</div>';
            if ((r.items || []).length > 0) {
                html += '<details style="margin-top:10px;"><summary style="cursor:pointer;font-weight:600;color:#7b2b8e;"><?php echo esc_js(__("Detayları Göster", "api-isarud")); ?></summary>';
                html += '<table class="isarud-n11-table" style="margin-top:10px;"><thead><tr><th>SKU</th><th><?php echo esc_js(__("Durum", "api-isarud")); ?></th><th><?php echo esc_js(__("Mesaj", "api-isarud")); ?></th></tr></thead><tbody>';
                (r.items || []).forEach(function(i) {
                    var iclass = (i.status === 'SUCCESS') ? 'isarud-n11-badge-green' : 'isarud-n11-badge-red';
                    html += '<tr>';
                    html += '<td style="font-family:monospace;font-size:12px;">' + (i.itemCode || '—') + '</td>';
                    html += '<td><span class="isarud-n11-badge ' + iclass + '">' + (i.status || '—') + '</span></td>';
                    html += '<td>' + (i.errorMessage || i.message || '—') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></details>';
            }
            html += '</div>';
            $w.html(html);
        });
    });

    // ─── SETTINGS ───
    $('#isarud-n11-retest').on('click', function() {
        var $w = $('#isarud-n11-settings-result');
        $w.html('<p style="color:#999">' + T.loading + '</p>');
        $.post(ajaxurl, { action: 'isarud_n11_status', nonce: NONCE }, function(r) {
            $w.html('<p style="color:' + (r && r.connected ? '#10b981' : '#d32f2f') + '">' + (r && r.message ? r.message : (r.connected ? 'OK' : T.error)) + '</p>');
            loadStatus();
        });
    });

    $('#isarud-n11-disconnect').on('click', function() {
        if (!confirm(T.disconnect_confirm)) return;
        $.post(ajaxurl, { action: 'isarud_n11_disconnect', nonce: NONCE, store_id: STORE_ID }, function(r) {
            alert(r && r.message ? r.message : (r && r.success ? 'OK' : T.error));
            if (r && r.success) loadStatus();
        });
    });

    // Init
    loadStatus();
});
</script>