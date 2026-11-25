<?php
require __DIR__.'/../addons/shared/web_session_counts.php';
$dir=sys_get_temp_dir().'/suite-session-count-test-'.bin2hex(random_bytes(8));mkdir($dir,0700);
class MKAC_SESSAO{static $directory;static function dir_sessao(){return self::$directory;}static function unzerialize($raw){return json_decode($raw,true);}}
MKAC_SESSAO::$directory=$dir;
try{
    foreach(array(array('MKA_Usuario'=>'bruno'),array('MKA_Usuario'=>'bruno'),array('MKA_Usuario'=>'alice'),array('central'=>'client'))as$i=>$row)file_put_contents($dir.'/sess_'.$i,json_encode($row));
    file_put_contents($dir.'/sess_invalid','invalid');
    $c=mka_web_session_counts();if($c!==array('users'=>2,'sessions'=>3))throw new RuntimeException('Users and sessions confused');
    MKAC_SESSAO::$directory=$dir.'/missing';if(mka_web_session_counts()!==null)throw new RuntimeException('Unavailable directory reported fake count');
    echo "PASS distinct users, multiple sessions, native filtering and unavailable directory\n";
}finally{foreach(glob($dir.'/*')as$file)unlink($file);rmdir($dir);}
