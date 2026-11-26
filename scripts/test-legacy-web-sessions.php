<?php
$dir=sys_get_temp_dir().'/mka-session-fixture-'.bin2hex(random_bytes(5));mkdir($dir);session_save_path($dir);
class MKAC_SESSAO {public static function unserialize($raw){return unserialize($raw,array('allowed_classes'=>false));}}
require $argv[1];
try{
 foreach(array(array('MKA_Usuario'=>'bruno','MKA_Logado'=>true),array('MKA_Usuario'=>'bruno','MKA_Logado'=>true),array('MKA_Usuario'=>'other','MKA_Logado'=>true),array('MKA_Usuario'=>'anonymous','MKA_Logado'=>false),array('portal'=>'customer'))as$i=>$data)file_put_contents($dir.'/sess_'.$i,serialize($data));
 $_SESSION=array('MKA_Usuario'=>'bruno');$counts=mka_web_session_counts();if($counts!==array('users'=>2,'sessions'=>3,'my_sessions'=>2))throw new Exception('Incorrect legacy counts');
 echo "PASS legacy native decoder, distinct users, own sessions and authenticated-only inventory\n";
}finally{foreach(glob($dir.'/sess_*')as$p)unlink($p);rmdir($dir);}
