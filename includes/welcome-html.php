<?php
if (!defined('ABSPATH')) exit;
$welcome = Isarud_Welcome::instance();
$dismissed = $welcome->is_dismissed();
$step = intval($_GET['step'] ?? 1);
$total_steps = 5;
?>
<div class="wrap" style="max-width:800px;margin:20px auto">

    <div style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);border-radius:16px;padding:40px;text-align:center;margin-bottom:24px;position:relative;overflow:hidden">
        <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2280%22 cy=%2220%22 r=%2240%22 fill=%22rgba(53,138,79,0.1)%22/><circle cx=%2220%22 cy=%2280%22 r=%2230%22 fill=%22rgba(53,138,79,0.08)%22/></svg>')"></div>
        <div style="position:relative;z-index:1">
            <div style="width:64px;height:64px;background:#358a4f;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <svg style="width:36px;height:36px;color:#fff" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <h1 style="color:#fff;font-size:28px;font-weight:800;margin:0 0 8px"><?php _e('Welcome to Isarud!', 'api-isarud'); ?></h1>
            <p style="color:rgba(255,255,255,0.7);font-size:14px;margin:0;max-width:500px;display:inline-block"><?php _e('Sanction screening, marketplace integration, and e-commerce management platform. Let\'s get started with quick setup.', 'api-isarud'); ?></p>
        </div>
    </div>

    <div style="display:flex;justify-content:center;gap:8px;margin-bottom:24px">
        <?php for ($i = 1; $i <= $total_steps; $i++): ?>
        <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=' . $i); ?>" style="width:<?php echo $i === $step ? '32px' : '10px'; ?>;height:10px;border-radius:5px;background:<?php echo $i === $step ? '#358a4f' : ($i < $step ? '#bbf7d0' : '#e5e7eb'); ?>;display:block;transition:all .2s"></a>
        <?php endfor; ?>
    </div>

    <?php if ($step === 1): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('1. Connect Your Isarud Account', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Create a free account on isarud.com and get your API key. This key is required for sanction screening.', 'api-isarud'); ?></p>
        <div style="background:#f9fafb;border-radius:10px;padding:20px;margin-bottom:16px">
            <div style="display:flex;align-items:start;gap:16px;margin-bottom:16px">
                <div style="width:32px;height:32px;background:#358a4f;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:14px">1</div>
                <div>
                    <p style="margin:0;font-weight:600;font-size:14px;color:#333"><?php _e('Create an account on isarud.com', 'api-isarud'); ?></p>
                    <p style="margin:4px 0 0;font-size:12px;color:#888"><?php _e('Register quickly with your Google or Apple account', 'api-isarud'); ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:start;gap:16px;margin-bottom:16px">
                <div style="width:32px;height:32px;background:#358a4f;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:14px">2</div>
                <div>
                    <p style="margin:0;font-weight:600;font-size:14px;color:#333"><?php _e('Get your API key from the Account > API Keys page', 'api-isarud'); ?></p>
                    <p style="margin:4px 0 0;font-size:12px;color:#888"><?php _e('Your API key is generated automatically, copy it', 'api-isarud'); ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:start;gap:16px">
                <div style="width:32px;height:32px;background:#358a4f;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:14px">3</div>
                <div>
                    <p style="margin:0;font-weight:600;font-size:14px;color:#333"><?php _e('Enter your API key in the Isarud > Settings page', 'api-isarud'); ?></p>
                    <p style="margin:4px 0 0;font-size:12px;color:#888"><?php _e('When the API key is entered, sanction screening is automatically activated', 'api-isarud'); ?></p>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <a href="https://isarud.com/register" target="_blank" class="button-primary" style="padding:10px 24px"><?php _e('Sign Up on isarud.com', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=2'); ?>" class="button" style="padding:10px 24px"><?php _e('Next Step', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 2): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('2. Enter Your Marketplace API Information', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('If you are selling on Trendyol, Hepsiburada, N11 or other marketplaces, set up the integration by entering your API information.', 'api-isarud'); ?></p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
            <?php
            $marketplaces = [
                ['name' => 'Trendyol', 'color' => '#f27a1a'],
                ['name' => 'Hepsiburada', 'color' => '#ff6000'],
                ['name' => 'N11', 'color' => '#7b2d8e'],
                ['name' => 'Amazon', 'color' => '#ff9900'],
                ['name' => 'Pazarama', 'color' => '#00b0ff'],
                ['name' => 'Etsy', 'color' => '#f1641e'],
            ];
            foreach ($marketplaces as $mp): ?>
            <div style="background:#f9fafb;border-radius:8px;padding:12px;text-align:center;border:1px solid #e5e7eb">
                <div style="width:8px;height:8px;border-radius:50%;background:<?php echo $mp['color']; ?>;margin:0 auto 6px"></div>
                <span style="font-size:13px;font-weight:600;color:#333"><?php echo $mp['name']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:10px">
            <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>" class="button-primary" style="padding:10px 24px"><?php _e('Marketplace API Settings', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=3'); ?>" class="button" style="padding:10px 24px"><?php _e('Next Step', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 3): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('3. Complete Your E-commerce Infrastructure', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Use the E-commerce Guide to set up payment, shipping, SEO and marketing tools.', 'api-isarud'); ?></p>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:20px">
            <?php
            $features = [
                ['icon' => '💳', 'title' => __('Payment System', 'api-isarud'), 'desc' => __('iyzico, PayTR setup', 'api-isarud')],
                ['icon' => '🚚', 'title' => __('Shipping', 'api-isarud'), 'desc' => __('Hezarfen (23 companies)', 'api-isarud')],
                ['icon' => '🔍', 'title' => __('SEO', 'api-isarud'), 'desc' => __('Yoast/RankMath', 'api-isarud')],
                ['icon' => '📧', 'title' => __('Marketing', 'api-isarud'), 'desc' => __('Email, popup, cart', 'api-isarud')],
                ['icon' => '🧾', 'title' => __('E-Invoice', 'api-isarud'), 'desc' => __('GIB e-Archive Portal', 'api-isarud')],
                ['icon' => '🏪', 'title' => __('POS', 'api-isarud'), 'desc' => __('Physical store', 'api-isarud')],
            ];
            foreach ($features as $f): ?>
            <div style="background:#f9fafb;border-radius:8px;padding:12px;display:flex;align-items:center;gap:10px;border:1px solid #e5e7eb">
                <span style="font-size:20px"><?php echo $f['icon']; ?></span>
                <div>
                    <p style="margin:0;font-size:13px;font-weight:600;color:#333"><?php echo $f['title']; ?></p>
                    <p style="margin:2px 0 0;font-size:11px;color:#888"><?php echo $f['desc']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:10px">
            <a href="<?php echo admin_url('admin.php?page=isarud-ecosystem'); ?>" class="button-primary" style="padding:10px 24px"><?php _e('E-commerce Guide', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=4'); ?>" class="button" style="padding:10px 24px"><?php _e('Next Step', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 4): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('4. Set Up Your Connection with Cloud Sync', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('With Cloud Sync, your WooCommerce store automatically synchronizes with your isarud.com account. Track your products and orders from any device.', 'api-isarud'); ?></p>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px;margin-bottom:20px">
            <h3 style="margin:0 0 12px;font-size:15px;color:#15803d"><?php _e('Access From Anywhere', 'api-isarud'); ?></h3>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
                <?php
                $platforms = [
                    ['icon' => '🌐', 'name' => 'isarud.com', 'desc' => __('Web browser', 'api-isarud'), 'url' => 'https://isarud.com', 'live' => true],
                    ['icon' => '📱', 'name' => 'iOS / iPadOS / macOS', 'desc' => 'App Store', 'url' => 'https://apps.apple.com/tr/app/isarud-e-commerce-tools/id6761309959', 'live' => true],
                    ['icon' => '🖥️', 'name' => 'Windows', 'desc' => 'Microsoft Store', 'url' => 'https://www.microsoft.com/store/apps/9PM1Z57C4GT3', 'live' => true],
                    ['icon' => '🤖', 'name' => 'Android', 'desc' => __('Coming Soon', 'api-isarud'), 'url' => '', 'live' => false],
                    ['icon' => '🔌', 'name' => 'WooCommerce', 'desc' => __('This plugin', 'api-isarud'), 'url' => '', 'live' => true],
                    ['icon' => '🛍️', 'name' => 'Shopify', 'desc' => __('Shopify app', 'api-isarud'), 'url' => '', 'live' => true],
                    ['icon' => '⚡', 'name' => 'REST API', 'desc' => __('Custom Integration', 'api-isarud'), 'url' => 'https://isarud.com/api-docs', 'live' => true],
                ];
                foreach ($platforms as $p): ?>
                <?php if (!empty($p['url'])): ?>
                <a href="<?php echo $p['url']; ?>" target="_blank" style="display:flex;align-items:center;gap:8px;padding:8px;background:#fff;border-radius:6px;text-decoration:none;transition:box-shadow .2s">
                <?php else: ?>
                <div style="display:flex;align-items:center;gap:8px;padding:8px;background:#fff;border-radius:6px<?php echo !$p['live'] ? ';opacity:0.7' : ''; ?>">
                <?php endif; ?>
                    <span style="font-size:18px"><?php echo $p['icon']; ?></span>
                    <div>
                        <p style="margin:0;font-size:12px;font-weight:600;color:#333"><?php echo $p['name']; ?></p>
                        <p style="margin:0;font-size:10px;color:<?php echo $p['live'] ? '#16a34a' : '#d97706'; ?>"><?php echo $p['desc']; ?><?php echo !empty($p['url']) ? ' ↗' : ''; ?></p>
                    </div>
                <?php echo !empty($p['url']) ? '</a>' : '</div>'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <a href="<?php echo admin_url('admin.php?page=isarud-cloud-sync'); ?>" class="button-primary" style="padding:10px 24px"><?php _e('Cloud Sync Settings', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=5'); ?>" class="button" style="padding:10px 24px"><?php _e('Next Step', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 5): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px;text-align:center">
        <div style="width:56px;height:56px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg style="width:28px;height:28px;color:#358a4f" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 style="margin:0 0 8px;font-size:22px;color:#1a1a2e"><?php _e('You\'re All Set!', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:14px;margin-bottom:24px;max-width:450px;display:inline-block"><?php _e('Isarud setup is complete. You can now run compliance scans, manage your marketplaces, and grow your e-commerce business.', 'api-isarud'); ?></p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px;max-width:500px;margin-left:auto;margin-right:auto">
            <a href="<?php echo admin_url('admin.php?page=isarud'); ?>" style="background:#358a4f;color:#fff;padding:14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600">
                <?php _e('Dashboard', 'api-isarud'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>" style="background:#1a1a2e;color:#fff;padding:14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600">
                <?php _e('Marketplaces', 'api-isarud'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=isarud-ecosystem'); ?>" style="background:#185fa5;color:#fff;padding:14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600">
                <?php _e('Guide', 'api-isarud'); ?>
            </a>
        </div>
        <button id="isarud-dismiss-welcome" class="button" style="padding:8px 20px"><?php _e('Close Welcome Screen', 'api-isarud'); ?></button>
        <p style="margin-top:12px;font-size:11px;color:#aaa"><?php _e('You can reopen the welcome screen from Isarud > Settings page.', 'api-isarud'); ?></p>
    </div>

    <script>
    jQuery('#isarud-dismiss-welcome').on('click', function(){
        jQuery.post(isarud.ajax, {action:'isarud_dismiss_welcome', nonce:isarud.nonce}, function(){
            window.location.href = '<?php echo admin_url('admin.php?page=isarud'); ?>';
        });
    });
    </script>
    <?php endif; ?>

    <?php if ($step > 1): ?>
    <div style="text-align:center;margin-top:16px">
        <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=' . ($step - 1)); ?>" style="font-size:12px;color:#888;text-decoration:none">&larr; <?php _e('Previous Step', 'api-isarud'); ?></a>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="<?php echo admin_url('admin.php?page=isarud'); ?>" style="font-size:12px;color:#888;text-decoration:none"><?php _e('Skip, Go to Dashboard', 'api-isarud'); ?></a>
    </div>
    <?php endif; ?>

</div>
