<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/attendance_history.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function attendance_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(!permissao('perm_chamados'))attendance_reply(403,array('error'=>'Sem permissao para chamados.'));
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
if($operator==='')attendance_reply(403,array('error'=>'Sessao administrativa necessaria.'));
if(empty($_SESSION['suite_attendance_csrf']))$_SESSION['suite_attendance_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['suite_attendance_csrf'];$canAdmin=permissao('perm_config');session_write_close();$conn->set_charset('utf8mb4');
$method=$_SERVER['REQUEST_METHOD']??'GET';$action=$_GET['action']??'history';
try {
    $scope=mka_attendance_scope($conn,$operator);
    $ext=is_file(__DIR__.'/../../suporte_ins.hhvm')?'hhvm':'php';
    $decorate=function($row)use($operator,$canAdmin,$ext){$row['can_edit']=$row['operator']===$operator||$canAdmin;$row['service_url']='/admin/suporte_ins.'.$ext.'?'.http_build_query(array('login'=>$row['login'],'mka_atendimento'=>$row['id']));return $row;};
    if($method==='GET' && $action==='report'){$report=mka_attendance_history_report($conn,$scope,$_GET);$report['records']=array_map($decorate,$report['records']);$report['csrf']=$csrf;$report['logged_user']=$operator;attendance_reply(200,$report);}
    if($method==='GET' && $action==='clients') {
        $q=trim((string)($_GET['q']??''));if(strlen($q)<2 || strlen($q)>255)attendance_reply(200,array('clients'=>array(),'csrf'=>$csrf));
        $like='%'.$q.'%';$statement=$conn->prepare("SELECT c.uuid_cliente,c.nome,c.login FROM sis_cliente c WHERE ($scope) AND c.cli_ativado='s' AND (c.nome LIKE ? OR c.login LIKE ?) ORDER BY c.nome LIMIT 15");
        $statement->bind_param('ss',$like,$like);$statement->execute();attendance_reply(200,array('clients'=>$statement->get_result()->fetch_all(MYSQLI_ASSOC),'csrf'=>$csrf));
    }
    if($method==='GET') {
        $id=(int)($_GET['id']??0);$uuid=(string)($_GET['uuid']??'');
        $sql="SELECT a.id,a.client_uuid,a.login,a.client_name,a.subject,a.notes,a.outcome,a.operator,a.created_at,a.version,a.updated_at,a.updated_by FROM suite_support_attendance a INNER JOIN sis_cliente c ON c.uuid_cliente=a.client_uuid WHERE ($scope)";
        if($action==='detail') {
            $statement=$conn->prepare($sql.' AND a.id=? LIMIT 1');$statement->bind_param('i',$id);
        } else {
            if($uuid!==''){$statement=$conn->prepare($sql.' AND a.client_uuid=? ORDER BY a.id DESC LIMIT 20');$statement->bind_param('s',$uuid);}
            else $statement=$conn->prepare($sql.' ORDER BY a.id DESC LIMIT 20');
        }
        $statement->execute();$rows=array_map($decorate,$statement->get_result()->fetch_all(MYSQLI_ASSOC));
        if($action==='detail' && !$rows)attendance_reply(404,array('error'=>'Atendimento nao encontrado.'));
        attendance_reply(200,array('records'=>$rows,'csrf'=>$csrf));
    }
    if($method!=='POST')attendance_reply(405,array('error'=>'Metodo nao permitido.'));
    $data=json_decode(file_get_contents('php://input',false,null,0,64000),true);
    if(!is_array($data) || !is_string($data['csrf']??null) || !hash_equals($csrf,$data['csrf']))attendance_reply(403,array('error'=>'Sessao expirada. Reabra o atendimento.'));
    $operation=$data['action']??'create';
    if($operation==='create'){
        foreach(array('uuid','subject','notes','outcome','key') as $field)if(!isset($data[$field]) || !is_string($data[$field]))attendance_reply(422,array('error'=>'Dados incompletos.'));
        $uuid=$data['uuid'];if(!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$uuid))attendance_reply(422,array('error'=>'Selecione um cliente.'));
        $statement=$conn->prepare("SELECT c.uuid_cliente,c.nome,c.login FROM sis_cliente c WHERE ($scope) AND c.cli_ativado='s' AND c.uuid_cliente=? LIMIT 1");
        $statement->bind_param('s',$uuid);$statement->execute();$client=$statement->get_result()->fetch_assoc();
        if(!$client)attendance_reply(403,array('error'=>'Cliente fora do acesso permitido.'));
        $saved=mka_attendance_save($conn,$client,$operator,trim($data['subject']),trim($data['notes']),$data['outcome'],$data['key']);
    }else{
        foreach(array('subject','notes','outcome') as $field)if(!is_string($data[$field]??null))attendance_reply(422,array('error'=>'Dados incompletos.'));
        $id=(int)($data['id']??0);$version=(int)($data['version']??0);
        if($id<1||$version<1)attendance_reply(422,array('error'=>'Registro invalido.'));
        $saved=mka_attendance_update($conn,$id,$operator,$scope,$canAdmin,$version,(string)$operation,trim($data['subject']),trim($data['notes']),$data['outcome']);
    }
    $id=(int)$saved['id'];
    $statement=$conn->prepare("SELECT a.id,a.client_uuid,a.login,a.client_name,a.subject,a.notes,a.outcome,a.operator,a.created_at,a.version,a.updated_at,a.updated_by FROM suite_support_attendance a INNER JOIN sis_cliente c ON c.uuid_cliente=a.client_uuid WHERE ($scope) AND a.id=? LIMIT 1");
    $statement->bind_param('i',$id);$statement->execute();$record=$decorate($statement->get_result()->fetch_assoc());
    attendance_reply(200,array('id'=>$id,'outcome'=>$record['outcome'],'service_url'=>$record['service_url'],'record'=>$record));
} catch(DomainException $error){attendance_reply(403,array('error'=>$error->getMessage()));}
catch(UnexpectedValueException $error){attendance_reply(409,array('error'=>$error->getMessage()));}
catch(InvalidArgumentException $error){attendance_reply(422,array('error'=>$error->getMessage()));}
catch(Throwable $error){attendance_reply(503,array('error'=>'Registro indisponivel. Confira a instalacao e tente novamente.'));}
