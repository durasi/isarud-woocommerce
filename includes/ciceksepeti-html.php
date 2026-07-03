<?php
/**
 * ciceksepeti-html.php — WP Plugin Ciceksepeti Admin UI
 * v6.6.0 — 8-tab modern interface
 *
 * AJAX endpoint'leri Isarud_Ciceksepeti class'ına bağlıdır.
 * Auth: isarud_cloud_api_key WP option (Cloud Sync key)
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap" id="isarud-ciceksepeti-app">


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
    <div id="isarud-cs-loading" class="isarud-cs-loading">
        <span class="spinner is-active"></span>
        <?php _e('Checking Ciceksepeti connection...', 'api-isarud'); ?>
    </div>

    <!-- Not connected state -->
    <div id="isarud-cs-not-connected" class="isarud-cs-not-connected" style="display:none;">
        <div class="isarud-cs-empty" style="max-width:600px;margin:0 auto;">
            <h2 style="font-size:24px;margin-bottom:10px;">🛒 <?php _e('Connect to Ciceksepeti', 'api-isarud'); ?></h2>
            <button type="button" class="button button-primary button-hero" id="isarud-cs-connect-btn" style="background:#6B3FA0;border-color:#6B3FA0;font-weight:600;font-size:16px;padding:12px 32px;height:auto;line-height:1.4">
                🔗 <?php _e('Connect with Ciceksepeti', 'api-isarud'); ?>
            </button>
            <p style="color:#6b7280;margin-top:15px;font-size:13px;line-height:1.6;">
                <?php _e('You can integrate your store using the API Key, API Secret, and Seller (Account) Number from the Ciceksepeti Seller Panel.', 'api-isarud'); ?>
            </p>
        </div>
    </div>

    <!-- Store selection modal -->
    <div id="isarud-cs-connect-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:520px;width:90%;padding:30px;box-shadow:0 10px 40px rgba(0,0,0,0.3);max-height:80vh;overflow-y:auto;">
            <h2 style="margin-top:0;font-size:20px;"><?php _e('Select Store', 'api-isarud'); ?></h2>
            <p style="color:#666;font-size:14px;"><?php _e('Select your Isarud store to connect to Ciceksepeti:', 'api-isarud'); ?></p>
            <div id="isarud-cs-stores-list" style="margin:20px 0;">
                <p style="text-align:center;color:#999;"><?php _e('Loading...', 'api-isarud'); ?></p>
            </div>
            <div style="text-align:right;margin-top:20px;border-top:1px solid #eee;padding-top:15px;">
                <button type="button" class="button" id="isarud-cs-modal-cancel"><?php _e('Cancel', 'api-isarud'); ?></button>
            </div>
        </div>
    </div>

    <!-- Connected state with tabs -->
    <div id="isarud-cs-app" style="display:none;">

        <!-- Connection info card -->
        <div class="isarud-cs-info-card">
            <div>
                <strong><?php _e('Store:', 'api-isarud'); ?></strong> <span id="isarud-cs-store-name">—</span>
                · <strong><?php _e('Seller ID:', 'api-isarud'); ?></strong> <span id="isarud-cs-seller-id">—</span>
                <span id="isarud-cs-stage-badge" style="display:none;" class="isarud-cs-badge isarud-cs-badge-purple"><?php _e('STAGE', 'api-isarud'); ?></span>
            </div>
            <div>
                <button class="button" id="isarud-cs-refresh-status">
                    <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'api-isarud'); ?>
                </button>
            </div>
        </div>

        <!-- Tab navigation -->
        <h2 class="nav-tab-wrapper isarud-cs-tabs">
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
        <div class="isarud-cs-tab-content" data-tab-content="listings">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Ciceksepeti Products', 'api-isarud'); ?></h3>
                <div>
                    <select id="isarud-cs-listings-filter">
                        <option value="all"><?php _e('All', 'api-isarud'); ?></option>
                        <option value="approved"><?php _e('Approved', 'api-isarud'); ?></option>
                        <option value="pending"><?php _e('Pending', 'api-isarud'); ?></option>
                        <option value="rejected"><?php _e('Rejected', 'api-isarud'); ?></option>
                    </select>
                    <button class="button" id="isarud-cs-listings-load"><?php _e('Refresh', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-cs-listings-area" class="isarud-cs-data-area">
                <p class="isarud-cs-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        
        <!-- TAB 2: CATEGORIES (Ciceksepeti) -->
        <div class="isarud-cs-tab-content" data-tab-content="categories" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Ciceksepeti Categories', 'api-isarud'); ?></h3>
                <button class="button" id="isarud-cs-categories-load"><?php _e('Upload', 'api-isarud'); ?></button>
            </div>
            <p><?php _e('View the Ciceksepeti category tree and review its attributes.', 'api-isarud'); ?></p>
            <div id="isarud-cs-categories-area" class="isarud-cs-data-area">
                <p class="isarud-cs-empty"><?php _e('Click the Upload button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 3: STOCK & PRICE -->
        <div class="isarud-cs-tab-content" data-tab-content="stock" style="display:none;">
            <h3><?php _e('Stock & Price Synchronization', 'api-isarud'); ?></h3>

            <div class="isarud-cs-card isarud-cs-card-blue">
                <h4><?php _e('Synchronize Single Product', 'api-isarud'); ?></h4>
                <p><?php _e('Send a product\'s barcode, stock, and price information to Ciceksepeti.', 'api-isarud'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="isarud-cs-single-barcode"><?php _e('Barcode', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-cs-single-barcode" class="regular-text" placeholder="8680000000000"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-cs-single-quantity"><?php _e('Inventory', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-cs-single-quantity" min="0" value="0"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-cs-single-price"><?php _e('Sales Price (TRY)', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-cs-single-price" min="0" step="0.01" placeholder="299.90"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-cs-single-list"><?php _e('List Price (optional)', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-cs-single-list" min="0" step="0.01" placeholder="349.90"></td>
                    </tr>
                </table>
                <button class="button button-primary" id="isarud-cs-single-sync"><?php _e('Synchronize', 'api-isarud'); ?></button>
                <div id="isarud-cs-single-result" style="margin-top:10px;"></div>
            </div>

            <div class="isarud-cs-card isarud-cs-card-purple" style="margin-top:20px;">
                <h4>🚀 <?php _e('Automatic Export (Auto-Export)', 'api-isarud'); ?></h4>
                <p style="color:#666;font-size:13px;line-height:1.6;margin-bottom:15px;"><?php _e('When this feature is enabled, whenever you add a new product or update an existing product in WooCommerce, the product is automatically sent to all active marketplaces (including Ciceksepeti).', 'api-isarud'); ?></p>
                
                <div style="display:flex;align-items:center;gap:15px;background:#fff;padding:12px 15px;border-radius:8px;border:1px solid #ddd;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;flex:1;">
                        <input type="checkbox" id="isarud-cs-auto-export-toggle" <?php echo get_option('isarud_auto_export_enabled', false) ? 'checked' : ''; ?> style="width:20px;height:20px;cursor:pointer;">
                        <span style="font-weight:600;font-size:15px;"><?php _e('Automatic export is ACTIVE', 'api-isarud'); ?></span>
                    </label>
                    <span id="isarud-cs-auto-export-status" style="font-size:13px;color:#666;"><?php echo get_option('isarud_auto_export_enabled', false) ? '✅ '.__('On','api-isarud') : '⚪ '.__('Off','api-isarud'); ?></span>
                </div>

                <p style="color:#999;font-size:12px;margin-top:12px;line-height:1.5;">
                    ⚠️ <?php _e('For automatic export, your products must have', 'api-isarud'); ?> 
                    <strong><?php _e('Ciceksepeti category and brand mappings', 'api-isarud'); ?></strong>
                    <?php _e('completed. Otherwise, the export will fail.', 'api-isarud'); ?>
                </p>
            </div>

            <div class="isarud-cs-card" style="margin-top:20px;">
                <h4>📦 <?php _e('Manual Bulk Submit (WooCommerce → Ciceksepeti)', 'api-isarud'); ?></h4>
                <p style="color:#666;font-size:13px;line-height:1.6;margin-bottom:15px;"><?php _e('Use this button to send all your WooCommerce products to Ciceksepeti at once. Only products with completed category and brand mappings will be transferred.', 'api-isarud'); ?></p>
                <button class="button button-primary" id="isarud-cs-bulk-export-btn" style="background:#6B3FA0;border-color:#6B3FA0;font-weight:600;">
                    🚀 <?php _e('Send All Products to Ciceksepeti', 'api-isarud'); ?>
                </button>
                <div id="isarud-cs-bulk-export-result" style="margin-top:12px;"></div>
            </div>
        </div>

        <!-- TAB 4: ORDERS -->
        <div class="isarud-cs-tab-content" data-tab-content="orders" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Order Packages', 'api-isarud'); ?></h3>
                <div>
                    <select id="isarud-cs-orders-status">
                        <option value=""><?php _e('All Statuses', 'api-isarud'); ?></option>
                        <option value="Created">Created</option>
                        <option value="Picking">Picking</option>
                        <option value="Invoiced">Invoiced</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <select id="isarud-cs-orders-days">
                        <option value="7"><?php _e('Last 7 Days', 'api-isarud'); ?></option>
                        <option value="14"><?php _e('Last 14 Days', 'api-isarud'); ?></option>
                        <option value="30"><?php _e('Last 30 Days', 'api-isarud'); ?></option>
                    </select>
                    <button class="button" id="isarud-cs-orders-load"><?php _e('Refresh', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-cs-orders-area" class="isarud-cs-data-area">
                <p class="isarud-cs-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 5: RETURNS -->
        <div class="isarud-cs-tab-content" data-tab-content="returns" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Return Requests', 'api-isarud'); ?></h3>
                <button class="button" id="isarud-cs-returns-load"><?php _e('Refresh', 'api-isarud'); ?></button>
            </div>
            <div id="isarud-cs-returns-area" class="isarud-cs-data-area">
                <p class="isarud-cs-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>
        <!-- TAB 6: CARGO -->
        <div class="isarud-cs-tab-content" data-tab-content="cargo" style="display:none;">
            <h3><?php _e('Shipping Information Management', 'api-isarud'); ?></h3>
            <p><?php _e('Add tracking numbers to Ciceksepeti orders.', 'api-isarud'); ?></p>
            <div class="isarud-cs-card isarud-cs-card-blue">
                <h4><?php _e('Assign Shipping', 'api-isarud'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><label for="isarud-cs-cargo-package"><?php _e('Package Number', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-cs-cargo-package" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-cs-cargo-tracking"><?php _e('Tracking Number', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-cs-cargo-tracking" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-cs-cargo-company"><?php _e('Shipping Company', 'api-isarud'); ?></label></th>
                        <td>
                            <select id="isarud-cs-cargo-company">
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
                <button class="button button-primary" id="isarud-cs-cargo-submit"><?php _e('Send Shipping Information', 'api-isarud'); ?></button>
                <div id="isarud-cs-cargo-result" style="margin-top:10px;"></div>
            </div>
        </div>

        <!-- TAB 7: SETTINGS -->
        <div class="isarud-cs-tab-content" data-tab-content="settings" style="display:none;">
            <h3><?php _e('Connection Settings', 'api-isarud'); ?></h3>
            <div class="isarud-cs-card">
                <h4><?php _e('Ciceksepeti Connection Information', 'api-isarud'); ?></h4>
                <p><?php _e('Status and information about your Ciceksepeti API connection.', 'api-isarud'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Store', 'api-isarud'); ?></th>
                        <td><span id="isarud-cs-settings-store">—</span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Seller ID', 'api-isarud'); ?></th>
                        <td><span id="isarud-cs-settings-merchant">—</span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'api-isarud'); ?></th>
                        <td><span id="isarud-cs-settings-status">—</span></td>
                    </tr>
                </table>
                <p style="margin-top:20px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=isarud-marketplaces')); ?>" class="button"><?php _e('Go to Marketplace API Settings', 'api-isarud'); ?></a>
                </p>
            </div>
        </div>



        
        </div>

    </div><!-- /isarud-cs-app -->
    <?php endif; ?>
</div>

<style>
/* Layout */
.isarud-cs-loading { padding:40px;text-align:center;color:#666; }
.isarud-cs-not-connected, .isarud-cs-empty { padding:40px;text-align:center;color:#666;background:#fff;border:1px solid #ddd;border-radius:6px; }
.isarud-cs-info-card { background:#fff;padding:12px 16px;border:1px solid #ddd;border-radius:6px;display:flex;justify-content:space-between;align-items:center;margin:15px 0; }
.isarud-cs-tabs { margin-top:15px; }
.isarud-cs-tab-content { padding:15px;background:#fff;border:1px solid #ddd;border-top:none;min-height:300px; }
.isarud-cs-data-area { background:#fafafa;border:1px solid #e2e2e2;border-radius:4px;padding:10px;min-height:200px; }
.isarud-cs-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:15px 0; }
.isarud-cs-card { background:#f9f9f9;padding:15px;border:1px solid #e2e2e2;border-radius:6px; }
.isarud-cs-card-blue { background:linear-gradient(135deg,#eff6ff,#dbeafe);border-color:#bfdbfe; }
.isarud-cs-card-purple { background:linear-gradient(135deg,#faf5ff,#f3e8ff);border-color:#e9d5ff; }

/* Badges */
.isarud-cs-badge { display:inline-block;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600; }
.isarud-cs-badge-green { background:#d4edda;color:#155724; }
.isarud-cs-badge-red { background:#f8d7da;color:#721c24; }
.isarud-cs-badge-gray { background:#e2e3e5;color:#383d41; }
.isarud-cs-badge-amber { background:#fff3cd;color:#856404; }
.isarud-cs-badge-blue { background:#d1ecf1;color:#0c5460; }
.isarud-cs-badge-purple { background:#e9d8fd;color:#553c9a; }

/* Tables */
.isarud-cs-table { width:100%;border-collapse:collapse;background:#fff; }
.isarud-cs-table th { background:#f1f1f1;padding:8px;text-align:left;font-size:12px;text-transform:uppercase;color:#666;border-bottom:1px solid #ddd; }
.isarud-cs-table td { padding:8px;border-bottom:1px solid #eee;font-size:13px; }
.isarud-cs-table tr:hover { background:#f9f9f9; }

/* Cards/Items */
.isarud-cs-item { background:#fff;padding:10px 15px;border:1px solid #ddd;border-radius:4px;margin-bottom:8px; }
.isarud-cs-item-flex { display:flex;justify-content:space-between;align-items:center; }
</style>

<script type="text/javascript">
jQuery(function($){
    var STATUS_LOADED = false;

    // ═══════ TAB SWITCHING ═══════
    $('.isarud-cs-tabs .nav-tab').on('click', function(e){
        e.preventDefault();
        var tab = $(this).data('tab');
        $('.isarud-cs-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.isarud-cs-tab-content').hide();
        $('[data-tab-content="' + tab + '"]').show();

        // Lazy load on tab open
        if (tab === 'webhooks' && !$('#isarud-cs-webhooks-area').data('loaded')) {
            loadWebhooks();
        }
    });

    // ═══════ STATUS / CONNECTION ═══════
    function loadStatus() {
        $('#isarud-cs-loading').show();
        $('#isarud-cs-not-connected,#isarud-cs-app').hide();

        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_status',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>'
        }, function(r){
            $('#isarud-cs-loading').hide();
            if (r && r.success && r.connected) {
                $('#isarud-cs-app').show();
                $('#isarud-cs-store-name').text(r.store?.name || '—');
                $('#isarud-cs-seller-id').text(r.seller_id || '—');
                $('#isarud-cs-status-badge').removeClass().addClass('isarud-cs-badge isarud-cs-badge-green').text('✓ <?php echo esc_js(__('Connected', 'api-isarud')); ?>');
                if (r.use_stage) $('#isarud-cs-stage-badge').show();
                STATUS_LOADED = true;
                loadListings(); // İlk tab açılışta listings yükle
            } else {
                $('#isarud-cs-not-connected').show();
                $('#isarud-cs-status-badge').removeClass().addClass('isarud-cs-badge isarud-cs-badge-red').text('<?php echo esc_js(__('Not Connected', 'api-isarud')); ?>');
            }
        }).fail(function(){
            $('#isarud-cs-loading').hide();
            $('#isarud-cs-status-badge').text('<?php echo esc_js(__('Error', 'api-isarud')); ?>').addClass('isarud-cs-badge-red');
        });
    }

    $('#isarud-cs-refresh-status').on('click', loadStatus);

    // ═══════ TAB 1: LISTINGS ═══════
    function loadListings() {
        var area = $('#isarud-cs-listings-area');
        area.html('<p><?php echo esc_js(__('Loading...', 'api-isarud')); ?></p>');

        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_listings',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            page: 0,
            size: 50,
            filter: $('#isarud-cs-listings-filter').val()
        }, function(r){
            if (!r || !r.success) {
                area.html('<p style="color:#d32f2f">' + (r?.message || 'Hata') + '</p>');
                return;
            }
            if (!r.items || r.items.length === 0) {
                area.html('<p class="isarud-cs-empty"><?php echo esc_js(__('No products found.','api-isarud')); ?></p>');
                return;
            }
            var html = '<table class="isarud-cs-table"><thead><tr>';
            html += '<th><?php echo esc_js(__('Product','api-isarud')); ?></th><th><?php echo esc_js(__('Barcode','api-isarud')); ?></th><th><?php echo esc_js(__('Price','api-isarud')); ?></th><th><?php echo esc_js(__('Stock','api-isarud')); ?></th><th><?php echo esc_js(__('Status','api-isarud')); ?></th><th></th>';
            html += '</tr></thead><tbody>';
            r.items.forEach(function(item){
                var status = item.onSale ? '<span class="isarud-cs-badge isarud-cs-badge-green">Aktif</span>' :
                                          '<span class="isarud-cs-badge isarud-cs-badge-gray">Pasif</span>';
                html += '<tr>';
                html += '<td><strong>' + (item.title || '—') + '</strong><br><small>' + (item.brand || '') + '</small></td>';
                html += '<td><code>' + (item.barcode || '—') + '</code></td>';
                html += '<td>' + (item.salePrice ? item.salePrice.toFixed(2) + ' TL' : '—') + '</td>';
                html += '<td>' + (item.quantity || 0) + '</td>';
                html += '<td>' + status + '</td>';
                html += '<td>';
                if (item.onSale) {
                    html += '<button class="button button-small isarud-cs-toggle" data-action="deactivate" data-barcode="' + item.barcode + '"><?php echo esc_js(__('Deactivate','api-isarud')); ?></button> ';
                } else {
                    html += '<button class="button button-small isarud-cs-toggle" data-action="activate" data-barcode="' + item.barcode + '"><?php echo esc_js(__('Activate','api-isarud')); ?></button> ';
                }
                html += '<button class="button button-small button-link-delete isarud-cs-delete" data-barcode="' + item.barcode + '">Sil</button>';
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '<p style="color:#666;margin-top:10px;"><?php echo esc_js(__('Total','api-isarud')); ?>: ' + (r.totalElements || r.items.length) + ' <?php echo esc_js(__('products','api-isarud')); ?> · <?php echo esc_js(__('Page','api-isarud')); ?>: ' + ((r.page || 0) + 1) + ' / ' + (r.totalPages || 1) + '</p>';
            area.html(html);
        });
    }
    $('#isarud-cs-listings-load').on('click', loadListings);
    $('#isarud-cs-listings-filter').on('change', loadListings);

    $(document).on('click', '.isarud-cs-toggle', function(){
        var action = $(this).data('action');
        var barcode = $(this).data('barcode');
        if (!confirm('<?php echo esc_js(__('Change product status?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_listing_' + action,
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            barcode: barcode
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadListings();
        });
    });

    $(document).on('click', '.isarud-cs-delete', function(){
        var barcode = $(this).data('barcode');
        if (!confirm('<?php echo esc_js(__('Delete this product from Ciceksepeti? This cannot be undone.','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_listing_delete',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            barcode: barcode
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadListings();
        });
    });

    // ═══════ TAB 3: STOCK & PRICE (single sync) ═══════
    $('#isarud-cs-single-sync').on('click', function(){
        var data = {
            action: 'isarud_ciceksepeti_sync_single',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            barcode: $('#isarud-cs-single-barcode').val(),
            quantity: $('#isarud-cs-single-quantity').val(),
            salePrice: $('#isarud-cs-single-price').val()
        };
        var listP = $('#isarud-cs-single-list').val();
        if (listP) data.listPrice = listP;
        if (!data.barcode || !data.salePrice) {
            alert('Barkod ve fiyat zorunlu.');
            return;
        }
        $('#isarud-cs-single-result').html('<p><?php echo esc_js(__('Sending...','api-isarud')); ?></p>');
        $.post(ajaxurl, data, function(r){
            $('#isarud-cs-single-result').html('<div class="notice notice-' + (r?.success ? 'success' : 'error') + '"><p>' + (r?.message || 'OK') + '</p></div>');
        });
    });

    // ═══════ TAB 4: ORDERS ═══════
    function loadOrders() {
        var area = $('#isarud-cs-orders-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_orders',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            status: $('#isarud-cs-orders-status').val(),
            days: $('#isarud-cs-orders-days').val()
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.items || r.items.length === 0) { area.html('<p class="isarud-cs-empty"><?php echo esc_js(__('No orders found.','api-isarud')); ?></p>'); return; }
            var html = '<table class="isarud-cs-table"><thead><tr>';
            html += '<th>#</th><th><?php echo esc_js(__('Customer','api-isarud')); ?></th><th><?php echo esc_js(__('Amount','api-isarud')); ?></th><th><?php echo esc_js(__('Status','api-isarud')); ?></th><th><?php echo esc_js(__('Shipping','api-isarud')); ?></th><th></th>';
            html += '</tr></thead><tbody>';
            r.items.forEach(function(o){
                var name = (o.shipmentAddress?.firstName || '') + ' ' + (o.shipmentAddress?.lastName || '');
                html += '<tr>';
                html += '<td><code>' + (o.orderNumber || '—') + '</code></td>';
                html += '<td>' + (name.trim() || '—') + '</td>';
                html += '<td>' + (o.grossAmount ? o.grossAmount.toFixed(2) + ' TL' : '—') + '</td>';
                html += '<td><span class="isarud-cs-badge isarud-cs-badge-blue">' + (o.status || '—') + '</span></td>';
                html += '<td><small>' + (o.cargoProviderName || '—') + '<br>' + (o.cargoTrackingNumber || '') + '</small></td>';
                html += '<td>';
                html += '<button class="button button-small isarud-cs-pkg-status" data-package="' + o.id + '" data-status="Picking">→ Picking</button> ';
                html += '<button class="button button-small isarud-cs-pkg-status" data-package="' + o.id + '" data-status="Shipped">→ Shipped</button>';
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            area.html(html);
        });
    }
    $('#isarud-cs-orders-load').on('click', loadOrders);

    $(document).on('click', '.isarud-cs-pkg-status', function(){
        var pkg = $(this).data('package');
        var st = $(this).data('status');
        if (!confirm('<?php echo esc_js(__('Update package status?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_package_status',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            package_id: pkg,
            status: st
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadOrders();
        });
    });

    // ═══════ TAB 5: CLAIMS ═══════
    function loadClaims() {
        var area = $('#isarud-cs-returns-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_returns',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>'
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.items || r.items.length === 0) { area.html('<p class="isarud-cs-empty"><?php echo esc_js(__('No returns found.','api-isarud')); ?></p>'); return; }
            var html = '';
            r.items.forEach(function(c){
                html += '<div class="isarud-cs-item">';
                html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;">';
                html += '<div><strong>#' + c.id + '</strong> <small>' + (c.orderNumber || '') + '</small></div>';
                html += '<span class="isarud-cs-badge isarud-cs-badge-amber">' + (c.claimItemsStatus || c.status || '—') + '</span>';
                html += '</div>';
                html += '<div style="margin:8px 0;">' + (c.customerName || '—') + '</div>';
                html += '<button class="button button-primary button-small isarud-cs-claim-approve" data-claim="' + c.id + '" data-lines="' + ((c.items || []).map(function(i){return i.id;}).join(',')) + '">Onayla</button>';
                html += '</div>';
            });
            area.html(html);
        });
    }
    $('#isarud-cs-returns-load').on('click', loadClaims);

    $(document).on('click', '.isarud-cs-claim-approve', function(){
        var cid = $(this).data('claim');
        var lines = String($(this).data('lines') || '').split(',').filter(Boolean);
        if (lines.length === 0) { alert('Onaylanacak kalem yok'); return; }
        if (!confirm('<?php echo esc_js(__('Approve this return?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_return_approve',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            claim_id: cid,
            line_ids: lines
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadClaims();
        });
    });

    // ═══════ CONNECT FLOW (Etsy paritesi) ═══════
    $('#isarud-cs-connect-btn').on('click', function(){
        $('#isarud-cs-connect-modal').css('display','flex');
        $('#isarud-cs-stores-list').html('<p style="text-align:center;color:#999;"><?php echo esc_js(__('Loading your stores...', 'api-isarud')); ?></p>');

        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_stores',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>'
        }, function(r){
            if (!r || !r.success || !r.stores || r.stores.length === 0) {
                $('#isarud-cs-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__('Store not found or could not be loaded.', 'api-isarud')); ?></p>');
                return;
            }
            var html = '';
            r.stores.forEach(function(s){
                html += '<div class="isarud-cs-store-item" data-store="' + s.id + '" style="padding:12px 16px;border:1px solid #ddd;border-radius:8px;margin-bottom:8px;cursor:pointer;background:#fff;transition:all 0.15s;">';
                html += '<strong style="font-size:15px;color:#1f2937;">' + (s.name || '<?php echo esc_js(__('Store','api-isarud')); ?> #' + s.id) + '</strong>';
                if (s.slug) html += '<br><small style="color:#999;">' + s.slug + '</small>';
                html += '</div>';
            });
            $('#isarud-cs-stores-list').html(html);
        }).fail(function(){
            $('#isarud-cs-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__('Request failed.', 'api-isarud')); ?></p>');
        });
    });

    $('#isarud-cs-modal-cancel').on('click', function(){
        $('#isarud-cs-connect-modal').hide();
    });

    $(document).on('click', '.isarud-cs-store-item', function(){
        var storeId = $(this).data('store');
        var $item = $(this);
        $('.isarud-cs-store-item').css({background:'#fff', borderColor:'#ddd'});
        $item.css({background:'#fef3e8', borderColor:'#6B3FA0'});

        // Connect URL al ve yeni tab'da aç
        $.post(ajaxurl, {
            action: 'isarud_ciceksepeti_connect_url',
            nonce: '<?php echo wp_create_nonce("isarud_ciceksepeti_nonce"); ?>',
            store_id: storeId
        }, function(r){
            if (r && r.success && r.connect_url) {
                $('#isarud-cs-connect-modal').hide();
                window.open(r.connect_url, '_blank');
            } else {
                alert((r && r.message) || '<?php echo esc_js(__('Connection URL could not be retrieved.', 'api-isarud')); ?>');
            }
        }).fail(function(){
            alert('<?php echo esc_js(__('Request failed.', 'api-isarud')); ?>');
        });
    });

    $(document).on('mouseenter', '.isarud-cs-store-item', function(){
        if (!$(this).is(':hover')) return;
        $(this).css({background:'#f9fafb'});
    }).on('mouseleave', '.isarud-cs-store-item', function(){
        $(this).css({background:'#fff'});
    });

    // ═══════ AUTO-EXPORT TOGGLE ═══════
    $('#isarud-cs-auto-export-toggle').on('change', function(){
        var enabled = $(this).is(':checked');
        $.post(ajaxurl, {
            action: 'isarud_toggle_auto_export',
            nonce: '<?php echo wp_create_nonce("isarud_nonce"); ?>',
            enabled: enabled ? '1' : '0'
        }, function(r){
            if (r && r.success) {
                $('#isarud-cs-auto-export-status').html(enabled ? '✅ '.__('On','api-isarud') : '⚪ '.__('Off','api-isarud'));
            } else {
                alert(r?.message || 'Hata');
                $('#isarud-cs-auto-export-toggle').prop('checked', !enabled); // revert
            }
        });
    });

    // ═══════ BULK EXPORT (WC → Ciceksepeti) ═══════
    $('#isarud-cs-bulk-export-btn').on('click', function(){
        if (!confirm('<?php echo esc_js(__('All WooCommerce products will be sent to Ciceksepeti. Continue?', 'api-isarud')); ?>')) return;
        
        var $btn = $(this);
        var $result = $('#isarud-cs-bulk-export-result');
        $btn.prop('disabled', true).text('🔄 <?php echo esc_js(__('Sending...','api-isarud')); ?>');
        $result.html('<p style="color:#666;">⏳ <?php echo esc_js(__('Products are being transferred, please wait...','api-isarud')); ?></p>');
        
        $.post(ajaxurl, {
            action: 'isarud_export_products',
            nonce: '<?php echo wp_create_nonce("isarud_nonce"); ?>',
            marketplace: 'ciceksepeti'
        }, function(r){
            $btn.prop('disabled', false).html('🚀 <?php echo esc_js(__('Send All Products to Ciceksepeti', 'api-isarud')); ?>');
            if (r && r.success) {
                $result.html('<div class="notice notice-success" style="margin:0;padding:10px;"><p><strong>✅ <?php echo esc_js(__('Transfer complete!','api-isarud')); ?></strong> ' + (r.summary || '') + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error" style="margin:0;padding:10px;"><p><strong>❌ Hata:</strong> ' + (r?.message || 'Bilinmeyen hata') + '</p></div>');
            }
        }).fail(function(){
            $btn.prop('disabled', false).html('🚀 <?php echo esc_js(__('Send All Products to Ciceksepeti', 'api-isarud')); ?>');
            $result.html('<div class="notice notice-error" style="margin:0;padding:10px;"><p><strong>❌ <?php echo esc_js(__('Request failed.','api-isarud')); ?></strong></p></div>');
        });
    });

    // Init
    <?php if (!empty($cloud_key)): ?>
    loadStatus();
    <?php endif; ?>
});
</script>