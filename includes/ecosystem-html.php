<?php if (!defined('ABSPATH')) exit;
$tab = sanitize_text_field($_GET['tab'] ?? 'overview');
$tabs = [
    'overview' => __('Overview', 'api-isarud'),
    'payment'  => __('Payment System', 'api-isarud'),
    'shipping' => __('Shipping', 'api-isarud'),
    'seo'      => __('SEO', 'api-isarud'),
    'marketing'=> __('Marketing', 'api-isarud'),
    'analytics'=> __('Analytics', 'api-isarud'),
    'security' => __('Security', 'api-isarud'),
];
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="isd-title"><?php _e('E-commerce Guide', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Build your end-to-end e-commerce infrastructure', 'api-isarud'); ?></div>
        </div>
    </div>

    <nav class="nav-tab-wrapper" style="margin-bottom:20px">
        <?php foreach ($tabs as $key => $label): ?>
        <a href="<?php echo admin_url('admin.php?page=isarud-ecosystem&tab=' . $key); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($tab === 'overview'): ?>
    <div class="isd-features" style="margin-bottom:20px">
        <?php
        $categories = [
            'payment'  => ['title' => __('Payment System', 'api-isarud'), 'desc' => __('Enable credit card, bank transfer, and cash on delivery payment options with Virtual POS setup.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>'],
            'shipping' => ['title' => __('Shipping Integration', 'api-isarud'), 'desc' => __('Set up automatic integration with Aras, Yurtiçi, MNG and other cargo companies.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>'],
            'seo'      => ['title' => __('SEO Management', 'api-isarud'), 'desc' => __('Set up SEO tools to rank higher in search engines.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'],
            'marketing'=> ['title' => __('Marketing Automation', 'api-isarud'), 'desc' => __('Cart abandonment reminders, email campaigns, and customer retention tools.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>'],
            'analytics'=> ['title' => __('Analytics and Reporting', 'api-isarud'), 'desc' => __('Visitor behavior, sales analytics, and conversion tracking.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'],
            'security' => ['title' => __('Security', 'api-isarud'), 'desc' => __('Site security, firewall, and spam protection.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>'],
        ];
        foreach ($categories as $key => $cat): ?>
        <a href="<?php echo admin_url('admin.php?page=isarud-ecosystem&tab=' . $key); ?>" class="isd-feature-card" style="text-decoration:none;transition:border-color .15s">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                <div style="width:36px;height:36px;border-radius:8px;background:#f0f6fc;display:flex;align-items:center;justify-content:center;color:#185fa5;flex-shrink:0"><?php echo $cat['icon']; ?></div>
                <h3 style="margin:0;font-size:15px"><?php echo $cat['title']; ?></h3>
            </div>
            <p class="subtitle" style="margin:0"><?php echo $cat['desc']; ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <?php elseif ($tab === 'payment'): ?>
    <div class="isd-activity">
        <h3><?php _e('Payment System Setup', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('You need to install a Virtual POS plugin to accept online payments in your WooCommerce store. You can choose one of the following options.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title">iyzico <?php _e('(Recommended)', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Turkey\'s most widely used payment infrastructure. Supports credit card, debit card, and BKM Express. Commission rates: 2.79% + 0.25 TL. To apply, open an account at iyzico.com, get your API keys, and enter them in the plugin.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('woocommerce-iyzico/iyzico-for-woocommerce.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Installed and active', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=iyzico+woocommerce&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px"><?php _e('Install iyzico Plugin', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title">PayTR</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Payment infrastructure known for low commission rates. Supports 3D Secure, installments, and Virtual POS. Apply at paytr.com and obtain your Store Number and API information.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('flavor-flavor-payment-gateway/flavor-flavor-payment-gateway.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Installed and active', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=flavor+payment&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('Install PayTR Plugin', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title">Param (eski Paramatik)</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Payment system operating on Türk Telekom infrastructure. Offers fast application process and competitive commission rates.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('param-sanal-pos/param-sanal-pos.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Installed and active', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=param+sanal+pos&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('Install Param Plugin', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>
        </div>

        <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
            <?php _e('After setup, remember to activate it and enter your API information from WooCommerce > Settings > Payments.', 'api-isarud'); ?>
        </div>
    </div>

    <?php elseif ($tab === 'shipping'): ?>
    <div class="isd-activity">
        <h3><?php _e('Shipping Integration Setup', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Install a shipping plugin to automatically forward your orders to shipping companies and obtain tracking numbers.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title"><?php _e('Select Shipping Plugin', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('There are plugins that integrate popular shipping companies in Turkey with WooCommerce. Select the plugin suitable for your shipping company.', 'api-isarud'); ?>
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                    <a href="<?php echo admin_url('plugin-install.php?s=hezarfen&tab=search&type=term'); ?>" class="button-primary" style="margin-right:4px"><?php _e('Hezarfen (23 Shipping Companies)', 'api-isarud'); ?> &rarr;</a>
                    <a href="<?php echo admin_url('plugin-install.php?s=kargo+entegrator&tab=search&type=term'); ?>" class="button" style="margin-right:4px"><?php _e('Cargo Integrator', 'api-isarud'); ?> &rarr;</a>
                    <a href="<?php echo admin_url('plugin-install.php?s=woocommerce+pos&tab=search&type=term'); ?>" class="button"><?php _e('POS (Physical Store)', 'api-isarud'); ?> &rarr;</a>
                </div>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('Configure Shipping Zones', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Define your shipping zones and rates from WooCommerce > Settings > Shipping. You can create fixed price, free shipping, or conditional shipping rules.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping'); ?>" class="button" style="margin-top:8px"><?php _e('Shipping Settings', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('Marketplace Shipping Integration', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Isarud has the ability to automatically assign shipping companies to Trendyol orders. Configure Trendyol shipping settings from Isarud > Marketplace API page.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>" class="button" style="margin-top:8px"><?php _e('Marketplace API', 'api-isarud'); ?> &rarr;</a>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'seo'): ?>
    <div class="isd-activity">
        <h3><?php _e('SEO Management Setup', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Install and configure an SEO plugin to help your products rank higher on Google.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title"><?php _e('Install SEO Plugin', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Yoast SEO or Rank Math creates meta titles, descriptions, schema markup, and sitemaps for your WooCommerce products.', 'api-isarud'); ?>
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                    <?php if (is_plugin_active('wordpress-seo/wp-seo.php')): ?>
                    <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600">Yoast SEO <?php _e('active', 'api-isarud'); ?></span>
                    <?php else: ?>
                    <a href="<?php echo admin_url('plugin-install.php?s=yoast+seo&tab=search&type=term'); ?>" class="button-primary">Yoast SEO <?php _e('Install', 'api-isarud'); ?> &rarr;</a>
                    <?php endif; ?>
                    <?php if (is_plugin_active('seo-by-rank-math/rank-math.php')): ?>
                    <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600">Rank Math <?php _e('active', 'api-isarud'); ?></span>
                    <?php else: ?>
                    <a href="<?php echo admin_url('plugin-install.php?s=rank+math+seo&tab=search&type=term'); ?>" class="button">Rank Math <?php _e('Install', 'api-isarud'); ?> &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('Connect Google Search Console', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Verify your site at search.google.com/search-console. Submit your sitemap and monitor indexing status.', 'api-isarud'); ?>
                </p>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('Product SEO Optimization', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Write unique titles and descriptions for each product. Add alt text to product images. Organize your category and tag structure.', 'api-isarud'); ?>
                </p>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'marketing'): ?>
    <div class="isd-activity">
        <h3><?php _e('Marketing Automation Setup', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Recover abandoned carts, send email campaigns, and stay in touch with your customers.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title"><?php _e('Cart Reminder', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Send automatic email reminders when customers abandon their carts. According to research, 10-15% of abandoned carts can be recovered this way.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=cart+abandonment+recovery&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px"><?php _e('Install Cart Recovery Plugin', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('Email Marketing', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Create a customer list with Mailchimp, send automatic welcome emails and campaign notifications.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=mailchimp+woocommerce&tab=search&type=term'); ?>" class="button" style="margin-top:8px">Mailchimp <?php _e('Install', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('Email Design', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Customize your WooCommerce order emails to match your brand. Adjust logo, colors, and content layout.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=kadence+email+designer&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('Install Email Design Plugin', 'api-isarud'); ?> &rarr;</a>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'analytics'): ?>
    <div class="isd-activity">
        <h3><?php _e('Analytics and Reporting Setup', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Track visitor behavior, sales performance, and conversion rates.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title">Google Site Kit</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('View Google Analytics, Search Console, PageSpeed Insights, and AdSense data from a single dashboard. Google\'s official WordPress plugin.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('google-site-kit/google-site-kit.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Installed and active', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=google+site+kit&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px">Google Site Kit <?php _e('Install', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('WooCommerce Google Analytics', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Send e-commerce events (product view, add to cart, purchase) to Google Analytics. Perform conversion funnel analysis.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=woocommerce+google+analytics&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('Install WC Analytics Plugin', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('Isarud Analytics', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Track compliance scans and marketplace sync activities from Isarud\'s own statistics page.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=isarud-statistics'); ?>" class="button" style="margin-top:8px"><?php _e('Isarud Statistics', 'api-isarud'); ?> &rarr;</a>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'security'): ?>
    <div class="isd-activity">
        <h3><?php _e('Security Setup', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Protect your e-commerce site against malware, brute force attacks, and spam.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title">Wordfence Security</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Firewall (WAF), malware scanner, login security, and real-time threat intelligence. Used by over 4 million sites.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('wordfence/wordfence.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Installed and active', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=wordfence&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px">Wordfence <?php _e('Install', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title">Akismet Anti-Spam</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Automatically filters comment and form spam. Free to use with WordPress.com account.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('akismet/akismet.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Installed and active', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=akismet&tab=search&type=term'); ?>" class="button" style="margin-top:8px">Akismet <?php _e('Install', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('SSL Certificate', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Ensure your site runs with HTTPS. Your hosting provider typically offers free SSL (Let\'s Encrypt). SSL is mandatory for e-commerce sites.', 'api-isarud'); ?>
                </p>
                <?php if (is_ssl()): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('SSL active', 'api-isarud'); ?></span>
                <?php else: ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#fef2f2;color:#dc2626;font-weight:600"><?php _e('SSL not active — contact your hosting provider', 'api-isarud'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top:20px;text-align:center">
        <a href="<?php echo admin_url('admin.php?page=isarud'); ?>" class="button">&larr; <?php _e('Return to Dashboard', 'api-isarud'); ?></a>
    </div>
</div>
