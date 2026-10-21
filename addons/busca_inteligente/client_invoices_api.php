<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/client_invoices.php';
require_once __DIR__.'/../shared/invoice_financial_notes.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function invoices_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
if($operator===''||(!mka_installation_permission($conn,$operator,'perm_clifin')&&!mka_installation_permission($conn,$operator,'perm_titulos')))invoices_reply(403,array('error'=>'Sem permissão para consultar faturas.'));
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')invoices_reply(405,array('error'=>'Método não permitido.'));
$uuid=$_GET['uuid']??'';$page=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1,'max_range'=>1000000)));
if(!is_string($uuid)||!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$uuid)||$page===false)invoices_reply(422,array('error'=>'Cliente ou página inválidos.'));
try{$conn->set_charset('utf8mb4');if(isset($_GET['download'])){$id=filter_var($_GET['download'],FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));if($id===false)invoices_reply(422,array('error'=>'Fatura inválida.'));if(!mka_installation_permission($conn,$operator,'perm_titprint'))invoices_reply(403,array('error'=>'Sem permissão para imprimir boletos.'));$pdf=mka_client_invoice_pdf($conn,$uuid,mka_attendance_scope($conn,$operator),$id);header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="boleto-'.$id.'.pdf"');header('X-Content-Type-Options: nosniff');readfile($pdf);exit;}if(isset($_GET['pix'])){$id=filter_var($_GET['pix'],FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));if($id===false)invoices_reply(422,array('error'=>'Fatura inválida.'));invoices_reply(200,array('pix'=>mka_client_invoice_pix($conn,$uuid,mka_attendance_scope($conn,$operator),$id)));}if(isset($_GET['invoice'])){$id=filter_var($_GET['invoice'],FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));if($id===false)invoices_reply(422,array('error'=>'Fatura inválida.'));invoices_reply(200,array('calculation'=>mka_client_invoice_interest($conn,$uuid,mka_attendance_scope($conn,$operator),$id)));}$data=mka_client_open_invoices($conn,$uuid,mka_attendance_scope($conn,$operator),$page);$ext=is_file(__DIR__.'/../../cliente_det.hhvm')?'hhvm':'php';$data['details_url']='/admin/cliente_det.'.$ext.'?'.http_build_query(array('uuid'=>$uuid));$canReceive=mka_installation_permission($conn,$operator,'perm_receber');$canEdit=mka_installation_permission($conn,$operator,'perm_alttitulos');$canPrint=mka_installation_permission($conn,$operator,'perm_titprint');
$notes=mka_invoice_note_latest($conn,$uuid,array_column($data['invoices'],'id'));$noteCounts=mka_invoice_note_counts($conn,array($uuid));$data['financial_note_count']=$noteCounts[$uuid]??0;
foreach($data['invoices'] as &$invoice){
 $invoice['financial_note']=isset($notes[(int)$invoice['id']])&&$notes[(int)$invoice['id']]['kind']==='note'?array_intersect_key($notes[(int)$invoice['id']],array_flip(array('note','promised_on','protected_until','operator','created_at'))):null;
 $key=(string)$invoice['uuid_lanc'];$validKey=preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$key);
 $invoice['receive_url']=$canReceive&&$validKey?'/admin/receber.'.$ext.'?'.http_build_query(array('titulo'=>$key)):null;
 $invoice['info_url']='/admin/titulo_info.'.$ext.'?'.http_build_query(array('titulo'=>(int)$invoice['id']));
 $invoice['edit_url']=$canEdit&&$validKey?'/admin/titulo_alt.'.$ext.'?'.http_build_query(array('titulo'=>$key)):null;
 $invoice['download_url']=$canPrint&&(int)$data['client']['conta']>0?'/admin/addons/busca_inteligente/client_invoices_api.php?'.http_build_query(array('uuid'=>$uuid,'download'=>(int)$invoice['id'])):null;
 $invoice['boleto_url']=$canPrint&&(int)$data['client']['conta']>0?'/admin/prepara_boleto.'.$ext.'?'.http_build_query(array('titulo'=>(int)$invoice['id'])):null;
 unset($invoice['uuid_lanc']);
}unset($invoice);unset($data['client']['conta']);invoices_reply(200,$data);}
catch(DomainException $e){invoices_reply(403,array('error'=>$e->getMessage()));}
catch(Throwable $e){error_log('Client invoices: '.$e->getMessage());invoices_reply(503,array('error'=>'Não foi possível consultar as faturas.'));}
