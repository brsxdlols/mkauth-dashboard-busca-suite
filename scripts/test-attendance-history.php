<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/support_attendance.php';require __DIR__.'/../addons/shared/attendance_history.php';
function history_check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli('127.0.0.1','root',getenv('MYSQL_PASS')?:'vertrigo');$db->set_charset('utf8mb4');$schema='codex_history_fixture_'.bin2hex(random_bytes(6));$db->query('CREATE DATABASE `'.$schema.'`');
try{
 $db->select_db($schema);mka_attendance_schema($db);$db->query('CREATE TABLE sis_cliente(uuid_cliente VARCHAR(64),grupo VARCHAR(64),nome VARCHAR(255),login VARCHAR(255))');
 $db->query("INSERT INTO sis_cliente VALUES('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa','allowed','Cliente exemplo','fixture'),('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb','denied','Cliente restrito','restricted')");
 $a=array('uuid_cliente'=>'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa','login'=>'fixture','nome'=>'Cliente exemplo');$b=array('uuid_cliente'=>'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb','login'=>'restricted','nome'=>'Cliente restrito');
 foreach(array(array($a,'alice','2026-10-15 08:00:00','resolved'),array($a,'bob','2026-10-15 23:59:59','service'),array($a,'bob','2026-10-14 09:00:00','finished'),array($b,'charlie','2026-10-15 10:00:00','resolved'),array($a,'alice','2026-10-16 00:00:00','resolved')) as $i=>$fixture){$saved=mka_attendance_save($db,$fixture[0],$fixture[1],'Sem conexão','Relato do atendimento',$fixture[3],'fixture_history_'.$i);$db->query("UPDATE suite_support_attendance SET created_at='".$fixture[2]."' WHERE id=".(int)$saved['id']);}
 $scope="c.grupo='allowed'";$today='2026-10-15';$report=mka_attendance_history_report($db,$scope,array(),$today);
 history_check($report['total']===2&&$report['records'][0]['operator']==='bob','default report is today, all visible operators, newest first');
 history_check($report['operators']===array('alice','bob'),'operator filter does not expose operators with only restricted clients');
 history_check(mka_attendance_history_report($db,$scope,array('operator'=>'alice'),$today)['total']===1,'operator filter applies within selected day');
 history_check(mka_attendance_history_report($db,$scope,array('from'=>'2026-10-14','to'=>'2026-10-15'),$today)['total']===3,'inclusive date range excludes next-day midnight');
 history_check(mka_attendance_history_report($db,$scope,array('all'=>'1','q'=>'fixture'),$today)['total']===4,'client login search covers all periods on request');
 history_check(mka_attendance_history_report($db,$scope,array('all'=>'1','outcome'=>'finished'),$today)['total']===1,'outcome filter works independently of period');
 history_check(mka_attendance_history_report($db,$scope,array('all'=>'1','uuid'=>$b['uuid_cliente']),$today)['total']===0,'exact client filter cannot bypass group restrictions');
 history_check(mka_attendance_history_report($db,$scope,array('all'=>'1','q'=>"' OR 1=1 --"),$today)['total']===0,'search text remains a prepared parameter');
 foreach(array(array('from'=>'2026-02-30'),array('from'=>'2026-10-16','to'=>'2026-10-15'),array('q'=>array('bad'))) as $bad){try{mka_attendance_history_filters($bad,$today);throw new LogicException('Expected invalid filter');}catch(InvalidArgumentException $expected){history_check(true,'invalid filter rejected');}}
 for($i=0;$i<30;$i++){$saved=mka_attendance_save($db,$a,'alice','Antigo','Histórico paginado','resolved','fixture_pagination_'.$i);$db->query("UPDATE suite_support_attendance SET created_at='2026-10-01 10:00:00' WHERE id=".(int)$saved['id']);}
 $first=mka_attendance_history_report($db,$scope,array('all'=>'1'),$today);$second=mka_attendance_history_report($db,$scope,array('all'=>'1','page'=>'2'),$today);
 history_check($first['total']===34&&count($first['records'])===25&&count($second['records'])===9,'history paginates instead of truncating to recent 20');
 history_check(!array_intersect(array_column($first['records'],'id'),array_column($second['records'],'id')),'stable pagination does not repeat records');
 history_check(mka_attendance_history_report($db,$scope,array('all'=>'1','page'=>'999'),$today)['page']===2,'out-of-range page is clamped');
}finally{$db->query('DROP DATABASE `'.$schema.'`');$db->close();}
