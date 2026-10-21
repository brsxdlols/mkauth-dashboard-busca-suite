<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/support_attendance.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
$login=$_GET['login']??'';
if($operator===''||!is_string($login)||trim($login)===''||strlen($login)>64){http_response_code(422);exit(json_encode(array('error'=>'Login inválido.')));}
$login=trim($login);$scope=mka_attendance_scope($conn,$operator);
$q=$conn->prepare("SELECT c.login FROM sis_cliente c WHERE c.login=? AND ($scope) UNION ALL SELECT a.username FROM sis_adicional a INNER JOIN sis_cliente c ON c.login=a.login WHERE a.username=? AND ($scope) LIMIT 1");
$q->bind_param('ss',$login,$login);$q->execute();$allowed=$q->get_result()->fetch_row();$q->close();
if(!$allowed){http_response_code(404);exit(json_encode(array('error'=>'Conexão não encontrada.')));}
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
$q=$conn->prepare('SELECT radacctid,framedipaddress,nasipaddress,acctstarttime FROM radacct WHERE username=? AND acctstoptime IS NULL ORDER BY radacctid DESC LIMIT 1');
$q->bind_param('s',$login);$q->execute();$session=$q->get_result()->fetch_assoc();$q->close();
if(!$session)exit(json_encode(array('online'=>false,'checked_at'=>date('c'))));
$seconds=max(0,time()-strtotime($session['acctstarttime']));$days=floor($seconds/86400);$hours=floor(($seconds%86400)/3600);$minutes=floor(($seconds%3600)/60);
echo json_encode(array('online'=>true,'session_id'=>(string)$session['radacctid'],'ip'=>$session['framedipaddress'],'nas'=>$session['nasipaddress'],'uptime'=>($days?$days.'d ':'').$hours.'h '.$minutes.'min','checked_at'=>date('c')),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
