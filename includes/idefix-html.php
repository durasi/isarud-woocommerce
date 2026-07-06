<?php
/**
 * idefix-html.php — İdefix tanıtım + bağlantı sayfası (v7.0).
 * Tam yönetim UI (CS/Etsy tarzı sekmeler) v7.1'de — sunucu İdefix API bloğu yayınlanınca.
 */
if (!defined('ABSPATH')) exit;

function isarud_idefix_page_render() {
    $connected = !empty(get_option('isarud_cloud_api_key', ''));
    ?>
    <div class="wrap" style="max-width:920px">
        <div style="border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;margin-top:16px">
            <div style="background:linear-gradient(135deg,#EA580C 0%,#F59E0B 100%);padding:28px 30px;color:#fff">
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <div style="width:52px;height:52px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center">
                        <span style="font-weight:800;font-size:15px;color:#EA580C;letter-spacing:-0.5px">idefix</span>
                    </div>
                    <div>
                        <h1 style="margin:0;color:#fff;font-size:24px">İdefix</h1>
                        <p style="margin:4px 0 0;opacity:0.9;font-size:13px"><?php esc_html_e('D&R family marketplace — books, music, electronics and more', 'api-isarud'); ?></p>
                    </div>
                </div>
            </div>
            <div style="padding:24px 30px">
                <p style="font-size:14px;color:#374151;line-height:1.7">
                    <?php esc_html_e('Connect your İdefix seller account through your isarud.com panel: product listing with barcode matching, stock & price sync and order import are managed centrally on isarud.com.', 'api-isarud'); ?>
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;font-size:11px;margin:14px 0 20px">
                    <span style="padding:4px 10px;background:#FFEDD5;color:#9A3412;border-radius:6px;font-weight:600">📦 <?php esc_html_e('Inventory', 'api-isarud'); ?></span>
                    <span style="padding:4px 10px;background:#FFEDD5;color:#9A3412;border-radius:6px;font-weight:600">💰 <?php esc_html_e('Price', 'api-isarud'); ?></span>
                    <span style="padding:4px 10px;background:#FFEDD5;color:#9A3412;border-radius:6px;font-weight:600">📋 <?php esc_html_e('Order', 'api-isarud'); ?></span>
                    <span style="padding:4px 10px;background:#FFEDD5;color:#9A3412;border-radius:6px;font-weight:600">🏷️ <?php esc_html_e('Barcode matching', 'api-isarud'); ?></span>
                </div>
                <?php if (!$connected) : ?>
                <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400E;margin-bottom:16px">
                    ⚠️ <?php esc_html_e('First connect this site with Cloud Sync, then link your İdefix account on isarud.com.', 'api-isarud'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=isarud-cloud')); ?>"><?php esc_html_e('Cloud Sync Settings', 'api-isarud'); ?> →</a>
                </div>
                <?php endif; ?>
                <p>
                    <a href="https://isarud.com/marketplaces" target="_blank" rel="noopener" class="button button-primary button-hero" style="background:#EA580C;border-color:#EA580C;text-shadow:none;font-weight:600">
                        🔗 <?php esc_html_e('Connect on isarud.com', 'api-isarud'); ?>
                    </a>
                    <a href="https://isarud.com/marketplaces/idefix" target="_blank" rel="noopener" class="button" style="margin-left:8px">
                        <?php esc_html_e('Learn more', 'api-isarud'); ?> →
                    </a>
                </p>
                <p style="font-size:12px;color:#6B7280;margin-top:18px">
                    ℹ️ <?php esc_html_e('In-WordPress İdefix management tabs (listings, orders, returns) arrive in v7.1.', 'api-isarud'); ?>
                </p>
            </div>
        </div>
    </div>
    <?php
}
