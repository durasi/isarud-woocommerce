<?php
/**
 * amazon-html.php — WP Plugin Amazon SP-API Admin UI
 * v6.6.0 — 8-tab modern interface
 *
 * AJAX endpoint'leri Isarud_Amazon class'ına bağlıdır.
 * Auth: isarud_cloud_api_key WP option (Cloud Sync key)
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap" id="isarud-amazon-app">


    <?php
    // Cloud Sync kontrolü
    $cloud_key = get_option('isarud_cloud_api_key', '');
    if (empty($cloud_key)):
    ?>
    <div class="notice notice-warning">
        <p><strong><?php _e('Cloud Sync is not configured.', 'api-isarud'); ?></strong>
        <?php _e('First', 'api-isarud'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=isarud-cloud-sync')); ?>"><?php _e('Go to Cloud Sync page', 'api-isarud'); ?></a>
        <?php _e('and connect to your isarud.com account.', 'api-isarud'); ?></p>
    </div>
    <?php else: ?>

    <!-- Main App -->
    <div id="isarud-amz-loading" class="isarud-amz-loading">
        <span class="spinner is-active"></span>
        <?php _e('Checking Amazon connection...', 'api-isarud'); ?>
    </div>

    <!-- Not connected state -->
    <div id="isarud-amz-not-connected" class="isarud-amz-not-connected" style="display:none;">
        <div class="isarud-amz-empty" style="max-width:600px;margin:0 auto;">
            <h2 style="font-size:24px;margin-bottom:10px;">🛒 <?php _e('Connect to Amazon', 'api-isarud'); ?></h2>
            <button type="button" class="button button-primary button-hero" id="isarud-amz-connect-btn" style="background:#ff9900;border-color:#ff9900;font-weight:600;font-size:16px;padding:12px 32px;height:auto;line-height:1.4">
                🔗 <?php _e('Connect with Amazon', 'api-isarud'); ?>
            </button>
            <p style="color:#6b7280;margin-top:15px;font-size:13px;line-height:1.6;">
                <?php _e('You can integrate your store using the API Key, API Secret, and Merchant (Account) Number that you will obtain from the Amazon Seller Panel.', 'api-isarud'); ?>
            </p>
        </div>
    </div>

    <!-- Store selection modal -->
    <div id="isarud-amz-connect-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:520px;width:90%;padding:30px;box-shadow:0 10px 40px rgba(0,0,0,0.3);max-height:80vh;overflow-y:auto;">
            <h2 style="margin-top:0;font-size:20px;"><?php _e('Select Store', 'api-isarud'); ?></h2>
            <p style="color:#666;font-size:14px;"><?php _e('Select your Isarud store to connect to Amazon:', 'api-isarud'); ?></p>
            <div id="isarud-amz-stores-list" style="margin:20px 0;">
                <p style="text-align:center;color:#999;"><?php _e('Loading...', 'api-isarud'); ?></p>
            </div>
            <div style="text-align:right;margin-top:20px;border-top:1px solid #eee;padding-top:15px;">
                <button type="button" class="button" id="isarud-amz-modal-cancel"><?php _e('Cancel', 'api-isarud'); ?></button>
            </div>
        </div>
    </div>

    <!-- Connected state with tabs -->
    <div id="isarud-amz-app" style="display:none;">

        <!-- Connection info card -->
        <div class="isarud-amz-info-card">
            <div>
                <strong><?php _e('Store:', 'api-isarud'); ?></strong> <span id="isarud-amz-store-name">—</span>
                · <strong><?php _e('Merchant ID:', 'api-isarud'); ?></strong> <span id="isarud-amz-seller-id">—</span>
                <span id="isarud-amz-stage-badge" style="display:none;" class="isarud-amz-badge isarud-amz-badge-purple"><?php _e('STAGE', 'api-isarud'); ?></span>
            </div>
            <div>
                <button class="button" id="isarud-amz-refresh-status">
                    <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'api-isarud'); ?>
                </button>
            </div>
        </div>

        <!-- Tab navigation -->
        <h2 class="nav-tab-wrapper isarud-amz-tabs">
            <a href="#listings"  class="nav-tab nav-tab-active" data-tab="listings">📦 <?php _e('Products', 'api-isarud'); ?></a>
            <a href="#brands"    class="nav-tab" data-tab="brands">🏷️ <?php _e('Brand & Category', 'api-isarud'); ?></a>
            <a href="#stock"     class="nav-tab" data-tab="stock">📊 <?php _e('Stock & Price', 'api-isarud'); ?></a>
            <a href="#orders"    class="nav-tab" data-tab="orders">📋 <?php _e('Orders', 'api-isarud'); ?></a>
            <a href="#returns"    class="nav-tab" data-tab="returns">↩️ <?php _e('Returns', 'api-isarud'); ?></a>
            <a href="#questions" class="nav-tab" data-tab="questions">💬 <?php _e('Questions', 'api-isarud'); ?></a>
            <a href="#invoice"   class="nav-tab" data-tab="invoice">📄 <?php _e('Invoice', 'api-isarud'); ?></a>
            <a href="#webhooks"  class="nav-tab" data-tab="webhooks">🔔 <?php _e('Webhook', 'api-isarud'); ?></a>
        </h2>

        <!-- Tab contents -->

        <!-- TAB 1: LISTINGS -->
        <div class="isarud-amz-tab-content" data-tab-content="listings">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Amazon Products', 'api-isarud'); ?></h3>
                <div>
                    <select id="isarud-amz-listings-filter">
                        <option value="all"><?php _e('All', 'api-isarud'); ?></option>
                        <option value="approved"><?php _e('Approved', 'api-isarud'); ?></option>
                        <option value="pending"><?php _e('Pending', 'api-isarud'); ?></option>
                        <option value="rejected"><?php _e('Rejected', 'api-isarud'); ?></option>
                    </select>
                    <button class="button" id="isarud-amz-listings-load"><?php _e('Refresh', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-amz-listings-area" class="isarud-amz-data-area">
                <p class="isarud-amz-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        
        <!-- TAB 2: CATEGORIES (Amazon) -->
        <div class="isarud-amz-tab-content" data-tab-content="categories" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Amazon Categories', 'api-isarud'); ?></h3>
                <button class="button" id="isarud-amz-categories-load"><?php _e('Upload', 'api-isarud'); ?></button>
            </div>
            <p><?php _e('View the Amazon category tree and examine its properties.', 'api-isarud'); ?></p>
            <div id="isarud-amz-categories-area" class="isarud-amz-data-area">
                <p class="isarud-amz-empty"><?php _e('Click the Upload button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 3: STOCK & PRICE -->
        <div class="isarud-amz-tab-content" data-tab-content="stock" style="display:none;">
            <h3><?php _e('Stock & Price Synchronization', 'api-isarud'); ?></h3>

            <div class="isarud-amz-card isarud-amz-card-blue">
                <h4><?php _e('Synchronize Single Product', 'api-isarud'); ?></h4>
                <p><?php _e('Send a product\'s barcode, stock, and price information to Amazon.', 'api-isarud'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="isarud-amz-single-barcode"><?php _e('Barcode', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-amz-single-barcode" class="regular-text" placeholder="8680000000000"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-amz-single-quantity"><?php _e('Inventory', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-amz-single-quantity" min="0" value="0"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-amz-single-price"><?php _e('Sales Price (TRY)', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-amz-single-price" min="0" step="0.01" placeholder="299.90"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-amz-single-list"><?php _e('List Price (optional)', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-amz-single-list" min="0" step="0.01" placeholder="349.90"></td>
                    </tr>
                </table>
                <button class="button button-primary" id="isarud-amz-single-sync"><?php _e('Synchronize', 'api-isarud'); ?></button>
                <div id="isarud-amz-single-result" style="margin-top:10px;"></div>
            </div>

            <div class="isarud-amz-card isarud-amz-card-purple" style="margin-top:20px;">
                <h4>🚀 <?php _e('Automatic Export (Auto-Export)', 'api-isarud'); ?></h4>
                <p style="color:#666;font-size:13px;line-height:1.6;margin-bottom:15px;"><?php _e('When this feature is enabled, whenever you add a new product in WooCommerce or update an existing product, the product is automatically sent to all active marketplaces (including Amazon).', 'api-isarud'); ?></p>
                
                <div style="display:flex;align-items:center;gap:15px;background:#fff;padding:12px 15px;border-radius:8px;border:1px solid #ddd;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;flex:1;">
                        <input type="checkbox" id="isarud-amz-auto-export-toggle" <?php echo get_option('isarud_auto_export_enabled', false) ? 'checked' : ''; ?> style="width:20px;height:20px;cursor:pointer;">
                        <span style="font-weight:600;font-size:15px;"><?php _e('Automatic export is ACTIVE', 'api-isarud'); ?></span>
                    </label>
                    <span id="isarud-amz-auto-export-status" style="font-size:13px;color:#666;"><?php echo get_option('isarud_auto_export_enabled', false) ? '✅ '.__('On','api-isarud') : '⚪ '.__('Off','api-isarud'); ?></span>
                </div>

                <p style="color:#999;font-size:12px;margin-top:12px;line-height:1.5;">
                    ⚠️ <?php _e('For automatic export, your products must have', 'api-isarud'); ?> 
                    <strong><?php _e('Amazon category and brand mappings', 'api-isarud'); ?></strong>
                    <?php _e('completed. Otherwise, the export will fail.', 'api-isarud'); ?>
                </p>
            </div>

            <div class="isarud-amz-card" style="margin-top:20px;">
                <h4>📦 <?php _e('Manual Bulk Export (WooCommerce → Amazon)', 'api-isarud'); ?></h4>
                <p style="color:#666;font-size:13px;line-height:1.6;margin-bottom:15px;"><?php _e('Use this button to send all your WooCommerce products to Amazon at once. Only products with completed category and brand mapping will be transferred.', 'api-isarud'); ?></p>
                <button class="button button-primary" id="isarud-amz-bulk-export-btn" style="background:#ff9900;border-color:#ff9900;font-weight:600;">
                    🚀 <?php _e('Send All Products to Amazon', 'api-isarud'); ?>
                </button>
                <div id="isarud-amz-bulk-export-result" style="margin-top:12px;"></div>
            </div>
        </div>

        <!-- TAB 4: ORDERS -->
        <div class="isarud-amz-tab-content" data-tab-content="orders" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Order Packages', 'api-isarud'); ?></h3>
                <div>
                    <select id="isarud-amz-orders-status">
                        <option value=""><?php _e('All Statuses', 'api-isarud'); ?></option>
                        <option value="Created">Created</option>
                        <option value="Picking">Picking</option>
                        <option value="Invoiced">Invoiced</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <select id="isarud-amz-orders-days">
                        <option value="7"><?php _e('Last 7 Days', 'api-isarud'); ?></option>
                        <option value="14"><?php _e('Last 14 Days', 'api-isarud'); ?></option>
                        <option value="30"><?php _e('Last 30 Days', 'api-isarud'); ?></option>
                    </select>
                    <button class="button" id="isarud-amz-orders-load"><?php _e('Refresh', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-amz-orders-area" class="isarud-amz-data-area">
                <p class="isarud-amz-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 5: RETURNS -->
        <div class="isarud-amz-tab-content" data-tab-content="returns" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Return Requests', 'api-isarud'); ?></h3>
                <button class="button" id="isarud-amz-returns-load"><?php _e('Refresh', 'api-isarud'); ?></button>
            </div>
            <div id="isarud-amz-returns-area" class="isarud-amz-data-area">
                <p class="isarud-amz-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>
        <!-- TAB 6: CARGO -->
        <div class="isarud-amz-tab-content" data-tab-content="cargo" style="display:none;">
            <h3><?php _e('Shipping Information Management', 'api-isarud'); ?></h3>
            <p><?php _e('Add shipping tracking number to Amazon orders.', 'api-isarud'); ?></p>
            <div class="isarud-amz-card isarud-amz-card-blue">
                <h4><?php _e('Assign Shipping', 'api-isarud'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><label for="isarud-amz-cargo-package"><?php _e('Package Number', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-amz-cargo-package" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-amz-cargo-tracking"><?php _e('Tracking Number', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-amz-cargo-tracking" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-amz-cargo-company"><?php _e('Shipping Company', 'api-isarud'); ?></label></th>
                        <td>
                            <select id="isarud-amz-cargo-company">
                                <option value="">—</option>
                                <option value="MNG">MNG Kargo</option>
                                <option value="Yurtici"><?php esc_html_e('Domestic Shipping', 'api-isarud'); ?></option>
                                <option value="Aras">Aras Kargo</option>
                                <option value="Surat"><?php esc_html_e('Express Shipping', 'api-isarud'); ?></option>
                                <option value="PTT">PTT Kargo</option>
                                <option value="Hepsijet">Hepsijet</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <button class="button button-primary" id="isarud-amz-cargo-submit"><?php _e('Send Shipping Information', 'api-isarud'); ?></button>
                <div id="isarud-amz-cargo-result" style="margin-top:10px;"></div>
            </div>
        </div>

        <!-- TAB 7: SETTINGS -->
        <div class="isarud-amz-tab-content" data-tab-content="settings" style="display:none;">
            <h3><?php _e('Connection Settings', 'api-isarud'); ?></h3>
            <div class="isarud-amz-card">
                <h4><?php _e('Amazon Connection Information', 'api-isarud'); ?></h4>
                <p><?php _e('Amazon SP-API connection status and information.', 'api-isarud'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Store', 'api-isarud'); ?></th>
                        <td><span id="isarud-amz-settings-store">—</span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Merchant ID', 'api-isarud'); ?></th>
                        <td><span id="isarud-amz-settings-merchant">—</span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'api-isarud'); ?></th>
                        <td><span id="isarud-amz-settings-status">—</span></td>
                    </tr>
                </table>
                <p style="margin-top:20px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=isarud-marketplaces')); ?>" class="button"><?php _e('Go to Marketplace API Settings', 'api-isarud'); ?></a>
                </p>
            </div>
        </div>



        
        </div>

    </div><!-- /isarud-amz-app -->
    <?php endif; ?>
</div>

<style>
/* Layout */
.isarud-amz-loading { padding:40px;text-align:center;color:#666; }
.isarud-amz-not-connected, .isarud-amz-empty { padding:40px;text-align:center;color:#666;background:#fff;border:1px solid #ddd;border-radius:6px; }
.isarud-amz-info-card { background:#fff;padding:12px 16px;border:1px solid #ddd;border-radius:6px;display:flex;justify-content:space-between;align-items:center;margin:15px 0; }
.isarud-amz-tabs { margin-top:15px; }
.isarud-amz-tab-content { padding:15px;background:#fff;border:1px solid #ddd;border-top:none;min-height:300px; }
.isarud-amz-data-area { background:#fafafa;border:1px solid #e2e2e2;border-radius:4px;padding:10px;min-height:200px; }
.isarud-amz-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:15px 0; }
.isarud-amz-card { background:#f9f9f9;padding:15px;border:1px solid #e2e2e2;border-radius:6px; }
.isarud-amz-card-blue { background:linear-gradient(135deg,#eff6ff,#dbeafe);border-color:#bfdbfe; }
.isarud-amz-card-purple { background:linear-gradient(135deg,#faf5ff,#f3e8ff);border-color:#e9d5ff; }

/* Badges */
.isarud-amz-badge { display:inline-block;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600; }
.isarud-amz-badge-green { background:#d4edda;color:#155724; }
.isarud-amz-badge-red { background:#f8d7da;color:#721c24; }
.isarud-amz-badge-gray { background:#e2e3e5;color:#383d41; }
.isarud-amz-badge-amber { background:#fff3cd;color:#856404; }
.isarud-amz-badge-blue { background:#d1ecf1;color:#0c5460; }
.isarud-amz-badge-purple { background:#e9d8fd;color:#553c9a; }

/* Tables */
.isarud-amz-table { width:100%;border-collapse:collapse;background:#fff; }
.isarud-amz-table th { background:#f1f1f1;padding:8px;text-align:left;font-size:12px;text-transform:uppercase;color:#666;border-bottom:1px solid #ddd; }
.isarud-amz-table td { padding:8px;border-bottom:1px solid #eee;font-size:13px; }
.isarud-amz-table tr:hover { background:#f9f9f9; }

/* Cards/Items */
.isarud-amz-item { background:#fff;padding:10px 15px;border:1px solid #ddd;border-radius:4px;margin-bottom:8px; }
.isarud-amz-item-flex { display:flex;justify-content:space-between;align-items:center; }
</style>

<script type="text/javascript">
jQuery(function($){
    var STATUS_LOADED = false;

    // ═══════ TAB SWITCHING ═══════
    $('.isarud-amz-tabs .nav-tab').on('click', function(e){
        e.preventDefault();
        var tab = $(this).data('tab');
        $('.isarud-amz-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.isarud-amz-tab-content').hide();
        $('[data-tab-content="' + tab + '"]').show();

        // Lazy load on tab open
        if (tab === 'webhooks' && !$('#isarud-amz-webhooks-area').data('loaded')) {
            loadWebhooks();
        }
    });

    // ═══════ STATUS / CONNECTION ═══════
    function loadStatus() {
        $('#isarud-amz-loading').show();
        $('#isarud-amz-not-connected,#isarud-amz-app').hide();

        $.post(ajaxurl, {
            action: 'isarud_amazon_status',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>'
        }, function(r){
            $('#isarud-amz-loading').hide();
            if (r && r.success && r.connected) {
                $('#isarud-amz-app').show();
                $('#isarud-amz-store-name').text(r.store?.name || '—');
                $('#isarud-amz-seller-id').text(r.seller_id || '—');
                $('#isarud-amz-status-badge').removeClass().addClass('isarud-amz-badge isarud-amz-badge-green').text('✓ <?php echo esc_js(__('Connected', 'api-isarud')); ?>');
                if (r.use_stage) $('#isarud-amz-stage-badge').show();
                STATUS_LOADED = true;
                loadListings(); // İlk tab açılışta listings yükle
            } else {
                $('#isarud-amz-not-connected').show();
                $('#isarud-amz-status-badge').removeClass().addClass('isarud-amz-badge isarud-amz-badge-red').text('<?php echo esc_js(__('Not Connected', 'api-isarud')); ?>');
            }
        }).fail(function(){
            $('#isarud-amz-loading').hide();
            $('#isarud-amz-status-badge').text('<?php echo esc_js(__('Error', 'api-isarud')); ?>').addClass('isarud-amz-badge-red');
        });
    }

    $('#isarud-amz-refresh-status').on('click', loadStatus);

    // ═══════ TAB 1: LISTINGS ═══════
    function loadListings() {
        var area = $('#isarud-amz-listings-area');
        area.html('<p><?php echo esc_js(__('Loading...', 'api-isarud')); ?></p>');

        $.post(ajaxurl, {
            action: 'isarud_amazon_listings',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            page: 0,
            size: 50,
            filter: $('#isarud-amz-listings-filter').val()
        }, function(r){
            if (!r || !r.success) {
                area.html('<p style="color:#d32f2f">' + (r?.message || 'Hata') + '</p>');
                return;
            }
            if (!r.items || r.items.length === 0) {
                area.html('<p class="isarud-amz-empty"><?php echo esc_js(__('No products found.','api-isarud')); ?></p>');
                return;
            }
            var html = '<table class="isarud-amz-table"><thead><tr>';
            html += '<th><?php echo esc_js(__('Product','api-isarud')); ?></th><th><?php echo esc_js(__('Barcode','api-isarud')); ?></th><th><?php echo esc_js(__('Price','api-isarud')); ?></th><th><?php echo esc_js(__('Stock','api-isarud')); ?></th><th><?php echo esc_js(__('Status','api-isarud')); ?></th><th></th>';
            html += '</tr></thead><tbody>';
            r.items.forEach(function(item){
                var status = item.onSale ? '<span class="isarud-amz-badge isarud-amz-badge-green">Aktif</span>' :
                                          '<span class="isarud-amz-badge isarud-amz-badge-gray">Pasif</span>';
                html += '<tr>';
                html += '<td><strong>' + (item.title || '—') + '</strong><br><small>' + (item.brand || '') + '</small></td>';
                html += '<td><code>' + (item.barcode || '—') + '</code></td>';
                html += '<td>' + (item.salePrice ? item.salePrice.toFixed(2) + ' TL' : '—') + '</td>';
                html += '<td>' + (item.quantity || 0) + '</td>';
                html += '<td>' + status + '</td>';
                html += '<td>';
                if (item.onSale) {
                    html += '<button class="button button-small isarud-amz-toggle" data-action="deactivate" data-barcode="' + item.barcode + '"><?php echo esc_js(__('Deactivate','api-isarud')); ?></button> ';
                } else {
                    html += '<button class="button button-small isarud-amz-toggle" data-action="activate" data-barcode="' + item.barcode + '"><?php echo esc_js(__('Activate','api-isarud')); ?></button> ';
                }
                html += '<button class="button button-small button-link-delete isarud-amz-delete" data-barcode="' + item.barcode + '">Sil</button>';
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '<p style="color:#666;margin-top:10px;"><?php echo esc_js(__('Total','api-isarud')); ?>: ' + (r.totalElements || r.items.length) + ' <?php echo esc_js(__('products','api-isarud')); ?> · <?php echo esc_js(__('Page','api-isarud')); ?>: ' + ((r.page || 0) + 1) + ' / ' + (r.totalPages || 1) + '</p>';
            area.html(html);
        });
    }
    $('#isarud-amz-listings-load').on('click', loadListings);
    $('#isarud-amz-listings-filter').on('change', loadListings);

    $(document).on('click', '.isarud-amz-toggle', function(){
        var action = $(this).data('action');
        var barcode = $(this).data('barcode');
        if (!confirm('<?php echo esc_js(__('Change product status?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_amazon_listing_' + action,
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            barcode: barcode
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadListings();
        });
    });

    $(document).on('click', '.isarud-amz-delete', function(){
        var barcode = $(this).data('barcode');
        if (!confirm('<?php echo esc_js(__('Delete this product from Amazon? This cannot be undone.','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_amazon_listing_delete',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            barcode: barcode
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadListings();
        });
    });

    // ═══════ TAB 3: STOCK & PRICE (single sync) ═══════
    $('#isarud-amz-single-sync').on('click', function(){
        var data = {
            action: 'isarud_amazon_sync_single',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            barcode: $('#isarud-amz-single-barcode').val(),
            quantity: $('#isarud-amz-single-quantity').val(),
            salePrice: $('#isarud-amz-single-price').val()
        };
        var listP = $('#isarud-amz-single-list').val();
        if (listP) data.listPrice = listP;
        if (!data.barcode || !data.salePrice) {
            alert('Barkod ve fiyat zorunlu.');
            return;
        }
        $('#isarud-amz-single-result').html('<p><?php echo esc_js(__('Sending...','api-isarud')); ?></p>');
        $.post(ajaxurl, data, function(r){
            $('#isarud-amz-single-result').html('<div class="notice notice-' + (r?.success ? 'success' : 'error') + '"><p>' + (r?.message || 'OK') + '</p></div>');
        });
    });

    // ═══════ TAB 4: ORDERS ═══════
    function loadOrders() {
        var area = $('#isarud-amz-orders-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_amazon_orders',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            status: $('#isarud-amz-orders-status').val(),
            days: $('#isarud-amz-orders-days').val()
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.items || r.items.length === 0) { area.html('<p class="isarud-amz-empty"><?php echo esc_js(__('No orders found.','api-isarud')); ?></p>'); return; }
            var html = '<table class="isarud-amz-table"><thead><tr>';
            html += '<th>#</th><th><?php echo esc_js(__('Customer','api-isarud')); ?></th><th><?php echo esc_js(__('Amount','api-isarud')); ?></th><th><?php echo esc_js(__('Status','api-isarud')); ?></th><th><?php echo esc_js(__('Shipping','api-isarud')); ?></th><th></th>';
            html += '</tr></thead><tbody>';
            r.items.forEach(function(o){
                var name = (o.shipmentAddress?.firstName || '') + ' ' + (o.shipmentAddress?.lastName || '');
                html += '<tr>';
                html += '<td><code>' + (o.orderNumber || '—') + '</code></td>';
                html += '<td>' + (name.trim() || '—') + '</td>';
                html += '<td>' + (o.grossAmount ? o.grossAmount.toFixed(2) + ' TL' : '—') + '</td>';
                html += '<td><span class="isarud-amz-badge isarud-amz-badge-blue">' + (o.status || '—') + '</span></td>';
                html += '<td><small>' + (o.cargoProviderName || '—') + '<br>' + (o.cargoTrackingNumber || '') + '</small></td>';
                html += '<td>';
                html += '<button class="button button-small isarud-amz-pkg-status" data-package="' + o.id + '" data-status="Picking">→ Picking</button> ';
                html += '<button class="button button-small isarud-amz-pkg-status" data-package="' + o.id + '" data-status="Shipped">→ Shipped</button>';
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            area.html(html);
        });
    }
    $('#isarud-amz-orders-load').on('click', loadOrders);

    $(document).on('click', '.isarud-amz-pkg-status', function(){
        var pkg = $(this).data('package');
        var st = $(this).data('status');
        if (!confirm('<?php echo esc_js(__('Update package status?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_amazon_package_status',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            package_id: pkg,
            status: st
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadOrders();
        });
    });

    // ═══════ TAB 5: CLAIMS ═══════
    function loadClaims() {
        var area = $('#isarud-amz-returns-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_amazon_returns',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>'
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.items || r.items.length === 0) { area.html('<p class="isarud-amz-empty"><?php echo esc_js(__('No returns found.','api-isarud')); ?></p>'); return; }
            var html = '';
            r.items.forEach(function(c){
                html += '<div class="isarud-amz-item">';
                html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;">';
                html += '<div><strong>#' + c.id + '</strong> <small>' + (c.orderNumber || '') + '</small></div>';
                html += '<span class="isarud-amz-badge isarud-amz-badge-amber">' + (c.claimItemsStatus || c.status || '—') + '</span>';
                html += '</div>';
                html += '<div style="margin:8px 0;">' + (c.customerName || '—') + '</div>';
                html += '<button class="button button-primary button-small isarud-amz-claim-approve" data-claim="' + c.id + '" data-lines="' + ((c.items || []).map(function(i){return i.id;}).join(',')) + '">Onayla</button>';
                html += '</div>';
            });
            area.html(html);
        });
    }
    $('#isarud-amz-returns-load').on('click', loadClaims);

    $(document).on('click', '.isarud-amz-claim-approve', function(){
        var cid = $(this).data('claim');
        var lines = String($(this).data('lines') || '').split(',').filter(Boolean);
        if (lines.length === 0) { alert('Onaylanacak kalem yok'); return; }
        if (!confirm('<?php echo esc_js(__('Approve this return?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_amazon_return_approve',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            claim_id: cid,
            line_ids: lines
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadClaims();
        });
    });

    // ═══════ CONNECT FLOW (Etsy paritesi) ═══════
    $('#isarud-amz-connect-btn').on('click', function(){
        $('#isarud-amz-connect-modal').css('display','flex');
        $('#isarud-amz-stores-list').html('<p style="text-align:center;color:#999;"><?php echo esc_js(__('Loading your stores...', 'api-isarud')); ?></p>');

        $.post(ajaxurl, {
            action: 'isarud_amazon_stores',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>'
        }, function(r){
            if (!r || !r.success || !r.stores || r.stores.length === 0) {
                $('#isarud-amz-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__('Store not found or could not be loaded.', 'api-isarud')); ?></p>');
                return;
            }
            var html = '';
            r.stores.forEach(function(s){
                html += '<div class="isarud-amz-store-item" data-store="' + s.id + '" style="padding:12px 16px;border:1px solid #ddd;border-radius:8px;margin-bottom:8px;cursor:pointer;background:#fff;transition:all 0.15s;">';
                html += '<strong style="font-size:15px;color:#1f2937;">' + (s.name || '<?php echo esc_js(__('Store','api-isarud')); ?> #' + s.id) + '</strong>';
                if (s.slug) html += '<br><small style="color:#999;">' + s.slug + '</small>';
                html += '</div>';
            });
            $('#isarud-amz-stores-list').html(html);
        }).fail(function(){
            $('#isarud-amz-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__('Request failed.', 'api-isarud')); ?></p>');
        });
    });

    $('#isarud-amz-modal-cancel').on('click', function(){
        $('#isarud-amz-connect-modal').hide();
    });

    $(document).on('click', '.isarud-amz-store-item', function(){
        var storeId = $(this).data('store');
        var $item = $(this);
        $('.isarud-amz-store-item').css({background:'#fff', borderColor:'#ddd'});
        $item.css({background:'#fef3e8', borderColor:'#ff9900'});

        // Connect URL al ve yeni tab'da aç
        $.post(ajaxurl, {
            action: 'isarud_amazon_connect_url',
            nonce: '<?php echo wp_create_nonce("isarud_amazon_nonce"); ?>',
            store_id: storeId
        }, function(r){
            if (r && r.success && r.connect_url) {
                $('#isarud-amz-connect-modal').hide();
                window.open(r.connect_url, '_blank');
            } else {
                alert((r && r.message) || '<?php echo esc_js(__('Connection URL could not be retrieved.', 'api-isarud')); ?>');
            }
        }).fail(function(){
            alert('<?php echo esc_js(__('Request failed.', 'api-isarud')); ?>');
        });
    });

    $(document).on('mouseenter', '.isarud-amz-store-item', function(){
        if (!$(this).is(':hover')) return;
        $(this).css({background:'#f9fafb'});
    }).on('mouseleave', '.isarud-amz-store-item', function(){
        $(this).css({background:'#fff'});
    });

    // ═══════ AUTO-EXPORT TOGGLE ═══════
    $('#isarud-amz-auto-export-toggle').on('change', function(){
        var enabled = $(this).is(':checked');
        $.post(ajaxurl, {
            action: 'isarud_toggle_auto_export',
            nonce: '<?php echo wp_create_nonce("isarud_nonce"); ?>',
            enabled: enabled ? '1' : '0'
        }, function(r){
            if (r && r.success) {
                $('#isarud-amz-auto-export-status').html(enabled ? '✅ '.__('On','api-isarud') : '⚪ '.__('Off','api-isarud'));
            } else {
                alert(r?.message || 'Hata');
                $('#isarud-amz-auto-export-toggle').prop('checked', !enabled); // revert
            }
        });
    });

    // ═══════ BULK EXPORT (WC → Amazon) ═══════
    $('#isarud-amz-bulk-export-btn').on('click', function(){
        if (!confirm('<?php echo esc_js(__('All WooCommerce products will be sent to Amazon. Continue?', 'api-isarud')); ?>')) return;
        
        var $btn = $(this);
        var $result = $('#isarud-amz-bulk-export-result');
        $btn.prop('disabled', true).text('🔄 <?php echo esc_js(__('Sending...','api-isarud')); ?>');
        $result.html('<p style="color:#666;">⏳ <?php echo esc_js(__('Products are being transferred, please wait...','api-isarud')); ?></p>');
        
        $.post(ajaxurl, {
            action: 'isarud_export_products',
            nonce: '<?php echo wp_create_nonce("isarud_nonce"); ?>',
            marketplace: 'amazon'
        }, function(r){
            $btn.prop('disabled', false).html('🚀 <?php echo esc_js(__('Send All Products to Amazon', 'api-isarud')); ?>');
            if (r && r.success) {
                $result.html('<div class="notice notice-success" style="margin:0;padding:10px;"><p><strong>✅ <?php echo esc_js(__('Transfer complete!','api-isarud')); ?></strong> ' + (r.summary || '') + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error" style="margin:0;padding:10px;"><p><strong>❌ Hata:</strong> ' + (r?.message || 'Bilinmeyen hata') + '</p></div>');
            }
        }).fail(function(){
            $btn.prop('disabled', false).html('🚀 <?php echo esc_js(__('Send All Products to Amazon', 'api-isarud')); ?>');
            $result.html('<div class="notice notice-error" style="margin:0;padding:10px;"><p><strong>❌ <?php echo esc_js(__('Request failed.','api-isarud')); ?></strong></p></div>');
        });
    });

    // Init
    <?php if (!empty($cloud_key)): ?>
    loadStatus();
    <?php endif; ?>
});
</script>