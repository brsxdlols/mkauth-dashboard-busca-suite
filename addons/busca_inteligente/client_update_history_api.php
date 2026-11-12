<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/client_action_access.php';
require_once __DIR__.'/../shared/client_update_audit.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
$uuid=isset($_GET['uuid'])&&is_string($_GET['uuid'])?trim($_GET['uuid']):'';
mka_action_client($conn,$uuid,true);
$page=max(1,min(10000,(int)($_GET['page']??1)));$offset=($page-1)*25;
if(!mka_client_audit_ensure_table($conn)){http_response_code(503);exit;}
$safe=mysqli_real_escape_string($conn,$uuid);
$sql="SELECT usuario,changed_at,detalhes FROM (
 SELECT usuario,captured_at,changed_at,detalhes FROM dashboard_am_client_update_history WHERE uuid_cliente='$safe'
 UNION
 SELECT a.usuario,a.captured_at,c.last_update changed_at,a.detalhes FROM dashboard_am_client_update_audit a INNER JOIN sis_cliente c ON c.uuid_cliente=a.uuid_cliente
 WHERE a.uuid_cliente='$safe' AND NOT(c.last_update <=> a.previous_last_update) AND c.last_update BETWEEN DATE_SUB(a.captured_at,INTERVAL 5 SECOND) AND DATE_ADD(a.captured_at,INTERVAL 5 MINUTE)
) h ORDER BY changed_at DESC,captured_at DESC LIMIT 26 OFFSET $offset";
$q=mysqli_query($conn,$sql);if(!$q){http_response_code(503);exit;}$rows=mysqli_fetch_all($q,MYSQLI_ASSOC);$more=count($rows)>25;if($more)array_pop($rows);
echo json_encode(array('ok'=>true,'items'=>$rows,'page'=>$page,'more'=>$more,'notice'=>'Histórico capturado pela suíte. Alterações anteriores à captura ou realizadas por outros caminhos podem não estar disponíveis.'),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
