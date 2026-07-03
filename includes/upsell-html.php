<?php
if (!defined('ABSPATH')) exit;
$ups = Isarud_Upsell::instance();
$settings = $ups->get_settings();
$rules = $ups->get_rules();

if (isset($_POST['isarud_save_upsell']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_upsell')) {
    $new = [
        'enabled' => !empty($_POST['enabled']),
        'fbt_enabled' => !empty($_POST['fbt_enabled']),
        'fbt_title' => sanitize_text_field($_POST['fbt_title'] ?? ''),
        'fbt_auto' => !empty($_POST['fbt_auto']),
        'fbt_limit' => intval($_POST['fbt_limit'] ?? 4),
        'fbt_discount' => floatval($_POST['fbt_discount'] ?? 0),
        'order_bump_enabled' => !empty($_POST['order_bump_enabled']),
        'order_bump_title' => sanitize_text_field($_POST['order_bump_title'] ?? ''),
        'cart_upsell_enabled' => !empty($_POST['cart_upsell_enabled']),
        'cart_upsell_title' => sanitize_text_field($_POST['cart_upsell_title'] ?? ''),
        'thankyou_enabled' => !empty($_POST['thankyou_enabled']),
        'thankyou_title' => sanitize_text_field($_POST['thankyou_title'] ?? ''),
        'thankyou_coupon' => sanitize_text_field($_POST['thankyou_coupon'] ?? ''),
    ];
    $ups->save_settings($new);
    $settings = $new;
    echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'api-isarud') . '</p></div>';
}
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div>
            <div class="isd-title"><?php _e('Cross-sell & Upsell', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Sales increase automation — product recommendation, order bump, cart suggestion', 'api-isarud'); ?></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="isd-activity">
            <h3><?php _e('Automation Settings', 'api-isarud'); ?></h3>
            <form method="post">
                <?php wp_nonce_field('isarud_upsell'); ?>
                <table class="form-table" style="margin:0;font-size:13px">
                    <tr><th style="padding:8px 0;width:160px"><?php _e('Active', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> <?php _e('Enable Cross-sell & Upsell module', 'api-isarud'); ?></label></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('1. Frequently Bought Together (FBT)', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="fbt_enabled" value="1" <?php checked($settings['fbt_enabled']); ?>> <?php _e('Show frequently bought together products on product page', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Title:', 'api-isarud'); ?> <input type="text" name="fbt_title" value="<?php echo esc_attr($settings['fbt_title']); ?>" style="width:60%"></td></tr>
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="fbt_auto" value="1" <?php checked($settings['fbt_auto']); ?>> <?php _e('Automatic recommendations from order history (if no manual selection)', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Max products:', 'api-isarud'); ?> <input type="number" name="fbt_limit" value="<?php echo esc_attr($settings['fbt_limit']); ?>" min="1" max="8" style="width:60px"></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('2. Order Bump (Checkout)', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="order_bump_enabled" value="1" <?php checked($settings['order_bump_enabled']); ?>> <?php _e('Show order bump on payment page', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Title:', 'api-isarud'); ?> <input type="text" name="order_bump_title" value="<?php echo esc_attr($settings['order_bump_title']); ?>" style="width:60%"></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('3. Cart Recommendation', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="cart_upsell_enabled" value="1" <?php checked($settings['cart_upsell_enabled']); ?>> <?php _e('Show related product recommendations on cart page', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Title:', 'api-isarud'); ?> <input type="text" name="cart_upsell_title" value="<?php echo esc_attr($settings['cart_upsell_title']); ?>" style="width:60%"></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('4. Post-Order Recommendation', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="thankyou_enabled" value="1" <?php checked($settings['thankyou_enabled']); ?>> <?php _e('Show product recommendations on thank you page', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Title:', 'api-isarud'); ?> <input type="text" name="thankyou_title" value="<?php echo esc_attr($settings['thankyou_title']); ?>" style="width:60%"></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Coupon:', 'api-isarud'); ?> <input type="text" name="thankyou_coupon" value="<?php echo esc_attr($settings['thankyou_coupon']); ?>" style="width:120px" placeholder="SONRAKI15"></td></tr>
                </table>

                <p style="margin-top:16px"><button type="submit" name="isarud_save_upsell" class="button-primary"><?php _e('Save Settings', 'api-isarud'); ?></button></p>
            </form>
        </div>

        <div>
            <div class="isd-activity">
                <h3><?php _e('Feature Descriptions', 'api-isarud'); ?></h3>
                <div style="font-size:12px;color:#666;line-height:1.8">
                    <div style="margin-bottom:8px"><strong><?php _e('Frequently Bought Together:', 'api-isarud'); ?></strong> <?php _e('Amazon-style "Customers who bought this also bought" block on the product page. Manual product selection or automatic based on order history.', 'api-isarud'); ?></div>
                    <div style="margin-bottom:8px"><strong><?php _e('Order Bump:', 'api-isarud'); ?></strong> <?php _e('One-click product recommendation that can be added before the payment button. Instantly added to cart with checkbox.', 'api-isarud'); ?></div>
                    <div style="margin-bottom:8px"><strong><?php _e('Cart Recommendation:', 'api-isarud'); ?></strong> <?php _e('Related products on cart page based on items in cart. Automatically selected from category.', 'api-isarud'); ?></div>
                    <div><strong><?php _e('Post-Order:', 'api-isarud'); ?></strong> <?php _e('Product recommendation for next order on thank you page + coupon code.', 'api-isarud'); ?></div>
                </div>
            </div>

            <div class="isd-activity" style="margin-top:16px">
                <h3><?php _e('Product-Based FBT Selection', 'api-isarud'); ?></h3>
                <p style="font-size:12px;color:#888"><?php _e('To select FBT products manually for each product: go to Edit Product > Linked Products. A "Frequently Bought Together" field has been added.', 'api-isarud'); ?></p>
            </div>
        </div>
    </div>

    <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
        <?php _e('How it works: Frequently Bought Together analyzes order history and automatically shows the most frequently purchased together products. Order Bump provides one-click product addition on the payment page. All recommendations are automatically selected from WooCommerce product categories.', 'api-isarud'); ?>
    </div>
</div>
