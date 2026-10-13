<?php
function perf_query($db,$sql){$r=mysqli_query($db,$sql);if(!$r)throw new RuntimeException('Consulta de diagnostico falhou ou excedeu o timeout.');return $r;}
function perf_scalar($db,$sql){$r=perf_query($db,$sql);$row=mysqli_fetch_row($r);return (int)$row[0];}
function perf_compatibility($db){
    $checks=array();
    $cols=perf_query($db,"SELECT TABLE_NAME,COLUMN_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND ((TABLE_NAME='sis_cliente' AND COLUMN_NAME='login') OR (TABLE_NAME='sis_adicional' AND COLUMN_NAME IN ('login','username')) OR (TABLE_NAME='radacct' AND COLUMN_NAME='username'))");
    $collations=array();while($row=mysqli_fetch_assoc($cols))$collations[]=$row['COLLATION_NAME'];
    $checks['matching_ci_collations']=count($collations)===4 && count(array_unique($collations))===1 && substr((string)$collations[0],-3)==='_ci';
    $checks['indexed_open_sessions']=perf_scalar($db,"SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='radacct' AND SEQ_IN_INDEX=1 AND COLUMN_NAME='acctstoptime'")>0;
    $active=perf_scalar($db,'SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL');
    $checks['bounded_active_sessions']=$active<=50000;
    if (!$checks['matching_ci_collations'] || !$checks['indexed_open_sessions'] || !$checks['bounded_active_sessions']) return array('compatible'=>false,'checks'=>$checks,'active_sessions'=>$active);
    $checks['clean_login_spaces']=perf_scalar($db,"SELECT (SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL AND BINARY username<>BINARY TRIM(username))+(SELECT COUNT(*) FROM sis_cliente WHERE cli_ativado='s' AND BINARY login<>BINARY TRIM(login))+(SELECT COUNT(*) FROM sis_adicional WHERE BINARY login<>BINARY TRIM(login) OR BINARY username<>BINARY TRIM(username))")===0;
    $old=mka_perf_reference_sql();$new=mka_perf_set_sql();
    // Both independently expressed classifications use one SQL snapshot.
    $same=perf_scalar($db,"SELECT COUNT(*) FROM sis_cliente c WHERE c.cli_ativado='s' AND (($old)<>($new))")===0;
    $checks['same_classification']=$same;
    $eligible=$checks;unset($eligible['clean_login_spaces']);
    return array('compatible'=>!in_array(false,$eligible,true),'checks'=>$checks,'active_sessions'=>$active);
}
