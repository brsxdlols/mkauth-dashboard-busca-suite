<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/visit_reminders.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$admin=new mysqli('127.0.0.1','root','vertrigo');$schema='suite_reminder_test_'.bin2hex(random_bytes(5));$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4');
function reminder_check($ok,$label){if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label.PHP_EOL;}
try {
 $db=new mysqli('127.0.0.1','root','vertrigo',$schema);$db->set_charset('utf8mb4');mka_visit_reminders_schema($db);
 $db->query('CREATE TABLE sis_acesso(login VARCHAR(64),cli_grupos VARCHAR(255))');$db->query("INSERT INTO sis_acesso VALUES('ana','A'),('joao','full_clientes')");
 $db->query('CREATE TABLE sis_cliente(login VARCHAR(64),grupo VARCHAR(64),cli_ativado VARCHAR(1))');$db->query("INSERT INTO sis_cliente VALUES('a','A','s'),('b','B','s')");
 $db->query('CREATE TABLE sis_msg(chamado VARCHAR(64),msg TEXT)');
 $db->query('CREATE TABLE sis_suporte(id INT PRIMARY KEY,chamado VARCHAR(64),nome VARCHAR(255),login VARCHAR(64),status VARCHAR(20),visita DATETIME)');
 $db->query("INSERT INTO sis_suporte VALUES(1,'001','Cliente A','a','aberto','2026-10-15 10:10:00'),(2,'002','Outro grupo','b','aberto','2026-10-15 10:05:00'),(3,'003','Ainda cedo','a','aberto','2026-10-15 10:10:01'),(4,'004','Fechado','a','fechado','2026-10-15 10:06:00')");
 $db->query('CREATE TABLE sis_solic(id INT PRIMARY KEY,nome VARCHAR(255),tipo VARCHAR(20),status VARCHAR(20),login_atend VARCHAR(64),visita DATETIME)');
 $db->query("INSERT INTO sis_solic VALUES(1,'Instalação própria','instalacao','pendente','ana','2026-10-15 10:08:00'),(2,'Outro operador','instalacao','aberto','joao','2026-10-15 10:05:00'),(3,'Assinatura','assinatura','aberto','ana','2026-10-15 10:04:00'),(4,'Finalizada','instalacao','concluido','ana','2026-10-15 10:04:00')");
 $events=mka_visit_reminders_collect($db,'ana',true,true,false,'2026-10-15 10:00:00');reminder_check(count($events)===2,'10-minute window includes exact boundary and applies ticket groups and installation ownership');
 reminder_check($events[0]['kind']==='installation'&&$events[1]['kind']==='ticket','both kinds appear chronologically');
 $again=mka_visit_reminders_collect($db,'ana',true,true,false,'2026-10-15 10:00:00');reminder_check(array_column($events,'id')===array_column($again,'id'),'polling is idempotent');
 $later=mka_visit_reminders_collect($db,'ana',true,true,false,'2026-10-16 10:00:00');reminder_check(count($later)===2,'pending reminders persist after visit time and next day');
 mka_visit_reminder_dismiss($db,'joao',(int)$events[0]['id']);reminder_check(count(mka_visit_reminders_collect($db,'ana',true,true,false,'2026-10-16 10:00:00'))===2,'another operator cannot dismiss your reminder');
 mka_visit_reminder_dismiss($db,'ana',(int)$events[0]['id']);reminder_check(count(mka_visit_reminders_collect($db,'ana',true,true,false,'2026-10-15 10:00:00'))===1,'dismissal persists and discovery does not recreate it');
 reminder_check(count(mka_visit_reminders_collect($db,'ana',false,false,false,'2026-10-15 10:00:00'))===0,'revoked permissions hide details');
 $db->query("UPDATE sis_suporte SET visita='2026-10-15 10:09:00' WHERE id=1");$changed=mka_visit_reminders_collect($db,'ana',true,false,false,'2026-10-15 10:00:00');reminder_check(count($changed)===1&&$changed[0]['id']!==$events[1]['id'],'rescheduling creates a distinct reminder and hides stale schedule');
 $all=mka_visit_reminders_collect($db,'joao',true,true,true,'2026-10-15 10:00:00');reminder_check(count($all)===4,'all-clients and all-installations permissions use native scopes');
 $db->query("INSERT INTO sis_suporte VALUES(5,'005','Viabilidade sem cliente','','aberto','2026-10-15 10:05:00')");$db->query("INSERT INTO suite_ticket_viability(ticket_id,operator,request_key,payload_hash,details) VALUES(5,'ana','fixture_viability_123','hash','{}')");
 $own=mka_visit_reminders_collect($db,'ana',true,false,false,'2026-10-15 10:00:00');reminder_check(in_array(5,array_column($own,'record_id')),'feasibility reminder includes owner without fake client');$other=mka_visit_reminders_collect($db,'joao',true,false,false,'2026-10-15 10:00:00');reminder_check(!in_array(5,array_column($other,'record_id')),'full client access does not expose another operator feasibility');
 // Preferences belong to each operator and visit kind, and schema upgrades never reset them.
 reminder_check(mka_visit_reminder_preferences($db,'new','ticket')===array('enabled'=>true,'advance_minutes'=>10),'new installation defaults to enabled and 10 minutes');
 mka_visit_reminder_preferences_save($db,'ana','ticket',array('enabled'=>false,'advance_minutes'=>5));
 mka_visit_reminders_schema($db);
 reminder_check(mka_visit_reminder_preferences($db,'ana','ticket')===array('enabled'=>false,'advance_minutes'=>5),'reinstall preserves disabled and advance choice');
 reminder_check(count(mka_visit_reminders_collect($db,'ana',true,false,false,'2026-10-15 10:00:00'))===0,'disabled ticket popups hide pending reminders');
 reminder_check(mka_visit_reminder_preferences($db,'ana','installation')['enabled']&&mka_visit_reminder_preferences($db,'joao','ticket')['enabled'],'other kind and user remain independent');
 mka_visit_reminder_preferences_save($db,'ana','ticket',array('enabled'=>true,'advance_minutes'=>5));
 $five=mka_visit_reminders_collect($db,'ana',true,false,false,'2026-10-15 10:00:00');reminder_check(count($five)===1&&$five[0]['record_id']==5,'5-minute preference does not show existing earlier reminders prematurely');
 mka_visit_reminder_preferences_save($db,'ana','ticket',array('enabled'=>true,'advance_minutes'=>0));
 reminder_check(count(mka_visit_reminders_collect($db,'ana',true,false,false,'2026-10-15 10:00:00'))===0,'exact-time preference never alerts in advance');
 $db->query("INSERT INTO sis_suporte VALUES(6,'006','Hora exata','a','aberto','2026-10-15 10:00:00')");
 $exact=mka_visit_reminders_collect($db,'ana',true,false,false,'2026-10-15 10:00:20');reminder_check(count($exact)===1&&$exact[0]['record_id']==6&&$exact[0]['advance_minutes']===0,'first poll after exact time creates reminder');
 mka_visit_reminder_dismiss($db,'ana',(int)$exact[0]['id']);mka_visit_reminders_schema($db);reminder_check(count(mka_visit_reminders_collect($db,'ana',true,false,false,'2026-10-15 10:00:30'))===0,'Entendido dismissal remains closed after schema reinstall');
 try{mka_visit_reminder_preferences_save($db,'ana','ticket',array('enabled'=>true,'advance_minutes'=>3));throw new RuntimeException('Invalid preference accepted');}catch(InvalidArgumentException $expected){reminder_check(true,'unsupported advance rejected');}
}finally{$admin->query('DROP DATABASE `'.$schema.'`');}
