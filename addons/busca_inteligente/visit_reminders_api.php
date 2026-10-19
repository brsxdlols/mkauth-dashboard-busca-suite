<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/visit_reminders.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function visit_reminder_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
if($operator==='')visit_reminder_reply(403,array('error'=>'Sessão administrativa necessária.'));
$tickets=mka_installation_permission($conn,$operator,'perm_chamados');$installations=mka_installation_permission($conn,$operator,'perm_instalacao');$all=mka_installation_permission($conn,$operator,'perm_instTodosUsuarios');
if(empty($_SESSION['suite_visit_reminders_csrf']))$_SESSION['suite_visit_reminders_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['suite_visit_reminders_csrf'];session_write_close();$conn->set_charset('utf8mb4');
try {
    $method=$_SERVER['REQUEST_METHOD']??'GET';
    if($method==='POST'){
        $data=json_decode(file_get_contents('php://input',false,null,0,2048),true);
        if(!is_array($data)||!is_string($data['csrf']??null)||!hash_equals($csrf,$data['csrf']))visit_reminder_reply(403,array('error'=>'Recarregue a página para fechar o aviso.'));
        if(($data['action']??'')==='settings'){
            $kind=$data['kind']??'';
            if(($kind!=='ticket'&&$kind!=='installation')||($kind==='ticket'&&!$tickets)||($kind==='installation'&&!$installations))visit_reminder_reply(403,array('error'=>'Sem permissão para estes lembretes.'));
            try{mka_visit_reminder_preferences_save($conn,$operator,$kind,$data);}catch(InvalidArgumentException $e){visit_reminder_reply(422,array('error'=>$e->getMessage()));}
            visit_reminder_reply(200,array('ok'=>true,'settings'=>mka_visit_reminder_preferences($conn,$operator,$kind)));
        }
        $id=filter_var($data['id']??null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));if(!$id)visit_reminder_reply(422,array('error'=>'Aviso inválido.'));
        mka_visit_reminder_dismiss($conn,$operator,$id);visit_reminder_reply(200,array('ok'=>true));
    }
    if($method!=='GET')visit_reminder_reply(405,array('error'=>'Método não permitido.'));
    if(($_GET['settings']??'')==='1'){
        $kind=$_GET['kind']??'';
        if(($kind!=='ticket'&&$kind!=='installation')||($kind==='ticket'&&!$tickets)||($kind==='installation'&&!$installations))visit_reminder_reply(403,array('error'=>'Sem permissão para estes lembretes.'));
        visit_reminder_reply(200,array('settings'=>mka_visit_reminder_preferences($conn,$operator,$kind),'csrf'=>$csrf));
    }
    visit_reminder_reply(200,array('events'=>mka_visit_reminders_collect($conn,$operator,$tickets,$installations,$all),'csrf'=>$csrf));
}catch(Throwable $e){visit_reminder_reply(503,array('error'=>'Não foi possível consultar os avisos de visita.'));}
