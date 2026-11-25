<?php
// Use exactly the native list's directory, decoder and administrative-session key.
function mka_web_session_counts() {
    if(!class_exists('MKAC_SESSAO')||!is_callable(array('MKAC_SESSAO','dir_sessao'))||!is_callable(array('MKAC_SESSAO','unzerialize')))return null;
    try {
        $dir=MKAC_SESSAO::dir_sessao();
        if(!is_string($dir)||!is_dir($dir)||!is_readable($dir))return null;
        $files=glob(rtrim($dir,'/').'/sess_*');if($files===false)return null;
        $users=array();$sessions=0;
        foreach($files as $file){
            if(!is_file($file)||is_link($file))continue;
            $raw=@file_get_contents($file);if($raw===false)continue;
            $data=@MKAC_SESSAO::unzerialize($raw);
            if(!is_array($data)||!isset($data['MKA_Usuario']))continue;
            $sessions++;$user=(string)$data['MKA_Usuario'];if($user!=='')$users[$user]=true;
        }
        return array('users'=>count($users),'sessions'=>$sessions);
    }catch(Throwable $e){return null;}
}
