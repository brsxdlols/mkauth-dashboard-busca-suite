<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function installation_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(!permissao('perm_instalacao'))installation_reply(403,array('error'=>'Sem permissão para instalações.'));
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
if($operator==='')installation_reply(403,array('error'=>'Sessão administrativa necessária.'));
$all=permissao('perm_instTodosUsuarios');$edit=permissao('perm_instAlterar');
if(empty($_SESSION['suite_installation_csrf']))$_SESSION['suite_installation_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['suite_installation_csrf'];session_write_close();$conn->set_charset('utf8mb4');
try {
    $scope=mka_installation_scope($conn,$operator,$all);$method=$_SERVER['REQUEST_METHOD']??'GET';
    if($method==='GET'&&($_GET['action']??'')==='slots')installation_reply(200,array_merge(mka_installation_slots($conn,(int)($_GET['id']??0),$scope,$_GET['day']??''),array('csrf'=>$csrf)));
    if($method==='GET')$row=mka_installation_row($conn,(int)($_GET['id']??0),$scope);
    elseif($method==='POST'){
        if(!$edit)installation_reply(403,array('error'=>'Sem permissão para alterar instalações.'));
        $data=json_decode(file_get_contents('php://input',false,null,0,8192),true);
        if(!is_array($data)||!is_string($data['csrf']??null)||!hash_equals($csrf,$data['csrf']))installation_reply(403,array('error'=>'Sessão expirada. Recarregue a página.'));
        if(($data['action']??'')!=='move')installation_reply(422,array('error'=>'Operação inválida.'));
        mka_installation_schema($conn);$row=mka_installation_move($conn,(int)($data['id']??0),$scope,$operator,$data);
    }else installation_reply(405,array('error'=>'Método não permitido.'));
    installation_reply(200,array('record'=>$row,'csrf'=>$csrf));
}catch(MkaTicketBookingConflict $e){installation_reply(409,array('error'=>'Já há instalações neste horário. Confirme a sobreposição e organize equipes diferentes.','booking_conflicts'=>$e->visits));}
catch(DomainException $e){installation_reply(403,array('error'=>$e->getMessage()));}
catch(UnexpectedValueException $e){installation_reply(409,array('error'=>$e->getMessage()));}
catch(InvalidArgumentException $e){installation_reply(422,array('error'=>$e->getMessage()));}
catch(Throwable $e){installation_reply(503,array('error'=>'Não foi possível consultar ou salvar a instalação. Tente novamente.'));}
