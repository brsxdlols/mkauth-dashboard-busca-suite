<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/additional_connection.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function additional_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
$canEdit=$operator!==''&&mka_installation_permission($conn,$operator,'perm_altadicional');
if(!$canEdit&&!mka_installation_permission($conn,$operator,'perm_adicionais'))additional_reply(403,array('error'=>'Sem permissão para consultar adicionais.'));
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,array('GET','POST'),true))additional_reply(405,array('error'=>'Método não permitido.'));
$uuid=($method==='POST'?$_POST:$_GET)['uuid']??'';
if(!is_string($uuid)||!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$uuid))additional_reply(422,array('error'=>'Adicional inválido.'));
try {
 $conn->set_charset('utf8mb4');$row=mka_additional_connection($conn,$uuid,mka_attendance_scope($conn,$operator));
 if(empty($_SESSION['suite_additional_csrf']))$_SESSION['suite_additional_csrf']=bin2hex(random_bytes(24));
 $csrf=$_SESSION['suite_additional_csrf'];
 if($method==='POST') {
  if(!$canEdit)additional_reply(403,array('error'=>'Sem permissão para reparar adicionais.'));
  if(!is_string($_POST['csrf']??null)||!hash_equals($csrf,$_POST['csrf']))additional_reply(403,array('error'=>'Atualize o modal e tente novamente.'));
  if(($_POST['action']??'')!=='repair')additional_reply(422,array('error'=>'Operação inválida.'));
  $repair=mka_additional_repair_methods();if(!$repair)additional_reply(503,array('error'=>'Reparo nativo indisponível nesta versão.'));
  session_write_close();
  // Same native routines used for each additional in reparar.hhvm; never repair the parent.
  call_user_func($repair[0],$row['username']);
  call_user_func($repair[1],$row['username']);
  additional_reply(200,array('ok'=>true,'message'=>'Reparo do adicional executado. Aguarde a reconexão.'));
 }
 session_write_close();$ext=is_file(__DIR__.'/../../adicional_alt.hhvm')?'hhvm':'php';
 additional_reply(200,array('additional'=>$row,'csrf'=>$csrf,'can_edit'=>$canEdit,'edit_url'=>'/admin/adicional_alt.'.$ext.'?uuid='.rawurlencode($uuid)));
} catch(DomainException $e){additional_reply(404,array('error'=>$e->getMessage()));}
catch(Throwable $e){additional_reply(500,array('error'=>'Não foi possível consultar ou reparar este adicional.'));}
