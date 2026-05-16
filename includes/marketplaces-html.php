<?php if (!defined('ABSPATH')) exit;
$webhook_urls = class_exists('Isarud_Webhook') ? Isarud_Webhook::get_webhook_urls() : [];
$mp_meta = [
    'etsy' => ['color'=>'#f1641e','grad'=>'linear-gradient(135deg,#f1641e 0%,#ff8a50 100%)','desc'=>__('Etsy v3 API listing senkronizasyonu','api-isarud'),'feat'=>['stock','price']],
    'n11' => ['color'=>'#7b2b8e','grad'=>'linear-gradient(135deg,#7b2b8e 0%,#a855f7 100%)','desc'=>__('Doğan Online pazaryeri. Modern REST API entegrasyonu','api-isarud'),'feat'=>['stock','price','upload','import','orders','webhook']],
    'trendyol' => ['color'=>'#f27a1a','grad'=>'linear-gradient(135deg,#f27a1a 0%,#ff9f43 100%)','desc'=>__('Türkiye\'nin en büyük e-ticaret platformu','api-isarud'),'feat'=>['stock','price','upload','import','orders','webhook','returns','invoice','questions','brands']],
    'hepsiburada' => ['color'=>'#ff6000','grad'=>'linear-gradient(135deg,#ff6000 0%,#ff8533 100%)','desc'=>__('Türkiye\'nin öncü online alışveriş sitesi','api-isarud'),'feat'=>['stock','price','upload','import','orders','webhook','returns','invoice']],
    'amazon' => ['color'=>'#ff9900','grad'=>'linear-gradient(135deg,#232f3e 0%,#37475a 100%)','desc'=>__('Amazon SP-API ile envanter senkronizasyonu','api-isarud'),'feat'=>['stock','price']],
];
$feat_labels = ['stock'=>'Stok','price'=>'Fiyat','upload'=>'Ürün Yükleme','import'=>'Ürün Çekme','orders'=>'Siparişler','webhook'=>'Webhook','returns'=>'İadeler','invoice'=>'Fatura','questions'=>'Müşteri Soruları','brands'=>'Marka Arama'];
$all_feat = array_keys($feat_labels);
?>
<style>
.imp{max-width:920px}
.imc{border-radius:14px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);transition:box-shadow .2s,transform .1s;border:none;background:#fff}
.imc:hover{box-shadow:0 6px 24px rgba(0,0,0,0.13);transform:translateY(-1px)}
.imh{display:flex;align-items:center;padding:20px 24px;cursor:pointer;user-select:none;gap:20px;color:#fff;position:relative;overflow:hidden}
.imh::before{content:'';position:absolute;top:-50%;right:-50%;width:100%;height:200%;background:radial-gradient(circle,rgba(255,255,255,0.08) 0%,transparent 70%);pointer-events:none}
.imh:hover{filter:brightness(1.05)}
.iml{width:120px;height:42px;background:rgba(255,255,255,0.97);border-radius:10px;display:flex;align-items:center;justify-content:center;padding:6px 12px;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,0.1)}
.iml span{font-weight:800;font-size:18px;letter-spacing:-0.5px}
.imt{flex:1}
.imt h3{margin:0;font-size:18px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.2)}
.imt p{margin:4px 0 0;font-size:12px;color:rgba(255,255,255,0.85);line-height:1.3}
.ims{font-size:11px;padding:5px 14px;border-radius:20px;font-weight:600;flex-shrink:0;letter-spacing:0.3px}
.ims-ok{background:rgba(255,255,255,0.97);box-shadow:0 1px 3px rgba(0,0,0,0.1)}
.ims-off{background:rgba(255,255,255,0.25);color:rgba(255,255,255,0.95);backdrop-filter:blur(4px)}
.ims-err{background:#fef2f2;color:#dc2626}
.ima{font-size:16px;color:rgba(255,255,255,0.7);transition:transform .3s cubic-bezier(.4,0,.2,1);flex-shrink:0}
.imc.open .ima{transform:rotate(180deg)}
.imb{display:none;padding:28px;border-top:none}
.imc.open .imb{display:block}
.imf{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:24px}
.imf span{font-size:11px;padding:5px 12px;border-radius:20px;font-weight:500;display:inline-flex;align-items:center;gap:5px;transition:all .15s}
.imf-on{border:1.5px solid}
.imf-on::before{content:'✓';font-weight:700;font-size:9px}
.imf-off{background:#f9fafb;color:#d1d5db;text-decoration:line-through;border:1px solid #f3f4f6;font-weight:400}
.imf-on:hover{transform:scale(1.05)}
.ims-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px 22px;margin-bottom:18px}
.ims-box h4{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin:0 0 16px}
.img{display:grid;grid-template-columns:160px 1fr;gap:12px 18px;align-items:center}
.img label{font-weight:600;font-size:13px;color:#334155}
.img input[type=text],.img input[type=password],.img input[type=number]{width:100%;max-width:350px;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;transition:all .2s;background:#fff}
.img input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,0.08)}
.img select{padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;background:#fff;cursor:pointer}
.img select:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,0.08)}
.imd{font-size:11px;color:#94a3b8;margin:0 0 14px;display:flex;align-items:center;gap:6px}
.imw{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-top:18px;font-size:12px;color:#166534}
.imw code{background:#bbf7d0;padding:3px 8px;border-radius:6px;font-size:11px;color:#15803d;font-weight:500}
.imw small{color:#22c55e;display:block;margin-top:4px}
.imx{display:flex;gap:10px;margin-top:22px;padding-top:20px;border-top:1px solid #f1f5f9;align-items:center}
.imx .button-primary{border-radius:10px;padding:9px 24px;font-weight:600;font-size:13px;box-shadow:0 2px 6px rgba(0,0,0,0.12);transition:all .15s}
.imx .button-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.18)}
.imx .button{border-radius:10px;padding:9px 18px;font-size:13px}
.imsr{display:flex;gap:8px;align-items:center}
.imsr select,.imsr input{padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px}
</style>

<div class="wrap">
    <h1 style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
        <span style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center">
            <span class="dashicons dashicons-store" style="font-size:20px;width:20px;height:20px;color:#fff"></span>
        </span>
        <?php _e('Pazar Yeri API Ayarları', 'api-isarud'); ?>
    </h1>
    <p style="color:#64748b;margin:0 0 28px 48px;font-size:13px"><?php _e('Pazar yeri API bilgilerinizi girin ve bağlantıyı test edin.', 'api-isarud'); ?></p>

    <div class="imp">
        <?php
// Etsy modern OAuth kart (özel render — generic loop'tan ÖNCE)
$etsy_url = admin_url('admin.php?page=isarud-etsy');
$etsy_cloud_set = !empty(get_option('isarud_cloud_api_key', ''));
?>
<div id="isarud-etsy-modern-card" style="border-radius:14px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;max-width:920px">
    <div style="background:linear-gradient(135deg,#f1641e 0%,#ff8a50 100%);padding:24px;color:#fff;position:relative;overflow:hidden">
        <div style="position:absolute;top:-50%;right:-50%;width:100%;height:200%;background:radial-gradient(circle,rgba(255,255,255,0.08) 0%,transparent 70%);pointer-events:none"></div>
        <div style="display:flex;align-items:center;gap:18px;position:relative">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.97);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.15)">
                <span style="font-weight:800;font-size:22px;color:#f1641e;letter-spacing:-0.5px">Etsy</span>
            </div>
            <div style="flex:1">
                <h3 style="margin:0;font-size:20px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.2)">Etsy</h3>
                <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.92);line-height:1.4"><?php esc_html_e("Etsy v3 API ile mağazanızı yönetin — tek tıkla OAuth bağlantısı, API anahtarı oluşturmaya gerek yok","api-isarud"); ?></p>
            </div>
            <div id="isarud-etsy-modern-status" style="font-size:11px;padding:6px 14px;border-radius:20px;background:rgba(255,255,255,0.25);color:#fff;font-weight:600;backdrop-filter:blur(4px);min-width:80px;text-align:center"><?php esc_html_e("Yükleniyor...","api-isarud"); ?></div>
        </div>
    </div>
    <div style="padding:24px;background:#fff">
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
            <span style="background:#fde4d4;color:#c2410c;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #fed7aa;display:inline-flex;align-items:center;gap:5px">📦 <?php esc_html_e("Listing CRUD","api-isarud"); ?></span>
            <span style="background:#fde4d4;color:#c2410c;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #fed7aa;display:inline-flex;align-items:center;gap:5px">🖼️ <?php esc_html_e("Resim","api-isarud"); ?></span>
            <span style="background:#fde4d4;color:#c2410c;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #fed7aa;display:inline-flex;align-items:center;gap:5px">🚚 <?php esc_html_e("Kargo","api-isarud"); ?></span>
            <span style="background:#fde4d4;color:#c2410c;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #fed7aa;display:inline-flex;align-items:center;gap:5px">📊 <?php esc_html_e("İstatistik","api-isarud"); ?></span>
            <span style="background:#fde4d4;color:#c2410c;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #fed7aa;display:inline-flex;align-items:center;gap:5px">🌍 <?php esc_html_e("Çeviri","api-isarud"); ?></span>
            <span style="background:#fde4d4;color:#c2410c;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #fed7aa;display:inline-flex;align-items:center;gap:5px">↩️ <?php esc_html_e("İade","api-isarud"); ?></span>
        </div>
        <div id="isarud-etsy-modern-action">
        <?php if(!$etsy_cloud_set): ?>
            <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:14px;font-size:13px;color:#92400e">
                ⚠️ <?php printf(esc_html__("Etsy bağlantısı için önce %s yapmanız gerekir.","api-isarud"), '<a href="' . esc_url(admin_url("admin.php?page=isarud-cloud")) . '" style="color:#92400e;font-weight:600">' . esc_html__("Cloud Sync","api-isarud") . '</a>'); ?>
            </div>
        <?php else: ?>
            <a href="<?php echo esc_url($etsy_url); ?>" id="isarud-etsy-modern-btn" class="button button-primary button-hero" style="background:#f1641e;border-color:#f1641e;text-shadow:none;box-shadow:0 2px 6px rgba(241,100,30,0.3);font-weight:600;padding:0 24px;height:44px;line-height:42px">
                🔗 <?php esc_html_e("Etsy ile Bağlan ve Yönet","api-isarud"); ?>
            </a>
            <p style="margin:12px 0 0;font-size:11px;color:#9ca3af">🔒 <?php esc_html_e("OAuth bilgileriniz Isarud sunucusunda güvenle saklanır","api-isarud"); ?></p>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php if($etsy_cloud_set): ?>
<script>
(function($){
    "use strict";
    function escH(t){return String(t||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
    $(document).ready(function(){
        $.get(<?php echo wp_json_encode(admin_url("admin-ajax.php")); ?>, {
            action: "isarud_etsy_status",
            nonce: <?php echo wp_json_encode(wp_create_nonce("isarud_etsy_nonce")); ?>
        }).done(function(r){
            if (r && r.success && r.connected) {
                var shop = (r.data && r.data.shop_name) ? r.data.shop_name : "Etsy";
                $("#isarud-etsy-modern-status").html("✅ <?php echo esc_js(__("Bağlı","api-isarud")); ?>").css({background:"#10b981",color:"#fff"});
                $("#isarud-etsy-modern-action").html(
                    '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#15803d">' +
                    '✅ <strong><?php echo esc_js(__("Bağlı","api-isarud")); ?></strong> — <?php echo esc_js(__("Mağaza:","api-isarud")); ?> <strong>' + escH(shop) + '</strong></div>' +
                    '<a href="<?php echo esc_js($etsy_url); ?>" class="button button-primary" style="background:#f1641e;border-color:#f1641e;font-weight:600;padding:0 24px;height:36px;line-height:34px">📋 <?php echo esc_js(__("Yönet","api-isarud")); ?></a>'
                );
            } else {
                $("#isarud-etsy-modern-status").html("⚪ <?php echo esc_js(__("Bağlı değil","api-isarud")); ?>").css({background:"rgba(255,255,255,0.25)",color:"#fff"});
            }
        }).fail(function(){
            $("#isarud-etsy-modern-status").html("⚠️").css({background:"#fef2f2",color:"#dc2626"});
        });
    });
})(jQuery);
</script>

<?php
$pazarama_url = admin_url('admin.php?page=isarud-pazarama');
$pazarama_cloud_set = !empty(get_option('isarud_cloud_api_key', ''));
?>
<div id="isarud-pazarama-modern-card" style="border-radius:14px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;max-width:920px">
    <div style="background:linear-gradient(135deg,#6B3FA0 0%,#8B5CF6 100%);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="width:42px;height:42px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,0.1)">
                <span style="font-weight:800;font-size:14px;color:#6B3FA0;letter-spacing:-0.5px">pazarama</span>
            </div>
            <div>
                <div style="font-size:18px;font-weight:700;color:#fff">Pazarama</div>
                <div style="font-size:12px;color:#fff;opacity:0.85;margin-top:2px"><?php esc_html_e("İş Bankası iştiraki — Modern REST API","api-isarud"); ?></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <div id="isarud-pazarama-modern-status" style="font-size:11px;padding:6px 14px;border-radius:20px;background:rgba(255,255,255,0.25);color:#fff;font-weight:600;backdrop-filter:blur(4px);min-width:80px;text-align:center"><?php esc_html_e("Yükleniyor...","api-isarud"); ?></div>
        </div>
    </div>
    <div style="padding:16px 24px;background:#fafafa;border-top:1px solid rgba(0,0,0,0.04);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;gap:8px;flex-wrap:wrap;font-size:11px">
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">📦 <?php esc_html_e("Stok","api-isarud"); ?></span>
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">💰 <?php esc_html_e("Fiyat","api-isarud"); ?></span>
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">📤 <?php esc_html_e("Yükleme","api-isarud"); ?></span>
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">📥 <?php esc_html_e("İçe Aktar","api-isarud"); ?></span>
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">📋 <?php esc_html_e("Sipariş","api-isarud"); ?></span>
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">🔄 <?php esc_html_e("İade","api-isarud"); ?></span>
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">💬 <?php esc_html_e("Soru","api-isarud"); ?></span>
            <span style="padding:4px 10px;background:#F3E8FF;color:#6B21A8;border-radius:6px;font-weight:600">🏷️ <?php esc_html_e("Marka","api-isarud"); ?></span>
        </div>
        <div id="isarud-pazarama-modern-action">
            <a href="<?php echo admin_url('admin.php?page=isarud-pazarama'); ?>" id="isarud-pazarama-modern-btn" class="button button-primary button-hero" style="background:#6B3FA0;border-color:#6B3FA0;text-shadow:none;box-shadow:0 2px 6px rgba(107,63,160,0.3);font-weight:600;padding:0 24px;height:44px;line-height:42px">
                🔗 <?php esc_html_e("Pazarama Sayfasına Git","api-isarud"); ?>
            </a>
        </div>
    </div>
</div>

<script>
jQuery(function($){
    $.post(ajaxurl, {
        action: "isarud_pazarama_status",
        nonce: "<?php echo wp_create_nonce('isarud_pazarama_nonce'); ?>"
    }).done(function(r){
        if(r && r.success && r.data && r.data.success){
            $("#isarud-pazarama-modern-status").html("✅ <?php echo esc_js(__('Bağlı','api-isarud')); ?>").css({background:"#10b981",color:"#fff"});
            $("#isarud-pazarama-modern-action").html(
                '<a href="<?php echo admin_url('admin.php?page=isarud-pazarama'); ?>" class="button button-primary" style="background:#6B3FA0;border-color:#6B3FA0;font-weight:600;padding:0 24px;height:36px;line-height:34px">📋 <?php echo esc_js(__('Yönet','api-isarud')); ?></a>'
            );
        } else {
            $("#isarud-pazarama-modern-status").html("⚪ <?php echo esc_js(__('Bağlı değil','api-isarud')); ?>").css({background:"rgba(255,255,255,0.25)",color:"#fff"});
        }
    }).fail(function(){
        $("#isarud-pazarama-modern-status").html("⚠️").css({background:"#fef2f2",color:"#dc2626"});
    });
});
</script>

<?php endif; ?>

<?php
// Trendyol modern kart (özel render — generic loop'tan ÖNCE)
$trendyol_url = admin_url('admin.php?page=isarud-trendyol');
$trendyol_cloud_set = !empty(get_option('isarud_cloud_api_key', ''));
?>
<div id="isarud-amazon-modern-card" style="border-radius:14px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;max-width:920px">
    <div style="background:linear-gradient(135deg,#232f3e 0%,#ff9900 100%);padding:22px 28px;color:#fff;display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0">
            <div style="width:48px;height:48px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-weight:800;font-size:14px;color:#ff9900;letter-spacing:-0.5px">amazon</span>
            </div>
            <div style="min-width:0;flex:1">
                <h3 style="margin:0;font-size:20px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.2)">Amazon SP-API</h3>
                <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.92);line-height:1.4">Amazon Selling Partner API entegrasyonu — listing, sipariş, stok, FBA, iade, rapor yönetimi</p>
            </div>
            <div id="isarud-amazon-modern-status" style="font-size:11px;padding:6px 14px;border-radius:20px;background:rgba(255,255,255,0.25);color:#fff;font-weight:600;backdrop-filter:blur(4px);min-width:80px;text-align:center">Yükleniyor...</div>
        </div>
    </div>
    <div style="padding:18px 28px">
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
            <span style="background:#fff3e0;color:#cc6600;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📦 Listing CRUD</span>
            <span style="background:#fff3e0;color:#cc6600;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📊 Stok &amp; Fiyat</span>
            <span style="background:#fff3e0;color:#cc6600;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📋 Sipariş Yönetimi</span>
            <span style="background:#fff3e0;color:#cc6600;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">🏭 FBA Stok Takibi</span>
            <span style="background:#fff3e0;color:#cc6600;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">↩️ İade</span>
            <span style="background:#fff3e0;color:#cc6600;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📈 Satış Raporları</span>
        </div>
        <div id="isarud-amazon-modern-action">
            <a href="<?php echo admin_url('admin.php?page=isarud-amazon'); ?>" id="isarud-amazon-modern-btn" class="button button-primary button-hero" style="background:#ff9900;border-color:#ff9900;text-shadow:none;box-shadow:0 2px 6px rgba(255,153,0,0.3);font-weight:600;padding:0 24px;height:44px;line-height:42px;color:#232f3e">
                🔗 Amazon SP-API Yönetim Paneli            </a>
        </div>
    </div>
</div>

<script>
(function($){
    "use strict";
    function escH(t){return String(t||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
    $(document).ready(function(){
        $.post("<?php echo admin_url('admin-ajax.php'); ?>", {
            action: "isarud_amazon_status",
            nonce: "<?php echo wp_create_nonce('isarud_amazon_nonce'); ?>"
        }).done(function(r){
            if (r && r.success && r.connected) {
                var name = (r.store && r.store.name) ? r.store.name : "Amazon";
                $("#isarud-amazon-modern-status").html("✅ Bağlı").css({background:"#10b981",color:"#fff"});
                $("#isarud-amazon-modern-action").html(
                    '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#15803d">' +
                    '✅ <strong>Bağlı</strong> — Mağaza: <strong>' + escH(name) + '</strong>' +
                    '</div>' +
                    '<a href="<?php echo admin_url('admin.php?page=isarud-amazon'); ?>" class="button button-primary" style="background:#ff9900;border-color:#ff9900;font-weight:600;padding:0 24px;height:36px;line-height:34px;color:#232f3e">📋 Yönet</a>'
                );
            } else {
                $("#isarud-amazon-modern-status").html("⚪ Bağlı değil").css({background:"rgba(255,255,255,0.25)",color:"#fff"});
                $("#isarud-amazon-modern-action").html(
                    '<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#9a3412">' +
                    '🛒 <strong>Amazon SP-API bağlantısı henüz yok</strong><br>' +
                    '<small>Yönetim Paneli\'nden \"Amazon ile Bağlan\" butonuna tıklayıp Seller Central\'da onay verin (manuel API key girmeye gerek yok — OAuth flow).</small>' +
                    '</div>' +
                    '<a href="<?php echo admin_url('admin.php?page=isarud-amazon'); ?>" class="button button-primary" style="background:#ff9900;border-color:#ff9900;font-weight:600;padding:0 24px;height:36px;line-height:34px;color:#232f3e">🔗 Amazon Sayfasına Git</a>'
                );
            }
        }).fail(function(){
            $("#isarud-amazon-modern-status").html("⚠️").css({background:"#fef2f2",color:"#dc2626"});
        });
    });
})(jQuery);
</script>

<div id="isarud-hepsiburada-modern-card" style="border-radius:14px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;max-width:920px">
    <div style="background:linear-gradient(135deg,#ff6000 0%,#ff8a50 100%);padding:22px 28px;color:#fff;display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0">
            <div style="width:48px;height:48px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-weight:800;font-size:20px;color:#ff6000;letter-spacing:-0.5px">HB</span>
            </div>
            <div style="min-width:0;flex:1">
                <h3 style="margin:0;font-size:20px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.2)">Hepsiburada</h3>
                <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.92);line-height:1.4">Hepsiburada Marketplace API entegrasyonu — listing, sipariş, stok, kategori, iade yönetimi</p>
            </div>
            <div id="isarud-hepsiburada-modern-status" style="font-size:11px;padding:6px 14px;border-radius:20px;background:rgba(255,255,255,0.25);color:#fff;font-weight:600;backdrop-filter:blur(4px);min-width:80px;text-align:center">Yükleniyor...</div>
        </div>
    </div>
    <div style="padding:18px 28px">
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
            <span style="background:#fde4d4;color:#c2410c;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📦 Ürün Yönetimi</span>
            <span style="background:#fde4d4;color:#c2410c;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📊 Stok &amp; Fiyat</span>
            <span style="background:#fde4d4;color:#c2410c;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📋 Sipariş</span>
            <span style="background:#fde4d4;color:#c2410c;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">🏷️ Kategori</span>
            <span style="background:#fde4d4;color:#c2410c;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">↩️ İade</span>
            <span style="background:#fde4d4;color:#c2410c;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">🚚 Kargo</span>
        </div>
        <div id="isarud-hepsiburada-modern-action">
            <a href="<?php echo admin_url('admin.php?page=isarud-hepsiburada'); ?>" id="isarud-hepsiburada-modern-btn" class="button button-primary button-hero" style="background:#ff6000;border-color:#ff6000;text-shadow:none;box-shadow:0 2px 6px rgba(255,96,0,0.3);font-weight:600;padding:0 24px;height:44px;line-height:42px">
                🔗 Hepsiburada Yönetim Paneli            </a>
        </div>
    </div>
</div>

<script>
(function($){
    "use strict";
    function escH(t){return String(t||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
    $(document).ready(function(){
        $.post("<?php echo admin_url('admin-ajax.php'); ?>", {
            action: "isarud_hepsiburada_status",
            nonce: "<?php echo wp_create_nonce('isarud_hepsiburada_nonce'); ?>"
        }).done(function(r){
            if (r && r.success && r.connected) {
                var name = (r.store && r.store.name) ? r.store.name : "Hepsiburada";
                $("#isarud-hepsiburada-modern-status").html("✅ Bağlı").css({background:"#10b981",color:"#fff"});
                $("#isarud-hepsiburada-modern-action").html(
                    '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#15803d">' +
                    '✅ <strong>Bağlı</strong> — Mağaza: <strong>' + escH(name) + '</strong>' +
                    '</div>' +
                    '<a href="<?php echo admin_url('admin.php?page=isarud-hepsiburada'); ?>" class="button button-primary" style="background:#ff6000;border-color:#ff6000;font-weight:600;padding:0 24px;height:36px;line-height:34px">📋 Yönet</a>'
                );
            } else {
                $("#isarud-hepsiburada-modern-status").html("⚪ Bağlı değil").css({background:"rgba(255,255,255,0.25)",color:"#fff"});
                $("#isarud-hepsiburada-modern-action").html(
                    '<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#9a3412">' +
                    '🛒 <strong>Hepsiburada bağlantısı henüz yok</strong><br>' +
                    '<small>isarud.com hesabınızdan Hepsiburada mağazanızı bağlayın, sonra bu sayfada yönetin.</small>' +
                    '</div>' +
                    '<a href="<?php echo admin_url('admin.php?page=isarud-hepsiburada'); ?>" class="button button-primary" style="background:#ff6000;border-color:#ff6000;font-weight:600;padding:0 24px;height:36px;line-height:34px">🔗 Hepsiburada Sayfasına Git</a>'
                );
            }
        }).fail(function(){
            $("#isarud-hepsiburada-modern-status").html("⚠️").css({background:"#fef2f2",color:"#dc2626"});
        });
    });
})(jQuery);
</script>

<div id="isarud-n11-modern-card" style="border-radius:14px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;max-width:920px">
    <div style="background:linear-gradient(135deg,#7b2b8e 0%,#a855f7 100%);padding:22px 28px;color:#fff;display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0">
            <div style="width:48px;height:48px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-weight:800;font-size:22px;color:#7b2b8e;letter-spacing:-0.5px">N11</span>
            </div>
            <div style="min-width:0;flex:1">
                <h3 style="margin:0;font-size:20px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.2)">N11</h3>
                <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.92);line-height:1.4">Doğan Online pazaryeri — modern REST API entegrasyonu, listing, sipariş, stok, kategori yönetimi</p>
            </div>
            <div id="isarud-n11-modern-status" style="font-size:11px;padding:6px 14px;border-radius:20px;background:rgba(255,255,255,0.25);color:#fff;font-weight:600;backdrop-filter:blur(4px);min-width:80px;text-align:center">Yükleniyor...</div>
        </div>
    </div>
    <div style="padding:18px 28px">
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
            <span style="background:#f5e8fa;color:#5b1f6b;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📦 Ürün Yönetimi</span>
            <span style="background:#f5e8fa;color:#5b1f6b;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📊 Stok &amp; Fiyat</span>
            <span style="background:#f5e8fa;color:#5b1f6b;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📋 Sipariş</span>
            <span style="background:#f5e8fa;color:#5b1f6b;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">🏷️ Kategori</span>
            <span style="background:#f5e8fa;color:#5b1f6b;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">🔄 Async Tasks</span>
            <span style="background:#f5e8fa;color:#5b1f6b;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">🔔 Webhook</span>
        </div>
        <div id="isarud-n11-modern-action">
                    <a href="<?php echo admin_url('admin.php?page=isarud-n11'); ?>" id="isarud-n11-modern-btn" class="button button-primary button-hero" style="background:#7b2b8e;border-color:#7b2b8e;text-shadow:none;box-shadow:0 2px 6px rgba(123,43,142,0.3);font-weight:600;padding:0 24px;height:44px;line-height:42px">
                🔗 N11 Yönetim Paneli            </a>
                </div>
    </div>
</div>

<script>
(function($){
    "use strict";
    function escH(t){return String(t||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
    $(document).ready(function(){
        $.post("<?php echo admin_url('admin-ajax.php'); ?>", {
            action: "isarud_n11_status",
            nonce: "<?php echo wp_create_nonce('isarud_n11_status'); ?>"
        }).done(function(r){
            if (r && r.success && r.connected) {
                var name = (r.store && r.store.name) ? r.store.name : "N11";
                $("#isarud-n11-modern-status").html("✅ Bağlı").css({background:"#10b981",color:"#fff"});
                $("#isarud-n11-modern-action").html(
                    '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#15803d">' +
                    '✅ <strong>Bağlı</strong> — Mağaza: <strong>' + escH(name) + '</strong>' +
                    '</div>' +
                    '<a href="<?php echo admin_url('admin.php?page=isarud-n11'); ?>" class="button button-primary" style="background:#7b2b8e;border-color:#7b2b8e;font-weight:600;padding:0 24px;height:36px;line-height:34px">📋 Yönet</a>'
                );
            } else {
                $("#isarud-n11-modern-status").html("⚪ Bağlı değil").css({background:"rgba(255,255,255,0.25)",color:"#fff"});
                $("#isarud-n11-modern-action").html(
                    '<div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#5b1f6b">' +
                    '🛒 <strong>N11 bağlantısı henüz yok</strong><br>' +
                    '<small>isarud.com hesabınızdan N11 mağazanızı bağlayın, sonra bu sayfada yönetin.</small>' +
                    '</div>' +
                    '<a href="<?php echo admin_url('admin.php?page=isarud-n11'); ?>" class="button button-primary" style="background:#7b2b8e;border-color:#7b2b8e;font-weight:600;padding:0 24px;height:36px;line-height:34px">🔗 N11 Sayfasına Git</a>'
                );
            }
        }).fail(function(){
            $("#isarud-n11-modern-status").html("⚠️").css({background:"#fef2f2",color:"#dc2626"});
        });
    });
})(jQuery);
</script>

<div id="isarud-trendyol-modern-card" style="border-radius:14px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);background:#fff;max-width:920px">
    <div style="background:linear-gradient(135deg,#f27a1a 0%,#ff9f43 100%);padding:22px 28px;color:#fff;display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0">
            <div style="width:48px;height:48px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-weight:800;font-size:22px;color:#f27a1a;letter-spacing:-0.5px">TY</span>
            </div>
            <div style="min-width:0;flex:1">
                <h3 style="margin:0;font-size:20px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.2)">Trendyol</h3>
                <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.92);line-height:1.4"><?php esc_html_e("Türkiye'nin en büyük e-ticaret platformu — listing, sipariş, iade, fatura, müşteri soruları, webhook","api-isarud"); ?></p>
            </div>
            <div id="isarud-trendyol-modern-status" style="font-size:11px;padding:6px 14px;border-radius:20px;background:rgba(255,255,255,0.25);color:#fff;font-weight:600;backdrop-filter:blur(4px);min-width:80px;text-align:center"><?php esc_html_e("Yükleniyor...","api-isarud"); ?></div>
        </div>
    </div>
    <div style="padding:18px 28px">
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
            <span style="background:#fef3e8;color:#9a3412;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📦 <?php esc_html_e("Ürün Yönetimi","api-isarud"); ?></span>
            <span style="background:#fef3e8;color:#9a3412;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📊 <?php esc_html_e("Stok & Fiyat","api-isarud"); ?></span>
            <span style="background:#fef3e8;color:#9a3412;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📋 <?php esc_html_e("Sipariş","api-isarud"); ?></span>
            <span style="background:#fef3e8;color:#9a3412;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">↩️ <?php esc_html_e("İade","api-isarud"); ?></span>
            <span style="background:#fef3e8;color:#9a3412;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">💬 <?php esc_html_e("Soru","api-isarud"); ?></span>
            <span style="background:#fef3e8;color:#9a3412;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">📄 <?php esc_html_e("Fatura","api-isarud"); ?></span>
            <span style="background:#fef3e8;color:#9a3412;padding:4px 10px;border-radius:14px;font-size:11px;font-weight:600">🔔 <?php esc_html_e("Webhook","api-isarud"); ?></span>
        </div>
        <div id="isarud-trendyol-modern-action">
        <?php if(!$trendyol_cloud_set): ?>
            <div style="background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;padding:14px;font-size:13px;color:#92400e">
                ⚠️ <?php printf(esc_html__("Trendyol bağlantısı için önce %s yapmanız gerekir.","api-isarud"), '<a href="' . esc_url(admin_url("admin.php?page=isarud-cloud")) . '" style="color:#92400e;font-weight:600">' . esc_html__("Cloud Sync","api-isarud") . '</a>'); ?>
            </div>
        <?php else: ?>
            <a href="<?php echo esc_url($trendyol_url); ?>" id="isarud-trendyol-modern-btn" class="button button-primary button-hero" style="background:#f27a1a;border-color:#f27a1a;text-shadow:none;box-shadow:0 2px 6px rgba(242,122,26,0.3);font-weight:600;padding:0 24px;height:44px;line-height:42px">
                🔗 <?php esc_html_e("Trendyol Yönetim Paneli","api-isarud"); ?>
            </a>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php if($trendyol_cloud_set): ?>
<script>
(function($){
    "use strict";
    function escH(t){return String(t||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
    $(document).ready(function(){
        $.post(<?php echo wp_json_encode(admin_url("admin-ajax.php")); ?>, {
            action: "isarud_trendyol_status",
            nonce: <?php echo wp_json_encode(wp_create_nonce("isarud_trendyol_nonce")); ?>
        }).done(function(r){
            if (r && r.success && r.connected) {
                var name = (r.store && r.store.name) ? r.store.name : "Trendyol";
                var sellerId = r.seller_id || "";
                $("#isarud-trendyol-modern-status").html("✅ <?php echo esc_js(__("Bağlı","api-isarud")); ?>").css({background:"#10b981",color:"#fff"});
                $("#isarud-trendyol-modern-action").html(
                    '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#15803d">' +
                    '✅ <strong><?php echo esc_js(__("Bağlı","api-isarud")); ?></strong> — <?php echo esc_js(__("Mağaza:","api-isarud")); ?> <strong>' + escH(name) + '</strong>' +
                    (sellerId ? ' · Seller ID: <code>' + escH(sellerId) + '</code>' : '') +
                    '</div>' +
                    '<a href="<?php echo esc_js($trendyol_url); ?>" class="button button-primary" style="background:#f27a1a;border-color:#f27a1a;font-weight:600;padding:0 24px;height:36px;line-height:34px">📋 <?php echo esc_js(__("Yönet","api-isarud")); ?></a>'
                );
            } else {
                $("#isarud-trendyol-modern-status").html("⚪ <?php echo esc_js(__("Bağlı değil","api-isarud")); ?>").css({background:"rgba(255,255,255,0.25)",color:"#fff"});
                $("#isarud-trendyol-modern-action").html(
                    '<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;margin-bottom:12px;font-size:13px;color:#9a3412">' +
                    '🛒 <strong><?php echo esc_js(__("Trendyol bağlantısı henüz yok","api-isarud")); ?></strong><br>' +
                    '<small><?php echo esc_js(__("isarud.com hesabınızdan Trendyol mağazanızı bağlayın, sonra bu sayfada yönetin.","api-isarud")); ?></small>' +
                    '</div>' +
                    '<a href="<?php echo esc_js($trendyol_url); ?>" class="button button-primary" style="background:#f27a1a;border-color:#f27a1a;font-weight:600;padding:0 24px;height:36px;line-height:34px">🔗 <?php echo esc_js(__("Trendyol Sayfasına Git","api-isarud")); ?></a>'
                );
            }
        }).fail(function(){
            $("#isarud-trendyol-modern-status").html("⚠️").css({background:"#fef2f2",color:"#dc2626"});
        });
    });
})(jQuery);
</script>

<?php endif; ?>

<?php
// Diğer marketplace'ler (Trendyol, HB, N11, Amazon, Pazarama) - generic render
foreach ($marketplaces as $key => $mp):
    if ($key === "etsy") continue; // Etsy yukarıda özel
    if ($key === "amazon") continue; // Amazon yukarıda özel (modern card)
    if ($key === "hepsiburada") continue; // Hepsiburada yukarıda özel (modern card)
    if ($key === "etsy") continue; // Etsy yukarıda özel (modern card)
    if ($key === "pazarama") continue; // Pazarama yukarıda özel (modern card)
    if ($key === "amazon") continue; // Amazon yukarıda özel (modern card)
    if ($key === "hepsiburada") continue; // Hepsiburada yukarıda özel (modern card)
    if ($key === "n11") continue; // N11 yukarıda özel (modern card)
    if ($key === "trendyol") continue; // Trendyol yukarıda özel
            $row = $saved[$key] ?? null;
            $data = $row ? json_decode($row->credentials, true) : [];
            $is_ok = $row && $row->test_status === 'success';
            $is_err = $row && $row->test_status === 'error';
            $m = $mp_meta[$key] ?? [];
            $color = $m['color'] ?? '#6366f1';
            $grad = $m['grad'] ?? "linear-gradient(135deg,{$color} 0%,{$color}cc 100%)";
            $feats = $m['feat'] ?? [];
        ?>
        <div class="imc" id="mp-<?php echo esc_attr($key); ?>">
            <div class="imh" style="background:<?php echo $grad; ?>" onclick="isarudT('<?php echo esc_attr($key); ?>')">
                <div class="iml"><span style="color:<?php echo esc_attr($color); ?>"><?php echo esc_html($mp['name']); ?></span></div>
                <div class="imt">
                    <h3><?php echo esc_html($mp['name']); ?></h3>
                    <p><?php echo esc_html($m['desc'] ?? ''); ?></p>
                </div>
                <span class="ims <?php echo $is_ok ? 'ims-ok' : ($is_err ? 'ims-err' : 'ims-off'); ?>" <?php if ($is_ok) echo 'style="color:' . esc_attr($color) . '"'; ?>>
                    <?php if ($is_ok): ?>● <?php _e('Bağlı', 'api-isarud'); ?><?php elseif ($is_err): ?>✕ <?php _e('Hata', 'api-isarud'); ?><?php elseif ($row): ?><?php _e('Kaydedildi', 'api-isarud'); ?><?php else: ?><?php _e('Yapılandırılmadı', 'api-isarud'); ?><?php endif; ?>
                </span>
                <span class="ima">▾</span>
            </div>
            <div class="imb">
                <div class="imf">
                    <?php foreach ($all_feat as $f):
                        $has = in_array($f, $feats);
                    ?>
                    <span class="<?php echo $has ? 'imf-on' : 'imf-off'; ?>" <?php if ($has) echo 'style="color:' . esc_attr($color) . ';border-color:' . esc_attr($color) . '40;background:' . esc_attr($color) . '08"'; ?>><?php echo esc_html($feat_labels[$f]); ?></span>
                    <?php endforeach; ?>
                </div>
                <form method="post">
                    <?php wp_nonce_field('isarud_mp'); ?>
                    <input type="hidden" name="marketplace" value="<?php echo esc_attr($key); ?>">
                    <div class="ims-box">
                        <h4><?php _e('API Bilgileri', 'api-isarud'); ?></h4>
                        <?php if (!empty($mp['docs'])): ?><p class="imd">📖 <?php echo esc_html($mp['docs']); ?></p><?php endif; ?>
                        <div class="img">
                            <?php foreach ($mp['fields'] as $fk => $field): ?>
                            <label><?php echo esc_html($field['label']); ?></label>
                            <input type="<?php echo $field['type'] === 'password' ? 'password' : 'text'; ?>" name="cred[<?php echo esc_attr($fk); ?>]" value="<?php echo esc_attr($data[$fk] ?? ''); ?>" placeholder="<?php echo esc_attr($field['label']); ?>">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="ims-box">
                        <h4><?php _e('Senkronizasyon Ayarları', 'api-isarud'); ?></h4>
                        <div class="img">
                            <label><?php _e('Fiyat Margin', 'api-isarud'); ?></label>
                            <div class="imsr">
                                <input type="number" step="0.01" name="price_margin" value="<?php echo esc_attr($row->price_margin ?? 0); ?>" style="width:80px">
                                <select name="price_margin_type" style="width:70px">
                                    <option value="percent" <?php selected($row->price_margin_type ?? 'percent', 'percent'); ?>>%</option>
                                    <option value="fixed" <?php selected($row->price_margin_type ?? '', 'fixed'); ?>>₺ / $</option>
                                </select>
                            </div>
                            <?php /* @@shipping_addition_v1@@ */ ?>
                            <label><?php _e('Kargo Ek Ücreti', 'api-isarud'); ?></label>
                            <div class="imsr">
                                <input type="number" step="0.01" min="0" name="shipping_addition" value="<?php echo esc_attr($row->shipping_addition ?? 0); ?>" style="width:80px" placeholder="0">
                                <select name="shipping_addition_currency" style="width:70px">
                                    <option value="TRY" <?php selected($row->shipping_addition_currency ?? 'TRY', 'TRY'); ?>>₺</option>
                                    <option value="USD" <?php selected($row->shipping_addition_currency ?? 'TRY', 'USD'); ?>>$</option>
                                    <option value="EUR" <?php selected($row->shipping_addition_currency ?? 'TRY', 'EUR'); ?>>€</option>
                                    <option value="GBP" <?php selected($row->shipping_addition_currency ?? 'TRY', 'GBP'); ?>>£</option>
                                </select>
                                <span style="font-size:11px;color:#94a3b8;margin-left:8px"><?php _e('Pazaryeri fiyatına eklenecek tutar', 'api-isarud'); ?></span>
                            </div>
                            <label><?php _e('Oto-Sync', 'api-isarud'); ?></label>
                            <div class="imsr">
                                <select name="auto_sync" style="width:85px">
                                    <option value="0" <?php selected($row->auto_sync ?? 0, 0); ?>><?php _e('Kapalı', 'api-isarud'); ?></option>
                                    <option value="1" <?php selected($row->auto_sync ?? 0, 1); ?>><?php _e('Açık', 'api-isarud'); ?></option>
                                </select>
                                <select name="sync_interval" style="width:100px">
                                    <option value="15min" <?php selected($row->sync_interval ?? 'daily', '15min'); ?>>15 dk</option>
                                    <option value="hourly" <?php selected($row->sync_interval ?? 'daily', 'hourly'); ?>>1 saat</option>
                                    <option value="6hours" <?php selected($row->sync_interval ?? 'daily', '6hours'); ?>>6 saat</option>
                                    <option value="daily" <?php selected($row->sync_interval ?? 'daily', 'daily'); ?>><?php _e('Günlük', 'api-isarud'); ?></option>
                                </select>
                            </div>
                            <?php if ($row && $row->last_sync): ?>
                            <label><?php _e('Son Sync', 'api-isarud'); ?></label>
                            <span style="font-size:12px;color:#64748b"><?php echo esc_html(human_time_diff(strtotime($row->last_sync))) . ' ' . __('önce', 'api-isarud'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($webhook_urls[$key])): ?>
                    <div class="imw">
                        <strong>Webhook URL:</strong> <code><?php echo esc_html($webhook_urls[$key]); ?></code>
                        <small><?php _e('Bu URL\'yi pazar yeri panelinizdeki webhook ayarlarına ekleyin.', 'api-isarud'); ?></small>
                    </div>
                    <?php endif; ?>
                    <div class="imx">
                        <input type="submit" name="isarud_save_marketplace" class="button-primary" value="<?php esc_attr_e('Kaydet', 'api-isarud'); ?>" style="background:<?php echo esc_attr($color); ?>;border-color:<?php echo esc_attr($color); ?>">
                        <button type="button" class="button isarud-test-btn" data-marketplace="<?php echo esc_attr($key); ?>"><?php _e('Bağlantıyı Test Et', 'api-isarud'); ?></button>
                        <span class="isarud-test-result" data-marketplace="<?php echo esc_attr($key); ?>"></span>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
function isarudT(k){var e=document.getElementById('mp-'+k);if(e)e.classList.toggle('open')}
document.addEventListener('DOMContentLoaded',function(){
<?php foreach($marketplaces as $key=>$mp):$row=$saved[$key]??null;if($row&&$row->test_status==='error'):?>document.getElementById('mp-<?php echo esc_js($key);?>')?.classList.add('open');
<?php endif;endforeach;?>});
</script>
