#!/usr/bin/env php
<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
$lock = @fopen('/var/run/mkauth-manual-block-enforcer.lock', 'c');
if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) exit(0);
$db = @new mysqli('127.0.0.1', 'root', 'vertrigo', 'mkradius');
if ($db->connect_error) exit(2);
$db->set_charset('utf8');
function mka_enforcer_has_table(mysqli $db, $table) {$safe=$db->real_escape_string($table);$r=$db->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$safe}' LIMIT 1");return $r&&$r->num_rows>0;}
function mka_enforcer_has_column(mysqli $db, $table, $column) {$t=$db->real_escape_string($table);$c=$db->real_escape_string($column);$r=$db->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$t}' AND COLUMN_NAME='{$c}' LIMIT 1");return $r&&$r->num_rows>0;}
if (!mka_enforcer_has_table($db,'dashboard_am_manual_block_audit') || !mka_enforcer_has_column($db,'sis_cliente','tipobloq')) exit(0);
$sql="SELECT c.uuid_cliente FROM sis_cliente c JOIN dashboard_am_manual_block_audit a ON a.uuid_cliente=c.uuid_cliente JOIN (SELECT uuid_cliente,MAX(id) id FROM dashboard_am_manual_block_audit GROUP BY uuid_cliente) latest ON latest.id=a.id WHERE a.acao='bloqueio' AND (c.bloqueado<>'sim' OR c.tipobloq<>'man')";
$result=$db->query($sql);$restored=0;
if($result)while($client=$result->fetch_assoc()){$uuid=$db->real_escape_string($client['uuid_cliente']);if($db->query("UPDATE sis_cliente SET bloqueado='sim',tipobloq='man',data_bloq=NOW() WHERE uuid_cliente='{$uuid}' LIMIT 1"))$restored++;}
if($restored>0)@file_put_contents('/var/log/mkauth_manual_block_enforcer.log',date('Y-m-d H:i:s').' restaurados='.$restored.PHP_EOL,FILE_APPEND|LOCK_EX);
$db->close();@flock($lock,LOCK_UN);@fclose($lock);exit(0);

