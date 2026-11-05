<?php
// suite-map-monitor-entry-v2: isolated runtime; never enables the full map.
header('Cache-Control: no-store, no-cache, must-revalidate');
define('MKA_SUITE_MONITOR_RUNTIME', true);
$_GET['monitor']='1';
$runtime=__DIR__.'/runtime';
if(!is_file($runtime.'/maps.hhvm')){http_response_code(503);exit('Monitor indisponível. Execute o instalador atualizado.');}
$base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/').'/runtime/';
ob_start(function($html)use($base){
    $head='<base href="'.htmlspecialchars($base,ENT_QUOTES,'UTF-8').'">';
    if (strpos($html,'id="app"')!==false) {
        $head.='<link rel="stylesheet" href="/admin/addons/shared/monitor_loading.css?v=20261105-1">';
        $head.='<script defer src="/admin/addons/shared/monitor_loading.js?v=20261105-1"></script>';
    }
    return preg_replace('/<head(?:\s[^>]*)?>/i','$0'.$head,$html,1);
});
chdir($runtime);
require $runtime.'/maps.hhvm';
