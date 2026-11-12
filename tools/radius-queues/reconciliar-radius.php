<?php
/* PHP CLI. Default: dry-run. --apply requires --users=JSON_FILE.
 * Uses local FreeRADIUS SQL credentials, never prints passwords.
 * Scope: active, unblocked primary PPPoE accounts without turbo/down;
 * additional accounts with valid parent and one unambiguous legacy-plan match.
 * Does not contact MikroTik, disconnect sessions or create queues.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$opts=getopt('', ['apply','users:','rollback:']);
$conf=file_get_contents('/etc/freeradius/3.0/mods-enabled/sql');
function cfg($key) {global $conf; if(!preg_match('/^\s*'.preg_quote($key,'/').'\s*=\s*"([^"]*)"/m',$conf,$m)) throw new Exception('Missing SQL configuration'); return $m[1];}
$db=new mysqli(cfg('server'),cfg('login'),cfg('password'),cfg('radius_db'));
$db->set_charset('latin1');
function queryRows($sql) {global $db; return $db->query($sql)->fetch_all(MYSQLI_ASSOC);}
function esc($v) {global $db; return $v===null?'NULL':"'".$db->real_escape_string((string)$v)."'";}
function restoreRow($table,$row) {global $db; $cols=array_keys($row);$db->query('INSERT INTO '.$table.' (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_map('esc',array_values($row))).')');}
function sameRows($a,$b) { $a=array_map('serialize',$a);$b=array_map('serialize',$b);sort($a);sort($b);return $a===$b; }
if(isset($opts['rollback'])) {
 $b=json_decode(file_get_contents($opts['rollback']),true);
 if(!is_array($b)||!isset($b['before'],$b['after'])) throw new Exception('Invalid backup');
 $db->begin_transaction();
 try {
  foreach($b['after'] as $user=>$expected) {
   $current=queryRows('SELECT * FROM radusergroup WHERE username='.esc($user).' FOR UPDATE');
   if($current!=$expected) throw new Exception('Post-change group drift: '.$user);
  }
  foreach($b['additional_after'] as $user=>$plan) {
   $r=queryRows('SELECT plano FROM sis_adicional WHERE username='.esc($user).' FOR UPDATE');
   if(count($r)!==1||$r[0]['plano']!==$plan) throw new Exception('Post-change plan drift: '.$user);
  }
  foreach($b['before'] as $user=>$rows) {
   $db->query('DELETE FROM radusergroup WHERE username='.esc($user));
   foreach($rows as $row) restoreRow('radusergroup',$row);
  }
  foreach($b['additional_before'] as $user=>$plan) $db->query('UPDATE sis_adicional SET plano='.esc($plan).' WHERE username='.esc($user));
  $db->commit(); echo "Rollback committed for ".count($b['before'])." users\n";
 } catch(Throwable $e) {$db->rollback();throw $e;}
 exit;
}
$apply=isset($opts['apply']);
if($apply&&!isset($opts['users'])) throw new Exception('Apply requires explicit reviewed scope');
$users=isset($opts['users'])?json_decode(file_get_contents($opts['users']),true):null;
if($users!==null&&(!is_array($users)||!count($users)||count(array_unique($users))!==count($users))) throw new Exception('Invalid scope');
$plans=[];foreach(queryRows("SELECT p.nome,r.value FROM sis_plano p JOIN radgroupreply r ON r.groupname=p.nome AND r.attribute='Mikrotik-Rate-Limit'") as $r) $plans[$r['nome']][]=$r['value'];
function validPlan($name) {global $plans;return isset($plans[$name])&&count(array_unique($plans[$name]))===1&&preg_match('/^[0-9]+[kKmMgG]?\/[0-9]+[kKmMgG]?(?: |$)/',$plans[$name][0]);}
$groups=[];foreach(queryRows('SELECT * FROM radusergroup ORDER BY priority') as $r) $groups[$r['username']][]=$r;
$primary=[];foreach(queryRows('SELECT login,tipo,plano,cli_ativado,bloqueado,statusturbo,statusdown FROM sis_cliente') as $r) $primary[$r['login']]=$r;
$additional=[];foreach(queryRows('SELECT username,login,tipo,plano,bloqueado FROM sis_adicional') as $r) $additional[$r['username']]=$r;
if($users===null) $users=array_keys($groups);
$proposals=[];$skipped=[];
foreach($users as $u) {
 $g=isset($groups[$u])?$groups[$u]:[];
 if(!$g) {$skipped[$u]='no group';continue;}
 if(validPlan($g[0]['groupname'])) {$skipped[$u]='first group already valid';continue;}
 $desired=null;$kind=null;$owner=null;
 if(isset($primary[$u])) {
  $c=$primary[$u];
  if($c['tipo']!=='pppoe'||$c['cli_ativado']!=='s'||$c['bloqueado']!=='nao'||$c['statusturbo']!=='off'||$c['statusdown']!=='off') {$skipped[$u]='primary special state';continue;}
  if(validPlan($c['plano'])) {$desired=$c['plano'];$kind='primary';$owner=$u;}
 } elseif(isset($additional[$u])) {
  $c=$additional[$u];$parent=isset($primary[$c['login']])?$primary[$c['login']]:null;
  if($c['tipo']!=='pppoe'||$c['bloqueado']!=='nao'||!$parent||$parent['cli_ativado']!=='s'||$parent['bloqueado']!=='nao'||$parent['statusturbo']!=='off'||$parent['statusdown']!=='off') {$skipped[$u]='additional special state';continue;}
  if(validPlan($c['plano'])) $desired=$c['plano'];
  // Only a legacy name made solely of speed + MB + FIBRA/FIBER.
  elseif(preg_match('/^(\d+)MB_FIB(?:RA|ER)$/i',$c['plano'],$m)) {
   $matches=[];
   foreach(array_keys($plans) as $name) if(preg_match('/ADICIONAIS_?'.preg_quote($m[1],'/').'MB$/i',$name)&&validPlan($name)) $matches[]=$name;
   if(count($matches)===1) $desired=$matches[0];
  }
  $kind='additional';$owner=$c['login'];
 }
 if(!$desired) {$skipped[$u]='no unambiguous current plan';continue;}
 $proposals[$u]=['kind'=>$kind,'owner'=>$owner,'from'=>$g[0]['groupname'],'to'=>$desired,'rate'=>$plans[$desired][0]];
}
echo json_encode(['mode'=>$apply?'apply':'dry-run','proposals'=>$proposals,'skipped'=>$skipped],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
if(!$apply) exit;
foreach($skipped as $reason) if($reason!=='first group already valid') throw new Exception('Scope changed or unsafe candidate; nothing applied');
if(!$proposals) {echo "Already reconciled; no changes\n";exit;}
$db->begin_transaction();$before=[];$after=[];$addBefore=[];$addAfter=[];
try {
 foreach($proposals as $u=>$p) {
  $now=queryRows('SELECT * FROM radusergroup WHERE username='.esc($u).' ORDER BY priority FOR UPDATE');
  if(!sameRows($now,$groups[$u])) throw new Exception('Group changed during preflight: '.$u);
  $table=$p['kind']==='primary'?'sis_cliente':'sis_adicional';$key=$p['kind']==='primary'?'login':'username';
  $locked=queryRows('SELECT * FROM '.$table.' WHERE '.$key.'='.esc($u).' FOR UPDATE');
  if(count($locked)!==1) throw new Exception('Missing account: '.$u);
  $cached=$p['kind']==='primary'?$primary[$u]:$additional[$u];
  foreach($cached as $k=>$v) if($locked[0][$k]!==$v) throw new Exception('Account changed: '.$u);
  $rate=queryRows("SELECT value FROM radgroupreply WHERE groupname=".esc($p['to'])." AND attribute='Mikrotik-Rate-Limit' FOR UPDATE");
  if(!$rate||count(array_unique(array_column($rate,'value')))!==1||$rate[0]['value']!==$p['rate']) throw new Exception('Rate changed: '.$u);
  if($p['kind']==='additional') {
   $pr=queryRows('SELECT login,plano,cli_ativado,bloqueado,statusturbo,statusdown FROM sis_cliente WHERE login='.esc($p['owner']).' FOR UPDATE');
   if(count($pr)!==1) throw new Exception('Parent missing');
   foreach($pr[0] as $k=>$v) if($primary[$p['owner']][$k]!==$v) throw new Exception('Parent changed');
   $addBefore[$u]=$locked[0]['plano'];$addAfter[$u]=$p['to'];
  }
  $before[$u]=$now;
  $after[$u]=[['username'=>$u,'groupname'=>$p['to'],'priority'=>'1','login'=>$p['owner']]];
 }
 $dir='/opt/mk-auth/backups/codex-queues/'.date('Ymd_His').'-reconcile';
 if(!mkdir($dir,0700,true)) throw new Exception('Cannot create backup');
 $backup=$dir.'/targeted-rollback.json';
 $data=['before'=>$before,'after'=>$after,'additional_before'=>$addBefore,'additional_after'=>$addAfter];
 $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);if($json===false) throw new Exception('Cannot encode backup');
 umask(0077);if(file_put_contents($backup,$json)===false) throw new Exception('Cannot save backup');chmod($backup,0600);
 if(json_decode(file_get_contents($backup),true)!==$data) throw new Exception('Backup validation failed');
 foreach($after as $u=>$r) {
  $db->query('DELETE FROM radusergroup WHERE username='.esc($u));restoreRow('radusergroup',$r[0]);
  if(isset($addAfter[$u])) $db->query('UPDATE sis_adicional SET plano='.esc($addAfter[$u]).' WHERE username='.esc($u));
 }
 foreach($after as $u=>$r) if(queryRows('SELECT * FROM radusergroup WHERE username='.esc($u))!=$r) throw new Exception('Verification failed: '.$u);
 $db->commit();
 echo json_encode(['committed'=>count($after),'additional_plans'=>count($addAfter),'rollback'=>$backup,'sha256'=>hash_file('sha256',$backup)],JSON_PRETTY_PRINT)."\n";
} catch(Throwable $e) {$db->rollback();throw $e;}
