<?php
require_once __DIR__.'/native_activity_performance.php';

function mka_native_disconnect_patch($body) {
    if(substr_count($body,'class MKAC_FREERADIUS')!==1)throw new RuntimeException('Classe nativa de desconexao desconhecida.');
    $pattern='/UPDATE radacct\s+SET acctstoptime = DATE_ADD\(acctstarttime, interval acctsessiontime second\), acctterminatecause = \'Lost-Service\'\s+WHERE radacctid = \'\{\$[a-zA-Z_]\w*\}\'/';
    if(preg_match_all($pattern,$body,$matches,PREG_OFFSET_CAPTURE)!==1)throw new RuntimeException('Consulta nativa de encerramento desconhecida; original preservado.');
    $match=$matches[0][0];
    $sql=str_replace('DATE_ADD(acctstarttime, interval acctsessiontime second)','CURRENT_TIMESTAMP()',$match[0]).' AND acctstoptime IS NULL';
    return substr_replace($body,$sql,$match[1],strlen($match[0]));
}

function mka_native_disconnect_second_stage($stage,$capture=false) {
    $pattern='/\beval\s*\(\s*(\$[a-zA-Z_]\w*)\s*\)\s*;/';
    if(preg_match_all($pattern,$stage,$matches,PREG_OFFSET_CAPTURE)!==1)throw new RuntimeException('Codec nativo de desconexao desconhecido.');
    $match=$matches[0][0];$value=$matches[1][0][0];
    $replacement=$capture?'$GLOBALS[\'mkaNativeDisconnectBody\']=mka_native_disconnect_patch('.$value.');':'eval(mka_native_disconnect_patch('.$value.'));';
    return substr_replace($stage,$replacement,$match[1],strlen($match[0]));
}

if(defined('MKA_NATIVE_DISCONNECT_TEST'))return;
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(404);exit;}
if(!isset($mkaNativeDisconnectOriginal)||!is_file($mkaNativeDisconnectOriginal))throw new RuntimeException('Original nativo de desconexao indisponivel.');
// Execute the original vendor codec; patch only the decoded SQL, retaining all
// native method names, disconnect transport and other class methods.
eval('?>'.mka_activity_decoder_source(file_get_contents($mkaNativeDisconnectOriginal),$mkaNativeDisconnectOriginal));
eval(mka_native_disconnect_second_stage($mkaActivityDecoded));
