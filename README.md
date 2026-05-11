# API Isarud — Trade Compliance & Marketplace Platform for WooCommerce

**Sanctions screening + full marketplace integration + cloud sync for WooCommerce.**
100% free — no premium version.

🌐 [isarud.com](https://isarud.com) · 📦 [WordPress.org](https://wordpress.org/plugins/api-isarud/) · 🐙 [GitHub](https://github.com/durasi/isarud-woocommerce)

**Current Version:** 6.6.8

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
| **Pazarama** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ |
| **Hepsiburada** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — |
| **N11** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| **Etsy** | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — | — |
| **Amazon SP-API** | ✅ | ✅ | — | — | — | — | — | — | — | — |

### 📦 Order Management
- Auto-sync order status: WooCommerce → marketplace (Picking / Shipped / Cancelled / Delivered)
- Cargo assignment via Trendyol API (Aras, Yurtiçi, MNG, Sürat, etc.)
- Order import from Trendyol, Hepsiburada, N11, Pazarama, Etsy
- Auto-import orders on schedule (configurable)

### 🔁 Returns & Refunds
- Fetch return/claim requests from Trendyol, Hepsiburada, Pazarama, Etsy
- Approve or reject returns from WP admin
- Dedicated admin page with marketplace filtering

### 🧾 Invoice Management
- Send invoice links to Trendyol and Hepsiburada
- Auto-send on WooCommerce order completion
- Manual send from admin

### 💬 Customer Questions (Trendyol, Pazarama)
- Fetch buyer questions
- Reply directly from WP admin
- Auto-notification on new questions

### 🏷️ Brand Management (Trendyol, Pazarama)
- Browse and search marketplace brand catalogs
- Map WooCommerce brand attributes to marketplace brand IDs
- Cached for performance

### 🎨 Etsy Integration (v3 API)
- Listing CRUD (create, update, delete)
- Images, sections, personalization
- Translations (9 listing + 7 shop locales)
- Shipping templates and tracking
- Inventory, statistics, sold listings
- Returns and shop settings

### 🛍️ Pazarama Integration (v2 API)
- OAuth 2.0 client_credentials authentication
- Product CRUD with batch operations
- Category tree and attributes
- Stock and price sync
- Order management with status updates
- Returns handling
- Customer questions
- Brand search
- 9 features marked active in admin dashboard

### ☁️ Cloud Sync
- Sync to [isarud.com](https://isarud.com) account
- Mobile app companion (iOS / Android)
- Centralized multi-store management
- Real-time order notifications via Slack

### 🌐 Internationalization
- 16 language support: Turkish, English, German, French, Spanish, Italian, Japanese, Chinese, Korean, Russian, Arabic, Portuguese, Dutch, Polish, Hindi, Indonesian
- Storefront and admin UI fully translated
- Marketplace-specific terminology localized

---

## Installation

### From WordPress.org (Recommended)
1. Go to **Plugins → Add New** in WP admin
2. Search for "Isarud" or "api-isarud"
3. Click **Install Now** then **Activate**

### Manual Installation
1. Download from [WordPress.org](https://wordpress.org/plugins/api-isarud/) or [GitHub](https://github.com/durasi/isarud-woocommerce)
2. Upload `api-isarud` folder to `/wp-content/plugins/`
3. Activate via **Plugins** menu

---

## Configuration

### Connect Marketplaces
1. Go to **Isarud → Pazaryeri API**
2. Click "Bağlan ve Yönet" on any marketplace card
3. Sign in or create account at isarud.com
4. Select your marketplace store
5. Authorize the connection
6. Configure sync settings per marketplace

### Cloud Sync
1. Go to **Isarud → Cloud Sync**
2. Enter your isarud.com API key
3. Enable auto-sync for orders, stock, returns

---

## Recent Releases

### v6.6.8 (May 11, 2026)
- **Pazarama Marketplace** full integration (9 features)
- Pazarama OAuth 2.0 authentication
- Modern card UI with #6B3FA0 purple theme
- 16 language localization for Pazarama
- Bridge pattern for product export, order import, status sync
- Old marketplace config form removed for modern card marketplaces

### v6.6.7 (May 10, 2026)
- Amazon SP-API integration with LWA OAuth
- Marketplace card cleanup (modern card UI for all 6 platforms)

### v6.6.2 (May 10, 2026)
- N11 SOAP → REST API migration
- Etsy 8-phase integration complete (30 features)

### Earlier versions
See full changelog in [readme.txt](readme.txt).

---

## Support

- **Documentation:** [isarud.com/docs](https://isarud.com)
- **Issues:** [GitHub Issues](https://github.com/durasi/isarud-woocommerce/issues)
- **Email:** support@isarud.com

---

## License

GPL v2 or later

---

**Developed by [Seçkin Sefa Durası](https://github.com/durasi)** · Made with ❤️ in Istanbul