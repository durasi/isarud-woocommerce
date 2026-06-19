<?php
/**
 * trendyol-html.php — WP Plugin Trendyol Admin UI
 * v6.6.0 — 8-tab modern interface
 *
 * AJAX endpoint'leri Isarud_Trendyol class'ına bağlıdır.
 * Auth: isarud_cloud_api_key WP option (Cloud Sync key)
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap" id="isarud-trendyol-app">


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
    <div id="isarud-tr-loading" class="isarud-tr-loading">
        <span class="spinner is-active"></span>
        <?php _e('Checking Trendyol connection...', 'api-isarud'); ?>
    </div>

    <!-- Not connected state -->
    <div id="isarud-tr-not-connected" class="isarud-tr-not-connected" style="display:none;">
        <div class="isarud-tr-empty" style="max-width:600px;margin:0 auto;">
            <h2 style="font-size:24px;margin-bottom:10px;">🛒 <?php _e('Connect to Trendyol', 'api-isarud'); ?></h2>
            <button type="button" class="button button-primary button-hero" id="isarud-tr-connect-btn" style="background:#f27a1a;border-color:#f27a1a;font-weight:600;font-size:16px;padding:12px 32px;height:auto;line-height:1.4">
                🔗 <?php _e('Connect with Trendyol', 'api-isarud'); ?>
            </button>
            <p style="color:#6b7280;margin-top:15px;font-size:13px;line-height:1.6;">
                <?php _e('You can integrate your store with the API Key, API Secret, and Seller (Account) Number obtained from the Trendyol Seller Panel.', 'api-isarud'); ?>
            </p>
        </div>
    </div>

    <!-- Store selection modal -->
    <div id="isarud-tr-connect-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:520px;width:90%;padding:30px;box-shadow:0 10px 40px rgba(0,0,0,0.3);max-height:80vh;overflow-y:auto;">
            <h2 style="margin-top:0;font-size:20px;"><?php _e('Select Store', 'api-isarud'); ?></h2>
            <p style="color:#666;font-size:14px;"><?php _e('Select your Isarud store to connect with Trendyol:', 'api-isarud'); ?></p>
            <div id="isarud-tr-stores-list" style="margin:20px 0;">
                <p style="text-align:center;color:#999;"><?php _e('Loading...', 'api-isarud'); ?></p>
            </div>
            <div style="text-align:right;margin-top:20px;border-top:1px solid #eee;padding-top:15px;">
                <button type="button" class="button" id="isarud-tr-modal-cancel"><?php _e('Cancel', 'api-isarud'); ?></button>
            </div>
        </div>
    </div>

    <!-- Connected state with tabs -->
    <div id="isarud-tr-app" style="display:none;">

        <!-- Connection info card -->
        <div class="isarud-tr-info-card">
            <div>
                <strong><?php _e('Store:', 'api-isarud'); ?></strong> <span id="isarud-tr-store-name">—</span>
                · <strong><?php _e('Seller ID:', 'api-isarud'); ?></strong> <span id="isarud-tr-seller-id">—</span>
                <span id="isarud-tr-stage-badge" style="display:none;" class="isarud-tr-badge isarud-tr-badge-purple"><?php _e('STAGE', 'api-isarud'); ?></span>
            </div>
            <div>
                <button class="button" id="isarud-tr-refresh-status">
                    <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'api-isarud'); ?>
                </button>
            </div>
        </div>

        <!-- Tab navigation -->
        <h2 class="nav-tab-wrapper isarud-tr-tabs">
            <a href="#listings"  class="nav-tab nav-tab-active" data-tab="listings">📦 <?php _e('Products', 'api-isarud'); ?></a>
            <a href="#brands"    class="nav-tab" data-tab="brands">🏷️ <?php _e('Brand & Category', 'api-isarud'); ?></a>
            <a href="#stock"     class="nav-tab" data-tab="stock">📊 <?php _e('Stock & Price', 'api-isarud'); ?></a>
            <a href="#orders"    class="nav-tab" data-tab="orders">📋 <?php _e('Orders', 'api-isarud'); ?></a>
            <a href="#claims"    class="nav-tab" data-tab="claims">↩️ <?php _e('Returns', 'api-isarud'); ?></a>
            <a href="#questions" class="nav-tab" data-tab="questions">💬 <?php _e('Questions', 'api-isarud'); ?></a>
            <a href="#invoice"   class="nav-tab" data-tab="invoice">📄 <?php _e('Invoice', 'api-isarud'); ?></a>
            <a href="#webhooks"  class="nav-tab" data-tab="webhooks">🔔 <?php _e('Webhook', 'api-isarud'); ?></a>
        </h2>

        <!-- Tab contents -->

        <!-- TAB 1: LISTINGS -->
        <div class="isarud-tr-tab-content" data-tab-content="listings">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Trendyol Products', 'api-isarud'); ?></h3>
                <div>
                    <select id="isarud-tr-listings-filter">
                        <option value="all"><?php _e('All', 'api-isarud'); ?></option>
                        <option value="approved"><?php _e('Approved', 'api-isarud'); ?></option>
                        <option value="pending"><?php _e('Pending', 'api-isarud'); ?></option>
                        <option value="rejected"><?php _e('Rejected', 'api-isarud'); ?></option>
                    </select>
                    <button class="button" id="isarud-tr-listings-load"><?php _e('Refresh', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-tr-listings-area" class="isarud-tr-data-area">
                <p class="isarud-tr-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 2: BRANDS & CATEGORIES -->
        <div class="isarud-tr-tab-content" data-tab-content="brands" style="display:none;">
            <div class="isarud-tr-grid-2">
                <div>
                    <h3><?php _e('Search Brand', 'api-isarud'); ?></h3>
                    <input type="text" id="isarud-tr-brand-query" class="regular-text"
                           placeholder="<?php esc_attr_e('Brand name (minimum 2 characters)...', 'api-isarud'); ?>" style="width:100%;">
                    <div id="isarud-tr-brands-area" class="isarud-tr-data-area" style="margin-top:10px;max-height:400px;overflow-y:auto;"></div>
                </div>
                <div>
                    <h3><?php _e('Category Tree', 'api-isarud'); ?></h3>
                    <button class="button" id="isarud-tr-categories-load"><?php _e('Load (24h cached)', 'api-isarud'); ?></button>
                    <div id="isarud-tr-categories-area" class="isarud-tr-data-area" style="margin-top:10px;max-height:400px;overflow-y:auto;"></div>
                </div>
            </div>
        </div>

        <!-- TAB 3: STOCK & PRICE -->
        <div class="isarud-tr-tab-content" data-tab-content="stock" style="display:none;">
            <h3><?php _e('Stock & Price Synchronization', 'api-isarud'); ?></h3>

            <div class="isarud-tr-card isarud-tr-card-blue">
                <h4><?php _e('Synchronize Single Product', 'api-isarud'); ?></h4>
                <p><?php _e('Send product barcode, stock, and price information to Trendyol.', 'api-isarud'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="isarud-tr-single-barcode"><?php _e('Barcode', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-tr-single-barcode" class="regular-text" placeholder="8680000000000"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-tr-single-quantity"><?php _e('Inventory', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-tr-single-quantity" min="0" value="0"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-tr-single-price"><?php _e('Sales Price (TRY)', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-tr-single-price" min="0" step="0.01" placeholder="299.90"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-tr-single-list"><?php _e('List Price (optional)', 'api-isarud'); ?></label></th>
                        <td><input type="number" id="isarud-tr-single-list" min="0" step="0.01" placeholder="349.90"></td>
                    </tr>
                </table>
                <button class="button button-primary" id="isarud-tr-single-sync"><?php _e('Synchronize', 'api-isarud'); ?></button>
                <div id="isarud-tr-single-result" style="margin-top:10px;"></div>
            </div>

            <div class="isarud-tr-card isarud-tr-card-purple" style="margin-top:20px;">
                <h4>🚀 <?php _e('Automatic Export (Auto-Export)', 'api-isarud'); ?></h4>
                <p style="color:#666;font-size:13px;line-height:1.6;margin-bottom:15px;"><?php _e('When this feature is enabled, whenever you add a new product or update an existing product in WooCommerce, the product is automatically sent to all active marketplaces (including Trendyol).', 'api-isarud'); ?></p>
                
                <div style="display:flex;align-items:center;gap:15px;background:#fff;padding:12px 15px;border-radius:8px;border:1px solid #ddd;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;flex:1;">
                        <input type="checkbox" id="isarud-tr-auto-export-toggle" <?php echo get_option('isarud_auto_export_enabled', false) ? 'checked' : ''; ?> style="width:20px;height:20px;cursor:pointer;">
                        <span style="font-weight:600;font-size:15px;"><?php _e('Automatic export is ACTIVE', 'api-isarud'); ?></span>
                    </label>
                    <span id="isarud-tr-auto-export-status" style="font-size:13px;color:#666;"><?php echo get_option('isarud_auto_export_enabled', false) ? '✅ '.__('On','api-isarud') : '⚪ '.__('Off','api-isarud'); ?></span>
                </div>

                <p style="color:#999;font-size:12px;margin-top:12px;line-height:1.5;">
                    ⚠️ <?php _e('For automatic export, your products must have', 'api-isarud'); ?> 
                    <strong><?php _e('Trendyol category and brand mappings', 'api-isarud'); ?></strong>
                    <?php _e('completed. Otherwise, the export will fail.', 'api-isarud'); ?>
                </p>
            </div>

            <div class="isarud-tr-card" style="margin-top:20px;">
                <h4>📦 <?php _e('Manual Bulk Send (WooCommerce → Trendyol)', 'api-isarud'); ?></h4>
                <p style="color:#666;font-size:13px;line-height:1.6;margin-bottom:15px;"><?php _e('Use this button to send all your WooCommerce products to Trendyol at once. Only products with completed category and brand mappings will be transferred.', 'api-isarud'); ?></p>
                <button class="button button-primary" id="isarud-tr-bulk-export-btn" style="background:#f27a1a;border-color:#f27a1a;font-weight:600;">
                    🚀 <?php _e('Send All Products to Trendyol', 'api-isarud'); ?>
                </button>
                <div id="isarud-tr-bulk-export-result" style="margin-top:12px;"></div>
            </div>
        </div>

        <!-- TAB 4: ORDERS -->
        <div class="isarud-tr-tab-content" data-tab-content="orders" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Order Packages', 'api-isarud'); ?></h3>
                <div>
                    <select id="isarud-tr-orders-status">
                        <option value=""><?php _e('All Statuses', 'api-isarud'); ?></option>
                        <option value="Created">Created</option>
                        <option value="Picking">Picking</option>
                        <option value="Invoiced">Invoiced</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <select id="isarud-tr-orders-days">
                        <option value="7"><?php _e('Last 7 Days', 'api-isarud'); ?></option>
                        <option value="14"><?php _e('Last 14 Days', 'api-isarud'); ?></option>
                        <option value="30"><?php _e('Last 30 Days', 'api-isarud'); ?></option>
                    </select>
                    <button class="button" id="isarud-tr-orders-load"><?php _e('Refresh', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-tr-orders-area" class="isarud-tr-data-area">
                <p class="isarud-tr-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 5: CLAIMS -->
        <div class="isarud-tr-tab-content" data-tab-content="claims" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Return Requests', 'api-isarud'); ?></h3>
                <button class="button" id="isarud-tr-claims-load"><?php _e('Refresh', 'api-isarud'); ?></button>
            </div>
            <div id="isarud-tr-claims-area" class="isarud-tr-data-area">
                <p class="isarud-tr-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 6: CUSTOMER Q&A -->
        <div class="isarud-tr-tab-content" data-tab-content="questions" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Customer Questions', 'api-isarud'); ?></h3>
                <div>
                    <select id="isarud-tr-questions-status">
                        <option value="WAITING_FOR_ANSWER"><?php _e('Awaiting Response', 'api-isarud'); ?></option>
                        <option value="ANSWERED"><?php _e('Answered', 'api-isarud'); ?></option>
                        <option value="REPORTED"><?php _e('Reported', 'api-isarud'); ?></option>
                        <option value="REJECTED"><?php _e('Rejected', 'api-isarud'); ?></option>
                    </select>
                    <button class="button" id="isarud-tr-questions-load"><?php _e('Refresh', 'api-isarud'); ?></button>
                </div>
            </div>
            <div id="isarud-tr-questions-area" class="isarud-tr-data-area">
                <p class="isarud-tr-empty"><?php _e('Click the Refresh button.', 'api-isarud'); ?></p>
            </div>
        </div>

        <!-- TAB 7: INVOICE -->
        <div class="isarud-tr-tab-content" data-tab-content="invoice" style="display:none;">
            <h3><?php _e('Invoice Link Sending', 'api-isarud'); ?></h3>
            <div class="isarud-tr-card isarud-tr-card-purple">
                <h4><?php _e('Send Invoice for Single Order', 'api-isarud'); ?></h4>
                <p><?php _e('Submit the e-invoice PDF link that the customer can view to Trendyol. The link must be HTTPS and publicly accessible.', 'api-isarud'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="isarud-tr-inv-package"><?php _e('Order Package ID', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-tr-inv-package" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-tr-inv-number"><?php _e('Invoice Number', 'api-isarud'); ?></label></th>
                        <td><input type="text" id="isarud-tr-inv-number" class="regular-text" placeholder="ABC2026000000001"></td>
                    </tr>
                    <tr>
                        <th><label for="isarud-tr-inv-link"><?php _e('Invoice PDF Link', 'api-isarud'); ?></label></th>
                        <td><input type="url" id="isarud-tr-inv-link" class="regular-text" style="width:100%;" placeholder="https://example.com/invoice.pdf"></td>
                    </tr>
                </table>
                <button class="button button-primary" id="isarud-tr-inv-send"><?php _e('Send Invoice', 'api-isarud'); ?></button>
                <div id="isarud-tr-inv-result" style="margin-top:10px;"></div>
            </div>
        </div>

        <!-- TAB 8: WEBHOOKS -->
        <div class="isarud-tr-tab-content" data-tab-content="webhooks" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                <h3><?php _e('Webhook Management', 'api-isarud'); ?></h3>
                <button class="button button-primary" id="isarud-tr-webhook-toggle-form">+ <?php _e('New Webhook', 'api-isarud'); ?></button>
            </div>

            <div id="isarud-tr-webhook-form" class="isarud-tr-card" style="display:none;">
                <h4><?php _e('Create New Webhook', 'api-isarud'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><label for="isarud-tr-wh-url"><?php _e('Webhook URL (HTTPS)', 'api-isarud'); ?></label></th>
                        <td><input type="url" id="isarud-tr-wh-url" class="regular-text" style="width:100%;" placeholder="https://example.com/webhook"></td>
                    </tr>
                    <tr>
                        <th><?php _e('Event Types', 'api-isarud'); ?></th>
                        <td>
                            <label><input type="checkbox" class="isarud-tr-wh-event" value="PackageStatusChanged"> PackageStatusChanged</label><br>
                            <label><input type="checkbox" class="isarud-tr-wh-event" value="OrderCreated"> OrderCreated</label><br>
                            <label><input type="checkbox" class="isarud-tr-wh-event" value="ClaimCreated"> ClaimCreated</label><br>
                            <label><input type="checkbox" class="isarud-tr-wh-event" value="ProductPriceChanged"> ProductPriceChanged</label>
                        </td>
                    </tr>
                </table>
                <button class="button button-primary" id="isarud-tr-wh-create"><?php _e('Create', 'api-isarud'); ?></button>
                <button class="button" id="isarud-tr-wh-cancel"><?php _e('Cancel', 'api-isarud'); ?></button>
            </div>

            <div id="isarud-tr-webhooks-area" class="isarud-tr-data-area" style="margin-top:15px;">
                <p class="isarud-tr-empty"><?php _e('Loading...', 'api-isarud'); ?></p>
            </div>
        </div>

    </div><!-- /isarud-tr-app -->
    <?php endif; ?>
</div>

<style>
/* Layout */
.isarud-tr-loading { padding:40px;text-align:center;color:#666; }
.isarud-tr-not-connected, .isarud-tr-empty { padding:40px;text-align:center;color:#666;background:#fff;border:1px solid #ddd;border-radius:6px; }
.isarud-tr-info-card { background:#fff;padding:12px 16px;border:1px solid #ddd;border-radius:6px;display:flex;justify-content:space-between;align-items:center;margin:15px 0; }
.isarud-tr-tabs { margin-top:15px; }
.isarud-tr-tab-content { padding:15px;background:#fff;border:1px solid #ddd;border-top:none;min-height:300px; }
.isarud-tr-data-area { background:#fafafa;border:1px solid #e2e2e2;border-radius:4px;padding:10px;min-height:200px; }
.isarud-tr-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:15px 0; }
.isarud-tr-card { background:#f9f9f9;padding:15px;border:1px solid #e2e2e2;border-radius:6px; }
.isarud-tr-card-blue { background:linear-gradient(135deg,#eff6ff,#dbeafe);border-color:#bfdbfe; }
.isarud-tr-card-purple { background:linear-gradient(135deg,#faf5ff,#f3e8ff);border-color:#e9d5ff; }

/* Badges */
.isarud-tr-badge { display:inline-block;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600; }
.isarud-tr-badge-green { background:#d4edda;color:#155724; }
.isarud-tr-badge-red { background:#f8d7da;color:#721c24; }
.isarud-tr-badge-gray { background:#e2e3e5;color:#383d41; }
.isarud-tr-badge-amber { background:#fff3cd;color:#856404; }
.isarud-tr-badge-blue { background:#d1ecf1;color:#0c5460; }
.isarud-tr-badge-purple { background:#e9d8fd;color:#553c9a; }

/* Tables */
.isarud-tr-table { width:100%;border-collapse:collapse;background:#fff; }
.isarud-tr-table th { background:#f1f1f1;padding:8px;text-align:left;font-size:12px;text-transform:uppercase;color:#666;border-bottom:1px solid #ddd; }
.isarud-tr-table td { padding:8px;border-bottom:1px solid #eee;font-size:13px; }
.isarud-tr-table tr:hover { background:#f9f9f9; }

/* Cards/Items */
.isarud-tr-item { background:#fff;padding:10px 15px;border:1px solid #ddd;border-radius:4px;margin-bottom:8px; }
.isarud-tr-item-flex { display:flex;justify-content:space-between;align-items:center; }
</style>

<script type="text/javascript">
jQuery(function($){
    var STATUS_LOADED = false;

    // ═══════ TAB SWITCHING ═══════
    $('.isarud-tr-tabs .nav-tab').on('click', function(e){
        e.preventDefault();
        var tab = $(this).data('tab');
        $('.isarud-tr-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.isarud-tr-tab-content').hide();
        $('[data-tab-content="' + tab + '"]').show();

        // Lazy load on tab open
        if (tab === 'webhooks' && !$('#isarud-tr-webhooks-area').data('loaded')) {
            loadWebhooks();
        }
    });

    // ═══════ STATUS / CONNECTION ═══════
    function loadStatus() {
        $('#isarud-tr-loading').show();
        $('#isarud-tr-not-connected,#isarud-tr-app').hide();

        $.post(ajaxurl, {
            action: 'isarud_trendyol_status',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>'
        }, function(r){
            $('#isarud-tr-loading').hide();
            if (r && r.success && r.connected) {
                $('#isarud-tr-app').show();
                $('#isarud-tr-store-name').text(r.store?.name || '—');
                $('#isarud-tr-seller-id').text(r.seller_id || '—');
                $('#isarud-tr-status-badge').removeClass().addClass('isarud-tr-badge isarud-tr-badge-green').text('✓ <?php echo esc_js(__('Connected', 'api-isarud')); ?>');
                if (r.use_stage) $('#isarud-tr-stage-badge').show();
                STATUS_LOADED = true;
                loadListings(); // İlk tab açılışta listings yükle
            } else {
                $('#isarud-tr-not-connected').show();
                $('#isarud-tr-status-badge').removeClass().addClass('isarud-tr-badge isarud-tr-badge-red').text('<?php echo esc_js(__('Not Connected', 'api-isarud')); ?>');
            }
        }).fail(function(){
            $('#isarud-tr-loading').hide();
            $('#isarud-tr-status-badge').text('<?php echo esc_js(__('Error', 'api-isarud')); ?>').addClass('isarud-tr-badge-red');
        });
    }

    $('#isarud-tr-refresh-status').on('click', loadStatus);

    // ═══════ TAB 1: LISTINGS ═══════
    function loadListings() {
        var area = $('#isarud-tr-listings-area');
        area.html('<p><?php echo esc_js(__('Loading...', 'api-isarud')); ?></p>');

        $.post(ajaxurl, {
            action: 'isarud_trendyol_listings',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            page: 0,
            size: 50,
            filter: $('#isarud-tr-listings-filter').val()
        }, function(r){
            if (!r || !r.success) {
                area.html('<p style="color:#d32f2f">' + (r?.message || 'Hata') + '</p>');
                return;
            }
            if (!r.items || r.items.length === 0) {
                area.html('<p class="isarud-tr-empty"><?php echo esc_js(__('No products found.','api-isarud')); ?></p>');
                return;
            }
            var html = '<table class="isarud-tr-table"><thead><tr>';
            html += '<th><?php echo esc_js(__('Product','api-isarud')); ?></th><th><?php echo esc_js(__('Barcode','api-isarud')); ?></th><th><?php echo esc_js(__('Price','api-isarud')); ?></th><th><?php echo esc_js(__('Stock','api-isarud')); ?></th><th><?php echo esc_js(__('Status','api-isarud')); ?></th><th></th>';
            html += '</tr></thead><tbody>';
            r.items.forEach(function(item){
                var status = item.onSale ? '<span class="isarud-tr-badge isarud-tr-badge-green">Aktif</span>' :
                                          '<span class="isarud-tr-badge isarud-tr-badge-gray">Pasif</span>';
                html += '<tr>';
                html += '<td><strong>' + (item.title || '—') + '</strong><br><small>' + (item.brand || '') + '</small></td>';
                html += '<td><code>' + (item.barcode || '—') + '</code></td>';
                html += '<td>' + (item.salePrice ? item.salePrice.toFixed(2) + ' TL' : '—') + '</td>';
                html += '<td>' + (item.quantity || 0) + '</td>';
                html += '<td>' + status + '</td>';
                html += '<td>';
                if (item.onSale) {
                    html += '<button class="button button-small isarud-tr-toggle" data-action="deactivate" data-barcode="' + item.barcode + '"><?php echo esc_js(__('Deactivate','api-isarud')); ?></button> ';
                } else {
                    html += '<button class="button button-small isarud-tr-toggle" data-action="activate" data-barcode="' + item.barcode + '"><?php echo esc_js(__('Activate','api-isarud')); ?></button> ';
                }
                html += '<button class="button button-small button-link-delete isarud-tr-delete" data-barcode="' + item.barcode + '">Sil</button>';
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '<p style="color:#666;margin-top:10px;"><?php echo esc_js(__('Total','api-isarud')); ?>: ' + (r.totalElements || r.items.length) + ' <?php echo esc_js(__('products','api-isarud')); ?> · <?php echo esc_js(__('Page','api-isarud')); ?>: ' + ((r.page || 0) + 1) + ' / ' + (r.totalPages || 1) + '</p>';
            area.html(html);
        });
    }
    $('#isarud-tr-listings-load').on('click', loadListings);
    $('#isarud-tr-listings-filter').on('change', loadListings);

    $(document).on('click', '.isarud-tr-toggle', function(){
        var action = $(this).data('action');
        var barcode = $(this).data('barcode');
        if (!confirm('<?php echo esc_js(__('Change product status?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_trendyol_listing_' + action,
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            barcode: barcode
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadListings();
        });
    });

    $(document).on('click', '.isarud-tr-delete', function(){
        var barcode = $(this).data('barcode');
        if (!confirm('<?php echo esc_js(__('Delete this product from Trendyol? This cannot be undone.','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_trendyol_listing_delete',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            barcode: barcode
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadListings();
        });
    });

    // ═══════ TAB 2: BRANDS & CATEGORIES ═══════
    var brandSearchTimer;
    $('#isarud-tr-brand-query').on('input', function(){
        clearTimeout(brandSearchTimer);
        var q = $(this).val();
        var area = $('#isarud-tr-brands-area');
        if (q.length < 2) { area.empty(); return; }
        brandSearchTimer = setTimeout(function(){
            area.html('<p><?php echo esc_js(__('Searching...','api-isarud')); ?></p>');
            $.post(ajaxurl, {
                action: 'isarud_trendyol_brands',
                nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
                query: q
            }, function(r){
                if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
                if (!r.brands || r.brands.length === 0) { area.html('<p><?php echo esc_js(__('No results.','api-isarud')); ?></p>'); return; }
                var html = '<ul style="list-style:none;padding:0;margin:0;">';
                r.brands.forEach(function(b){
                    html += '<li class="isarud-tr-item isarud-tr-item-flex"><span>' + b.name + '</span><code>#' + b.id + '</code></li>';
                });
                html += '</ul>';
                area.html(html);
            });
        }, 300);
    });

    $('#isarud-tr-categories-load').on('click', function(){
        var area = $('#isarud-tr-categories-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_trendyol_categories',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>'
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.categories || r.categories.length === 0) { area.html('<p>Kategori yok.</p>'); return; }
            var html = '';
            r.categories.forEach(function(c){
                html += '<div style="padding:6px 0;border-bottom:1px solid #eee;">';
                html += '<strong>' + c.name + ' <code>#' + c.id + '</code></strong>';
                if (c.subCategories && c.subCategories.length) {
                    c.subCategories.slice(0, 5).forEach(function(s){
                        html += '<div style="margin-left:20px;color:#666;font-size:12px;">└ ' + s.name + ' #' + s.id + '</div>';
                    });
                    if (c.subCategories.length > 5) html += '<div style="margin-left:20px;color:#999;font-size:11px;">+ ' + (c.subCategories.length - 5) + ' alt kategori daha...</div>';
                }
                html += '</div>';
            });
            area.html(html);
        });
    });

    // ═══════ TAB 3: STOCK & PRICE (single sync) ═══════
    $('#isarud-tr-single-sync').on('click', function(){
        var data = {
            action: 'isarud_trendyol_sync_single',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            barcode: $('#isarud-tr-single-barcode').val(),
            quantity: $('#isarud-tr-single-quantity').val(),
            salePrice: $('#isarud-tr-single-price').val()
        };
        var listP = $('#isarud-tr-single-list').val();
        if (listP) data.listPrice = listP;
        if (!data.barcode || !data.salePrice) {
            alert('Barkod ve fiyat zorunlu.');
            return;
        }
        $('#isarud-tr-single-result').html('<p><?php echo esc_js(__('Sending...','api-isarud')); ?></p>');
        $.post(ajaxurl, data, function(r){
            $('#isarud-tr-single-result').html('<div class="notice notice-' + (r?.success ? 'success' : 'error') + '"><p>' + (r?.message || 'OK') + '</p></div>');
        });
    });

    // ═══════ TAB 4: ORDERS ═══════
    function loadOrders() {
        var area = $('#isarud-tr-orders-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_trendyol_orders',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            status: $('#isarud-tr-orders-status').val(),
            days: $('#isarud-tr-orders-days').val()
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.items || r.items.length === 0) { area.html('<p class="isarud-tr-empty"><?php echo esc_js(__('No orders found.','api-isarud')); ?></p>'); return; }
            var html = '<table class="isarud-tr-table"><thead><tr>';
            html += '<th>#</th><th><?php echo esc_js(__('Customer','api-isarud')); ?></th><th><?php echo esc_js(__('Amount','api-isarud')); ?></th><th><?php echo esc_js(__('Status','api-isarud')); ?></th><th><?php echo esc_js(__('Shipping','api-isarud')); ?></th><th></th>';
            html += '</tr></thead><tbody>';
            r.items.forEach(function(o){
                var name = (o.shipmentAddress?.firstName || '') + ' ' + (o.shipmentAddress?.lastName || '');
                html += '<tr>';
                html += '<td><code>' + (o.orderNumber || '—') + '</code></td>';
                html += '<td>' + (name.trim() || '—') + '</td>';
                html += '<td>' + (o.grossAmount ? o.grossAmount.toFixed(2) + ' TL' : '—') + '</td>';
                html += '<td><span class="isarud-tr-badge isarud-tr-badge-blue">' + (o.status || '—') + '</span></td>';
                html += '<td><small>' + (o.cargoProviderName || '—') + '<br>' + (o.cargoTrackingNumber || '') + '</small></td>';
                html += '<td>';
                html += '<button class="button button-small isarud-tr-pkg-status" data-package="' + o.id + '" data-status="Picking">→ Picking</button> ';
                html += '<button class="button button-small isarud-tr-pkg-status" data-package="' + o.id + '" data-status="Shipped">→ Shipped</button>';
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            area.html(html);
        });
    }
    $('#isarud-tr-orders-load').on('click', loadOrders);

    $(document).on('click', '.isarud-tr-pkg-status', function(){
        var pkg = $(this).data('package');
        var st = $(this).data('status');
        if (!confirm('<?php echo esc_js(__('Update package status?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_trendyol_package_status',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            package_id: pkg,
            status: st
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadOrders();
        });
    });

    // ═══════ TAB 5: CLAIMS ═══════
    function loadClaims() {
        var area = $('#isarud-tr-claims-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_trendyol_claims',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>'
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.items || r.items.length === 0) { area.html('<p class="isarud-tr-empty"><?php echo esc_js(__('No returns found.','api-isarud')); ?></p>'); return; }
            var html = '';
            r.items.forEach(function(c){
                html += '<div class="isarud-tr-item">';
                html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;">';
                html += '<div><strong>#' + c.id + '</strong> <small>' + (c.orderNumber || '') + '</small></div>';
                html += '<span class="isarud-tr-badge isarud-tr-badge-amber">' + (c.claimItemsStatus || c.status || '—') + '</span>';
                html += '</div>';
                html += '<div style="margin:8px 0;">' + (c.customerName || '—') + '</div>';
                html += '<button class="button button-primary button-small isarud-tr-claim-approve" data-claim="' + c.id + '" data-lines="' + ((c.items || []).map(function(i){return i.id;}).join(',')) + '">Onayla</button>';
                html += '</div>';
            });
            area.html(html);
        });
    }
    $('#isarud-tr-claims-load').on('click', loadClaims);

    $(document).on('click', '.isarud-tr-claim-approve', function(){
        var cid = $(this).data('claim');
        var lines = String($(this).data('lines') || '').split(',').filter(Boolean);
        if (lines.length === 0) { alert('Onaylanacak kalem yok'); return; }
        if (!confirm('<?php echo esc_js(__('Approve this return?','api-isarud')); ?>')) return;
        $.post(ajaxurl, {
            action: 'isarud_trendyol_claim_approve',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            claim_id: cid,
            line_ids: lines
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadClaims();
        });
    });

    // ═══════ TAB 6: QUESTIONS ═══════
    function loadQuestions() {
        var area = $('#isarud-tr-questions-area');
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_trendyol_questions',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            status: $('#isarud-tr-questions-status').val()
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.items || r.items.length === 0) { area.html('<p class="isarud-tr-empty"><?php echo esc_js(__('No questions found.','api-isarud')); ?></p>'); return; }
            var html = '';
            r.items.forEach(function(q){
                html += '<div class="isarud-tr-item">';
                html += '<div style="font-size:11px;color:#999;">@' + (q.userName || '—') + '</div>';
                html += '<div style="margin:8px 0;">' + (q.text || '') + '</div>';
                if (q.answer) {
                    html += '<div style="background:#e3f2fd;padding:8px;border-radius:4px;border-left:3px solid #2196f3;font-size:13px;">';
                    html += '<strong>Cevap:</strong> ' + q.answer.text;
                    html += '</div>';
                } else {
                    html += '<div style="margin-top:8px;">';
                    html += '<textarea class="isarud-tr-q-answer" data-qid="' + q.id + '" rows="2" style="width:100%;" placeholder="<?php echo esc_js(__('Write your answer (min 10 characters)...','api-isarud')); ?>"></textarea>';
                    html += '<button class="button button-small button-primary isarud-tr-q-send" data-qid="' + q.id + '" style="margin-top:5px;"><?php echo esc_js(__('Send Answer','api-isarud')); ?></button>';
                    html += '</div>';
                }
                html += '</div>';
            });
            area.html(html);
        });
    }
    $('#isarud-tr-questions-load').on('click', loadQuestions);

    $(document).on('click', '.isarud-tr-q-send', function(){
        var qid = $(this).data('qid');
        var text = $('.isarud-tr-q-answer[data-qid="' + qid + '"]').val();
        if (!text || text.length < 10) { alert('<?php echo esc_js(__('Answer must be at least 10 characters.','api-isarud')); ?>'); return; }
        $.post(ajaxurl, {
            action: 'isarud_trendyol_question_answer',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            question_id: qid,
            text: text
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) loadQuestions();
        });
    });

    // ═══════ TAB 7: INVOICE ═══════
    $('#isarud-tr-inv-send').on('click', function(){
        var data = {
            action: 'isarud_trendyol_invoice_send',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            shipment_package_id: $('#isarud-tr-inv-package').val(),
            invoice_number: $('#isarud-tr-inv-number').val(),
            invoice_link: $('#isarud-tr-inv-link').val()
        };
        if (!data.shipment_package_id || !data.invoice_number || !data.invoice_link) {
            alert('<?php echo esc_js(__('Please fill in all fields.','api-isarud')); ?>');
            return;
        }
        $('#isarud-tr-inv-result').html('<p><?php echo esc_js(__('Sending...','api-isarud')); ?></p>');
        $.post(ajaxurl, data, function(r){
            $('#isarud-tr-inv-result').html('<div class="notice notice-' + (r?.success ? 'success' : 'error') + '"><p>' + (r?.message || 'OK') + '</p></div>');
        });
    });

    // ═══════ TAB 8: WEBHOOKS ═══════
    function loadWebhooks() {
        var area = $('#isarud-tr-webhooks-area');
        area.data('loaded', true);
        area.html('<p><?php echo esc_js(__('Loading...','api-isarud')); ?></p>');
        $.post(ajaxurl, {
            action: 'isarud_trendyol_webhooks_list',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>'
        }, function(r){
            if (!r?.success) { area.html('<p>' + (r?.message || 'Hata') + '</p>'); return; }
            if (!r.webhooks || r.webhooks.length === 0) { area.html('<p class="isarud-tr-empty"><?php echo esc_js(__('No webhooks defined.','api-isarud')); ?></p>'); return; }
            var html = '';
            r.webhooks.forEach(function(w){
                html += '<div class="isarud-tr-item isarud-tr-item-flex">';
                html += '<div style="flex:1;min-width:0;">';
                html += '<code style="font-size:11px;display:block;word-break:break-all;">' + w.url + '</code>';
                html += '<small>' + (w.subscribedStatuses || []).join(', ') + '</small>';
                html += '</div>';
                html += '<div>';
                html += '<span class="isarud-tr-badge isarud-tr-badge-' + (w.status === 'ACTIVE' ? 'green' : 'gray') + '">' + w.status + '</span> ';
                html += '<button class="button button-small isarud-tr-wh-toggle" data-id="' + w.id + '" data-active="' + (w.status === 'ACTIVE') + '">' + (w.status === 'ACTIVE' ? '<?php echo esc_js(__('Deactivate','api-isarud')); ?>' : '<?php echo esc_js(__('Activate','api-isarud')); ?>') + '</button> ';
                html += '<button class="button button-small button-link-delete isarud-tr-wh-delete" data-id="' + w.id + '">Sil</button>';
                html += '</div></div>';
            });
            area.html(html);
        });
    }

    $('#isarud-tr-webhook-toggle-form').on('click', function(){
        $('#isarud-tr-webhook-form').toggle();
    });
    $('#isarud-tr-wh-cancel').on('click', function(){
        $('#isarud-tr-webhook-form').hide();
    });

    $('#isarud-tr-wh-create').on('click', function(){
        var url = $('#isarud-tr-wh-url').val();
        var events = [];
        $('.isarud-tr-wh-event:checked').each(function(){ events.push($(this).val()); });
        if (!url || events.length === 0) { alert('<?php echo esc_js(__('You must select a URL and at least 1 event type.','api-isarud')); ?>'); return; }
        $.post(ajaxurl, {
            action: 'isarud_trendyol_webhook_create',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            url: url,
            event_types: events
        }, function(r){
            alert(r?.message || (r?.success ? 'OK' : 'Hata'));
            if (r?.success) {
                $('#isarud-tr-webhook-form').hide();
                $('#isarud-tr-wh-url').val('');
                $('.isarud-tr-wh-event').prop('checked', false);
                loadWebhooks();
            }
        });
    });

    $(document).on('click', '.isarud-tr-wh-toggle', function(){
        var id = $(this).data('id');
        var currentActive = $(this).data('active') === true || $(this).data('active') === 'true';
        $.post(ajaxurl, {
            action: 'isarud_trendyol_webhook_update',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            webhook_id: id,
            active: currentActive ? '0' : '1'
        }, function(r){
            if (r?.success) loadWebhooks();
            else alert(r?.message || 'Hata');
        });
    });

    $(document).on('click', '.isarud-tr-wh-delete', function(){
        var id = $(this).data('id');
        if (!confirm('Webhook silinsin mi?')) return;
        $.post(ajaxurl, {
            action: 'isarud_trendyol_webhook_delete',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            webhook_id: id
        }, function(r){
            if (r?.success) loadWebhooks();
            else alert(r?.message || 'Hata');
        });
    });

    // ═══════ CONNECT FLOW (Etsy paritesi) ═══════
    $('#isarud-tr-connect-btn').on('click', function(){
        $('#isarud-tr-connect-modal').css('display','flex');
        $('#isarud-tr-stores-list').html('<p style="text-align:center;color:#999;"><?php echo esc_js(__('Loading your stores...', 'api-isarud')); ?></p>');

        $.post(ajaxurl, {
            action: 'isarud_trendyol_stores',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>'
        }, function(r){
            if (!r || !r.success || !r.stores || r.stores.length === 0) {
                $('#isarud-tr-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__('Store not found or could not be loaded.', 'api-isarud')); ?></p>');
                return;
            }
            var html = '';
            r.stores.forEach(function(s){
                html += '<div class="isarud-tr-store-item" data-store="' + s.id + '" style="padding:12px 16px;border:1px solid #ddd;border-radius:8px;margin-bottom:8px;cursor:pointer;background:#fff;transition:all 0.15s;">';
                html += '<strong style="font-size:15px;color:#1f2937;">' + (s.name || '<?php echo esc_js(__('Store','api-isarud')); ?> #' + s.id) + '</strong>';
                if (s.slug) html += '<br><small style="color:#999;">' + s.slug + '</small>';
                html += '</div>';
            });
            $('#isarud-tr-stores-list').html(html);
        }).fail(function(){
            $('#isarud-tr-stores-list').html('<p style="color:#d32f2f;"><?php echo esc_js(__('Request failed.', 'api-isarud')); ?></p>');
        });
    });

    $('#isarud-tr-modal-cancel').on('click', function(){
        $('#isarud-tr-connect-modal').hide();
    });

    $(document).on('click', '.isarud-tr-store-item', function(){
        var storeId = $(this).data('store');
        var $item = $(this);
        $('.isarud-tr-store-item').css({background:'#fff', borderColor:'#ddd'});
        $item.css({background:'#fef3e8', borderColor:'#f27a1a'});

        // Connect URL al ve yeni tab'da aç
        $.post(ajaxurl, {
            action: 'isarud_trendyol_connect_url',
            nonce: '<?php echo wp_create_nonce("isarud_trendyol_nonce"); ?>',
            store_id: storeId
        }, function(r){
            if (r && r.success && r.connect_url) {
                $('#isarud-tr-connect-modal').hide();
                window.open(r.connect_url, '_blank');
            } else {
                alert((r && r.message) || '<?php echo esc_js(__('Connection URL could not be retrieved.', 'api-isarud')); ?>');
            }
        }).fail(function(){
            alert('<?php echo esc_js(__('Request failed.', 'api-isarud')); ?>');
        });
    });

    $(document).on('mouseenter', '.isarud-tr-store-item', function(){
        if (!$(this).is(':hover')) return;
        $(this).css({background:'#f9fafb'});
    }).on('mouseleave', '.isarud-tr-store-item', function(){
        $(this).css({background:'#fff'});
    });

    // ═══════ AUTO-EXPORT TOGGLE ═══════
    $('#isarud-tr-auto-export-toggle').on('change', function(){
        var enabled = $(this).is(':checked');
        $.post(ajaxurl, {
            action: 'isarud_toggle_auto_export',
            nonce: '<?php echo wp_create_nonce("isarud_nonce"); ?>',
            enabled: enabled ? '1' : '0'
        }, function(r){
            if (r && r.success) {
                $('#isarud-tr-auto-export-status').html(enabled ? '✅ '.__('On','api-isarud') : '⚪ '.__('Off','api-isarud'));
            } else {
                alert(r?.message || 'Hata');
                $('#isarud-tr-auto-export-toggle').prop('checked', !enabled); // revert
            }
        });
    });

    // ═══════ BULK EXPORT (WC → Trendyol) ═══════
    $('#isarud-tr-bulk-export-btn').on('click', function(){
        if (!confirm('<?php echo esc_js(__('All WooCommerce products will be sent to Trendyol. Continue?', 'api-isarud')); ?>')) return;
        
        var $btn = $(this);
        var $result = $('#isarud-tr-bulk-export-result');
        $btn.prop('disabled', true).text('🔄 <?php echo esc_js(__('Sending...','api-isarud')); ?>');
        $result.html('<p style="color:#666;">⏳ <?php echo esc_js(__('Products are being transferred, please wait...','api-isarud')); ?></p>');
        
        $.post(ajaxurl, {
            action: 'isarud_export_products',
            nonce: '<?php echo wp_create_nonce("isarud_nonce"); ?>',
            marketplace: 'trendyol'
        }, function(r){
            $btn.prop('disabled', false).html('🚀 <?php echo esc_js(__('Send All Products to Trendyol', 'api-isarud')); ?>');
            if (r && r.success) {
                $result.html('<div class="notice notice-success" style="margin:0;padding:10px;"><p><strong>✅ <?php echo esc_js(__('Transfer complete!','api-isarud')); ?></strong> ' + (r.summary || '') + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error" style="margin:0;padding:10px;"><p><strong>❌ Hata:</strong> ' + (r?.message || 'Bilinmeyen hata') + '</p></div>');
            }
        }).fail(function(){
            $btn.prop('disabled', false).html('🚀 <?php echo esc_js(__('Send All Products to Trendyol', 'api-isarud')); ?>');
            $result.html('<div class="notice notice-error" style="margin:0;padding:10px;"><p><strong>❌ <?php echo esc_js(__('Request failed.','api-isarud')); ?></strong></p></div>');
        });
    });

    // Init
    <?php if (!empty($cloud_key)): ?>
    loadStatus();
    <?php endif; ?>
});
</script>