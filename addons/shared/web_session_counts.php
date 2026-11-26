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
            $entries[]=array('user'=>$user,'agent'=>(string)($data['MKA_Navegador']??''),'ip'=>(string)($data['MKA_IP']??''),'updated'=>filemtime($file));
        }
        return array('users'=>$users,'sessions'=>$sessions,'entries'=>$entries);
    }catch(Throwable $e){return null;}
}
function mka_web_session_counts() {
    $inventory=mka_web_session_inventory();if($inventory===null)return null;
    $actor=(string)($_SESSION['MKA_Usuario']??($_SESSION['MM_Usuario']??''));
    return array('users'=>count($inventory['users']),'sessions'=>$inventory['sessions'],'my_sessions'=>(int)($inventory['users'][$actor]??0));
}

