<?php if (!defined('ABSPATH')) exit;
$tab = sanitize_text_field($_GET['tab'] ?? 'overview');
$tabs = [
    'overview' => __('Genel Bakış', 'api-isarud'),
    'payment'  => __('Ödeme Sistemi', 'api-isarud'),
    'shipping' => __('Kargo', 'api-isarud'),
    'seo'      => __('SEO', 'api-isarud'),
    'marketing'=> __('Pazarlama', 'api-isarud'),
    'analytics'=> __('Analitik', 'api-isarud'),
    'security' => __('Güvenlik', 'api-isarud'),
];
?>
<div class="wrap">
    <div class="isd-header">
        <div class="isd-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="isd-title"><?php _e('E-ticaret Rehberi', 'api-isarud'); ?></div>
            <div class="isd-version"><?php _e('Uçtan uca e-ticaret altyapınızı kurun', 'api-isarud'); ?></div>
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
            'payment'  => ['title' => __('Ödeme Sistemi', 'api-isarud'), 'desc' => __('Sanal POS kurulumu ile kredi kartı, banka havalesi ve kapıda ödeme seçeneklerini aktifleştirin.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>'],
            'shipping' => ['title' => __('Kargo Entegrasyonu', 'api-isarud'), 'desc' => __('Aras, Yurtiçi, MNG ve diğer kargo firmalarıyla otomatik entegrasyon kurun.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>'],
            'seo'      => ['title' => __('SEO Yönetimi', 'api-isarud'), 'desc' => __('Arama motorlarında üst sıralara çıkmak için SEO araçlarını kurun.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'],
            'marketing'=> ['title' => __('Pazarlama Otomasyonu', 'api-isarud'), 'desc' => __('Sepet hatırlatma, e-posta kampanyaları ve müşteri geri kazanım araçları.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>'],
            'analytics'=> ['title' => __('Analitik ve Raporlama', 'api-isarud'), 'desc' => __('Ziyaretçi davranışı, satış analitiği ve dönüşüm takibi.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'],
            'security' => ['title' => __('Güvenlik', 'api-isarud'), 'desc' => __('Site güvenliği, güvenlik duvarı ve spam koruması.', 'api-isarud'), 'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>'],
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
        <h3><?php _e('Ödeme Sistemi Kurulumu', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('WooCommerce mağazanızda online ödeme almak için bir sanal POS eklentisi kurmanız gerekir. Aşağıdaki seçeneklerden birini tercih edebilirsiniz.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title">iyzico <?php _e('(Önerilen)', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Türkiye\'nin en yaygın ödeme altyapısı. Kredi kartı, banka kartı, BKM Express destekler. Komisyon oranları: %2.79 + 0.25 TL. Başvuru için iyzico.com\'dan hesap açın, API anahtarlarınızı alın ve eklentiye girin.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('woocommerce-iyzico/iyzico-for-woocommerce.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Kurulu ve aktif', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=iyzico+woocommerce&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px"><?php _e('iyzico Eklentisini Kur', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title">PayTR</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Düşük komisyon oranlarıyla bilinen ödeme altyapısı. 3D Secure, taksit ve sanal POS destekler. paytr.com\'dan başvurun, Mağaza No ve API bilgilerinizi alın.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('flavor-flavor-payment-gateway/flavor-flavor-payment-gateway.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Kurulu ve aktif', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=flavor+payment&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('PayTR Eklentisini Kur', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title">Param (eski Paramatik)</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Türk Telekom altyapısıyla çalışan ödeme sistemi. Hızlı başvuru süreci ve rekabetçi komisyon oranları sunar.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('param-sanal-pos/param-sanal-pos.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Kurulu ve aktif', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=param+sanal+pos&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('Param Eklentisini Kur', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>
        </div>

        <div style="background:#f0f6fc;border:1px solid #b5d4f4;border-radius:10px;padding:16px;margin-top:16px;font-size:12px;color:#185fa5">
            <?php _e('Kurulum sonrası WooCommerce > Ayarlar > Ödemeler sayfasından aktifleştirmeyi ve API bilgilerinizi girmeyi unutmayın.', 'api-isarud'); ?>
        </div>
    </div>

    <?php elseif ($tab === 'shipping'): ?>
    <div class="isd-activity">
        <h3><?php _e('Kargo Entegrasyonu Kurulumu', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Siparişlerinizi otomatik olarak kargo firmalarına iletmek ve takip numarası almak için bir kargo eklentisi kurun.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title"><?php _e('Kargo Eklentisi Seçin', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Türkiye\'deki popüler kargo firmalarını WooCommerce ile entegre eden eklentiler mevcuttur. Kullandığınız kargo firmasına uygun eklentiyi seçin.', 'api-isarud'); ?>
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                    <a href="<?php echo admin_url('plugin-install.php?s=hezarfen&tab=search&type=term'); ?>" class="button-primary" style="margin-right:4px"><?php _e('Hezarfen (23 Kargo Firmasi)', 'api-isarud'); ?> &rarr;</a>
                    <a href="<?php echo admin_url('plugin-install.php?s=kargo+entegrator&tab=search&type=term'); ?>" class="button" style="margin-right:4px"><?php _e('Kargo Entegrator', 'api-isarud'); ?> &rarr;</a>
                    <a href="<?php echo admin_url('plugin-install.php?s=woocommerce+pos&tab=search&type=term'); ?>" class="button"><?php _e('POS (Fiziksel Magaza)', 'api-isarud'); ?> &rarr;</a>
                </div>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('Kargo Bölgeleri Ayarlayın', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('WooCommerce > Ayarlar > Kargo bölümünden kargo bölgelerinizi ve ücretlerinizi tanımlayın. Sabit fiyat, ücretsiz kargo veya koşullu kargo kuralları oluşturabilirsiniz.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping'); ?>" class="button" style="margin-top:8px"><?php _e('Kargo Ayarları', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('Pazar Yeri Kargo Entegrasyonu', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Isarud, Trendyol siparişlerine otomatik kargo firması atama özelliğine sahiptir. Isarud > Pazar Yeri API sayfasından Trendyol kargo ayarlarını yapılandırın.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>" class="button" style="margin-top:8px"><?php _e('Pazar Yeri API', 'api-isarud'); ?> &rarr;</a>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'seo'): ?>
    <div class="isd-activity">
        <h3><?php _e('SEO Yönetimi Kurulumu', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Ürünlerinizin Google\'da üst sıralarda çıkması için bir SEO eklentisi kurun ve temel ayarları yapın.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title"><?php _e('SEO Eklentisi Kurun', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Yoast SEO veya Rank Math, WooCommerce ürünleriniz için meta başlık, açıklama, şema işaretleme ve site haritası oluşturur.', 'api-isarud'); ?>
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                    <?php if (is_plugin_active('wordpress-seo/wp-seo.php')): ?>
                    <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600">Yoast SEO <?php _e('aktif', 'api-isarud'); ?></span>
                    <?php else: ?>
                    <a href="<?php echo admin_url('plugin-install.php?s=yoast+seo&tab=search&type=term'); ?>" class="button-primary">Yoast SEO <?php _e('Kur', 'api-isarud'); ?> &rarr;</a>
                    <?php endif; ?>
                    <?php if (is_plugin_active('seo-by-rank-math/rank-math.php')): ?>
                    <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600">Rank Math <?php _e('aktif', 'api-isarud'); ?></span>
                    <?php else: ?>
                    <a href="<?php echo admin_url('plugin-install.php?s=rank+math+seo&tab=search&type=term'); ?>" class="button">Rank Math <?php _e('Kur', 'api-isarud'); ?> &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('Google Search Console Bağlayın', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('search.google.com/search-console adresinden sitenizi doğrulayın. Sitemap\'inizi gönderin ve indeksleme durumunu takip edin.', 'api-isarud'); ?>
                </p>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('Ürün SEO Optimizasyonu', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Her ürün için benzersiz başlık ve açıklama yazın. Ürün görsellerine alt etiketleri ekleyin. Kategori ve etiket yapınızı düzenleyin.', 'api-isarud'); ?>
                </p>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'marketing'): ?>
    <div class="isd-activity">
        <h3><?php _e('Pazarlama Otomasyonu Kurulumu', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Terk edilen sepetleri geri kazanın, e-posta kampanyaları gönderin ve müşterilerinizle iletişimde kalın.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title"><?php _e('Sepet Hatırlatma', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Müşteriler sepetlerini terk ettiğinde otomatik e-posta ile hatırlatma gönderin. Araştırmalara göre terk edilen sepetlerin %10-15\'i bu şekilde geri kazanılabilir.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=cart+abandonment+recovery&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px"><?php _e('Sepet Hatırlatma Eklentisi Kur', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('E-posta Pazarlama', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Mailchimp ile müşteri listesi oluşturun, otomatik hoş geldiniz e-postaları ve kampanya bildirimleri gönderin.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=mailchimp+woocommerce&tab=search&type=term'); ?>" class="button" style="margin-top:8px">Mailchimp <?php _e('Kur', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('E-posta Tasarımı', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('WooCommerce sipariş e-postalarınızı markanıza uygun şekilde özelleştirin. Logo, renkler ve içerik düzeni ayarlayın.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=kadence+email+designer&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('E-posta Tasarım Eklentisi Kur', 'api-isarud'); ?> &rarr;</a>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'analytics'): ?>
    <div class="isd-activity">
        <h3><?php _e('Analitik ve Raporlama Kurulumu', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Ziyaretçi davranışlarını, satış performansını ve dönüşüm oranlarını takip edin.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title">Google Site Kit</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Google Analytics, Search Console, PageSpeed Insights ve AdSense verilerini tek panelden görün. Google\'ın resmi WordPress eklentisi.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('google-site-kit/google-site-kit.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Kurulu ve aktif', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=google+site+kit&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px">Google Site Kit <?php _e('Kur', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title"><?php _e('WooCommerce Google Analytics', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('E-ticaret olaylarını (ürün görüntüleme, sepete ekleme, satın alma) Google Analytics\'e gönderin. Dönüşüm hunisi analizi yapın.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('plugin-install.php?s=woocommerce+google+analytics&tab=search&type=term'); ?>" class="button" style="margin-top:8px"><?php _e('WC Analytics Eklentisi Kur', 'api-isarud'); ?> &rarr;</a>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('Isarud Analitik', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Isarud\'un kendi istatistikler sayfasından yaptırım tarama ve pazar yeri sync aktivitelerinizi takip edin.', 'api-isarud'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=isarud-statistics'); ?>" class="button" style="margin-top:8px"><?php _e('Isarud İstatistikler', 'api-isarud'); ?> &rarr;</a>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'security'): ?>
    <div class="isd-activity">
        <h3><?php _e('Güvenlik Kurulumu', 'api-isarud'); ?></h3>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('E-ticaret sitenizi kötü amaçlı yazılımlara, brute force saldırılarına ve spam\'e karşı koruyun.', 'api-isarud'); ?></p>

        <div class="isd-steps" style="grid-template-columns:1fr;max-width:700px">
            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">1</div>
                <div class="isd-step-title">Wordfence Security</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Güvenlik duvarı (WAF), kötü amaçlı yazılım tarayıcısı, giriş güvenliği ve gerçek zamanlı tehdit istihbaratı. 4 milyondan fazla site tarafından kullanılıyor.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('wordfence/wordfence.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Kurulu ve aktif', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=wordfence&tab=search&type=term'); ?>" class="button-primary" style="margin-top:8px">Wordfence <?php _e('Kur', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">2</div>
                <div class="isd-step-title">Akismet Anti-Spam</div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Yorum ve form spam\'ini otomatik filtreler. WordPress.com hesabıyla ücretsiz kullanılabilir.', 'api-isarud'); ?>
                </p>
                <?php if (is_plugin_active('akismet/akismet.php')): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('Kurulu ve aktif', 'api-isarud'); ?></span>
                <?php else: ?>
                <a href="<?php echo admin_url('plugin-install.php?s=akismet&tab=search&type=term'); ?>" class="button" style="margin-top:8px">Akismet <?php _e('Kur', 'api-isarud'); ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="isd-step" style="padding:20px">
                <div class="isd-step-num">3</div>
                <div class="isd-step-title"><?php _e('SSL Sertifikası', 'api-isarud'); ?></div>
                <p class="isd-step-desc" style="font-size:12px;line-height:1.6;color:#555">
                    <?php _e('Sitenizin HTTPS ile çalıştığından emin olun. Hosting sağlayıcınız genellikle ücretsiz SSL (Let\'s Encrypt) sunar. E-ticaret siteleri için SSL zorunludur.', 'api-isarud'); ?>
                </p>
                <?php if (is_ssl()): ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#f0fdf4;color:#15803d;font-weight:600"><?php _e('SSL aktif', 'api-isarud'); ?></span>
                <?php else: ?>
                <span style="font-size:12px;padding:4px 12px;border-radius:8px;background:#fef2f2;color:#dc2626;font-weight:600"><?php _e('SSL aktif degil — hosting saglayicinizla iletisime gecin', 'api-isarud'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top:20px;text-align:center">
        <a href="<?php echo admin_url('admin.php?page=isarud'); ?>" class="button">&larr; <?php _e('Dashboard\'a Dön', 'api-isarud'); ?></a>
    </div>
</div>
