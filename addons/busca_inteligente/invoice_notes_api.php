<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/layout_mode.php';require_once __DIR__.'/../shared/invoice_financial_notes.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function note_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
$canRead=mka_installation_permission($conn,$operator,'perm_clifin')||mka_installation_permission($conn,$operator,'perm_titulos');
$canWrite=mka_installation_permission($conn,$operator,'perm_alttitulos');$canProtect=mka_installation_permission($conn,$operator,'perm_observacao');
if(!$canRead)note_reply(403,array('error'=>'Sem permissão para consultar o financeiro.'));
$method=$_SERVER['REQUEST_METHOD']??'GET';if(!in_array($method,array('GET','POST'),true))note_reply(405,array('error'=>'Método não permitido.'));
$input=$method==='POST'?$_POST:$_GET;
foreach($input as $value)if(!is_string($value))note_reply(422,array('error'=>'Dados inválidos.'));
$uuid=$input['uuid']??'';$id=filter_var($input['invoice']??0,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
if(!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$uuid)||!$id)note_reply(422,array('error'=>'Fatura inválida.'));
if(empty($_SESSION['suite_invoice_note_csrf']))$_SESSION['suite_invoice_note_csrf']=bin2hex(random_bytes(24));$csrf=$_SESSION['suite_invoice_note_csrf'];
if($method==='POST'&&(!$canWrite||!isset($input['csrf'])||!hash_equals($csrf,$input['csrf'])))note_reply(403,array('error'=>'Sem permissão ou sessão expirada. Reabra a nota.'));
try{
 $conn->set_charset('utf8mb4');$scope=mka_attendance_scope($conn,$operator);$client=mka_invoice_note_client($conn,$uuid,$id,$scope);
 $settings=mka_suite_get_trust_unlock_settings($conn);$recent=mka_suite_get_recent_trust_unlock($conn,$client['login'],$settings['recent_days']);
 session_write_close();
 if($method==='POST'){
  if(!in_array($input['action']??'',array('save','resolve'),true))note_reply(422,array('error'=>'Operação inválida.'));
  mka_invoice_notes_schema($conn);mka_suite_ensure_trust_unlock_audit($conn);
  mka_invoice_note_save($conn,$uuid,$id,$scope,$operator,$input,$canProtect,$settings);$client=mka_invoice_note_client($conn,$uuid,$id,$scope);
 }
 note_reply(200,array('client_note_count'=>mka_invoice_note_counts($conn,array($uuid))[$uuid]??0,'history'=>mka_invoice_note_history($conn,$uuid,$id),'csrf'=>$csrf,'can_write'=>$canWrite,'can_protect'=>$canProtect,'settings'=>$settings,'recent_unlock'=>$recent?array('operator'=>$recent['performed_by'],'created_at'=>$recent['created_at']):null,'protection'=>array('enabled'=>$client['observacao']==='sim','until'=>$client['rem_obs']),'today'=>date('Y-m-d')));
}catch(InvalidArgumentException $e){note_reply(422,array('error'=>$e->getMessage()));}
catch(UnexpectedValueException $e){note_reply(409,array('error'=>$e->getMessage()));}
catch(DomainException $e){note_reply(403,array('error'=>$e->getMessage()));}
catch(Throwable $e){error_log('Invoice notes: '.$e->getMessage());note_reply(503,array('error'=>'Não foi possível registrar a nota financeira.'));}
