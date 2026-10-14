<?php
/** Dependency-free deterministic compatibility harness, NOT a replacement for real WordPress integration. */
define('ABSPATH', __DIR__.'/');
define('LFXSP_FILE', dirname(__DIR__).'/luxefind-search-pro.php');
define('LFXSP_VERSION', '1.0.0-rc.3');
define('MINUTE_IN_SECONDS',60);define('HOUR_IN_SECONDS',3600);define('DAY_IN_SECONDS',86400);
define('LFXSP_BRAVE_API_KEY','mock_brave_key');define('LFXSP_MOJEEK_API_KEY','mock_mojeek_key');
define('LFXSP_LIBRETRANSLATE_URL','https://translate.example.test');
$GLOBALS['mock_options']=array();$GLOBALS['mock_transients']=array();$GLOBALS['mock_calls']=array();
$GLOBALS['mock_posts']=array((object)array('ID'=>1,'post_type'=>'post','title'=>'Dubai architecture','excerpt'=>'Architecture with a beautifully simple story.'),
    (object)array('ID'=>2,'post_type'=>'product','title'=>'Golden watch','excerpt'=>'A luxury watch for sale.'));
function test_it($ok,$description){if(!$ok){fwrite(STDERR,"FAIL: $description\n");exit(1);}echo "PASS: $description\n";}
class WP_Error {private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
class WP_Query {public $posts;public function __construct($args){$GLOBALS['mock_last_query']=$args;$this->posts=array();foreach($GLOBALS['mock_posts'] as $p){if(!in_array($p->post_type,$args['post_type'],true))continue;if(stripos($p->title,$args['s'])===false && stripos($p->excerpt,$args['s'])===false)continue;$this->posts[]=$p;}}}
class Mock_Product {public function is_visible(){return true;}public function get_price_html(){return '<span>$199</span>';}public function get_sku(){return 'SKU-007';}public function get_price(){return '199';}public function is_in_stock(){return true;}}
class Mock_Request {private $p;public function __construct($p){$this->p=$p;}public function get_param($key){return $this->p[$key]??null;}}
function get_post_types($filter,$output='names'){return $output==='objects'?array('post'=>(object)array('name'=>'post','label'=>'Posts'),'page'=>(object)array('name'=>'page','label'=>'Pages'),'product'=>(object)array('name'=>'product','label'=>'Products')):array('post','page','product');}
function get_post_type($post){return $post->post_type;}
function has_term($cat,$tax,$id){return $cat==='jewelry';}
function get_post_status($id){return $id===2?'publish':'draft';}
function get_the_title($post){return is_object($post)?$post->title:'Golden watch';}
function get_the_excerpt($post){return is_object($post)?$post->excerpt:'Golden watch sale';}
function get_permalink($post){return 'https://site.example.test/?p='.(is_object($post)?$post->ID:$post);}
function get_the_post_thumbnail_url($post,$size){return 'https://site.example.test/photo.jpg';}
function wc_get_product($id){return $id===2?new Mock_Product():null;}
function wc_get_product_id_by_sku($sku){return $sku==='SKU-007'?2:0;}
function wp_trim_words($s,$num){return implode(' ',array_slice(explode(' ',$s),0,$num));}
function wp_strip_all_tags($s){return strip_tags((string)$s);}
function wp_parse_args($args,$defaults){return array_merge($defaults,$args);}
function get_option($name,$default=array()){return $GLOBALS['mock_options'][$name]??$default;}
function add_option($key,$v){$GLOBALS['mock_options'][$key]=$v;}
function update_option($key,$v){$GLOBALS['mock_options'][$key]=$v;}
function delete_option($key){unset($GLOBALS['mock_options'][$key]);}
function get_transient($key){return $GLOBALS['mock_transients'][$key]??false;}
function set_transient($key,$v,$ttl){$GLOBALS['mock_transients'][$key]=$v;}
function delete_transient($key){unset($GLOBALS['mock_transients'][$key]);}
function sanitize_text_field($v){return trim(strip_tags((string)$v));}
function sanitize_title($v){return trim(preg_replace('/[^a-z0-9-]+/','-',strtolower((string)$v)),'-');}
function sanitize_key($v){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$v));}
function absint($v){return abs((int)$v);}
function sanitize_hex_color($s){return preg_match('/^#[a-f0-9]{6}$/i',(string)$s)?$s:null;}
function wp_parse_url($s,$part=-1){return parse_url($s,$part);}
function esc_url_raw($url,$protocols=array('http','https')){return preg_match('#^https?://#',(string)$url)?(string)$url:'';}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return esc_html($s);}
function esc_url($s){return esc_attr($s);}
function wp_safe_remote_get($url,$opts){$GLOBALS['mock_calls'][]=$url;$GLOBALS['mock_http_args'][]=array('url'=>$url,'options'=>$opts);
 if(str_contains($url,'brave.com/res/v1/web/search'))return response(array('web'=>array('results'=>array(
    array('title'=>'Shared Result','url'=>'https://www.example.com/a?utm_source=brave','description'=>'One'),
    array('title'=>'Brave Only','url'=>'https://other.example/b','description'=>'Two')))));
 if(str_contains($url,'api.mojeek.com') && str_contains($url,'quota-fail'))return response(array('response'=>array('status'=>'ERROR: Daily Limit Reached','results'=>array())));
 if(str_contains($url,'api.mojeek.com'))return response(array('response'=>array('results'=>array(
    array('title'=>'Shared Result','url'=>'https://www.example.com/a','desc'=>'One'),
    array('title'=>'Mojeek Only','url'=>'https://third.example/c','desc'=>'Three')))));
 if(str_contains($url,'brave.com/res/v1/images/search'))return response(array('results'=>array(array('title'=>'Photo','url'=>'https://images.example/photo','thumbnail'=>array('src'=>'https://images.example/preview')))));
 if(str_contains($url,'brave.com/res/v1/news/search'))return response(array('results'=>array(array('title'=>'Headline','url'=>'https://news.example/story'))));
 if(str_contains($url,'brave.com/res/v1/videos/search'))return response(array('results'=>array(array('title'=>'Video','url'=>'https://video.example/watch'))));
 if(str_contains($url,'wikipedia.org'))return response(array('query'=>array('pages'=>array(array('title'=>'Dubai','extract'=>'City in the UAE','fullurl'=>'https://en.wikipedia.org/wiki/Dubai')))));
 if(str_contains($url,'nominatim.openstreetmap.org'))return response(array(array('lat'=>'25.2048','lon'=>'55.2708','display_name'=>'Dubai, UAE')));
 return new WP_Error('unexpected');}
function wp_safe_remote_post($url,$opts){return response(array('translatedText'=>'مرحبا بالعالم'));}
function response($a){return array('response'=>array('code'=>200),'body'=>json_encode($a));}
function wp_remote_retrieve_response_code($r){return $r['response']['code'];}
function wp_remote_retrieve_body($r){return $r['body'];}
function is_wp_error($v){return $v instanceof WP_Error;}
function wp_http_validate_url($v){return (bool)filter_var($v,FILTER_VALIDATE_URL);}
function wp_json_encode($v){return json_encode($v);}
function add_query_arg($pairs,$url){return $url.'?'.http_build_query($pairs);}
function home_url($path='/'){return 'https://site.example.test'.$path;}
function wp_salt($what){return 'test_salt';}
function is_user_logged_in(){return false;}
function get_current_user_id(){return 0;}
function rest_ensure_response($value){return $value;}
function plugin_dir_path($file){return dirname($file).'/';}
function plugins_url($sub,$file){return 'https://site.example.test/wp-content/plugins/luxefind-search-pro/'.$sub;}
function rest_url($path){return 'https://site.example.test/wp-json/'.$path;}
function wp_unique_id(){return '123';}
function wp_register_style(...$args){}function wp_enqueue_style(...$args){}function wp_register_script(...$args){}function wp_enqueue_script(...$args){}
function wp_localize_script(...$args){}function register_block_type(...$args){return true;}
function is_admin(){return true;}
function add_action(...$args){}function add_shortcode(...$args){}function register_activation_hook(...$args){}function register_deactivation_hook(...$args){}
function register_rest_route(...$args){}function add_options_page(...$args){}function register_setting(...$args){}
function checked($a,$b=true){echo $a===$b?'checked="checked"':'';}
function wp_clear_scheduled_hook($e){return 0;}
function wp_next_scheduled($e){return false;}
function wp_schedule_event(...$a){return true;}
function shortcode_atts($a,$b,$c){return array_merge($a,$b);}
function current_user_can($role){return $role==='manage_options';}
function wp_create_nonce($v){return 'mock_nonce';}
function check_ajax_referer($a,$b,$c){return true;}
function admin_url($x){return 'https://site.example.test/wp-admin/'.$x;}
function settings_fields($x){}function wp_nonce_field($x){}function submit_button($text){echo '<button>'.esc_html($text).'</button>';}
function wp_safe_redirect($url){}function wp_die($message){throw new Exception($message);}
require dirname(__DIR__).'/includes/class-swp-plugin.php';
$plugin=LFXSP_Plugin::boot();$opts=LFXSP_Plugin::defaults();
test_it($opts['external_enabled']===0&&$opts['knowledge_enabled']===0&&$opts['maps_enabled']===0,'external services disabled by default');
test_it(count(LFXSP_Plugin::modes())===11,'11 search modes supported');
$bad=$plugin->sanitize_options(array('placeholder'=>'<script>X</script>','posts_per_page'=>999,'types'=>array('private','product'),'providers'=>array('brave','garbage'),'external_limit'=>500,'cache_minutes'=>999,'min_query_length'=>0,'accent_color'=>'expression(alert(1))'));
test_it($bad['placeholder']==='X'&&$bad['posts_per_page']===20,'sanitization of text and result bounds');
test_it($bad['types']===array('product')&&$bad['providers']===array('brave'),'post type and provider allowlists');
test_it($bad['accent_color']==='#d5b37a'&&$bad['min_query_length']===2,'safe styling and min length');
test_it(LFXSP_Providers::normalize_url('javascript:alert(1)')==='','unsafe protocol rejected');
test_it(LFXSP_Providers::normalize_url('https://user:pass@example.org/')==='','embedded credentials rejected');
test_it(LFXSP_Providers::dedupe_key('https://www.example.com/a?utm_source=x')===LFXSP_Providers::dedupe_key('https://www.example.com/a'),'tracking query dedupe');
test_it(LFXSP_Engine::instant('2+3')['value']==='5'&&LFXSP_Engine::instant('10 km to mi')['type']==='units','instant answers');
test_it(LFXSP_Engine::instant('1/0')===null,'division by zero safely rejected');
test_it(LFXSP_Engine::instant('20% of 150')['value']==='30','instant percentages');
test_it(LFXSP_Engine::instant('rgb(12, 34, 56)')['value']==='#0C2238','RGB to HEX');
test_it(LFXSP_Engine::instant('32 f to c')['value']==='0 C','Fahrenheit conversion');
test_it(LFXSP_Engine::instant('5 gb to mb')['value']==='5000 mb','data units');
test_it(LFXSP_Engine::instant('0xff to decimal')['value']==='255','hex base conversion');
test_it(LFXSP_Engine::instant('unix 1700000000')['value']==='2023-11-14 22:13:20 UTC','timestamp UTC');
test_it(preg_match('/^[a-f0-9-]{36}$/', LFXSP_Engine::instant('uuid v4')['value'])===1,'UUID generation');
test_it(in_array(LFXSP_Engine::instant('flip coin')['value'],['Heads','Tails'],true),'coin flip');
test_it(LFXSP_Engine::instant('random 2 to 2')['value']==='2','bounded random integer');
test_it(LFXSP_Engine::instant('90000000000000000000000*9')===null,'unbounded expressions rejected');
$opts['external_enabled']=1;$opts['providers']=array('brave','mojeek');
$out=LFXSP_Providers::search('Dubai',$opts);
test_it(count($out['results'])===3,'Brave + Mojeek combined with tracking dedupe');
test_it(count($out['results'][0]['sources'])===2,'RRF consensus ranks merged result first');

$mojeek_calls=array_values(array_filter($GLOBALS['mock_http_args'], static function($row){return str_contains($row['url'],'api.mojeek.com');}));
$mc=end($mojeek_calls);parse_str((string)wp_parse_url($mc['url'],PHP_URL_QUERY),$mq);
test_it(($mq['api_key']??'')==='mock_mojeek_key' && ($mq['fmt']??'')==='json','Mojeek credentials and JSON format match official contract');
test_it(!isset($mc['options']['headers']['Authorization']),'Mojeek does not use incorrect Bearer authentication');
test_it(is_wp_error(LFXSP_Providers::json_request('https://127.0.0.1/internal')),'private loopback upstream refused');
test_it(is_wp_error(LFXSP_Providers::json_request('http://api.mojeek.com/search')),'insecure HTTP upstream refused');
test_it(is_wp_error(LFXSP_Providers::json_request('https://api.search.brave.com.evil.example/search')),'lookalike hostname refused');
test_it(count(LFXSP_Providers::normalize_results(array(array('title'=>array('<script>'),'description'=>array('x'),'url'=>'https://valid.test/')), 'brave', 'web', 10))===1,'malformed upstream fields do not crash normalizer');
$bad_provider=LFXSP_Providers::search('quota-fail',$opts);
test_it(in_array('Mojeek is currently unavailable.',$bad_provider['warnings'],true),'Mojeek API quota error fails gracefully');

$calls=count($GLOBALS['mock_calls']);LFXSP_Providers::search('Dubai',$opts);
test_it(count($GLOBALS['mock_calls'])===$calls,'provider cache hit avoids duplicate API calls');
$media=LFXSP_Providers::media('images','Dubai',$opts);
test_it(count($media['results'])===1&&$media['results'][0]['thumbnail']==='https://images.example/preview','images normalized');
test_it(count(LFXSP_Providers::media('news','Dubai',$opts)['results'])===1,'news search');
test_it(count(LFXSP_Providers::media('videos','Dubai',$opts)['results'])===1,'video search');
test_it(LFXSP_Discovery::wikipedia('Dubai')['title']==='Dubai','knowledge lookup');
$opts['maps_enabled']=1;
test_it(count(LFXSP_Discovery::maps('Dubai',$opts)['results'])===1,'map search');
test_it(count(LFXSP_Discovery::maps('Dubai',$opts)['results'])===1,'map caching');
test_it(LFXSP_Discovery::bang('!gh langgraph')['source']==='shortcut','bang shortcuts');
test_it(LFXSP_Discovery::bang('!mdn css grid')['source']==='shortcut' && LFXSP_Discovery::bang('!instant uuid')['mode']==='instant','expanded built-in bangs');
test_it(LFXSP_Discovery::bang('!images watches')['mode']==='images','internal tab bangs');
$custom=LFXSP_Plugin::sanitize_bangs("docs=https://docs.example.test/?q={query}\nevil=javascript:alert({query})\nunsafe=https://u:p@example.com/?q={query}");
test_it(count($custom)===1&&isset($custom['docs']),'custom shortcuts enforce HTTPS and reject URL credentials');
test_it(str_contains(LFXSP_Discovery::bang('!docs multi agent',array('custom_bangs'=>$custom))['url'],'multi%20agent'),'custom bang safely URL-encodes query');
$opts['translate_enabled']=1;
test_it(LFXSP_Discovery::translate('Hello',$opts,'ar')['results'][0]['description']==='مرحبا بالعالم','translation bridge');
test_it(!LFXSP_Discovery::translate('hi',$opts,'invalid')['results'],'translation language allowlist');
$opts['woo_enabled']=1; // Optional integration enabled only for product-specific tests.
$GLOBALS['mock_options']['lfxsp_options']=$opts;
$data=$plugin->search(new Mock_Request(array('q'=>'Dubai','mode'=>'all')));
test_it(count($data['site'])===1&&count($data['web'])===3,'REST all mode combines local and web search');
$data=$plugin->search(new Mock_Request(array('q'=>'Golden','mode'=>'products','min_price'=>'100','max_price'=>'250','sort'=>'price_asc')));
test_it(count($data['site'])===1&&$GLOBALS['mock_last_query']['post_type']===array('product'),'WooCommerce products filtered to product post type');
test_it(count($GLOBALS['mock_last_query']['meta_query'])===2,'price conditions passed to WP_Query');
$data=$plugin->search(new Mock_Request(array('q'=>'SKU-007','mode'=>'products')));
test_it(count($data['site'])===1&&$data['site'][0]['sku']==='SKU-007','exact SKU discovery');
$data=$plugin->search(new Mock_Request(array('q'=>'SKU-007','mode'=>'products','min_price'=>'500')));
test_it(!count($data['site']),'exact SKU respects product price filters');
$data=$plugin->search(new Mock_Request(array('q'=>'Dubai','mode'=>'maps')));
test_it(count($data['media'])===1,'REST maps mode');
$data=$plugin->search(new Mock_Request(array('q'=>'Dubai','mode'=>'translate','lang'=>'ar')));
test_it(count($data['media'])===1,'REST translate mode');
$before=count($GLOBALS['mock_calls']);$plugin->search(new Mock_Request(array('q'=>'Dubai','mode'=>'site')));
test_it(count($GLOBALS['mock_calls'])===$before,'local search never calls external providers');
$bad=$plugin->search(new Mock_Request(array('q'=>'x','mode'=>'all')));
test_it(is_wp_error($bad)&&$bad->get_error_code()==='swp_invalid_query','short queries rejected');
$html=$plugin->shortcode(array('placeholder'=>'<script>alert(1)</script>'));
test_it(str_contains($html,'Find the')&&str_contains($html,'data-lfxsp-tab="images"'),'premium shortcode renders tabs');
test_it(!str_contains($html,'<script>alert(1)</script>'),'shortcode placeholder escaped');
$plugin->register_block();
test_it(count((array)get_option('lfxsp_cache_keys',array()))>=5,'media, knowledge and map cache keys tracked');
LFXSP_Engine::clear_cache();
test_it(!isset($GLOBALS['mock_options']['lfxsp_cache_keys']),'cache management clears bookkeeping');
for($i=0;$i<40;$i++)$last=$plugin->search(new Mock_Request(array('q'=>'Dubai','mode'=>'site')));
$blocked=$plugin->search(new Mock_Request(array('q'=>'Dubai','mode'=>'site')));
test_it(is_wp_error($blocked)&&$blocked->get_error_code()==='swp_rate_limited','rate limiting enforced');
echo "ALL ACCEPTANCE CHECKS PASSED\n";
