<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/ticket_schedule.php';
function schedule_check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root',getenv('MYSQL_PASS')?:'vertrigo');$db->set_charset('utf8mb4');$schema='codex_schedule_fixture_'.bin2hex(random_bytes(6));$db->query('CREATE DATABASE `'.$schema.'`');
try{
 $db->select_db($schema);mka_ticket_schedule_schema($db);mka_ticket_schedule_schema($db);
 $db->query('CREATE TABLE sis_cliente(login VARCHAR(64),grupo VARCHAR(64)) ENGINE=InnoDB');
 $db->query('CREATE TABLE sis_suporte(id INT PRIMARY KEY,chamado VARCHAR(64),login VARCHAR(64),nome VARCHAR(64),assunto VARCHAR(255),abertura VARCHAR(64),visita DATETIME NULL,prioridade VARCHAR(20),tecnico INT,status VARCHAR(64),ramal VARCHAR(20)) ENGINE=InnoDB');
 $db->query("INSERT INTO sis_cliente VALUES('fixture','allowed'),('other','denied')");
 $db->query("INSERT INTO sis_suporte VALUES(1,'123','fixture','Cliente exemplo','Sem conexão','2026-10-15 10:00:00','2026-10-16 14:35:21','normal',7,'aberto','Preservado'),(2,'456','other','Outro cliente','Outro assunto','2026-10-15 10:00:00','2026-10-16 09:00:00','baixa',8,'aberto','Outro')");
 $scope="c.grupo='allowed'";$first=mka_ticket_schedule_row($db,1,$scope);$version=mka_ticket_schedule_version($first);
 $moved=mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'move',array('day'=>'2026-10-20'));
 schedule_check($moved['visita']==='2026-10-20 14:35:21','drag changes day while preserving time and seconds');
 $native=$db->query('SELECT * FROM sis_suporte WHERE id=1')->fetch_assoc();
 schedule_check($native['tecnico']==='7'&&$native['ramal']==='Preservado'&&$native['abertura']===$first['abertura']&&$native['status']==='aberto'&&$native['assunto']===$first['assunto'],'move preserves native ticket fields');
 try{mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'move',array('day'=>'2026-10-21'));throw new LogicException('Expected stale conflict');}catch(UnexpectedValueException $expected){schedule_check(true,'stale calendar cannot overwrite current ticket');}
 try{mka_ticket_schedule_save($db,2,$scope,'fixture_operator','invalid','move',array('day'=>'2026-10-21'));throw new LogicException('Expected group denial');}catch(DomainException $expected){schedule_check(true,'group restrictions apply to writes');}
 $version=mka_ticket_schedule_version($moved);
 try{mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'move',array('day'=>'2026-02-30'));throw new LogicException('Expected date rejection');}catch(InvalidArgumentException $expected){schedule_check(true,'impossible date is rejected without changing ticket');}
 $edited=mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'edit',array('visit'=>'2026-10-22 16:20:00','subject'=>'Visita de revisão','priority'=>'alta'));
 schedule_check($edited['visita']==='2026-10-22 16:20:00'&&$edited['assunto']==='Visita de revisão'&&$edited['prioridade']==='alta','editor saves date, time, subject and priority');
 $cleared=mka_ticket_schedule_save($db,1,$scope,'fixture_operator',mka_ticket_schedule_version($edited),'edit',array('visit'=>'','subject'=>'Visita de revisão','priority'=>'alta'));
 schedule_check($cleared['visita']===null,'editor can remove schedule without closing ticket');
 schedule_check((int)$db->query('SELECT COUNT(*) FROM suite_ticket_schedule_changes')->fetch_row()[0]===3,'successful changes retain audit history only once');
 $db->query("INSERT INTO sis_suporte VALUES(3,'789','fixture','Cliente exemplo','Outra visita','2026-10-15 10:00:00','2026-11-04 11:30:00','normal',7,'aberto','A'),(4,'790','other','Outro grupo','Invisivel','2026-10-15 10:00:00','2026-11-04 11:00:00','normal',8,'aberto','B'),(5,'791','fixture','Fechado','Fechado','2026-10-15 10:00:00','2026-11-04 11:00:00','normal',7,'fechado','C'),(6,'792','fixture','Outro horario','Combinado','2026-10-15 10:00:00','2026-11-04 20:00:00','normal',7,'aberto','D')");
 $slots=mka_ticket_schedule_slots($db,1,$scope,'2026-11-04');
 schedule_check(count($slots['slots'])===11&&$slots['slots'][0]['time']==='08:00'&&$slots['slots'][10]['time']==='18:00','fixed hourly choices run from 08 to 18 inclusive');
 schedule_check($slots['hours'][11]===1&&$slots['hours'][20]===1,'availability counts only visible open tickets, including custom hours');
 $version=mka_ticket_schedule_version($cleared);$payload=array('visit'=>'2026-11-04 11:00:00','subject'=>'Visita de revisão','priority'=>'alta');
 try{mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'edit',$payload);throw new LogicException('Expected overlap confirmation');}catch(MkaTicketBookingConflict $expected){schedule_check($expected->visits===1,'overlap requests confirmation instead of rejecting occupied slot');}
 $payload['conflicts_confirmed']=1;$sameHour=mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'edit',$payload);
 schedule_check($sameHour['visita']==='2026-11-04 11:00:00','confirmed overlap is allowed for separate teams');
 $version=mka_ticket_schedule_version($sameHour);$payload=array('day'=>'2026-11-05','time'=>'07:00');
 try{mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'move',$payload);throw new LogicException('Expected outside-hours confirmation');}catch(InvalidArgumentException $expected){schedule_check(true,'outside office hours requires explicit confirmation');}
 $payload['outside_confirmed']=true;$early=mka_ticket_schedule_save($db,1,$scope,'fixture_operator',$version,'move',$payload);
 schedule_check($early['visita']==='2026-11-05 07:00:00','agreed custom early time is accepted after confirmation');
 $payload=array('day'=>'2026-11-04','time'=>'20:00','outside_confirmed'=>true,'conflicts_confirmed'=>1);$late=mka_ticket_schedule_save($db,1,$scope,'fixture_operator',mka_ticket_schedule_version($early),'move',$payload);
 schedule_check($late['visita']==='2026-11-04 20:00:00','late custom time and overlap can both be confirmed');
 schedule_check(!mka_ticket_schedule_outside_hours('2026-11-04 08:00:00')&&!mka_ticket_schedule_outside_hours('2026-11-04 18:00:00')&&mka_ticket_schedule_outside_hours('2026-11-04 18:01:00'),'business-hour boundaries are explicit');
 mka_agenda_settings_save($db,array('enabled'=>true,'start'=>'12:00','end'=>'13:00'));
 try{mka_ticket_schedule_save($db,1,$scope,'fixture_operator',mka_ticket_schedule_version($late),'move',array('day'=>'2026-11-04','time'=>'12:00'));throw new LogicException('Expected lunch block');}catch(InvalidArgumentException $expected){schedule_check(strpos($expected->getMessage(),'almoço')!==false,'reserved lunch cannot be bypassed by direct reschedule request');}
 schedule_check(mka_ticket_schedule_row($db,1,$scope)['visita']===$late['visita'],'blocked lunch does not change the original visit');
 $db->query("UPDATE sis_suporte SET status='fechado' WHERE id=1");
 try{mka_ticket_schedule_save($db,1,$scope,'fixture_operator',mka_ticket_schedule_version($cleared),'move',array('day'=>'2026-10-21'));throw new LogicException('Expected closed denial');}catch(DomainException $expected){schedule_check(true,'closed ticket cannot be rescheduled');}
}finally{$db->query('DROP DATABASE `'.$schema.'`');$db->close();}
