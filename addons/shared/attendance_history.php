<?php
function mka_attendance_history_filters($query,$today=null) {
    if($today===null)$today=(new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
    foreach(array('from','to','all','q','operator','outcome','uuid','page') as $key)if(isset($query[$key])&&!is_string($query[$key])&&!is_int($query[$key]))throw new InvalidArgumentException('Filtro inválido.');
    $all=($query['all']??'')==='1';$from=$all?'':(string)($query['from']??$today);$to=$all?'':(string)($query['to']??$today);
    if(!$all){foreach(array($from,$to) as $value){$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);if(!$date||$date->format('Y-m-d')!==$value||$value<'2000-01-01'||$value>'2199-12-31')throw new InvalidArgumentException('Informe datas válidas para o período.');}if($from>$to)throw new InvalidArgumentException('A data inicial deve ser anterior ou igual à data final.');}
    $q=trim((string)($query['q']??''));$operator=trim((string)($query['operator']??''));$outcome=(string)($query['outcome']??'');$uuid=(string)($query['uuid']??'');
    if(preg_match_all('/./us',$q)>255||strlen($operator)>64||!in_array($outcome,array('','open','resolved','finished','service'),true)||($uuid!==''&&!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$uuid)))throw new InvalidArgumentException('Filtro inválido.');
    $page=filter_var($query['page']??1,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1,'max_range'=>1000000)));if($page===false)throw new InvalidArgumentException('Página inválida.');
    return array('from'=>$from,'to'=>$to,'all'=>$all,'q'=>$q,'operator'=>$operator,'outcome'=>$outcome,'uuid'=>$uuid,'page'=>$page,'today'=>$today);
}
function mka_attendance_history_query($db,$sql,$params) {
    $statement=$db->prepare($sql);if($params)$statement->bind_param(str_repeat('s',count($params)),...$params);$statement->execute();return $statement->get_result();
}
function mka_attendance_history_report($db,$scope,$query,$today=null) {
    $filters=mka_attendance_history_filters($query,$today);$where=array('('.$scope.')');$params=array();
    if(!$filters['all']){$where[]='a.created_at>=? AND a.created_at<?';$params[]=$filters['from'].' 00:00:00';$params[]=(new DateTimeImmutable($filters['to']))->modify('+1 day')->format('Y-m-d').' 00:00:00';}
    if($filters['q']!==''){$where[]='(a.client_name LIKE ? OR a.login LIKE ? OR c.nome LIKE ?)';$like='%'.$filters['q'].'%';array_push($params,$like,$like,$like);}
    foreach(array('operator','outcome') as $field)if($filters[$field]!==''){$where[]='a.'.$field.'=?';$params[]=$filters[$field];}
    if($filters['uuid']!==''){$where[]='a.client_uuid=?';$params[]=$filters['uuid'];}
    $base=' FROM suite_support_attendance a INNER JOIN sis_cliente c ON c.uuid_cliente=a.client_uuid WHERE '.implode(' AND ',$where);
    $total=(int)mka_attendance_history_query($db,'SELECT COUNT(*)'.$base,$params)->fetch_row()[0];$perPage=25;$pages=max(1,(int)ceil($total/$perPage));$page=min($filters['page'],$pages);$offset=($page-1)*$perPage;
    $rows=mka_attendance_history_query($db,'SELECT a.id,a.client_uuid,a.login,a.client_name,a.subject,a.notes,a.outcome,a.operator,a.created_at,a.version,a.updated_at,a.updated_by'.$base.' ORDER BY a.created_at DESC,a.id DESC LIMIT '.$perPage.' OFFSET '.$offset,$params)->fetch_all(MYSQLI_ASSOC);
    $operators=mka_attendance_history_query($db,"SELECT DISTINCT a.operator FROM suite_support_attendance a INNER JOIN sis_cliente c ON c.uuid_cliente=a.client_uuid WHERE ($scope) ORDER BY a.operator",array())->fetch_all(MYSQLI_ASSOC);
    $filters['page']=$page;
    return array('records'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$perPage,'filters'=>$filters,'operators'=>array_column($operators,'operator'));
}
