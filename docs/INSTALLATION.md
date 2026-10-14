# LuxeFind Search Pro — Installation and configuration

## Requirements

- WordPress 6.2 or higher; PHP 8.1 or higher.
- WooCommerce is optional; required only for product-specific features.
- HTTPS for production sites is recommended.
- Optional external search subscriptions/API keys are supplied and paid by the site owner.

## Install

1. Open WordPress dashboard → **Plugins → Add New → Upload Plugin**.
2. Select `luxefind-search-pro.zip` (the **inner plugin ZIP**, not the entire CodeCanyon buyer package).
3. Install/activate, then open **Settings → LuxeFind Search**.
4. Create a page and insert a **LuxeFind Premium Search** Gutenberg block or shortcode `[luxefind_search]`.
5. Optional: `[luxefind_search mode="site"]`, `[luxefind_search mode="products"]`, `[luxefind_search mode="web"]`.

## Provider setup: wp-config.php, server-only

Add before the `/* That's all, stop editing! */` line, replacing placeholders:

```php
define('LFXSP_BRAVE_API_KEY', 'YOUR_OWN_BRAVE_SEARCH_API_KEY');
// Optional provider request budget per hour. Best-effort, not atomic under concurrency:
define('LFXSP_MAX_PAID_REQUESTS_HOURLY', 200);
// Optional, experimental connector; test your Mojeek plan/contract:
define('LFXSP_MOJEEK_API_KEY', 'YOUR_OWN_MOJEEK_API_KEY');
// Optional LibreTranslate instance, public HTTPS URL, no trailing /translate:
define('LFXSP_LIBRETRANSLATE_URL', 'https://translate.your-company.example');
// Optional:
define('LFXSP_LIBRETRANSLATE_API_KEY', 'YOUR_OWN_TRANSLATION_API_KEY');
```

Use provider keys with least privilege and appropriate quotas. **Never paste API keys into a shortcode, website page, frontend JavaScript, screenshots or a support ticket.**

External provider tabs are disabled until you enable them in Settings. Some Brave API subscription tiers may not cover image/news/video endpoints. Each provider's subscription, rate limits, acceptable use and data handling apply.

## Privacy considerations

Local-only search uses the WordPress site's own published content. Optional web, media, map, knowledge and translation modes forward search terms to third-party providers. External preview images may also be requested by the visitor's browser; leave the disclosure enabled. No user query/IP is stored in the built-in anonymous analytics (only daily counts), but WordPress/server/provider logs remain outside the plugin's control.

## REST API

GET `/wp-json/luxefind/v1/search?q=architecture&mode=site`

Modes: `all`, `site`, `products`, `web`, `images`, `news`, `videos`, `maps`, `knowledge`, `translate`.

Product-only options: `category` (product category slug), `min_price`, `max_price`, `sort` (`relevance`, `newest`, `price_asc`, `price_desc`). Translation: `lang` (`en`,`fr`,`es`,`de`,`it`,`nl`,`pt`,`ar`,`hi`,`tr`,`ru`).

Responses contain `site`, `web`, `media`, optional `knowledge`, optional `instant`, and `warnings`. Rate limiting is best effort. It is a public endpoint and not suitable for searching confidential/private content.

## Troubleshooting

- No results from site: check published posts/pages, correct post types under Settings and exact search terms.
- Product tab missing: activate WooCommerce and enable product search.
- Web provider not configured: define appropriate API key in wp-config.php, enable external search and provider checkbox.
- Rate limit: wait 60 seconds; for production behind proxies/WAF use a shared edge limiter. Do **not** blindly trust X-Forwarded-For headers.
- Translation unavailable: use a publicly reachable, HTTPS LibreTranslate endpoint; local/private addresses are deliberately rejected by WordPress's safe HTTP validation.
- External provider notice: check quotas/permissions/provider service status; remote API errors are intentionally not exposed verbatim to visitors.
- Search results look stale: Settings → LuxeFind Search → Clear cached results.

## Upgrade/uninstall

Install newer plugin versions using WordPress's replacement flow. Uninstall deletes LuxeFind plugin settings, tracked cache keys, scheduled analytics cleanup event, and recent aggregate statistics. Site posts/products are never deleted.

## Migrating from the earlier Seurch WP beta

The updated product has a different plugin directory and shortcode. A compatibility shortcode `[seurch_search]` is registered for existing pages, but new sites should use `[luxefind_search]`. The WordPress settings migration reads the previous beta's `swp_options` if present. Server-side keys must be renamed from `SWP_*` constants to the documented `LFXSP_*` constants. Back up WordPress before installing on a site with earlier betas; migration has not been tested against real production data.

## Custom bangs

Under **Settings → LuxeFind Search → Custom search shortcuts**, configure up to 25 entries such as `docs=https://example.org/?q={query}`. Visitors can then type `!docs indexing`. Only HTTPS templates with a single `{query}` placeholder are stored. Curated `!w`, `!gh`, `!yt` and internal tab shortcuts are built in.

## Metasearch-specific shortcuts & instant answers

Built-in shortcuts include `!w`, `!gh`, `!yt`, `!ddg`, `!so`, `!mdn`, `!npm`, `!pypi`, plus mode bangs such as `!images`, `!news`, `!maps`, `!instant`, `!translate`. Add up to 25 extra HTTPS shortcuts under Settings, one `name=https://provider.example/search?q={query}` per line. This does **not** include the original Seurch 13k+ bang directory.

The dedicated Instant Answers tab supports small safe calculators (e.g. `2+3`, `20% of 150`), unit conversions (`10 km to miles`, `32 f to c`), `rgb(12,34,56)`, `0xff to decimal`, `unix 1700000000`, `uuid`, `flip coin`, `roll dice`, and `random 2 to 10`. No arbitrary code execution or LLM calls.

## Public production limitations

The publicly reachable REST `/luxefind/v1/search` endpoint has a simple per-IP transient throttle. A CDN/WAF edge budget and provider-side quotas are strongly recommended for paid external APIs. User authentication is not required to execute read-only searches.

The Mojeek API uses a query-string `api_key` in server outbound requests per its API documentation. Do not log full provider URLs in public server debug logs. External calls and image previews can share visitor search terms and/or network metadata with external services.
