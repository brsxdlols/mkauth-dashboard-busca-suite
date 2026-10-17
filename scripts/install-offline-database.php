<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/installer-database.php';
$db = mka_installer_database_connect();
$db->query("CREATE TABLE IF NOT EXISTS sis_rel_cli_offline_display_cfg (id TINYINT UNSIGNED NOT NULL PRIMARY KEY, enabled TINYINT(1) NOT NULL DEFAULT 1, hours DECIMAL(8,2) NOT NULL DEFAULT 6.00) ENGINE=InnoDB");
$db->query('INSERT IGNORE INTO sis_rel_cli_offline_display_cfg (id,enabled,hours) VALUES (1,1,6.00)');
$db->query("CREATE TABLE IF NOT EXISTS sis_rel_con_ms_cfg (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, mail_host VARCHAR(100) DEFAULT NULL, mail_username VARCHAR(100) DEFAULT NULL, mail_password VARCHAR(50) DEFAULT NULL, mail_port VARCHAR(5) DEFAULT '587', link_logo VARCHAR(200) DEFAULT 'https://')");
$db->query("INSERT IGNORE INTO sis_rel_con_ms_cfg (id,mail_host,mail_username,mail_password,mail_port,link_logo) VALUES (1,'smtp.com.br','meuemail@email.com.br','','587','http://')");
$db->close();
echo "[offline] Tabelas verificadas; configuracoes existentes preservadas.\n";
