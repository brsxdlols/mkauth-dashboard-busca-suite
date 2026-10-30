<?php
// Read-only confirmation after a native customer operation.
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/support_attendance.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
if(!mka_installation_permission($conn,$operator,'perm_clientes')){http_response_code(403);exit('{}');}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){http_response_code(405);exit('{}');}
$login=$_GET['login']??null;
if(!is_string($login)||trim($login)===''||strlen($login)>64){http_response_code(422);exit('{}');}
$scope=mka_attendance_scope($conn,$operator);session_write_close();
try{
 $query=$conn->prepare("SELECT c.cli_ativado FROM sis_cliente c WHERE ($scope) AND c.login=? LIMIT 1");
 $query->bind_param('s',$login);$query->execute();$row=$query->get_result()->fetch_assoc();
 echo json_encode(array('exists'=>(bool)$row,'active'=>$row?$row['cli_ativado']==='s':null));
}catch(Throwable $error){error_log('Client action status: '.$error->getMessage());http_response_code(503);echo '{}';}
