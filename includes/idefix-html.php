<?php
/**
 * idefix-html.php — İdefix yönetim sayfası (v7.1: sekmeli, canlı uçlara bağlı).
 * Sekmeler: Durum · Kategoriler · Siparişler · Stok/Fiyat + "v7.2'de" listesi.
 * Kategori/sipariş içeriği ham-JSON görüntüleyici — gerçek satıcı verisiyle tablolaşır (v7.2).
 */
if (!defined('ABSPATH')) exit;

function isarud_idefix_page_render() {
    $connected = !empty(get_option('isarud_cloud_api_key', ''));
    $nonce = wp_create_nonce('isarud_idefix');
    ?>
    <div class="wrap" style="max-width:960px">
        <div style="border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;margin-top:16px">
            <div style="background:linear-gradient(135deg,#EA580C 0%,#F59E0B 100%);padding:22px 28px;color:#fff;display:flex;align-items:center;gap:14px">
                <div style="width:46px;height:46px;background:#fff;border-radius:11px;display:flex;align-items:center;justify-content:center">
                    <span style="font-weight:800;font-size:14px;color:#EA580C">idefix</span>
                </div>
                <div>
                    <h1 style="margin:0;color:#fff;font-size:22px">İdefix</h1>
                    <p style="margin:2px 0 0;opacity:0.9;font-size:12px"><?php esc_html_e('D&R family marketplace — status, categories, orders and stock/price sync via the isarud.com bridge', 'api-isarud'); ?></p>
                </div>
            </div>

            <?php if (!$connected) : ?>
            <div style="margin:16px 24px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400E">
                ⚠️ <?php esc_html_e('First connect this site with Cloud Sync, then link your İdefix account on isarud.com.', 'api-isarud'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=isarud-cloud')); ?>"><?php esc_html_e('Cloud Sync Settings', 'api-isarud'); ?> →</a>
            </div>
            <?php endif; ?>

            <div style="padding:14px 24px;border-bottom:1px solid #eee;display:flex;gap:6px;flex-wrap:wrap">
                <?php foreach (['status' => __('Status', 'api-isarud'), 'categories' => __('Categories', 'api-isarud'), 'orders' => __('Orders', 'api-isarud'), 'stock' => __('Stock/Price', 'api-isarud')] as $tab => $label) : ?>
                <button type="button" class="button isarud-idefix-tab" data-tab="<?php echo esc_attr($tab); ?>"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>

            <div style="padding:20px 24px">
                <div class="isarud-idefix-pane" data-pane="status">
                    <p><button type="button" class="button button-primary" id="isarud-idefix-check" style="background:#EA580C;border-color:#EA580C">🔌 <?php esc_html_e('Check Connection', 'api-isarud'); ?></button></p>
                    <div id="isarud-idefix-status-out" style="font-size:13px;color:#374151"></div>
                </div>

                <div class="isarud-idefix-pane" data-pane="categories" style="display:none">
                    <p><button type="button" class="button" id="isarud-idefix-cats">📂 <?php esc_html_e('Load Categories', 'api-isarud'); ?></button></p>
                    <pre id="isarud-idefix-cats-out" style="max-height:340px;overflow:auto;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-size:11px"></pre>
                </div>

                <div class="isarud-idefix-pane" data-pane="orders" style="display:none">
                    <p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                        <label><?php esc_html_e('Last', 'api-isarud'); ?>
                            <select id="isarud-idefix-days"><option value="7">7</option><option value="14">14</option><option value="30">30</option></select>
                            <?php esc_html_e('days', 'api-isarud'); ?></label>
                        <button type="button" class="button" id="isarud-idefix-load-orders">📋 <?php esc_html_e('Load Orders', 'api-isarud'); ?></button>
                        <span id="isarud-idefix-orders-count" style="font-size:12px;color:#6B7280"></span>
                    </p>
                    <pre id="isarud-idefix-orders-out" style="max-height:340px;overflow:auto;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-size:11px"></pre>
                </div>

                <div class="isarud-idefix-pane" data-pane="stock" style="display:none">
                    <p style="font-size:13px;color:#374151"><?php esc_html_e('One line per product:', 'api-isarud'); ?> <code>barcode,price,stock</code></p>
                    <textarea id="isarud-idefix-items" rows="6" style="width:100%;font-family:monospace;font-size:12px" placeholder="8683772071724,499.90,25"></textarea>
                    <p><button type="button" class="button button-primary" id="isarud-idefix-send-stock" style="background:#EA580C;border-color:#EA580C">📤 <?php esc_html_e('Send Stock/Price', 'api-isarud'); ?></button></p>
                    <div id="isarud-idefix-stock-out" style="font-size:13px;color:#374151"></div>
                </div>

                <p style="font-size:12px;color:#6B7280;border-top:1px solid #eee;padding-top:14px;margin-top:20px">
                    ℹ️ <?php esc_html_e('Coming in v7.2: product listing & management, returns, customer questions (as the İdefix API rollout completes).', 'api-isarud'); ?>
                </p>
            </div>
        </div>
    </div>

    <script>
    jQuery(function($){
        var nonce = <?php echo wp_json_encode($nonce); ?>;
        function call(action, extra){ return $.post(ajaxurl, $.extend({action: action, nonce: nonce}, extra || {})); }
        function msg(r){ return (r && (r.message || (r.data && r.data.message))) || ''; }

        $('.isarud-idefix-tab').on('click', function(){
            var t = $(this).data('tab');
            $('.isarud-idefix-pane').hide();
            $('.isarud-idefix-pane[data-pane="'+t+'"]').show();
        });

        $('#isarud-idefix-check').on('click', function(){
            var $o = $('#isarud-idefix-status-out').html('⏳');
            call('isarud_idefix_status').done(function(r){
                if (r && r.success) $o.html('<span style="color:#059669">✅ ' + <?php echo wp_json_encode(esc_html__('Connected — İdefix API responded.', 'api-isarud')); ?> + '</span>');
                else $o.html('<span style="color:#dc2626">⚠️ ' + $('<i>').text(msg(r) || 'Error').html() + '</span>');
            }).fail(function(){ $o.html('⚠️ HTTP'); });
        });

        $('#isarud-idefix-cats').on('click', function(){
            var $o = $('#isarud-idefix-cats-out').text('⏳');
            call('isarud_idefix_categories').done(function(r){
                $o.text(r && r.success ? JSON.stringify(r.data, null, 2) : ('⚠️ ' + msg(r)));
            }).fail(function(){ $o.text('⚠️ HTTP'); });
        });

        $('#isarud-idefix-load-orders').on('click', function(){
            var d = parseInt($('#isarud-idefix-days').val(), 10) || 7;
            var since = new Date(Date.now() - d*86400000).toISOString().slice(0,10);
            var $o = $('#isarud-idefix-orders-out').text('⏳');
            $('#isarud-idefix-orders-count').text('');
            call('isarud_idefix_orders', {since: since, limit: 50}).done(function(r){
                if (r && r.success) {
                    var data = r.data || {};
                    $('#isarud-idefix-orders-count').text((data.totalCount != null ? data.totalCount : (data.items||[]).length) + ' ' + <?php echo wp_json_encode(esc_html__('orders', 'api-isarud')); ?>);
                    $o.text(JSON.stringify(data.items || data, null, 2));
                } else { $o.text('⚠️ ' + msg(r)); }
            }).fail(function(){ $o.text('⚠️ HTTP'); });
        });

        $('#isarud-idefix-send-stock').on('click', function(){
            var $o = $('#isarud-idefix-stock-out').html('⏳');
            call('isarud_idefix_sync_stock', {items: $('#isarud-idefix-items').val()}).done(function(r){
                if (r && r.success) {
                    var b = r.data && (r.data.data && r.data.data.batchRequestId || r.data.batchRequestId);
                    $o.html('<span style="color:#059669">✅ ' + <?php echo wp_json_encode(esc_html__('Sent.', 'api-isarud')); ?> + (b ? ' batchRequestId: <code>' + $('<i>').text(b).html() + '</code>' : '') + '</span>');
                } else $o.html('<span style="color:#dc2626">⚠️ ' + $('<i>').text(msg(r) || 'Error').html() + '</span>');
            }).fail(function(){ $o.html('⚠️ HTTP'); });
        });

        $('.isarud-idefix-tab[data-tab="status"]').trigger('click');
    });
    </script>
    <?php
}
