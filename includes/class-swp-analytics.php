<?php
if (!defined('ABSPATH')) { exit; }
/** Anonymous daily search totals. No raw queries, visitor IPs or identities are persisted. */
final class LFXSP_Analytics {
    public static function record($query, $count) {
        $key='lfxsp_stat_'.gmdate('Ymd');
        $value=(array)get_option($key,array('searches'=>0,'zero'=>0));
        $value['searches']=min(10000000,(int)($value['searches']??0)+1);
        if ($count===0) { $value['zero']=min(10000000,(int)($value['zero']??0)+1); }
        update_option($key,$value,false);
    }
    public static function total() {
        $sum=0;
        for ($i=0;$i<14;$i++) { $sum+=(int)((array)get_option('lfxsp_stat_'.gmdate('Ymd',time()-DAY_IN_SECONDS*$i),array('searches'=>0)))['searches']; }
        return $sum;
    }
    public static function cleanup() {
        for ($i=60;$i<90;$i++) { delete_option('lfxsp_stat_'.gmdate('Ymd',time()-DAY_IN_SECONDS*$i)); }
    }
    public static function clear() {
        for ($i=0;$i<90;$i++) { delete_option('lfxsp_stat_'.gmdate('Ymd',time()-DAY_IN_SECONDS*$i)); }
    }
}
