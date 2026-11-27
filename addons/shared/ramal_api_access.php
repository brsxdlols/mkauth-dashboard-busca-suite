<?php
function mka_ramal_password_manifest($admin=null){
 if($admin===null)$admin=dirname(__DIR__,2);$path=dirname($admin).'/dados/suite-ramal-password/manifest.json';
 $info=is_file($path)?json_decode((string)@file_get_contents($path),true):null;if(!is_array($info)||!isset($info['verify_method'],$info['login_sha256'],$info['files'])||!preg_match('/^[A-Za-z_]\w*$/D',$info['verify_method'])||!is_file($admin.'/executar_login.hhvm')||hash_file('sha256',$admin.'/executar_login.hhvm')!==$info['login_sha256'])return null;
 if(!isset($info['guard_sha256'])||!is_file($admin.'/mka_suite_ramal_password_guard.hhvm')||hash_file('sha256',$admin.'/mka_suite_ramal_password_guard.hhvm')!==$info['guard_sha256'])return null;
 foreach(array('ramal_alt.hhvm','executar_ramal.hhvm')as$name){$record=$info['files'][$name]??null;if(!is_array($record)||!is_file($admin.'/'.$name)||!is_file($record['original']??'')||hash_file('sha256',$admin.'/'.$name)!==($record['installed_sha256']??'')||hash_file('sha256',$record['original'])!==($record['source_sha256']??''))return null;}
 return $info;
}
function mka_ramal_has_permission($db,$actor,$name){$q=$db->prepare("SELECT 1 FROM sis_perm WHERE usuario=? AND nome=? AND permissao='sim' LIMIT 1");$q->bind_param('ss',$actor,$name);$q->execute();$yes=$q->get_result()->num_rows===1;$q->close();return $yes;}
function mka_ramal_verify_operator_password($password,$stored,$method){
 if(!is_string($password)||$password===''||strlen($password)>1024||!is_string($stored)||$stored==='')return false;
 if(!is_callable(array('MKAC_CRYPT',$method)))throw new RuntimeException('Verificador nativo indisponivel.');
 if(call_user_func(array('MKAC_CRYPT',$method),$password,$stored)===true)return true;
 // Native MK-Auth also accepts legacy plain-text credentials. Never accept a hash as the password.
 if(preg_match('/^(?:\$|[a-f0-9]{32,128}$)/i',$stored))return false;
 return hash_equals($stored,$password);
}
function mka_ramal_source_ip($router,$manifest=null){
 $GLOBALS['mka_ramal_source_cached']=false;
 if(!filter_var($router,FILTER_VALIDATE_IP))return null;
 $binary=null;foreach(array('/sbin/ip','/usr/sbin/ip','/bin/ip','/usr/bin/ip')as$path)if(is_executable($path)){$binary=$path;break;}
 if($binary===null||!is_callable('exec'))return mka_ramal_cached_source_ip($router,$manifest);
 $lines=array();$status=1;exec(escapeshellarg($binary).' -j route get '.escapeshellarg($router).' 2>/dev/null',$lines,$status);
 $routes=json_decode(implode("\n",$lines),true);$ip=$routes[0]['prefsrc']??($routes[0]['src']??null);return $status===0&&filter_var($ip,FILTER_VALIDATE_IP)?$ip:mka_ramal_cached_source_ip($router,$manifest);
}
function mka_ramal_rate_begin($directory,$actor){
 if(!is_dir($directory)||is_link($directory))throw new RuntimeException('Protecao de tentativas indisponivel.');
 $path=$directory.'/'.hash('sha256',$actor).'.json';if(is_link($path))throw new RuntimeException('Protecao de tentativas indisponivel.');
 $file=fopen($path,'c+');if(!$file||!flock($file,LOCK_EX))throw new RuntimeException('Protecao de tentativas indisponivel.');chmod($path,0600);
 $state=json_decode(stream_get_contents($file),true);if(!is_array($state)||time()-($state['start']??0)>=900)$state=array('start'=>time(),'failures'=>0);
 if(($state['failures']??0)>=5){flock($file,LOCK_UN);fclose($file);throw new RuntimeException('Limite de tentativas. Tente novamente em 15 minutos.');}
 return array($file,$state);
}
function mka_ramal_rate_end($ticket,$ok){list($file,$state)=$ticket;$state['failures']=$ok?0:$state['failures']+1;rewind($file);ftruncate($file,0);fwrite($file,json_encode($state));fflush($file);flock($file,LOCK_UN);fclose($file);}

function mka_ramal_reveal($db,$actor,$id,$password,$method,$stored){
 if(!mka_ramal_has_permission($db,$actor,'perm_servidor')||!mka_ramal_has_permission($db,$actor,'perm_usuarios'))throw new RuntimeException('Sem permissao.');
 $q=$db->prepare('SELECT sha,ativo,validade FROM sis_acesso WHERE login=? LIMIT 1');$q->bind_param('s',$actor);$q->execute();$user=$q->get_result()->fetch_assoc();$q->close();
 if(!$user||$user['ativo']!=='sim'||!mka_ramal_verify_operator_password($password,(string)$user['sha'],$method))throw new RuntimeException('Senha incorreta.');
 if($user['validade']&&strtotime($user['validade'])<strtotime(date('Y-m-d')))throw new RuntimeException('Acesso vencido.');
 $result=array('ok'=>true);
 if($stored){$q=$db->prepare('SELECT senha FROM nas WHERE uuid_nas=? LIMIT 1');$q->bind_param('s',$id);$q->execute();$row=$q->get_result()->fetch_assoc();$q->close();if(!$row)throw new RuntimeException('Ramal nao encontrado.');$result['password']=(string)$row['senha'];}
 return $result;
}

function mka_ramal_cached_source_ip($router,$manifest){$ip=is_array($manifest)?($manifest['source_ips'][$router]??null):null;if(!filter_var($ip,FILTER_VALIDATE_IP))return null;$GLOBALS['mka_ramal_source_cached']=true;return $ip;}
