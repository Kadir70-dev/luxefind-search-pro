<?php
if (!defined('ABSPATH')) { exit; }
/** Deterministic, server-side instant answers. No eval(), remote fetches, or arbitrary regex execution. */
final class LFXSP_Instant {
    private static function answer($type, $title, $value) {
        return array('type'=>$type, 'title'=>$title, 'value'=>(string)$value);
    }
    private static function decimal($value, $digits = 7) {
        if (!is_finite($value)) { return null; }
        return rtrim(rtrim(number_format(round($value, $digits), $digits, '.', ''), '0'), '.') ?: '0';
    }
    public static function evaluate($raw) {
        $q = trim(strtolower((string)$raw));
        if (strlen($q) > 160) { return null; }
        if (preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/', $q, $m)) {
            return self::answer('color', 'Hex color', '#'.strtoupper($m[1]));
        }
        if (preg_match('/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $q, $m)) {
            $channels=array_map('intval',array_slice($m,1));
            if (max($channels) <= 255) { return self::answer('color','RGB to HEX',sprintf('#%02X%02X%02X',...$channels)); }
        }
        if (preg_match('/^(\d+(?:\.\d+)?)\s*%\s+of\s+(-?\d+(?:\.\d+)?)$/', $q, $m)) {
            $v=self::decimal((float)$m[1]*(float)$m[2]/100);
            return $v===null?null:self::answer('percent','Percentage',$v);
        }
        if (preg_match('/^(-?\d{1,12}(?:\.\d{1,8})?)\s*([+*\/-])\s*(-?\d{1,12}(?:\.\d{1,8})?)$/',$q,$m)) {
            $a=(float)$m[1];$b=(float)$m[3];$v=null;
            switch($m[2]){case '+':$v=$a+$b;break;case '-':$v=$a-$b;break;case '*':$v=$a*$b;break;case '/':if($b!=0)$v=$a/$b;break;}
            if ($v!==null) { $v=self::decimal($v,8); return $v===null?null:self::answer('math','Calculator',$v); }
        }
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(km|mi|miles|m|cm|mm|ft|in|kg|g|lb|lbs|oz|l|ml|gal|gb|mb|kb|byte|bytes|s|min|hr|h)\s+(?:to|in)\s+(km|mi|miles|m|cm|mm|ft|in|kg|g|lb|lbs|oz|l|ml|gal|gb|mb|kb|byte|bytes|s|min|hr|h)$/',$q,$m)) {
            $factors=array('km'=>['length',1000],'mi'=>['length',1609.344],'miles'=>['length',1609.344],'m'=>['length',1],'cm'=>['length',.01],'mm'=>['length',.001],'ft'=>['length',.3048],'in'=>['length',.0254],
                'kg'=>['mass',1000],'g'=>['mass',1],'lb'=>['mass',453.59237],'lbs'=>['mass',453.59237],'oz'=>['mass',28.349523125],
                'l'=>['volume',1],'ml'=>['volume',.001],'gal'=>['volume',3.785411784],
                'gb'=>['data',1000000000],'mb'=>['data',1000000],'kb'=>['data',1000],'byte'=>['data',1],'bytes'=>['data',1],
                's'=>['time',1],'min'=>['time',60],'hr'=>['time',3600],'h'=>['time',3600]);
            $from=$factors[$m[2]];$to=$factors[$m[3]];
            if ($from[0]===$to[0]) { $v=self::decimal((float)$m[1]*$from[1]/$to[1],7);return $v===null?null:self::answer('units','Unit conversion',$v.' '.$m[3]); }
        }
        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*(c|f|k|celsius|fahrenheit|kelvin)\s+(?:to|in)\s+(c|f|k|celsius|fahrenheit|kelvin)$/',$q,$m)) {
            $names=['c'=>'c','celsius'=>'c','f'=>'f','fahrenheit'=>'f','k'=>'k','kelvin'=>'k'];
            $from=$names[$m[2]];$to=$names[$m[3]];$n=(float)$m[1];
            $k=$from==='c'?$n+273.15:($from==='f'?($n+459.67)*5/9:$n);
            if ($k<0) { return null; }
            $v=$to==='c'?$k-273.15:($to==='f'?$k*9/5-459.67:$k);
            $v=self::decimal($v,5);
            return $v===null?null:self::answer('temperature','Temperature',$v.' '.strtoupper($to));
        }
        if (preg_match('/^(?:0x)([0-9a-f]{1,12})\s+to\s+(?:decimal|dec)$/',$q,$m)) {
            return self::answer('base','Hex to decimal',(string)hexdec($m[1]));
        }
        if (preg_match('/^(\d{1,12})\s+(?:decimal|dec)\s+to\s+hex$/',$q,$m)) {
            return self::answer('base','Decimal to hex','0x'.strtoupper(dechex((int)$m[1])));
        }
        if (preg_match('/^(?:unix|timestamp)\s+(\d{1,10})$/',$q,$m)) {
            $date=gmdate('Y-m-d H:i:s', (int)$m[1]);
            return self::answer('time','UTC timestamp',$date.' UTC');
        }
        if (preg_match('/^(?:flip coin|coin toss)$/',$q)) {
            return self::answer('random','Coin toss',random_int(0,1)===0?'Heads':'Tails');
        }
        if (preg_match('/^(?:roll dice|roll die)$/',$q)) {
            return self::answer('random','Six-sided die',(string)random_int(1,6));
        }
        if (preg_match('/^random\s+(-?\d{1,8})\s+(?:to|-)\s+(-?\d{1,8})$/',$q,$m)) {
            $a=(int)$m[1];$b=(int)$m[2];
            return $a<=$b?self::answer('random','Random integer',(string)random_int($a,$b)):null;
        }
        if ($q==='uuid' || $q==='uuid v4') {
            $b=random_bytes(16);$b[6]=chr((ord($b[6])&0x0f)|0x40);$b[8]=chr((ord($b[8])&0x3f)|0x80);
            $hex=bin2hex($b);
            return self::answer('uuid','UUID v4',substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20));
        }
        return null;
    }
}
