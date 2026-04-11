# API Isarud — Trade Compliance & Marketplace Platform for WooCommerce

**Sanctions screening + full marketplace integration + cloud sync for WooCommerce.**
100% free — no premium version.

🌐 [isarud.com](https://isarud.com) · 📦 [WordPress.org](https://wordpress.org/plugins/api-isarud/) · 🐙 [GitHub](https://github.com/durasi/isarud-woocommerce)

**Current Version:** 6.2.1

---

## Features

### 🛡️ Sanctions Screening
- Screen customers and companies against **32,500+ sanctioned entities**
- 8 global lists: OFAC SDN, OFAC Consolidated, EU, UN, UK HMT, Canada SEMA, Australia DFAT, World Bank
- Fuzzy matching algorithm with configurable threshold
- Automatic screening on new orders (configurable)
- Block orders on match (optional)
- Alert email notifications
- Screening log with full audit trail

### 🏪 Marketplace Integration (6 Platforms)

| Platform | Stock Sync | Price Sync | Upload | Import | Orders | Webhook | Returns | Invoice | Questions | Brands |
|---|---|---|---|---|---|---|---|---|---|---|
| **Trendyol** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Hepsiburada** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — |
| **N11** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| **Amazon SP-API** | ✅ | ✅ | — | — | — | — | — | — | — | — |
| **Pazarama** | ✅ | ✅ | — | — | — | — | — | — | — | — |
| **Etsy** | ✅ | ✅ | — | — | — | — | — | — | — | — |

### 📦 Order Management
- Auto-sync order status: WooCommerce → marketplace (Picking / Shipped / Cancelled)
- Cargo assignment via Trendyol API (Aras, Yurtiçi, MNG, Sürat, etc.)
- Order import from Trendyol, Hepsiburada, N11
- Auto-import orders on schedule (configurable)

### 🔁 Returns & Refunds
- Fetch return/claim requests from Trendyol and Hepsiburada
- Approve or reject returns from WP admin
- Dedicated admin page with marketplace filtering

### 🧾 Invoice Management
- Send invoice links to Trendyol and Hepsiburada
- Auto-send on WooCommerce order completion
- Manual send from admin

### 💬 Customer Questions (Trendyol)
- View and answer customer questions from WP admin
- Filter by status (waiting / answered)

### 🏷️ Brand & Category Lookup (Trendyol)
- Search brand database, browse category tree, get required attributes per category

### 🔄 Two-Way Stock Sync + Webhooks
- Real-time webhook endpoints for Trendyol, Hepsiburada, N11
- WP Cron auto-sync intervals: 15min / 1hr / 6hr / daily
- Webhook security with secret key validation

### 📤 Product Export & Import
- WooCommerce → marketplaces: single or bulk product upload
- Marketplace → WooCommerce: product catalog import
- Variation/variant support
- Category mapping + attribute mapping
- CSV/Excel import and export (Turkish column headers)

### ☁️ Cloud Sync (isarud.com)
- Sync all plugin data to your [isarud.com](https://isarud.com) account
- **9 data modules synced:**
  - Plugin settings (19 option keys)
  - B2B customers (with segments: VIP, loyal, new, at risk, churned, one-time)
  - Abandoned carts (with recovery status)
  - E-invoices (GİB e-Arşiv / e-Fatura)
  - Marketplace credentials
  - Screening logs
  - Sync logs
  - Dropshipping suppliers
  - Affiliates
- **Reverse sync**: Changes made from iOS/web are pulled back to WP
- Auto API key provisioning when connected
- Hourly auto-sync via WP Cron

### 💱 TCMB Dynamic Pricing
- Real-time exchange rates from TCMB XML API (Central Bank of Turkey)
- Configurable margin (percent or fixed)
- Rounding options (none, up, down, nearest)
- Auto-refresh via WP Cron (hourly/daily)

### 🏢 B2B Wholesale Module
- Corporate customer registration with tax ID
- Wholesale pricing per customer
- Minimum order quantity
- Application approval workflow
- Customer segment-based discounts

### 👥 Customer Segmentation & Analytics
- Automatic segmentation: VIP, Loyal, Regular, New, At Risk, Churned, One-Time
- Configurable thresholds per segment
- Discount rates per segment
- Analytics dashboard with segment distribution

### 🛒 Abandoned Cart Recovery
- Automatic detection of abandoned carts
- 3-tier email recovery automation (1hr, 24hr, 72hr — configurable)
- Coupon code generation for recovery emails
- Recovery tracking with rate calculation
- WP Cron scheduled checks

### 🪟 Popup Campaign Manager
- Exit-intent popups
- Timed popups (configurable delay)
- Scroll-triggered popups
- Add-to-cart triggered popups
- Coupon display support
- Campaign scheduling

### 📧 Email Marketing Automation
- Welcome email for new customers
- Post-purchase follow-up
- Review request emails
- Win-back campaigns for inactive customers
- WP Mail integration

### 🧾 E-Invoice / E-Archive (GİB)
- GİB Portal API integration via `mlevent/fatura`
- No special integrator certificate required
- Auto-generate e-archive invoice on order completion
- Draft → Sign → PDF download → Email
- E-Fatura and E-Arşiv support

### 🔀 Cross-sell / Upsell Automation
- Frequently Bought Together (FBT) recommendations
- Order Bump on checkout
- Cart-based product suggestions
- Thank-you page upsells
- Configurable rules engine

### 📊 Statistics & Activity Dashboard
- WP Dashboard widget with real-time screening and sync statistics
- Last 24 hours activity timeline
- Dedicated Statistics page with period filtering (1 Hour / 24 Hours / 7 Days / 30 Days / All)
- Detailed activity log (up to 100 records)

### 🔗 Webhooks
- Webhook endpoints for Trendyol, Hepsiburada, N11, and generic
- Webhook security with secret key
- Real-time stock, order, and price notifications

### 🚚 Dropshipping Module
- Supplier management (name, email, API URL)
- Auto-forward orders to suppliers
- Commission rate tracking
- Active/inactive toggle

### 🤝 Affiliate Module
- Affiliate registration with unique codes
- Commission rate per affiliate
- Sales and commission tracking
- Active/inactive toggle

### 🎨 E-Commerce Infrastructure Guide
- 7-tab setup wizard on first activation
- Payment gateway setup guides (iyzico, PayTR, Param)
- Shipping integration guides (Aras, Yurtiçi, MNG)
- SEO tools guide (Yoast/RankMath)
- Marketing & campaign guides
- Analytics setup guide
- Security checklist

### 🎨 Modern Admin UI
- Brand-colored gradient marketplace cards with feature pills
- 16+ admin pages
- HPOS (High-Performance Order Storage) compatible
- Accordion-style collapsible sections
- Responsive design
- Türkçe + English i18n
- Full changelog (v5.5 → v6.2.0)

---

## Cross-Platform Ecosystem

Isarud is a multi-platform trade compliance and e-commerce management system. The WooCommerce plugin syncs all data to [isarud.com](https://isarud.com), making it accessible from:

| Platform | Status | Features |
|---|---|---|
| **[isarud.com](https://isarud.com)** | ✅ Live | Full dashboard, screening, trade tools, marketplace management |
| **iOS / iPadOS / macOS** | ✅ [App Store](https://apps.apple.com/tr/app/isarud-e-commerce-tools/id6761309959) | 6 tabs, 13 trade tools, WP plugin management (9 modules), Shopify sync |
| **Windows** | ✅ [Microsoft Store](https://www.microsoft.com/store/apps/9PM1Z57C4GT3) | Desktop app via Electron WebView |
| **Shopify** | ✅ Shopify App Store | OAuth install, order screening, webhooks |
| **WooCommerce** | ✅ [WordPress.org](https://wordpress.org/plugins/api-isarud/) | This plugin |

---

## Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 8.0+

## Installation

1. Upload the plugin to `/wp-content/plugins/api-isarud/`
2. Activate through the WordPress Plugins screen
3. Go to **Isarud** in the admin menu
4. Follow the 5-step setup wizard
5. (Optional) Connect to [isarud.com](https://isarud.com) for cloud sync

## License

GPLv2 or later

---

## Contributors & Acknowledgments

- [@orkasoft](https://github.com/orkasoft) — Trendyol User-Agent bug report (#6)

We welcome bug reports, feature requests, and pull requests! If you contribute, you will be credited here.

---

# 🇹🇷 Türkçe

**WooCommerce için yaptırım tarama + tam pazar yeri entegrasyonu + bulut senkronizasyon.** %100 ücretsiz.

### Özellikler
- **Yaptırım Taraması**: 32.500+ kayıt, 8 küresel liste, fuzzy matching, otomatik tarama
- **6 Pazar Yeri**: Trendyol, Hepsiburada, N11, Amazon, Pazarama, Etsy
- **Sipariş Yönetimi**: WC → pazar yeri otomatik durum güncelleme + kargo atama
- **İade Yönetimi**: Trendyol + HB iade talepleri çekme, onaylama, reddetme
- **Fatura Gönderme**: Otomatik + manuel fatura linki
- **Müşteri Soruları**: Trendyol sorularını WP admin'den yanıtlama
- **Marka Arama**: Trendyol marka + kategori + zorunlu attribute'lar
- **Çift Yönlü Stok Sync**: Webhook + WP Cron (15dk / 1sa / 6sa / günlük)
- **Ürün Import/Export**: Çekme + yükleme + varyasyon + kategori eşleştirme
- **CSV İşlemleri**: Excel uyumlu, Türkçe sütunlar
- **☁️ Cloud Sync**: isarud.com entegrasyonu (9 veri modülü + ters senkronizasyon)
- **💱 TCMB Dinamik Fiyatlandırma**: Otomatik döviz kuru, marj, yuvarlama
- **🏢 B2B Toptan Satış**: Kurumsal müşteri, toptan fiyat, vergi no, onay akışı
- **👥 Müşteri Segmentasyonu**: VIP, sadık, yeni, riskli, kayıp, tek seferlik
- **🛒 Sepet Hatırlatma**: 3 kademe e-posta, kupon, otomatik tespit
- **🪟 Popup Kampanya**: Exit-intent, zamanlı, scroll, sepete ekleme tetikleyici
- **📧 E-posta Pazarlama**: Hoşgeldin, satın alma sonrası, geri kazanım
- **🧾 E-Fatura / E-Arşiv**: GİB Portal API entegrasyonu
- **🔀 Cross-sell / Upsell**: FBT, Order Bump, sepet önerisi
- **🚚 Dropshipping**: Tedarikçi yönetimi, otomatik sipariş iletme
- **🤝 Affiliate**: Satış ortağı yönetimi, komisyon takibi
- **Modern UI**: Gradient kartlar, pill badge'ler, accordion, responsive, HPOS uyumlu
- **16+ Admin Sayfası** · **Türkçe + İngilizce** · **Changelog (v5.5→v6.2.0)**
