<?php
require __DIR__.'/../shared/notification_bootstrap.php';
header('Cache-Control: no-store');header('Content-Type: application/json; charset=utf-8');
function paymentReply($data){echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if (!permissao('perm_titulos')) { http_response_code(403);paymentReply(array('enabled'=>false)); }
$user=!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??'');
if ($user==='') {http_response_code(403);paymentReply(array('enabled'=>false));}
if(empty($_SESSION['mka_payment_csrf']))$_SESSION['mka_payment_csrf']=bin2hex(random_bytes(24));
$token=$_SESSION['mka_payment_csrf'];session_write_close();
try {
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($token,(string)($_POST['csrf']??''))){http_response_code(403);paymentReply(array('error'=>'Sessão expirada.'));}
  if(isset($_POST['duration_seconds']) || isset($_POST['display_mode'])){
   $duration=max(1,min(30,(int)($_POST['duration_seconds']??3)));$mode=($_POST['display_mode']??'simple')==='detailed'?'detailed':'simple';
   $st=$conn->prepare('INSERT INTO mka_payment_display(username,duration_seconds,display_mode) VALUES(?,?,?) ON DUPLICATE KEY UPDATE duration_seconds=VALUES(duration_seconds),display_mode=VALUES(display_mode)');$st->bind_param('sis',$user,$duration,$mode);$st->execute();
  }
  $enabled=($_POST['enabled']??'')==='1'?1:0;
  $st=$conn->prepare('INSERT INTO mka_payment_preferences(username,enabled) VALUES(?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled)');$st->bind_param('si',$user,$enabled);$st->execute();paymentReply(array('enabled'=>(bool)$enabled));
 }
 $st=$conn->prepare('SELECT enabled FROM mka_payment_preferences WHERE username=?');$st->bind_param('s',$user);$st->execute();$pref=$st->get_result()->fetch_assoc();$enabled=!$pref||(bool)$pref['enabled'];
 $duration=3;$mode='simple';
 try{$st=$conn->prepare('SELECT duration_seconds,display_mode FROM mka_payment_display WHERE username=?');$st->bind_param('s',$user);$st->execute();$display=$st->get_result()->fetch_assoc();if($display){$duration=max(1,min(30,(int)$display['duration_seconds']));$mode=$display['display_mode']==='detailed'?'detailed':'simple';}}catch(Throwable $ignored){}
 $max=(int)$conn->query('SELECT COALESCE(MAX(id),0) id FROM mka_payment_events')->fetch_assoc()['id'];
 $st=$conn->prepare('SELECT cli_grupos FROM sis_acesso WHERE login=? LIMIT 1');$st->bind_param('s',$user);$st->execute();$access=$st->get_result()->fetch_assoc();
 if(!$access){http_response_code(403);paymentReply(array('enabled'=>false));}
 $groups=array_map('trim',explode(',',(string)$access['cli_grupos']));$full=trim((string)$access['cli_grupos'])===''||in_array('full_clientes',$groups,true);
 $events=array();$cursor=isset($_GET['after'])?max(0,(int)$_GET['after']):$max;$last=$cursor;
 if($enabled && $cursor<$max){
  $st=$conn->prepare('SELECT e.*,c.nome,c.plano,c.grupo FROM mka_payment_events e JOIN sis_lanc l ON l.id=e.invoice_id AND l.status=\'pago\' AND COALESCE(l.deltitulo,0)=0 LEFT JOIN sis_cliente c ON c.login=e.login WHERE e.id>? ORDER BY e.id LIMIT 100');$st->bind_param('i',$cursor);$st->execute();$rs=$st->get_result();
  while($row=$rs->fetch_assoc()){$last=(int)$row['id'];if(!$full&&!in_array($row['grupo'],$groups,true))continue;
   // Never replay old payments after a long idle period.
   if(strtotime($row['created_at'])<time()-120)continue;
   $events[]=array('id'=>$row['id'],'name'=>$row['nome']?:$row['login'],'amount'=>$row['amount'],'due'=>$row['due_at'],'paid'=>$row['paid_at'],'plan'=>$row['plano'],'description'=>$row['description']);
  }
  if($rs->num_rows<100)$last=$max;
 }
 if(!$enabled)$last=$max;
 // Explicit CLI preview, isolated from the real financial event queue.
 $previewPath=__DIR__.'/../shared/payment-previews/'.hash('sha256',$user).'.json';
 if($enabled && is_file($previewPath)){
  $preview=json_decode((string)file_get_contents($previewPath),true);
  if(is_array($preview) && !empty($preview['demo']) && ($preview['expires']??0)>time())$events[]=$preview;
 }
 paymentReply(array('enabled'=>$enabled,'duration_seconds'=>$duration,'display_mode'=>$mode,'csrf'=>$token,'user_key'=>hash('sha256',$user),'cursor'=>$last,'events'=>$events));
}catch(Throwable $e){http_response_code(503);paymentReply(array('enabled'=>false,'error'=>'Notificações não instaladas ou indisponíveis.'));}
