=== LuxeFind Search Pro ===
Contributors: luxefind
Tags: ajax-search, live-search, woo-commerce, metasearch, gutenberg
Requires at least: 6.2
Requires PHP: 8.1
Stable tag: 1.0.0-rc.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

An independent WordPress-native premium search extension. Site content search works locally; optional external providers need credentials and may incur usage charges.

== Description ==
* Responsive, accessible dark/gold search experience with live local suggestions.
* Search public WordPress posts, pages and WooCommerce products.
* WooCommerce category/price filters, relevance/newest/price sorting and exact SKU lookup.
* Optional Brave and experimental Mojeek web results with URL deduplication and Reciprocal Rank Fusion.
* Optional Brave images, news and video result categories.
* Optional Wikipedia summaries, OpenStreetMap geocoding and LibreTranslate integration.
* Curated built-in !w, !gh, !yt, !ddg, !so, !mdn, !npm, !pypi and internal mode bangs.
* Arithmetic, percentage, temperature, color, units, timestamp, UUID and random instant answers; anonymous opt-in count analytics.
* Gutenberg dynamic block and [luxefind_search] shortcode. [seurch_search] is accepted only for beta compatibility.

== Installation ==
1. Upload the plug-in ZIP under Plugins > Add New > Upload Plugin, then activate it.
2. Visit Settings > LuxeFind Search to configure the site content types and optional providers.
3. Insert the LuxeFind Search block or [luxefind_search] shortcode into a page.
4. To enable web/media search, define LFXSP_BRAVE_API_KEY in wp-config.php and enable External web search.
5. External modes are OFF by default. See documentation for data-sharing and quotas.

== FAQ ==
= Does it require Python or a search server? =
No. The core feature is a native WordPress plugin. External search modes use configured third-party APIs.

= Does it need WooCommerce? =
No. WooCommerce features appear when WooCommerce is active.

= Is it the original Seurch project? =
No. It is an independently authored plugin; no Seurch code, assets or trademark are bundled. Not affiliated with seurch-eu.

== Changelog ==
= 1.0.0-rc.3 =
* Updated Mojeek provider contract, robust error handling and request budgets.
* Added richer instant answers and new built-in shortcuts.
* Improved privacy disclosure, admin hook separation and deactivation cleanup.
* Added Gutenberg block, provider web/media adapters, knowledge/maps/translation opt-in modes.
* Added WooCommerce filters/SKU search, premium responsive UI, shortcuts, analytics cleanup and mocked acceptance harness.
* Added security checks and CodeCanyon preparation materials.
