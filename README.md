# LuxeFind Search Pro

Premium WordPress metasearch plugin (release candidate `1.0.0-rc.3`).

> **Release status:** Not yet approved for marketplace sale. The Vercel frontend preview uses simulated results. Live Brave/Mojeek and real WordPress integration testing remain outstanding.

## Contents

- `luxefind-search-pro.php` — plugin bootstrap
- `includes/` — WordPress plugin services, metasearch adapters and settings
- `assets/` — frontend/admin CSS and JavaScript
- `tests/` — mocked acceptance, fuzzing and UI harnesses
- `docs/` — setup instructions, caveats, provider contracts and release-readiness checklist
- `demo/` — simulated UI preview
- `marketplace/` — draft artwork, not proof of product functionality

## Local validation

```bash
php -l luxefind-search-pro.php
php tests/acceptance.php
php tests/fuzz.php
```

Run a real WordPress + database staging install before submitting to CodeCanyon. See `docs/STAGING-AND-RELEASE-QA.md` and `docs/FINAL-MILESTONE-REPORT.md`.

## License and credits

Review `LICENSE` and `docs/LICENSE-NOTE.md`; upstream Seurch code is AGPL-3.0-only. This repository is a separate WordPress-native implementation, not an official Seurch port or affiliated product. Third-party provider terms apply. `LuxeFind` is a working brand pending clearance.
