<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/ticket_schedule.php';
require_once __DIR__.'/../shared/ticket_board.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function ticket_schedule_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(!permissao('perm_chamados'))ticket_schedule_reply(403,array('error'=>'Sem permissão para chamados.'));
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
if($operator==='')ticket_schedule_reply(403,array('error'=>'Sessão administrativa necessária.'));
if(empty($_SESSION['suite_schedule_csrf']))$_SESSION['suite_schedule_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['suite_schedule_csrf'];session_write_close();$conn->set_charset('utf8mb4');
try{
    $scope=mka_attendance_scope($conn,$operator);$method=$_SERVER['REQUEST_METHOD']??'GET';
    if($method==='GET')$row=mka_ticket_schedule_row($conn,(int)($_GET['id']??0),$scope);
    elseif($method==='POST'){
        $data=json_decode(file_get_contents('php://input',false,null,0,8192),true);
        if(!is_array($data)||!is_string($data['csrf']??null)||!hash_equals($csrf,$data['csrf']))ticket_schedule_reply(403,array('error'=>'Sessão expirada. Recarregue a página.'));
        $row=mka_ticket_schedule_save($conn,(int)($data['id']??0),$scope,$operator,$data['version']??null,$data['action']??null,$data);
    }else ticket_schedule_reply(405,array('error'=>'Método não permitido.'));
    $row['version']=mka_ticket_schedule_version($row);$row['timing']=mka_ticket_timing($row['visita']);
    ticket_schedule_reply(200,array('record'=>$row,'csrf'=>$csrf));
}catch(DomainException $e){ticket_schedule_reply(403,array('error'=>$e->getMessage()));}
catch(UnexpectedValueException $e){ticket_schedule_reply(409,array('error'=>$e->getMessage()));}
catch(InvalidArgumentException $e){ticket_schedule_reply(422,array('error'=>$e->getMessage()));}
catch(Throwable $e){ticket_schedule_reply(503,array('error'=>'Não foi possível salvar o agendamento. Tente novamente.'));}
