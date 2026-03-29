<?php if (!defined('ABSPATH')) exit;
$webhook_urls = class_exists('Isarud_Webhook') ? Isarud_Webhook::get_webhook_urls() : [];
$mp_meta = [
    'trendyol' => ['color'=>'#f27a1a','grad'=>'linear-gradient(135deg,#f27a1a 0%,#ff9f43 100%)','desc'=>__('Türkiye\'nin en büyük e-ticaret platformu','api-isarud'),'feat'=>['stock','price','upload','import','orders','webhook','returns','invoice','questions','brands']],
    'hepsiburada' => ['color'=>'#ff6000','grad'=>'linear-gradient(135deg,#ff6000 0%,#ff8533 100%)','desc'=>__('Türkiye\'nin öncü online alışveriş sitesi','api-isarud'),'feat'=>['stock','price','upload','import','orders','webhook','returns','invoice']],
    'n11' => ['color'=>'#7b2b8e','grad'=>'linear-gradient(135deg,#7b2b8e 0%,#a855f7 100%)','desc'=>__('Doğan Online pazaryeri. SOAP tabanlı entegrasyon','api-isarud'),'feat'=>['stock','price','upload','import','orders','webhook']],
    'amazon' => ['color'=>'#ff9900','grad'=>'linear-gradient(135deg,#232f3e 0%,#37475a 100%)','desc'=>__('Amazon SP-API ile envanter senkronizasyonu','api-isarud'),'feat'=>['stock','price']],
    'pazarama' => ['color'=>'#00b900','grad'=>'linear-gradient(135deg,#00b900 0%,#34d399 100%)','desc'=>__('Pazarama REST API entegrasyonu','api-isarud'),'feat'=>['stock','price']],
    'etsy' => ['color'=>'#f1641e','grad'=>'linear-gradient(135deg,#f1641e 0%,#ff8a50 100%)','desc'=>__('Etsy v3 API listing senkronizasyonu','api-isarud'),'feat'=>['stock','price']],
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
        <?php foreach ($marketplaces as $key => $mp):
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
