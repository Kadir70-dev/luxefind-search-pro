<?php
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . "/class-swp-instant.php";
final class LFXSP_Engine {
    public static function local_search($q, $options, $mode = 'site', $filters = array()) {
        $types = array_values(array_intersect((array) ($options['types'] ?? array('post','page')), get_post_types(array('public'=>true))));
        if (empty($options['woo_enabled']) || !function_exists('wc_get_product')) {
            $types = array_values(array_diff($types, array('product')));
        }
        if ($mode === 'products') { $types = !empty($options['woo_enabled']) && function_exists('wc_get_product') ? array('product') : array(); }
        if (!$types) { return array(); }
        $args = array('s' => $q, 'post_type' => $types, 'post_status' => 'publish',
            'posts_per_page' => max(1,min(20,(int)$options['posts_per_page'])),
            'ignore_sticky_posts' => true, 'no_found_rows' => true, 'suppress_filters' => false,
            'orderby' => 'relevance');
        if ($mode === 'products' && in_array('product',$types,true)) {
            if (!empty($filters['category'])) {
                $args['tax_query'] = array(array('taxonomy'=>'product_cat','field'=>'slug',
                    'terms'=>sanitize_title($filters['category'])));
            }
            $meta = array();
            if (isset($filters['min_price']) && $filters['min_price'] !== '') {
                $meta[] = array('key'=>'_price','value'=>max(0,(float)$filters['min_price']),'compare'=>'>=','type'=>'DECIMAL(12,2)');
            }
            if (isset($filters['max_price']) && $filters['max_price'] !== '') {
                $meta[] = array('key'=>'_price','value'=>max(0,(float)$filters['max_price']),'compare'=>'<=','type'=>'DECIMAL(12,2)');
            }
            if ($meta) { $args['meta_query'] = $meta; }
            $sort = $filters['sort'] ?? 'relevance';
            if ($sort === 'newest') { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
            if ($sort === 'price_asc' || $sort === 'price_desc') {
                $args['meta_key'] = '_price'; $args['orderby'] = 'meta_value_num';
                $args['order'] = $sort === 'price_asc' ? 'ASC' : 'DESC';
            }
        }
        $query = new WP_Query($args);
        $items = array();
        foreach ((array) $query->posts as $post) {
            if (count($items) >= (int) $args['posts_per_page']) { break; }
            $type = get_post_type($post);
            $product = $type === 'product' && function_exists('wc_get_product') ? wc_get_product($post->ID) : null;
            if ($type === 'product' && (!$product || !$product->is_visible())) { continue; }
            $item = array('title'=>get_the_title($post), 'url'=>get_permalink($post),
                'description'=>wp_trim_words(wp_strip_all_tags(get_the_excerpt($post)), 25),
                'source'=>'site', 'type'=>$type, 'thumbnail'=>'');
            if ($product) {
                $item['price'] = wp_strip_all_tags($product->get_price_html());
                $item['sku'] = $product->get_sku();
                $item['in_stock'] = $product->is_in_stock();
            }
            if (function_exists('get_the_post_thumbnail_url')) {
                $item['thumbnail'] = (string) get_the_post_thumbnail_url($post, 'medium');
            }
            $items[] = $item;
        }
        // WooCommerce SKU exact lookup: a SKU often doesn't appear in post title/content.
        if ($mode === 'products' && function_exists('wc_get_product_id_by_sku') && count($items) < (int)$args['posts_per_page']) {
            $product_id = (int) wc_get_product_id_by_sku($q);
            if ($product_id && get_post_status($product_id) === 'publish') {
                $product = wc_get_product($product_id);
                if ($product && $product->is_visible() && self::product_matches_filters($product, $product_id, $filters)) {
                    $exists = false;
                    foreach ($items as $item) { if ($item['url'] === get_permalink($product_id)) { $exists=true; break; } }
                    if (!$exists) { array_unshift($items, array('title'=>get_the_title($product_id),
                        'url'=>get_permalink($product_id), 'description'=>wp_trim_words(wp_strip_all_tags(get_the_excerpt($product_id)),25),
                        'source'=>'site', 'type'=>'product', 'price'=>wp_strip_all_tags($product->get_price_html()),
                        'sku'=>$product->get_sku(), 'in_stock'=>$product->is_in_stock(),
                        'thumbnail'=>function_exists('get_the_post_thumbnail_url') ? (string)get_the_post_thumbnail_url($product_id,'medium') : '')); }
                }
            }
        }
        return array_slice($items, 0, (int)$args['posts_per_page']);
    }
    private static function product_matches_filters($product, $product_id, $filters) {
        if (!empty($filters['category']) && function_exists('has_term') && !has_term($filters['category'], 'product_cat', $product_id)) { return false; }
        if (method_exists($product,'get_price')) {
            $price=(float)$product->get_price();
            if (isset($filters['min_price']) && $filters['min_price']!=='' && $price<(float)$filters['min_price']) { return false; }
            if (isset($filters['max_price']) && $filters['max_price']!=='' && $price>(float)$filters['max_price']) { return false; }
        }
        return true;
    }
    /** Safe, deterministic instant-answer parser; external search not required. */
    public static function instant($q) { return LFXSP_Instant::evaluate($q); }
    public static function clear_cache() {
        foreach (array_keys((array) get_option('lfxsp_cache_keys', array())) as $key) { delete_transient($key); }
        delete_option('lfxsp_cache_keys');
    }
}
