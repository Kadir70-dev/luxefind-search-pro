<?php
ob_start();require __DIR__.'/acceptance.php';ob_end_clean();
$opts=LFXSP_Plugin::defaults();
$opts['external_enabled']=1;
$opts['woo_enabled']=1; // Showcase optional feature in mock design preview.
$opts['maps_enabled']=1;
$opts['knowledge_enabled']=1;
$opts['translate_enabled']=1;
$GLOBALS['mock_options']['lfxsp_options']=$opts;
$shortcode=LFXSP_Plugin::boot()->shortcode();
echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LuxeFind Search Pro — Mock Design Preview</title><link rel="stylesheet" href="../assets/search.css"><style>body{background:#edeae3;min-height:100vh;padding:50px 20px;margin:0}body:before{content:"DESIGN PREVIEW — MOCK DATA, NOT LIVE WORDPRESS";display:block;text-align:center;letter-spacing:3px;font:12px Arial,sans-serif;color:#605c52;margin-bottom:32px}@media(max-width:600px){body{padding:12px 8px}}</style></head><body>';
echo $shortcode;
echo '<script>window.lfxspConfig={endpoint:"http://127.0.0.1:8765/wp-json/luxefind/v1/search",min:2,autocomplete:true,thumbnails:true};</script><script src="../assets/search.js"></script></body></html>';
