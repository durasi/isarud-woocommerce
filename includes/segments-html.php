<?php
if (!defined('ABSPATH')) exit;
$seg = Isarud_Segments::instance();
$settings = $seg->get_settings();

if (isset($_POST['isarud_save_segments']) && wp_verify_nonce($_POST['_wpnonce'], 'isarud_segments')) {
    $new = [
        'enabled' => true,
        'vip_threshold' => intval($_POST['vip_threshold'] ?? 5),
        'vip_amount' => floatval($_POST['vip_amount'] ?? 5000),
        'at_risk_days' => intval($_POST['at_risk_days'] ?? 90),
        'lost_days' => intval($_POST['lost_days'] ?? 180),
        'last_refresh' => $settings['last_refresh'],
    ];
    $seg->save_settings($new);
    $settings = $new;
    echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'api-isarud') . '</p></div>';
}

$data = $seg->analyze();
$segments = $data['segments'];
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="isd-title"><?php _e('Customer Segmentation', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Customer analysis based on purchase behavior', 'api-isarud'); ?></div>
        </div>
    </div>

    <div class="isd-metrics" style="margin-bottom:20px">
        <div class="isd-metric">
            <div class="isd-metric-val blue"><?php echo number_format_i18n($data['total_customers']); ?></div>
            <div class="isd-metric-label"><?php _e('Total Customers', 'api-isarud'); ?></div>
        </div>
        <div class="isd-metric">
            <div class="isd-metric-val green"><?php echo wc_price($data['total_revenue']); ?></div>
            <div class="isd-metric-label"><?php _e('Total Revenue', 'api-isarud'); ?></div>
        </div>
        <div class="isd-metric">
            <div class="isd-metric-val blue"><?php echo count($segments['vip']['customers']); ?></div>
            <div class="isd-metric-label"><?php _e('VIP Customer', 'api-isarud'); ?></div>
        </div>
        <div class="isd-metric">
            <div class="isd-metric-val amber"><?php echo count($segments['at_risk']['customers']); ?></div>
            <div class="isd-metric-label"><?php _e('At Risk', 'api-isarud'); ?></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px">
        <?php foreach ($segments as $key => $s):
            $count = count($s['customers']);
            $pct = $data['total_customers'] > 0 ? round($count / $data['total_customers'] * 100) : 0;
        ?>
        <div class="isd-status-card" style="padding:14px;text-align:center;border-left:3px solid <?php echo $s['color']; ?>;border-radius:0 10px 10px 0">
            <div style="font-size:22px;font-weight:700;color:<?php echo $s['color']; ?>"><?php echo $count; ?></div>
            <div style="font-size:11px;color:#888;margin-top:2px"><?php echo $s['label']; ?></div>
            <div style="font-size:10px;color:#aaa;margin-top:2px"><?php echo $pct; ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px">
        <div>
            <?php foreach ($segments as $key => $s):
                if (empty($s['customers'])) continue;
            ?>
            <div class="isd-activity" style="margin-bottom:16px">
                <h3 style="display:flex;align-items:center;gap:8px">
                    <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $s['color']; ?>;flex-shrink:0"></span>
                    <?php echo $s['label']; ?>
                    <span style="font-size:12px;padding:2px 10px;border-radius:12px;background:#f8f9fa;color:#888;font-weight:400"><?php echo count($s['customers']); ?></span>
                </h3>
                <div style="max-height:250px;overflow-y:auto">
                    <table class="wp-list-table widefat fixed striped" style="font-size:12px">
                        <thead><tr>
                            <th><?php _e('Email', 'api-isarud'); ?></th>
                            <th style="width:70px"><?php _e('Order', 'api-isarud'); ?></th>
                            <th style="width:100px"><?php _e('Total', 'api-isarud'); ?></th>
                            <th style="width:90px"><?php _e('Average Cart', 'api-isarud'); ?></th>
                            <th style="width:100px"><?php _e('Last Order', 'api-isarud'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach (array_slice($s['customers'], 0, 20) as $c): ?>
                        <tr>
                            <td><?php echo esc_html($c['email']); ?></td>
                            <td><?php echo $c['orders']; ?></td>
                            <td><?php echo wc_price($c['spent']); ?></td>
                            <td><?php echo wc_price($c['avg_order']); ?></td>
                            <td style="color:#888"><?php echo esc_html($c['days_since']); ?> <?php _e('days ago', 'api-isarud'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($s['customers']) > 20): ?>
                        <tr><td colspan="5" style="text-align:center;color:#888">+<?php echo count($s['customers']) - 20; ?> <?php _e('more', 'api-isarud'); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($data['total_customers'] === 0): ?>
            <div class="isd-activity">
                <p class="isd-empty"><?php _e('No order data yet. Customer segments will be created automatically as orders arrive.', 'api-isarud'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="isd-activity" style="margin-bottom:16px">
                <h3><?php _e('Segment Settings', 'api-isarud'); ?></h3>
                <form method="post">
                    <?php wp_nonce_field('isarud_segments'); ?>
                    <table style="width:100%;font-size:13px">
                        <tr>
                            <td style="padding:8px 0"><label><?php _e('VIP Min. Order', 'api-isarud'); ?></label></td>
                            <td style="padding:8px 0"><input type="number" name="vip_threshold" value="<?php echo esc_attr($settings['vip_threshold']); ?>" min="1" style="width:70px"></td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0"><label><?php _e('VIP Min. Amount', 'api-isarud'); ?></label></td>
                            <td style="padding:8px 0"><input type="number" name="vip_amount" value="<?php echo esc_attr($settings['vip_amount']); ?>" step="0.01" style="width:90px"> <?php echo get_woocommerce_currency_symbol(); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0"><label><?php _e('Risk (days)', 'api-isarud'); ?></label></td>
                            <td style="padding:8px 0"><input type="number" name="at_risk_days" value="<?php echo esc_attr($settings['at_risk_days']); ?>" min="1" style="width:70px"></td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0"><label><?php _e('Lost (days)', 'api-isarud'); ?></label></td>
                            <td style="padding:8px 0"><input type="number" name="lost_days" value="<?php echo esc_attr($settings['lost_days']); ?>" min="1" style="width:70px"></td>
                        </tr>
                    </table>
                    <button type="submit" name="isarud_save_segments" class="button-primary" style="width:100%;margin-top:8px"><?php _e('Save and Refresh', 'api-isarud'); ?></button>
                </form>
            </div>

            <div class="isd-activity">
                <h3><?php _e('Segment Definitions', 'api-isarud'); ?></h3>
                <div style="font-size:12px;color:#666;line-height:1.8">
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#185fa5;margin-right:6px;vertical-align:middle"></span><strong>VIP:</strong> <?php echo $settings['vip_threshold']; ?>+ <?php _e('order and', 'api-isarud'); ?> <?php echo wc_price($settings['vip_amount']); ?>+</div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#0f6e56;margin-right:6px;vertical-align:middle"></span><strong><?php _e('Loyal:', 'api-isarud'); ?></strong> 3+ <?php _e('order, active', 'api-isarud'); ?></div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#534ab7;margin-right:6px;vertical-align:middle"></span><strong><?php _e('New:', 'api-isarud'); ?></strong> <?php _e('Last 30 days, 1-2 orders', 'api-isarud'); ?></div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ba7517;margin-right:6px;vertical-align:middle"></span><strong><?php _e('Risk:', 'api-isarud'); ?></strong> <?php echo $settings['at_risk_days']; ?>+ <?php _e('days ago', 'api-isarud'); ?></div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#a32d2d;margin-right:6px;vertical-align:middle"></span><strong><?php _e('Lost:', 'api-isarud'); ?></strong> <?php echo $settings['lost_days']; ?>+ <?php _e('days ago', 'api-isarud'); ?></div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#888780;margin-right:6px;vertical-align:middle"></span><strong><?php _e('One-Time:', 'api-isarud'); ?></strong> <?php _e('1 order', 'api-isarud'); ?></div>
                </div>
            </div>

            <?php if ($settings['last_refresh']): ?>
            <p style="font-size:11px;color:#aaa;text-align:center;margin-top:8px"><?php _e('Last analysis:', 'api-isarud'); ?> <?php echo esc_html($settings['last_refresh']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
        <?php _e('How it works: Your customers are automatically divided into segments based on their order history. You can send special campaigns to your VIP customers and recovery emails to at-risk customers.', 'api-isarud'); ?>
    </div>
</div>
