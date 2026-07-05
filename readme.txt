=== API Isarud Tüm Pazar Yerleri Ticaret Entegrasyonu ===
Contributors: durasi
Tags: marketplace, trendyol, etsy, hepsiburada, n11
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 6.9.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce marketplace integration and product sync for Trendyol, Etsy, Hepsiburada, N11, Amazon, Pazarama, Ciceksepeti and eBay.

== Description ==

**API Isarud is the official WordPress plugin of the Isarud platform (https://isarud.com).** This plugin is for sellers who already run a WooCommerce store. **If you do not have a website, you do not need WordPress:** create a free account at https://isarud.com and connect your Trendyol, Hepsiburada, N11, Pazarama, Ciceksepeti, Etsy and Amazon stores directly from the cloud dashboard - no website required.

**API Isarud** is the most comprehensive free multi-marketplace integration plugin for WooCommerce. It connects Trendyol, Etsy, Hepsiburada, N11, Amazon SP-API, Pazarama, Ciceksepeti and eBay to a single WordPress panel. Sanctions screening, bi-directional stock sync, automatic product export, order management, returns and invoicing, customer questions - all 100% free.

= New: Automatic Product Export (Auto-Export) =

When you add a new product or update an existing one in WooCommerce, the product is **automatically sent to all connected marketplaces** - no manual action required. Enable it with a single click for Trendyol, Etsy, N11, Hepsiburada and more.

* Per-product, per-marketplace on/off toggle
* Manual bulk submit button (to transfer existing products in one go)
* Automatic category and brand mapping checks
* Async batch processing (up to 1000 products in a single request for Trendyol)

= Marketplace Integrations =

**Trendyol** (rewritten with the modern API)
* Modern 8-tab management panel (Listings, Brand & Category, Stock & Price, Orders, Returns, Questions, Invoice, Webhook)
* Modal connect flow: "Connect with Trendyol" from WordPress, pick a store, connect with one click
* Stock and price synchronization (automatic or manual)
* Product upload (WC to Trendyol) and pull (Trendyol to WC)
* Order transfer + automatic status updates (Preparing/Shipped/Cancelled)
* Return/claim management (approve/reject)
* Invoice link submission (automatic + manual)
* Customer questions (view and reply from WP admin)
* Brand search + category tree + required attributes
* Webhook CRUD (event notifications)

**Etsy** (full 8-phase integration, OAuth 2.0)
* Listing CRUD (create, update, delete, activate/deactivate)
* Image upload + ordering + deletion (10 images per listing)
* Shop sections management + personalization
* 9+7 locale translations (EN/DE/FR/ES/IT/JA/KO/PT/NL/RU + IT-IT/PT-BR/ZH-TW etc.)
* Shipping profile CRUD + per-order shipping notifications
* Shop settings + announcements + vacation mode
* Inventory management + statistics (views, favorites)
* Sold listings report
* Return policies management

**N11** (rewritten with the modern REST API)
* 6-tab management panel (Products, Categories, Stock & Price, Orders, Tasks, Settings)
* Modal connect flow: pick a store, connect with one click
* Async task tracking system (taskId pattern - PROCESSED/IN_QUEUE/REJECT)
* Product CRUD, category tree + attributes
* Stock and price synchronization (max 1000 SKUs per batch)
* Order transfer + updates
* Auto-Export support

**Hepsiburada**
* Stock and price synchronization
* Product upload and pull
* Order transfer and status updates
* Return/claim management + invoice link submission

**Ciceksepeti** (new in 6.8)
* Products, stock/price sync with async batch tracking
* Orders, returns (approve/reject/received)
* Customer questions with reply support
* Modal connect flow via isarud.com

**Amazon SP-API** and **Pazarama**
* Stock sync, order transfer, price updates

**eBay** (via isarud.com cloud bridge)
* Products, orders, finances and analytics management

= Sanctions Screening =

Screen your customers and companies against **32,500+ sanctions records** from 8 global lists: OFAC SDN, OFAC Consolidated, EU, UN, UK HMT, Canada SEMA, Australia DFAT and World Bank. Fuzzy matching, adjustable threshold and full audit trail.

= 16 Language Support =

Fully translated admin panel and customer-facing UI: Turkish, English, German, French, Spanish, Italian, Portuguese, Dutch, Polish, Russian, Arabic, Chinese, Japanese, Korean, Hindi, Indonesian.

= Cloud Sync (isarud.com) =

Synchronize your WooCommerce data to your isarud.com account:
* Web (isarud.com)
* iOS (App Store)
* Android (Play Store - coming soon)
* Windows 11 (Microsoft Store)
* macOS (DMG)
* Chrome / Firefox browser extension

One-click connection, automatic API key, multi-device access.

= Additional Features =

* CSV import/export (Excel compatible, UTF-8 BOM)
* Variation sync (size, color)
* Category and attribute mapping
* Dropshipping (supplier management + automatic order forwarding)
* Affiliate (referral codes + commission tracking)
* B2B (custom role, wholesale pricing, minimum quantities, tax fields)
* Customer segmentation (RFM analysis)
* Abandoned cart recovery (3-stage email + coupon)
* Pop-up campaigns (exit intent, timed, scroll)
* Email marketing automation
* Turkish Central Bank (TCMB) exchange rate module
* GIB e-Invoice / e-Archive integration (Turkiye)
* Cross-sell / Upsell automation
* HPOS compatible

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/api-isarud/` directory, or install directly from WordPress.org
2. Activate the plugin through the **Plugins** menu
3. Visit **Isarud > Dashboard** for the getting-started guide
4. Go to **Isarud > Marketplaces** and click "Connect" to link a marketplace
5. (Optional) Connect your isarud.com account via **Isarud > Cloud Sync**

== Frequently Asked Questions ==

= Is this plugin really free? =
Yes. 100% free with no premium version, no feature restrictions and no hidden costs.

= Which marketplaces are supported? =
Trendyol, Etsy, Hepsiburada, N11, Amazon SP-API, Pazarama, Ciceksepeti and eBay.

= How does Auto-Export work? =
Hooks fire automatically when you add or update a product in WooCommerce. The plugin sends the product payload to all enabled marketplaces in parallel. For Trendyol/N11 the async batch API is called through the isarud.com bridge, and status is tracked via batch_request_id or taskId.

= Do I need marketplace seller accounts? =
Yes. You must have active seller accounts on the marketplaces you want to connect.

= Is WooCommerce required? =
WooCommerce is required for the marketplace features. Sanctions screening also works without WooCommerce.

= How does bi-directional stock sync work? =
When a sale happens on a marketplace, a webhook notifies your WordPress site. When WooCommerce stock changes, it is synced back to the marketplace via WP Cron.

= How do I connect Etsy? =
Etsy requires app approval (Etsy Developer Approval). Authorization is done via OAuth 2.0 in the connect modal.

= What is an N11 async task? =
N11 product/stock/price updates run asynchronously. Each operation returns a taskId (PROCESSED/IN_QUEUE/REJECT). You can track the status from the "Tasks" tab in the N11 management panel.

= Where can I get support? =
Visit [isarud.com](https://isarud.com) or open an issue on [GitHub](https://github.com/durasi/isarud-woocommerce).

== Screenshots ==

1. Brand-colored cards for every marketplace - modern management panel
2. Trendyol connect modal - store selection screen
3. Trendyol 8-tab management panel
4. Etsy 8-tab management panel
5. N11 6-tab management panel (async tracking on the Tasks tab)
6. Auto-Export toggle and manual bulk submit
7. WordPress Dashboard widget
8. Sanctions screening interface

== Changelog ==

= 6.9.3 =
* İyileştirme: senkronizasyon sonucu ekranı deneme süresi dolduğunda sayaç yerine net uyarı ve "Planı Yükselt" butonu gösterir.

= 6.9.2 =
* İyileştirme: deneme süresi dolduğunda "Şimdi Senkronize Et" artık anında net uyarı gösterir (yükseltme bağlantısıyla); istek zinciri gereksiz yere devam etmez.

= 6.9.1 =
* Metin: "Her Zaman Ücretsiz / %100 ücretsiz" ifadeleri "30 gün ücretsiz deneme" olarak güncellendi (deneme modeliyle uyum).

= 6.9 =
* Yeni: 30 günlük deneme süresi dolduğunda yönetici panelinde bilgilendirme bannerı gösterilir (yükseltme bağlantısıyla). Plan yenilendiğinde banner otomatik kaldırılır.
* Düzeltme: eklenti sürüm sabiti (ISARUD_VERSION) başlıktaki sürümle hizalandı.

= 6.8 =
* New: Ciceksepeti marketplace integration - products, stock/price sync with async batch tracking, orders, returns (approve/reject/received), customer questions with reply, connect flow
* Fix: two remaining Turkish source strings converted to English originals

= 6.7.2 =
* eBay marketplace card added to the Marketplaces hub page (cloud bridge connection status, management panel link)

= 6.7.1 =
* Fixed: The plugin now displays in the correct language when the site language is one of the 16 supported languages, even before official translation packs are available.
* Fixed: A wholesale minimum order quantity message now uses the correct source text.

= 6.7 =
* New: eBay marketplace integration - product, order, finance and analytics management
* eBay OAuth (19 scopes) managed via isarud.com cloud bridge
* eBay admin page with 4 tabs (Products, Orders, Finances, Analytics)

= 6.6.11 =
* Fixed dashboard widget database error (screening log column name)

= 6.6.10 =
* WordPress 7.0 compatibility verified
* No functional changes

= 6.6.9 =
* New: Shipping fee addition per marketplace (TL/USD/EUR/GBP)
* New: Auto commission + shipping combined pricing
* Improvement: Server reverse-sync for shipping settings
* Date: 2026-05-16

= 6.6.8 =
* Pazarama Marketplace full integration (9 features: stock, price, upload, import, orders, returns, brands, questions, webhook)
* Pazarama OAuth 2.0 client_credentials authentication
* Pazarama 6-tab admin UI with #6B3FA0 purple theme
* Modern card on marketplaces page (purple gradient)
* 16 language localization (Turkish + English + 14 Haiku-translated)
* Bridge pattern: export_to_pazarama, import_pazarama_orders, update_pazarama_status
* WooCommerce to Pazarama: product export, order import, status sync
* Old marketplace config form removed for modern card marketplaces

= 6.6.7 - May 10, 2026 =
* Amazon SP-API full integration: bridge pattern, 7-tab admin UI, 16 AJAX handlers, connect-URL endpoint
* Amazon modern card on Marketplace API page (Etsy > Amazon > Hepsiburada > N11 > Trendyol)
* Amazon credentials moved to server via OAuth (LWA - Login with Amazon flow, no manual API key entry)
* Amazon 7 tabs: Listings, Categories, Stock, Orders, Returns, Cargo, Settings
* Amazon UI in 16 languages
* 12 backend endpoints (status, listings, orders, FBA inventory, reports - 3 active, 9 ready for test seller)
* Bridge pattern: export_to_amazon, import_amazon_orders, update_amazon_status

= 6.6.6 - May 10, 2026 =
* Hepsiburada full integration: bridge pattern, 7-tab admin UI, 17 AJAX handlers, connect-URL endpoint
* Hepsiburada modern card on Marketplace API page (Etsy > Hepsiburada > N11 > Trendyol)
* Hepsiburada credentials moved to server (Cloud Sync bridge) - improved security
* Hepsiburada 7 tabs: Listings, Categories, Stock, Orders, Returns, Cargo, Settings
* Hepsiburada UI in 16 languages
* All legacy direct Hepsiburada API calls eliminated

= 6.6.2 - May 10, 2026 =
* N11: SOAP API references removed; product export, order import, and status updates now use modern REST API exclusively
* N11: Marketplace card promoted to modern dashboard card with status indicator and direct management link
* Marketplace listing: Etsy feature tags refreshed to consistent active styling (matches Trendyol/N11 design)
* Code cleanup: All legacy api.n11.com/ws/ SOAP calls eliminated

= 6.6.1 =
* **New: N11 integration rewritten with the modern REST API**
* Migration from the legacy SOAP API to the modern REST API (api.n11.com/ms/* + /rest/*)
* 6-tab management panel: Products, Categories, Stock & Price, Orders, Tasks, Settings
* Store selection modal + connection setup via the isarud.com bridge
* Async task tracking system (taskId pattern - PROCESSED/IN_QUEUE/REJECT)
* Product CRUD, category tree + attributes, order management, stock/price sync
* Auto-Export support (automatic WooCommerce to N11 submission)
* 16 language support

= 6.6.0 =
* **Trendyol integration rewritten with the modern API**
* Modern 8-tab management panel (Listings, Brand & Category, Stock & Price, Orders, Returns, Questions, Invoice, Webhook)
* Modal connect flow
* 24 AJAX handlers
* **Automatic Product Export (Auto-Export) system**
* Async batch product create endpoint (1000 products per batch)
* Bridge pattern: WP plugin > isarud.com API > official Trendyol API

= 6.5.1 =
* Cleanup: removed a `.bak2` file accidentally committed in v6.5.0

= 6.5.0 =
* **Etsy full 8-phase integration**
* OAuth 2.0 authorization flow
* 8 phases: listing CRUD, images, sections, translations, shipping, shop, inventory, returns

= 6.4.5 =
* Cleanup: removed the temporary api-isarud.php.clean file

= 6.4.4 =
* CRITICAL hotfix: Etsy management page restored

= 6.4.0 - 6.4.3 =
* Early versions of the Etsy integration

= 6.2.x =
* Early Trendyol integration

= 6.0.x =
* Welcome screen + GIB e-Invoice + Cross-sell + Abandoned cart recovery + Pop-ups

= 5.x =
* RFM segmentation + B2B + TCMB exchange rates + Modern dashboard

= 4.x =
* Bi-directional stock sync + webhooks + HPOS

= 3.0.0 =
* isarud.com connection via Cloud Sync

= 2.x =
* Marketplace stock sync + dropshipping + affiliate

= 1.0.0 =
* Initial release - sanctions screening

== Upgrade Notice ==

= 6.6.1 =
The N11 integration has been rewritten with the modern REST API. Auto-Export, 6-tab admin panel, 16 language support, async task tracking. You may need to reconnect your N11 store.

= 6.6.0 =
The Trendyol integration has been rewritten with the new API. Auto-Export system.

= 6.5.0 =
Etsy full 8-phase integration. Authorize via OAuth 2.0.
