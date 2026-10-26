<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';$db=mka_installer_database_connect();$db->set_charset('utf8mb4');
if(in_array(($argv[1]??'read'),array('set','restore'),true)){
    $offset=$argv[2]??'';if(!preg_match('/^-[0][2-5]:00$/D',$offset) && !(($argv[1]??'')==='restore' && ($offset==='SYSTEM'||preg_match('/^[+-](?:0[0-9]|1[0-4]):[0-5][0-9]$/D',$offset))))throw new RuntimeException('Invalid Brazilian offset');
    if(!$db->query("SET GLOBAL time_zone='".$offset."'"))throw new RuntimeException("Não foi possível aplicar o fuso ao banco.");echo 'ok';exit;
}
$q=$db->query("SELECT valor FROM sis_opcao WHERE nome='suite_server_timezone' LIMIT 1");$v=$q->fetch_assoc();
$p=$db->query('SELECT cidade,estado,cep FROM sis_provedor ORDER BY id LIMIT 1')->fetch_assoc();
$m=$db->query('SELECT NOW() AS `local_time`,UTC_TIMESTAMP() AS `utc_time`,@@global.time_zone global_zone,TIMESTAMPDIFF(SECOND,UTC_TIMESTAMP(),NOW()) offset_seconds')->fetch_assoc();
echo json_encode(array('config'=>$v?json_decode($v['valor'],true):array('mode'=>'auto','enabled'=>false),'provider'=>$p,'mysql'=>$m));
