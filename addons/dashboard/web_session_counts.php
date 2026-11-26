<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/web_session_counts.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
$counts=mka_web_session_counts();
if($counts===null){http_response_code(503);echo json_encode(array('error'=>'Contagem de sessoes indisponivel.'));exit;}
$counts['session_url']=is_file(dirname(__DIR__,2).'/sessoes.hhvm')?'/admin/sessoes.hhvm':(is_file(dirname(__DIR__,2).'/sessoes.php')?'/admin/sessoes.php':'/admin/addons/dashboard/web_sessions.php');
echo json_encode($counts);


