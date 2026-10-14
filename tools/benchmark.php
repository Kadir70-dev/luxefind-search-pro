<?php
/** Synthetic PHP microbenchmarks only. Does not exercise WordPress DB, hosting, API or concurrent load. */
ob_start();require dirname(__DIR__).'/tests/acceptance.php';ob_end_clean();
$cases=array('instant'=>static fn()=>LFXSP_Engine::instant('25 km to miles'),
             'url_dedupe'=>static fn()=>LFXSP_Providers::dedupe_key('https://example.org/story?utm_source=test&ref=foo'));
foreach($cases as $name=>$fn){$n=15000;$start=hrtime(true);for($i=0;$i<$n;$i++){$fn();}$ms=(hrtime(true)-$start)/1000000;
    echo $name.': '.number_format($ms,2).' ms per '.$n.' iterations ('.number_format($ms/$n,4).' ms/op)'.PHP_EOL;}
echo 'Not a search/WordPress load benchmark!'.PHP_EOL;
