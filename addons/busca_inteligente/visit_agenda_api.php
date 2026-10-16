<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/visit_windows.php';
require_once __DIR__.'/../shared/agenda_settings.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function agenda_reply($status,$data){http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$operator=(string)(!empty($_SESSION['MKA_Usuario'])?$_SESSION['MKA_Usuario']:($_SESSION['MM_Usuario']??''));
$tickets=permissao('perm_chamados');$installations=permissao('perm_instalacao');$all=permissao('perm_instTodosUsuarios');session_write_close();
if(!$operator||(!$tickets&&!$installations))agenda_reply(403,array('error'=>'Sem permissão para consultar a agenda.'));
try{
 $kind=($_GET['kind']??'ticket')==='installation'?'installation':'ticket';$day=$_GET['day']??'';if(!is_string($day)||!preg_match('/^20\d\d-\d\d-\d\d$/D',$day))throw new InvalidArgumentException('Data inválida.');
 $date=DateTimeImmutable::createFromFormat('!Y-m-d',$day);if(!$date||$date->format('Y-m-d')!==$day)throw new InvalidArgumentException('Data inválida.');
 $from=$day.' 00:00:00';$until=$date->modify('+7 days')->format('Y-m-d H:i:s');$conn->set_charset('utf8mb4');$events=array();
 if($tickets){$scope=mka_attendance_scope($conn,$operator);$q=$conn->prepare("SELECT s.id AS id,s.chamado AS reference,s.nome AS name,s.assunto AS subject,s.prioridade AS priority,s.visita AS visit FROM sis_suporte s INNER JOIN sis_cliente c ON c.login=s.login WHERE ($scope) AND s.status='aberto' AND s.visita>=? AND s.visita<? ORDER BY s.visita LIMIT 1000");$q->bind_param('ss',$from,$until);$q->execute();foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $r){$r['kind']='ticket';$r['period']=mka_visit_period($conn,$r['reference'],$r['visit']);$events[]=$r;}}
 if($installations){$scope=mka_installation_where(mka_installation_scope($conn,$operator,$all));$q=$conn->prepare("SELECT s.id AS id,s.uuid_solic AS uuid,s.id AS reference,s.nome AS name,s.visita AS visit FROM sis_solic s WHERE ($scope) AND s.visita>=? AND s.visita<? ORDER BY s.visita LIMIT 1000");$q->bind_param('ss',$from,$until);$q->execute();foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $r){$r['kind']='installation';$r['subject']='Instalação';$r['priority']='';$events[]=$r;}}
 agenda_reply(200,array('events'=>$events,'lunch'=>mka_agenda_settings($conn,$kind),'from'=>$day,'until'=>$until,'scope_notice'=>'A agenda mostra as visitas que seu usuário tem permissão para consultar.'));
}catch(InvalidArgumentException $e){agenda_reply(422,array('error'=>$e->getMessage()));}catch(Throwable $e){agenda_reply(503,array('error'=>'Não foi possível consultar a agenda. Tente novamente.'));}
