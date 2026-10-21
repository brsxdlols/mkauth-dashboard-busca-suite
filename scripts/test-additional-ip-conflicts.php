<?php
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root',getenv('MK_TEST_DB_PASSWORD'),'mkradius');
require $argv[1].'/additional_connection.php';require $argv[1].'/ip_conflicts.php';
function verify($value,$message){if(!$value)throw new RuntimeException($message);}
$db->query('CREATE TEMPORARY TABLE sis_cliente(login VARCHAR(64),nome VARCHAR(128),uuid_cliente VARCHAR(48),grupo VARCHAR(40),ip VARCHAR(15),cli_ativado CHAR(1))');
$db->query('CREATE TEMPORARY TABLE sis_adicional(uuid_adicional VARCHAR(48),username VARCHAR(64),nome VARCHAR(128),plano VARCHAR(64),bloqueado VARCHAR(3),ip VARCHAR(15),mac VARCHAR(32),ramal VARCHAR(32),login VARCHAR(64))');
$db->query('CREATE TEMPORARY TABLE radacct(username VARCHAR(64),framedipaddress VARCHAR(15),nasipaddress VARCHAR(15),acctstoptime DATETIME NULL)');
$db->query("INSERT INTO sis_cliente VALUES('parent','Titular','parent-uuid','allowed','100.64.1.48','s'),('other','Outro','other-uuid','restricted','','s'),('same','Outro NAS','same-uuid','allowed','','s')");
$db->query("INSERT INTO sis_adicional VALUES('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','child','Adicional','100M','sim','','','todos','parent'),('ffffffff-bbbb-cccc-dddd-eeeeeeeeeeee','hidden','Restrito','50M','nao','','','todos','other')");
$db->query("INSERT INTO radacct VALUES('parent','100.64.1.48','10.0.0.1',NULL),('parent','100.64.1.48','10.0.0.1',NULL),('child','100.64.1.48','10.0.0.1',NULL),('other','100.64.1.48','10.0.0.1',NULL),('same','100.64.1.48','10.0.0.2',NULL),('same','100.64.1.49','10.0.0.1','2026-01-01')");
$scope="c.grupo='allowed'";$a=mka_additional_connection($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',$scope);
verify($a['username']==='child'&&$a['parent_login']==='parent'&&$a['bloqueado']==='sim','Additional identity and own block');
try{mka_additional_connection($db,'ffffffff-bbbb-cccc-dddd-eeeeeeeeeeee',$scope);throw new RuntimeException('Scope leaked');}catch(DomainException $e){}
$groups=mka_ip_conflicts($db,$scope);verify(count($groups)===1,'One conflict on same NAS');verify(count($groups[0]['clients'])===2,'Duplicate sessions, restricted clients and different NAS excluded');
verify($groups[0]['clients'][0]['additional']===true&&!$groups[0]['clients'][0]['fixed_matches'],'Dynamic additional');verify($groups[0]['clients'][1]['fixed_matches']===true,'Fixed parent');
$db->query("UPDATE radacct SET acctstoptime='2026-01-01' WHERE username='child'");verify(mka_ip_conflicts($db,$scope)===array(),'Conflict gone after disconnect');
verify(mka_ip_conflict_groups(array(array('ip'=>'0.0.0.0','nas'=>'10.0.0.1','login'=>'a','name'=>'a','fixed_ip'=>'','additional'=>0)))===array(),'Unknown IP ignored');
echo "Additional identity/scope and IP conflicts: fixed/dynamic, duplicate session, different NAS, restricted and disconnected clients OK\n";
