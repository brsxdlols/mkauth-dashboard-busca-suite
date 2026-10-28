<?php
// Installer-only SQL optimization. Never manufactures a browser session.
if (PHP_SAPI !== 'cli') exit(1);
$admin = rtrim($argv[1] ?? '/opt/mk-auth/admin', '/');
$backup = $argv[2] ?? '';
if (!$backup || !is_dir($backup)) {fwrite(STDERR,"Backup indisponivel.\n");exit(1);}
require $admin.'/addons/shared/search_performance.php';
require $admin.'/addons/shared/performance_compatibility.php';
require_once $admin.'/addons/shared/client_update_audit.php';
if (strpos(file_get_contents($admin.'/addons/busca_inteligente/index.php'), "define('MKA_SEARCH_PREEXISTING_DIRECT',true)")!==false) define('MKA_SEARCH_PREEXISTING_DIRECT',true);
function install_perf_median($v) {sort($v,SORT_NUMERIC);return $v[(int)floor(count($v)/2)];}
function install_perf_flag($db,$flag) {
    $s=$db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('suite_search_optimization',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
    $s->bind_param('s',$flag);if(!$s->execute())throw new RuntimeException('Falha ao salvar politica.');
}
function install_perf_measure($db,$predicate,$allowBaselineTimeout=false) {
    $report=array();
    foreach(array('on','off') as $filter) {
        $query="SELECT c.*,cua.usuario AS last_update_user,cua.detalhes AS last_update_details FROM sis_cliente c LEFT JOIN sis_adicional c2 ON c.login=c2.login LEFT JOIN dashboard_am_client_update_audit cua ON c.uuid_cliente=cua.uuid_cliente AND c.last_update<>cua.previous_last_update AND c.last_update BETWEEN DATE_SUB(cua.captured_at,INTERVAL 5 SECOND) AND DATE_ADD(cua.captured_at,INTERVAL 5 MINUTE) WHERE c.cli_ativado='s' AND ".($filter==='off'?'NOT ':'')."($predicate) GROUP BY c.login ORDER BY c.nome";
        foreach(array('total'=>'','page'=>' LIMIT 0,25') as $phase=>$limit) {
            $times=array();
            for($i=0;$i<3;$i++) {
                $t=microtime(true);
                try {$r=perf_query($db,$query.$limit);$times[]=microtime(true)-$t;$r->free();}
                catch(Throwable $e) {
                    if(!$allowBaselineTimeout || (int)$db->errno!==1969)throw $e;
                    $times[]=microtime(true)-$t;$report[$filter][$phase.'_lower_bound']=true;break;
                }
                usleep(100000);
            }
            $report[$filter][$phase]=install_perf_median($times);
        }
        $report[$filter]['sum']=$report[$filter]['total']+$report[$filter]['page'];
    }
    return $report;
}
$report=array('measurement'=>'SQL only: full client projection and first 25 rows; excludes HTTP, assets and browser rendering');
$changed=false;$db=null;$previous='';
try {
    require_once __DIR__.'/installer-database.php';
    $db=mka_installer_database_connect();
    // First installs have not visited the Search page that normally creates this table.
    if(!mka_client_audit_ensure_table($db))throw new RuntimeException('Tabela auxiliar de auditoria indisponivel.');
    if(stripos(mysqli_get_server_info($db),'MariaDB')===false)throw new RuntimeException('MariaDB necessario para timeout validado.');
    perf_query($db,'SET SESSION max_statement_time=15');
    $lock=fopen(mka_perf_dir().'/optimization.lock','c');
    if(!$lock || !flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Outra otimizacao esta em andamento.');
    $report['compatibility']=perf_compatibility($db);
    if(!$report['compatibility']['compatible'])throw new RuntimeException('Equivalencia ou compatibilidade nao comprovada; politica preservada.');
    $previous=mka_perf_flag($db);
    $baseline=mka_perf_set_enabled($db)?mka_perf_set_sql():(mka_perf_mode($db)==='direct'?mka_perf_direct_sql():mka_online_family_sql());
    $candidate=mka_perf_set_sql();
    $report['before']=install_perf_measure($db,$baseline,true);
    $report['candidate']=install_perf_measure($db,$candidate);
    $gain=false;
    foreach(array('on','off') as $f){$b=$report['before'][$f]['sum'];$a=$report['candidate'][$f]['sum'];if($a>$b*1.10){$report['status']='unchanged';$report['reason']='Configuracao atual mantida: a alternativa testada ficou mais lenta.';echo '[desempenho] '.$report['reason'].PHP_EOL;file_put_contents($backup.'/performance-install.json',json_encode($report,JSON_PRETTY_PRINT),LOCK_EX);echo '[desempenho] Relatorio: '.$backup."/performance-install.json\n";exit(0);}if($b>0.5 && $a<$b*0.85)$gain=true;}
    if(($argv[3] ?? '')==='--dry-run') {$report['status']='dry_run';echo "[desempenho] Simulacao concluida; nenhuma politica alterada.\n";}
    elseif(!$gain) {$report['status']='unchanged';echo "[desempenho] Nenhum ganho relevante; politica preservada.\n";}
    else {
        if(!perf_compatibility($db)['compatible'] || mka_perf_flag($db)!==$previous)throw new RuntimeException('Ambiente alterado durante analise.');
        $policy=json_encode(array('previous'=>$previous,'source_hash'=>hash_file('sha256',$admin.'/addons/busca_inteligente/index.php')));
        if(file_put_contents($backup.'/performance-policy-backup.json',$policy,LOCK_EX)===false)throw new RuntimeException('Backup da politica falhou.');
        chmod($backup.'/performance-policy-backup.json',0600);
        $flag=json_encode(array('mode'=>'pending','until'=>time()+300,'version'=>mka_perf_version(),'schema'=>mka_perf_schema($db)));
        install_perf_flag($db,$flag);$changed=true;
        if(!mka_perf_set_enabled($db))throw new RuntimeException('Politica provisoria nao ativada.');
        $_GET['busca']='on';$report['after']=install_perf_measure($db,mka_search_online_family_sql($db));
        foreach(array('on','off') as $f)if($report['after'][$f]['sum']>$report['before'][$f]['sum']*1.10)throw new RuntimeException('Validacao posterior apresentou piora.');
        if(!perf_compatibility($db)['compatible'] || mka_perf_flag($db)!==$flag)throw new RuntimeException('Politica ou classificacao mudou durante validacao.');
        install_perf_flag($db,json_encode(array('mode'=>'direct','version'=>mka_perf_version(),'schema'=>mka_perf_schema($db))));$changed=false;$report['status']='optimized';
        foreach(array('on','off') as $f)printf("[desempenho] %s SQL: %s%.3f s antes -> %.3f s depois\n",$f,(!empty($report['before'][$f]['total_lower_bound']) || !empty($report['before'][$f]['page_lower_bound'])) ? '>= ' : '',$report['before'][$f]['sum'],$report['after'][$f]['sum']);
    }
} catch(Throwable $e) {
    if($changed && $db) {install_perf_flag($db,$previous);$report['rollback']=true;}
    $report['status']='not_applied';$report['reason']=$e->getMessage();
    if($db)$report['database_error_code']=(int)$db->errno;echo '[aviso] '.$e->getMessage()."\n";
}
file_put_contents($backup.'/performance-install.json',json_encode($report,JSON_PRETTY_PRINT),LOCK_EX);
echo '[desempenho] Relatorio: '.$backup."/performance-install.json\n";
