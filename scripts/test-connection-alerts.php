<?php
if (PHP_SAPI !== 'cli') exit;
require_once __DIR__.'/installer-database.php';
$db=mka_installer_database_connect();
$db->query('SET SESSION max_statement_time=10');
$db->query("SET SESSION optimizer_switch='semijoin=off'");
$source=file_get_contents(__DIR__.'/../addons/busca_inteligente/cli_conn_alerta.php');
if(!preg_match('/\$query_conn = mysqli_query\(\$link, "(SELECT r.username, COUNT\(\*\).*?)"\);/s',$source,$match))throw new RuntimeException('Alert query not found');
function compare_alerts($db,$template,$from,$to,$minutes,$minimum,$search){
 $replacements=['$data_inicial_sql'=>$db->real_escape_string($from),'$data_final_sql'=>$db->real_escape_string($to),'$tempo_conn'=>(string)$minutes,'$qtd_quedas'=>(string)$minimum,'$busca_sql'=>$db->real_escape_string($search)];
 $candidate=strtr($template,$replacements);
 $search=$db->real_escape_string($search);
 $baseline="SELECT username FROM radacct WHERE username IN (SELECT login FROM sis_cliente WHERE cli_ativado='s' AND bloqueado='nao') AND acctstarttime BETWEEN '$from' AND '$to 23:59:59' AND acctsessiontime < $minutes*60 AND username LIKE '%$search%' ORDER BY username";
 $t=microtime(true);$r=$db->query($baseline);if(!$r)throw new RuntimeException($db->error);$expected=[];while($row=$r->fetch_row())$expected[$row[0]]=($expected[$row[0]]??0)+1;$expected=array_filter($expected,function($count)use($minimum){return $count>=$minimum;});$baselineMs=round((microtime(true)-$t)*1000,1);
 $t=microtime(true);$r=$db->query($candidate);if(!$r)throw new RuntimeException($db->error);$actual=[];while($row=$r->fetch_assoc())$actual[$row['username']]=(int)$row['quedas'];$candidateMs=round((microtime(true)-$t)*1000,1);
 ksort($expected);ksort($actual);if($expected!==$actual)throw new RuntimeException('Alert counts differ');
 echo json_encode(['equivalent'=>true,'clients'=>count($actual),'baseline_ms'=>$baselineMs,'aggregate_ms'=>$candidateMs]).PHP_EOL;
}
$db->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
compare_alerts($db,$match[1],date('Y-m-d',strtotime('-5 days')),date('Y-m-d'),15,20,'');
$db->commit();
$db->query("CREATE TEMPORARY TABLE sis_cliente(login VARCHAR(64),cli_ativado CHAR(1),bloqueado VARCHAR(3)) CHARACTER SET latin1 COLLATE latin1_swedish_ci");
$db->query("CREATE TEMPORARY TABLE radacct(username VARCHAR(64),acctstarttime DATETIME,acctsessiontime INT) CHARACTER SET latin1 COLLATE latin1_swedish_ci");
$db->query("INSERT INTO sis_cliente VALUES ('alpha','s','nao'),('ALPHA','s','nao'),('blocked','s','sim'),('inactive','n','nao'),('quote\\'user','s','nao')");
$db->query("INSERT INTO radacct VALUES ('alpha','2026-10-17 00:00:00',10),('alpha','2026-10-17 23:59:59',10),('ALPHA','2026-10-17 12:00:00',10),('alpha','2026-10-16 23:59:59',10),('alpha','2026-10-18 00:00:00',10),('alpha','2026-10-17 12:00:00',900),('blocked','2026-10-17 12:00:00',10),('inactive','2026-10-17 12:00:00',10),('orphan','2026-10-17 12:00:00',10),('quote\\'user','2026-10-17 12:00:00',10),('alpha','2026-10-17 12:00:00',NULL)");
foreach([[1,''],[2,''],[20,''],[1,"quote'user"],[1,'missing']] as $test)compare_alerts($db,$match[1],'2026-10-17','2026-10-17',15,$test[0],$test[1]);
echo "PASS: date boundaries, case-sensitive counts, duplicate clients, blocked/inactive, threshold, null duration and quoted search\n";
