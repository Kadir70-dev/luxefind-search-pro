<?php
if (!defined('ABSPATH')) { exit; }
/** Opt-in discovery tools. All URLs are fixed provider endpoints or strict allowlisted destinations. */
final class LFXSP_Discovery {
    public static function bang($q, $opts = array()) {
        if (!preg_match('/^!([a-z]+)\s+(.+)$/iu', trim($q), $m)) { return null; }
        $word = strtolower($m[1]); $term = trim($m[2]);
        if ($term === '' || strlen($term) > 160) { return null; }
        $targets = array('w'=>'https://en.wikipedia.org/wiki/Special:Search?search=',
            'gh'=>'https://github.com/search?q=', 'yt'=>'https://www.youtube.com/results?search_query=',
            'ddg'=>'https://duckduckgo.com/?q=', 'so'=>'https://stackoverflow.com/search?q=',
            'mdn'=>'https://developer.mozilla.org/en-US/search?q=', 'npm'=>'https://www.npmjs.com/search?q=',
            'pypi'=>'https://pypi.org/search/?q=');
        $custom = (array)($opts['custom_bangs'] ?? array());
        if (isset($custom[$word]) && is_string($custom[$word])) {
            $url=str_replace('{query}',rawurlencode($term),$custom[$word]);
            return array('title'=>strtoupper($word).' shortcut','description'=>'Open custom search',
                'url'=>$url,'source'=>'shortcut','type'=>'web');
        }
        if (isset($targets[$word])) {
            return array('title'=>strtoupper($word).' shortcut','description'=>'Open results for '.$term,
                'url'=>$targets[$word].rawurlencode($term),'source'=>'shortcut','type'=>'web');
        }
        if (in_array($word, array('images','news','videos','maps','web','site','knowledge','translate','instant'), true)) {
            return array('mode'=>$word,'query'=>$term);
        }
        return null;
    }
    public static function wikipedia($query) {
        $q = lfxsp_substr_safe($query, 0, 100);
        $key = 'lfxsp_knowledge_'.md5($q);
        $cached = get_transient($key);
        if (is_array($cached)) { return $cached; }
        $url = add_query_arg(array('action'=>'query','generator'=>'search','gsrsearch'=>$q,'gsrlimit'=>1,
            'prop'=>'extracts|info','exintro'=>1,'explaintext'=>1,'exsentences'=>3,'inprop'=>'url',
            'format'=>'json','formatversion'=>2), 'https://en.wikipedia.org/w/api.php');
        $data = LFXSP_Providers::json_request($url, array('User-Agent'=>'LuxeFindSearchPro/1.0 (WordPress knowledge lookup)'));
        if (is_wp_error($data)) { return null; }
        $pages = $data['query']['pages'] ?? array();
        $page = isset($pages[0]) && is_array($pages[0]) ? $pages[0] : null;
        if (!$page) { return null; }
        $out = array('title'=>sanitize_text_field($page['title'] ?? ''),
            'description'=>sanitize_text_field(lfxsp_substr_safe($page['extract'] ?? '', 0, 450)),
            'url'=>LFXSP_Providers::normalize_url($page['fullurl'] ?? ''),'source'=>'wikipedia','type'=>'knowledge');
        if (empty($out['url'])) { return null; }
        set_transient($key, $out, HOUR_IN_SECONDS * 12);
        LFXSP_Providers::remember_key($key);
        return $out;
    }
    public static function maps($q, $opts) {
        if (empty($opts['maps_enabled'])) { return array('results'=>array(),'warnings'=>array('Maps are not enabled.')); }
        $key = 'lfxsp_maps_'.md5($q);
        $cached = get_transient($key);
        if (is_array($cached)) { return array('results'=>$cached,'warnings'=>array()); }
        // Public Nominatim policy: absolute max one request per second per application.
        if (get_transient('lfxsp_nominatim_cooldown')) {
            return array('results'=>array(),'warnings'=>array('Map provider is cooling down. Try again shortly.'));
        }
        set_transient('lfxsp_nominatim_cooldown', 1, 2); // Best-effort, cannot enforce globally under multiple workers.
        $url = add_query_arg(array('q'=>$q,'format'=>'jsonv2','limit'=>5,'addressdetails'=>0), 'https://nominatim.openstreetmap.org/search');
        $data = LFXSP_Providers::json_request($url, array('User-Agent'=>'LuxeFindSearchPro/1.0 ('.home_url('/').')','Referer'=>home_url('/')));
        if (is_wp_error($data)) { return array('results'=>array(),'warnings'=>array('Maps service unavailable.')); }
        $out = array();
        foreach (array_slice($data, 0, 5) as $row) {
            if (!is_array($row)) { continue; }
            $lat = (float) ($row['lat'] ?? 1000); $lon = (float) ($row['lon'] ?? 1000);
            if (abs($lat) > 90 || abs($lon) > 180) { continue; }
            $url = 'https://www.openstreetmap.org/?mlat='.rawurlencode((string)$lat).'&mlon='.rawurlencode((string)$lon).'#map=13/'.$lat.'/'.$lon;
            $out[] = array('title'=>sanitize_text_field($row['display_name'] ?? 'Map result'),
                'description'=>'Open location in OpenStreetMap', 'url'=>$url, 'type'=>'maps', 'source'=>'openstreetmap');
        }
        set_transient($key, $out, DAY_IN_SECONDS);
        LFXSP_Providers::remember_key($key);
        return array('results'=>$out,'warnings'=>array());
    }
    public static function translate($text, $opts, $lang) {
        if (empty($opts['translate_enabled']) || !defined('LFXSP_LIBRETRANSLATE_URL')) {
            return array('results'=>array(),'warnings'=>array('Translation service is not configured.'));
        }
        $languages = array('en','de','es','fr','it','nl','pt','ar','hi','tr','ru');
        if (!in_array($lang, $languages, true)) { return array('results'=>array(),'warnings'=>array('Unsupported translation language.')); }
        $base = rtrim((string) constant('LFXSP_LIBRETRANSLATE_URL'), '/');
        // A server operator configures the endpoint in wp-config.php; never take it from search input.
        if (wp_parse_url($base, PHP_URL_SCHEME) !== 'https' || !wp_http_validate_url($base)) {
            return array('results'=>array(),'warnings'=>array('Translation endpoint needs a public HTTPS URL.'));
        }
        $url = $base.'/translate';
        $request = array('timeout'=>10, 'redirection'=>0, 'reject_unsafe_urls'=>true,
            'limit_response_size'=>16384, 'headers'=>array('Content-Type'=>'application/json','Accept'=>'application/json'),
            'body'=>wp_json_encode(array('q'=>$text, 'source'=>'auto', 'target'=>$lang,
                'format'=>'text','api_key'=>defined('LFXSP_LIBRETRANSLATE_API_KEY') ? constant('LFXSP_LIBRETRANSLATE_API_KEY') : '')));
        $response = wp_safe_remote_post($url, $request);
        if (is_wp_error($response) || (int)wp_remote_retrieve_response_code($response) !== 200) {
            return array('results'=>array(),'warnings'=>array('Translation provider unavailable.'));
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $translated = sanitize_text_field((string)($data['translatedText'] ?? ''));
        if (!$translated) { return array('results'=>array(),'warnings'=>array('No translation returned.')); }
        return array('results'=>array(array('title'=>'Translation → '.strtoupper($lang), 'description'=>$translated,
            'url'=>'', 'type'=>'translate','source'=>'libretranslate')), 'warnings'=>array());
    }
}
/** Unicode-safe substring without requiring mbstring extension on low-cost WordPress hosts. */
function lfxsp_substr_safe($input, $start, $limit) {
    $input = (string) $input;
    return function_exists('mb_substr') ? mb_substr($input,$start,$limit) : substr($input,$start,$limit);
}
