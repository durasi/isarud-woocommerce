<?php
/**
 * ebay-html.php — WP Plugin eBay Admin UI
 * v6.6.11 — Cloud bridge (isarud.com/api/v2/marketplace/ebay/*)
 *
 * eBay OAuth (19-scope) isarud.com tarafinda yonetilir.
 * AJAX endpoint'leri Isarud_Ebay class'ina baglidir.
 * Auth: isarud_cloud_api_key WP option (Cloud Sync key)
 */

if (!defined('ABSPATH')) exit;

$cloud_key = get_option('isarud_cloud_api_key', '');
$ebay_nonce = wp_create_nonce('isarud_ebay_nonce');
?>

<div class="wrap" id="isarud-ebay-app">
    <h1 style="display:flex;align-items:center;gap:10px;">
        <span style="display:inline-block;width:32px;height:32px;background:#e53238;border-radius:7px;text-align:center;line-height:32px;color:#fff;font-weight:700;font-size:13px;">eB</span>
        eBay
    </h1>

    <?php if (empty($cloud_key)): ?>
    <div class="notice notice-warning">
        <p><strong><?php _e('Cloud Sync not installed.', 'api-isarud'); ?></strong>
        <?php _e('First', 'api-isarud'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=isarud-cloud')); ?>"><?php _e('Go to Cloud Sync page', 'api-isarud'); ?></a>
        <?php _e('and connect to your isarud.com account.', 'api-isarud'); ?></p>
    </div>
    <?php else: ?>

    <!-- Loading -->
    <div id="iseb-loading" style="padding:40px;text-align:center;color:#666;">
        <span class="spinner is-active" style="float:none;"></span>
        <?php _e('Checking eBay connection...', 'api-isarud'); ?>
    </div>

    <!-- Not connected -->
    <div id="iseb-not-connected" style="display:none;max-width:600px;margin:30px auto;text-align:center;background:#fff;border:1px solid #e2e4e7;border-radius:10px;padding:40px 30px;">
        <h2 style="font-size:22px;margin-bottom:10px;"><?php _e('Your eBay Store is Not Connected', 'api-isarud'); ?></h2>
        <p style="color:#6b7280;font-size:14px;line-height:1.6;margin-bottom:20px;">
            <?php _e('Complete eBay OAuth authorization from the isarud.com panel to connect your eBay account. Once connected, your products and orders will appear here.', 'api-isarud'); ?>
        </p>
        <a href="https://isarud.com/store" target="_blank" class="button button-primary button-hero" style="background:#0654ba;border-color:#0654ba;font-weight:600;">
            <?php _e('Connect eBay on isarud.com', 'api-isarud'); ?>
        </a>
        <p id="iseb-nc-msg" style="color:#999;font-size:12px;margin-top:15px;"></p>
    </div>

    <!-- Connected app -->
    <div id="iseb-app" style="display:none;">
        <div style="background:#fff;border:1px solid #e2e4e7;border-radius:8px;padding:12px 16px;margin:15px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="font-size:13px;">
                <strong><?php _e('Store:', 'api-isarud'); ?></strong> <span id="iseb-store">—</span>
                · <strong><?php _e('eBay Account:', 'api-isarud'); ?></strong> <span id="iseb-account">—</span>
            </div>
            <button class="button" id="iseb-refresh"><span class="dashicons dashicons-update" style="margin-top:3px;"></span> <?php _e('Refresh', 'api-isarud'); ?></button>
        </div>

        <h2 class="nav-tab-wrapper">
            <a href="#" class="nav-tab nav-tab-active" data-tab="listings"><?php _e('Products', 'api-isarud'); ?></a>
            <a href="#" class="nav-tab" data-tab="orders"><?php _e('Orders', 'api-isarud'); ?></a>
            <a href="#" class="nav-tab" data-tab="finances"><?php _e('Finance', 'api-isarud'); ?></a>
            <a href="#" class="nav-tab" data-tab="analytics"><?php _e('Analytics', 'api-isarud'); ?></a>
        </h2>

        <!-- Listings -->
        <div class="iseb-pane" data-pane="listings" style="padding:15px 0;">
            <p><button class="button button-secondary" id="iseb-load-listings"><?php _e('Upload Products', 'api-isarud'); ?></button></p>
            <div id="iseb-listings-result"></div>
        </div>

        <!-- Orders -->
        <div class="iseb-pane" data-pane="orders" style="display:none;padding:15px 0;">
            <p>
                <label><?php _e('Last', 'api-isarud'); ?>
                    <select id="iseb-order-days"><option value="7">7</option><option value="30" selected>30</option><option value="90">90</option></select>
                    <?php _e('day', 'api-isarud'); ?>
                </label>
                <button class="button button-secondary" id="iseb-load-orders"><?php _e('Upload Orders', 'api-isarud'); ?></button>
            </p>
            <div id="iseb-orders-result"></div>
        </div>

        <!-- Finances -->
        <div class="iseb-pane" data-pane="finances" style="display:none;padding:15px 0;">
            <p><button class="button button-secondary" id="iseb-load-finances"><?php _e('Upload Financial Data', 'api-isarud'); ?></button></p>
            <div id="iseb-finances-result"></div>
        </div>

        <!-- Analytics -->
        <div class="iseb-pane" data-pane="analytics" style="display:none;padding:15px 0;">
            <p><button class="button button-secondary" id="iseb-load-analytics"><?php _e('Upload Traffic Report', 'api-isarud'); ?></button></p>
            <div id="iseb-analytics-result"></div>
        </div>
    </div>

    <script>
    (function($){
        var ISEB = {
            ajax: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js($ebay_nonce); ?>'
        };

        function post(action, data, cb) {
            data = data || {};
            data.action = 'isarud_ebay_' + action;
            data.nonce = ISEB.nonce;
            $.post(ISEB.ajax, data, cb).fail(function(){ cb({success:false, message:'Baglanti hatasi'}); });
        }

        function esc(s){ return $('<div>').text(s == null ? '' : String(s)).html(); }

        // Init — status
        post('status', {}, function(r){
            $('#iseb-loading').hide();
            if (r && r.connected) {
                $('#iseb-store').text(r.store ? r.store.name : '—');
                $('#iseb-account').text(r.account_name || '—');
                $('#iseb-app').show();
                loadListings();
            } else {
                $('#iseb-nc-msg').text(r && r.message ? r.message : '');
                $('#iseb-not-connected').show();
            }
        });

        // Tabs
        $('.nav-tab').on('click', function(e){
            e.preventDefault();
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            var tab = $(this).data('tab');
            $('.iseb-pane').hide();
            $('.iseb-pane[data-pane="'+tab+'"]').show();
        });

        $('#iseb-refresh').on('click', function(){ location.reload(); });

        // Listings
        function loadListings(){
            $('#iseb-listings-result').html('<span class="spinner is-active" style="float:none;"></span>');
            post('listings', {page:0, size:50}, function(r){
                if (!r || r.success === false) { $('#iseb-listings-result').html('<p style="color:#b32d2e;">'+esc(r && r.message ? r.message : 'Hata')+'</p>'); return; }
                var items = r.items || r.data || [];
                if (!items.length) { $('#iseb-listings-result').html('<p style="color:#666;">Urun bulunamadi.</p>'); return; }
                var h = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Baslik</th><th>SKU</th><th>Fiyat</th><th>Stok</th></tr></thead><tbody>';
                items.forEach(function(it){
                    h += '<tr><td>'+esc(it.title||it.name||'-')+'</td><td>'+esc(it.sku||'-')+'</td><td>'+esc(it.price!=null?it.price:'-')+'</td><td>'+esc(it.stock!=null?it.stock:(it.quantity!=null?it.quantity:'-'))+'</td></tr>';
                });
                h += '</tbody></table>';
                $('#iseb-listings-result').html(h);
            });
        }
        $('#iseb-load-listings').on('click', loadListings);

        // Orders
        $('#iseb-load-orders').on('click', function(){
            var days = $('#iseb-order-days').val();
            $('#iseb-orders-result').html('<span class="spinner is-active" style="float:none;"></span>');
            post('orders', {days:days, page:0, size:50}, function(r){
                if (!r || r.success === false) { $('#iseb-orders-result').html('<p style="color:#b32d2e;">'+esc(r && r.message ? r.message : 'Hata')+'</p>'); return; }
                var items = r.orders || r.items || r.data || [];
                if (!items.length) { $('#iseb-orders-result').html('<p style="color:#666;">Siparis bulunamadi.</p>'); return; }
                var h = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Siparis</th><th>Tarih</th><th>Durum</th><th>Tutar</th></tr></thead><tbody>';
                items.forEach(function(o){
                    h += '<tr><td>'+esc(o.order_id||o.orderId||o.id||'-')+'</td><td>'+esc((o.order_date||o.creationDate||'').substring(0,10))+'</td><td>'+esc(o.status||o.orderFulfillmentStatus||'-')+'</td><td>'+esc(o.total!=null?o.total:'-')+'</td></tr>';
                });
                h += '</tbody></table>';
                $('#iseb-orders-result').html(h);
            });
        });

        // Finances
        $('#iseb-load-finances').on('click', function(){
            $('#iseb-finances-result').html('<span class="spinner is-active" style="float:none;"></span>');
            post('finances', {}, function(r){
                if (!r || r.success === false) { $('#iseb-finances-result').html('<p style="color:#b32d2e;">'+esc(r && r.message ? r.message : 'Hata')+'</p>'); return; }
                var f = r.funds || {};
                var h = '<table class="wp-list-table widefat fixed"><tbody>';
                h += '<tr><td><strong>Odenebilir</strong></td><td>'+esc(fmt(f.totalFunds))+'</td></tr>';
                h += '<tr><td><strong>Islemde</strong></td><td>'+esc(fmt(f.processingFunds))+'</td></tr>';
                h += '<tr><td><strong>Beklemede</strong></td><td>'+esc(fmt(f.fundsOnHold))+'</td></tr>';
                h += '</tbody></table>';
                var tx = r.transactions || [];
                if (tx.length) {
                    h += '<h3>Son Islemler</h3><table class="wp-list-table widefat fixed striped"><thead><tr><th>Tip</th><th>Tarih</th><th>Tutar</th></tr></thead><tbody>';
                    tx.forEach(function(t){ h += '<tr><td>'+esc(t.transactionType||'-')+'</td><td>'+esc((t.transactionDate||'').substring(0,10))+'</td><td>'+esc(fmt(t.amount))+'</td></tr>'; });
                    h += '</tbody></table>';
                }
                $('#iseb-finances-result').html(h);
            });
        });

        function fmt(v){
            if (v == null) return '-';
            if (typeof v === 'object') { var val = v.value != null ? v.value : (v.amount != null ? v.amount : ''); var cur = v.currency || ''; return val ? (val+' '+cur).trim() : '-'; }
            return String(v);
        }

        // Analytics
        $('#iseb-load-analytics').on('click', function(){
            $('#iseb-analytics-result').html('<span class="spinner is-active" style="float:none;"></span>');
            post('analytics', {}, function(r){
                if (!r || r.success === false) { $('#iseb-analytics-result').html('<p style="color:#b32d2e;">'+esc(r && r.message ? r.message : 'Hata')+'</p>'); return; }
                var recs = (r.traffic && r.traffic.records) || r.records || [];
                if (!recs.length) { $('#iseb-analytics-result').html('<p style="color:#666;">Trafik verisi bulunamadi.</p>'); return; }
                $('#iseb-analytics-result').html('<p style="color:#666;">'+recs.length+' kayit yuklendi.</p>');
            });
        });

    })(jQuery);
    </script>

    <?php endif; ?>
</div>
