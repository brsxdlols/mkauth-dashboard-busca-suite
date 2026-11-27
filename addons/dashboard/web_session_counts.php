<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/web_session_counts.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
if(($_SERVER['HTTP_X_MKA_SESSION_HEARTBEAT']??'')==='1')mka_web_session_track_current();
$counts=mka_web_session_counts();
if($counts===null){http_response_code(503);echo json_encode(array('error'=>'Contagem de sessoes indisponivel.'));exit;}
$counts['session_url']=mka_web_session_native_url()??'/admin/addons/dashboard/web_sessions.php';
echo json_encode($counts);


