=== API Isarud Trade Compliance ===
Contributors: durasi
Tags: sanctions, compliance, OFAC, screening, marketplace
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: trunk
License: GPLv2 or later

Yaptırım taraması + pazar yeri stok senkronizasyonu + dropshipping + affiliate. Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy desteği. Isarud API.

== Description ==

API Isarud Trade Compliance, tek bir eklentide yaptırım taraması, pazar yeri stok senkronizasyonu, dropshipping ve affiliate yönetimi sunar.

= 1. Yaptırım Taraması (Isarud API) =
* Her WooCommerce siparişini 32.000+ yaptırım kaydında otomatik tarama
* OFAC SDN, AB, BM, İngiltere HMT, Kanada SEMA, Dünya Bankası
* Yaptırımlı kuruluşlar için sipariş engelleme (opsiyonel)
* E-posta uyarıları + sipariş meta box
* Manuel tarama butonu

= 2. Pazar Yeri Stok Senkronizasyonu =
* Trendyol — Supplier API ile fiyat ve stok senkronizasyonu
* Hepsiburada — Listing External API ile stok yükleme
* N11 — SOAP tabanlı ürün senkronizasyonu
* Amazon SP-API — envanter feed gönderimi
* Pazarama — fiyat ve stok güncelleme
* Etsy — v3 API ile listing envanter senkronizasyonu

= 3. Otomatik Senkronizasyon (YENİ v2.2) =
* WP Cron tabanlı otomatik sync: 15dk / 1 saat / 6 saat / günlük
* Pazar yeri bazlı açma/kapama
* Dashboard'da son sync zamanı gösterimi

= 4. Fiyat Margin/Markup (YENİ v2.2) =
* Pazar yerine gönderirken yüzdesel veya sabit tutar ekleme
* Örnek: WooCommerce fiyatı + %15 = Trendyol fiyatı
* Her pazar yeri için ayrı margin ayarı

= 5. Toplu Senkronizasyon (YENİ v2.2) =
* Tüm WooCommerce ürünlerini tek tuşla senkronize edin
* İlerleme çubuğu ile takip
* Başarılı/hatalı sayıları

= 6. Dropshipping (YENİ v2.2) =
* Tedarikçi yönetimi (isim, e-posta, API bilgileri)
* Siparişleri tedarikçiye otomatik iletme (API veya e-posta)
* Tedarikçi komisyon oranı takibi

= 7. Affiliate Pazarlama (YENİ v2.2) =
* Affiliate oluşturma ve referans kodu atama
* ?ref=KOD ile 30 günlük cookie takibi
* Otomatik komisyon hesaplama
* Satış ve kazanç istatistikleri

= Özellikler =
* Dashboard: tarama, sync, marketplace istatistikleri
* Bağlantı test butonu (her pazar yeri)
* Senkronizasyon günlüğü
* WooCommerce HPOS uyumlu
* Türkçe arayüz

== Installation ==

1. Eklentiyi /wp-content/plugins/ dizinine yükleyin
2. Eklentiyi etkinleştirin
3. Isarud > Yaptırım Taraması — isarud.com API anahtarınızı girin
4. Isarud > Pazar Yeri API — pazar yeri kimlik bilgilerinizi girin
5. Bağlantıyı Test Et butonu ile doğrulayın

== Changelog ==

= 2.2.0 =
* WP Cron ile otomatik senkronizasyon (15dk/1saat/6saat/günlük)
* Pazar yeri bazlı fiyat margin/markup (% veya sabit)
* Toplu senkronizasyon (progress bar ile)
* Dropshipping: tedarikçi yönetimi + otomatik sipariş iletimi
* Affiliate: referans kodu, cookie takibi, komisyon hesaplama
* Türkçe arayüz (tüm admin sayfaları)

= 2.1.0 =
* Sanctions + marketplace birleşik
* Trendyol/Hepsiburada API düzeltmeleri
* Bağlantı testi + screening/sync log tabloları

= 2.0.0 =
* Isarud API ile tamamen yeniden yazıldı

= 1.0.0 =
* İlk sürüm (pazar yeri API)
