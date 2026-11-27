<?php
function mka_ramal_uuid($id){return is_string($id)&&preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/Di',$id);}
function mka_ramal_password_placeholder($row,$id){
 if(!mka_ramal_uuid($id)||!is_array($row)||($row['uuid_nas']??'')!==$id)throw new RuntimeException('Cadastro de ramal incompativel.');
 $token='__MKA_KEEP_'.bin2hex(random_bytes(16)).((string)($row['senha']??'')===''?'_EMPTY':'');
 if(!isset($_SESSION['suite_ramal_password_keep']))$_SESSION['suite_ramal_password_keep']=array();
 $tokens=array_filter($_SESSION['suite_ramal_password_keep'][$id]??array(),function($value){return is_array($value)&&($value['expires']??0)>=time();});$tokens[$token]=array('token'=>$token,'actor'=>(string)($_SESSION['MKA_Usuario']??''),'expires'=>time()+43200);$_SESSION['suite_ramal_password_keep'][$id]=array_slice($tokens,-12,null,true);
 return $token;
}
function mka_ramal_password_preserve_post($db){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||($_GET['acao']??'')!=='alt.ramal')return;
 $password=$_POST['senha']??null;if(!is_string($password)||strpos($password,'__MKA_KEEP_')!==0)return;
 $id=(string)($_POST['uuid']??($_POST['idramal']??''));$keep=$_SESSION['suite_ramal_password_keep'][$id][$password]??null;
 if(!mka_ramal_uuid($id)||!is_array($keep)||($keep['expires']??0)<time()||($keep['actor']??'')!==(string)($_SESSION['MKA_Usuario']??'')||!hash_equals($keep['token'],$password))throw new RuntimeException('Atualize o cadastro antes de gravar. A senha existente foi preservada.');
 $query=$db->prepare('SELECT senha,nasname FROM nas WHERE uuid_nas=? LIMIT 1');$query->bind_param('s',$id);$query->execute();$row=$query->get_result()->fetch_assoc();$query->close();
 if(!$row)throw new RuntimeException('Ramal nao encontrado; alteracao cancelada.');
 if(isset($row['nasname'],$_POST['ramal'])&&(string)$row['nasname']!==(string)$_POST['ramal'])throw new RuntimeException('Identificacao do ramal divergente; alteracao cancelada.');
 $_POST['senha']=(string)$row['senha'];
}
function mka_ramal_password_save_value($db){mka_ramal_password_preserve_post($db);return $_POST['senha'];}
function mka_ramal_password_patch($source,$kind){
 if($kind==='form'){
  $source=preg_replace_callback('/echo\s+(\$[A-Za-z_]\w*)\[\x27senha\x27\]\s*;/',function($match){return "echo mka_ramal_password_placeholder(".$match[1].",\$_GET['id']??'');";},$source,-1,$count);
  if($count!==1)throw new RuntimeException('Campo nativo de senha desconhecido; fonte preservada.');
 }else{
  $anchor="include('sessao_admin.hhvm');";
  if(substr_count($source,$anchor)!==1||substr_count($source,"\$_POST['senha']")!==2||(strpos($source,"\$_POST['uuid']")===false&&strpos($source,"\$_POST['idramal']")===false))throw new RuntimeException('Gravacao nativa desconhecida; fonte preservada.');
  $source=str_replace("\$_POST['senha']",'mka_ramal_password_save_value($LOADMYSQL)',$source);
 }
 return $source;
}
if(defined('MKA_RAMAL_PASSWORD_TEST'))return;
if(!isset($mkaRamalOriginal,$mkaRamalHash,$mkaRamalKind)||!is_file($mkaRamalOriginal)||!hash_equals($mkaRamalHash,hash_file('sha256',$mkaRamalOriginal))){http_response_code(503);exit('Integracao do ramal indisponivel. Revise a instalacao.');}
require_once __DIR__.'/native_activity_performance.php';
try{
 eval('?>'.mka_activity_decoder_source(file_get_contents($mkaRamalOriginal),$mkaRamalOriginal));
 $mkaRamalPatched=mka_ramal_password_patch($mkaActivityDecoded,$mkaRamalKind);
 $entry=dirname(__DIR__,2).'/'.($mkaRamalKind==='form'?'ramal_alt.hhvm':'executar_ramal.hhvm');$tokens=token_get_all('<?php '.$mkaRamalPatched);$code='';foreach($tokens as$token){if(is_array($token)){if($token[0]===T_OPEN_TAG)continue;$code.=$token[0]===T_FILE?var_export($entry,true):($token[0]===T_DIR?var_export(dirname($entry),true):$token[1]);}else $code.=$token;}
 chdir(dirname(__DIR__,2));eval($code);
}catch(Throwable $error){http_response_code(409);error_log('[suite-ramal-password] native compatibility failure');exit('Nao foi possivel concluir. Atualize o cadastro; nenhuma senha deve ser substituida por um marcador.');}
