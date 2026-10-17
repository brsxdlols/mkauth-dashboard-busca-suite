<?php
if (PHP_SAPI !== 'cli') exit;
require_once __DIR__.'/installer-database.php';
$db=mka_installer_database_connect();
$db->query('SET SESSION max_statement_time=10');
$clients=$db->query("SELECT login FROM sis_cliente WHERE cli_ativado='s'")->fetch_all(MYSQLI_NUM);
$oldMs=0;$newMs=0;
foreach($clients as $client){
 $login=$db->real_escape_string($client[0]);
 $t=microtime(true);$result=$db->query("SELECT DISTINCT acctstoptime FROM radacct WHERE username='$login' ORDER BY acctstoptime DESC");$row=$result->fetch_row();$old=$row?$row[0]:null;$oldMs+=(microtime(true)-$t)*1000;
 $t=microtime(true);$new=$db->query("SELECT MAX(acctstoptime) FROM radacct WHERE username='$login'")->fetch_row()[0];$newMs+=(microtime(true)-$t)*1000;
 if($old!==$new)throw new RuntimeException('Last disconnect classification differs');
}
echo json_encode(['clients'=>count($clients),'equivalent'=>true,'old_ms'=>round($oldMs,2),'new_ms'=>round($newMs,2)]).PHP_EOL;
