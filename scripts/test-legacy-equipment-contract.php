<?php
define('MKA_EQUIPMENT_CODEC_TEST',true);require __DIR__.'/../addons/client_equipment/contract_runtime.php';
$fixture= <<<'PHP'
include('configure.php');include('conexao.php');include('sessao_admin.hhvm');
foreach($requests as $request){echo"<div style=\"page-break-after: always\"> {$html} </div>";}
foreach($clients as $login){$sql="SELECT *
FROM sis_cliente
WHERE login = '{$login}'
LIMIT 1";
$html=str_replace('%nomecliente%',$name,$html);
echo"<div style=\"page-break-after: always\"> {$html} </div>";}
PHP;
$patched=mka_equipment_patch_contract($fixture);
$insert='$html=mka_equipment_contract_replace($LOADMYSQL,$login,$html);';
if(substr_count($patched,$insert)!==1||str_replace($insert,'',$patched)!==$fixture||strpos($patched,$insert)<strpos($patched,'foreach($clients'))throw new Exception('Wrong legacy insertion');
foreach(array(str_replace('FROM sis_cliente','FROM unknown_table',$fixture),str_replace("include('sessao_admin.hhvm');",'',$fixture),$fixture.'echo"<div style=\\"page-break-after: always\\"> {$html} </div>";')as$unknown){
 try{mka_equipment_patch_contract($unknown);throw new LogicException('Unknown source accepted');}catch(RuntimeException$expected){}
}
$modern="include('sessao_admin.hhvm');\$v=new MKAC_VARS('cliente',\$login);\$html=\$v->expand(\$html);";
$result=mka_equipment_patch_contract($modern);
if(strpos($result,'mkaEquipmentMarker')===false||strpos($result,'$v->expand($html)')===false)throw new Exception('Modern branch regression');
echo 'PASS legacy client-only insertion, original/authentication preserved, unknown branches refused, modern expansion preserved'.PHP_EOL;
