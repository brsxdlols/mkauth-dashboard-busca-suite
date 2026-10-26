<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/server_timezone.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
$user=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
$q=$conn->prepare("SELECT 1 FROM sis_perm WHERE usuario=? AND nome='perm_config' AND permissao='sim' LIMIT 1");$q->bind_param('s',$user);$q->execute();if(!$q->get_result()->fetch_row()){http_response_code(403);exit;}
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
echo json_encode(mka_server_timezone_snapshot($conn),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
