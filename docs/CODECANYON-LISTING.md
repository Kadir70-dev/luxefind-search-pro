# Draft CodeCanyon Listing — NOT SUBMITTED

## Title
**LuxeFind Search Pro — Premium Live Search & Metasearch for WordPress**

> Working title pending trademark/name review. A public US LUXEFIND trademark record exists in a different category (Serial 98807368); this is not clearance. No affiliation with the Seurch project or its authors.

## Short description
A refined, responsive, WordPress-native search experience with local live results, optional Brave/Mojeek web discovery, images/news/videos, knowledge summaries, map lookup, translation bridge and instant-answer utilities.

## Feature overview (RC.3 source-supported)
- Premium responsive search panel with keyboard-accessible category tabs and reduced-motion styles.
- Search published WordPress posts and pages; optional WooCommerce mode (disabled by default).
- Public read-only REST search endpoint and local-only autocomplete suggestions.
- Optional Brave web, image, news and video search; Mojeek web blending via Reciprocal Rank Fusion, with cache and best-effort budgets.
- Optional Wikipedia summary cards, OSM/Nominatim map lookup and self-configured LibreTranslate integration.
- Instant-answer utilities: arithmetic, percent, physical units, temperature, RGB/HEX, hexadecimal/decimal, timestamp, UUID, dice/coin and bounded random integers.
- Built-in curated `!` shortcuts, plus up to 25 configured HTTPS custom shortcuts.
- Gutenberg dynamic block and `[luxefind_search]` shortcode.
- Opt-in anonymous aggregate search-count statistics; no raw queries or visitor IPs stored in plugin analytics.
- Server-side keys only, WordPress sanitization, safe remote HTTP calls, content sanitization and basic public REST throttling.

## System requirements
WordPress 6.2+, PHP 8.1+. No Python or Django dependency. Database is standard WordPress database. JavaScript enabled for AJAX interface. Provider API accounts separately required to enable external modes; per-provider quotas and terms apply.

## What's NOT included / accurate buyer disclosures
- External API subscriptions and usage costs are NOT included.
- Not an independent internet crawler, AI answer generator, semantic reranker or search index.
- Not a full 1:1 port of Seurch, no 13,000 shortcut list, no anonymous privacy guarantee.
- Simulated UI demo data is labeled as simulated; provider results depend on account entitlements.
- WooCommerce, maps, knowledge and translation are optional. Local WordPress search does not require them.
- Plugin itself does not include any license activation service or updater.

## Installation
Upload the plugin ZIP in WordPress Admin > Plugins > Add New > Upload Plugin; activate it, open Settings > LuxeFind Search and insert `[luxefind_search]` or the Gutenberg search block. Optional API keys are configured server-side in `wp-config.php` as explained in INSTALLATION.md.

## Support and updates
Define the team-operated support email/helpdesk, hours and update policy BEFORE publication; do not promise 24/7 or fixed updates until staffed. Do not display fake reviews, sales numbers or compatibility claims.

## Suggested tags
`wordpress search, live search, ajax search, metasearch, search plugin, website search, brave search, gutenberg, responsive search`

## Proposed price hypothesis
$39–$59 regular-license range, **not** an assertion of market pricing or guaranteed sales. Review pricing, support obligations, Envato fees and provider costs separately.

## Demo
Local static *design simulation* in `demo/index.html`. Must host on your own platform and label mock data; a public URL does not yet exist. Real WordPress staging/demo recommended before submitting.
