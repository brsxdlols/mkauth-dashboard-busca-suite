<?php
require __DIR__.'/../shared/notification_bootstrap.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$allowed=permissao('perm_config');
if(empty($_SESSION['suite_time_csrf']))$_SESSION['suite_time_csrf']=bin2hex(random_bytes(24));
$csrf=$_SESSION['suite_time_csrf'];session_write_close();
$dir='/opt/mk-auth/dados/suite-time-monitor';$method=$_SERVER['REQUEST_METHOD'];
if($method==='POST'){
 if(!$allowed||!hash_equals($csrf,(string)($_POST['csrf']??''))){http_response_code(403);exit(json_encode(array('error'=>'Permissao ou sessao invalida.')));}
 if(($_POST['action']??'')!=='repair'){http_response_code(400);exit;}
 $previous=is_file($dir.'/status.json')?json_decode((string)file_get_contents($dir.'/status.json'),true):null;
 if(is_array($previous)&&($previous['repair_status']??'')==='running'){http_response_code(409);exit(json_encode(array('error'=>'Uma correcao ja esta em execucao. Aguarde o resultado.')));}
 $id=bin2hex(random_bytes(16));$f=@fopen($dir.'/request.json','x');
 if(!$f){http_response_code(409);exit(json_encode(array('error'=>'Ja existe uma correcao aguardando execucao, ou o monitor nao foi instalado.')));}
 fwrite($f,json_encode(array('id'=>$id,'action'=>'repair')));fclose($f);chmod($dir.'/request.json',0660);
 echo json_encode(array('queued'=>true,'id'=>$id));exit;
}
if($method!=='GET'){http_response_code(405);exit;}
$state=is_file($dir.'/status.json')?json_decode((string)file_get_contents($dir.'/status.json'),true):null;
if(is_array($state)&&abs(time()-(int)$state['checked_at'])>300)$state['issue']='stale';
echo json_encode(array('epoch_ms'=>round(microtime(true)*1000),'timezone'=>date_default_timezone_get(),'alert_enabled'=>mka_suite_get_radius_alert_enabled($conn)==='s','can_repair'=>$allowed,'csrf'=>$allowed?$csrf:null,'status'=>$state),JSON_UNESCAPED_UNICODE);
