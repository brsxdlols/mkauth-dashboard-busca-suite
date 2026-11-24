<?php
if(PHP_SAPI!=='cli')exit(1);
define('MKA_NATIVE_DISCONNECT_TEST',true);
require __DIR__.'/../addons/shared/native_disconnect_clock.php';
require __DIR__.'/installer-database.php';
function stop_check($ok,$label){if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label.PHP_EOL;}
// This stub prevents the native method from sending any disconnect packet.
class MKAC_SHELL {
    public static $calls=0;public static $race=false;
    public static function __callStatic($name,$args){
        self::$calls++;
        if(self::$race)$GLOBALS['LOADMYSQL']->query("UPDATE radacct SET acctstoptime=DATE_SUB(NOW(),INTERVAL 2 SECOND),acctterminatecause='NAS-Request' WHERE radacctid=1");
        return '';
    }
}
if(isset($argv[1])){
    $p=$argv[1];ob_start();eval('?>'.mka_activity_decoder_source(file_get_contents($p),$p));ob_end_clean();
    eval(mka_native_disconnect_second_stage($mkaActivityDecoded,true));$patched=$GLOBALS['mkaNativeDisconnectBody'];
}else{
    $fixture= <<<'PHP'
class MKAC_FREERADIUS {
 public static function close_fixture($login){global $LOADMYSQL;
  $r=$LOADMYSQL->query("SELECT radacctid,framedipaddress,nasipaddress FROM radacct WHERE username='$login' ORDER BY radacctid DESC LIMIT 1");$row=$r->fetch_assoc();if(!$row)return;
  $id=$row['radacctid'];MKAC_SHELL::stub_disconnect();
  $LOADMYSQL->query("UPDATE radacct
 SET acctstoptime = DATE_ADD(acctstarttime, interval acctsessiontime second), acctterminatecause = 'Lost-Service'
 WHERE radacctid = '{$id}'");
 }
}
PHP;
    $patched=mka_native_disconnect_patch($fixture);
    stop_check(strpos($fixture,'DATE_ADD')!==false&&strpos($patched,'DATE_ADD')===false,'original input preserved; only known formula replaced');
    try{mka_native_disconnect_patch(str_replace('interval acctsessiontime second','interval 1 day',$fixture));throw new LogicException('Unknown query accepted');}catch(RuntimeException $expected){}
}
stop_check(substr_count($patched,'CURRENT_TIMESTAMP()')>=1&&substr_count($patched,'AND acctstoptime IS NULL')===1,'current database clock and stopped-session guard');
eval($patched);
$ref=new ReflectionClass('MKAC_FREERADIUS');$method=null;
foreach($ref->getMethods(ReflectionMethod::IS_STATIC)as $m)if($m->getNumberOfParameters()===1){
 // Native class also has one-argument NAS setting helpers; locate the method
 // containing this exact accounting query from the decoded class source.
 if(preg_match('/function\s+'.preg_quote($m->name,'/').'\s*\([^)]*\)\{(.*?)(?=public static|\$MKACLASSE|$)/s',$patched,$part)&&strpos($part[1],'UPDATE radacct')!==false)$method=$m->name;
}
stop_check($method!==null,'native disconnect method located without invoking unrelated methods');
$LOADMYSQL=mka_installer_database_connect();$db=$LOADMYSQL;
// Connection-local temporary tables shadow the production tables.
$db->query('CREATE TEMPORARY TABLE radacct(radacctid INT PRIMARY KEY,username VARCHAR(64),framedipaddress VARCHAR(40),nasipaddress VARCHAR(40),acctstarttime DATETIME,acctstoptime DATETIME NULL,acctsessiontime INT,acctterminatecause VARCHAR(32)) ENGINE=InnoDB');
$db->query('CREATE TEMPORARY TABLE nas(nasname VARCHAR(40),secret VARCHAR(64)) ENGINE=InnoDB');
$db->query("INSERT INTO nas VALUES('192.0.2.1','fixture-only')");
$db->query("INSERT INTO radacct VALUES(1,'fixture','192.0.2.2','192.0.2.1',NOW()-INTERVAL 1 DAY,NULL,1253135,'')");
$r=$db->query('SELECT TIMESTAMPADD(SECOND,acctsessiontime,acctstarttime)>NOW() future_formula FROM radacct')->fetch_assoc();stop_check((int)$r['future_formula']===1,'old formula reproduces future date');
call_user_func(array('MKAC_FREERADIUS',$method),'fixture');
$r=$db->query('SELECT ABS(TIMESTAMPDIFF(SECOND,acctstoptime,NOW())) age,acctterminatecause FROM radacct WHERE radacctid=1')->fetch_assoc();stop_check((int)$r['age']<5&&$r['acctterminatecause']==='Lost-Service','manual close uses real current time despite inconsistent duration');
$db->query("UPDATE radacct SET acctstoptime=NOW()-INTERVAL 1 HOUR,acctterminatecause='NAS-Request' WHERE radacctid=1");$before=$db->query('SELECT acctstoptime FROM radacct')->fetch_assoc()['acctstoptime'];
call_user_func(array('MKAC_FREERADIUS',$method),'fixture');
$r=$db->query('SELECT acctstoptime,acctterminatecause FROM radacct')->fetch_assoc();stop_check($r['acctstoptime']===$before&&$r['acctterminatecause']==='NAS-Request','existing genuine Accounting-Stop preserved');
$db->query("UPDATE radacct SET acctstoptime=NULL,acctterminatecause='' WHERE radacctid=1");MKAC_SHELL::$race=true;
call_user_func(array('MKAC_FREERADIUS',$method),'fixture');
$r=$db->query('SELECT acctterminatecause FROM radacct')->fetch_assoc();stop_check($r['acctterminatecause']==='NAS-Request','Accounting-Stop arriving during disconnect preserved');
stop_check(MKAC_SHELL::$calls===3,'transport stub only; no real router packet sent');
echo 'PASS temporary tables only; production accounting untouched'.PHP_EOL;
