# Security and privacy model

## Trust boundaries

Visitor input is untrusted; all public search request fields are bounded and validated; search result text is rendered by `textContent` in JavaScript. Admin forms use WP Settings API, WordPress capabilities and nonce-protected state-changing actions.

Provider secrets are read only as PHP constants from `wp-config.php`. External search modes are opt-in. Upstream connections use fixed HTTPS hosts (`api.search.brave.com`, `api.mojeek.com`, `en.wikipedia.org`, `nominatim.openstreetmap.org`) or a trusted operator-configured HTTPS LibreTranslate host. Requests use WP safe HTTP, no redirects, short timeouts and response size limits.

Search responses expose only published local content and public product data; WooCommerce visibility is checked when emitting products. No public route calls admin mutation actions.

## Known limitations requiring deployment controls

- Transient increments for visitor throttling, provider hourly budgets and Nominatim are **not atomic**. Concurrent anonymous traffic can bypass the soft request budget or block users behind a shared IP address. Enforce strong edge/WAF limits for public exposure.
- Public Nominatim is not appropriate for high volume. The cooldown is not atomic; deploy dedicated geocoding under load.
- Third-party images can expose user IP/referrer to image hosts; preview images are opt-in via settings, use no-referrer.
- Analytics option counts are non-atomic; do not use as billing or compliance metrics.
- Need a live SSRF/redirect/DNS-rebinding pen-test and PHP/WordPress compatibility matrix.
- No license key activation, payments, tracking pixels, remote auto-updater or telemetry is included.
- `LFXSP_MOJEEK_API_KEY` now uses the documented API key query parameter and `fmt=json`; live contracted-account validation remains pending. Mojeek keys can be recorded in server-side outbound URL logs and MUST be redacted.
