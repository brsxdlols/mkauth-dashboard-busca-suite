<?php
// Hermetic MariaDB fixtures: TEMPORARY tables shadow native names only in this connection.
// No permanent table, accounting row or client is modified.
if (PHP_SAPI!=='cli') exit(1);
require __DIR__.'/../addons/shared/search_performance.php';
$db=new mysqli(getenv('MYSQL_HOST')?:'127.0.0.1',getenv('MYSQL_USER')?:'root',getenv('MYSQL_PASS')?:'',getenv('MYSQL_DB')?:'mkradius');
function check($ok,$name){if(!$ok)throw new RuntimeException('FAIL '.$name);echo 'PASS '.$name."\n";}
function sql($db,$s){if(!$db->query($s))throw new RuntimeException('Fixture query failed.');}
try {
 sql($db,"CREATE TEMPORARY TABLE radacct(username VARCHAR(64) COLLATE latin1_swedish_ci,acctstoptime DATETIME NULL)");
 sql($db,"CREATE TEMPORARY TABLE sis_cliente(id INT,login VARCHAR(64) COLLATE latin1_swedish_ci,cli_ativado CHAR(1))");
 sql($db,"CREATE TEMPORARY TABLE sis_adicional(login VARCHAR(64) COLLATE latin1_swedish_ci,username VARCHAR(64) COLLATE latin1_swedish_ci)");
 sql($db,"CREATE TEMPORARY TABLE sis_opcao(nome VARCHAR(64) PRIMARY KEY,valor VARCHAR(255))");
 sql($db,"INSERT INTO sis_cliente VALUES(1,'alice','s'),(2,'bob','s'),(3,'carol','s'),(4,'dana','n')");
 sql($db,"INSERT INTO sis_adicional VALUES('bob','child')");
 sql($db,"INSERT INTO radacct VALUES('ALICE',NULL),('alice',NULL),('CHILD',NULL),('bob','2026-10-01'),('dana',NULL)");
 $old=mka_online_family_sql();$new=mka_perf_direct_sql();
 $r=$db->query("SELECT id,($old) old_on,($new) new_on FROM sis_cliente c WHERE cli_ativado='s' ORDER BY id");$rows=$r->fetch_all(MYSQLI_ASSOC);
 check(count($rows)===3 && $rows[0]['new_on']==1 && $rows[1]['new_on']==1 && $rows[2]['new_on']==0,'principal, additional, case-insensitive, offline and inactive');
 foreach($rows as $row)check($row['old_on']===$row['new_on'],'same classification '.$row['id']);
 sql($db,"INSERT INTO radacct VALUES(' carol',NULL)");
 $row=$db->query("SELECT ($old) old_on,($new) new_on FROM sis_cliente c WHERE id=3")->fetch_assoc();
 check($row['old_on']==1 && $row['new_on']==0,'leading-space incompatibility detected');
 $version=mka_perf_version();
 $st=$db->prepare("INSERT INTO sis_opcao VALUES('suite_search_optimization',?)");$flag=json_encode(array('mode'=>'pending','until'=>time()+60,'version'=>$version,'schema'=>mka_perf_schema($db)));$st->bind_param('s',$flag);$st->execute();
 check(mka_perf_mode($db)==='legacy','future dirty login returns to normalized rule');
 // This DELETE affects the TEMPORARY fixture table only, never native accounting.
 sql($db,"DELETE FROM radacct WHERE BINARY username=BINARY ' carol'");
 check(mka_perf_mode($db)==='direct','provisional lease');
 $flag=json_encode(array('mode'=>'pending','until'=>time()-1,'version'=>$version,'schema'=>mka_perf_schema($db)));$st=$db->prepare("UPDATE sis_opcao SET valor=?");$st->bind_param('s',$flag);$st->execute();
 check(mka_perf_mode($db)==='legacy','abandoned validation expires to legacy');
 $flag=json_encode(array('mode'=>'direct','version'=>$version,'schema'=>mka_perf_schema($db)));$st->execute();
 $_GET['busca']='on';check(mka_search_online_family_sql($db)===mka_perf_direct_sql(),'approved direct mode scoped to online');
 $_GET['busca']='atrasado';check(mka_search_online_family_sql($db)===mka_online_family_sql(),'unrelated filters remain unchanged');
 $flag=json_encode(array('mode'=>'direct','version'=>'different'));$st->execute();
 check(mka_perf_mode($db)==='legacy','changed implementation requires revalidation');
} finally { $db->close(); }
