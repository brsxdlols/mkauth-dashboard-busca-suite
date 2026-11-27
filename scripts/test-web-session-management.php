<?php
// Isolated files only. Never terminate an installed MK-Auth session.
$root=sys_get_temp_dir().'/mka-session-management-'.bin2hex(random_bytes(6));mkdir($root);session_save_path($root);session_start();
class MKAC_SESSAO{public static function unserialize($raw){return unserialize($raw,array('allowed_classes'=>false));}}
require $argv[1].'/web_session_counts.php';require $argv[1].'/web_session_management.php';
function check($condition,$message){if(!$condition)throw new Exception($message);}
function refused($call){try{$call();}catch(RuntimeException $e){return;}throw new Exception('Action unexpectedly allowed');}
try{
 $_SESSION['MKA_Usuario']='alice';$_SESSION['MKA_Logado']=true;$_SERVER['REMOTE_ADDR']='192.0.2.5';$_SERVER['HTTP_X_FORWARDED_FOR']='198.51.100.1';$_SERVER['HTTP_USER_AGENT']='Mozilla Windows <script>';
 mka_web_session_track_current();check($_SESSION['suite_web_session_meta']['ip']==='192.0.2.5','Untrusted forwarded IP used');$created=$_SESSION['suite_web_session_meta']['created'];mka_web_session_track_current();check($_SESSION['suite_web_session_meta']['created']===$created,'Creation changed');
 $id='sess_'.str_repeat('b',26);$other='sess_'.str_repeat('c',26);$self='sess_'.str_repeat('d',26);
 $alice=array('MKA_Logado'=>true,'MKA_Usuario'=>'alice','suite_web_session_meta'=>$_SESSION['suite_web_session_meta']);$bob=array('MKA_Logado'=>true,'MKA_Usuario'=>'bob','MKA_IPaddress'=>'198.51.100.9','MKA_Navegador'=>'Android');
 file_put_contents($root.'/'.$id,serialize($alice));file_put_contents($root.'/'.$other,serialize($bob));file_put_contents($root.'/'.$self,serialize($alice));file_put_contents($root.'/sess_anon',serialize(array('MKA_Logado'=>false,'MKA_Usuario'=>'ignored')));
 $inventory=mka_web_session_inventory();check(count($inventory['users'])===2&&$inventory['sessions']===3,'Counts must be2 users3 sessions');check(mka_web_session_counts()['my_sessions']===2,'Own count incorrect');
 refused(function()use($root,$other){mka_web_session_remove_file($root,$other,'bob','alice',false,'current');});check(is_file($root.'/'.$other),'Unauthorized deletion');
 refused(function()use($root,$id){mka_web_session_remove_file($root,$id,'alice','alice',false,$id);});
 refused(function()use($root){mka_web_session_remove_file($root,'../outside','alice','alice',true,'current');});
 refused(function()use($root,$id){mka_web_session_remove_file($root,$id,'bob','alice',true,'current');});
 $lock=fopen($root.'/'.$id,'rb');flock($lock,LOCK_EX);refused(function()use($root,$id){mka_web_session_remove_file($root,$id,'alice','alice',false,'current');});flock($lock,LOCK_UN);fclose($lock);
 mka_web_session_remove_file($root,$id,'alice','alice',false,'current');check(!is_file($root.'/'.$id),'Own other session not removed');
 mka_web_session_remove_file($root,$other,'bob','alice',true,'current');check(!is_file($root.'/'.$other),'Authorized admin removal failed');
 $base=$root.'/version';mkdir($base);mkdir($base.'/admin');mkdir($base.'/dados');mkdir($base.'/dados/suite-native-sessions');$admin=$base.'/admin';
 check(mka_web_session_native_url($admin)===null,'Legacy should use addon');file_put_contents($admin.'/sessoes.hhvm','<?php /* mka-suite-native-web-sessions-v1 */');file_put_contents($admin.'/executar_sessao.hhvm','<?php');file_put_contents($base.'/original.hhvm','original');
 $manifest=$base.'/dados/suite-native-sessions/manifest.json';file_put_contents($manifest,json_encode(array('original'=>$base.'/original.hhvm','source_sha256'=>hash_file('sha256',$base.'/original.hhvm'),'installed_sha256'=>hash_file('sha256',$admin.'/sessoes.hhvm'))));
 check(mka_web_session_native_url($admin)==='/admin/sessoes.hhvm','Compatible native should be used');file_put_contents($admin.'/sessoes.hhvm','native update');check(mka_web_session_native_url($admin)===null,'Native update must be revalidated');
 check(mka_web_session_os('Mozilla iPhone')==='iPhone / iOS','OS detection incorrect');
 session_write_close();echo "PASS metadata, native/legacy detection,2 users/3 sessions, own/admin logout, self/permission/traversal/changed-owner/lock protection\n";
}finally{
 if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
 $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($files as$file){if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($root);
}
