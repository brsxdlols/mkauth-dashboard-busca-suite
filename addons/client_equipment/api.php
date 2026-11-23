<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/client_action_access.php';
require_once __DIR__.'/model.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,array('GET','POST'),true)){http_response_code(405);exit;}
$user=$_SESSION['MKA_Usuario']??($_SESSION['MM_Usuario']??'');
$uuid=isset($_GET['uuid'])&&is_string($_GET['uuid'])?trim($_GET['uuid']):'';
$client=mka_action_client($conn,$uuid,true);
// Both editing the customer and moving stock require the operator's explicit permission.
$q=mka_equipment_query($conn,"SELECT COUNT(DISTINCT nome) n FROM sis_perm WHERE usuario=? AND permissao='sim' AND nome IN ('perm_altcliente','perm_estoque')",'s',array($user));
if((int)$q->get_result()->fetch_assoc()['n']!==2){http_response_code(403);echo json_encode(array('error'=>'É necessário ter permissão para alterar clientes e acessar o estoque.'));exit;}
mysqli_set_charset($conn,'utf8mb4');
try {
    if($method==='POST') {
        mka_action_check_token();
        if(!isset($_POST['revision'],$_POST['request_key'],$_POST['items']) || !is_string($_POST['revision']) || !ctype_digit($_POST['revision']) || !is_string($_POST['request_key']) || !is_string($_POST['items']) || strlen($_POST['items'])>100000)throw new InvalidArgumentException('Dados de equipamentos inválidos.');
        $rows=json_decode($_POST['items'],true);
        mka_equipment_save($conn,$uuid,$client['login'],$user,(int)$_POST['revision'],$_POST['request_key'],$rows);
    }
    $conn->begin_transaction();
    try {$state=mka_equipment_state($conn,$client);$conn->commit();}catch(Throwable $e){$conn->rollback();throw $e;}
    $state['csrf']=mka_action_token();$state['ok']=true;
    echo json_encode($state,JSON_UNESCAPED_UNICODE|(defined('JSON_INVALID_UTF8_SUBSTITUTE')?constant('JSON_INVALID_UTF8_SUBSTITUTE'):0));
} catch(InvalidArgumentException $e) {http_response_code(422);echo json_encode(array('error'=>$e->getMessage()),JSON_UNESCAPED_UNICODE);}
catch(UnexpectedValueException $e) {http_response_code(409);echo json_encode(array('error'=>$e->getMessage()),JSON_UNESCAPED_UNICODE);}
catch(Throwable $e) {error_log('suite equipment: '.$e->getMessage());http_response_code(503);echo json_encode(array('error'=>'Não foi possível gravar. Nenhuma baixa foi confirmada. Atualize os equipamentos para conferir o saldo.'));}
