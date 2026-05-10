<?php
/**
 * Etsy Pazaryeri Yönetim Sayfası — Phase 1-8 Sekmeli
 *
 * @package api-isarud
 * @since 6.5.0
 */
if (!defined('ABSPATH')) exit;

// Callback success notice
$callback_status = sanitize_text_field($_GET['etsy'] ?? '');
$nonce = wp_create_nonce('isarud_etsy_nonce');
?>

<div class="wrap">
    <h1 style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:28px;">🛍️</span>
        <?php esc_html_e('Etsy Pazaryeri Yönetimi', 'api-isarud'); ?>
        <span style="background:linear-gradient(135deg,#f56565 0%,#ed8936 100%);color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">v6.5.0</span>
    </h1>

    <?php if ($callback_status === 'connected'): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('✅ Etsy bağlantısı başarılı!', 'api-isarud'); ?></p></div>
    <?php elseif ($callback_status === 'failed'): ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('❌ Etsy bağlantısı başarısız oldu.', 'api-isarud'); ?></p></div>
    <?php endif; ?>

    <!-- Status Bar (Üst tarafta her zaman görünür) -->
    <div id="isarud-etsy-status-bar" style="background:white;border:1px solid #e5e7eb;border-radius:8px;padding:14px 18px;margin:14px 0;display:flex;align-items:center;justify-content:space-between;">
        <div id="isarud-etsy-status-content" style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:18px;">⏳</span>
            <span><?php esc_html_e('Bağlantı kontrol ediliyor...', 'api-isarud'); ?></span>
        </div>
        <button type="button" class="button" id="isarud-etsy-refresh-status">🔄 <?php esc_html_e('Yenile', 'api-isarud'); ?></button>
    </div>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper" id="isarud-etsy-tabs" style="margin-top:20px;">
        <a href="#listings" class="nav-tab nav-tab-active" data-tab="listings">📦 <?php esc_html_e('Ürünler', 'api-isarud'); ?></a>
        <a href="#images" class="nav-tab" data-tab="images">🖼️ <?php esc_html_e('Resimler', 'api-isarud'); ?></a>
        <a href="#sections" class="nav-tab" data-tab="sections">📋 <?php esc_html_e('Bölümler', 'api-isarud'); ?></a>
        <a href="#translations" class="nav-tab" data-tab="translations">🌍 <?php esc_html_e('Çeviriler', 'api-isarud'); ?></a>
        <a href="#shipping" class="nav-tab" data-tab="shipping">🚚 <?php esc_html_e('Kargo Profilleri', 'api-isarud'); ?></a>
        <a href="#shop" class="nav-tab" data-tab="shop">🏪 <?php esc_html_e('Mağaza', 'api-isarud'); ?></a>
        <a href="#stats" class="nav-tab" data-tab="stats">📊 <?php esc_html_e('İstatistik', 'api-isarud'); ?></a>
        <a href="#returns" class="nav-tab" data-tab="returns">↩️ <?php esc_html_e('İade Politikaları', 'api-isarud'); ?></a>
    </nav>

    <!-- TAB CONTENT WRAPPER -->
    <div id="isarud-etsy-tab-content" style="background:white;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;padding:24px;min-height:400px;">

        <!-- TAB: LISTINGS -->
        <div class="isarud-tab-pane active" data-tab-pane="listings">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h2 style="margin:0;"><?php esc_html_e('Etsy Ürün Listesi', 'api-isarud'); ?></h2>
                <div>
                    <select id="isarud-etsy-page-size" style="margin-right:8px;">
                        <option value="10">10 / <?php esc_html_e('sayfa', 'api-isarud'); ?></option>
                        <option value="25" selected>25 / <?php esc_html_e('sayfa', 'api-isarud'); ?></option>
                        <option value="50">50 / <?php esc_html_e('sayfa', 'api-isarud'); ?></option>
                        <option value="100">100 / <?php esc_html_e('sayfa', 'api-isarud'); ?></option>
                    </select>
                    <button type="button" class="button button-primary" id="isarud-etsy-refresh-listings">🔄 <?php esc_html_e('Yenile', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-etsy-listings-container"><p style="text-align:center;padding:40px;color:#6b7280;">⏳ <?php esc_html_e('Yükleniyor...', 'api-isarud'); ?></p></div>
        </div>

        <!-- TAB: IMAGES -->
        <div class="isarud-tab-pane" data-tab-pane="images" style="display:none;">
            <h2><?php esc_html_e('Listing Resim Yönetimi', 'api-isarud'); ?></h2>
            <p style="color:#6b7280;"><?php esc_html_e('Bir listing seçin, resimlerini yönetin.', 'api-isarud'); ?></p>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;">
                <label><?php esc_html_e('Listing ID:', 'api-isarud'); ?></label>
                <input type="number" id="isarud-img-listing-id" class="regular-text" placeholder="<?php esc_attr_e('Listing ID girin', 'api-isarud'); ?>">
                <button type="button" class="button button-primary" id="isarud-img-load">📥 <?php esc_html_e('Resimleri Yükle', 'api-isarud'); ?></button>
            </div>
            <div id="isarud-img-container" style="margin-top:20px;"></div>

            <h3 style="margin-top:32px;"><?php esc_html_e('Yeni Resim Yükle', 'api-isarud'); ?></h3>
            <div style="background:#f9fafb;padding:16px;border-radius:6px;">
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <input type="url" id="isarud-img-url" class="regular-text" style="flex:1;min-width:300px;" placeholder="https://example.com/image.jpg">
                    <input type="number" id="isarud-img-rank" placeholder="<?php esc_attr_e('Sıra', 'api-isarud'); ?>" value="1" min="1" style="width:80px;">
                    <button type="button" class="button button-primary" id="isarud-img-upload-btn">⬆️ <?php esc_html_e('Yükle', 'api-isarud'); ?></button>
                </div>
            </div>
        </div>

        <!-- TAB: SECTIONS -->
        <div class="isarud-tab-pane" data-tab-pane="sections" style="display:none;">
            <h2><?php esc_html_e('Mağaza Bölümleri', 'api-isarud'); ?></h2>
            <p style="color:#6b7280;"><?php esc_html_e('Etsy mağazanızın bölümlerini (kategorileri) yönetin.', 'api-isarud'); ?></p>
            <button type="button" class="button button-primary" id="isarud-sections-load">📥 <?php esc_html_e('Bölümleri Yükle', 'api-isarud'); ?></button>
            <div id="isarud-sections-container" style="margin-top:16px;"></div>

            <h3 style="margin-top:32px;"><?php esc_html_e('Yeni Bölüm Ekle', 'api-isarud'); ?></h3>
            <div style="background:#f9fafb;padding:16px;border-radius:6px;">
                <input type="text" id="isarud-section-title" class="regular-text" placeholder="<?php esc_attr_e('Bölüm adı', 'api-isarud'); ?>">
                <button type="button" class="button button-primary" id="isarud-section-create-btn">➕ <?php esc_html_e('Oluştur', 'api-isarud'); ?></button>
            </div>
        </div>

        <!-- TAB: TRANSLATIONS -->
        <div class="isarud-tab-pane" data-tab-pane="translations" style="display:none;">
            <h2><?php esc_html_e('Listing Çevirileri', 'api-isarud'); ?></h2>
            <p style="color:#6b7280;"><?php esc_html_e('16 dilde listing çevirilerini görüntüleyin ve düzenleyin.', 'api-isarud'); ?></p>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;">
                <label><?php esc_html_e('Listing ID:', 'api-isarud'); ?></label>
                <input type="number" id="isarud-tr-listing-id" class="regular-text" placeholder="<?php esc_attr_e('Listing ID girin', 'api-isarud'); ?>">
                <button type="button" class="button button-primary" id="isarud-tr-load">📥 <?php esc_html_e('Çevirileri Yükle', 'api-isarud'); ?></button>
            </div>
            <div id="isarud-tr-container" style="margin-top:20px;"></div>
        </div>

        <!-- TAB: SHIPPING -->
        <div class="isarud-tab-pane" data-tab-pane="shipping" style="display:none;">
            <h2><?php esc_html_e('Kargo Profilleri', 'api-isarud'); ?></h2>
            <p style="color:#6b7280;"><?php esc_html_e('Etsy mağazanızın kargo profillerini yönetin.', 'api-isarud'); ?></p>
            <button type="button" class="button button-primary" id="isarud-ship-load">📥 <?php esc_html_e('Profilleri Yükle', 'api-isarud'); ?></button>
            <div id="isarud-ship-container" style="margin-top:16px;"></div>
        </div>

        <!-- TAB: SHOP -->
        <div class="isarud-tab-pane" data-tab-pane="shop" style="display:none;">
            <h2><?php esc_html_e('Mağaza Bilgileri', 'api-isarud'); ?></h2>
            <button type="button" class="button button-primary" id="isarud-shop-load">📥 <?php esc_html_e('Mağaza Bilgilerini Yükle', 'api-isarud'); ?></button>
            <div id="isarud-shop-container" style="margin-top:20px;"></div>
        </div>

        <!-- TAB: STATS -->
        <div class="isarud-tab-pane" data-tab-pane="stats" style="display:none;">
            <h2><?php esc_html_e('İstatistik & Envanter', 'api-isarud'); ?></h2>
            <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:24px;">
                <div style="flex:1;min-width:300px;background:#f9fafb;padding:16px;border-radius:8px;">
                    <h3 style="margin-top:0;">📊 <?php esc_html_e('Listing İstatistik', 'api-isarud'); ?></h3>
                    <input type="number" id="isarud-stats-listing-id" class="regular-text" placeholder="<?php esc_attr_e('Listing ID', 'api-isarud'); ?>">
                    <button type="button" class="button" id="isarud-stats-listing-btn"><?php esc_html_e('İstatistik', 'api-isarud'); ?></button>
                    <button type="button" class="button" id="isarud-inv-btn"><?php esc_html_e('Envanter', 'api-isarud'); ?></button>
                    <div id="isarud-stats-listing-out" style="margin-top:12px;"></div>
                </div>
                <div style="flex:1;min-width:300px;background:#f9fafb;padding:16px;border-radius:8px;">
                    <h3 style="margin-top:0;">💰 <?php esc_html_e('Satılan Ürünler', 'api-isarud'); ?></h3>
                    <button type="button" class="button button-primary" id="isarud-sold-btn"><?php esc_html_e('Yükle', 'api-isarud'); ?></button>
                    <div id="isarud-sold-out" style="margin-top:12px;"></div>
                </div>
            </div>
        </div>

        <!-- TAB: RETURNS -->
        <div class="isarud-tab-pane" data-tab-pane="returns" style="display:none;">
            <h2><?php esc_html_e('İade Politikaları', 'api-isarud'); ?></h2>
            <p style="color:#6b7280;"><?php esc_html_e('Etsy mağazanızın iade politikalarını yönetin.', 'api-isarud'); ?></p>
            <button type="button" class="button button-primary" id="isarud-ret-load">📥 <?php esc_html_e('Politikaları Yükle', 'api-isarud'); ?></button>
            <div id="isarud-ret-container" style="margin-top:16px;"></div>
        </div>

    </div><!-- /tab-content -->

    <!-- Connect Card (sadece bağlı değilse görünür) -->
    <div id="isarud-etsy-connect-card" style="display:none;background:linear-gradient(135deg,#fff5f5 0%,#ffe8e8 100%);border:1px solid #fc8181;border-radius:8px;padding:24px;margin-top:20px;">
        <h3 style="margin:0 0 12px 0;"><?php esc_html_e('Etsy Mağazanızı Bağlayın', 'api-isarud'); ?></h3>
        <p style="margin:0 0 16px 0;color:#5b6770;"><?php esc_html_e('Etsy hesabınıza Isarud uygulaması üzerinden tek tıkla bağlanın.', 'api-isarud'); ?></p>
        <button type="button" class="button button-primary" id="isarud-etsy-connect-btn" style="background:#f56565;border-color:#e53e3e;color:white;font-size:14px;padding:8px 20px;height:auto;">
            🔗 <?php esc_html_e('Etsy ile Bağlan', 'api-isarud'); ?>
        </button>
    </div>

    <!-- Store Selection Modal -->
    <div id="isarud-etsy-store-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;">
        <div style="background:white;border-radius:8px;padding:24px;max-width:500px;width:90%;">
            <h3 style="margin-top:0;"><?php esc_html_e('Hangi Isarud mağazası?', 'api-isarud'); ?></h3>
            <div id="isarud-etsy-store-list"></div>
            <button type="button" class="button" id="isarud-etsy-store-cancel" style="margin-top:16px;"><?php esc_html_e('İptal', 'api-isarud'); ?></button>
        </div>
    </div>

</div>

<script>
(function($) {
    var ajaxUrl = ajaxurl;
    var nonce = '<?php echo esc_js($nonce); ?>';

    var i18n = {
        loading: <?php echo wp_json_encode(__('Yükleniyor...', 'api-isarud')); ?>,
        error: <?php echo wp_json_encode(__('Hata', 'api-isarud')); ?>,
        success: <?php echo wp_json_encode(__('Başarılı', 'api-isarud')); ?>,
        confirmDelete: <?php echo wp_json_encode(__('Silmek istediğinize emin misiniz?', 'api-isarud')); ?>,
        active: <?php echo wp_json_encode(__('Aktif', 'api-isarud')); ?>,
        inactive: <?php echo wp_json_encode(__('Pasif', 'api-isarud')); ?>,
        draft: <?php echo wp_json_encode(__('Taslak', 'api-isarud')); ?>,
        sold_out: <?php echo wp_json_encode(__('Tükendi', 'api-isarud')); ?>,
        noListings: <?php echo wp_json_encode(__('Etsy mağazanızda ürün bulunamadı.', 'api-isarud')); ?>,
        noImages: <?php echo wp_json_encode(__('Bu listing için resim yok.', 'api-isarud')); ?>,
        noSections: <?php echo wp_json_encode(__('Henüz bölüm yok.', 'api-isarud')); ?>,
        noShipping: <?php echo wp_json_encode(__('Kargo profili yok.', 'api-isarud')); ?>,
        noReturns: <?php echo wp_json_encode(__('İade politikası yok.', 'api-isarud')); ?>,
        actionActivate: <?php echo wp_json_encode(__('Aktifleştir', 'api-isarud')); ?>,
        actionDeactivate: <?php echo wp_json_encode(__('Pasifleştir', 'api-isarud')); ?>,
        actionDelete: <?php echo wp_json_encode(__('Sil', 'api-isarud')); ?>,
        actionView: <?php echo wp_json_encode(__('Etsy\'de Gör', 'api-isarud')); ?>,
        actionEdit: <?php echo wp_json_encode(__('Düzenle', 'api-isarud')); ?>,
        actionSave: <?php echo wp_json_encode(__('Kaydet', 'api-isarud')); ?>
    };

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    // ─── TAB SWITCHING with AUTO-LOAD ───────────────────────
    var firstListingId = null; // Otomatik kullanılacak ilk listing ID
    var tabsLoaded = { listings: false, images: false, sections: false, translations: false, shipping: false, shop: false, stats: false, returns: false };

    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var $tab = $(this);
        var target = $tab.data('tab');
        $('.nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');
        $('.isarud-tab-pane').hide();
        $('.isarud-tab-pane[data-tab-pane="' + target + '"]').show();

        // Auto-load tab content (sadece ilk açılışta)
        if (!tabsLoaded[target]) {
            tabsLoaded[target] = true;
            switch(target) {
                case 'listings':    loadListings(); break;
                case 'images':      autoLoadImages(); break;
                case 'sections':    loadSections(); break;
                case 'translations':autoLoadTranslations(); break;
                case 'shipping':    loadShipping(); break;
                case 'shop':        loadShop(); break;
                case 'stats':       loadSold(); break;
                case 'returns':     loadReturns(); break;
            }
        }
    });

    // Refresh button her sekme için yeniden yüklesin
    function refreshTab(tabName) {
        tabsLoaded[tabName] = false;
        $('.nav-tab[data-tab="' + tabName + '"]').click();
    }

    // ═══════════════════════════════════════════════════════
    // STATUS
    // ═══════════════════════════════════════════════════════
    function loadStatus() {
        $.get(ajaxUrl, { action:'isarud_etsy_status', nonce:nonce })
        .done(function(r) {
            var $c = $('#isarud-etsy-status-content');
            var $card = $('#isarud-etsy-connect-card');

            if (r && r.success && r.connected) {
                var name = (r.data && r.data.data && r.data.data.shop_name) || ('Store ' + (r.store_id || ''));
                $c.html('<span style="font-size:18px;">✅</span><strong>' + escapeHtml(i18n.active) + '</strong>—<?php esc_html_e('Mağaza:', 'api-isarud'); ?> <strong>' + escapeHtml(name) + '</strong>');
                $card.hide();
            } else {
                $c.html('<span style="font-size:18px;">⚠️</span><strong><?php esc_html_e('Bağlı değil', 'api-isarud'); ?></strong>');
                $card.show();
            }
        })
        .fail(function() {
            $('#isarud-etsy-status-content').html('<span style="color:#dc2626;">❌ <?php esc_html_e('Durum kontrol edilemedi', 'api-isarud'); ?></span>');
        });
    }

    // ═══════════════════════════════════════════════════════
    // PHASE 1 — LISTINGS
    // ═══════════════════════════════════════════════════════
    function loadListings() {
        var size = parseInt($('#isarud-etsy-page-size').val(), 10) || 25;
        var $cont = $('#isarud-etsy-listings-container');
        $cont.html('<p style="text-align:center;padding:40px;color:#6b7280;">⏳ ' + escapeHtml(i18n.loading) + '</p>');

        $.get(ajaxUrl, { action:'isarud_etsy_listings', nonce:nonce, page:0, size:size })
        .done(function(r) {
            if (!r.success) {
                $cont.html('<div class="notice notice-error"><p>' + escapeHtml(r.error || r.message || i18n.error) + '</p></div>');
                return;
            }
            var items = (r.data && (r.data.items || r.data.results || r.data.listings || r.data)) || [];
            var list = Array.isArray(items) ? items : [];

            if (list.length === 0) {
                $cont.html('<p style="text-align:center;padding:40px;color:#6b7280;">' + escapeHtml(i18n.noListings) + '</p>');
                return;
            }

            // İlk listing'i diğer sekmeler için sakla
            if (list.length > 0 && !firstListingId) {
                firstListingId = list[0].listing_id || list[0].id;
                $('#isarud-img-listing-id, #isarud-tr-listing-id, #isarud-stats-listing-id').val(firstListingId);
            }

            var html = '<table class="wp-list-table widefat fixed striped"><thead><tr>';
            html += '<th style="width:90px;">ID</th><th><?php esc_html_e('Başlık', 'api-isarud'); ?></th>';
            html += '<th style="width:100px;"><?php esc_html_e('Durum', 'api-isarud'); ?></th>';
            html += '<th style="width:80px;"><?php esc_html_e('Stok', 'api-isarud'); ?></th>';
            html += '<th style="width:130px;"><?php esc_html_e('Fiyat', 'api-isarud'); ?></th>';
            html += '<th style="width:280px;"><?php esc_html_e('İşlemler', 'api-isarud'); ?></th></tr></thead><tbody>';

            list.forEach(function(item) {
                var id = item.listing_id || item.id;
                var title = item.title || '-';
                var state = item.state || 'unknown';
                var qty = item.quantity || 0;
                var priceTxt = '-';
                if (item.price) {
                    if (typeof item.price === 'object' && item.price.amount) {
                        priceTxt = (item.price.amount / (item.price.divisor || 100)).toFixed(2) + ' ' + (item.price.currency_code || '');
                    } else {
                        priceTxt = item.price;
                    }
                }
                var stateLabel = i18n[state] || state;
                var stateColor = state === 'active' ? '#16a34a' : (state === 'inactive' ? '#6b7280' : '#f59e0b');

                html += '<tr><td><code>' + escapeHtml(id) + '</code></td>';
                html += '<td><strong>' + escapeHtml(title) + '</strong></td>';
                html += '<td><span style="color:' + stateColor + ';font-weight:600;">' + escapeHtml(stateLabel) + '</span></td>';
                html += '<td>' + escapeHtml(qty) + '</td>';
                html += '<td>' + escapeHtml(priceTxt) + '</td>';
                html += '<td>';
                if (item.url) html += '<a href="' + escapeHtml(item.url) + '" target="_blank" class="button button-small">🔗 ' + escapeHtml(i18n.actionView) + '</a> ';
                if (state === 'active') {
                    html += '<button class="button button-small isarud-deact" data-id="' + escapeHtml(id) + '">⏸ ' + escapeHtml(i18n.actionDeactivate) + '</button> ';
                } else {
                    html += '<button class="button button-small isarud-act" data-id="' + escapeHtml(id) + '">▶ ' + escapeHtml(i18n.actionActivate) + '</button> ';
                }
                html += '<button class="button button-small isarud-del" data-id="' + escapeHtml(id) + '" style="color:#dc2626;">🗑 ' + escapeHtml(i18n.actionDelete) + '</button>';
                html += '</td></tr>';
            });
            html += '</tbody></table>';
            html += '<p style="margin-top:14px;color:#6b7280;"><?php esc_html_e('Toplam:', 'api-isarud'); ?> <strong>' + list.length + '</strong> <?php esc_html_e('ürün', 'api-isarud'); ?></p>';
            $cont.html(html);
        })
        .fail(function(xhr) {
            $cont.html('<div class="notice notice-error"><p><?php esc_html_e('Yükleme başarısız:', 'api-isarud'); ?> HTTP ' + xhr.status + '</p></div>');
        });
    }

    function listingAction(id, action) {
        if (action === 'delete' && !confirm(i18n.confirmDelete)) return;
        $.post(ajaxUrl, { action:'isarud_etsy_listing_'+action, nonce:nonce, listing_id:id })
        .done(function(r) {
            if (r.success) { alert(i18n.success); loadListings(); }
            else alert(i18n.error + ': ' + (r.error || r.message || 'unknown'));
        })
        .fail(function(xhr){ alert(i18n.error + ': HTTP ' + xhr.status); });
    }

    $(document).on('click', '.isarud-act',   function(){ listingAction($(this).data('id'), 'activate'); });
    $(document).on('click', '.isarud-deact', function(){ listingAction($(this).data('id'), 'deactivate'); });
    $(document).on('click', '.isarud-del',   function(){ listingAction($(this).data('id'), 'delete'); });

    $('#isarud-etsy-refresh-status').on('click', loadStatus);
    $('#isarud-etsy-refresh-listings').on('click', loadListings);

    // ═══════════════════════════════════════════════════════
    // PHASE 2 — IMAGES
    // ═══════════════════════════════════════════════════════
    $('#isarud-img-load').on('click', function() {
        var id = parseInt($('#isarud-img-listing-id').val(), 10);
        if (!id || id <= 0) { alert('<?php esc_html_e('Geçerli Listing ID girin', 'api-isarud'); ?>'); return; }
        var $cont = $('#isarud-img-container');
        $cont.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');

        $.get(ajaxUrl, { action:'isarud_etsy_images_list', nonce:nonce, listing_id:id })
        .done(function(r) {
            if (!r.success) { $cont.html('<div class="notice notice-error"><p>' + escapeHtml(r.error||r.message) + '</p></div>'); return; }
            var imgs = (r.data && (r.data.images || r.data.results || [])) || [];
            if (!Array.isArray(imgs) || imgs.length === 0) { $cont.html('<p style="color:#6b7280;">' + escapeHtml(i18n.noImages) + '</p>'); return; }

            var html = '<div style="display:flex;flex-wrap:wrap;gap:14px;">';
            imgs.forEach(function(img) {
                var imgId = img.listing_image_id || img.image_id;
                var url = img.url_170x135 || img.url_75x75 || img.url_300x300 || img.url || '';
                var rank = img.rank || 0;
                html += '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:8px;max-width:200px;">';
                html += '<img src="' + escapeHtml(url) + '" style="width:100%;border-radius:4px;">';
                html += '<p style="font-size:12px;margin:8px 0 4px 0;"><strong><?php esc_html_e('Sıra:', 'api-isarud'); ?></strong> ' + rank + '</p>';
                html += '<p style="font-size:11px;color:#6b7280;margin:0 0 8px 0;">ID: ' + imgId + '</p>';
                html += '<button class="button button-small isarud-img-del" data-listing="' + id + '" data-img="' + imgId + '" style="color:#dc2626;">🗑 ' + escapeHtml(i18n.actionDelete) + '</button>';
                html += '</div>';
            });
            html += '</div>';
            $cont.html(html);
        })
        .fail(function(xhr){ $cont.html('<div class="notice notice-error"><p>HTTP ' + xhr.status + '</p></div>'); });
    });

    $(document).on('click', '.isarud-img-del', function() {
        if (!confirm(i18n.confirmDelete)) return;
        var lid = $(this).data('listing'), iid = $(this).data('img');
        $.post(ajaxUrl, { action:'isarud_etsy_image_delete', nonce:nonce, listing_id:lid, image_id:iid })
        .done(function(r){
            if (r.success){ alert(i18n.success); $('#isarud-img-load').click(); }
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    $('#isarud-img-upload-btn').on('click', function() {
        var lid = parseInt($('#isarud-img-listing-id').val(), 10);
        var url = $('#isarud-img-url').val().trim();
        var rank = parseInt($('#isarud-img-rank').val(), 10) || 1;
        if (!lid || !url) { alert('<?php esc_html_e('Listing ID ve URL girin', 'api-isarud'); ?>'); return; }
        $.post(ajaxUrl, { action:'isarud_etsy_image_upload', nonce:nonce, listing_id:lid, image_url:url, rank:rank })
        .done(function(r){
            if (r.success){ alert(i18n.success); $('#isarud-img-url').val(''); $('#isarud-img-load').click(); }
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    // ═══════════════════════════════════════════════════════
    // PHASE 3 — SECTIONS
    // ═══════════════════════════════════════════════════════
    function loadSections() {
        var $cont = $('#isarud-sections-container');
        $cont.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_sections_list', nonce:nonce })
        .done(function(r) {
            if (!r.success) { $cont.html('<div class="notice notice-error"><p>' + escapeHtml(r.error||r.message) + '</p></div>'); return; }
            var secs = (r.data && (r.data.sections || r.data.results || [])) || [];
            if (!Array.isArray(secs) || secs.length === 0) { $cont.html('<p style="color:#6b7280;">' + escapeHtml(i18n.noSections) + '</p>'); return; }

            var html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th><?php esc_html_e('Başlık', 'api-isarud'); ?></th><th><?php esc_html_e('Aktif Listing', 'api-isarud'); ?></th><th><?php esc_html_e('İşlemler', 'api-isarud'); ?></th></tr></thead><tbody>';
            secs.forEach(function(s) {
                var sid = s.shop_section_id || s.section_id;
                html += '<tr data-sid="' + sid + '"><td><code>' + escapeHtml(sid) + '</code></td>';
                html += '<td><span class="sec-title">' + escapeHtml(s.title || '-') + '</span></td>';
                html += '<td>' + escapeHtml(s.active_listing_count || 0) + '</td>';
                html += '<td><button class="button button-small isarud-sec-edit" data-sid="' + sid + '">✏️ ' + escapeHtml(i18n.actionEdit) + '</button> ';
                html += '<button class="button button-small isarud-sec-del" data-sid="' + sid + '" style="color:#dc2626;">🗑 ' + escapeHtml(i18n.actionDelete) + '</button></td></tr>';
            });
            html += '</tbody></table>';
            $cont.html(html);
        });
    }
    $('#isarud-sections-load').on('click', loadSections);

    $('#isarud-section-create-btn').on('click', function() {
        var title = $('#isarud-section-title').val().trim();
        if (!title) { alert('<?php esc_html_e('Bölüm adı girin', 'api-isarud'); ?>'); return; }
        $.post(ajaxUrl, { action:'isarud_etsy_section_create', nonce:nonce, title:title })
        .done(function(r){
            if (r.success){ alert(i18n.success); $('#isarud-section-title').val(''); loadSections(); }
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    $(document).on('click', '.isarud-sec-edit', function() {
        var sid = $(this).data('sid');
        var $row = $('tr[data-sid="' + sid + '"]');
        var current = $row.find('.sec-title').text();
        var newTitle = prompt('<?php esc_html_e('Yeni başlık:', 'api-isarud'); ?>', current);
        if (!newTitle || newTitle === current) return;
        $.post(ajaxUrl, { action:'isarud_etsy_section_update', nonce:nonce, section_id:sid, title:newTitle })
        .done(function(r){
            if (r.success){ alert(i18n.success); loadSections(); }
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    $(document).on('click', '.isarud-sec-del', function() {
        if (!confirm(i18n.confirmDelete)) return;
        var sid = $(this).data('sid');
        $.post(ajaxUrl, { action:'isarud_etsy_section_delete', nonce:nonce, section_id:sid })
        .done(function(r){
            if (r.success){ alert(i18n.success); loadSections(); }
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    // ═══════════════════════════════════════════════════════
    // PHASE 4 — TRANSLATIONS
    // ═══════════════════════════════════════════════════════
    var locales = ['de','en','es','fr','it','ja','nl','pl','pt','ru','tr'];
    $('#isarud-tr-load').on('click', function() {
        var lid = parseInt($('#isarud-tr-listing-id').val(), 10);
        if (!lid) { alert('<?php esc_html_e('Listing ID girin', 'api-isarud'); ?>'); return; }
        var $cont = $('#isarud-tr-container');
        $cont.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_translations_all', nonce:nonce, listing_id:lid })
        .done(function(r) {
            if (!r.success) { $cont.html('<div class="notice notice-error"><p>' + escapeHtml(r.error||r.message) + '</p></div>'); return; }
            var trs = (r.data && r.data.translations) || {};
            var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:16px;">';
            locales.forEach(function(loc) {
                var t = trs[loc] || {};
                html += '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;">';
                html += '<h4 style="margin-top:0;">🌍 ' + loc.toUpperCase() + '</h4>';
                html += '<p style="margin:4px 0;"><strong><?php esc_html_e('Başlık:', 'api-isarud'); ?></strong> <input type="text" class="regular-text isarud-tr-title" data-locale="' + loc + '" value="' + escapeHtml(t.title || '') + '" style="width:100%;"></p>';
                html += '<p style="margin:4px 0;"><strong><?php esc_html_e('Açıklama:', 'api-isarud'); ?></strong> <textarea class="isarud-tr-desc" data-locale="' + loc + '" style="width:100%;height:80px;">' + escapeHtml(t.description || '') + '</textarea></p>';
                html += '<button class="button button-small button-primary isarud-tr-save" data-locale="' + loc + '">💾 ' + escapeHtml(i18n.actionSave) + '</button>';
                html += '</div>';
            });
            html += '</div>';
            $cont.html(html);
        });
    });

    $(document).on('click', '.isarud-tr-save', function() {
        var loc = $(this).data('locale');
        var lid = parseInt($('#isarud-tr-listing-id').val(), 10);
        var title = $('.isarud-tr-title[data-locale="' + loc + '"]').val();
        var desc = $('.isarud-tr-desc[data-locale="' + loc + '"]').val();
        $.post(ajaxUrl, { action:'isarud_etsy_translation_update', nonce:nonce, listing_id:lid, locale:loc, fields:{title:title, description:desc} })
        .done(function(r){
            if (r.success) alert(i18n.success);
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    // ═══════════════════════════════════════════════════════
    // PHASE 5 — SHIPPING
    // ═══════════════════════════════════════════════════════
    function loadShipping() {
        var $cont = $('#isarud-ship-container');
        $cont.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_shipping_list', nonce:nonce })
        .done(function(r) {
            if (!r.success) { $cont.html('<div class="notice notice-error"><p>' + escapeHtml(r.error||r.message) + '</p></div>'); return; }
            var profiles = (r.data && (r.data.profiles || r.data.results || [])) || [];
            if (!Array.isArray(profiles) || profiles.length === 0) { $cont.html('<p style="color:#6b7280;">' + escapeHtml(i18n.noShipping) + '</p>'); return; }
            var html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th><?php esc_html_e('Başlık', 'api-isarud'); ?></th><th><?php esc_html_e('Menşe', 'api-isarud'); ?></th><th><?php esc_html_e('Min Süre', 'api-isarud'); ?></th><th><?php esc_html_e('Max Süre', 'api-isarud'); ?></th><th><?php esc_html_e('İşlemler', 'api-isarud'); ?></th></tr></thead><tbody>';
            profiles.forEach(function(p) {
                var pid = p.shipping_profile_id || p.profile_id;
                html += '<tr><td><code>' + escapeHtml(pid) + '</code></td>';
                html += '<td>' + escapeHtml(p.title||'-') + '</td>';
                html += '<td>' + escapeHtml(p.origin_country_iso||'-') + '</td>';
                html += '<td>' + escapeHtml(p.min_processing_days||0) + '</td>';
                html += '<td>' + escapeHtml(p.max_processing_days||0) + '</td>';
                html += '<td><button class="button button-small isarud-ship-del" data-pid="' + pid + '" style="color:#dc2626;">🗑</button></td></tr>';
            });
            html += '</tbody></table>';
            $cont.html(html);
        });
    }
    $('#isarud-ship-load').on('click', loadShipping);

    $(document).on('click', '.isarud-ship-del', function() {
        if (!confirm(i18n.confirmDelete)) return;
        var pid = $(this).data('pid');
        $.post(ajaxUrl, { action:'isarud_etsy_shipping_delete', nonce:nonce, profile_id:pid })
        .done(function(r){
            if (r.success){ alert(i18n.success); $('#isarud-ship-load').click(); }
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    // ═══════════════════════════════════════════════════════
    // PHASE 6 — SHOP
    // ═══════════════════════════════════════════════════════
    function loadShop() {
        var $cont = $('#isarud-shop-container');
        $cont.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_shop_fetch', nonce:nonce })
        .done(function(r) {
            if (!r.success) { $cont.html('<div class="notice notice-error"><p>' + escapeHtml(r.error||r.message) + '</p></div>'); return; }
            var s = (r.data && r.data.shop) || {};
            var html = '<div style="background:#f9fafb;padding:16px;border-radius:8px;">';
            html += '<h3 style="margin-top:0;">🏪 ' + escapeHtml(s.shop_name || '-') + '</h3>';
            html += '<p><strong><?php esc_html_e('Başlık:', 'api-isarud'); ?></strong> <input type="text" id="isarud-shop-title" class="regular-text" value="' + escapeHtml(s.title || '') + '"></p>';
            html += '<p><strong><?php esc_html_e('Duyuru:', 'api-isarud'); ?></strong> <textarea id="isarud-shop-announce" class="regular-text" style="width:100%;height:80px;">' + escapeHtml(s.announcement || '') + '</textarea></p>';
            html += '<p><strong><?php esc_html_e('Tatilde mi?', 'api-isarud'); ?></strong> <input type="checkbox" id="isarud-shop-vacation"' + (s.is_vacation?' checked':'') + '></p>';
            html += '<p><strong><?php esc_html_e('Tatil Mesajı:', 'api-isarud'); ?></strong> <textarea id="isarud-shop-vacmsg" class="regular-text" style="width:100%;height:60px;">' + escapeHtml(s.vacation_message || '') + '</textarea></p>';
            html += '<p><strong><?php esc_html_e('Para Birimi:', 'api-isarud'); ?></strong> ' + escapeHtml(s.currency_code || '-') + '</p>';
            html += '<button class="button button-primary" id="isarud-shop-save">💾 ' + escapeHtml(i18n.actionSave) + '</button>';
            html += '</div>';
            $cont.html(html);
        });
    }
    $('#isarud-shop-load').on('click', loadShop);

    $(document).on('click', '#isarud-shop-save', function() {
        var updates = {
            title: $('#isarud-shop-title').val(),
            announcement: $('#isarud-shop-announce').val(),
            is_vacation: $('#isarud-shop-vacation').is(':checked'),
            vacation_message: $('#isarud-shop-vacmsg').val()
        };
        $.post(ajaxUrl, { action:'isarud_etsy_shop_update', nonce:nonce, updates:updates })
        .done(function(r){
            if (r.success) alert(i18n.success);
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    // ═══════════════════════════════════════════════════════
    // PHASE 7 — STATS
    // ═══════════════════════════════════════════════════════
    $('#isarud-stats-listing-btn').on('click', function() {
        var lid = parseInt($('#isarud-stats-listing-id').val(), 10);
        if (!lid) { alert('<?php esc_html_e('Listing ID', 'api-isarud'); ?>'); return; }
        var $out = $('#isarud-stats-listing-out');
        $out.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_listing_stats', nonce:nonce, listing_id:lid })
        .done(function(r) {
            if (!r.success) { $out.html('<p style="color:#dc2626;">' + escapeHtml(r.error||r.message) + '</p>'); return; }
            $out.html('<pre style="background:white;padding:10px;border-radius:4px;overflow:auto;font-size:11px;">' + escapeHtml(JSON.stringify(r.data, null, 2)) + '</pre>');
        });
    });

    $('#isarud-inv-btn').on('click', function() {
        var lid = parseInt($('#isarud-stats-listing-id').val(), 10);
        if (!lid) { alert('<?php esc_html_e('Listing ID', 'api-isarud'); ?>'); return; }
        var $out = $('#isarud-stats-listing-out');
        $out.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_inventory_fetch', nonce:nonce, listing_id:lid })
        .done(function(r) {
            if (!r.success) { $out.html('<p style="color:#dc2626;">' + escapeHtml(r.error||r.message) + '</p>'); return; }
            $out.html('<pre style="background:white;padding:10px;border-radius:4px;overflow:auto;font-size:11px;">' + escapeHtml(JSON.stringify(r.data, null, 2)) + '</pre>');
        });
    });

    function loadSold() {
        var $out = $('#isarud-sold-out');
        $out.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_sold_listings', nonce:nonce, limit:25, offset:0 })
        .done(function(r) {
            if (!r.success) { $out.html('<p style="color:#dc2626;">' + escapeHtml(r.error||r.message) + '</p>'); return; }
            var items = (r.data && (r.data.items || r.data.results || [])) || [];
            if (!items.length) { $out.html('<p style="color:#6b7280;"><?php esc_html_e('Henüz satış yok.', 'api-isarud'); ?></p>'); return; }
            var html = '<ul style="margin:0;padding-left:20px;">';
            items.forEach(function(it) {
                html += '<li>' + escapeHtml(it.title || it.listing_id || '?') + ' — <strong>' + escapeHtml(it.quantity || 1) + 'x</strong></li>';
            });
            html += '</ul>';
            $out.html(html);
        });
    }
    $('#isarud-sold-btn').on('click', loadSold);

    // ═══════════════════════════════════════════════════════
    // PHASE 8 — RETURNS
    // ═══════════════════════════════════════════════════════
    function loadReturns() {
        var $cont = $('#isarud-ret-container');
        $cont.html('<p>⏳ ' + escapeHtml(i18n.loading) + '</p>');
        $.get(ajaxUrl, { action:'isarud_etsy_returns_list', nonce:nonce })
        .done(function(r) {
            if (!r.success) { $cont.html('<div class="notice notice-error"><p>' + escapeHtml(r.error||r.message) + '</p></div>'); return; }
            var policies = (r.data && (r.data.policies || r.data.results || [])) || [];
            if (!Array.isArray(policies) || policies.length === 0) { $cont.html('<p style="color:#6b7280;">' + escapeHtml(i18n.noReturns) + '</p>'); return; }
            var html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th><?php esc_html_e('Kabul Edilir Mi?', 'api-isarud'); ?></th><th><?php esc_html_e('Değişim', 'api-isarud'); ?></th><th><?php esc_html_e('Süre (gün)', 'api-isarud'); ?></th><th><?php esc_html_e('İşlemler', 'api-isarud'); ?></th></tr></thead><tbody>';
            policies.forEach(function(p) {
                var pid = p.return_policy_id || p.policy_id;
                html += '<tr><td><code>' + escapeHtml(pid) + '</code></td>';
                html += '<td>' + (p.accepts_returns ? '✅' : '❌') + '</td>';
                html += '<td>' + (p.accepts_exchanges ? '✅' : '❌') + '</td>';
                html += '<td>' + escapeHtml(p.return_deadline || '-') + '</td>';
                html += '<td><button class="button button-small isarud-ret-del" data-pid="' + pid + '" style="color:#dc2626;">🗑</button></td></tr>';
            });
            html += '</tbody></table>';
            $cont.html(html);
        });
    }
    $('#isarud-ret-load').on('click', loadReturns);

    $(document).on('click', '.isarud-ret-del', function() {
        if (!confirm(i18n.confirmDelete)) return;
        var pid = $(this).data('pid');
        $.post(ajaxUrl, { action:'isarud_etsy_returns_delete', nonce:nonce, policy_id:pid })
        .done(function(r){
            if (r.success){ alert(i18n.success); $('#isarud-ret-load').click(); }
            else alert(i18n.error + ': ' + (r.error||r.message));
        });
    });

    // ═══════════════════════════════════════════════════════
    // CONNECT FLOW
    // ═══════════════════════════════════════════════════════
    $('#isarud-etsy-connect-btn').on('click', function() {
        $.get(ajaxUrl, { action:'isarud_etsy_stores', nonce:nonce })
        .done(function(r) {
            if (!r.success) { alert(i18n.error + ': ' + (r.error||r.message)); return; }
            var stores = (r.data && (r.data.stores || r.data.results || [])) || [];
            if (!stores.length) { alert('<?php esc_html_e('Isarud mağazası yok.', 'api-isarud'); ?>'); return; }
            if (stores.length === 1) { initiateAuth(stores[0].id); return; }
            var html = '';
            stores.forEach(function(s) {
                html += '<button class="button isarud-pick-store" data-id="' + s.id + '" style="display:block;width:100%;text-align:left;margin-bottom:8px;padding:12px;">' + escapeHtml(s.name) + '</button>';
            });
            $('#isarud-etsy-store-list').html(html);
            $('#isarud-etsy-store-modal').css('display', 'flex');
        });
    });
    $(document).on('click', '.isarud-pick-store', function(){ initiateAuth($(this).data('id')); });
    $('#isarud-etsy-store-cancel').on('click', function(){ $('#isarud-etsy-store-modal').hide(); });

    function initiateAuth(storeId) {
        $('#isarud-etsy-store-modal').hide();
        var returnUrl = window.location.href.split('?')[0] + '?page=isarud-etsy';
        $.post(ajaxUrl, { action:'isarud_etsy_authorize_url', nonce:nonce, store_id:storeId, return_url:returnUrl })
        .done(function(r) {
            if (!r.success || !r.data || !r.data.url) { alert(i18n.error + ': ' + (r.error||r.message)); return; }
            window.location.href = r.data.url;
        });
    }


    // Auto-load helpers
    function autoLoadImages() {
        if (!firstListingId) return;
        $('#isarud-img-listing-id').val(firstListingId);
        $('#isarud-img-load').click();
    }

    function autoLoadTranslations() {
        if (!firstListingId) return;
        $('#isarud-tr-listing-id').val(firstListingId);
        $('#isarud-tr-load').click();
    }

    // ═══════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════
    $(document).ready(function() {
        loadStatus();
        loadListings();
    });

})(jQuery);
</script>

<style>
.isarud-tab-pane { display: none; }
.isarud-tab-pane.active { display: block; }
.nav-tab-active { background: white !important; border-bottom-color: white !important; }
</style>
