<?php
require_once __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/report_model.php';
$operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
if(!mka_installation_permission($conn,$operator,'perm_relFat')){http_response_code(403);exit('Sem permissão para consultar relatórios de clientes.');}
$scope=mka_attendance_scope($conn,$operator);
$period=rc_period($_GET);
$dimensions=array('cidade'=>'Cidade','bairro'=>'Bairro','estado'=>'Estado','plano'=>'Plano','vendedor'=>'Vendedor','tecnico'=>'Técnico');
$dimension=isset($_GET['filtro'])&&is_string($_GET['filtro'])&&isset($dimensions[$_GET['filtro']])?$_GET['filtro']:'cidade';
$value=isset($_GET['valor'])&&is_string($_GET['valor'])?trim($_GET['valor']):'';
if($value===''&&isset($_GET[$dimension])&&is_string($_GET[$dimension])&&$_GET[$dimension]!=='%')$value=trim($_GET[$dimension]);
$search=isset($_GET['busca'])&&is_string($_GET['busca'])?substr(trim($_GET['busca']),0,160):'';
$kind=rc_kind(isset($_GET['tipo'])&&is_string($_GET['tipo'])?$_GET['tipo']:'todos');
$state=isset($_GET['situacao'])&&in_array($_GET['situacao'],array('ativos','desativados','todos'),true)?$_GET['situacao']:'ativos';
$months=array('01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez','all'=>'Ano inteiro');
$labels=array('todos'=>'Todos os movimentos','instalacao'=>'Instalações','reativacao'=>'Reativações','cancelamento'=>'Cancelamentos','adicional'=>'Novos adicionais');
$events=$plans=$segments=$options=array();$counts=array_fill_keys(array_keys($labels),0);$problem='';
function rc_url($changes=array()){
 global $period,$dimension,$value,$search,$kind,$state;
 $data=array('mes'=>$period['month'],'ano'=>$period['year'],'filtro'=>$dimension,'valor'=>$value,'busca'=>$search,'tipo'=>$kind,'situacao'=>$state);
 return '?'.http_build_query(array_merge($data,$changes));
}
try{
 if($rcTab==='planos'){
  $plans=rc_query($conn,"SELECT p.nome,p.valor,COALESCE(SUM(x.active_count),0) active_count,COALESCE(SUM(x.inactive_count),0) inactive_count,COALESCE(SUM(x.additional_count),0) additional_count FROM sis_plano p LEFT JOIN (SELECT c.plano,SUM(c.cli_ativado='s') active_count,SUM(c.cli_ativado='n') inactive_count,0 additional_count FROM sis_cliente c WHERE ($scope) GROUP BY c.plano UNION ALL SELECT a.plano,0,0,COUNT(*) FROM sis_adicional a INNER JOIN sis_cliente c ON c.login=a.login WHERE ($scope) GROUP BY a.plano) x ON x.plano=p.nome GROUP BY p.nome,p.valor ORDER BY p.nome");
 }else{
  $where="($scope)";$params=array();
  if($rcTab==='segmentos'){
   $options=rc_query($conn,"SELECT DISTINCT c.`$dimension` value FROM sis_cliente c WHERE ($scope) AND c.`$dimension` IS NOT NULL AND c.`$dimension`<>'' ORDER BY c.`$dimension`");
   if($value!==''){$where.=" AND c.`$dimension`=?";$params[]=$value;}
  }
  if($search!==''){$where.=" AND (LOCATE(?,c.nome)>0 OR LOCATE(?,c.login)>0)";$params[]=$search;$params[]=$search;}
  $columns="c.nome,c.login,c.uuid_cliente,c.cli_ativado,c.`$dimension` segment";
  foreach(array('instalacao'=>'data_ins','cancelamento'=>'data_desativacao') as $type=>$date){
   $rows=rc_query($conn,"SELECT $columns,c.`$date` event_date FROM sis_cliente c WHERE $where AND c.`$date`>=? AND c.`$date`<?",array_merge($params,array($period['from'],$period['until'])));
   foreach($rows as $row)$events[]=rc_event($row,$type,$row['event_date']);
  }
  // Native logs use dd/mm/YYYY text. Count reactivated clients once per period,
  // as the previous report did, but retain the latest recorded date for display.
  $rows=rc_query($conn,"SELECT $columns,MAX(STR_TO_DATE(l.data,'%d/%m/%Y %H:%i:%s')) event_date FROM sis_logs l INNER JOIN sis_cliente c ON c.login=l.login WHERE $where AND l.registro LIKE '%ativou o cliente%' AND l.registro NOT LIKE '%desativou%' AND l.data LIKE ? GROUP BY c.nome,c.login,c.uuid_cliente,c.cli_ativado,c.`$dimension`",array_merge($params,array($period['loglike'])));
  foreach($rows as $row)$events[]=rc_event($row,'reativacao',$row['event_date']);
  $rows=rc_query($conn,"SELECT $columns,l.data event_date,l.registro detail FROM sis_logs l INNER JOIN sis_adicional a ON TRIM(SUBSTRING_INDEX(SUBSTRING(l.registro,22),' -',1))=a.username INNER JOIN sis_cliente c ON c.login=a.login WHERE $where AND l.registro LIKE 'inseriu o adicional:%' AND l.data LIKE ?",array_merge($params,array($period['loglike'])));
  foreach($rows as $row){$row['event_date']=str_replace('/','-',$row['event_date']);$events[]=rc_event($row,'adicional',date('Y-m-d H:i:s',strtotime($row['event_date'])), $row['detail']);}
  usort($events,'rc_sort_events');
  foreach($events as $event){$counts['todos']++;$counts[$event['kind']]++;$key=$event['segment'];if(!isset($segments[$key]))$segments[$key]=array('name'=>$key,'instalacao'=>0,'adicional'=>0,'reativacao'=>0,'cancelamento'=>0);$segments[$key][$event['kind']]++;}
 }
}catch(Throwable $error){error_log('Relatorio clientes: '.$error->getMessage());$problem='Não foi possível carregar o relatório. Tente novamente ou consulte o administrador.';}
$nativeExt=is_file(__DIR__.'/../../login.hhvm')?'.hhvm':'.php';
require __DIR__.'/report_view.php';
