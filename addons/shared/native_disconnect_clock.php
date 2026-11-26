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

function mka_native_disconnect_load($source,$original,$capture=false) {
    // 24.03 puts the final eval inside a function in the first base64 codec.
    // Replace only that eval; the vendor codec still reads the untouched original.
    $legacy=false;
    if(preg_match("/base64_decode\\('([A-Za-z0-9+\\/=]+)'/",$source,$first,PREG_OFFSET_CAPTURE)){
        $codec=base64_decode($first[1][0],true);
        if(is_string($codec)&&preg_match('/function (FFF[A-Z]+)\(\$(\w+)\)/',$codec,$function)){
            $pattern='/eval\((\$\w+)\);return \$'.preg_quote($function[2],'/').';/';
            if(preg_match_all($pattern,$codec,$calls)!==1)throw new RuntimeException('Codec legado de desconexao desconhecido; original preservado.');
            $value=$calls[1][0];
            $replacement=$capture?'$GLOBALS[\'mkaNativeDisconnectBody\']=mka_native_disconnect_patch('.$value.');':'eval(mka_native_disconnect_patch('.$value.'));';
            $codec=preg_replace($pattern,str_replace('\\','\\\\',$replacement).'return $'.$function[2].';',$codec,1);
            $source=substr_replace($source,base64_encode($codec),$first[1][1],strlen($first[1][0]));
            $legacy=true;
        }
    }
    eval('?>'.mka_activity_decoder_source($source,$original));
    if($legacy){
        if(!preg_match('/^FFF[A-Z]+\(\x27[A-Za-z0-9+\/=]+\x27\);\s*$/D',$mkaActivityDecoded))throw new RuntimeException('Payload legado de desconexao desconhecido.');
        eval($mkaActivityDecoded);
    }else eval(mka_native_disconnect_second_stage($mkaActivityDecoded,$capture));
}

if(defined('MKA_NATIVE_DISCONNECT_TEST'))return;
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(404);exit;}
if(!isset($mkaNativeDisconnectOriginal)||!is_file($mkaNativeDisconnectOriginal))throw new RuntimeException('Original nativo de desconexao indisponivel.');
// Execute the original vendor codec; patch only the decoded SQL, retaining all
// native method names, disconnect transport and other class methods.
mka_native_disconnect_load(file_get_contents($mkaNativeDisconnectOriginal),$mkaNativeDisconnectOriginal);

