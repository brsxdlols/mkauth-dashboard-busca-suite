<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/support_attendance.php';
require __DIR__.'/../addons/shared/ticket_board.php';
function attendance_check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
$now=strtotime('2026-10-15 10:00:00');
attendance_check(mka_ticket_timing('2026-10-15 09:59:00',$now)['state']==='late','even a recent overdue visit is marked late');
attendance_check(mka_ticket_timing('2026-10-15 10:00:00',$now)['text']==='Visita agora','visit due now is explicit');
attendance_check(mka_ticket_timing('2026-10-16 10:00:00',$now)['state']==='upcoming' && mka_ticket_timing('2026-10-16 10:01:00',$now)['state']==='scheduled','24-hour filter boundary');
attendance_check(mka_ticket_timing('0000-00-00 00:00:00',$now)['state']==='unscheduled' && mka_ticket_timing(null,$now)['state']==='unscheduled','empty and native zero dates are unscheduled');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root',getenv('MYSQL_PASS')?:'vertrigo');$db->set_charset('utf8mb4');$schema='codex_attendance_fixture_'.bin2hex(random_bytes(6));$db->query('CREATE DATABASE `'.$schema.'`');
try{
 $db->select_db($schema);mka_attendance_schema($db);mka_attendance_schema($db);
 $db->query('CREATE TABLE sis_acesso(login VARCHAR(64),cli_grupos VARCHAR(255))');$db->query('CREATE TABLE sis_cliente(uuid_cliente VARCHAR(64),grupo VARCHAR(64))');
 $db->query("INSERT INTO sis_acesso VALUES('restricted','allowed,ped_fil'),('full','full_clientes')");$db->query("INSERT INTO sis_cliente VALUES('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa','allowed'),('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb','other')");
 $scope=mka_attendance_scope($db,'restricted');attendance_check((int)$db->query('SELECT COUNT(*) FROM sis_cliente c WHERE '.$scope)->fetch_row()[0]===1,'restricted operator only sees allowed client groups');
 attendance_check(mka_attendance_scope($db,'missing')==='1=0' && mka_attendance_scope($db,'full')==='1=1','unknown operator is denied and full access is recognized');
 $client=array('uuid_cliente'=>'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa','login'=>'fixture','nome'=>'Cliente de teste');
 foreach(array('resolved','finished','service') as $outcome){$key='fixture_key_'.$outcome;$first=mka_attendance_save($db,$client,'restricted','Sem conexão','Relato do atendimento',$outcome,$key);$again=mka_attendance_save($db,$client,'restricted','Sem conexão','Relato do atendimento',$outcome,$key);attendance_check($first['id']===$again['id'] && $again['outcome']===$outcome,'outcome '.$outcome.' is saved once on repeated submission');}
 try{mka_attendance_save($db,$client,'restricted','Alterado','Outro relato','resolved','fixture_key_resolved');throw new LogicException('Expected conflict');}catch(InvalidArgumentException $expected){attendance_check(true,'same request key cannot be reused for different data');}
 attendance_check((int)$db->query('SELECT COUNT(*) FROM suite_support_attendance')->fetch_row()[0]===3,'no duplicate attendance records');
 $id=(int)$db->query("SELECT id FROM suite_support_attendance WHERE request_key='fixture_key_resolved'")->fetch_row()[0];
 mka_attendance_update($db,$id,'restricted',$scope,false,1,'edit','Assunto editado','Relato corrigido','service');
 $row=$db->query('SELECT * FROM suite_support_attendance WHERE id='.$id)->fetch_assoc();
 attendance_check($row['outcome']==='resolved' && (int)$row['version']===2,'editing preserves result and increments version');
 try{mka_attendance_update($db,$id,'restricted',$scope,false,1,'reopen','','','');throw new LogicException('Expected conflict');}catch(UnexpectedValueException $expected){attendance_check(true,'stale version cannot overwrite another change');}
 try{mka_attendance_update($db,$id,'another',$scope,false,2,'reopen','','','');throw new LogicException('Expected denial');}catch(DomainException $expected){attendance_check(true,'another operator cannot change record without admin permission');}
 mka_attendance_update($db,$id,'restricted',$scope,false,2,'reopen','','','');
 mka_attendance_update($db,$id,'restricted',$scope,false,3,'finish','Assunto editado','Relato final','finished');
 $row=$db->query('SELECT * FROM suite_support_attendance WHERE id='.$id)->fetch_assoc();
 attendance_check($row['outcome']==='finished' && (int)$row['version']===4,'reopened attendance finishes on same record');
 attendance_check((int)$db->query('SELECT COUNT(*) FROM suite_support_attendance_changes WHERE attendance_id='.$id)->fetch_row()[0]===3,'changes retain audit history');
 $unicode=mka_attendance_save($db,$client,'restricted',str_repeat('á',255),str_repeat('é',8000),'resolved','fixture_unicode_key');attendance_check((int)$unicode['id']>0,'character limits support accented Portuguese text');
}finally{$db->query('DROP DATABASE `'.$schema.'`');$db->close();}
