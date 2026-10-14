#!/usr/bin/env bash
# Read-only checks for an EXISTING disposable WordPress staging directory.
# Will not install, activate, edit, migrate, delete content or call paid APIs.
set -euo pipefail
: "${LFXSP_STAGING_WP_PATH:?Set LFXSP_STAGING_WP_PATH to the staging WordPress root}"
command -v wp >/dev/null || { echo 'ERROR: WP-CLI unavailable.' >&2; exit 2; }
WP=(wp --path="$LFXSP_STAGING_WP_PATH" --skip-themes)
"${WP[@]}" core is-installed
"${WP[@]}" plugin is-active luxefind-search-pro
"${WP[@]}" eval 'echo "WordPress ".get_bloginfo("version")."; PHP ".PHP_VERSION."\n";'
"${WP[@]}" eval 'echo "Shortcode registered: ".(shortcode_exists("luxefind_search")?"yes":"no")."\n";'
"${WP[@]}" eval '$p=do_shortcode("[luxefind_search]"); if(strpos($p,"data-lfxsp-search")===false){exit(9);}echo "Shortcode rendering: pass\n";'
"${WP[@]}" eval '$r=rest_do_request((function(){ $r=new WP_REST_Request("GET","/luxefind/v1/search"); $r->set_param("q","local"); $r->set_param("mode","site"); return $r; })()); echo "REST local status: ".$r->get_status()."\n"; if($r->get_status()!==200){exit(9);}'
if "${WP[@]}" plugin is-active plugin-check; then
  "${WP[@]}" plugin check luxefind-search-pro || echo 'Plugin Check returned warnings/errors; review before launch.'
else
  echo 'Plugin Check not installed on staging; PENDING.'
fi
printf '%s\n' 'Completed read-only staging smoke. Review actual PHP logs and browser UI separately.'
