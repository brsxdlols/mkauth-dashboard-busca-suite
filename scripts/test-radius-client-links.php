<?php
require $argv[1];
$db = new mysqli(getenv('MYSQL_HOST') ?: '127.0.0.1', 'root', getenv('MYSQL_PWD'), 'mkradius');
// Connection-local fixtures shadow tables; no customer records are changed.
$db->query("CREATE TEMPORARY TABLE sis_cliente (login varchar(64), nome varchar(100), uuid_cliente varchar(64), grupo varchar(64),cli_ativado varchar(3) DEFAULT 's',bloqueado varchar(3) DEFAULT 'nao',pgcorte varchar(3) DEFAULT 'sim',pgaviso varchar(3) DEFAULT 'sim')");
$db->query('CREATE TEMPORARY TABLE sis_adicional (username varchar(64), login varchar(64))');
$db->query("INSERT INTO sis_cliente (login,nome,uuid_cliente,grupo) VALUES ('Principal','Cliente teste','uuid-a','A'),('restrito','Restrito','uuid-b','B')");
$db->query("INSERT INTO sis_adicional VALUES ('Extra','Principal')");
$all = radius_live_clients($db, array(' PRINCIPAL ', 'extra', 'restrito', 'desconhecido', "' OR 1=1 --"), true, array());
if (count($all)!==3 || $all['extra']['url']!==$all['principal']['url']) throw new Exception('Primary/additional resolution failed');
$limited = radius_live_clients($db, array('principal','EXTRA','restrito'), false, array('A'));
if (count($limited)!==2 || isset($limited['restrito'])) throw new Exception('Group scope failed');
if (radius_live_clients($db,array('desconhecido'),true,array())!==array()) throw new Exception('Unknown login failed');
$db->query("UPDATE sis_cliente SET cli_ativado='n',bloqueado='sim',pgcorte='nao' WHERE login='Principal'");
$flags=radius_live_clients($db,array('principal','extra'),true,array());
foreach($flags as $flag)if(!$flag['disabled'] || !$flag['blocked'] || !$flag['missing_pages'])throw new Exception('Flags failed');
$db->query("UPDATE sis_cliente SET pgcorte='sim',pgaviso='sim' WHERE login='Principal'");
$flags=radius_live_clients($db,array('principal'),true,array());
if($flags['principal']['missing_pages'])throw new Exception('Unexpected page warning');
echo "PASS: primary/additional, unknown, SQL input, group scope, disabled/blocked/page flags\n";
