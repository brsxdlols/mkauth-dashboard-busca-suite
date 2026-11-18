<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/installations.php';
require_once __DIR__.'/../shared/support_attendance.php';
require_once __DIR__.'/../rel_clientes/report_model.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
try{
 $user=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
 if(!mka_installation_permission($conn,$user,'perm_relFat')||!mka_installation_permission($conn,$user,'perm_numClientes')){http_response_code(403);echo json_encode(array('ok'=>false));exit;}
 $scope=mka_attendance_scope($conn,$user);session_write_close();
 $clock=$conn->query('SELECT CURRENT_DATE today')->fetch_assoc();$current=new DateTimeImmutable(substr($clock['today'],0,7).'-01');$start=$current->modify('-5 months');$end=$current->modify('+1 month');
 $months=array();for($i=0;$i<6;$i++){$key=$start->modify('+'.$i.' months')->format('Y-m');$months[$key]=array('month'=>$key,'activations'=>0,'reactivations'=>0,'cancellations'=>0);}
 foreach(array('activations'=>'data_ins','cancellations'=>'data_desativacao') as $type=>$column){
  $rows=rc_query($conn,"SELECT DATE_FORMAT(c.`$column`,'%Y-%m') month,COUNT(*) total FROM sis_cliente c WHERE ($scope) AND c.`$column`>=? AND c.`$column`<? GROUP BY DATE_FORMAT(c.`$column`,'%Y-%m')",array($start->format('Y-m-d'),$end->format('Y-m-d')));
  foreach($rows as $row)if(isset($months[$row['month']]))$months[$row['month']][$type]=(int)$row['total'];
 }
 $patterns=array();$logParams=array();foreach(array_keys($months) as $month){$patterns[]='l.data LIKE ?';$logParams[]='%/'.substr($month,5,2).'/'.substr($month,0,4).'%';}
 $rows=rc_query($conn,"SELECT CONCAT(SUBSTRING(l.data,7,4),'-',SUBSTRING(l.data,4,2)) month,COUNT(DISTINCT c.login) total FROM sis_logs l INNER JOIN sis_cliente c ON c.login=l.login WHERE ($scope) AND l.registro LIKE '%ativou o cliente%' AND l.registro NOT LIKE '%desativou%' AND (".implode(' OR ',$patterns).") GROUP BY CONCAT(SUBSTRING(l.data,7,4),'-',SUBSTRING(l.data,4,2))",$logParams);
 foreach($rows as $row)if(isset($months[$row['month']]))$months[$row['month']]['reactivations']=(int)$row['total'];
 echo json_encode(array('ok'=>true,'months'=>array_values($months)),JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){error_log('Client growth: '.$e->getMessage());http_response_code(500);echo json_encode(array('ok'=>false));}
