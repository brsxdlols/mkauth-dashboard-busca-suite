<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/installations.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
if($operator===''||!mka_installation_permission($conn,$operator,'perm_clientes')){http_response_code(403);echo json_encode(array('clients'=>array()));exit;}
$term=$_GET['q']??'';if(!is_string($term)||strlen(trim($term))<2||strlen($term)>120){echo json_encode(array('clients'=>array()));exit;}
$scope=mka_attendance_scope($conn,$operator);if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
try{$conn->set_charset('utf8mb4');$like='%'.strtr(trim($term),array('!'=>'!!','%'=>'!%','_'=>'!_')).'%';$s=$conn->prepare("SELECT c.nome,c.login FROM sis_cliente c WHERE ($scope) AND (c.nome LIKE ? ESCAPE '!' OR c.login LIKE ? ESCAPE '!' OR c.cpf_cnpj LIKE ? ESCAPE '!') ORDER BY c.nome,c.login LIMIT 10");$s->bind_param('sss',$like,$like,$like);$s->execute();echo json_encode(array('clients'=>$s->get_result()->fetch_all(MYSQLI_ASSOC)),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}catch(Throwable $e){http_response_code(503);echo json_encode(array('error'=>'SugestÃµes indisponÃ­veis. Use Buscar.'));}
