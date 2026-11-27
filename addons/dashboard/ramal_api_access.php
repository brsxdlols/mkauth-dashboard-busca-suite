<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/ramal_api_access.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');
function ramal_access_reply($status,$data){http_response_code($status);echo json_encode($data);exit;}
try{
 $actor=(string)($_SESSION['MKA_Usuario']??($_SESSION['MM_Usuario']??''));if($actor==='')ramal_access_reply(403,array('error'=>'Acesso negado.'));
 $db=$conn??($LOADMYSQL??null);if(!($db instanceof mysqli))throw new RuntimeException('Conexao indisponivel.');
 if(!mka_ramal_has_permission($db,$actor,'perm_servidor'))ramal_access_reply(403,array('error'=>'Sem acesso a Ramais.'));
 $canReveal=mka_ramal_has_permission($db,$actor,'perm_usuarios');$manifest=mka_ramal_password_manifest();
 $id=(string)($_GET['ramal']??($_POST['ramal']??''));$router=(string)($_GET['router']??'');
 if(!preg_match('/^[a-f0-9-]{36}$/Di',$id)&&!filter_var($router,FILTER_VALIDATE_IP))ramal_access_reply(400,array('error'=>'Ramal invalido.'));
 $q=$db->prepare('SELECT uuid_nas,nasname FROM nas WHERE uuid_nas=? OR nasname=? LIMIT 1');$q->bind_param('ss',$id,$router);$q->execute();$ramal=$q->get_result()->fetch_assoc();$q->close();if(!$ramal)ramal_access_reply(404,array('error'=>'Ramal nao encontrado.'));
 if($_SERVER['REQUEST_METHOD']==='GET'){
  if(empty($_SESSION['suite_ramal_reveal_csrf']))$_SESSION['suite_ramal_reveal_csrf']=bin2hex(random_bytes(32));
  ramal_access_reply(200,array('username'=>'mkauth','operator'=>$actor,'server_ip'=>mka_ramal_source_ip($ramal['nasname'],$manifest),'source_cached'=>!empty($GLOBALS['mka_ramal_source_cached']),'protected'=>$manifest!==null,'can_reveal'=>$canReveal&&$manifest!==null,'csrf'=>$canReveal?$_SESSION['suite_ramal_reveal_csrf']:null,'ramal'=>$ramal['uuid_nas']));
 }
 if($_SERVER['REQUEST_METHOD']!=='POST')ramal_access_reply(405,array('error'=>'Metodo nao permitido.'));
 if(!$canReveal)ramal_access_reply(403,array('error'=>'A visualizacao exige a permissao Usuarios.'));
 if($manifest===null)ramal_access_reply(409,array('error'=>'Integracao nativa pendente. Atualize o instalador.'));
 if(!isset($_SESSION['suite_ramal_reveal_csrf'])||!hash_equals($_SESSION['suite_ramal_reveal_csrf'],(string)($_POST['csrf']??'')))ramal_access_reply(403,array('error'=>'Confirmacao expirada. Atualize a pagina.'));
 $password=$_POST['operator_password']??'';if(!is_string($password)||$password===''||strlen($password)>1024)ramal_access_reply(400,array('error'=>'Informe a senha do operador logado.'));
 $rate=mka_ramal_rate_begin('/opt/mk-auth/dados/suite-ramal-password/rate-limits',$actor);$verified=false;
 try{
  $result=mka_ramal_reveal($db,$actor,$ramal['uuid_nas'],$password,$manifest['verify_method'],($_POST['stored']??'')==='1');$verified=true;
 }catch(Throwable $authError){$verified=false;}finally{mka_ramal_rate_end($rate,$verified);$password=null;unset($_POST['operator_password']);}
 if(!$verified){error_log('[suite-ramal-password] reauthentication refused');ramal_access_reply(403,array('error'=>'Senha do operador incorreta ou acesso indisponivel.'));}
 error_log('[suite-ramal-password] '.json_encode(array('actor'=>$actor,'ramal'=>$ramal['uuid_nas'],'action'=>'reveal')));ramal_access_reply(200,$result);
}catch(Throwable $error){ramal_access_reply(409,array('error'=>'Nao foi possivel validar o acesso. Atualize a pagina ou consulte o administrador.'));}
