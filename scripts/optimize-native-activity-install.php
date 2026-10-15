<?php
if (PHP_SAPI!=='cli') exit(1);
$admin=rtrim($argv[1]??'/opt/mk-auth/admin','/');
$backup=$argv[2]??'';
$target=$admin.'/logs_ajax.hhvm';
$helper=$admin.'/addons/shared/native_activity_performance.php';
$report=array('status'=>'not_applied');
try {
    if (!$backup || !is_dir($backup) || !is_file($target)) throw new RuntimeException('Native activity file or backup unavailable.');
    require $helper;
    $source=file_get_contents($target);$original=$target;
    if (strpos($source,'mka-suite-native-activity-v1')!==false) {
        if (!preg_match('/\$mkaActivityOriginal=(\x27[^\x27]+\x27);/',$source,$saved)) throw new RuntimeException('Native activity backup reference missing.');
        $original=substr($saved[1],1,-1);
        if (!is_file($original)) throw new RuntimeException('Native activity original missing.');
        $source=file_get_contents($original);
    }
    $decoder=mka_activity_decoder_source($source,$original);
    eval('?>'.$decoder);
    if (!isset($mkaActivityDecoded) || !is_string($mkaActivityDecoded)) throw new RuntimeException('Native activity decoding failed.');
    $patched=mka_activity_patch_body($mkaActivityDecoded);
    if (substr_count($mkaActivityDecoded,"include('sessao_admin.hhvm')")!==1 || strpos($mkaActivityDecoded,'$_SESSION')!==false) throw new RuntimeException('Native authentication shape not supported.');
    require_once __DIR__.'/installer-database.php';
    $db=mka_installer_database_connect();
    $db->set_charset('utf8mb4');
    $view=$db->query('SHOW CREATE VIEW vtab_ativ')->fetch_row();
    $definition=$view[1];
    $expected="select sis_ativ.id AS id,sis_ativ.registro AS registro,sis_ativ.data AS data,sis_ativ.login AS login,sis_ativ.ip AS ip,sis_ativ.tipo AS tipo,sis_ativ.operacao AS operacao from sis_ativ union select sis_logs.id AS id,sis_logs.registro AS registro,str_to_date(sis_logs.data,'%d/%m/%Y %H:%i:%s') AS data,sis_logs.login AS login,'0.0.0.0' AS ip,sis_logs.tipo AS tipo,sis_logs.operacao AS operacao from sis_logs";
    $viewStart=stripos($definition,' AS select ');
    $normalize=function($value){
        $parts=preg_split("/('(?:''|[^'])*')/",str_replace('`','',$value),-1,PREG_SPLIT_DELIM_CAPTURE);
        foreach($parts as $i=>$part)if($i%2===0)$parts[$i]=preg_replace('/\s+/','',strtolower($part));
        return implode('',$parts);
    };
    if ($viewStart===false || $normalize(substr($definition,$viewStart+4))!==$normalize($expected)) throw new RuntimeException('Native activity view differs from the validated schema.');
    $collations=array();
    foreach(array('vtab_ativ','sis_ativ','sis_logs') as $table) {
        $columns=$db->query('SHOW FULL COLUMNS FROM '.$table);
        while($column=$columns->fetch_assoc())if(in_array($column['Field'],array('login','tipo'),true) || ($column['Field']==='id' && $table!=='sis_logs')) {
            if(isset($collations[$column['Field']]) && $collations[$column['Field']]!==$column['Collation']) throw new RuntimeException('Native activity predicate collations differ.');
            $collations[$column['Field']]=$column['Collation'];
        }
    }
    foreach(array('sis_ativ','sis_logs') as $table) {
        $keys=$db->query('SHOW INDEX FROM '.$table.' WHERE Key_name=\'PRIMARY\'');
        $key=$keys->fetch_assoc();
        if (!$key || $key['Column_name']!=='id' || $keys->num_rows!==1) throw new RuntimeException('Activity branch IDs are not unique.');
    }
    if (stripos($db->server_info,'MariaDB')!==false) $db->query('SET SESSION max_statement_time=30');
    $db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
    $query="SELECT * FROM vtab_ativ WHERE tipo <> 'central' ORDER BY data DESC LIMIT 0,90";
    $hashes=array();
    foreach(array('before'=>$query,'candidate'=>mka_activity_recent_sql($query)) as $phase=>$sql) {
        $start=microtime(true);$result=$db->query(preg_replace('/^SELECT /','SELECT SQL_NO_CACHE ',$sql,1));
        if (!$result) throw new RuntimeException('Activity equivalence measurement failed.');
        $rows=$result->fetch_all(MYSQLI_ASSOC);
        $hashes[$phase]=hash('sha256',serialize($rows));
        $report[$phase.'_ms']=(int)round((microtime(true)-$start)*1000);
    }
    $db->rollback();
    if ($hashes['before']!==$hashes['candidate']) throw new RuntimeException('Native activity results differ; original preserved.');
    if (($argv[3]??'')==='--dry-run') {$report['status']='validated';}
    elseif ($original!==$target) {$report['status']='already_applied';}
    else {
        $storage='/var/lib/mkauth-suite-native-activity';
        if (!is_dir($storage) && !mkdir($storage,0755,true)) throw new RuntimeException('Activity original storage unavailable.');
        $saved=$storage.'/logs_ajax-'.hash('sha256',$source).'.hhvm';
        if (!is_file($saved) && file_put_contents($saved,$source,LOCK_EX)===false) throw new RuntimeException('Activity original backup failed.');
        chmod($saved,0444);
        if (hash_file('sha256',$saved)!==hash('sha256',$source)) throw new RuntimeException('Activity original backup checksum differs.');
        if (!copy($target,$backup.'/logs_ajax-before.hhvm')) throw new RuntimeException('Installation rollback backup failed.');
        $wrapper="<?php\n/* mka-suite-native-activity-v1 */\n\$mkaActivityOriginal=".var_export($saved,true).";\nrequire ".var_export($helper,true).";\ntry {\n    \$mkaActivitySource=file_get_contents(\$mkaActivityOriginal);\n    eval('?>'.mka_activity_decoder_source(\$mkaActivitySource,\$mkaActivityOriginal));\n    if (!is_string(\$mkaActivityDecoded)) throw new RuntimeException('Native activity decoding failed.');\n    \$mkaActivityProgram=mka_activity_patch_body(\$mkaActivityDecoded);\n} catch (Throwable \$mkaActivityError) {\n    http_response_code(503);exit('Atividades temporariamente indisponiveis.');\n}\neval(\$mkaActivityProgram);\n";
        $staged=$target.'.mka-stage';$mode=fileperms($target)&0777;$owner=fileowner($target);$group=filegroup($target);
        if (file_put_contents($staged,$wrapper,LOCK_EX)===false) throw new RuntimeException('Activity wrapper staging failed.');
        chmod($staged,$mode);chown($staged,$owner);chgrp($staged,$group);
        if (!rename($staged,$target)) throw new RuntimeException('Activity wrapper installation failed.');
        $report['status']='optimized';
    }
    printf("[desempenho] Atividades: %d ms antes -> %d ms depois (%s).\n",$report['before_ms'],$report['candidate_ms'],$report['status']);
} catch (Throwable $error) {
    $report['reason']=$error->getMessage();echo '[aviso] '.$error->getMessage()."\n";
}
if ($backup && is_dir($backup)) file_put_contents($backup.'/native-activity-performance.json',json_encode($report,JSON_PRETTY_PRINT),LOCK_EX);
exit($report['status']==='not_applied'?1:0);
