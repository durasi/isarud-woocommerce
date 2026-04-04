=== API Isarud Tüm Pazar Yerleri Ticaret Entegrasyonu ===
Contributors: durasi
Tags: sanctions, compliance, marketplace, trendyol, hepsiburada, n11, woocommerce, stock sync, trade
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 6.2.1
License: GPLv2 or later

Yaptırım tarama + Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy API entegrasyonu + sipariş yönetimi + iade + fatura + müşteri soruları + marka arama. %100 ücretsiz.


== Description ==

**API Isarud Trade Compliance** yaptırım taraması ve çoklu pazar yeri entegrasyonu için en kapsamlı ücretsiz WooCommerce eklentisidir.

= Yaptırım Taraması =
Müşterilerinizi ve şirketlerinizi 8 küresel listeden **32.500+ yaptırım kaydına** karşı tarayın: OFAC SDN, OFAC Consolidated, EU, UN, UK HMT, Canada SEMA, Australia DFAT ve World Bank. Bulanık eşleşme, ayarlanabilir eşik değeri ve tam denetim izi.

= 6 Pazar Yeri Entegrasyonu =
WooCommerce mağazanızı **Trendyol, Hepsiburada, N11, Amazon SP-API, Pazarama ve Etsy** ile bağlayın. Çift yönlü stok sync, fiyat sync, ürün yükleme/çekme, sipariş yönetimi ve daha fazlası.

= Trendyol Tam Entegrasyon =
* Stok ve fiyat senkronizasyonu
* Ürün yükleme (WC → Trendyol) ve çekme (Trendyol → WC)
* Sipariş aktarma ve otomatik durum güncelleme (Hazırlanıyor/Kargoda/İptal)
* Kargo firması atama
* İade/talep yönetimi (onaylama/reddetme)
* Fatura linki gönderme (otomatik + manuel)
* Müşteri soruları (WP admin'den görüntüle + yanıtla)
* Marka arama + kategori ağacı + zorunlu attribute'lar

= Hepsiburada Entegrasyonu =
* Stok ve fiyat senkronizasyonu
* Ürün yükleme ve çekme
* Sipariş aktarma ve durum güncelleme
* İade/talep yönetimi
* Fatura linki gönderme

= N11 Entegrasyonu =
* Stok ve fiyat senkronizasyonu (SOAP API)
* Ürün yükleme ve çekme
* Sipariş aktarma ve durum güncelleme
* Webhook desteği

= İstatistikler ve Aktivite Paneli =
* WP Başlangıç sayfası widget'ı (gerçek zamanlı istatistikler)
* Isarud Dashboard'da son 24 saat aktivite timeline'ı
* Dönem filtrelemeli istatistikler sayfası (1 Saat / 24 Saat / 7 Gün / 30 Gün / Tümü)
* Tarama ve sync geçmişiyle detaylı aktivite günlüğü (100 kayıt)

= Sipariş Yönetimi =
WooCommerce sipariş durumu değişiklikleri otomatik olarak bağlı pazar yerlerine senkronize edilir. Kargo firması atayın ve siparişleri platformlar arası takip edin.

= İade ve Fatura =
Trendyol ve Hepsiburada'dan iade taleplerini çekin, WP admin'den onaylayın veya reddedin. Sipariş tamamlandığında fatura linklerini otomatik gönderin.

= Müşteri Soruları =
Trendyol müşteri sorularını doğrudan WordPress admin panelinden görüntüleyin ve yanıtlayın. Duruma göre filtreleyin (bekleyen/yanıtlanan).

= Marka ve Kategori Arama =
Trendyol marka veritabanında arama yapın, kategori ağacını gezin ve ürün yükleme için zorunlu attribute'ları alın.

= Modern Admin Arayüzü =
Her pazar yeri için marka renkli gradient kartlar, özellik göstergeleri, accordion düzen ve 16+ admin sayfası: Dashboard, İstatistikler, İadeler, Müşteri Soruları ve daha fazlası.

= Cloud Sync =
WooCommerce verilerinizi isarud.com hesabınıza senkronize edin. Ürünlerinize, siparişlerinize ve tarama sonuçlarınıza web, mobil ve masaüstü uygulamalardan erişin.

= Ek Özellikler =
* CSV import/export (Excel uyumlu, UTF-8 BOM)
* Varyasyon sync (beden, renk)
* Kategori ve attribute eşleştirme
* Dropshipping (tedarikçi yönetimi + otomatik sipariş iletimi)
* Affiliate (referans kodu + komisyon takibi)
* HPOS uyumlu (Yüksek Performanslı Sipariş Depolama)
* Türkçe + İngilizce i18n, tam .po/.mo dosyaları

== Installation ==

1. Eklenti dosyalarını `/wp-content/plugins/api-isarud/` dizinine yükleyin veya doğrudan WordPress.org'dan kurun
2. **Eklentiler** menüsünden eklentiyi etkinleştirin
3. Başlangıç rehberi için **Isarud → Dashboard** sayfasına gidin
4. Pazar yeri API bilgilerinizi **Isarud → Pazar Yeri API** sayfasına girin
5. (İsteğe bağlı) **Isarud → Cloud Sync** ile isarud.com hesabınıza bağlanın
6. (İsteğe bağlı) Çift yönlü stok sync için **Isarud → Webhooks** sayfasından webhook kurun

== Frequently Asked Questions ==

= Bu eklenti gerçekten ücretsiz mi? =
Evet. Premium sürüm, özellik kısıtlaması ve gizli maliyet olmadan %100 ücretsiz.

= Hangi pazar yerleri destekleniyor? =
Trendyol, Hepsiburada, N11, Amazon SP-API, Pazarama ve Etsy.

= Pazar yeri satıcı hesabı gerekli mi? =
Evet. Bağlamak istediğiniz pazar yerlerinde aktif satıcı hesaplarınız olmalıdır. API bilgileri her pazar yerinin satıcı panelinden alınır.

= WooCommerce gerekli mi? =
Pazar yeri özellikleri için WooCommerce gereklidir. Yaptırım taraması WooCommerce olmadan çalışır.

= Çift yönlü stok sync nasıl çalışır? =
Pazar yerinde satış olduğunda webhook WordPress sitenizi bilgilendirir ve WooCommerce stoğu otomatik güncellenir. WooCommerce stoğu değiştiğinde WP Cron ile pazar yerine geri senkronize edilir (ayarlanabilir: 15dk / 1 saat / 6 saat / günlük).

= Trendyol iadelerini WordPress'ten yönetebilir miyim? =
Evet. İadeler sayfası Trendyol ve Hepsiburada'dan iade/talep isteklerini WP admin'den çekmenize, onaylamanıza ve reddetmenize olanak tanır.

= Ürün varyasyonlarını destekliyor mu? =
Evet. Beden ve renk varyasyonları WooCommerce ile pazar yerleri arasında senkronize edilir.

= Destek nereden alabilirim? =
[isarud.com](https://isarud.com) adresini ziyaret edin veya [GitHub](https://github.com/durasi/isarud-woocommerce) üzerinden sorun bildirin.

== Screenshots ==

1. Marka renkli gradient kartlar ve özellik göstergeleri ile pazar yeri ayarları
2. Bulanık eşleşme ile yaptırım tarama arayüzü
3. Başlangıç rehberi ve aktivite timeline'ı ile Dashboard
4. Dönem filtreleme ve aktivite günlüğü ile istatistikler sayfası
5. Gerçek zamanlı Isarud istatistikleri ile WP Dashboard widget'ı

== Changelog ==


= 6.0.4 =
* NEW: Welcome screen - 5-step setup wizard for first-time users
* NEW: isarud.com/get-started multi-platform landing page
* Activation redirect to welcome screen
* Dismissible welcome with restart option

= 6.0.3 =
* Ecosystem guide updated: Hezarfen (23 cargo companies), Kargo Entegrator, POS recommendations
* Replaced generic cargo links with verified WP.org plugin slugs

= 6.0.2 =
* NEW: Cross-sell and Upsell automation module
* Frequently Bought Together (FBT) - auto or manual product suggestions
* Order Bump at checkout - one-click add to cart
* Cart page upsell recommendations
* Thank you page product suggestions with coupon support
* Product edit page FBT field integration

= 6.0.1 =
* NEW: E-Fatura / E-Arsiv GIB integration
* GIB e-Arsiv Portal API (no integrator certificate required)
* Auto invoice on order completion
* Draft, sign, PDF download, email send workflow
* Invoice cancel request support
* HPOS compatible order metabox
* Custom DB table for invoice tracking

= 6.0.0 =
* NEW: Abandoned cart recovery (3-tier email, coupon, WP Cron)
* NEW: Popup campaign manager (exit-intent, timed, scroll, add-to-cart triggers)
* NEW: Email marketing automation (welcome, post-purchase, review request, win-back)
* Plugin name updated: API Isarud Tum Pazaryerleri Ticaret Entegrasyonu

= 5.8.0 =
* NEW: Customer segmentation - VIP, loyal, new, at-risk, lost, one-time (RFM analysis)
* HPOS + legacy WP post table compatibility
* Adjustable thresholds for segment rules

= 5.7.0 =
* NEW: B2B wholesale module
* Custom isarud_b2b WordPress role
* Per-product wholesale price and minimum order quantity
* Variation support for B2B pricing
* Checkout fields: company name, tax office, tax number
* B2B application approval/rejection system

= 5.6.0 =
* NEW: TCMB currency exchange rate module
* Dynamic pricing from TCMB XML API
* WP Cron auto-update (hourly/twice-daily/daily)
* Price margin (percent or fixed) and rounding
* Exchange rate type selection (forex/banknote buying/selling)

= 5.5.3 =
* CTA banner: gradient "E-ticaret Altyapinizi Tamamlayin" with white button

= 5.5.2 =
* NEW: E-commerce ecosystem setup guide (7 tabs)
* Payment, shipping, SEO, marketing, analytics, security guides
* Auto-detect installed plugins with status badges

= 5.5.1 =
* WordPress 6.9 compatibility (Tested up to: 6.9)
* E-commerce infrastructure status on dashboard (6 categories)

= 5.5.0 =
* Turkish plugin name and description
* Complete Turkish readme with FAQ and screenshots
* Dashboard UI improvements
= 5.4.1 =
* Turkish short description with marketplace names (Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy)

= 5.4.0 =
* Modern dashboard UI redesign — CSS class-based, no inline styles
* Flat metric cards with hover effects and responsive grid
* Status indicators with dot-based design (replaces emoji)
* Activity timeline with pill badges (Clean/Match/Success/Error)
* Modernized WP Dashboard widget with SVG icons
* CSS loaded on WP Dashboard page for widget styling
* Responsive layout (4→2→1 columns on smaller screens)
* Welcome banner with gradient and CTA buttons
* Feature cards with checkmark icons (green/blue)
* Numbered step cards with hover effects

= 5.3.4 =
* Updated all readme descriptions with complete feature list
* Complete Turkish translation (.po/.mo) with 253 strings
* Statistics menu item fix

= 5.3.2 =
* Fixed: Statistics page menu item added to admin sidebar
* Statistics page now accessible from Isarud menu

= 5.3.1 =
* NEW: WordPress Dashboard widget with real-time statistics and activity feed
* NEW: Last 24 hours activity timeline on Isarud Dashboard
* NEW: Statistics & Activity page with period filtering (1h/24h/7d/30d/all)
* NEW: Detailed activity log combining screening and sync events

= 5.3.0 =
* Reverted dashboard to stable version
* Kept modern marketplace UI

= 5.1.1 =
* Modern marketplace UI with brand gradient cards, colored feature pills, hover effects
* Tested up to WordPress 6.9.4

= 5.0.0 =
* NEW: Order status sync (WC → marketplace automatic Picking/Shipped/Cancelled)
* NEW: Cargo company assignment (Trendyol shipping companies API)
* NEW: Returns management (Trendyol + Hepsiburada claims — fetch, approve, reject)
* NEW: Invoice link sending (Trendyol + Hepsiburada — auto + manual)
* NEW: Customer questions (Trendyol — view + answer from WP admin)
* NEW: Brand search (Trendyol brand API + category tree + required attributes)
* NEW: Returns admin page
* NEW: Customer Questions admin page

= 4.3.0 =
* Marketplace page redesign with accordion UI, logos, feature badges

= 4.2.0 =
* Dashboard getting started guide
* WooCommerce menu gating

= 4.1.0 =
* Product export (WC → marketplaces)
* Attribute mapping UI
* CSV import/export (Excel compatible UTF-8 BOM)

= 4.0.0 =
* Two-way stock sync + webhook endpoints
* Order import from marketplaces
* Product import from marketplaces
* Variation sync (size, color)
* Category mapping
* HPOS compatibility

= 3.0.0 =
* Cloud Sync with isarud.com
* Auto API key when connected

= 2.2.0 =
* 6 marketplace stock sync
* WP Cron auto-sync
* Price margin/markup
* Dropshipping + Affiliate modules

= 1.0.0 =
* Initial release with sanctions screening
