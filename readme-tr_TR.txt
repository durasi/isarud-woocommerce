=== API Isarud Tüm Pazar Yerleri Ticaret Entegrasyonu ===
Contributors: durasi
Tags: marketplace, trendyol, etsy, hepsiburada, n11
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 6.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Trendyol, Etsy, Hepsiburada, N11, Amazon ve Pazarama için tek çatı altında pazaryeri entegrasyonu. WooCommerce'i 6 pazaryeri ile bağlayın, otomatik ürün gönderimi yapın.

== Description ==

**API Isarud** WooCommerce için en kapsamlı ücretsiz çoklu pazaryeri entegrasyon eklentisidir. Trendyol, Etsy, Hepsiburada, N11, Amazon SP-API ve Pazarama'yı tek WordPress paneline bağlar. Yaptırım taraması, çift yönlü stok sync, otomatik ürün gönderimi (auto-export), sipariş yönetimi, iade ve fatura, müşteri soruları — hepsi %100 ücretsiz.

= Yeni: Otomatik Ürün Gönderimi (Auto-Export) =

WooCommerce'de yeni bir ürün eklediğinizde veya mevcut bir ürünü güncellediğinizde, ürün **otomatik olarak tüm bağlı pazaryerlerine gönderilir** — manuel işlem gerektirmeden. Trendyol, Etsy, N11, Hepsiburada ve daha fazlası için tek tıkla aktif edin.

* Toggle ile aç/kapa (her ürün, her pazaryeri için)
* Manuel toplu gönderim butonu (mevcut ürünleri tek seferde aktarmak için)
* Otomatik kategori ve marka eşleştirme kontrolü
* Async batch işleme (Trendyol için 1000 ürüne kadar tek istek)

= 6 Pazaryeri Tam Entegrasyonu =

**Trendyol** (modern API ile yeniden yazıldı)
* 8 sekmeli modern yönetim paneli (Listings, Marka & Kategori, Stok & Fiyat, Siparişler, İadeler, Sorular, Fatura, Webhook)
* Modal ile bağlantı: WordPress'ten "Trendyol ile Bağlan" → mağaza seç → tek tıkla bağla
* Stok ve fiyat senkronizasyonu (otomatik veya manuel)
* Ürün yükleme (WC → Trendyol) ve çekme (Trendyol → WC)
* Sipariş aktarma + otomatik durum güncelleme (Hazırlanıyor/Kargoda/İptal)
* İade/talep yönetimi (onaylama/reddetme)
* Fatura linki gönderme (otomatik + manuel)
* Müşteri soruları (WP admin'den görüntüle + yanıtla)
* Marka arama + kategori ağacı + zorunlu attribute'lar
* Webhook CRUD (yenilik bildirimleri)

**Etsy** (8-Phase tam entegrasyon, OAuth 2.0)
* Listing CRUD (oluşturma, güncelleme, silme, aktif/pasif)
* Görsel yükleme + sıralama + silme (10 görsel/listing)
* Bölümler (sections) yönetimi + kişiselleştirme
* 9+7 locale çeviri (EN/DE/FR/ES/IT/JA/KO/PT/NL/RU + IT-IT/PT-BR/ZH-TW vb.)
* Kargo profili CRUD + sipariş bazında kargo bildirimi
* Mağaza ayarları + duyurular + tatil modu
* Stok yönetimi + istatistikler (görüntülenme, favoriler)
* Satılan ürünler raporu
* İade politikaları yönetimi

**N11** (modern REST API ile yeniden yazıldı)
* 6 sekmeli yönetim paneli (Ürünler, Kategoriler, Stok & Fiyat, Siparişler, İşlemler, Ayarlar)
* Modal ile bağlantı: mağaza seç → tek tıkla bağla
* Async task tracking sistemi (taskId pattern - PROCESSED/IN_QUEUE/REJECT)
* Ürün CRUD, kategori ağacı + öznitelikler
* Stok ve fiyat senkronizasyonu (max 1000 SKU/batch)
* Sipariş aktarma + güncelleme
* Auto-Export desteği

**Hepsiburada**
* Stok ve fiyat senkronizasyonu
* Ürün yükleme ve çekme
* Sipariş aktarma ve durum güncelleme
* İade/talep yönetimi + fatura linki gönderme

**Amazon SP-API** ve **Pazarama**
* Stok sync, sipariş aktarma, fiyat güncelleme

= Yaptırım Taraması =

Müşterilerinizi ve şirketlerinizi 8 küresel listeden **32.500+ yaptırım kaydına** karşı tarayın: OFAC SDN, OFAC Consolidated, EU, UN, UK HMT, Canada SEMA, Australia DFAT ve World Bank. Bulanık eşleşme, ayarlanabilir eşik değeri ve tam denetim izi.

= 16 Dil Desteği =

Tam çevrili admin paneli ve müşteri arayüzü: Türkçe, İngilizce, Almanca, Fransızca, İspanyolca, İtalyanca, Portekizce, Hollandaca, Lehçe, Rusça, Arapça, Çince, Japonca, Korece, Hintçe, Endonezce.

= Cloud Sync (isarud.com) =

WooCommerce verilerinizi isarud.com hesabınıza senkronize edin:
* Web (isarud.com)
* iOS (App Store)
* Android (Play Store - yakında)
* Windows 11 (Microsoft Store)
* macOS (DMG)
* Chrome / Firefox tarayıcı eklentisi

Tek tıkla bağlantı, otomatik API anahtarı, multi-device erişim.

= Ek Özellikler =

* CSV import/export (Excel uyumlu, UTF-8 BOM)
* Varyasyon sync (beden, renk)
* Kategori ve attribute eşleştirme
* Dropshipping (tedarikçi yönetimi + otomatik sipariş iletimi)
* Affiliate (referans kodu + komisyon takibi)
* B2B (özel rol, toptan fiyat, minimum miktar, vergi alanları)
* Müşteri segmentasyonu (RFM analizi)
* Sepet kurtarma (3-aşama email + kupon)
* Pop-up kampanyaları (çıkış niyeti, zamanlı, kaydırma)
* Email marketing automation
* TCMB döviz kuru modülü
* GIB e-Fatura / e-Arşiv entegrasyonu
* Cross-sell / Upsell otomasyonu
* HPOS uyumlu

== Installation ==

1. Eklenti dosyalarını `/wp-content/plugins/api-isarud/` dizinine yükleyin veya doğrudan WordPress.org'dan kurun
2. **Eklentiler** menüsünden eklentiyi etkinleştirin
3. Başlangıç rehberi için **Isarud → Dashboard** sayfasına gidin
4. Pazaryeri bağlantısı için **Isarud → Pazar Yerleri** sayfasına gidin, "Bağlan" butonuna tıklayın
5. (İsteğe bağlı) **Isarud → Cloud Sync** ile isarud.com hesabınıza bağlanın

== Frequently Asked Questions ==

= Bu eklenti gerçekten ücretsiz mi? =
Evet. Premium sürüm, özellik kısıtlaması ve gizli maliyet olmadan %100 ücretsiz.

= Hangi pazaryerleri destekleniyor? =
Trendyol, Etsy, Hepsiburada, N11, Amazon SP-API ve Pazarama.

= Auto-Export nasıl çalışır? =
WooCommerce'de bir ürün ekler veya günceller iken hook'lar otomatik tetiklenir. Eklenti, etkin tüm pazaryerlerine paralel olarak ürün payload'ını gönderir. Trendyol/N11 için isarud.com bridge üzerinden async batch API çağrılır, batch_request_id veya taskId ile durum takibi yapılır.

= Pazaryeri satıcı hesabı gerekli mi? =
Evet. Bağlamak istediğiniz pazaryerlerinde aktif satıcı hesaplarınız olmalıdır.

= WooCommerce gerekli mi? =
Pazaryeri özellikleri için WooCommerce gereklidir. Yaptırım taraması WooCommerce olmadan da çalışır.

= Çift yönlü stok sync nasıl çalışır? =
Pazaryerinde satış olduğunda webhook WordPress sitenizi bilgilendirir. WooCommerce stoğu değiştiğinde WP Cron ile pazaryerine geri senkronize edilir.

= Etsy bağlantısı nasıl yapılır? =
Etsy uygulama onayı (Etsy Developer Approval) gerektirir. Bağlantı modalında OAuth 2.0 ile yetkilendirme yapılır.

= N11 async task nedir? =
N11 ürün/stok/fiyat güncellemeleri async çalışır. Her işlem için bir taskId döner (PROCESSED/IN_QUEUE/REJECT). N11 yönetim panelindeki "İşlemler" sekmesinden durumu takip edebilirsiniz.

= Destek nereden alabilirim? =
[isarud.com](https://isarud.com) adresini ziyaret edin veya [GitHub](https://github.com/durasi/isarud-woocommerce) üzerinden sorun bildirin.

== Screenshots ==

1. 6 pazaryeri için marka renkli kartlar — modern yönetim paneli
2. Trendyol bağlantı modal'ı — mağaza seçim ekranı
3. Trendyol 8-sekmeli yönetim paneli
4. Etsy 8-sekmeli yönetim paneli
5. N11 6-sekmeli yönetim paneli (Tasks sekmesi async takip)
6. Auto-Export toggle ve manuel toplu gönderim
7. WordPress Dashboard widget'ı
8. Yaptırım tarama arayüzü

== Changelog ==

= 6.7 =
* Yeni: eBay pazaryeri entegrasyonu — ürün, sipariş, finans ve analitik yönetimi
* eBay OAuth (19 kapsam) isarud.com bulut köprüsü üzerinden yönetilir
* eBay yönetim paneli 4 sekme (Ürünler, Siparişler, Finans, Analitik)

= 6.6.9 =
* Yeni: Pazaryerleri için kargo ek ücreti ayarı (TL/USD/EUR/GBP)
* Yeni: Otomatik komisyon + kargo birleşik fiyat hesaplama
* İyileştirme: Sunucu reverse-sync ile kargo ayarı senkronizasyonu
* Tarih: 2026-05-16


= 6.6.8 =
* Pazarama Marketplace tam entegrasyon (9 özellik: stok, fiyat, yükleme, içe aktarma, sipariş, iade, marka, soru, webhook)
* Pazarama OAuth 2.0 client_credentials doğrulama
* Pazarama 6-sekme yönetim paneli (#6B3FA0 mor tema)
* Marketplaces sayfasında modern kart (mor gradient)
* 16 dil yerelleştirme (Türkçe + İngilizce + 14 Haiku çeviri)
* Bridge pattern: export_to_pazarama, import_pazarama_orders, update_pazarama_status
* WooCommerce → Pazarama: ürün yükleme, sipariş içe aktarma, durum senkron
* Modern kart marketplaces için eski config form kaldırıldı




= 6.6.7 — 10 Mayıs 2026 =
* Amazon SP-API tam entegrasyon: bridge pattern, 7 sekmeli admin arayüzü, 16 AJAX endpoint, connect-URL
* Pazar Yeri API sayfasında Amazon modern kart (Etsy → Amazon → Hepsiburada → N11 → Trendyol sırası)
* Amazon API bilgileri sunucuda (LWA OAuth flow, manuel API key girmeye gerek yok)
* Amazon 7 sekme: Ürünler, Kategoriler, Stok, Siparişler, İadeler, Kargo, Ayarlar
* Amazon arayüzü 16 dilde
* 12 backend endpoint (durum, listing, sipariş, FBA stok, rapor — 3 aktif, 9 test satıcı bekleniyor)
* Bridge pattern: export_to_amazon, import_amazon_orders, update_amazon_status

= 6.6.6 — 10 Mayıs 2026 =
* Hepsiburada tam entegrasyon: bridge pattern, 7 sekmeli admin arayüzü, 17 AJAX endpoint, connect-URL
* Pazar Yeri API sayfasında Hepsiburada modern kart (Etsy → Hepsiburada → N11 → Trendyol sırası)
* Hepsiburada API bilgileri sunucuya taşındı (Cloud Sync bridge) — güvenlik iyileştirmesi
* Hepsiburada 7 sekme: Ürünler, Kategoriler, Stok, Siparişler, İadeler, Kargo, Ayarlar
* Hepsiburada arayüzü 16 dilde
* Tüm eski doğrudan Hepsiburada API çağrıları kaldırıldı

= 6.6.2 — 10 Mayıs 2026 =
* N11: SOAP API referansları kaldırıldı; ürün dışa aktarma, sipariş içe aktarma ve durum güncellemeleri artık tamamen modern REST API kullanır
* N11: Pazaryeri kartı modern dashboard kartına yükseltildi - durum göstergesi ve doğrudan yönetim linkiyle
* Pazaryeri listesi: Etsy özellik etiketleri tutarlı aktif stiline güncellendi (Trendyol/N11 tasarımıyla uyumlu)
* Kod temizliği: Tüm eski api.n11.com/ws/ SOAP çağrıları kaldırıldı

= 6.6.1 =
* **YENİ: N11 entegrasyonu modern REST API ile yeniden yazıldı**
* Eski SOAP API'den modern REST API'ye geçiş (api.n11.com/ms/* + /rest/*)
* 6 sekmeli yönetim paneli: Ürünler, Kategoriler, Stok & Fiyat, Siparişler, İşlemler, Ayarlar
* Mağaza seçim modal'ı + isarud.com bridge ile bağlantı kurulumu
* Async task tracking sistemi (taskId pattern - PROCESSED/IN_QUEUE/REJECT)
* Ürün CRUD, kategori ağacı + öznitelikler, sipariş yönetimi, stok/fiyat senkronizasyonu
* Auto-Export desteği (WooCommerce → N11 otomatik gönderim)
* 16 dil desteği

= 6.6.0 =
* **Trendyol entegrasyonu modern API ile yeniden yazıldı**
* 8 sekmeli modern yönetim paneli (Listings, Marka & Kategori, Stok & Fiyat, Siparişler, İadeler, Sorular, Fatura, Webhook)
* Modal ile bağlantı akışı
* 24 AJAX handler
* **Otomatik Ürün Gönderimi (Auto-Export) sistemi**
* Async batch product create endpoint (1000 ürün/batch)
* Bridge pattern: WP plugin → isarud.com API → Trendyol resmi API

= 6.5.1 =
* Cleanup: v6.5.0'da yanlışlıkla commit edilmiş `.bak2` dosyası kaldırıldı

= 6.5.0 =
* **Etsy 8-Phase tam entegrasyon**
* OAuth 2.0 yetkilendirme akışı
* 8 phase: listing CRUD, görseller, sections, çeviriler, kargo, mağaza, stok, iadeler

= 6.4.5 =
* Cleanup: api-isarud.php.clean geçici dosyası kaldırıldı

= 6.4.4 =
* KRİTİK acil hotfix: Etsy yönetim sayfası restore

= 6.4.0 - 6.4.3 =
* Etsy entegrasyonu erken sürümleri

= 6.2.x =
* Trendyol erken entegrasyon

= 6.0.x =
* Welcome screen + GIB e-Fatura + Cross-sell + Sepet kurtarma + Pop-up

= 5.x =
* RFM segmentasyon + B2B + TCMB + Modern dashboard

= 4.x =
* Çift yönlü stok sync + webhook + HPOS

= 3.0.0 =
* Cloud Sync ile isarud.com bağlantısı

= 2.x =
* 6 pazaryeri stok sync + dropshipping + affiliate

= 1.0.0 =
* İlk sürüm — yaptırım tarama

== Upgrade Notice ==

= 6.6.1 =
N11 entegrasyonu modern REST API ile yeniden yazıldı. Auto-Export, 6 sekmeli admin paneli, 16 dil desteği, async task tracking. N11 bağlantınızı yeniden kurmanız gerekebilir.

= 6.6.0 =
Trendyol entegrasyonu yeni API ile yeniden yazıldı. Auto-Export sistemi.

= 6.5.0 =
Etsy 8-Phase tam entegrasyon. OAuth 2.0 ile yetkilendirme yapın.
