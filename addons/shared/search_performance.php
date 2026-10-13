<?php
require_once __DIR__.'/online_rule.php';
function mka_perf_direct_sql() {
    return "(EXISTS (SELECT 1 FROM radacct r WHERE r.acctstoptime IS NULL AND r.username=c.login) OR EXISTS (SELECT 1 FROM sis_adicional a INNER JOIN radacct r ON r.username=a.username AND r.acctstoptime IS NULL WHERE a.login=c.login))";
}
// UNION forces a reusable set instead of scanning open sessions for each customer.
function mka_perf_set_sql() {
    return "COALESCE(c.login IN (SELECT online_family.login FROM (SELECT r.username AS login FROM radacct r WHERE r.acctstoptime IS NULL AND r.username IS NOT NULL UNION SELECT a.login FROM sis_adicional a JOIN radacct r ON r.username=a.username AND r.acctstoptime IS NULL WHERE a.login IS NOT NULL) online_family),0)";
}
function mka_perf_preexisting_direct() { return defined('MKA_SEARCH_PREEXISTING_DIRECT') && MKA_SEARCH_PREEXISTING_DIRECT; }
function mka_perf_set_enabled($db) {
    $flag=json_decode(mka_perf_flag($db),true);
    return is_array($flag) && ($flag['version'] ?? '')===mka_perf_version() && in_array($flag['mode'] ?? '',array('direct','pending'),true) && mka_perf_mode($db)==='direct';
}
function mka_perf_version() { return hash('sha256',hash_file('sha256',__FILE__).hash_file('sha256',__DIR__.'/online_rule.php').hash_file('sha256',__DIR__.'/../busca_inteligente/index.php')); }
function mka_perf_schema($db) {
    $q=mysqli_query($db,"SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND ((TABLE_NAME='sis_cliente' AND COLUMN_NAME='login') OR (TABLE_NAME='sis_adicional' AND COLUMN_NAME IN ('login','username')) OR (TABLE_NAME='radacct' AND COLUMN_NAME='username')) ORDER BY TABLE_NAME,COLUMN_NAME");
    if(!$q)return ''; $rows=array();while($r=mysqli_fetch_assoc($q))$rows[]=$r;
    return count($rows)===4 ? hash('sha256',mysqli_get_server_info($db).json_encode($rows)) : '';
}
function mka_perf_flag($db) {
    $q=mysqli_query($db,"SELECT valor FROM sis_opcao WHERE nome='suite_search_optimization' LIMIT 1");
    $r=$q ? mysqli_fetch_assoc($q) : null; return $r ? (string)$r['valor'] : '';
}
function mka_perf_mode($db) {
    $flag=json_decode(mka_perf_flag($db),true);
    if (!is_array($flag) || ($flag['version'] ?? '')!==mka_perf_version()) return defined('MKA_SEARCH_PREEXISTING_DIRECT') && MKA_SEARCH_PREEXISTING_DIRECT ? 'direct' : 'legacy';
    if (($flag['schema'] ?? '')!==mka_perf_schema($db)) return 'legacy';
    // Future dirty logins must never silently change the normalized classification.
    $guard=mysqli_query($db,"SELECT EXISTS(SELECT 1 FROM radacct WHERE acctstoptime IS NULL AND BINARY username<>BINARY TRIM(username) LIMIT 1) OR EXISTS(SELECT 1 FROM sis_cliente WHERE cli_ativado='s' AND BINARY login<>BINARY TRIM(login) LIMIT 1) OR EXISTS(SELECT 1 FROM sis_adicional WHERE BINARY login<>BINARY TRIM(login) OR BINARY username<>BINARY TRIM(username) LIMIT 1) OR EXISTS(SELECT 1 FROM sis_cliente c JOIN radacct r ON r.username=c.login AND r.acctstoptime IS NULL WHERE c.cli_ativado='s' AND c.login='' LIMIT 1) AS incompatible");
    $row=$guard ? mysqli_fetch_assoc($guard) : null;if(!$row || (!mka_perf_preexisting_direct() && !empty($row['incompatible'])))return 'legacy';
    if (($flag['mode'] ?? '')==='direct') return 'direct';
    if (($flag['mode'] ?? '')==='pending' && ($flag['until'] ?? 0)>time()) return 'direct';
    return 'legacy';
}
function mka_perf_dir() { return '/var/lib/mkauth-suite-performance'; }
function mka_perf_plan() {
    $id=$_SESSION['suite_perf_plan'] ?? '';
    if (!preg_match('/^[a-f0-9]{48}$/D',$id)) return null;
    $file=mka_perf_dir().'/'.$id.'.json';
    $p=is_file($file) ? json_decode((string)file_get_contents($file),true) : null;
    return is_array($p) && ($p['expires'] ?? 0)>time() ? $p : null;
}
function mka_perf_start($db,$allowed) {
    if (!in_array($_GET['busca'] ?? '',array('on','off'),true) || (string)($_GET['pagina'] ?? '')!=='1' || (string)($_GET['num_registros'] ?? '')!=='25' || ($_GET['organizar'] ?? '')!=='c.nome') return;
    $nonce=$_GET['_mka_perf'] ?? '';
    if (!$allowed || !function_exists('permissao') || !permissao('perm_totais') || !is_string($nonce) || !hash_equals((string)($_SESSION['suite_perf_plan'] ?? ''),$nonce)) return;
    $p=mka_perf_plan(); if (!$p) return;
    $rule=$_GET['_mka_rule'] ?? 'baseline';
    if (!in_array($rule,array('baseline','candidate','applied'),true)) return;
    if ($rule==='candidate' && empty($p['compatible'])) return;
    if (stripos(mysqli_get_server_info($db),'MariaDB')===false) return;
    if (!mysqli_query($db,'SET SESSION max_statement_time=15')) return;
    if (function_exists('set_time_limit')) set_time_limit(45);
    $GLOBALS['mka_perf']=array('started'=>(float)($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),'id'=>$nonce,'rule'=>$rule,'baseline_mode'=>!empty($p['already_direct']) || !empty($p['baseline_direct'])?'direct':'legacy','baseline_set'=>!empty($p['already_direct']),'metrics'=>array(),'failed'=>false);
    if (!headers_sent()) header('Cache-Control: no-store');
}
function mka_search_online_family_sql($db) {
    $filter=strtolower(trim((string)($_GET['busca'] ?? '')));
    if (!in_array($filter,array('on','online','off','offline'),true) && !(defined('MKA_SEARCH_PREEXISTING_DIRECT') && MKA_SEARCH_PREEXISTING_DIRECT)) return mka_online_family_sql();
    $p=$GLOBALS['mka_perf'] ?? null;
    $mode=$p ? ($p['rule']==='candidate' ? 'direct' : ($p['rule']==='baseline' ? $p['baseline_mode'] : mka_perf_mode($db))) : mka_perf_mode($db);
    if ($mode!=='direct') return mka_online_family_sql();
    if ($p && $p['rule']==='baseline') return !empty($p['baseline_set']) ? mka_perf_set_sql() : mka_perf_direct_sql();
    return ($p && $p['rule']==='candidate') || mka_perf_set_enabled($db) ? mka_perf_set_sql() : mka_perf_direct_sql();
}
function mka_perf_query($db,$sql,$phase) {
    if (!isset($GLOBALS['mka_perf'])) return mysqli_query($db,$sql);
    $start=microtime(true);
    try { $result=mysqli_query($db,$sql); } catch (Throwable $e) { $result=false; }
    $record=array('phase'=>$phase,'seconds'=>microtime(true)-$start,'rows'=>$result instanceof mysqli_result ? mysqli_num_rows($result) : null,'ok'=>$result!==false);
    if (!$result) $GLOBALS['mka_perf']['failed']=true;
    if ($phase==='list-total' && $result instanceof mysqli_result) {
        $ids=array();while($row=mysqli_fetch_assoc($result)) $ids[]=(string)($row['id'] ?? $row['uuid_cliente'] ?? '');
        sort($ids,SORT_STRING);$record['result_hash']=hash('sha256',json_encode($ids));if(mysqli_num_rows($result)>0)mysqli_data_seek($result,0);
    }
    $GLOBALS['mka_perf']['metrics'][]=$record;return $result;
}
function mka_perf_cards($online,$offline) {
    if (isset($GLOBALS['mka_perf'])) $GLOBALS['mka_perf']['cards']=array('online'=>$online,'offline'=>$offline,'offline_derived'=>true);
}
function mka_perf_emit() {
    if (!isset($GLOBALS['mka_perf'])) return;
    $p=$GLOBALS['mka_perf'];unset($p['started']);$p['application_seconds']=microtime(true)-$GLOBALS['mka_perf']['started'];
    $p['filter']=isset($_GET['busca']) && $_GET['busca']==='off' ? 'off' : 'on';
    $p['ordering']=$_GET['organizar'] ?? 'c.nome';$p['page_size']=$_GET['num_registros'] ?? '25';
    $p['rule_mode']=$GLOBALS['mka_perf']['rule']==='candidate'?'direct':($GLOBALS['mka_perf']['rule']==='baseline'?'legacy':'applied');
    $line=json_encode($p);$file=mka_perf_dir().'/'.$p['id'].'.runs';
    if ($line===false || file_put_contents($file,$line."\n",FILE_APPEND|LOCK_EX)===false) $p['failed']=true;
    echo '<script type="application/json" id="mka-perf-metrics">'.json_encode($p,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
}
