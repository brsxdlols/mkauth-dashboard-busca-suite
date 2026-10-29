<?php
// Temporary tables only: does not call a router or alter production accounting.
if(PHP_SAPI!=='cli')exit(1);
$source=file_get_contents($argv[1]);
if(!preg_match('/prepare\("(SELECT radacctid FROM radacct.*?)"\)/s',$source,$match))throw new RuntimeException('Session lookup missing');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root',getenv('MK_TEST_DB_PASSWORD')?:'vertrigo','mkradius');
$db->query('CREATE TEMPORARY TABLE radacct(radacctid INT,username VARCHAR(64),nasipaddress VARCHAR(40),acctsessionid VARCHAR(64),acctstoptime DATETIME NULL,acctstarttime DATETIME,acctupdatetime DATETIME) ENGINE=InnoDB');
$db->query("INSERT INTO radacct VALUES(90,'user','old-nas','abc',NULL,NOW(),NOW()),(10,'user','current-nas','0xabc',NULL,NOW(),NOW()),(91,'user','current-nas','different',NULL,NOW(),NOW()),(92,'user','current-nas','abc',NOW(),NOW(),NOW()),(93,'user','current-nas','abc',NULL,NOW()-INTERVAL 1 DAY,NOW()-INTERVAL 1 DAY),(99,'migrated','old-nas','def',NULL,NOW(),NOW())");
$q=$db->prepare($match[1]);$login='user';$nas='current-nas';$session='abc';$q->bind_param('sss',$login,$nas,$session);$q->execute();$rows=$q->get_result()->fetch_all(MYSQLI_ASSOC);
if(count($rows)!==1||(int)$rows[0]['radacctid']!==10)throw new RuntimeException('Wrong NAS/session, stopped or stale row accepted');
$login='migrated';$session='def';$q->execute();if($q->get_result()->num_rows!==0)throw new RuntimeException('Old NAS falsely accepted as current online');
echo "PASS exact NAS/session, 0x normalization, stopped/stale exclusion and migrated NAS\n";
