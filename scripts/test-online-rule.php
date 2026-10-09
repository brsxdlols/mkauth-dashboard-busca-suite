<?php
// Temporary tables only; no changes to actual radacct or customer records.
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/online_rule.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('MYSQL_HOST')?:'127.0.0.1',getenv('MYSQL_USER')?:'root',getenv('MYSQL_PASS')?:'vertrigo',getenv('MYSQL_DB')?:'mkradius');
$db->query('CREATE TEMPORARY TABLE fixture_clients (login VARCHAR(64),cli_ativado CHAR(1),grupo VARCHAR(20),ramal VARCHAR(40))');
$db->query('CREATE TEMPORARY TABLE fixture_additional (login VARCHAR(64),username VARCHAR(64))');
$db->query('CREATE TEMPORARY TABLE fixture_radacct (username VARCHAR(64),acctstoptime DATETIME NULL)');
$db->query("INSERT INTO fixture_clients VALUES (' Alice ','s','A','R1'),('ALICE','s','A','R1'),('Carol','s','B','R2'),('inactive','n','A','R1')");
$db->query("INSERT INTO fixture_additional VALUES ('alice',' Bob '),('ALICE','BOB'),('alice','alice'),('Carol','Dave'),('inactive','child'),('missing','orphan')");
$db->query("INSERT INTO fixture_radacct VALUES ('alice',NULL),(' ALICE ',NULL),('bob',NULL),(' BOB ',NULL),('Dave',NULL),('Carol','2026-01-01'),('inactive',NULL),('child',NULL),('orphan',NULL),('unknown',NULL),('',NULL)");
function fixture_sql($sql){return str_replace(array('sis_cliente','sis_adicional','radacct'),array('fixture_clients','fixture_additional','fixture_radacct'),$sql);}
foreach(array(''=>3,"c.grupo='A' AND "=>2,"c.grupo='B' AND "=>1) as $scope=>$expected){
 $actual=$db->query(fixture_sql(mka_online_sql($scope)))->num_rows;
 if($actual!==$expected)throw new RuntimeException("Count mismatch $actual != $expected");
}
$rows=$db->query(fixture_sql(mka_online_ramal_sql()))->fetch_all(MYSQLI_ASSOC);
if(count($rows)!==2 || (int)$rows[0]['total_online']!==2 || (int)$rows[1]['total_online']!==1)throw new RuntimeException('NAS counts mismatch');
$family=$db->query(fixture_sql("SELECT DISTINCT LOWER(TRIM(c.login)) FROM sis_cliente c WHERE c.cli_ativado='s' AND ".mka_online_family_sql()))->num_rows;
if($family!==2)throw new RuntimeException('Additional-only family must appear in search');
echo "OK normalized distinct logins, duplicate sessions/registrations, additional-only online, inactive/orphan exclusion, group scope and NAS counts\n";
