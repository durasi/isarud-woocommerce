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
            <h1 style="color:#fff;font-size:28px;font-weight:800;margin:0 0 8px"><?php _e('Isarud\'a Hosgeldiniz!', 'api-isarud'); ?></h1>
            <p style="color:rgba(255,255,255,0.7);font-size:14px;margin:0;max-width:500px;display:inline-block"><?php _e('Yaptirim tarama, pazaryeri entegrasyonu ve e-ticaret yonetim platformu. Hizli kuruluma baslayalim.', 'api-isarud'); ?></p>
        </div>
    </div>

    <div style="display:flex;justify-content:center;gap:8px;margin-bottom:24px">
        <?php for ($i = 1; $i <= $total_steps; $i++): ?>
        <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=' . $i); ?>" style="width:<?php echo $i === $step ? '32px' : '10px'; ?>;height:10px;border-radius:5px;background:<?php echo $i === $step ? '#358a4f' : ($i < $step ? '#bbf7d0' : '#e5e7eb'); ?>;display:block;transition:all .2s"></a>
        <?php endfor; ?>
    </div>

    <?php if ($step === 1): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('1. Isarud Hesabinizi Baglayın', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('isarud.com uzerinden ucretsiz hesap olusturun ve API anahtarinizi alin. Bu anahtar yaptirim taramasi icin gereklidir.', 'api-isarud'); ?></p>
        <div style="background:#f9fafb;border-radius:10px;padding:20px;margin-bottom:16px">
            <div style="display:flex;align-items:start;gap:16px;margin-bottom:16px">
                <div style="width:32px;height:32px;background:#358a4f;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:14px">1</div>
                <div>
                    <p style="margin:0;font-weight:600;font-size:14px;color:#333"><?php _e('isarud.com\'da hesap oluşturun', 'api-isarud'); ?></p>
                    <p style="margin:4px 0 0;font-size:12px;color:#888"><?php _e('Google veya Apple hesabinizla hizli kayit olun', 'api-isarud'); ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:start;gap:16px;margin-bottom:16px">
                <div style="width:32px;height:32px;background:#358a4f;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:14px">2</div>
                <div>
                    <p style="margin:0;font-weight:600;font-size:14px;color:#333"><?php _e('Hesap > API Anahtarlari sayfasindan API key alin', 'api-isarud'); ?></p>
                    <p style="margin:4px 0 0;font-size:12px;color:#888"><?php _e('API anahtariniz otomatik olusturulur, kopyalayin', 'api-isarud'); ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:start;gap:16px">
                <div style="width:32px;height:32px;background:#358a4f;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:14px">3</div>
                <div>
                    <p style="margin:0;font-weight:600;font-size:14px;color:#333"><?php _e('Isarud > Ayarlar sayfasina API anahtarinizi girin', 'api-isarud'); ?></p>
                    <p style="margin:4px 0 0;font-size:12px;color:#888"><?php _e('API key girildiginde yaptirim taramasi otomatik aktif olur', 'api-isarud'); ?></p>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <a href="https://isarud.com/register" target="_blank" class="button-primary" style="padding:10px 24px"><?php _e('isarud.com\'da Kayit Ol', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=2'); ?>" class="button" style="padding:10px 24px"><?php _e('Sonraki Adim', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 2): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('2. Pazaryeri API Bilgilerinizi Girin', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Trendyol, Hepsiburada, N11 veya diger pazaryerlerinde satış yapıyorsanız API bilgilerinizi girerek entegrasyonu kurun.', 'api-isarud'); ?></p>
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
            <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>" class="button-primary" style="padding:10px 24px"><?php _e('Pazaryeri API Ayarlari', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=3'); ?>" class="button" style="padding:10px 24px"><?php _e('Sonraki Adim', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 3): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('3. E-ticaret Altyapinizi Tamamlayin', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Odeme, kargo, SEO ve pazarlama araclarini kurmak icin E-ticaret Rehberini kullanin.', 'api-isarud'); ?></p>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:20px">
            <?php
            $features = [
                ['icon' => '💳', 'title' => __('Odeme Sistemi', 'api-isarud'), 'desc' => __('iyzico, PayTR kurulumu', 'api-isarud')],
                ['icon' => '🚚', 'title' => __('Kargo', 'api-isarud'), 'desc' => __('Hezarfen (23 firma)', 'api-isarud')],
                ['icon' => '🔍', 'title' => __('SEO', 'api-isarud'), 'desc' => __('Yoast/RankMath', 'api-isarud')],
                ['icon' => '📧', 'title' => __('Pazarlama', 'api-isarud'), 'desc' => __('E-posta, popup, sepet', 'api-isarud')],
                ['icon' => '🧾', 'title' => __('E-Fatura', 'api-isarud'), 'desc' => __('GIB e-Arsiv Portal', 'api-isarud')],
                ['icon' => '🏪', 'title' => __('POS', 'api-isarud'), 'desc' => __('Fiziksel magaza', 'api-isarud')],
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
            <a href="<?php echo admin_url('admin.php?page=isarud-ecosystem'); ?>" class="button-primary" style="padding:10px 24px"><?php _e('E-ticaret Rehberi', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=4'); ?>" class="button" style="padding:10px 24px"><?php _e('Sonraki Adim', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 4): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">
        <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a2e"><?php _e('4. Cloud Sync ile Baglantinizi Kurun', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:13px;margin-bottom:20px"><?php _e('Cloud Sync sayesinde WooCommerce magazaniz isarud.com hesabinizla otomatik senkronize olur. Urunlerinizi ve siparislerinizi her cihazdan takip edin.', 'api-isarud'); ?></p>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px;margin-bottom:20px">
            <h3 style="margin:0 0 12px;font-size:15px;color:#15803d"><?php _e('Her Yerden Erisim', 'api-isarud'); ?></h3>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
                <?php
                $platforms = [
                    ['icon' => '🌐', 'name' => 'isarud.com', 'desc' => __('Web tarayici', 'api-isarud')],
                    ['icon' => '🖥️', 'name' => 'Windows / macOS', 'desc' => __('Masaustu uygulama', 'api-isarud')],
                    ['icon' => '📱', 'name' => 'iOS / Android', 'desc' => __('Mobil uygulama (yakinda)', 'api-isarud')],
                    ['icon' => '🔌', 'name' => 'WooCommerce', 'desc' => __('Bu eklenti', 'api-isarud')],
                    ['icon' => '🛍️', 'name' => 'Shopify', 'desc' => __('Shopify uygulamasi', 'api-isarud')],
                    ['icon' => '⚡', 'name' => 'REST API', 'desc' => __('Ozel entegrasyon', 'api-isarud')],
                ];
                foreach ($platforms as $p): ?>
                <div style="display:flex;align-items:center;gap:8px;padding:8px;background:#fff;border-radius:6px">
                    <span style="font-size:18px"><?php echo $p['icon']; ?></span>
                    <div>
                        <p style="margin:0;font-size:12px;font-weight:600;color:#333"><?php echo $p['name']; ?></p>
                        <p style="margin:0;font-size:10px;color:#888"><?php echo $p['desc']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <a href="<?php echo admin_url('admin.php?page=isarud-cloud-sync'); ?>" class="button-primary" style="padding:10px 24px"><?php _e('Cloud Sync Ayarlari', 'api-isarud'); ?> &rarr;</a>
            <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=5'); ?>" class="button" style="padding:10px 24px"><?php _e('Sonraki Adim', 'api-isarud'); ?> &rarr;</a>
        </div>
    </div>

    <?php elseif ($step === 5): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px;text-align:center">
        <div style="width:56px;height:56px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg style="width:28px;height:28px;color:#358a4f" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 style="margin:0 0 8px;font-size:22px;color:#1a1a2e"><?php _e('Hazirsiniz!', 'api-isarud'); ?></h2>
        <p style="color:#666;font-size:14px;margin-bottom:24px;max-width:450px;display:inline-block"><?php _e('Isarud kurulumu tamamlandi. Artik yaptirim taramasi yapabilir, pazaryerlerinizi yonetebilir ve e-ticaretinizi buyutebilirsiniz.', 'api-isarud'); ?></p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px;max-width:500px;margin-left:auto;margin-right:auto">
            <a href="<?php echo admin_url('admin.php?page=isarud'); ?>" style="background:#358a4f;color:#fff;padding:14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600">
                <?php _e('Dashboard', 'api-isarud'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=isarud-marketplaces'); ?>" style="background:#1a1a2e;color:#fff;padding:14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600">
                <?php _e('Pazaryerleri', 'api-isarud'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=isarud-ecosystem'); ?>" style="background:#185fa5;color:#fff;padding:14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600">
                <?php _e('Rehber', 'api-isarud'); ?>
            </a>
        </div>
        <button id="isarud-dismiss-welcome" class="button" style="padding:8px 20px"><?php _e('Karsilama ekranini kapat', 'api-isarud'); ?></button>
        <p style="margin-top:12px;font-size:11px;color:#aaa"><?php _e('Isarud > Ayarlar sayfasindan karsilama ekranini tekrar acabilirsiniz.', 'api-isarud'); ?></p>
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
        <a href="<?php echo admin_url('admin.php?page=isarud-welcome&step=' . ($step - 1)); ?>" style="font-size:12px;color:#888;text-decoration:none">&larr; <?php _e('Onceki adim', 'api-isarud'); ?></a>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="<?php echo admin_url('admin.php?page=isarud'); ?>" style="font-size:12px;color:#888;text-decoration:none"><?php _e('Atla, Dashboard\'a git', 'api-isarud'); ?></a>
    </div>
    <?php endif; ?>

</div>
