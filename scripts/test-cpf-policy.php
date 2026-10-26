<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';
if(!preg_match('/^mka_cpf_test_[a-f0-9]+$/D',(string)getenv('MYSQL_DB')))throw new RuntimeException('Only an isolated CPF test database is allowed');
$LOADMYSQL=mka_installer_database_connect();require __DIR__.'/../addons/nn_cpf_opcional/policy.php';
define('KEY2PROVEDOR','synthetic-test-key');
class MKAC_PERMISSAO {public static function FKHMTAHMUAUKTMTMKKUMUAHKTAKMMMTAU($u,$p){return $u==='allowed'&&$p==='perm_config';}}
$_SESSION=array('MKA_Usuario'=>'allowed');$_SERVER['REQUEST_METHOD']='POST';$_GET=array('acao'=>'opcoes');$_POST=array('nn_permitir_sem_cpf'=>'sim','nn_cpf_token'=>nn_cpf_csrf());$case=$argv[1];
register_shutdown_function(function()use($case){echo json_encode(array('case'=>$case,'status'=>http_response_code()?:200)).PHP_EOL;});
if($case==='csrf_bad')$_POST['nn_cpf_token']='bad';if($case==='permission_bad')$_SESSION['MKA_Usuario']='denied';if($case==='value_bad')$_POST['nn_permitir_sem_cpf']='invalid';if($case==='save_no')$_POST['nn_permitir_sem_cpf']='nao';
if(strpos($case,'save_')===0||in_array($case,array('csrf_bad','permission_bad','value_bad'),true))nn_cpf_save_policy();
else {$_GET=array('acao'=>strpos($case,'edit')===0?'alt.cliente':'ins.cliente');$_POST=array('cpf_cnpj'=>strpos($case,'filled')!==false?'11144477735':'   ');nn_cpf_guard_client();$doc=$_POST['cpf_cnpj'];$q=$LOADMYSQL->prepare("INSERT INTO cpf_fixture(login,cpf_cnpj) VALUES('fixture',?) ON DUPLICATE KEY UPDATE cpf_cnpj=VALUES(cpf_cnpj)");$q->bind_param('s',$doc);$q->execute();}
