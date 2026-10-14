<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require __DIR__.'/../shared/search_performance.php';
if (strpos((string)file_get_contents(__DIR__.'/index.php'), "define('MKA_SEARCH_PREEXISTING_DIRECT',true)")!==false) define('MKA_SEARCH_PREEXISTING_DIRECT',true);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function perf_reply($data,$status=200){http_response_code($status);echo json_encode($data);exit;}
$user=(string)($_SESSION['MKA_Usuario'] ?? $_SESSION['MM_Usuario'] ?? '');
if (!permissao('perm_clientes') || $user==='') perf_reply(array('error'=>'Acesso administrativo necessario.'),403);
$st=$conn->prepare('SELECT cli_grupos FROM sis_acesso WHERE login=? LIMIT 1');$st->bind_param('s',$user);$st->execute();$access=$st->get_result()->fetch_assoc();
if (!$access || (trim((string)$access['cli_grupos'])!=='' && !in_array('full_clientes',array_map('trim',explode(',',(string)$access['cli_grupos'])),true))) perf_reply(array('error'=>'Este diagnostico requer acesso a todos os grupos.'),403);
if (stripos(mysqli_get_server_info($conn),'MariaDB')===false) perf_reply(array('error'=>'Benchmark SQL com timeout validado apenas para MariaDB. Nenhuma alteracao aplicada.'),422);
if (!mysqli_query($conn,'SET SESSION max_statement_time=15')) perf_reply(array('error'=>'Nao foi possivel limitar o tempo das consultas.'),422);
if (empty($_SESSION['suite_perf_csrf'])) $_SESSION['suite_perf_csrf']=bin2hex(random_bytes(24));
$input=json_decode(file_get_contents('php://input',false,null,0,10000),true) ?: array();
$action=$_SERVER['REQUEST_METHOD']==='GET' ? 'prepare' : ($input['action'] ?? '');
if ($_SERVER['REQUEST_METHOD']!=='GET' && (!isset($input['csrf']) || !is_string($input['csrf']) || !hash_equals($_SESSION['suite_perf_csrf'],$input['csrf']))) perf_reply(array('error'=>'Sessao expirada. Reabra o diagnostico.'),403);
require_once __DIR__.'/../shared/performance_compatibility.php';
function perf_write_flag($db,$value){$s=$db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('suite_search_optimization',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");$s->bind_param('s',$value);if(!$s->execute())throw new RuntimeException('Nao foi possivel salvar a politica.');}
function perf_runs($plan){$p=mka_perf_dir().'/'.$plan['id'].'.runs';$rows=array();foreach(is_file($p)?file($p,FILE_IGNORE_NEW_LINES):array() as $line){$r=json_decode($line,true);if(is_array($r))$rows[]=$r;}return $rows;}
function perf_median($values){sort($values,SORT_NUMERIC);$n=count($values);return $n%2?$values[(int)floor($n/2)]:($values[$n/2-1]+$values[$n/2])/2;}
function perf_report($plan){
    $groups=array();foreach(perf_runs($plan) as $r){if(!empty($r['failed']))throw new RuntimeException('Uma requisicao medida falhou. Nenhuma otimizacao pode ser confirmada.');$phases=array_column($r['metrics'],'phase');foreach(array('card-online','card-base','card-additional','list-total','list-page') as $required)if(!in_array($required,$phases,true))throw new RuntimeException('Medicao incompleta: faltou uma consulta real da pagina.');if(($r['ordering'] ?? '')!=='c.nome' || (string)($r['page_size'] ?? '')!=='25')throw new RuntimeException('Ordenacao ou paginacao nao correspondem ao benchmark.');$key=$r['rule'].'-'.$r['filter'];$groups[$key][]=$r;}
    $result=array();foreach($groups as $key=>$rows){$phases=array();foreach($rows as $r){foreach($r['metrics'] as $m)$phases[$m['phase']][]=$m['seconds'];}
        $med=array();foreach($phases as $phase=>$values)$med[$phase]=perf_median($values);
        $signatures=array();foreach($rows as $r){foreach($r['metrics'] as $m)if($m['phase']==='list-total')$signatures[]=($m['result_hash'] ?? '').':'.$m['rows'];}
        $result[$key]=array('samples'=>count($rows),'application_median'=>perf_median(array_column($rows,'application_seconds')),'sql_medians'=>$med,'cards'=>$rows[count($rows)-1]['cards'] ?? null,'results_varied'=>count(array_unique($signatures))>1);
    }return $result;
}
function perf_recommended($report,$plan){
    if(empty($plan['compatible']) || !empty($plan['already_direct']))return false;
    $gain=false;foreach(array('on','off') as $f){$b=$report['baseline-'.$f] ?? null;$a=$report['candidate-'.$f] ?? null;if(!$a || !$b || $a['samples']<3 || $b['samples']<3 || $a['application_median']>$b['application_median']*1.10)return false;if($b['application_median']>0.5 && $a['application_median']<$b['application_median']*0.85)$gain=true;}return $gain;
}
try {
 if(!is_dir(mka_perf_dir()) || !is_writable(mka_perf_dir()))throw new RuntimeException('Diretorio privado de diagnostico indisponivel. Reinstale o pacote.');
 if($action==='prepare'){
    $old=mka_perf_plan();$lease=$old && !empty($old['pending']) ? json_decode($old['pending'],true) : null;if($lease && ($lease['until'] ?? 0)>time())perf_reply(array('ok'=>true,'plan'=>$old,'csrf'=>$_SESSION['suite_perf_csrf']));
    $compat=perf_compatibility($conn);$id=bin2hex(random_bytes(24));
    $p=array_merge($compat,array('id'=>$id,'expires'=>time()+1800,'previous'=>mka_perf_flag($conn),'already_direct'=>mka_perf_set_enabled($conn),'baseline_direct'=>mka_perf_mode($conn)==='direct','source_hash'=>hash_file('sha256',__DIR__.'/index.php'),'user_hash'=>hash('sha256',$user)));
    $p['tables']=array();$r=perf_query($conn,"SELECT TABLE_NAME,ENGINE,TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('radacct','sis_cliente','sis_adicional')");while($row=mysqli_fetch_assoc($r))$p['tables'][]=$row;
    $p['indexes']=array();$r=perf_query($conn,"SELECT TABLE_NAME,INDEX_NAME,COLUMN_NAME,SEQ_IN_INDEX FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND ((TABLE_NAME='radacct' AND COLUMN_NAME IN ('username','acctstoptime')) OR (TABLE_NAME='sis_cliente' AND COLUMN_NAME IN ('login','cli_ativado')) OR (TABLE_NAME='sis_adicional' AND COLUMN_NAME IN ('login','username'))) ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX");while($row=mysqli_fetch_assoc($r))$p['indexes'][]=$row;
    $planJson=json_encode($p);
    if ($planJson===false || @file_put_contents(mka_perf_dir().'/'.$id.'.json',$planJson,LOCK_EX)!==strlen($planJson)) throw new RuntimeException('Nao foi possivel gravar o diagnostico. Verifique permissoes e AppArmor; execute o instalador atualizado.');
    $_SESSION['suite_perf_plan']=$id;
    perf_reply(array('ok'=>true,'plan'=>$p,'csrf'=>$_SESSION['suite_perf_csrf'],'php'=>PHP_VERSION,'database'=>mysqli_get_server_info($conn),'mk_auth'=>defined('VERSAO2UPDATE')?(string)VERSAO2UPDATE:'nao identificado'));
 }
 $p=mka_perf_plan();if(!$p)throw new RuntimeException('Diagnostico expirado. Inicie novamente.');
 if(($p['user_hash'] ?? '')!==hash('sha256',$user) || $p['source_hash']!==hash_file('sha256',__DIR__.'/index.php'))throw new RuntimeException('Usuario ou arquivos alterados durante a medicao. Inicie novamente.');
 if($action==='report'){$report=perf_report($p);perf_reply(array('ok'=>true,'report'=>$report,'recommended'=>perf_recommended($report,$p)));}
 $lock=fopen(mka_perf_dir().'/optimization.lock','c');if(!$lock || !flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Outra otimizacao esta em andamento.');
 if($action==='apply'){
    if(!perf_compatibility($conn)['compatible'])throw new RuntimeException('A compatibilidade mudou. Aplicacao bloqueada.');
    if($p['already_direct'])throw new RuntimeException('Esta instalacao ja utiliza a politica otimizada.');
    $report=perf_report($p);$improved=false;
    foreach(array('on','off') as $filter){$b=$report['baseline-'.$filter] ?? null;$a=$report['candidate-'.$filter] ?? null;if(!$b || !$a || $b['samples']<3 || $a['samples']<3)throw new RuntimeException('Faltam tres medicoes de cada filtro, antes e candidato.');if($a['application_median']>$b['application_median']*1.10)throw new RuntimeException('Candidato apresentou regressao. Aplicacao bloqueada.');if($b['application_median']>0.5 && $a['application_median']<$b['application_median']*0.85)$improved=true;}
    if(!$improved)throw new RuntimeException('Nao foi comprovada uma melhora relevante. Nenhuma alteracao necessaria.');
    if(mka_perf_flag($conn)!==$p['previous'])throw new RuntimeException('A politica foi alterada por outra operacao.');
    // A short lease automatically returns requests to legacy mode if the browser closes before validation.
    $flag=json_encode(array('mode'=>'pending','until'=>time()+300,'version'=>mka_perf_version(),'schema'=>mka_perf_schema($conn)));
    $p['pending']=$flag;$p['backup']=mka_perf_dir().'/'.$p['id'].'.backup.json';
    if(file_put_contents($p['backup'],json_encode(array('previous'=>$p['previous'],'source_hash'=>$p['source_hash'],'report'=>$report)),LOCK_EX)===false)throw new RuntimeException('Backup da politica falhou.');
    perf_write_flag($conn,$flag);
 }elseif($action==='rollback'){
    if(!empty($p['pending']) && mka_perf_flag($conn)===$p['pending'])perf_write_flag($conn,$p['previous']);unset($p['pending']);
 }elseif($action==='confirm'){
    if(empty($p['pending']) || mka_perf_flag($conn)!==$p['pending'] || mka_perf_mode($conn)!=='direct')throw new RuntimeException('Aplicacao pendente expirou ou foi alterada.');
    $report=perf_report($p);$safe=perf_compatibility($conn)['compatible'];
    foreach(array('on','off') as $filter){$a=$report['applied-'.$filter] ?? null;$b=$report['baseline-'.$filter] ?? null;$safe=$safe && $a && $b && $a['samples']>=3 && $a['application_median']<=$b['application_median']*1.10;}
    if(!$safe){perf_write_flag($conn,$p['previous']);unset($p['pending']);file_put_contents(mka_perf_dir().'/'.$p['id'].'.json',json_encode($p),LOCK_EX);throw new RuntimeException('Validacao posterior falhou. Politica anterior restaurada.');}
    perf_write_flag($conn,json_encode(array('mode'=>'direct','version'=>mka_perf_version(),'schema'=>mka_perf_schema($conn))));unset($p['pending']);$p['confirmed']=true;
 }else throw new RuntimeException('Acao desconhecida.');
 file_put_contents(mka_perf_dir().'/'.$p['id'].'.json',json_encode($p),LOCK_EX);
 perf_reply(array('ok'=>true,'plan'=>$p,'report'=>perf_report($p)));
}catch(Throwable $e){perf_reply(array('error'=>$e->getMessage()),422);}
