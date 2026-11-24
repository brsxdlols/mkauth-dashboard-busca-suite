<?php
require __DIR__.'/notification_bootstrap.php';require_once __DIR__.'/mobile_menu.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
try {echo json_encode(array('version'=>mka_mobile_menu_version($conn)));}
catch(Throwable $e){http_response_code(503);echo json_encode(array('error'=>'Não foi possível consultar o menu.'));}
