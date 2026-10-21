<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/ip_conflicts.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
if($operator===''){http_response_code(403);exit;}
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
try{$conn->set_charset('utf8mb4');$groups=mka_ip_conflicts($conn,mka_attendance_scope($conn,$operator));echo json_encode(array('conflicts'=>$groups,'checked_at'=>date('c')),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}
catch(Throwable $e){http_response_code(503);echo json_encode(array('error'=>'Verificação de IPs indisponível no momento.'));}
