<?php
if (!defined('ABSPATH')) exit;
$em = Isarud_Email_Marketing::instance();
$settings = $em->get_settings();

if (isset($_POST['isarud_save_email_marketing']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_email_marketing')) {
    $new = [
        'enabled' => !empty($_POST['enabled']),
        'from_name' => sanitize_text_field($_POST['from_name'] ?? get_bloginfo('name')),
        'from_email' => sanitize_email($_POST['from_email'] ?? get_option('admin_email')),
        'welcome_enabled' => !empty($_POST['welcome_enabled']),
        'welcome_subject' => sanitize_text_field($_POST['welcome_subject'] ?? ''),
        'welcome_coupon' => sanitize_text_field($_POST['welcome_coupon'] ?? ''),
        'welcome_delay' => intval($_POST['welcome_delay'] ?? 0),
        'post_purchase_enabled' => !empty($_POST['post_purchase_enabled']),
        'post_purchase_subject' => sanitize_text_field($_POST['post_purchase_subject'] ?? ''),
        'post_purchase_delay' => intval($_POST['post_purchase_delay'] ?? 3),
        'review_request_enabled' => !empty($_POST['review_request_enabled']),
        'review_request_subject' => sanitize_text_field($_POST['review_request_subject'] ?? ''),
        'review_request_delay' => intval($_POST['review_request_delay'] ?? 7),
        'winback_enabled' => !empty($_POST['winback_enabled']),
        'winback_subject' => sanitize_text_field($_POST['winback_subject'] ?? ''),
        'winback_days' => intval($_POST['winback_days'] ?? 60),
        'winback_coupon' => sanitize_text_field($_POST['winback_coupon'] ?? ''),
    ];
    $em->save_settings($new);
    $settings = $new;
    echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'api-isarud') . '</p></div>';
}

$stats = $em->get_stats();
$log = $em->get_log(15);
$types = ['welcome' => __('Welcome', 'api-isarud'), 'post_purchase' => __('Post-Purchase', 'api-isarud'), 'review' => __('Review Request', 'api-isarud'), 'winback' => __('Win-Back', 'api-isarud')];
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div>
            <div class="isd-title"><?php _e('Email Marketing', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Retain your customers with automated email campaigns', 'api-isarud'); ?></div>
        </div>
    </div>

    <div class="isd-metrics" style="margin-bottom:20px">
        <div class="isd-metric"><div class="isd-metric-val blue"><?php echo $stats['total']; ?></div><div class="isd-metric-label"><?php _e('Total Sent', 'api-isarud'); ?></div></div>
        <div class="isd-metric"><div class="isd-metric-val green"><?php echo $stats['welcome']; ?></div><div class="isd-metric-label"><?php _e('Welcome', 'api-isarud'); ?></div></div>
        <div class="isd-metric"><div class="isd-metric-val blue"><?php echo $stats['post_purchase']; ?></div><div class="isd-metric-label"><?php _e('Purchase', 'api-isarud'); ?></div></div>
        <div class="isd-metric"><div class="isd-metric-val amber"><?php echo $stats['winback']; ?></div><div class="isd-metric-label"><?php _e('Win-Back', 'api-isarud'); ?></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="isd-activity">
            <h3><?php _e('Automation Settings', 'api-isarud'); ?></h3>
            <form method="post">
                <?php wp_nonce_field('isarud_email_marketing'); ?>
                <table class="form-table" style="margin:0;font-size:13px">
                    <tr><th style="padding:8px 0;width:150px"><?php _e('Active', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> <?php _e('Enable email marketing module', 'api-isarud'); ?></label></td></tr>
                    <tr><th style="padding:8px 0"><?php _e('Sender', 'api-isarud'); ?></th>
                        <td style="padding:8px 0"><input type="text" name="from_name" value="<?php echo esc_attr($settings['from_name']); ?>" style="width:48%"> <input type="email" name="from_email" value="<?php echo esc_attr($settings['from_email']); ?>" style="width:48%"></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('1. Welcome Email', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="welcome_enabled" value="1" <?php checked($settings['welcome_enabled']); ?>> <?php _e('Send a welcome email to newly registered users', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><input type="text" name="welcome_subject" value="<?php echo esc_attr($settings['welcome_subject']); ?>" style="width:100%" placeholder="<?php _e('Subject', 'api-isarud'); ?>"></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Coupon:', 'api-isarud'); ?> <input type="text" name="welcome_coupon" value="<?php echo esc_attr($settings['welcome_coupon']); ?>" style="width:120px" placeholder="HOSGELDIN10"> &nbsp; <?php _e('Delay:', 'api-isarud'); ?> <input type="number" name="welcome_delay" value="<?php echo esc_attr($settings['welcome_delay']); ?>" min="0" style="width:60px"> <?php _e('hours', 'api-isarud'); ?></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('2. Post-Purchase Recommendation', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="post_purchase_enabled" value="1" <?php checked($settings['post_purchase_enabled']); ?>> <?php _e('Send relevant product recommendations when order is completed', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><input type="text" name="post_purchase_subject" value="<?php echo esc_attr($settings['post_purchase_subject']); ?>" style="width:100%"></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Delay:', 'api-isarud'); ?> <input type="number" name="post_purchase_delay" value="<?php echo esc_attr($settings['post_purchase_delay']); ?>" min="1" style="width:60px"> <?php _e('day', 'api-isarud'); ?></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('3. Review Request', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="review_request_enabled" value="1" <?php checked($settings['review_request_enabled']); ?>> <?php _e('Request product review when order is completed', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><input type="text" name="review_request_subject" value="<?php echo esc_attr($settings['review_request_subject']); ?>" style="width:100%"></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Delay:', 'api-isarud'); ?> <input type="number" name="review_request_delay" value="<?php echo esc_attr($settings['review_request_delay']); ?>" min="1" style="width:60px"> <?php _e('day', 'api-isarud'); ?></td></tr>
                </table>

                <h4 style="margin:16px 0 8px;padding-top:12px;border-top:1px solid #eee"><?php _e('4. Win-Back', 'api-isarud'); ?></h4>
                <table style="width:100%;font-size:13px">
                    <tr><td style="padding:6px 0"><label><input type="checkbox" name="winback_enabled" value="1" <?php checked($settings['winback_enabled']); ?>> <?php _e('Send win-back email to customers who haven\'t ordered in a long time', 'api-isarud'); ?></label></td></tr>
                    <tr><td style="padding:6px 0"><input type="text" name="winback_subject" value="<?php echo esc_attr($settings['winback_subject']); ?>" style="width:100%"></td></tr>
                    <tr><td style="padding:6px 0"><?php _e('Inactive period:', 'api-isarud'); ?> <input type="number" name="winback_days" value="<?php echo esc_attr($settings['winback_days']); ?>" min="7" style="width:60px"> <?php _e('day', 'api-isarud'); ?> &nbsp; <?php _e('Coupon:', 'api-isarud'); ?> <input type="text" name="winback_coupon" value="<?php echo esc_attr($settings['winback_coupon']); ?>" style="width:120px" placeholder="GERIDÖN15"></td></tr>
                </table>

                <p style="margin-top:16px"><button type="submit" name="isarud_save_email_marketing" class="button-primary"><?php _e('Save Settings', 'api-isarud'); ?></button></p>
            </form>
        </div>

        <div>
            <div class="isd-activity">
                <h3><?php _e('Sending History', 'api-isarud'); ?></h3>
                <?php if (!empty($log)): ?>
                <div style="max-height:400px;overflow-y:auto">
                    <table class="wp-list-table widefat fixed striped" style="font-size:12px">
                        <thead><tr><th><?php _e('Type', 'api-isarud'); ?></th><th><?php _e('Email', 'api-isarud'); ?></th><th style="width:80px"><?php _e('Time', 'api-isarud'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($log as $entry): ?>
                        <tr>
                            <td><span style="font-size:11px;padding:1px 8px;border-radius:10px;background:#f0f6fc;color:#185fa5"><?php echo esc_html($types[$entry['type']] ?? $entry['type']); ?></span></td>
                            <td><?php echo esc_html($entry['email']); ?></td>
                            <td style="color:#888"><?php echo human_time_diff(strtotime($entry['sent_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="isd-empty"><?php _e('No email sent yet.', 'api-isarud'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
        <?php _e('How it works: Welcome email is sent instantly to newly registered users. Post-purchase recommendations and review requests are sent automatically a certain number of days after order completion. Win-back emails are checked and sent daily to customers who haven\'t placed an order within the specified number of days.', 'api-isarud'); ?>
    </div>
</div>
