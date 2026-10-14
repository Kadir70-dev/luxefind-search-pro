# WordPress staging and marketplace acceptance gate

**The checks in this document require a real staging WordPress instance; they were not run in the offline development runtime.** Do not mark live certification passed until a dated operator report captures the results.

## 1. Create disposable staging

- Isolate from production database, payment and real customer data. Do not enable real user access or crawl indexing.
- Test current WordPress on supported PHP versions (at least PHP 8.1, 8.2, 8.3, 8.4) and one older supported core installation, plus current default block theme and one popular commercial theme.
- Use test data: >= 100 published posts, 100 pages, custom public post types, drafts, private posts, malicious HTML text and Unicode queries.
- Install the plugin ZIP from `plugin/luxefind-search-pro.zip`. Activate; assert no warnings/fatal errors, admin Settings page available, and `[luxefind_search]` shortcode renders.
- Run `wp plugin check luxefind-search-pro` after installing WordPress Plugin Check. Run PHPCS WordPress-Core/WordPress-Extra/WordPress-Docs; manually review suppressions.

## 2. Provider integration

- With external modules OFF: capture outbound HTTP traffic; confirm no upstream requests from anonymous site searches and local live suggestions.
- With TEST keys configured in `wp-config.php`, verify actual Brave Web, Images, News and Videos response shapes, response warnings and plan entitlements; inspect consumed credits.
- Verify Mojeek API with `fmt=json` + `api_key`; confirm errors/quota expiration fail gracefully without leaking secrets. Never include real key in screenshots.
- Test knowledge summaries against Wikipedia search, map lookup against Nominatim respecting <1 req/s globally, and translation against a licensed LibreTranslate endpoint.
- Verify all returned remote URLs in final HTML are HTTP(S), non-empty and do not reveal internal addresses, API secrets, or unsanitized HTML.

## 3. Security and privacy

- Test REST anonymous throttling, empty/short/long queries, HTML/script injection and invalid price/category/sort/lang. Public routes must not mutate WordPress.
- Test XSS in titles, descriptions, source values, thumbnail URLs, shortcode attributes and custom bangs. Check with browser developer tools and DOM inspection.
- Test SSRF defenses (private hosts, redirects, IPv4/IPv6 loopback, link-local, DNS rebinding) on the production server; test timeout and 1MB response cap.
- Confirm API secrets never appear in HTML, localized JS, REST JSON, PHP fatal logs or demo captures. Protect Mojeek key in server outbound URL logs.
- Independently validate shared rate limiting across processes/CDN; transient increments are best effort only.
- Verify privacy notice and OpenStreetMap attribution appear when appropriate. Disable third-party image loading if site policy requires it.

## 4. Performance and accessibility

- Record cold/warm requests at 100, 1,000 and 10,000+ published posts, repeat with concurrency 1/10/50; report p50/p95 and query count; monitor API quota, PHP workers, PHP memory and database load.
- Test keyboard-only operation, spoken status updates, screen-reader landmark announcements, focus-visible, color contrast and reduced-motion preference.
- Validate Chrome, Firefox, Safari and iOS/Android mobile widths. Test header/footer collision in default and commercial themes.

## 5. Release decision

- Obtain approval from product owners for brand, copyright/ownership, AGPL code non-inclusion assertion, GPL terms, provider API rights, screenshots and demo data licensing.
- Use an eligible CodeCanyon author account. Complete supported product category, item title, content description, version, changelog, publicly accessible documentation, preview artwork, screenshots and optional working live demo URL.
- Never label a simulated API demo as a live WordPress end-to-end test.
- Publish the approved ZIP only after all blocking security/provider/compatibility rows are signed off.

## Recommended real-WordPress scripts

`tools/staging-check.sh` performs safe read-only checks against an already prepared staging installation with WP-CLI; it does not install/delete plugins or create content. See script comments.

## References

- Envato WordPress Plugin Requirements: https://help.author.envato.com/hc/en-us/articles/360000510603-WordPress-Plugin-Requirements
- Envato Code Item Preparation: https://help.author.envato.com/hc/en-us/articles/360000471583-Code-Item-Preparation-Technical-Requirements
- WordPress Plugin Check: https://github.com/WordPress/plugin-check
