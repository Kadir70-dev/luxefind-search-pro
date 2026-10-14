<?php
if (!defined('ABSPATH')) { exit; }
/** Provider adapters; independently implemented WordPress integrations. */
final class LFXSP_Providers {
    private const ENGINES = array('brave', 'mojeek');
    /** Best-effort global upstream request budget; not an atomic distributed quota. */
    private static function request_budget($host) {
        if (!in_array($host, array('api.search.brave.com', 'api.mojeek.com'), true)) { return true; }
        $budget = defined('LFXSP_MAX_PAID_REQUESTS_HOURLY') ? (int)constant('LFXSP_MAX_PAID_REQUESTS_HOURLY') : 200;
        $budget = max(0, min(20000, $budget));
        if ($budget === 0) { return false; }
        $key = 'lfxsp_budget_'.gmdate('YmdH');
        $count = (int)get_transient($key);
        if ($count >= $budget) { return false; }
        set_transient($key, $count + 1, 2 * HOUR_IN_SECONDS);
        return true;
    }
    public static function configured($provider) {
        $map = array('brave' => 'LFXSP_BRAVE_API_KEY', 'mojeek' => 'LFXSP_MOJEEK_API_KEY');
        return isset($map[$provider]) && defined($map[$provider]) && is_string(constant($map[$provider])) && constant($map[$provider]) !== '';
    }
    public static function normalize_url($url) {
        if (!is_string($url) || strlen($url) > 2048) { return ''; }
        $url = esc_url_raw(trim($url), array('http', 'https'));
        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)) { return ''; }
        if (isset($parts['user']) || isset($parts['pass'])) { return ''; }
        return $url;
    }
    /** No user-controlled URL, redirects forbidden, 1 MB response cap, short timeout. */
    public static function json_request($url, $headers = array(), $method = 'GET', $body = null) {
        $allowed = array('api.search.brave.com','api.mojeek.com','en.wikipedia.org','nominatim.openstreetmap.org');
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        if (!in_array($host, $allowed, true) || wp_parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return new WP_Error('swp_blocked_upstream', 'Upstream host is not approved.');
        }
        if (!self::request_budget($host)) { return new WP_Error('lfxsp_budget_exceeded', 'External search hourly budget reached.'); }
        $args = array('timeout' => 8, 'redirection' => 0, 'headers' => array_merge(array('Accept'=>'application/json'), $headers), 'limit_response_size' => 1048576, 'reject_unsafe_urls' => true);
        // Keys remain server-side; upstream requests may expose the Mojeek key in server-side URL logs.
        if ($method === 'POST') { $args['body'] = $body; $response = wp_safe_remote_post($url, $args); }
        else { $response = wp_safe_remote_get($url, $args); }
        if (is_wp_error($response)) { return new WP_Error('swp_upstream_error', 'Search provider unavailable.'); }
        if ((int) wp_remote_retrieve_response_code($response) !== 200) { return new WP_Error('swp_upstream_status', 'Search provider returned an error.'); }
        $raw = wp_remote_retrieve_body($response);
        if (!is_string($raw) || strlen($raw) > 1048576) { return new WP_Error('swp_upstream_size', 'Search provider response was too large.'); }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : new WP_Error('swp_upstream_json', 'Invalid search provider response.');
    }
    public static function remember_key($cache_key) {
        $keys = (array) get_option('lfxsp_cache_keys', array());
        $keys[$cache_key] = time();
        if (count($keys) > 200) { asort($keys); $keys = array_slice($keys, -200, null, true); }
        update_option('lfxsp_cache_keys', $keys, false);
    }
    private static function cached($cache_key, $minutes, $callback) {
        $hit = get_transient($cache_key);
        if (is_array($hit)) { return $hit; }
        $data = call_user_func($callback);
        if (!is_wp_error($data)) {
            set_transient($cache_key, $data, max(1, min(120, (int) $minutes)) * MINUTE_IN_SECONDS);
            self::remember_key($cache_key);
        }
        return $data;
    }
    public static function search($q, $opts) {
        $active = array_values(array_unique(array_intersect((array) ($opts['providers'] ?? array('brave')), self::ENGINES)));
        $results = array(); $warnings = array();
        foreach ($active as $provider) {
            if (!self::configured($provider)) { $warnings[] = ucfirst($provider).' is not configured.'; continue; }
            $found = self::web_from_provider($provider, $q, $opts);
            if (is_wp_error($found)) { $warnings[] = ucfirst($provider).' is currently unavailable.'; continue; }
            foreach ($found as $index => $row) {
                $url = self::normalize_url($row['url'] ?? '');
                if (!$url) { continue; }
                // URL comparisons deliberately exclude fragments and common tracking parameters.
                $key = self::dedupe_key($url);
                if (!isset($results[$key])) {
                    $row['url'] = $url; $row['sources'] = array($provider); $row['_rrf'] = 0.0;
                    $results[$key] = $row;
                } elseif (!in_array($provider, $results[$key]['sources'], true)) {
                    $results[$key]['sources'][] = $provider;
                }
                $results[$key]['_rrf'] += 1 / (60 + $index + 1);
            }
        }
        uasort($results, static function($a, $b) { return $b['_rrf'] <=> $a['_rrf']; });
        $rows = array_slice(array_values($results), 0, max(1, min(10, (int) ($opts['external_limit'] ?? 5))));
        foreach ($rows as &$row) { unset($row['_rrf']); }
        unset($row);
        return array('results' => $rows, 'warnings' => $warnings);
    }
    public static function dedupe_key($url) {
        $parts = wp_parse_url($url);
        if (!is_array($parts)) { return ''; }
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = rtrim((string) ($parts['path'] ?? '/'), '/') ?: '/';
        $args = array();
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $args);
            foreach (array_keys($args) as $k) {
                if (preg_match('/^(utm_|fbclid$|gclid$|mc_)/i', $k)) { unset($args[$k]); }
            }
            ksort($args);
        }
        return hash('sha256', $host.$path.'?'.http_build_query($args));
    }
    private static function web_from_provider($provider, $q, $opts) {
        $limit = max(1, min(10, (int) ($opts['external_limit'] ?? 5)));
        $key = 'lfxsp_web_'.md5($provider.'|'.$q.'|'.$limit);
        return self::cached($key, $opts['cache_minutes'] ?? 15, static function() use ($provider, $q, $limit) {
            if ($provider === 'brave') {
                $url = add_query_arg(array('q'=>$q,'count'=>$limit,'safesearch'=>'strict'), 'https://api.search.brave.com/res/v1/web/search');
                $headers = array('X-Subscription-Token' => constant('LFXSP_BRAVE_API_KEY'));
            } else {
                // Mojeek's documented Search API uses api_key=... and fmt=json, not a Bearer token.
                $url = add_query_arg(array('q'=>$q,'t'=>$limit,'api_key'=>constant('LFXSP_MOJEEK_API_KEY'),'fmt'=>'json'), 'https://api.mojeek.com/search');
                $headers = array();
            }
            $data = self::json_request($url, $headers);
            if (is_wp_error($data)) { return $data; }
            if ($provider === 'mojeek' && strtoupper((string)($data['response']['status'] ?? 'OK')) !== 'OK') {
                return new WP_Error('lfxsp_mojeek_status', 'Mojeek rejected the request.');
            }
            $raw = $provider === 'brave' ? ($data['web']['results'] ?? array()) : ($data['response']['results'] ?? $data['results'] ?? array());
            return self::normalize_results($raw, $provider, 'web', $limit);
        });
    }
    public static function media($kind, $q, $opts) {
        if (!in_array($kind, array('images','news','videos'), true)) { return array('results'=>array(),'warnings'=>array('Invalid media type.')); }
        if (empty($opts['external_enabled']) || !self::configured('brave')) {
            return array('results'=>array(),'warnings'=>array('Brave API is required for media search.'));
        }
        $limit = max(1, min(10, (int) ($opts['external_limit'] ?? 5)));
        $key = 'lfxsp_media_'.md5($kind.'|'.$q.'|'.$limit);
        $data = self::cached($key, $opts['cache_minutes'] ?? 15, static function() use ($kind, $q, $limit) {
            $url = add_query_arg(array('q'=>$q,'count'=>$limit,'safesearch'=>'strict'), 'https://api.search.brave.com/res/v1/'.$kind.'/search');
            $json = self::json_request($url, array('X-Subscription-Token' => constant('LFXSP_BRAVE_API_KEY')));
            if (is_wp_error($json)) { return $json; }
            return self::normalize_results($json['results'] ?? ($json[$kind]['results'] ?? array()), 'brave', $kind, $limit);
        });
        return is_wp_error($data) ? array('results'=>array(),'warnings'=>array('Media provider is unavailable.')) : array('results'=>$data,'warnings'=>array());
    }
    public static function normalize_results($raw, $provider, $kind, $limit) {
        $out = array();
        foreach (array_slice(is_array($raw) ? $raw : array(), 0, $limit) as $item) {
            if (!is_array($item)) { continue; }
            $url = self::normalize_url($item['url'] ?? $item['link'] ?? '');
            if (!$url) { continue; }
            $thumbnail = $item['thumbnail']['src'] ?? $item['thumbnail']['original'] ?? $item['thumbnail'] ?? '';
            if (!is_string($thumbnail)) { $thumbnail = ''; }
            $title = is_scalar($item['title'] ?? null) ? (string)$item['title'] : 'Untitled';
            $description = is_scalar($item['description'] ?? $item['desc'] ?? null) ? (string)($item['description'] ?? $item['desc']) : '';
            $out[] = array('title'=>sanitize_text_field(wp_strip_all_tags($title)),
                'description'=>sanitize_text_field(wp_strip_all_tags($description)),
                'url'=>$url, 'thumbnail'=>self::normalize_url($thumbnail), 'source'=>$provider, 'type'=>$kind);
        }
        return $out;
    }
}
