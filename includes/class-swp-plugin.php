<?php
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/class-swp-engine.php';
require_once __DIR__ . '/class-swp-providers.php';
require_once __DIR__ . '/class-swp-discovery.php';
require_once __DIR__ . '/class-swp-analytics.php';
/** WordPress-native search system: no Python runtime and no upstream Seurch code bundled. */
final class LFXSP_Plugin {
    private static $instance = null;
    public static function boot() { if (!self::$instance) { self::$instance = new self(); } return self::$instance; }
    public static function defaults() {
        return array('placeholder'=>'Search articles, products, and more…', 'posts_per_page'=>8,
            'types'=>array('post','page','product'), 'min_query_length'=>2, 'external_enabled'=>0,
            'external_limit'=>6, 'cache_minutes'=>15, 'providers'=>array('brave'),
            'privacy_notice'=>1, 'analytics_enabled'=>0, 'instant_enabled'=>1,
            'woo_enabled'=>0, 'knowledge_enabled'=>0, 'maps_enabled'=>0, 'translate_enabled'=>0,
            'autocomplete_enabled'=>1, 'show_thumbnails'=>1, 'custom_bangs'=>array(), 'accent_color'=>'#d5b37a');
    }
    public static function options() {
        $saved=get_option('lfxsp_options',null);
        // Beta migration: read older setting once without mutating user content.
        if (!is_array($saved)) { $saved=get_option('swp_options',array()); }
        return wp_parse_args((array)$saved,self::defaults());
    }
    public static function activate() {
        $legacy=get_option('swp_options',array());
        add_option('lfxsp_options',is_array($legacy) && $legacy ? wp_parse_args($legacy,self::defaults()):self::defaults(),'',false);
        if (!wp_next_scheduled('lfxsp_daily_cleanup')) { wp_schedule_event(time()+HOUR_IN_SECONDS,'daily','lfxsp_daily_cleanup'); }
    }
    public static function deactivate() { wp_clear_scheduled_hook('lfxsp_daily_cleanup'); }
    private function __construct() {
        add_shortcode('luxefind_search',array($this,'shortcode'));
        add_shortcode('seurch_search',array($this,'shortcode')); // compatibility with older beta layouts
        add_action('wp_enqueue_scripts',array($this,'register_assets'));
        add_action('init',array($this,'register_block'));
        add_action('rest_api_init',array($this,'register_routes'));
        if (is_admin()) {
            add_action('admin_menu',array($this,'admin_menu'));
            add_action('admin_init',array($this,'settings'));
            add_action('admin_enqueue_scripts',array($this,'admin_assets'));
            add_action('wp_ajax_lfxsp_clear_cache',array($this,'clear_cache'));
            add_action('wp_ajax_lfxsp_clear_analytics',array($this,'clear_analytics'));
        }
        add_action('lfxsp_daily_cleanup',array('LFXSP_Analytics','cleanup'));
    }
    public function register_assets() {
        wp_register_style('lfxsp-ui',plugins_url('assets/search.css',LFXSP_FILE),array(),LFXSP_VERSION);
        wp_register_script('lfxsp-ui',plugins_url('assets/search.js',LFXSP_FILE),array(),LFXSP_VERSION,true);
    }
    public function register_block() {
        if (!function_exists('register_block_type')) { return; }
        wp_register_script('lfxsp-editor',plugins_url('assets/editor.js',LFXSP_FILE),array('wp-blocks','wp-element','wp-components','wp-block-editor'),LFXSP_VERSION,true);
        register_block_type('luxefind/search',array('api_version'=>2,'editor_script'=>'lfxsp-editor',
            'render_callback'=>array($this,'block'), 'attributes'=>array(
                'mode'=>array('type'=>'string','default'=>'all'),
                'placeholder'=>array('type'=>'string','default'=>''))));
    }
    public function block($attrs) { return $this->shortcode($attrs); }
    public function shortcode($atts=array()) {
        $opts=self::options();
        $atts=shortcode_atts(array('placeholder'=>$opts['placeholder'],'mode'=>'all'),(array)$atts,'luxefind_search');
        $mode=in_array($atts['mode'],self::modes(),true) ? $atts['mode'] : 'all';
        $placeholder = sanitize_text_field($atts['placeholder'] ?: $opts['placeholder']);
        wp_enqueue_style('lfxsp-ui'); wp_enqueue_script('lfxsp-ui');
        wp_localize_script('lfxsp-ui','lfxspConfig',array('endpoint'=>esc_url_raw(rest_url('luxefind/v1/search')),
            'min'=>(int)$opts['min_query_length'],'autocomplete'=>!empty($opts['autocomplete_enabled']),
            'thumbnails'=>!empty($opts['show_thumbnails'])));
        $id='swp-'.wp_unique_id(); $accent=sanitize_hex_color($opts['accent_color']) ?: '#d5b37a';
        $tabs=array('all'=>'All','site'=>'Site','products'=>'Products','web'=>'Web','images'=>'Images','news'=>'News','videos'=>'Videos','maps'=>'Maps','knowledge'=>'Knowledge','translate'=>'Translate','instant'=>'Instant answers');
        ob_start(); ?>
        <section class="lfxsp-search" style="--swp-accent:<?php echo esc_attr($accent); ?>" data-lfxsp-search data-mode="<?php echo esc_attr($mode); ?>" aria-label="LuxeFind search">
          <div class="lfxsp-search__ambient" aria-hidden="true"></div>
          <header class="lfxsp-search__head"><span class="lfxsp-search__eyebrow">LUXEFIND <span aria-hidden="true">✦</span> DISCOVERY</span>
            <h2>Find the <em>extraordinary.</em></h2><p>Intelligent discovery, beautifully simple.</p></header>
          <form class="lfxsp-search__form" role="search" data-lfxsp-form>
            <label class="screen-reader-text" for="<?php echo esc_attr($id); ?>">Search query</label>
            <span class="lfxsp-search__icon" aria-hidden="true">⌕</span>
            <input id="<?php echo esc_attr($id); ?>" type="search" name="q" autocomplete="off" minlength="<?php echo esc_attr($opts['min_query_length']); ?>" maxlength="160" placeholder="<?php echo esc_attr($placeholder); ?>" required>
            <button type="submit"><span>Explore</span><span aria-hidden="true">↗</span></button>
          </form>
          <nav class="lfxsp-search__tabs" aria-label="Search category" data-lfxsp-tabs>
          <?php foreach ($tabs as $tab=>$label):
            if ($tab==='products' && (empty($opts['woo_enabled']) || !function_exists('wc_get_product'))) { continue; }
            if (in_array($tab,array('web','images','news','videos'),true) && empty($opts['external_enabled'])) { continue; }
            if ($tab==='maps' && empty($opts['maps_enabled'])) { continue; }
            if ($tab==='knowledge' && empty($opts['knowledge_enabled'])) { continue; }
            if ($tab==='translate' && empty($opts['translate_enabled'])) { continue; }
            if ($tab==='instant' && empty($opts['instant_enabled'])) { continue; }
            ?>
            <button class="lfxsp-search__tab" type="button" data-lfxsp-tab="<?php echo esc_attr($tab); ?>" aria-pressed="<?php echo $tab===$mode?'true':'false'; ?>"><?php echo esc_html($label); ?></button>
          <?php endforeach; ?></nav>
          <?php if (!empty($opts['woo_enabled']) && function_exists('wc_get_product')): ?>
          <div class="lfxsp-search__filters" data-lfxsp-filters hidden aria-label="Product filters">
            <label>Category <input type="text" name="category" maxlength="60" placeholder="Category slug"></label>
            <label>Min price <input type="number" name="min_price" min="0" max="9999999" step="0.01" placeholder="0"></label>
            <label>Max price <input type="number" name="max_price" min="0" max="9999999" step="0.01" placeholder="Any"></label>
            <label>Sort <select name="sort"><option value="relevance">Relevance</option><option value="newest">Newest</option><option value="price_asc">Price: low to high</option><option value="price_desc">Price: high to low</option></select></label>
          </div><?php endif; ?>
          <?php if (!empty($opts['translate_enabled'])): ?><div class="lfxsp-search__language" data-lfxsp-language hidden><label>Translate to <select name="lang"><option value="en">English</option><option value="ar">Arabic</option><option value="fr">French</option><option value="es">Spanish</option><option value="de">German</option><option value="hi">Hindi</option><option value="tr">Turkish</option></select></label></div><?php endif; ?>
          <div class="lfxsp-search__status" role="status" aria-live="polite" aria-atomic="true" data-lfxsp-status>Start typing to explore.</div>
          <div class="lfxsp-search__results" data-lfxsp-results></div>
          <footer class="lfxsp-search__footer"><span>POWERED BY LUXEFIND</span><span>DISCOVER WITH CONFIDENCE</span></footer>
          <?php if (!empty($opts['maps_enabled'])): ?><p class="lfxsp-search__attribution">Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>.</p><?php endif; ?>
          <?php if ((!empty($opts['external_enabled']) || !empty($opts['maps_enabled']) || !empty($opts['knowledge_enabled']) || !empty($opts['translate_enabled'])) ): ?>
            <p class="lfxsp-search__disclosure">External search, map, knowledge and translation modes share submitted queries with their configured providers. External image previews may also contact their hosts. Site content search stays on this website.</p>
          <?php endif; ?>
        </section><?php return ob_get_clean();
    }
    public static function modes() { return array('all','site','products','web','images','news','videos','maps','knowledge','translate','instant'); }
    public function register_routes() {
        register_rest_route('luxefind/v1','/search',array('methods'=>'GET','callback'=>array($this,'search'),'permission_callback'=>'__return_true',
            'args'=>array('q'=>array('required'=>true,'type'=>'string','sanitize_callback'=>'sanitize_text_field'),
                'mode'=>array('default'=>'all','type'=>'string','sanitize_callback'=>'sanitize_key'),
                'category'=>array('type'=>'string','sanitize_callback'=>'sanitize_title'),
                'min_price'=>array('type'=>'string'), 'max_price'=>array('type'=>'string'),
                'sort'=>array('type'=>'string','sanitize_callback'=>'sanitize_key'),
                'lang'=>array('type'=>'string','sanitize_callback'=>'sanitize_key'))));
    }
    private static function price_param($raw) {
        if ($raw===null || $raw==='') { return ''; }
        if (!is_scalar($raw) || !preg_match('/^\d{1,7}(?:\.\d{1,2})?$/',(string)$raw)) { return ''; }
        return min(9999999,(float)$raw);
    }
    public function search($request) {
        $opts=self::options(); $q=trim((string)$request->get_param('q'));
        $len=function_exists('mb_strlen') ? mb_strlen($q) : strlen($q);
        if ($len<(int)$opts['min_query_length'] || $len>160) {
            return new WP_Error('swp_invalid_query','Query must be between '.(int)$opts['min_query_length'].' and 160 characters.',array('status'=>400));
        }
        $identity=is_user_logged_in() ? 'user:'.get_current_user_id() : 'ip:'.(string)($_SERVER['REMOTE_ADDR'] ?? '');
        $rate_key='lfxsp_rl_'.md5(wp_salt('auth').'|'.$identity);
        $hits=(int)get_transient($rate_key);
        if ($hits>=45) { return new WP_Error('swp_rate_limited','Please wait before searching again.',array('status'=>429)); }
        set_transient($rate_key,$hits+1,MINUTE_IN_SECONDS);
        $mode=(string)$request->get_param('mode'); if (!in_array($mode,self::modes(),true)) { $mode='all'; }
        $shortcut=LFXSP_Discovery::bang($q, $opts);
        if ($shortcut && isset($shortcut['mode'])) { $mode=$shortcut['mode']; $q=$shortcut['query']; }
        $result=array('query'=>$q,'mode'=>$mode,'site'=>array(),'web'=>array(),'media'=>array(),'warnings'=>array(),'knowledge'=>null,'instant'=>null);
        if ($shortcut && isset($shortcut['url'])) {
            $result['web']=array($shortcut);
            return rest_ensure_response($result);
        }
        if ($mode==='all' || $mode==='site' || $mode==='products') {
            $filters=array('category'=>sanitize_title((string)($request->get_param('category') ?? '')),
                'min_price'=>self::price_param($request->get_param('min_price')),
                'max_price'=>self::price_param($request->get_param('max_price')),
                'sort'=>sanitize_key((string)($request->get_param('sort') ?? 'relevance')));
            $result['site']=LFXSP_Engine::local_search($q,$opts,$mode,$filters);
        }
        if (($mode==='all' || $mode==='web') && !empty($opts['external_enabled'])) {
            $out=LFXSP_Providers::search($q,$opts); $result['web']=$out['results'];
            $result['warnings']=array_merge($result['warnings'],$out['warnings']);
        }
        if (in_array($mode,array('images','news','videos'),true) && !empty($opts['external_enabled'])) {
            $out=LFXSP_Providers::media($mode,$q,$opts); $result['media']=$out['results'];
            $result['warnings']=array_merge($result['warnings'],$out['warnings']);
        }
        if ($mode==='maps') {
            $out=LFXSP_Discovery::maps($q,$opts); $result['media']=$out['results'];
            $result['warnings']=$out['warnings'];
        }
        if (($mode==='knowledge' || $mode==='all') && !empty($opts['knowledge_enabled'])) {
            $result['knowledge']=LFXSP_Discovery::wikipedia($q);
        }
        if ($mode==='translate') {
            $lang=sanitize_key((string)($request->get_param('lang') ?? 'en'));
            $out=LFXSP_Discovery::translate($q,$opts,$lang);
            $result['media']=$out['results'];$result['warnings']=$out['warnings'];
        }
        if (in_array($mode,array('all','site','web','instant'),true) && !empty($opts['instant_enabled'])) {
            $result['instant']=LFXSP_Engine::instant($q);
        }
        if (!empty($opts['analytics_enabled'])) {
            LFXSP_Analytics::record($q,count($result['site'])+count($result['web'])+count($result['media'])+($result['knowledge']?1:0));
        }
        return rest_ensure_response($result);
    }
    public function admin_menu() { add_options_page('LuxeFind Search Pro','LuxeFind Search','manage_options','luxefind-search-pro',array($this,'settings_page')); }
    public function settings() {
        register_setting('lfxsp_options_group','lfxsp_options',array('type'=>'array','sanitize_callback'=>array($this,'sanitize_options'),'default'=>self::defaults()));
    }
    public function admin_assets($hook) {
        if ($hook!=='settings_page_luxefind-search-pro') { return; }
        wp_enqueue_style('lfxsp-admin',plugins_url('assets/admin.css',LFXSP_FILE),array(),LFXSP_VERSION);
    }
    public function sanitize_options($raw) {
        $raw=is_array($raw)?$raw:array();$defaults=self::defaults();
        $types=array_values(array_intersect(array_map('sanitize_key',array_filter((array)($raw['types']??array()),'is_scalar')),get_post_types(array('public'=>true))));
        $providers=array_values(array_unique(array_intersect(array_map('sanitize_key',(array)($raw['providers']??array())),array('brave','mojeek'))));
        $flags=array('external_enabled','analytics_enabled','instant_enabled','woo_enabled','knowledge_enabled','maps_enabled','translate_enabled','autocomplete_enabled','show_thumbnails');
        $out=array('placeholder'=>sanitize_text_field($raw['placeholder']??$defaults['placeholder']),
            'posts_per_page'=>max(1,min(20,absint($raw['posts_per_page']??8))),
            'types'=>$types ?: array('post','page'),
            'min_query_length'=>max(2,min(5,absint($raw['min_query_length']??2))),
            'external_limit'=>max(1,min(10,absint($raw['external_limit']??6))),
            'cache_minutes'=>max(1,min(120,absint($raw['cache_minutes']??15))),
            'providers'=>$providers,
            'custom_bangs'=>self::sanitize_bangs($raw['custom_bangs_text'] ?? ''),
            'accent_color'=>sanitize_hex_color($raw['accent_color']??'#d5b37a')?:'#d5b37a');
        foreach ($flags as $key) { $out[$key]=!empty($raw[$key])?1:0; }
        return $out;
    }
    /** One HTTPS URL template per line: docs=https://example.org/search?q={query} */
    public static function sanitize_bangs($raw) {
        $result=array();
        foreach (array_slice(explode("\n", (string)$raw), 0, 25) as $line) {
            if (!str_contains($line,'=')) { continue; }
            list($key,$url)=array_map('trim',explode('=',$line,2));
            $key=sanitize_key($key);
            if (!preg_match('/^[a-z][a-z0-9_-]{1,19}$/',$key) || strlen($url)>300 || substr_count($url,'{query}')!==1) { continue; }
            $check=str_replace('{query}','test',$url);
            $parts=wp_parse_url($check);
            if (!is_array($parts) || strtolower($parts['scheme']??'')!=='https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) { continue; }
            $result[$key]=$url;
        }
        return $result;
    }
    private static function checkbox($opts,$key,$label,$details='') { ?>
        <label><input type="checkbox" name="lfxsp_options[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($opts[$key])); ?>> <?php echo esc_html($label); ?></label>
        <?php if ($details): ?><p class="description"><?php echo esc_html($details); ?></p><?php endif;
    }
    public function settings_page() {
        if (!current_user_can('manage_options')) { return; }
        $opts=self::options(); ?>
        <div class="wrap lfxsp-admin"><div class="lfxsp-admin__hero"><span class="lfxsp-admin__eyebrow">LUXEFIND / CONTROL CENTER</span>
            <h1>Premium search. <em>Exceptional discovery.</em></h1>
            <p>Configure on-site search, product discovery and optional external intelligence. Your API keys remain in wp-config.php.</p>
            <code>[luxefind_search]</code> <code>[luxefind_search mode="products"]</code></div>
        <form method="post" action="options.php"><?php settings_fields('lfxsp_options_group'); ?>
        <div class="lfxsp-admin__grid">
          <section class="lfxsp-admin__panel"><h2>01 — Search experience</h2>
          <table class="form-table" role="presentation">
            <tr><th><label for="lfxsp-placeholder">Placeholder</label></th><td><input id="lfxsp-placeholder" class="regular-text" name="lfxsp_options[placeholder]" maxlength="120" value="<?php echo esc_attr($opts['placeholder']); ?>"></td></tr>
            <tr><th>Results per page</th><td><input type="number" min="1" max="20" name="lfxsp_options[posts_per_page]" value="<?php echo esc_attr($opts['posts_per_page']); ?>"></td></tr>
            <tr><th>Query min length</th><td><input type="number" min="2" max="5" name="lfxsp_options[min_query_length]" value="<?php echo esc_attr($opts['min_query_length']); ?>"></td></tr>
            <tr><th>Content types</th><td><?php foreach(get_post_types(array('public'=>true),'objects') as $type): ?><label class="lfxsp-admin__choice"><input type="checkbox" name="lfxsp_options[types][]" value="<?php echo esc_attr($type->name); ?>" <?php checked(in_array($type->name,(array)$opts['types'],true)); ?>> <?php echo esc_html($type->label); ?></label><?php endforeach; ?></td></tr>
            <tr><th>Accent color</th><td><input type="color" name="lfxsp_options[accent_color]" value="<?php echo esc_attr($opts['accent_color']); ?>"></td></tr>
            <tr><th>Autocomplete</th><td><?php self::checkbox($opts,'autocomplete_enabled','Enable local-only autocomplete','Autocomplete avoids external provider calls.'); ?></td></tr>
            <tr><th>Image previews</th><td><?php self::checkbox($opts,'show_thumbnails','Display result thumbnails','External images can load from third-party domains.'); ?></td></tr>
          </table></section>
          <section class="lfxsp-admin__panel"><h2>02 — Sources &amp; privacy</h2>
          <table class="form-table" role="presentation">
            <tr><th>Web providers</th><td><?php self::checkbox($opts,'external_enabled','Enable external web search','Upstream queries may incur API costs.'); ?></td></tr>
            <tr><th>Providers</th><td><?php foreach(array('brave'=>'Brave','mojeek'=>'Mojeek (experimental)') as $key=>$name): ?><label class="lfxsp-admin__choice"><input type="checkbox" name="lfxsp_options[providers][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key,(array)$opts['providers'],true)); ?>> <?php echo esc_html($name); ?> — <?php echo LFXSP_Providers::configured($key)?'configured':'missing key'; ?></label><?php endforeach; ?><p class="description">Set LFXSP_BRAVE_API_KEY / LFXSP_MOJEEK_API_KEY in wp-config.php. Media uses Brave endpoints.</p></td></tr>
            <tr><th>External result limit</th><td><input type="number" name="lfxsp_options[external_limit]" min="1" max="10" value="<?php echo esc_attr($opts['external_limit']); ?>"></td></tr>
            <tr><th>Cache duration</th><td><input type="number" name="lfxsp_options[cache_minutes]" min="1" max="120" value="<?php echo esc_attr($opts['cache_minutes']); ?>"> minutes</td></tr>
            <tr><th>Knowledge</th><td><?php self::checkbox($opts,'knowledge_enabled','Enable Wikipedia summaries'); ?></td></tr>
            <tr><th>Maps</th><td><?php self::checkbox($opts,'maps_enabled','Enable OpenStreetMap lookup','Uses public Nominatim subject to usage policy and caching.'); ?></td></tr>
            <tr><th>Translation</th><td><?php self::checkbox($opts,'translate_enabled','Enable LibreTranslate','Requires LFXSP_LIBRETRANSLATE_URL configured to a public HTTPS endpoint.'); ?></td></tr>
            <tr><th>Custom search shortcuts</th><td><textarea class="large-text code" name="lfxsp_options[custom_bangs_text]" rows="4" placeholder="docs=https://example.org/search?q={query}"><?php foreach ((array)($opts['custom_bangs']??array()) as $name=>$url) { echo esc_html($name.'='.$url."\n"); } ?></textarea><p class="description">Up to 25 HTTPS shortcuts; one name=URL-template per line. Use !name followed by keywords. Custom shortcuts override built-ins.</p></td></tr>
            <tr><th>External disclosure</th><td><p class="description">External-provider privacy notice is always visible when external features are enabled.</p></td></tr>
          </table></section>
          <section class="lfxsp-admin__panel"><h2>03 — Commerce &amp; intelligence</h2>
          <table class="form-table" role="presentation">
            <tr><th>WooCommerce</th><td><?php self::checkbox($opts,'woo_enabled','Enable WooCommerce product search','Requires WooCommerce; category, price, sort and exact SKU available.'); ?></td></tr>
            <tr><th>Instant answers</th><td><?php self::checkbox($opts,'instant_enabled','Calculator, color and unit answers'); ?></td></tr>
            <tr><th>Analytics</th><td><?php self::checkbox($opts,'analytics_enabled','Store anonymous daily totals','No query terms or visitor IP addresses are stored in analytics.'); ?></td></tr>
          </table><p>Last 14 days: <strong><?php echo esc_html((string)LFXSP_Analytics::total()); ?></strong> searches.</p></section>
        </div><?php submit_button('Save Search Settings','primary large'); ?></form>
        <div class="lfxsp-admin__actions"><form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"><input type="hidden" name="action" value="lfxsp_clear_cache"><?php wp_nonce_field('lfxsp_clear_cache'); ?><?php submit_button('Clear cached results','secondary'); ?></form>
        <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"><input type="hidden" name="action" value="lfxsp_clear_analytics"><?php wp_nonce_field('lfxsp_clear_analytics'); ?><?php submit_button('Reset analytics','secondary'); ?></form></div>
        <p class="description">API endpoint: <code><?php echo esc_html(rest_url('luxefind/v1/search')); ?></code>. Standard public-search endpoint with abuse limiting; do not expose private data in searchable post types.</p>
        </div><?php
    }
    public function clear_cache() {
        if (!current_user_can('manage_options') || !check_ajax_referer('lfxsp_clear_cache','_wpnonce',false)) { wp_die('Forbidden','',array('response'=>403)); }
        LFXSP_Engine::clear_cache(); wp_safe_redirect(admin_url('options-general.php?page=luxefind-search-pro&cache=cleared')); exit;
    }
    public function clear_analytics() {
        if (!current_user_can('manage_options') || !check_ajax_referer('lfxsp_clear_analytics','_wpnonce',false)) { wp_die('Forbidden','',array('response'=>403)); }
        LFXSP_Analytics::clear(); wp_safe_redirect(admin_url('options-general.php?page=luxefind-search-pro&stats=cleared')); exit;
    }
}
