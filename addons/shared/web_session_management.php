<?php
// Session identifiers stay on the server; the browser receives expiring action tokens.
function mka_web_session_remove_file($directory,$filename,$owner,$actor,$canManage,$currentFile) {
    if(ini_get('session.save_handler')!=='files')throw new RuntimeException('Armazenamento de sessões incompatível; ação preservada.');
    if($filename===$currentFile)throw new RuntimeException('Esta é a sessão atual. Use Sair para encerrá-la.');
    if($actor!==$owner&&!$canManage)throw new RuntimeException('Sem permissão para deslogar outro usuário.');
    if(!preg_match('/^sess_[A-Za-z0-9,-]{16,128}$/D',$filename))throw new RuntimeException('Sessão inválida.');
    $directory=rtrim($directory,'/');if(realpath($directory)!==$directory)throw new RuntimeException('Diretório de sessões não reconhecido.');
    $path=$directory.'/'.$filename;if(is_link($path)||!is_file($path))throw new RuntimeException('Essa sessão já foi encerrada.');
    $file=@fopen($path,'rb');if(!$file)throw new RuntimeException('Não foi possível acessar a sessão.');
    try {
        if(!flock($file,LOCK_EX|LOCK_NB))throw new RuntimeException('Sessão em uso neste instante. Tente novamente.');
        $stat=fstat($file);clearstatcache(true,$path);$named=@lstat($path);
        if(!$named||is_link($path)||$named['ino']!==$stat['ino']||$named['dev']!==$stat['dev'])throw new RuntimeException('Sessão alterada; atualize a lista.');
        $raw=stream_get_contents($file);$decoder=is_callable(array('MKAC_SESSAO','unzerialize'))?'unzerialize':'unserialize';
        $data=@call_user_func(array('MKAC_SESSAO',$decoder),$raw);
        if(!is_array($data)||empty($data['MKA_Logado'])||(string)($data['MKA_Usuario']??'')!==$owner)throw new RuntimeException('Sessão alterada; atualize a lista.');
        if(!@unlink($path))throw new RuntimeException('O servidor não permitiu encerrar essa sessão.');
    } finally {flock($file,LOCK_UN);fclose($file);}
}
function mka_web_session_os($agent) {
    foreach(array('iPhone'=>'iPhone / iOS','iPad'=>'iPad / iOS','Android'=>'Android','Windows'=>'Windows','Macintosh'=>'macOS','Linux'=>'Linux')as$match=>$name)if(stripos($agent,$match)!==false)return $name;
    return $agent===''?'Não registrado':'Outro sistema';
}
