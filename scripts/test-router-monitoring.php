<?php
require __DIR__.'/installer-database.php';require __DIR__.'/../addons/shared/router_monitoring.php';
$db=mka_installer_database_connect();
$db->query("CREATE TEMPORARY TABLE nas(nasname varchar(64),shortname varchar(64))");
$db->query("INSERT INTO nas VALUES('172.16.12.62','Active'),('172.16.12.173','Backup')");
$db->query("CREATE TEMPORARY TABLE sis_opcao(nome varchar(100) PRIMARY KEY,valor TEXT)");
function check($v){if(!$v)throw new RuntimeException('Monitoring test failed');}
check(mka_router_monitoring_disabled($db)===array());
mka_router_monitoring_save($db,array('172.16.12.62'));
check(mka_router_monitoring_disabled($db)===array('172.16.12.173'));
mka_router_monitoring_save($db,array());check(count(mka_router_monitoring_disabled($db))===2);
mka_router_monitoring_save($db,array('172.16.12.62','172.16.12.173'));
check(mka_router_monitoring_disabled($db)===array());
echo "Temporary-table tests passed: default, disable backup, disable all, re-enable. No native NAS rows changed.\n";
