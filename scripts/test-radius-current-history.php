<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/radius/radius_lib.php';
require __DIR__.'/../addons/radius/history.php';
require __DIR__.'/../addons/radius/current_state.php';
function radius_test($ok,$name){if(!$ok)throw new RuntimeException('FAIL '.$name);echo 'PASS '.$name."\n";}
$dir=sys_get_temp_dir().'/radius-history-'.bin2hex(random_bytes(8));mkdir($dir,0700);
$log=$dir.'/radius.log';putenv('RADIUS_LOG_FILE='.$log);
try {
    file_put_contents($log,"Tue Oct 13 10:00:00 2026 : Auth: Login OK: [other/secret] (from client test)\n");
    file_put_contents($log.'.1',"Mon Oct 12 09:00:00 2026 : Auth: Login OK: [old/secret] (from client test)\n");
    $r=radius_latest_history('absent');
    radius_test($r['row']===null && $r['range']['files']===2,'absent login retains scanned archive count');
    radius_test($r['range']['from']==='12/10/2026 09:00:00' && $r['range']['to']==='13/10/2026 10:00:00','actual timestamp range across rotations');
    $r=radius_latest_history('old');radius_test(strpos($r['row']['line'],'secret')===false,'historical credentials masked');
    $db=new mysqli(getenv('MYSQL_HOST')?:'127.0.0.1',getenv('MYSQL_USER')?:'root',getenv('MYSQL_PASS')?:'',getenv('MYSQL_DB')?:'mkradius');
    $db->query('CREATE TEMPORARY TABLE radacct(username VARCHAR(64),acctstoptime DATETIME NULL,acctstarttime DATETIME NULL,INDEX username(username))');
    $db->query("INSERT INTO radacct VALUES('connected',NULL,'2026-10-13 10:00:00'),('offline','2026-10-12 10:00:00','2026-10-12 09:00:00')");
    $client=array('name'=>'Fixture','url'=>'');
    radius_test(radius_current_state($db,'connected',$client)['state']==='online','connected without log remains online');
    radius_test(radius_current_state($db,'offline',$client)['state']==='offline','closed session is offline');
    radius_test(radius_current_state($db,'connected',array())['state']==='unknown','unresolved customer never exposes current sessions');
    $broken=new class {function prepare($sql){throw new RuntimeException('unavailable');}};
    radius_test(radius_current_state($broken,'connected',$client)['state']==='unknown','query failure never becomes offline');
    $online=radius_current_state($db,'connected',$client);
    $old=array('type'=>'conectados','label'=>'Conectado','line'=>'Mon Oct 12 09:00:00 2026 : Auth: Login OK: [connected/***]','login'=>'connected');
    $event=radius_current_event($online,array($old));radius_test(strpos($event['line'],'13/10/2026 10:00:00')!==false,'old authorization never substitutes current session');
    $offline=radius_current_state($db,'offline',$client);$event=radius_current_event($offline,array($old));radius_test($event['type']==='desconectados' && strpos($event['line'],'12/10/2026 10:00:00')!==false,'offline shows latest session stop, not Login OK');
    $db->close();
} finally {unlink($log);unlink($log.'.1');rmdir($dir);putenv('RADIUS_LOG_FILE');}
