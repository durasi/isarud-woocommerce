=== API Isarud Ticaret Uyum ===
Contributors: durasi
Tags: yaptırım, uyum, pazar yeri, trendyol, hepsiburada, n11, woocommerce, stok sync, ticaret
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 6.2.0
License: GPLv2 or later

Yaptırım tarama + 6 pazar yeri entegrasyonu + sipariş yönetimi + iade + fatura + müşteri soruları + marka arama. %100 ücretsiz.

== Description ==

**API Isarud Ticaret Uyum**, WooCommerce için yaptırım tarama ve çoklu pazar yeri entegrasyonu sunan en kapsamlı ücretsiz eklentidir.

= 🛡️ Yaptırım Taraması =
Müşterilerinizi ve şirketlerinizi **32.500+ yaptırım kaydına** karşı tarayın. 8 küresel liste: OFAC SDN, OFAC Consolidated, AB, BM, İngiltere HMT, Kanada SEMA, Avustralya DFAT ve Dünya Bankası. Bulanık eşleştirme ve tam denetim izi.

= 🏪 6 Pazar Yeri Entegrasyonu =
WooCommerce mağazanızı **Trendyol, Hepsiburada, N11, Amazon SP-API, Pazarama ve Etsy** ile bağlayın. Çift yönlü stok sync, fiyat sync, ürün yükleme/çekme, sipariş yönetimi ve daha fazlası.

= 📦 Tam Trendyol Entegrasyonu =
* Stok ve fiyat senkronizasyonu
* Ürün yükleme (WC → Trendyol) ve çekme (Trendyol → WC)
* Sipariş çekme ve otomatik durum güncelleme (Hazırlanıyor/Kargoda/İptal)
* Kargo firması atama
* İade/talep yönetimi (onaylama/reddetme)
* Fatura linki gönderme (otomatik + manuel)
* Müşteri soruları (WP admin'den görüntüle + yanıtla)
* Marka arama + kategori ağacı + zorunlu attribute'lar

= 📦 Hepsiburada Entegrasyonu =
* Stok ve fiyat senkronizasyonu
* Ürün yükleme ve çekme
* Sipariş çekme ve durum güncelleme
* İade/talep yönetimi
* Fatura linki gönderme

= 📦 N11 Entegrasyonu =
* Stok ve fiyat senkronizasyonu (SOAP API)
* Ürün yükleme ve çekme
* Sipariş çekme ve durum güncelleme
* Webhook desteği

= 📊 İstatistikler & Aktivite Paneli =
* WP Başlangıç sayfasında Isarud widget'ı (gerçek zamanlı istatistikler)
* Isarud Dashboard'da son 24 saat aktivite zaman çizelgesi
* Ayrı İstatistikler sayfası — dönem filtreleme (1 Saat / 24 Saat / 7 Gün / 30 Gün / Tümü)
* Detaylı aktivite günlüğü — tarama ve sync geçmişi (100 kayıta kadar)

= 🔄 Sipariş Yönetimi =
WooCommerce sipariş durumu değişiklikleri bağlı pazar yerlerine otomatik olarak yansır. Kargo firması atayın ve siparişleri platformlar arası takip edin.

= 🔁 İade ve Fatura =
Trendyol ve Hepsiburada'dan iade taleplerini çekin, WP admin'den onaylayın veya reddedin. Sipariş tamamlandığında fatura linklerini otomatik gönderin.

= 💬 Müşteri Soruları =
Trendyol müşteri sorularını doğrudan WordPress admin'den görüntüleyin ve yanıtlayın. Duruma göre filtreleyin (bekliyor/yanıtlandı).

= 🏷️ Marka ve Kategori Arama =
Trendyol marka veritabanında arama yapın, tam kategori ağacını gezin ve ürün yükleme için zorunlu attribute'ları alın.

= 🎨 Modern Yönetim Paneli =
Her pazar yeri için marka renkli gradient kartlar, özellik göstergeleri, accordion düzeni ve 16+ yönetim sayfası: Dashboard, İstatistikler, İadeler, Müşteri Soruları ve daha fazlası.

= ☁️ Cloud Sync =
WooCommerce verilerinizi isarud.com hesabınıza senkronize edin. Ürünlerinize, siparişlerinize ve tarama sonuçlarınıza web, mobil ve masaüstü uygulamalarından erişin.

= Ek Özellikler =
* CSV içe/dışa aktarma (Excel uyumlu, UTF-8 BOM)
* Varyasyonlu ürün sync (beden, renk)
* Kategori ve attribute eşleştirme
* Dropshipping (tedarikçi yönetimi + otomatik sipariş iletimi)
* Affiliate (referans kodu + komisyon takibi)
* HPOS uyumlu (High-Performance Order Storage)
* Türkçe + İngilizce dil desteği (253 çeviri string'i)

== Installation ==

1. Eklenti dosyalarını `/wp-content/plugins/api-isarud/` klasörüne yükleyin veya WordPress.org'dan doğrudan kurun
2. **Eklentiler** menüsünden eklentiyi etkinleştirin
3. Başlangıç rehberi için **Isarud → Dashboard** sayfasına gidin
4. **Isarud → Pazar Yeri API** sayfasından pazar yeri API bilgilerinizi girin
5. (İsteğe bağlı) **Isarud → Cloud Sync** ile isarud.com'a bağlanın
6. (İsteğe bağlı) Çift yönlü stok sync için **Isarud → Webhooks** ayarlarını yapın

== Frequently Asked Questions ==

= Bu eklenti gerçekten ücretsiz mi? =
Evet. %100 ücretsiz, premium sürüm yok, özellik sınırlaması yok, gizli maliyet yok.

= Hangi pazar yerleri destekleniyor? =
Trendyol, Hepsiburada, N11, Amazon SP-API, Pazarama ve Etsy.

= Pazar yeri satıcı hesabına ihtiyacım var mı? =
Evet. Bağlanmak istediğiniz pazar yerlerinde aktif satıcı hesaplarınız olmalı. API bilgileri her pazar yerinin satıcı panelinden alınır.

= WooCommerce gerekli mi? =
Pazar yeri özellikleri için WooCommerce gereklidir. Yaptırım taraması WooCommerce olmadan da çalışır.

= Çift yönlü stok sync nasıl çalışır? =
Pazar yerinde satış olduğunda webhook WordPress sitenizi bilgilendirir ve WooCommerce stoğu otomatik güncellenir. WooCommerce stoğu değiştiğinde WP Cron ile pazar yerine geri senkronize olur (ayarlanabilir: 15dk / 1saat / 6saat / günlük).

= Trendyol iadelerini WordPress'ten yönetebilir miyim? =
Evet. İadeler sayfası Trendyol ve Hepsiburada'dan iade/talep isteklerini çekmenize, onaylamanıza ve reddetmenize olanak tanır.

= Ürün varyasyonlarını destekliyor mu? =
Evet. Beden ve renk varyasyonları WooCommerce ve pazar yerleri arasında senkronize edilir.

= Destek nereden alabilirim? =
[isarud.com](https://isarud.com) adresini ziyaret edin veya [GitHub](https://github.com/durasi/isarud-woocommerce) üzerinden sorun bildirin.

== Screenshots ==

1. Marka renkli gradient kartlar ve özellik pill'leri ile pazar yeri ayarları
2. Bulanık eşleştirmeli yaptırım tarama arayüzü
3. Başlangıç rehberi ve aktivite zaman çizelgesi ile Dashboard
4. Dönem filtreleme ve aktivite günlüğü ile İstatistikler sayfası
5. Gerçek zamanlı Isarud istatistikleri ile WP Dashboard widget'ı

== Changelog ==


= 6.0.4 =
* YENI: Karsilama ekrani - 5 adimli kurulum sihirbazi
* YENI: isarud.com/get-started coklu platform bilgilendirme sayfasi
* Ilk aktivasyonda otomatik yonlendirme
* Istege bagli kapatma ve tekrar acma secenegi

= 6.0.3 =
* Rehber guncelleme: Hezarfen (23 kargo firmasi), Kargo Entegrator, POS onerileri
* Dogrulanmis WP.org eklenti slug linkleri

= 6.0.2 =
* YENI: Cross-sell ve Upsell otomasyon modulu
* Birlikte Satin Alinanlar (FBT) - otomatik veya manuel urun onerisi
* Order Bump - odeme sayfasinda tek tikla sepete ekleme
* Sepet sayfasi urun onerileri
* Tesekkur sayfasi urun onerileri + kupon destegi

= 6.0.1 =
* YENI: E-Fatura / E-Arsiv GIB entegrasyonu
* GIB e-Arsiv Portal API (entegrator sertifikasi gerektirmez)
* Siparis tamamlandiginda otomatik fatura olusturma
* Taslak, imza, PDF indirme, e-posta gonderim akisi
* Fatura iptal talebi destegi
* HPOS uyumlu siparis metabox

= 6.0.0 =
* YENI: Sepet hatirlatma otomasyonu (3 kademe e-posta, kupon, WP Cron)
* YENI: Popup kampanya yoneticisi (exit-intent, zamanli, scroll, sepete ekleme)
* YENI: E-posta pazarlama otomasyonu (hosgeldin, satin alma sonrasi, yorum istegi, geri kazanim)
* Eklenti adi guncelleme: API Isarud Tum Pazaryerleri Ticaret Entegrasyonu

= 5.8.0 =
* YENI: Musteri segmentasyonu - VIP, sadik, yeni, risk, kayip, tek seferlik (RFM analizi)
* HPOS + geleneksel WP post tablosu uyumu
* Ayarlanabilir esik degerleri

= 5.7.0 =
* YENI: B2B toptan satis modulu
* Ozel isarud_b2b WordPress rolu
* Urun bazinda toptan fiyat ve minimum siparis adedi
* Varyasyon destegi
* Odeme sayfasinda firma adi, vergi dairesi, vergi no alanlari
* B2B basvuru onay/red sistemi

= 5.6.0 =
* YENI: TCMB doviz kuru modulu
* TCMB XML API ile dinamik fiyatlandirma
* WP Cron otomatik guncelleme (saatlik/gunde 2/gunluk)
* Fiyat marji (yuzde veya sabit) ve yuvarlama
* Kur tipi secimi (doviz/efektif alis/satis)

= 5.5.3 =
* CTA banner: gradient "E-ticaret Altyapinizi Tamamlayin" ve beyaz buton

= 5.5.2 =
* YENI: E-ticaret ekosistem kurulum rehberi (7 sekme)
* Odeme, kargo, SEO, pazarlama, analitik, guvenlik rehberleri
* Kurulu eklentileri otomatik tespit ve durum rozeti

= 5.5.1 =
* WordPress 6.9 uyumlulugu
* Dashboard e-ticaret altyapi durumu (6 kategori)
= 5.5.1 =
* E-ticaret Altyapi Durumu dashboard bolumu (odeme, kargo, SEO, pazarlama, analitik, guvenlik otomatik tespit)
* Tek tikla eklenti kurulum yonlendirmesi
* Ilerleme cubugu (X/6 tamamlandi)

= 5.5.0 =
* Modern dashboard UI — CSS class tabanli, SVG ikonlar, responsive grid, pill badge
* Turkce aciklama: Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy
* Turkce eklenti adi cevirisi
* WP Dashboard widget modernize
* Responsive layout (4-2-1 sutun)

= 5.4.0 =
* Modern dashboard UI yeniden tasarımı — CSS class tabanlı, inline style yok
* Düz metrik kartları, hover efektleri, responsive grid
* Nokta tabanlı durum göstergeleri (emoji yerine)
* Pill badge'li aktivite timeline'ı (Temiz/Eşleşme/Başarılı/Hata)
* SVG ikonlu modernize WP Dashboard widget
* WP Dashboard sayfasında CSS yükleme desteği
* Responsive layout (4→2→1 sütun)
* Gradient hoş geldiniz banner'ı
* Checkmark ikonlu özellik kartları
* Numaralı hover'lı adım kartları

= 5.3.4 =
* Tüm readme açıklamaları eksiksiz özellik listesiyle güncellendi
* Tam Türkçe çeviri (.po/.mo) — 253 string
* İstatistikler menü öğesi düzeltmesi

= 5.3.2 =
* Düzeltme: İstatistikler sayfası menü öğesi admin kenar çubuğuna eklendi

= 5.3.1 =
* YENİ: WordPress Başlangıç widget'ı (gerçek zamanlı istatistikler + aktivite akışı)
* YENİ: Isarud Dashboard'da son 24 saat aktivite zaman çizelgesi
* YENİ: İstatistikler & Aktivite sayfası (dönem filtreleme: 1s/24s/7g/30g/tümü)
* YENİ: Tarama ve sync olaylarını birleştiren detaylı aktivite günlüğü

= 5.3.0 =
* Dashboard kararlı sürüme döndürüldü
* Modern pazar yeri UI korundu

= 5.1.1 =
* Modern pazar yeri UI — marka renkli gradient kartlar, özellik pill'leri
* WordPress 6.9.4 uyumluluğu

= 5.0.0 =
* YENİ: Sipariş durumu güncelleme (WC → pazar yeri otomatik sync)
* YENİ: Kargo firması atama (Trendyol kargo API)
* YENİ: İade yönetimi (Trendyol + HB talepleri — çekme, onaylama, reddetme)
* YENİ: Fatura linki gönderme (otomatik + manuel)
* YENİ: Müşteri soruları (Trendyol — görüntüle + yanıtla)
* YENİ: Marka arama + kategori ağacı + zorunlu attribute'lar
* YENİ: İadeler admin sayfası
* YENİ: Müşteri Soruları admin sayfası

= 4.3.0 =
* Pazar yeri sayfası yeniden tasarım — accordion UI, logolar, özellik badge'leri

= 4.2.0 =
* Dashboard başlangıç rehberi
* WooCommerce menü kontrolü

= 4.1.0 =
* Ürün yükleme (WC → pazar yerleri)
* Attribute eşleştirme UI
* CSV içe/dışa aktarma

= 4.0.0 =
* Çift yönlü stok sync + webhook endpoint'leri
* Sipariş çekme
* Ürün çekme
* Varyasyon sync
* Kategori eşleştirme
* HPOS uyumluluğu

= 3.0.0 =
* isarud.com Cloud Sync
* Otomatik API key

= 2.2.0 =
* 6 pazar yeri stok sync
* WP Cron otomatik sync
* Fiyat margin
* Dropshipping + Affiliate

= 1.0.0 =
* İlk sürüm — yaptırım taraması
