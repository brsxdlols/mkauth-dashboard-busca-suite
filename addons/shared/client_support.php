<?php
require_once __DIR__.'/support_attendance.php';
require_once __DIR__.'/attendance_history.php';
function mka_support_history_report($db,$scope,$query,$today=null){
    $filters=mka_attendance_history_filters($query,$today);$kind=$query['kind']??'all';
    if(!in_array($kind,array('all','attendance','ticket'),true))throw new InvalidArgumentException('Tipo de registro inválido.');
    $parts=array();
    if($kind!=='ticket')$parts[]="SELECT 'attendance' kind,a.id,a.client_uuid,a.login,a.client_name,a.subject,a.notes,a.outcome,a.operator,a.created_at,a.version,a.updated_at,a.updated_by,NULL reference,NULL visit FROM suite_support_attendance a INNER JOIN sis_cliente c ON c.uuid_cliente=a.client_uuid WHERE ($scope)";
    if($kind!=='attendance')$parts[]="SELECT 'ticket' kind,s.id,c.uuid_cliente client_uuid,c.login,c.nome client_name,s.assunto subject,'' notes,IF(s.status='aberto','os_open','os_closed') outcome,s.login_atend operator,CAST(s.abertura AS DATETIME) created_at,0 version,NULL updated_at,NULL updated_by,s.chamado reference,s.visita visit FROM sis_suporte s INNER JOIN sis_cliente c ON c.login=s.login WHERE ($scope)";
    $union=implode(' UNION ALL ',$parts);$where=array('1=1');$params=array();
    if(!$filters['all']){$where[]='a.created_at>=? AND a.created_at<?';$params[]=$filters['from'].' 00:00:00';$params[]=(new DateTimeImmutable($filters['to']))->modify('+1 day')->format('Y-m-d').' 00:00:00';}
    if($filters['q']!==''){$where[]='(a.client_name LIKE ? OR a.login LIKE ?)';$params[]='%'.$filters['q'].'%';$params[]='%'.$filters['q'].'%';}
    foreach(array('operator','outcome') as $field)if($filters[$field]!==''){$where[]='a.'.$field.'=?';$params[]=$filters[$field];}
    if($filters['uuid']!==''){$where[]='a.client_uuid=?';$params[]=$filters['uuid'];}
    $base=' FROM ('.$union.') a WHERE '.implode(' AND ',$where);$total=(int)mka_attendance_history_query($db,'SELECT COUNT(*)'.$base,$params)->fetch_row()[0];$size=25;$pages=max(1,(int)ceil($total/$size));$page=min($filters['page'],$pages);$offset=($page-1)*$size;
    $records=mka_attendance_history_query($db,'SELECT a.*'.$base.' ORDER BY a.created_at DESC,a.kind,a.id DESC LIMIT '.$size.' OFFSET '.$offset,$params)->fetch_all(MYSQLI_ASSOC);
    $operators=mka_attendance_history_query($db,'SELECT DISTINCT a.operator FROM ('.$union.') a ORDER BY a.operator',array())->fetch_all(MYSQLI_ASSOC);$filters['page']=$page;$filters['kind']=$kind;
    return array('records'=>$records,'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$size,'filters'=>$filters,'operators'=>array_column($operators,'operator'));
}
function mka_client_support_client($db,$uuid,$scope){
    $q=$db->prepare("SELECT c.uuid_cliente,c.nome,c.login,c.cli_ativado FROM sis_cliente c WHERE ($scope) AND c.uuid_cliente=? LIMIT 1");$q->bind_param('s',$uuid);$q->execute();$client=$q->get_result()->fetch_assoc();
    if(!$client)throw new DomainException('Cliente fora do acesso permitido.');return $client;
}
function mka_client_support_service($db,$attendance,$scope){
    if($attendance['outcome']!=='service')return null;
    $marker='[Atendimento #'.(int)$attendance['id'].']';
    $q=$db->prepare("SELECT s.id,s.chamado,s.status FROM sis_suporte s INNER JOIN sis_cliente c ON c.login=s.login WHERE ($scope) AND c.uuid_cliente=? AND EXISTS(SELECT 1 FROM sis_msg m WHERE m.chamado=s.chamado AND LOCATE(?,m.msg)>0) ORDER BY s.id DESC LIMIT 1");
    $q->bind_param('ss',$attendance['client_uuid'],$marker);$q->execute();return $q->get_result()->fetch_assoc()?:null;
}
function mka_client_support_feed($db,$uuid,$scope,$page=1){
    $client=mka_client_support_client($db,$uuid,$scope);$page=max(1,(int)$page);$size=20;
    $attendance=" FROM suite_support_attendance a INNER JOIN sis_cliente c ON c.uuid_cliente=a.client_uuid WHERE ($scope) AND c.uuid_cliente=?";
    $tickets=" FROM sis_suporte s INNER JOIN sis_cliente c ON c.login=s.login WHERE ($scope) AND c.uuid_cliente=?";
    $q=$db->prepare('SELECT (SELECT COUNT(*)'.$attendance.')+(SELECT COUNT(*)'.$tickets.')');$q->bind_param('ss',$uuid,$uuid);$q->execute();$total=(int)$q->get_result()->fetch_row()[0];$pages=max(1,(int)ceil($total/$size));$page=min($pages,$page);$offset=($page-1)*$size;
    $q=$db->prepare("SELECT * FROM (SELECT 'attendance' kind,a.id,a.subject,a.notes,a.outcome status,a.created_at,a.operator,NULL reference,NULL visit".$attendance." UNION ALL SELECT 'ticket' kind,s.id,s.assunto subject,'' notes,s.status,CAST(s.abertura AS DATETIME) created_at,s.atendente operator,s.chamado reference,s.visita visit".$tickets.") history ORDER BY created_at DESC,id DESC LIMIT $size OFFSET $offset");
    $q->bind_param('ss',$uuid,$uuid);$q->execute();return array('client'=>$client,'records'=>$q->get_result()->fetch_all(MYSQLI_ASSOC),'total'=>$total,'page'=>$page,'pages'=>$pages);
}
