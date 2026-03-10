# API Isarud Trade Compliance

**Sanctions screening + marketplace stock sync for WooCommerce**

> ⚠️ **Eski "API ISARUD" eklentisini mi kullanıyorsunuz?** Bu eklenti tamamen yeniden yazıldı. v2.1+ sürümüne güncelleyin.
> WordPress.org: [wordpress.org/plugins/api-isarud](https://wordpress.org/plugins/api-isarud/)

---

## Özellikler

### 1. Yaptırım Taraması (Isarud API)
- Her WooCommerce siparişini **32.000+** yaptırım kaydında otomatik tarama
- OFAC SDN, AB, BM, İngiltere HMT, Kanada SEMA, Dünya Bankası
- Yaptırımlı kuruluşlar için sipariş engelleme (opsiyonel)
- E-posta uyarıları + sipariş meta box
- Manuel tarama butonu

### 2. Pazar Yeri Stok Senkronizasyonu
| Pazar Yeri | API | Durum |
|---|---|---|
| **Trendyol** | Supplier API (fiyat + stok) | ✅ |
| **Hepsiburada** | Listing External API | ✅ |
| **N11** | SOAP API | ✅ |
| **Amazon** | SP-API + LWA | ✅ |
| **Pazarama** | REST API | ✅ |
| **Etsy** | v3 API | ✅ |

### 3. v2.2.0 — Yeni Özellikler
- ⏱️ **Otomatik Senkronizasyon** — WP Cron ile 15dk / 1saat / 6saat / günlük
- 💰 **Fiyat Margin/Markup** — Pazar yerine gönderirken %X artır/azalt
- 📊 **Toplu Senkronizasyon** — Tüm ürünleri tek tuşla senkronize et

### 4. v3.0.0 — Planlanan
- 🚚 **Dropshipping** — Tedarikçi yönetimi + otomatik sipariş iletimi
- 🔗 **Affiliate** — Bağlantı oluşturma + komisyon takibi

---

## Kurulum

1. WordPress.org'dan indirin: [wordpress.org/plugins/api-isarud](https://wordpress.org/plugins/api-isarud/)
2. Veya bu repoyu `/wp-content/plugins/` dizinine kopyalayın
3. Eklentiyi etkinleştirin
4. **Isarud > Sanctions Screening** — [isarud.com](https://isarud.com) API anahtarınızı girin
5. **Isarud > Marketplace APIs** — Pazar yeri kimlik bilgilerinizi girin
6. **Bağlantıyı Test Et** butonuyla doğrulayın

## API Anahtarı

Ücretsiz API anahtarı almak için: [isarud.com/account/api-keys](https://isarud.com/account/api-keys)

## Gereksinimler

- WordPress 6.0+
- PHP 8.0+
- WooCommerce 8.0+

## Sürüm Geçmişi

| Sürüm | Tarih | Değişiklikler |
|---|---|---|
| **2.2.0** | Mart 2026 | Otomatik sync (WP Cron), fiyat margin, toplu sync |
| **2.1.0** | Mart 2026 | Sanctions + marketplace birleşik, Trendyol/HB düzeltme |
| **2.0.0** | Mart 2026 | Isarud API ile tamamen yeniden yazıldı |
| **1.0.0** | Ocak 2025 | İlk sürüm (sadece marketplace API) |

## Diğer Entegrasyonlar

- **Magento 2:** [github.com/durasi/isarud-magento](https://github.com/durasi/isarud-magento)
- **Shopify:** [Shopify App Store](https://apps.shopify.com) (Yakında)
- **REST API:** [isarud.com/api-docs](https://isarud.com/api-docs)

## Destek

- 🌐 [isarud.com](https://isarud.com)
- 📧 i@seckin.ws
- 🐛 [GitHub Issues](https://github.com/durasi/isarud-woocommerce/issues)

## Lisans

GPL v2 or later
