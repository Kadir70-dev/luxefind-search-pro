<?php
/** Deterministic fuzz/safety testing via mocked WordPress APIs; NOT live security testing. */
ob_start();require __DIR__.'/acceptance.php';ob_end_clean();
set_error_handler(static function($level,$message){throw new ErrorException($message,0,$level);});
$values=array(null, false, true, 0, 4.5, '', '<script>alert(1)</script>', 'javascript:alert(1)',
    'https://127.0.0.1/', 'https://example.com/?utm_source=evil', 'a'.str_repeat('x',2050),
    array('<svg/onload=alert(1)>'), array('nested'=>array('x')), new stdClass());
$count=0;
foreach($values as $value){
    $item=array('title'=>$value,'description'=>$value,'url'=>$value,'thumbnail'=>$value);
    try {
        $normalized=LFXSP_Providers::normalize_results(array($item),'brave','web',10);
        if ($normalized) {
            $result=$normalized[0];
            if (!preg_match('#^https?://#',$result['url'])) {throw new RuntimeException('Unsafe URL accepted');}
            if (str_contains($result['title'],'<script') || str_contains($result['description'],'<script')) {throw new RuntimeException('Raw HTML accepted');}
        }
        ++$count;
    } catch(Throwable $e){fwrite(STDERR,'FAIL fuzz case '.var_export($value,true).': '.$e->getMessage().PHP_EOL);exit(1);}
}
mt_srand(14);
for($i=0;$i<300;$i++){
    $chars='abcdef0123456789<>:;{}[]@!?/\\&%#\'\"';$v='';
    for($j=0;$j<mt_rand(0,90);$j++)$v.=$chars[mt_rand(0,strlen($chars)-1)];
    try{
        LFXSP_Instant::evaluate($v);
        LFXSP_Providers::normalize_url($v);
        LFXSP_Plugin::sanitize_bangs('x=https://example.org/?q={query}'."\n".$v);
        LFXSP_Discovery::bang($v);
    }catch(Throwable $e){fwrite(STDERR,'FAIL random fuzz '.$e->getMessage().PHP_EOL);exit(1);}
    ++$count;
}
restore_error_handler();echo "PASS: $count deterministic malformed-input fuzz cases (mock WordPress)\n";
