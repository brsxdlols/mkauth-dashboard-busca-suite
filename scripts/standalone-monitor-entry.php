<?php
// suite-map-monitor-entry-v2: isolated runtime; never enables the full map.
header('Cache-Control: no-store, no-cache, must-revalidate');
define('MKA_SUITE_MONITOR_RUNTIME', true);
$_GET['monitor']='1';
$runtime=__DIR__.'/runtime';
if(!is_file($runtime.'/maps.hhvm')){http_response_code(503);exit('Monitor indisponível. Execute o instalador atualizado.');}
$base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/').'/runtime/';
ob_start(function($html)use($base){return preg_replace('/<head(?:\s[^>]*)?>/i','$0<base href="'.htmlspecialchars($base,ENT_QUOTES,'UTF-8').'">',$html,1);});
chdir($runtime);
require $runtime.'/maps.hhvm';
