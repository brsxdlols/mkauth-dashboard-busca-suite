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
$provider=null;$q=@mysqli_query($conn,'SELECT cidade,estado FROM sis_provedor ORDER BY id LIMIT 1');
if($q)$provider=mysqli_fetch_assoc($q);
$uf=$provider?strtoupper(trim((string)$provider['estado'])):'';
$zones=array('AC'=>'America/Rio_Branco','AL'=>'America/Maceio','AP'=>'America/Belem','BA'=>'America/Bahia','CE'=>'America/Fortaleza','DF'=>'America/Sao_Paulo','ES'=>'America/Sao_Paulo','GO'=>'America/Sao_Paulo','MA'=>'America/Fortaleza','MT'=>'America/Cuiaba','MS'=>'America/Campo_Grande','MG'=>'America/Sao_Paulo','PA'=>'America/Belem','PB'=>'America/Fortaleza','PR'=>'America/Sao_Paulo','PI'=>'America/Fortaleza','RJ'=>'America/Sao_Paulo','RN'=>'America/Fortaleza','RS'=>'America/Sao_Paulo','RO'=>'America/Porto_Velho','RR'=>'America/Boa_Vista','SC'=>'America/Sao_Paulo','SP'=>'America/Sao_Paulo','SE'=>'America/Maceio','TO'=>'America/Araguaina');
$expected=isset($zones[$uf])?$zones[$uf]:null;
if($uf==='PE')$expected=stripos((string)$provider['cidade'],'noronha')!==false?'America/Noronha':'America/Recife';
$expectedOffset=null;
if($expected){$expectedOffset=-10800;if($uf==='AC')$expectedOffset=-18000;if(in_array($uf,array('MT','MS','RO','RR'),true))$expectedOffset=-14400;if($expected==='America/Noronha')$expectedOffset=-7200;}
$config=array('provider_city'=>$provider?(string)$provider['cidade']:'','provider_state'=>$uf,'expected_timezone'=>$expected,'expected_utc_offset'=>$expectedOffset,'php_timezone'=>date_default_timezone_get(),'php_utc_offset'=>(int)date('Z'),'php_dst'=>date('I')==='1','server_utc'=>gmdate('d/m/Y H:i:s').' UTC','reference_utc'=>is_array($state)&&isset($state['offset_seconds'])?gmdate('d/m/Y H:i:s',(int)round(time()+$state['offset_seconds'])).' UTC':null);
$config['server_local']=gmdate('d/m/Y H:i:s',time()+(int)($state['system_utc_offset']??date('Z')));
$config['php_local']=date('d/m/Y H:i:s');
$config['reference_local']=is_array($state)&&isset($state['offset_seconds'])&&$expectedOffset!==null?gmdate('d/m/Y H:i:s',(int)round(time()+$state['offset_seconds']+$expectedOffset)):null;
if(is_array($state)&&$expectedOffset!==null){
 $mismatch=(isset($state['system_utc_offset'])&&(int)$state['system_utc_offset']!==$expectedOffset)||(int)date('Z')!==$expectedOffset;
 if($mismatch&&$state['issue']==='ok')$state['issue']='timezone_mismatch';
 if(($state['repair_status']??'')==='success'){
  if($mismatch){$state['repair_status']='pending';$state['repair_message']='O relogio foi sincronizado, mas o fuso Linux/PHP ainda diverge da localidade. A correcao ainda nao foi confirmada; confira os detalhes.';}
  elseif($state['issue']!=='ok'){$state['repair_status']='pending';$state['repair_message']='Fusos conferidos, mas o diagnostico ainda apresenta uma pendencia de sincronizacao. Aguarde a proxima verificacao.';}
  else $state['repair_message']='Correcao verificada: relogio sincronizado e fusos Linux/PHP de acordo com a localidade do provedor.';
 }
}
echo json_encode(array('epoch_ms'=>round(microtime(true)*1000),'timezone'=>date_default_timezone_get(),'alert_enabled'=>mka_suite_get_radius_alert_enabled($conn)==='s','can_repair'=>$allowed,'csrf'=>$allowed?$csrf:null,'status'=>$state,'configuration'=>$config),JSON_UNESCAPED_UNICODE);
