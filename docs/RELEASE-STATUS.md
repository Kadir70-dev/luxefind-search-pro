# LuxeFind Search Pro — v1.0.0-rc.3 release status

## What is shipped

- WordPress-native site search via shortcode `[luxefind_search]` and a server-rendered Gutenberg block.
- Full responsive dark/champagne-gold frontend (keyboard-operated tabs, reduced motion, accessible status region, local autocomplete).
- Published posts/pages/public custom types; WooCommerce product-only tab, category/price filtering, price/newest sorting, and exact SKU lookup.
- Optional external web metasearch: Brave, experimental Mojeek, RRF aggregation and canonicalized URL deduplication.
- Opt-in image/news/video modes powered by Brave endpoints (API key + relevant subscription required).
- Opt-in Wikipedia knowledge summaries, Nominatim geocoding, LibreTranslate bridge and a curated bang list.
- Local simple calculator, unit conversion and color answer; optional anonymous daily usage totals.
- Fixed upstream host allowlist for outbound provider requests; no exposed API credentials; bounded remote response size, redirect denial and result URL protocol allowlist.
- Cache expiry and purge, admin permission/nonce checks, sanitized settings, simple transient request budget, uninstall cleanup.

## What testing has actually been run

- PHP 8.4 syntax checks on all PHP source files.
- Node 22 `--check` for browser scripts.
- `php tests/acceptance.php` with deterministic simulated WordPress/WooCommerce and fake upstream provider HTTP responses. **This is not a WordPress integration test.**
- Browser UI smoke tests against in-memory HTML and mock REST results passed at 1440×900 and 390×844 in headless Chromium; see `tests/UI-RESULTS.md`.
- ZIP integrity and manifest verification.

## Why this is a release candidate, NOT an approved CodeCanyon release

- Real WordPress (6.2+) and WooCommerce installation / plugin-check, admin navigation, front-end end-to-end testing across mainstream themes, and cross-version PHP matrix are **not verified** in this environment.
- Live Brave/Mojeek/LibreTranslate/Nominatim API requests are **not tested** against production credentials; Mojeek adapter remains explicitly experimental.
- Full Seurch Django feature parity is **not implemented** (full bang directory, science search, extensive instant answers, multi-language catalogs, extensive provider monitoring, account management, more engines, translation UX parity, image proxy, etc.).
- No production-grade central rate limiter under concurrency: transients are best-effort; consider Redis/WAF/edge restrictions for public traffic.
- Aggregate option-based analytics counters may lose writes under concurrent load. Suitable only for moderate-traffic evaluation.
- WooCommerce category/price filters use postmeta and need performance/compatibility evaluation for large catalogs; SKU exact lookup needs real WC tests.
- Public Nominatim 1 request/second constraint cannot be guaranteed across multi-worker PHP servers by non-atomic transients. Use a paid/provider-specific geocoder or external shared limiter for scale.
- Provider contracts and result reuse/image licensing must be confirmed by product owner.
- Privacy notice should be enabled whenever external queries/images are used; local/third-party analytics consent requirements depend on jurisdiction.
- **Brand trademark search, marketplace account eligibility, product demonstration, support commitments and Envato reviewer approval remain external/manual steps.**

## Go/no-go

**CodeCanyon submission: NO-GO until real WordPress/WooCommerce E2E, external-provider tests, licensing/branding, theme/browser QA and current marketplace requirements are reviewed.**

## RC.3 delta (2026-10-14)

- Corrected Mojeek API contract (`api_key`, `fmt=json`); added contract fixture assertions and quota rejection handling. This is a **contract-level mock**, not a live provider test.
- Added dedicated Instant Answers mode and more deterministic tools; expanded curated bangs.
- Added best-effort paid provider hourly request budget and public attribution/disclosure refinements.
- Isolated admin-only hooks, added deactivation cleanup, hardened malformed external result shapes.
- Created provider contract register, WordPress staging checklist/script, market listing copy, simulated offline demo, and correctly labeled marketplace preview graphics.
- CI source workflow runs PHP syntax + mock acceptance; no real WordPress/Plugin Check or paid API verification has been performed.

- Brand-clearance reminder: a US LUXEFIND trademark record exists in a non-software product category (Serial 98807368). Not a legal infringement determination; obtain professional clearance before commercial sale.
- The provider implementation runs web providers sequentially; slow uncached external requests can accumulate per-provider HTTP timeouts. Benchmark under load before production, and consider request parallelization.
- Deterministic fuzz checks added, without implying penetration-test certification.
