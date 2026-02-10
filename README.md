# Clerk.io for Magento 2

The official [Clerk.io](https://clerk.io) extension for Magento 2. It connects your store to Clerk.io's AI platform, giving you personalized search, product recommendations, and visitor tracking out of the box.

**Version:** 4.8.9 · **PHP:** 7.4+ · **Magento:** 2.3+

---

## What It Does

This extension handles three things:

1. **Feeds your product data to Clerk.io** — Exposes REST-style endpoints (`/clerk/product`, `/clerk/order`, `/clerk/category`, etc.) that the Clerk.io platform calls to sync your catalog. Also pushes real-time updates when products are saved or deleted.

2. **Renders Clerk.io on the frontend** — Injects [Clerk.js](https://docs.clerk.io/docs/clerkjs-quick-start) on every page, which handles search results, live search dropdowns, recommendation sliders, exit-intent popups, and tracking.

3. **Tracks visitor behavior** — Logs page views, clicks, cart contents, and completed orders so Clerk.io's AI can learn and improve results.

### Feature Overview

| Feature | What it does |
|---------|-------------|
| **Search** | Replaces Magento's native search results page with Clerk.io's. Supports faceted filtering. |
| **Live Search** | Type-ahead dropdown on the search field. Configurable selector, categories, suggestions count. |
| **Recommendations** | Sliders on product, category, cart, and home pages ("also bought", "alternatives", "trending", etc.) |
| **Powerstep** | After add-to-cart, shows a page or popup with "keep shopping" recommendations. Works by overriding `Magento\Checkout\Controller\Cart\Add` via a DI preference. |
| **Exit Intent** | Overlay triggered when the visitor moves to leave the page. |
| **Sales Tracking** | `<span>` on the checkout success page that logs the order to Clerk.io. |
| **Basket Tracking** | Intercepts Magento's cart AJAX calls via a patched `XMLHttpRequest` to keep Clerk.io in sync with the cart. |
| **Real-Time Sync** | Observers on `catalog_product_save_after` and `catalog_product_delete_after_done` push changes to the Clerk.io API instantly. Also tracks order returns via credit memo observer. |

---

## Installation

**Composer (recommended):**

```bash
composer require clerk/magento2
bin/magento module:enable Clerk_Clerk
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

After install, go to **Stores > Configuration > Clerk > Configuration** and enter your API keys from [my.clerk.io](https://my.clerk.io).

Full setup guide: [help.clerk.io/integrations/magento-2/get-started](https://help.clerk.io/integrations/magento-2/get-started/)

---

## How It Works Under the Hood

### Data Feed Endpoints

All under the `clerk/` frontend route. Authentication legacy public/private key. Auth logic is in `Controller/AbstractAction.php`.

| Endpoint | What it returns |
|----------|----------------|
| `/clerk/product` | Products with prices (incl/excl tax), stock, images, categories, child product data, tier prices, visibility |
| `/clerk/order` | Orders with line items, quantities, email, customer ID |
| `/clerk/category` | Categories with parent/child relationships and URLs |
| `/clerk/customer` | Customers with configurable attributes (if enabled) |
| `/clerk/page` | CMS pages |
| `/clerk/version` | Magento version, extension version, PHP version |

All paginated endpoints accept `page`, `limit`, `orderby`, `order`, `fields` parameters.

There are also diagnostic endpoints (`/clerk/getconfig`, `/clerk/setconfig`, `/clerk/rotatekey`.

---

## Structure

```
├── Controller/
│   ├── AbstractAction.php          ← Base class: auth, pagination, scope resolution
│   ├── Product/Index.php           ← Product data feed
│   ├── Order/Index.php             ← Order data feed
│   ├── Category/Index.php          ← Category data feed
│   ├── Customer/Index.php          ← Customer data feed
│   ├── Page/Index.php              ← CMS page data feed
│   ├── Checkout/Cart/Add.php       ← Overrides native add-to-cart (powerstep)
│   ├── Cart/Added.php              ← Powerstep page controller
│   ├── Powerstep/Popup.php         ← Powerstep popup AJAX
│   └── Adminhtml/Dashboard/        ← Admin dashboard iframe controllers
│
├── Model/
│   ├── Config.php                  ← All config path constants
│   ├── Api.php                     ← Curl-based Clerk.io API client
│   └── Adapter/Product.php         ← Builds the product data payload (prices, stock, images, children)
│
├── Observer/
│   ├── ProductSaveAfterObserver.php          ← Real-time sync on product save
│   ├── ProductDeleteAfterDoneObserver.php    ← Real-time removal on product delete
│   ├── SalesOrderCreditmemoSaveAfterObserver ← Track returned orders
│   ├── CheckoutCartAddProductCompleteObserver ← Trigger powerstep popup
│   ├── CheckoutCartUpdateItemsAfterObserver  ← Basket tracking to Clerk API
│   └── LayoutLoadBeforeObserver.php          ← Inject Clerk search layout handle
│
├── Block/
│   ├── Tracking.php                ← Clerk.js init block
│   ├── LiveSearch.php              ← Live search config block
│   ├── Result.php                  ← Search results page block
│   ├── SalesTracking.php           ← Order tracking block
│   ├── Powerstep.php               ← Powerstep page block
│   ├── PowerstepPopup.php          ← Powerstep popup block
│   ├── ExitIntent.php              ← Exit intent block
│   └── Widget/Content.php          ← Recommendations widget (product/category/cart)
│
├── etc/
│   ├── module.xml                  ← Module declaration (depends on Magento_CatalogSearch)
│   ├── di.xml                      ← DI preference overriding Cart\Add controller
│   ├── events.xml                  ← Global observers (product save/delete)
│   ├── config.xml                  ← Default config values
│   ├── widget.xml                  ← Clerk content widget definition
│   ├── csp_whitelist.xml           ← CSP rules for api/cdn/custom.clerk.io
│   ├── adminhtml/system.xml        ← Admin config fields (this is where all settings live)
│   └── frontend/
│       ├── routes.xml              ← Frontend routes: clerk/*, checkout/*
│       └── events.xml              ← Frontend observers (layout, cart, powerstep)
│
├── view/frontend/
│   ├── layout/
│   │   ├── default.xml             ← Adds tracking, live search, exit intent to every page
│   │   ├── clerk_result_index.xml  ← Replaces native search with Clerk search
│   │   ├── catalog_product_view.xml ← Product page recommendations
│   │   ├── catalog_category_view.xml ← Category page recommendations
│   │   ├── checkout_cart_index.xml  ← Cart page recommendations
│   │   └── checkout_onepage_success.xml ← Sales tracking
│   └── templates/
│       ├── tracking.phtml          ← Clerk.js loader + config + basket tracking
│       ├── livesearch.phtml        ← Live search span
│       ├── result.phtml            ← Search results page
│       ├── sales_tracking.phtml    ← Order tracking span
│       └── widget.phtml            ← Recommendation slider span
│
├── Helper/Image.php                ← Product image URL builder
├── i18n/                           ← Translations (en_US, da_DK, es_ES, it_IT)
├── registration.php                ← Module registration
└── composer.json                   ← Package: clerk/magento2
```

---

## Customizing & Extending

If you need to customize the extension, here are the parts to be careful with.

**`tracking.phtml` — be very careful.** This template loads Clerk.js and configures the entire integration. It also intercepts Magento's cart AJAX calls to keep basket tracking in sync with Clerk.io. If you override this template, make sure you keep the Clerk.js initialization and the `Clerk('config', {...})` call intact, or nothing will work. You can disable basket tracking in admin under **Synchronization > Collect Baskets**.

**`etc/di.xml` — the add-to-cart override.** The extension replaces Magento's `Magento\Checkout\Controller\Cart\Add` controller entirely with its own version (`Clerk\Clerk\Controller\Checkout\Cart\Add`) to make the powerstep work. This means:
- If another extension also tries to override `Cart\Add` via a DI preference, one of them will win and the other will break. Magento only allows one preference per class.
- If you need to modify add-to-cart behavior, create a plugin on `Clerk\Clerk\Controller\Checkout\Cart\Add` — not on the original Magento class.
- If the powerstep is causing conflicts and you don't need it, you can disable it in admin, but the DI preference is still active.

**`Controller/AbstractAction.php` — the authentication layer.** All data feed endpoints (`/clerk/product`, `/clerk/order`, etc.) go through this base controller which handles JWT token validation and API key authentication. Don't plugin `around` the `dispatch()` method unless you fully understand the auth flow — you could accidentally expose your product feed publicly or break the sync.

**The Clerk.js `<span>` data attributes.** The search results, live search, and recommendation blocks work by outputting `<span class="clerk" data-template="@..." data-products="[...]">` tags. Clerk.js finds these spans and replaces them with rendered content. If you change the `data-*` attribute names or the `class="clerk"` marker, Clerk.js won't find them and nothing will render.

---

## Troubleshooting

- **Extension not showing up:** Run `bin/magento module:status Clerk_Clerk`. If disabled, run `bin/magento module:enable Clerk_Clerk && bin/magento setup:upgrade`.
- **Search not working:** Check API keys in admin config. Open browser console and look for Clerk.js errors. Verify `clerk/search/enabled` is "Yes".
- **Products missing from feed:** Visit `/clerk/product?limit=1` (with auth) to check the feed. Verify product visibility settings in the Clerk config match what you expect.
- **Real-time sync not working:** Check `clerk/product_synchronization/use_realtime_updates` is enabled. Check `var/log/clerk_log.log` for errors.
- **Powerstep conflicting with another extension:** Both may be overriding `Magento\Checkout\Controller\Cart\Add`. You'll need to resolve the DI preference conflict.

Enable logging at **Stores > Configuration > Clerk > Logging** — set level to "All" and destination to "File" to write to `var/log/clerk_log.log`.

---

## Links

- [Setup Guide](https://help.clerk.io/integrations/magento-2/get-started/)
- [Configuration Docs](https://help.clerk.io/integrations/magento-2/extension/)
- [Data Sync Docs](https://help.clerk.io/integrations/magento-2/sync-data/)
- [Clerk.js Docs](https://docs.clerk.io/docs/clerkjs-quick-start)
- [Design Template Language](https://docs.clerk.io/docs/clerkjs-template-language)
- [API Reference](https://docs.clerk.io/reference)
- [Dashboard](https://my.clerk.io)

---

## Contributing

1. Fork and branch from `master`
2. Test against a Magento 2 instance
3. Open a PR with a clear description

Issues & feature requests: [github.com/clerkio/clerk-magento2/issues](https://github.com/clerkio/clerk-magento2/issues)

---

## Support

- [help.clerk.io](https://help.clerk.io) · [support@clerk.io](mailto:support@clerk.io) · [status.clerk.io](https://status.clerk.io)
