=== API Isarud Trade Compliance ===
Contributors: durasi
Tags: sanctions, compliance, OFAC, screening, marketplace
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: trunk
License: GPLv2 or later

WooCommerce siparişleri için otomatik yaptırım taraması + pazar yeri stok senkronizasyonu (Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy). Isarud API ile çalışır.

== Description ==

API Isarud Trade Compliance iki-bir-arada eklentidir:

**1. Yaptırım Taraması (Isarud API)**
* Her WooCommerce siparişini 32.000+ yaptırım kaydında otomatik tarama
* OFAC SDN, AB, BM, İngiltere HMT, Kanada SEMA, Dünya Bankası
* Yaptırımlı kuruluşlar için isteğe bağlı sipariş engelleme
* Eşleşmelerde e-posta uyarısı
* Sipariş detayında uyum durumu göstergesi
* Sipariş başına manuel tarama butonu
* Zaman damgalı tarama günlüğü

**2. Pazar Yeri Stok Senkronizasyonu**
* Trendyol — Supplier API ile fiyat ve stok senkronizasyonu
* Hepsiburada — Listing External API ile stok yükleme
* N11 — SOAP tabanlı ürün senkronizasyonu
* Amazon SP-API — envanter feed gönderimi
* Pazarama — fiyat ve stok güncelleme
* Etsy — v3 API ile listing envanter senkronizasyonu

**Özellikler**
* Tarama istatistikleri ve senkronizasyon özeti içeren dashboard
* Her pazar yeri için bağlantı test butonu
* Başarı/hata takipli senkronizasyon günlüğü
* WooCommerce ürün alanları (barkod, N11 ID, Etsy listing ID)
* WooCommerce HPOS uyumlu

== Installation ==

1. Eklentiyi /wp-content/plugins/ dizinine yükleyin
2. Eklentiyi etkinleştirin
3. Isarud > Yaptırım Taraması menüsüne gidin — isarud.com'dan API anahtarınızı girin
4. Isarud > Pazar Yeri API'leri menüsüne gidin — pazar yeri kimlik bilgilerinizi yapılandırın
5. Her pazar yeri için "Bağlantıyı Test Et" butonunu kullanın

== Frequently Asked Questions ==

= Isarud API anahtarını nereden alabilirim? =
isarud.com'a kayıt olun ve Hesap > API Anahtarları sayfasına gidin.

= WooCommerce olmadan çalışır mı? =
Yaptırım taraması sipariş tarama için WooCommerce gerektirir. Pazar yeri senkronizasyonu WooCommerce ürünleri gerektirir.

= Trendyol API bağlantısı neden başarısız oluyor? =
Supplier ID'nizin doğru olduğundan ve API Key/Secret'ın Trendyol Satıcı Paneli > Entegrasyon bölümünden alındığından emin olun.

= Hepsiburada bağlantısı neden başarısız? =
Yeni Listing External API kimlik bilgilerini (listing-external.hepsiburada.com) kullandığınızdan emin olun, eski mpop API bilgilerini değil.

== Changelog ==

= 2.1.0 =
* Yaptırım taraması + pazar yeri senkronizasyonu tek eklentide birleştirildi
* İstatistikli dashboard eklendi
* Tüm pazar yerleri için bağlantı testi eklendi
* Trendyol API düzeltildi: supplier_id artık endpoint'e ekleniyor
* Hepsiburada API güncellendi: listing-external.hepsiburada.com
* Amazon SP-API + LWA token exchange eklendi
* Senkronizasyon günlüğü veritabanı tablosu eklendi
* Tarama günlüğü veritabanı tablosu eklendi
* WooCommerce HPOS desteği eklendi
* Manuel sipariş tarama butonu eklendi

= 2.0.0 =
* Isarud API v1 ile tamamen yeniden yazıldı
* Yaptırım taraması eklendi

= 1.0.0 =
* İlk sürüm (pazar yeri API)
