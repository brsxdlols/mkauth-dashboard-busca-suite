<?php
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli('127.0.0.1','root',getenv('MK_TEST_DB_PASSWORD'),'mkradius');require $argv[1];
function check_connection($condition,$message){if(!$condition)throw new RuntimeException($message);}
$db->query('CREATE TEMPORARY TABLE sis_cliente(id INT PRIMARY KEY,login VARCHAR(64),ip VARCHAR(64) NULL,mac VARCHAR(64) NULL,autoip VARCHAR(8),automac VARCHAR(8),grupo VARCHAR(40)) ENGINE=InnoDB');
$db->query('CREATE TEMPORARY TABLE suite_connection_operations(id BIGINT AUTO_INCREMENT PRIMARY KEY,operator VARCHAR(64),request_key VARCHAR(96),selection VARCHAR(16),client_login VARCHAR(64) NULL,options_json TEXT,changed_count INT,UNIQUE KEY once_only(operator,request_key)) ENGINE=InnoDB');
$db->query('CREATE TEMPORARY TABLE suite_connection_operation_items(operation_id BIGINT,client_id INT,login VARCHAR(64),previous_ip VARCHAR(64) NULL,previous_mac VARCHAR(64) NULL,previous_autoip VARCHAR(8),previous_automac VARCHAR(8),PRIMARY KEY(operation_id,client_id)) ENGINE=InnoDB');
$db->query("INSERT INTO sis_cliente VALUES(1,'first','100.64.1.1','AA:BB','sim','sim','allowed'),(2,'second',NULL,'CC:DD','sim','nao','allowed'),(3,'restricted','100.64.1.3','EE:FF','sim','sim','restricted')");
$scope="c.grupo='allowed'";$options=mka_connection_settings_options(array('clear'=>'ip'));$rows=mka_connection_settings_rows($db,$scope,'first');$preview=array('key'=>'fixture-connection-operation-1','login'=>'first','options'=>$options,'snapshot'=>mka_connection_settings_snapshot($rows));
$result=mka_connection_settings_execute($db,'operator',$scope,$preview);check_connection($result['changed']===1,'Clear IP only');$row=$db->query('SELECT * FROM sis_cliente WHERE id=1')->fetch_assoc();check_connection($row['ip']===null&&$row['mac']==='AA:BB'&&$row['autoip']==='sim','Unselected fields preserved');check_connection($db->query('SELECT previous_ip FROM suite_connection_operation_items')->fetch_row()[0]==='100.64.1.1','Previous values saved');
check_connection(mka_connection_settings_execute($db,'operator',$scope,$preview)===$result,'Operation retry is idempotent');
$rows=mka_connection_settings_rows($db,$scope);$preview=array('key'=>'fixture-connection-operation-2','login'=>null,'options'=>mka_connection_settings_options(array('clear'=>'both','autoip'=>'nao','automac'=>'nao')),'snapshot'=>mka_connection_settings_snapshot($rows));
$db->query("UPDATE sis_cliente SET mac='CHANGED' WHERE id=2");try{mka_connection_settings_execute($db,'operator',$scope,$preview);throw new RuntimeException('Stale preview accepted');}catch(UnexpectedValueException $e){}
check_connection($db->query('SELECT autoip FROM sis_cliente WHERE id=2')->fetch_row()[0]==='sim','Stale preview does not update');$preview['snapshot']=mka_connection_settings_snapshot(mka_connection_settings_rows($db,$scope));$result=mka_connection_settings_execute($db,'operator',$scope,$preview);check_connection($result['changed']===2,'Scoped bulk update');
$rows=$db->query('SELECT * FROM sis_cliente ORDER BY id')->fetch_all(MYSQLI_ASSOC);check_connection($rows[0]['ip']===null&&$rows[0]['mac']===null&&$rows[0]['autoip']==='nao'&&$rows[0]['automac']==='nao','Selected options applied');check_connection($rows[2]['ip']==='100.64.1.3'&&$rows[2]['autoip']==='sim','Restricted group preserved');
$db->query("UPDATE sis_cliente SET ip='100.64.2.1' WHERE id=1");check_connection(mka_connection_settings_rows($db,$scope,'first')[0]['ip']==='100.64.2.1','Future manual fixation is allowed');
try{mka_connection_settings_options(array('autoip'=>"nao'; DROP TABLE sis_cliente"));throw new RuntimeException('Unsafe option accepted');}catch(InvalidArgumentException $e){}
echo "Connection settings: preview conflict detection, IP-only preservation, scoped bulk update, audit, idempotency and future manual fixation OK\n";

$db->query("ALTER TABLE sis_cliente ADD simultaneo VARCHAR(3) DEFAULT 'nao'");
$db->query('CREATE TEMPORARY TABLE sis_adicional(id INT PRIMARY KEY,login VARCHAR(64),username VARCHAR(64),simultaneo VARCHAR(3)) ENGINE=InnoDB');
$db->query("INSERT INTO sis_adicional VALUES(1,'first','extra1','nao'),(2,'restricted','extra2','nao')");
$db->query('CREATE TEMPORARY TABLE suite_simultaneous_operation_items(operation_id BIGINT UNSIGNED,record_type VARCHAR(12),record_id INT,login VARCHAR(64),previous_value VARCHAR(3),PRIMARY KEY(operation_id,record_type,record_id)) ENGINE=InnoDB');
$preview=array('key'=>'simultaneous-fixture-1','login'=>null,'options'=>array('simultaneous'=>'sim'),'snapshot'=>mka_connection_settings_snapshot(mka_simultaneous_rows($db,$scope)));
$result=mka_simultaneous_execute($db,'operator',$scope,$preview);check_connection($result['changed']===3,'Both principals and additional included');
check_connection(mka_simultaneous_execute($db,'operator',$scope,$preview)===$result,'Simultaneous retry idempotent');
check_connection($db->query("SELECT simultaneo FROM sis_adicional WHERE id=1")->fetch_row()[0]==='sim','Additional updated');
check_connection($db->query("SELECT simultaneo FROM sis_adicional WHERE id=2")->fetch_row()[0]==='nao','Restricted additional preserved');
check_connection($db->query("SELECT simultaneo FROM sis_cliente WHERE id=3")->fetch_row()[0]==='nao','Restricted principal preserved');
check_connection($db->query('SELECT COUNT(*) FROM suite_simultaneous_operation_items')->fetch_row()[0]==3,'Previous simultaneous values audited');
check_connection($db->query('SELECT ip FROM sis_cliente WHERE id=1')->fetch_row()[0]==='100.64.2.1','IP unchanged by simultaneous');
$preview=array('key'=>'simultaneous-fixture-2','login'=>'first','options'=>array('simultaneous'=>'nao'),'snapshot'=>mka_connection_settings_snapshot(mka_simultaneous_rows($db,$scope,'first')));
$db->query("UPDATE sis_adicional SET simultaneo='nao' WHERE id=1");try{mka_simultaneous_execute($db,'operator',$scope,$preview);throw new RuntimeException('Stale simultaneous accepted');}catch(UnexpectedValueException $e){}
echo "Simultaneous: scoped principals/additionals, audit, IP preservation, stale preview and retry OK\n";
