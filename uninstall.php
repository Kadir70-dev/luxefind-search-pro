<?php
/** User-requested uninstall removes plugin preferences, cache, events and aggregate stats. */
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
wp_clear_scheduled_hook('lfxsp_daily_cleanup');
delete_option('lfxsp_options');
delete_option('swp_options');
foreach (array('lfxsp_cache_keys','swp_cache_keys') as $registry) {
    foreach (array_keys((array)get_option($registry,array())) as $key) {
        if (preg_match('/^(lfxsp_|swp_)(web_|media_|knowledge_|maps_|v2_|ext_)/',(string)$key)) { delete_transient($key); }
    }
    delete_option($registry);
}
for ($day=0;$day<90;$day++) { delete_option('lfxsp_stat_'.gmdate('Ymd',time()-DAY_IN_SECONDS*$day)); }
