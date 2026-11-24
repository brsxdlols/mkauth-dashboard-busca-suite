<?php
if(PHP_SAPI!=='cli'||getenv('MKA_MENU_TEST')!=='1')exit("Set MKA_MENU_TEST=1 for isolated preference tests.\n");
require __DIR__.'/installer-database.php';require __DIR__.'/../addons/shared/mobile_menu.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=mka_installer_database_connect();
function check_menu($yes,$message){if(!$yes)throw new RuntimeException($message);}
function menu_rejected($fn,$type){try{$fn();}catch(Throwable $e){check_menu($e instanceof $type,'Unexpected exception');return;}throw new RuntimeException('Invalid preference accepted');}
$sql="SELECT valor FROM sis_opcao WHERE nome='suite_mobile_menu_version'";$before=$db->query($sql)->fetch_all(MYSQLI_ASSOC);
try{
 $db->query('CREATE TEMPORARY TABLE sis_opcao(nome VARCHAR(64) PRIMARY KEY,valor TEXT) ENGINE=InnoDB');
 check_menu(mka_mobile_menu_version($db)==='nova','Missing preference must use Nova');
 mka_mobile_menu_save($db,'legada',true,'csrf','csrf');check_menu(mka_mobile_menu_version($db)==='legada','Legacy preference not saved');
 mka_mobile_menu_save($db,'nova',true,'csrf','csrf');check_menu(mka_mobile_menu_version($db)==='nova','Nova preference not saved');
 menu_rejected(function()use($db){mka_mobile_menu_save($db,'legada',false,'csrf','csrf');},'DomainException');
 menu_rejected(function()use($db){mka_mobile_menu_save($db,'legada',true,'csrf','wrong');},'DomainException');
 menu_rejected(function()use($db){mka_mobile_menu_save($db,'legada',true,'','');},'DomainException');
 menu_rejected(function()use($db){mka_mobile_menu_save($db,'invalid',true,'csrf','csrf');},'InvalidArgumentException');
 menu_rejected(function()use($db){mka_mobile_menu_save($db,array('nova'),true,'csrf','csrf');},'InvalidArgumentException');
 check_menu(mka_mobile_menu_version($db)==='nova','Rejected requests changed preference');
 $db->query("UPDATE sis_opcao SET valor='unknown'");check_menu(mka_mobile_menu_version($db)==='nova','Unknown preference must use Nova');
}finally{$db->query('DROP TEMPORARY TABLE IF EXISTS sis_opcao');}
check_menu($before===$db->query($sql)->fetch_all(MYSQLI_ASSOC),'Persistent preference was changed');
echo "PASS defaults, Nova/Legada persistence, permission, CSRF, invalid values and unchanged persistent settings.\n";
