<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';require __DIR__.'/install-hotsite-default.php';
$db=mka_installer_database_connect();$table='suite_hotsite_test_'.bin2hex(random_bytes(4));$dir=sys_get_temp_dir().'/'.$table;mkdir($dir,0700);
try{
 $db->query("CREATE TEMPORARY TABLE `$table`(nome VARCHAR(64) PRIMARY KEY,valor VARCHAR(128))");
 mka_install_hotsite_default($db,$dir,$table);$r=$db->query("SELECT valor FROM `$table`")->fetch_assoc();if($r['valor']!=='layout-vpscloud-whatsapp')throw new RuntimeException('Missing default not selected');
 foreach(array('padrao','padrao_antigo','custom-site')as $value){$db->query("UPDATE `$table` SET valor='$value'");mka_install_hotsite_default($db,$dir,$table);$r=$db->query("SELECT valor FROM `$table`")->fetch_assoc();if($r['valor']!==($value==='custom-site'?$value:'layout-vpscloud-whatsapp'))throw new RuntimeException('Default/custom mismatch');}
 $db->query("UPDATE `$table` SET valor='padrao'");mka_install_hotsite_default($db,$dir.'/absent',$table);if($db->query("SELECT valor FROM `$table`")->fetch_assoc()['valor']!=='padrao')throw new RuntimeException('Missing layout changed preference');
 echo "PASS hotsite defaults, custom preservation and missing asset; temporary table only\n";
}finally{rmdir($dir);}
