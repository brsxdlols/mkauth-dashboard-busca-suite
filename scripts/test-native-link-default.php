<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/install-native-link-default.php';
require __DIR__.'/installer-database.php';
$db=mka_installer_database_connect();$table='suite_links_test_'.bin2hex(random_bytes(5));
function check_link($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message.PHP_EOL;}
try {
    $db->query("CREATE TEMPORARY TABLE `$table` (id INT AUTO_INCREMENT PRIMARY KEY,num_conexoes INT NOT NULL,tempo_queda INT NOT NULL,porta_acesso INT NOT NULL,porta_acesso2 INT,porta_acesso3 INT,link_whats VARCHAR(255),links_ext VARCHAR(10) DEFAULT 'php',check_online VARCHAR(10),extra_column INT NOT NULL DEFAULT 9)");
    mka_install_native_link_default($db,$table);
    $row=$db->query("SELECT * FROM `$table`")->fetch_assoc();check_link($row['links_ext']==='hhvm'&&(int)$row['extra_column']===9,'fresh setup uses HHVM with extra schema columns');
    $column=$db->query("SHOW COLUMNS FROM `$table` LIKE 'links_ext'")->fetch_assoc();check_link($column['Default']==='hhvm','database default is HHVM');
    $db->query("UPDATE `$table` SET links_ext='php',num_conexoes=77");mka_install_native_link_default($db,$table);$row=$db->query("SELECT * FROM `$table`")->fetch_assoc();check_link($row['links_ext']==='php'&&(int)$row['num_conexoes']===77,'existing PHP and unrelated preferences preserved');
    $db->query("UPDATE `$table` SET links_ext='hhvm'");mka_install_native_link_default($db,$table);check_link($db->query("SELECT links_ext FROM `$table`")->fetch_assoc()['links_ext']==='hhvm','existing HHVM preserved');
    $db->query("UPDATE `$table` SET links_ext=NULL");mka_install_native_link_default($db,$table);check_link($db->query("SELECT links_ext FROM `$table`")->fetch_assoc()['links_ext']==='hhvm','empty setting falls back to HHVM');
    mka_install_native_link_default($db,$table);check_link((int)$db->query("SELECT COUNT(*) n FROM `$table`")->fetch_assoc()['n']===1,'reinstallation does not duplicate settings');
}finally{$db->query("DROP TEMPORARY TABLE IF EXISTS `$table`");$db->close();}
