<?php
// Included from a native entry: its original authentication runs below.
require_once __DIR__.'/policy.php';
require_once __DIR__.'/../shared/native_activity_performance.php';
if(!isset($nnCpfOriginal,$nnCpfMode,$nnCpfPermissionMethod)) {http_response_code(503);exit('Patch CPF indisponivel.');}
$GLOBALS['nn_cpf_entry']=$nnCpfMode;
$GLOBALS['nn_cpf_permission_method']=$nnCpfPermissionMethod;
$nnCpfSource=file_get_contents($nnCpfOriginal);
eval('?>'.mka_activity_decoder_source($nnCpfSource,$nnCpfOriginal));
if(!isset($mkaActivityDecoded)||!is_string($mkaActivityDecoded))throw new RuntimeException('Formato nativo CPF incompativel.');
$nnCpfBody=nn_cpf_filter_payload($mkaActivityDecoded);
// Retain magic path semantics even though the entry delegates to this runtime.
$nnCpfTokens=token_get_all('<?php '.$nnCpfBody);$nnCpfBody='';
foreach($nnCpfTokens as $nnCpfToken) {
    if(is_array($nnCpfToken)) {
        if($nnCpfToken[0]===T_OPEN_TAG)continue;
        if($nnCpfToken[0]===T_FILE)$nnCpfBody.=var_export($nnCpfOriginal,true);
        elseif($nnCpfToken[0]===T_DIR)$nnCpfBody.=var_export(dirname($nnCpfOriginal),true);
        else $nnCpfBody.=$nnCpfToken[1];
    } else $nnCpfBody.=$nnCpfToken;
}
ob_start('nn_cpf_render');
eval($nnCpfBody);
