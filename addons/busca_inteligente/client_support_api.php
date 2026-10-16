<?php
require __DIR__.'/../shared/notification_bootstrap.php';require_once __DIR__.'/../shared/client_support.php';require_once __DIR__.'/../shared/installations.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function client_support_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
if($operator===''||!mka_installation_permission($conn,$operator,'perm_chamados'))client_support_reply(403,array('error'=>'Sem permissão para suporte.'));
session_write_close();$conn->set_charset('utf8mb4');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')client_support_reply(405,array('error'=>'Método não permitido.'));
$uuid=$_GET['uuid']??'';$page=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1,'max_range'=>1000000)));
if(!is_string($uuid)||!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$uuid)||$page===false)client_support_reply(422,array('error'=>'Cliente ou página inválidos.'));
try{
    $data=mka_client_support_feed($conn,$uuid,mka_attendance_scope($conn,$operator),$page);$ext=is_file(__DIR__.'/../../suporte_ins.hhvm')?'hhvm':'php';
    $data['service_url']='/admin/suporte_ins.'.$ext.'?'.http_build_query(array('login'=>$data['client']['login']));
    foreach($data['records'] as &$row)if($row['kind']==='ticket')$row['url']='/admin/suporte_info.'.$ext.'?'.http_build_query(array('login'=>$data['client']['login'],'chamado'=>$row['reference']));unset($row);
    client_support_reply(200,$data);
}catch(DomainException $e){client_support_reply(403,array('error'=>$e->getMessage()));}
catch(Throwable $e){client_support_reply(503,array('error'=>'Não foi possível consultar o histórico do cliente.'));}
