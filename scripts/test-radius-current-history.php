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
    $rows=array();foreach(array('10:05:00','10:04:00','10:03:00','10:02:00','10:01:00','10:00:05') as $time)$rows[]=array('type'=>$time==='10:00:05'?'conectados':'erros','line'=>'Tue Oct 13 '.$time.' 2026 : Auth: Login incorrect: [connected/***]','login'=>'connected');
    $events=radius_monitor_events($db,$online,$rows);radius_test(count($events)===5 && $events[0]['type']==='erros' && $events[0]['event_time']>$events[4]['event_time'],'five newest mixed events sorted descending');
    $events=radius_monitor_events($db,$online,array($rows[5]));radius_test(count($events)===1 && $events[0]['source']==='radius','authorization and session start deduplicated');
    $events=radius_monitor_events($db,$offline,array());radius_test(count($events)===2 && $events[0]['type']==='desconectados' && $events[1]['type']==='conectados','accounting stop and start remain separate events');
    $r=radius_latest_history('other',5);radius_test(count($r['rows'])===1,'bounded multiple-event archive lookup');
    $db->close();
} finally {unlink($log);unlink($log.'.1');rmdir($dir);putenv('RADIUS_LOG_FILE');}
