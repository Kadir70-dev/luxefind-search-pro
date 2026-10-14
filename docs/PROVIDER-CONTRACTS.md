# Provider compatibility and operator responsibility — 1.0.0-rc.3

This document separates **contract reviewed** from **live credentials verified**. No paid service credentials were supplied during RC.3 validation. All mocked tests are deterministic and do not demonstrate quota availability.

| Provider | Product feature | Endpoint / authentication | Contract review | Live API test |
|---|---|---|---|---|
| Brave Search | Web, images, news, videos | HTTPS `api.search.brave.com` with `X-Subscription-Token` header | Code inspected; official response shapes checked | NOT RUN |
| Mojeek | Web | HTTPS `api.mojeek.com/search` `?api_key=...&fmt=json` | Corrected from incorrect Bearer header per official docs | NOT RUN |
| Wikipedia | Knowledge extract | fixed Wikipedia HTTPS MediaWiki endpoint | Mock-validated | NOT RUN |
| Nominatim | Map lookup | public OSMF HTTPS Nominatim | Best-effort site throttle and 24h cache | NOT RUN |
| LibreTranslate | Translation | Operator-supplied public HTTPS endpoint | Mock-validated; endpoint must pass `wp_http_validate_url` | NOT RUN |

**Mojeek key visibility:** Official Mojeek Search API uses `api_key` in its outbound *server-to-server* URL. It is never inserted into browser JavaScript, frontend HTML or REST responses, but a proxy, HTTP debug logger, or reverse proxy storing full outbound URLs can record the key. Protect or redact such logs. The host may enforce additional billing/privacy terms.

**Brave plans:** Some plans may not support every media endpoint. Before advertising coverage, run licensed live smoke tests for all four modes and confirm country/language/provider permissions.

**Nominatim:** OSMF public endpoint limits use to **1 request per second maximum per application**, requires a valid identifying User-Agent or Referer and clear OSM attribution. The WordPress transient cooldown is not an atomic cross-server limiter. For busy/paid customer sites, recommend private geocoding or a provider with published SLA. Automated autocomplete must never call public Nominatim. See https://operations.osmfoundation.org/policies/nominatim/.

**Search privacy:** External-provider modes share submitted queries with upstream providers. Default install disables third-party calls; local autocomplete remains site-only. Using one plugin does not promise user anonymity from upstream APIs. Images loaded from providers may contact their hosts. A visitor-facing external-provider disclosure appears whenever an external module is enabled.

**Server-side hourly budget:** `LFXSP_MAX_PAID_REQUESTS_HOURLY` (default 200) can be set in `wp-config.php` to limit *best-effort* paid upstream requests. Cache hits do not count; the shared budget counts Bravo/Mojeek provider requests. This WordPress transient counter is **not atomic across workers** and is not a billing guarantee. Enforce upstream account quotas + CDN/WAF limits for cost safety. `0` blocks paid requests.

**References reviewed:**
- https://www.mojeek.com/support/api/search/quickstart.html
- https://www.mojeek.com/support/api/search/json_response.html
- https://api-dashboard.search.brave.com/api-reference/images/image_search
- https://operations.osmfoundation.org/policies/nominatim/
