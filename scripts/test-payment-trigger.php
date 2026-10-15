<?php
// Isolated schema only: no real invoices or notification queue are modified.
if (PHP_SAPI!=='cli') exit(1);
require __DIR__.'/patch-payment-trigger.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MYSQL_HOST')?:'127.0.0.1',getenv('MYSQL_USER')?:'root',getenv('MYSQL_PASS')?:'vertrigo');
$schema='codex_payment_fixture_'.bin2hex(random_bytes(6));
$backup=sys_get_temp_dir().'/'.$schema;
function payment_check($ok,$label) { if (!$ok) throw new RuntimeException('FAIL '.$label); echo 'PASS '.$label.PHP_EOL; }
$db->query('CREATE DATABASE `'.$schema.'`');
try {
    $db->select_db($schema);putenv('MYSQL_DB='.$schema);
    $db->query('CREATE TABLE sis_cliente(login VARCHAR(64),zap VARCHAR(8),cli_ativado CHAR(1))');
    $db->query('CREATE TABLE sis_lanc(id INT PRIMARY KEY,login VARCHAR(64),status VARCHAR(20),coletor VARCHAR(64),datavenc DATETIME,datapag DATETIME,valor DECIMAL(10,2),valorpag DECIMAL(10,2),formapag VARCHAR(20))');
    $db->query('CREATE TABLE brl_pago(id INT PRIMARY KEY,data DATETIME,login VARCHAR(64),coletor VARCHAR(64),datavenc DATETIME,datapag DATETIME,valor DECIMAL(10,2),valorpag DECIMAL(10,2),formapag VARCHAR(20),envio INT DEFAULT 0)');
    $original="BEGIN IF NEW.status = 'pago' AND EXISTS (SELECT 1 FROM sis_cliente WHERE login = NEW.login AND zap <> 'nao' AND cli_ativado <> 'n') AND DATE(NEW.datapag) = DATE(NOW()) THEN INSERT INTO brl_pago (id,data,login,coletor,datavenc,datapag,valor,valorpag,formapag) VALUES (NEW.id,NOW(),NEW.login,NEW.coletor,NEW.datavenc,NEW.datapag,NEW.valor,NEW.valorpag,NEW.formapag); END IF; END";
    $db->query('CREATE TRIGGER tig_brl_pag AFTER UPDATE ON sis_lanc FOR EACH ROW '.$original);
    $db->query("INSERT INTO sis_cliente VALUES('allowed','sim','s'),('disabled','sim','n'),('nozap','nao','s')");
    $db->query("INSERT INTO sis_lanc VALUES(1,'allowed','aberto','fixture',NOW(),NOW(),55,55,'dinheiro'),(2,'disabled','aberto','fixture',NOW(),NOW(),55,55,'dinheiro'),(3,'nozap','aberto','fixture',NOW(),NOW(),55,55,'dinheiro'),(4,'allowed','aberto','fixture',NOW(),DATE_SUB(NOW(),INTERVAL 1 DAY),55,55,'dinheiro')");
    $db->query("UPDATE sis_lanc SET status='pago' WHERE id=1");
    $db->query("UPDATE sis_lanc SET status='aberto' WHERE id=1");
    $failed=false;try {$db->query("UPDATE sis_lanc SET status='pago' WHERE id=1");}catch(mysqli_sql_exception $error){$failed=$error->getCode()===1062;}
    payment_check($failed,'original trigger reproduces duplicate key after refund');
    mka_payment_trigger_install($backup);
    $db->query('UPDATE brl_pago SET envio=1');
    $db->query("UPDATE sis_lanc SET status='pago',valorpag=60 WHERE id=1");
    $row=$db->query('SELECT COUNT(*) n,MAX(valorpag) amount,MAX(envio) sent FROM brl_pago')->fetch_assoc();
    payment_check((int)$row['n']===1 && (float)$row['amount']===60.0 && (int)$row['sent']===0,'second payment refreshes the same notification without duplicate');
    $db->query('UPDATE brl_pago SET envio=1');$db->query('UPDATE sis_lanc SET valorpag=61 WHERE id=1');
    payment_check((int)$db->query('SELECT envio FROM brl_pago WHERE id=1')->fetch_row()[0]===1,'editing an already paid invoice does not requeue');
    $db->query("UPDATE sis_lanc SET status='pago' WHERE id IN(2,3,4)");
    payment_check((int)$db->query('SELECT COUNT(*) FROM brl_pago')->fetch_row()[0]===1,'inactive clients, disabled WhatsApp and old payment dates stay excluded');
    mka_payment_trigger_install($backup);
    payment_check(is_file($backup.'/rollback-trigger.sql'),'migration is repeatable and saves rollback');
    payment_check(mka_payment_trigger_body($original.' /* custom */')===null,'unknown custom triggers are preserved');
} finally {
    $db->query('DROP DATABASE `'.$schema.'`');$db->close();
    foreach(glob($backup.'/*')?:array() as $file)unlink($file);
    if(is_dir($backup))rmdir($backup);
}
