<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/support_attendance.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
if(!mka_installation_permission($conn,$operator,'perm_clientes')){http_response_code(403);exit('{}');}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit('{}');}
$input=json_decode(file_get_contents('php://input',false,null,0,20000),true);
$logins=$input['logins']??null;
if(!is_array($logins)||!$logins||count($logins)>100){http_response_code(422);exit('{}');}
foreach($logins as $login)if(!is_string($login)||strlen($login)>64||trim($login)===''){http_response_code(422);exit('{}');}
$scope=mka_attendance_scope($conn,$operator);session_write_close();
try{
 $safe=array();foreach(array_unique($logins) as $login)$safe[]="'".$conn->real_escape_string($login)."'";
 $in=implode(',',$safe);
 $query=$conn->query("SELECT c.login,c.cli_ativado,c.bloqueado,c.observacao,r.radacctid,r.framedipaddress FROM sis_cliente c LEFT JOIN radacct r ON r.radacctid=(SELECT MAX(a.radacctid) FROM radacct a WHERE a.username=c.login AND a.acctstoptime IS NULL) WHERE ($scope) AND c.login IN ($in)");
 $states=array();while($row=$query->fetch_assoc())$states[$row['login']]=array('online'=>$row['radacctid']!==null,'ip'=>$row['framedipaddress']??'','disabled'=>$row['cli_ativado']==='n','blocked'=>$row['bloqueado']==='sim','observation'=>$row['observacao']==='sim');
 echo json_encode(array('states'=>(object)$states),JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){error_log('Live client states: '.$e->getMessage());http_response_code(503);echo '{}';}
