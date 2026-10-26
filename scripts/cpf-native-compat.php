<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/native_activity_performance.php';
$p=$argv[1];$mode=$argv[2];
eval('?>'.mka_activity_decoder_source(file_get_contents($p),$p));
if(!isset($mkaActivityDecoded))throw new RuntimeException('Native decode failed');
$body=$mkaActivityDecoded;
$auth="include('sessao_admin.hhvm')";
if(substr_count($body,$auth)!==1)throw new RuntimeException('Native authentication shape differs');
if(strpos($mode,'executar_')===0 && (substr_count($body,'switch(true){')<1 || strpos($body,$auth)>strpos($body,'switch(true){')))throw new RuntimeException('Dispatch precedes native authentication');
$readable=preg_replace_callback('/\\x([a-fA-F0-9]{2})/',function($m){return chr(hexdec($m[1]));},$body);
$templates='';
foreach(get_defined_vars() as $key=>$value)if(is_string($value)&&$key!=='mkaActivityDecoded'&&strpos($value,'<')!==false)$templates.=$value;
if(in_array($mode,array('conf_opcoes','cliente_ins','cliente_alt'),true)) {
    if(strpos($templates,'id="form"')===false)throw new RuntimeException('Native form anchor differs');
    $field=$mode==='conf_opcoes'?'validar_cpf':'cpf_cnpj';
    if(strpos($templates,'name="'.$field.'"')===false)throw new RuntimeException('Native CPF field anchor differs');
}
$method=null;
if(preg_match_all('/MKAC_PERMISSAO::([A-Za-z0-9_]+)\(/',$readable,$matches)) {
    $methods=array_unique($matches[1]);if(count($methods)===1)$method=reset($methods);
}
// Decode only: no native authentication, database writes or controller dispatch.
echo json_encode(array('auth'=>true,'mode'=>$mode,'permission_method'=>$method,'source_sha256'=>hash_file('sha256',$p)));
