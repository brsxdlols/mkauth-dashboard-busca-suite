<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';require __DIR__.'/../addons/shared/invoice_financial_notes.php';
$db=mka_installer_database_connect();$db->set_charset('utf8mb4');
$db->query("CREATE TEMPORARY TABLE sis_cliente(uuid_cliente VARCHAR(64) COLLATE utf8mb4_unicode_ci,login VARCHAR(64) CHARACTER SET latin1)");
$db->query("CREATE TEMPORARY TABLE sis_lanc(id INT,login VARCHAR(64) CHARACTER SET latin1,status VARCHAR(16),deltitulo INT)");
$db->query("CREATE TEMPORARY TABLE suite_invoice_financial_notes(id INT,invoice_id INT,client_uuid VARCHAR(64) COLLATE utf8mb4_general_ci,kind VARCHAR(12))");
$db->query("INSERT INTO sis_cliente VALUES('fixture-uuid','fixture-login')");$db->query("INSERT INTO sis_lanc VALUES(1,'fixture-login','aberto',0)");$db->query("INSERT INTO suite_invoice_financial_notes VALUES(1,1,'fixture-uuid','note')");
$r=mka_invoice_note_counts($db,array('fixture-uuid'));if(($r['fixture-uuid']??0)!==1)throw new RuntimeException('Mixed collation count failed: '.$db->error);
$sql=mka_invoice_note_filter_sql($db);$r=$db->query('SELECT c.uuid_cliente FROM sis_cliente c WHERE '.$sql);if(!$r||$r->num_rows!==1)throw new RuntimeException('Mixed collation filter failed: '.$db->error);
$db->query('ALTER TABLE suite_invoice_financial_notes DROP COLUMN invoice_id');mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);if(mka_invoice_note_counts($db,array('fixture-uuid'))!==array())throw new RuntimeException('Optional note count failure must not hide customers');
echo "PASS mixed latin1 / utf8mb4 general / unicode notes count and filter; temporary tables only\n";
