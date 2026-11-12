<?php
// Local-only JSON bridge. Never expose this file through a web server.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
 $req=json_decode(stream_get_contents(STDIN),true);
 if(!is_array($req)) throw new Exception('Invalid request');
 $conf=file_get_contents(getenv('MKA_QUEUES_SQL_CONFIG')?:'/etc/freeradius/3.0/mods-enabled/sql');
 function configValue($key,$default=null) {global $conf; if(preg_match('/^\s*'.preg_quote($key,'/').'\s*=\s*"([^"]*)"/m',$conf,$m))return $m[1];return $default;}
 $db=new mysqli(configValue('server','localhost'),configValue('login'),configValue('password'),configValue('radius_db'));
 $db->set_charset('utf8mb4');
 function rows($q) {global $db;return $db->query($q)->fetch_all(MYSQLI_ASSOC);}
 function quoteValue($v) {global $db;return $v===null?'NULL':"'".$db->real_escape_string((string)$v)."'";}
 function account($table,$key,$user,$lock='') {
  $columns=$table==='sis_cliente'?'login,tipo,plano,cli_ativado,bloqueado,statusdown,statusturbo,night,status_corte':'username,login,tipo,plano,bloqueado,night';
  return rows('SELECT '.$columns.' FROM '.$table.' WHERE '.$key.'='.quoteValue($user).$lock);
 }
 function guard($user,$plan,$lock='') {
  $c=account('sis_cliente','login',$user,$lock);$a=account('sis_adicional','username',$user,$lock);
  $parent=count($a)===1?account('sis_cliente','login',$a[0]['login'],$lock):[];
  $g=rows('SELECT * FROM radusergroup WHERE username='.quoteValue($user).' ORDER BY groupname,priority,login'.$lock);
  $names=[$plan];foreach($g as $r)$names[]=$r['groupname'];$names=array_unique($names);
  $in=implode(',',array_map('quoteValue',$names));
  return ['primary'=>$c,'additional'=>$a,'parent'=>$parent,'groups'=>$g,
   'replies'=>rows('SELECT * FROM radreply WHERE username='.quoteValue($user).' ORDER BY id'.$lock),
   'group_replies'=>rows('SELECT * FROM radgroupreply WHERE groupname IN ('.$in.') ORDER BY id'.$lock),
   'group_checks'=>rows('SELECT * FROM radgroupcheck WHERE groupname IN ('.$in.') ORDER BY id'.$lock),
   'plan'=>rows('SELECT * FROM sis_plano WHERE nome='.quoteValue($plan).$lock)];
 }
 function writeRows($table,$data) {global $db;foreach($data as $r)$db->query('INSERT INTO '.$table.' (`'.implode('`,`',array_keys($r)).'`) VALUES ('.implode(',',array_map('quoteValue',array_values($r))).')');}
 function saveBackup($data) {
  $root=getenv('MKA_QUEUES_BACKUPS')?:'/opt/mk-auth/backups/codex-queues';
  $dir=$root.'/'.date('Ymd_His').'-auto-'.bin2hex(random_bytes(4));
  $old=umask(0077);if(!mkdir($dir,0700,true))throw new Exception('Cannot create backup');
  $f=$dir.'/database.json';$data=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
  if($data===false||file_put_contents($f,$data)===false||file_get_contents($f)!==$data)throw new Exception('Cannot validate backup');
  umask($old);return $f;
 }
 $action=isset($req['action'])?$req['action']:'snapshot';
 if($action==='snapshot') {
  $out=['nas'=>rows('SELECT nasname,shortname,sshativo,portassh,senha,secret FROM nas'),
   'primary'=>rows('SELECT login,tipo,plano,cli_ativado,bloqueado,statusdown,statusturbo,night,status_corte FROM sis_cliente'),
   'additional'=>rows('SELECT username,login,tipo,plano,bloqueado,night FROM sis_adicional'),
   'plans'=>rows('SELECT * FROM sis_plano'),
   'groups'=>rows('SELECT * FROM radusergroup ORDER BY groupname,priority,login'),
   'replies'=>rows('SELECT * FROM radreply ORDER BY id'),
   'group_replies'=>rows('SELECT * FROM radgroupreply ORDER BY id'),
   'group_checks'=>rows('SELECT * FROM radgroupcheck ORDER BY id')];
 } elseif($action==='guard') {$out=guard($req['user'],$req['plan']);
 } elseif($action==='accounting') {
  $out=rows('SELECT username,acctsessionid,framedipaddress FROM radacct WHERE username='.quoteValue($req['user']).' AND nasipaddress='.quoteValue($req['nas']).' AND acctstoptime IS NULL');
 } elseif($action==='apply') {
  $db->begin_transaction();
  try {
   $u=$req['user'];$p=$req['plan'];$before=guard($u,$p,' FOR UPDATE');
   if($before!==$req['guard'])throw new Exception('Database changed since audit');
   if(count($before['plan'])!==1)throw new Exception('Plan missing');
   $groupBefore=array_values(array_filter($before['group_replies'],function($r)use($p){return $r['groupname']===$p;}));
   $changeGroups=$req['change_groups'];$changeRate=$req['change_rate'];$changeAdditional=$req['change_additional'];
   $owner=count($before['primary'])===1?$u:$before['additional'][0]['login'];
   $afterGroups=$changeGroups?[['username'=>$u,'groupname'=>$p,'priority'=>'1','login'=>$owner]]:$before['groups'];
   $data=['user'=>$u,'plan'=>$p,'before'=>$before,'change_groups'=>$changeGroups,'change_rate'=>$changeRate,'change_additional'=>$changeAdditional];
   $backup=saveBackup($data);
   if($changeRate){$db->query('DELETE FROM radgroupreply WHERE groupname='.quoteValue($p)." AND attribute='Mikrotik-Rate-Limit'");$db->query('INSERT INTO radgroupreply (groupname,attribute,op,value) VALUES ('.quoteValue($p).",'Mikrotik-Rate-Limit','=',".quoteValue($req['rate']).')');}
   if($changeGroups){$db->query('DELETE FROM radusergroup WHERE username='.quoteValue($u));writeRows('radusergroup',$afterGroups);}
   if($changeAdditional)$db->query('UPDATE sis_adicional SET plano='.quoteValue($p).' WHERE username='.quoteValue($u));
   $after=guard($u,$p);
   if($after['groups']!=$afterGroups)throw new Exception('Group verification failed');
   if($changeAdditional && (count($after['additional'])!==1 || $after['additional'][0]['plano']!==$p))throw new Exception('Additional plan verification failed');
   if($changeRate){$rr=array_values(array_filter($after['group_replies'],function($r)use($p){return $r['groupname']===$p;}));if(count($rr)!==1||$rr[0]['value']!==$req['rate'])throw new Exception('Plan rate verification failed');}
   $data['after']=$after;
   $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
   if($json===false||file_put_contents($backup,$json)===false||file_get_contents($backup)!==$json)throw new Exception('Backup update failed');
   $db->commit();$out=['committed'=>true,'backup'=>$backup];
  } catch(Throwable $e){$db->rollback();throw $e;}
 } elseif($action==='rollback') {
  $b=json_decode(file_get_contents($req['file']),true);if(!isset($b['after']))throw new Exception('Incomplete backup');
  $db->begin_transaction();
  try {
   if(guard($b['user'],$b['plan'],' FOR UPDATE')!==$b['after'])throw new Exception('Post-change drift; rollback refused');
   if($b['change_groups']){$db->query('DELETE FROM radusergroup WHERE username='.quoteValue($b['user']));writeRows('radusergroup',$b['before']['groups']);}
   if($b['change_rate']){$db->query('DELETE FROM radgroupreply WHERE groupname='.quoteValue($b['plan'])." AND attribute='Mikrotik-Rate-Limit'");$old=array_values(array_filter($b['before']['group_replies'],function($r)use($b){return $r['groupname']===$b['plan']&&$r['attribute']==='Mikrotik-Rate-Limit';}));writeRows('radgroupreply',$old);}
   if($b['change_additional'])$db->query('UPDATE sis_adicional SET plano='.quoteValue($b['before']['additional'][0]['plano']).' WHERE username='.quoteValue($b['user']));
   $db->commit();$out=['rolled_back'=>true];
  } catch(Throwable $e){$db->rollback();throw $e;}
 } else throw new Exception('Unknown action');
 $encoded=json_encode($out,JSON_UNESCAPED_SLASHES);
 if($encoded===false)throw new Exception('JSON conversion failed');
 echo $encoded;
} catch(Throwable $e) {fwrite(STDERR,"Database bridge failed; check local schema, configuration or state drift.\n");exit(1);}
