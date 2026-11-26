<?php
require_once __DIR__.'/model.php';
require_once __DIR__.'/../shared/native_activity_performance.php';
function mka_equipment_patch_contract($body) {
    $auth="include('sessao_admin.hhvm')";
    if(substr_count($body,$auth)!==1)throw new RuntimeException('Autenticação nativa do contrato incompatível.');
    $pattern='/\$(\w+)\s*=\s*new\s+MKAC_VARS\(\s*[\x27\x22]cliente[\x27\x22]\s*,\s*(\$\w+)\s*\)\s*;\s*(\$\w+)\s*=\s*\$\1->\w+\(\s*\3\s*\)\s*;/';
    if(preg_match_all($pattern,$body,$matches,PREG_OFFSET_CAPTURE)!==1){
        // 24.03 expands fields inline. Match its client loop and output only;
        // never insert in the separate installation/service-request branch.
        $loop='/foreach\(\$\w+ as (\$\w+)\)\{\$\w+="SELECT \*\s+FROM sis_cliente\s+WHERE login = \x27\{\1\}\x27\s+LIMIT 1";/';
        if(strpos($body,'MKAC_VARS')!==false||preg_match_all($loop,$body,$loops,PREG_OFFSET_CAPTURE)!==1)throw new RuntimeException('Variáveis nativas do contrato incompatíveis.');
        $output='/echo"<div style=\\\\"page-break-after: always\\\\"> \{(\$\w+)\} <\/div>";/';
        if(preg_match_all($output,$body,$outputs,PREG_OFFSET_CAPTURE)!==2)throw new RuntimeException('Saída nativa do contrato legado incompatível.');
        $selected=array();foreach($outputs[0]as$i=>$out)if($out[1]>$loops[0][0][1])$selected[]=$i;
        if(count($selected)!==1||$loops[0][0][1]<strpos($body,$auth))throw new RuntimeException('Ramo nativo do cliente incompatível.');
        $i=$selected[0];$html=$outputs[1][$i][0];$login=$loops[1][0][0];
        $insert=$html.'=mka_equipment_contract_replace($LOADMYSQL,'.$login.','.$html.');';
        return substr_replace($body,$insert,$outputs[0][$i][1],0);
    }
    if($matches[0][0][1]<strpos($body,$auth))throw new RuntimeException('Variáveis nativas do contrato incompatíveis.');
    $match=$matches[0][0];$login=$matches[2][0][0];$html=$matches[3][0][0];
    // Protect the new token from native versions that remove unknown %variables%.
    $before='$mkaEquipmentMarker="__mkaequipment_".bin2hex(random_bytes(16))."__";'.$html.'=str_replace("%equipamentos%",$mkaEquipmentMarker,'.$html.');';
    $after=$html.'=str_replace($mkaEquipmentMarker,"%equipamentos%",'.$html.');'.$html.'=mka_equipment_contract_replace($LOADMYSQL,'.$login.','.$html.');';
    return substr_replace($body,$before.$match[0].$after,$match[1],strlen($match[0]));
}
if(defined('MKA_EQUIPMENT_CODEC_TEST'))return;
if(!isset($mkaEquipmentOriginal)||!is_file($mkaEquipmentOriginal)){http_response_code(503);exit('Contrato indisponível.');}
eval('?>'.mka_activity_decoder_source(file_get_contents($mkaEquipmentOriginal),$mkaEquipmentOriginal));
$mkaEquipmentBody=mka_equipment_patch_contract($mkaActivityDecoded);
// Retain the native source file and directory semantics after delegation.
$mkaEquipmentEntry=dirname(__DIR__,2).'/gera_contrato.hhvm';
$mkaEquipmentTokens=token_get_all('<?php '.$mkaEquipmentBody);$mkaEquipmentBody='';
foreach($mkaEquipmentTokens as $token){if(is_array($token)){if($token[0]===T_OPEN_TAG)continue;if($token[0]===T_FILE)$mkaEquipmentBody.=var_export($mkaEquipmentEntry,true);elseif($token[0]===T_DIR)$mkaEquipmentBody.=var_export(dirname($mkaEquipmentEntry),true);else$mkaEquipmentBody.=$token[1];}else$mkaEquipmentBody.=$token;}
eval($mkaEquipmentBody);

