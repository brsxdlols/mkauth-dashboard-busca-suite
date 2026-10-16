<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/client_support.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$admin=new mysqli('127.0.0.1','root','vertrigo');$schema='suite_client_support_test_'.bin2hex(random_bytes(5));$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4');
function support_assert($ok,$label){if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label.PHP_EOL;}
try{
 $db=new mysqli('127.0.0.1','root','vertrigo',$schema);$db->set_charset('utf8mb4');mka_attendance_schema($db);
 $db->query('CREATE TABLE sis_acesso(login VARCHAR(64),cli_grupos VARCHAR(255))');$db->query("INSERT INTO sis_acesso VALUES('ana','A')");
 $db->query('CREATE TABLE sis_cliente(uuid_cliente VARCHAR(64),login VARCHAR(64),nome VARCHAR(255),cli_ativado VARCHAR(1),grupo VARCHAR(64))');$db->query("INSERT INTO sis_cliente VALUES('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa','clienteA','Cliente A','s','A'),('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb','clienteB','Cliente B','s','B')");
 $db->query('CREATE TABLE sis_suporte(id INT PRIMARY KEY,chamado VARCHAR(64),assunto VARCHAR(255),abertura VARCHAR(255),status VARCHAR(20),login VARCHAR(64),atendente VARCHAR(255),login_atend VARCHAR(64),visita DATETIME)');
 $db->query("INSERT INTO sis_suporte VALUES(1,'001','OS do cliente A','2026-10-15 09:30:00','aberto','clienteA','Ana','ana','2026-10-16 10:00:00'),(2,'002','OS privada','2026-10-15 09:00:00','aberto','clienteB','Ana','ana',NULL),(3,'003','OS antiga','2026-10-14 09:00:00','fechado','clienteA','Ana','ana',NULL)");
 $db->query('CREATE TABLE sis_msg(id INT AUTO_INCREMENT PRIMARY KEY,chamado VARCHAR(64),msg TEXT)');
 $db->query("INSERT INTO suite_support_attendance(id,client_uuid,login,client_name,operator,subject,notes,outcome,request_key,payload_hash,created_at) VALUES(1,'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa','clienteA','Cliente A','ana','Sem conexão','Relato A','service','request_fixture_1','hash','2026-10-15 09:00:00'),(2,'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa','clienteA','Cliente A','ana','Orientação','Relato B','resolved','request_fixture_2','hash','2026-10-15 08:00:00')");
 $db->query("INSERT INTO sis_msg(chamado,msg) VALUES('001','[Atendimento #1] Relato A'),('002','[Atendimento #2] Outro cliente'),('003','[Atendimento #10] Não é o atendimento 1')");
 $scope=mka_attendance_scope($db,'ana');$uuid='aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
 $feed=mka_client_support_feed($db,$uuid,$scope);support_assert($feed['total']===4&&$feed['records'][0]['kind']==='ticket','client feed combines real attendance and native OS chronologically');
 try{mka_client_support_feed($db,'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',$scope);throw new RuntimeException('private client exposed');}catch(DomainException $e){echo "PASS client outside allowed group denied\n";}
 $service=mka_client_support_service($db,array('id'=>1,'client_uuid'=>$uuid,'outcome'=>'service'),$scope);support_assert($service['chamado']==='001','OS is linked only after native record and exact attendance marker exist');
 support_assert(mka_client_support_service($db,array('id'=>2,'client_uuid'=>$uuid,'outcome'=>'service'),$scope)===null,'marker on another client does not link');
 $report=mka_support_history_report($db,$scope,array('kind'=>'all','from'=>'2026-10-15','to'=>'2026-10-15'),'2026-10-15');support_assert($report['total']===3&&count(array_filter($report['records'],function($r){return $r['kind']==='ticket';}))===1,'daily report combines both kinds and excludes private and prior-day OS');
 $report=mka_support_history_report($db,$scope,array('kind'=>'ticket','all'=>'1'),'2026-10-15');support_assert($report['total']===2,'OS-only history includes open and closed records');
 $report=mka_support_history_report($db,$scope,array('kind'=>'all','all'=>'1','outcome'=>'resolved'),'2026-10-15');support_assert($report['total']===1&&$report['records'][0]['kind']==='attendance','attendance result filter does not invent OS entries');
 $report=mka_support_history_report($db,$scope,array('kind'=>'all','all'=>'1','outcome'=>'os_closed'),'2026-10-15');support_assert($report['total']===1&&$report['records'][0]['reference']==='003','closed OS filter');
 support_assert((int)$db->query('SELECT COUNT(*) FROM sis_suporte')->fetch_row()[0]===3&&(int)$db->query('SELECT COUNT(*) FROM suite_support_attendance')->fetch_row()[0]===2,'history reads do not create duplicate attendance or OS');
 $draftClient=array('uuid_cliente'=>$uuid,'login'=>'clienteA','nome'=>'Cliente A');$draft=mka_attendance_save($db,$draftClient,'ana','Contato em andamento','Aguardando retorno do cliente','open','fixture_open_attendance_123');$same=mka_attendance_save($db,$draftClient,'ana','Contato em andamento','Aguardando retorno do cliente','open','fixture_open_attendance_123');support_assert($draft['outcome']==='open'&&$draft['id']===$same['id'],'new attendance can remain open and retries do not duplicate the record');
}finally{$admin->query('DROP DATABASE `'.$schema.'`');}
