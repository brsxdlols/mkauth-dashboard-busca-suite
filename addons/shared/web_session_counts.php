<?php
// Use exactly the native list's directory, decoder and administrative-session key.
function mka_web_session_inventory() {
    if(!class_exists('MKAC_SESSAO') && is_file('/opt/mk-auth/libs/sessao.class'))require_once '/opt/mk-auth/libs/sessao.class';
    $decoder=is_callable(array('MKAC_SESSAO','unzerialize'))?'unzerialize':(is_callable(array('MKAC_SESSAO','unserialize'))?'unserialize':null);
    if($decoder===null)return null;
    try {
        $dir=is_callable(array('MKAC_SESSAO','dir_sessao'))?MKAC_SESSAO::dir_sessao():session_save_path();
        if(strpos($dir,';')!==false)$dir=substr($dir,strrpos($dir,';')+1);
        if(!is_string($dir)||!is_dir($dir)||!is_readable($dir))return null;
        $files=glob(rtrim($dir,'/').'/sess_*');if($files===false)return null;
        $users=array();$sessions=0;$entries=array();
        foreach($files as $file){
            if(!is_file($file)||is_link($file))continue;
            $raw=@file_get_contents($file);if($raw===false)continue;
            $data=@call_user_func(array('MKAC_SESSAO',$decoder),$raw);
            if(!is_array($data)||!isset($data['MKA_Usuario'])||empty($data['MKA_Logado']))continue;
            $user=(string)$data['MKA_Usuario'];if($user==='')continue;
            $sessions++;$users[$user]=($users[$user]??0)+1;
            $meta=isset($data['suite_web_session_meta'])&&is_array($data['suite_web_session_meta'])&&($data['suite_web_session_meta']['owner']??'')===$user?$data['suite_web_session_meta']:array();
            $entries[]=array('file'=>basename($file),'user'=>$user,'agent'=>(string)($data['MKA_Navegador']??($meta['agent']??'')),'ip'=>(string)($data['MKA_IPaddress']??($data['MKA_IP']??($meta['ip']??''))),'updated'=>filemtime($file));
        }
        return array('users'=>$users,'sessions'=>$sessions,'entries'=>$entries,'directory'=>$dir);
    }catch(Throwable $e){return null;}
}
function mka_web_session_counts() {
    $inventory=mka_web_session_inventory();if($inventory===null)return null;
    $actor=(string)($_SESSION['MKA_Usuario']??($_SESSION['MM_Usuario']??''));
    return array('users'=>count($inventory['users']),'sessions'=>$inventory['sessions'],'my_sessions'=>(int)($inventory['users'][$actor]??0));
}


function mka_web_session_native_url($admin=null) {
    if($admin===null)$admin=dirname(__DIR__,2);
    $manifest=dirname($admin).'/dados/suite-native-sessions/manifest.json';
    $info=is_file($manifest)?json_decode((string)@file_get_contents($manifest),true):null;
    if(!is_array($info)||!isset($info['original'],$info['source_sha256'],$info['installed_sha256'])||!is_file($info['original'])||hash_file('sha256',$info['original'])!==$info['source_sha256'])return null;
    foreach(array('hhvm','php')as$extension){
        $page=$admin.'/sessoes.'.$extension;$action=$admin.'/executar_sessao.'.$extension;
        if(!is_file($page)||!is_file($action))continue;
        $source=@file_get_contents($page,false,null,0,4096);
        if(is_string($source)&&strpos($source,'mka-suite-native-web-sessions-v1')!==false&&hash_file('sha256',$page)===$info['installed_sha256'])return '/admin/sessoes.'.$extension;
    }
    return null;
}
function mka_web_session_track_current() {
    $actor=(string)($_SESSION['MKA_Usuario']??($_SESSION['MM_Usuario']??''));
    if($actor===''||session_status()!==PHP_SESSION_ACTIVE)return;
    $ip=(string)($_SERVER['REMOTE_ADDR']??'');if(!filter_var($ip,FILTER_VALIDATE_IP))$ip='';
    $old=$_SESSION['suite_web_session_meta']??array();
    $_SESSION['suite_web_session_meta']=array('owner'=>$actor,'agent'=>substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,1024),'ip'=>$ip,'seen'=>time(),'created'=>is_array($old)&&($old['owner']??'')===$actor?($old['created']??time()):time());
}
