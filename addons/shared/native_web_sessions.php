<?php
// Add the owner to the vendor's own authenticated session cards.
function mka_web_sessions_patch($source) {
    if(substr_count($source,"include('sessao_admin.hhvm')")!==1 || substr_count($source,'<strong> SO: </strong>')!==1 ||
       !preg_match('/(\$[A-Za-z_]\w*)\s*=\s*MKAC_SESSAO::unzerialize\(file_get_contents\(/',$source,$match)) {
        throw new RuntimeException('Lista nativa de sessoes incompativel; original preservado.');
    }
    $owner=$match[1]."['MKA_Usuario']";
    $assignment='/'.preg_quote($match[1],'/').'\s*=\s*MKAC_SESSAO::unzerialize\(file_get_contents\([^;]+;/';
    $guard="if((\$_GET['scope']??'')==='mine' && ((string)(\$_SESSION['MKA_Usuario']??(\$_SESSION['MM_Usuario']??''))==='' || (string)(".$owner."??'')!==(string)(\$_SESSION['MKA_Usuario']??(\$_SESSION['MM_Usuario']??''))))continue;";
    $source=preg_replace_callback($assignment,function($row)use($guard){return $row[0].$guard;},$source,-1,$count);
    if($count!==1)throw new RuntimeException('Filtro nativo de sessoes incompativel; original preservado.');
    $label="<strong> Usu&aacute;rio: </strong> ';echo htmlspecialchars((string)(".$owner." ?? ''),ENT_QUOTES|ENT_SUBSTITUTE,'ISO-8859-1');echo '<br />\n<strong> SO: </strong>";
    return str_replace('<strong> SO: </strong>',$label,$source);
}
if(defined('MKA_WEB_SESSIONS_TEST'))return;
if(!isset($mkaSessionsOriginal,$mkaSessionsOriginalHash) || !is_file($mkaSessionsOriginal) || !hash_equals($mkaSessionsOriginalHash,hash_file('sha256',$mkaSessionsOriginal))) {
    http_response_code(503);exit('Lista de sessoes indisponivel. Revise a instalacao.');
}
require_once __DIR__.'/native_activity_performance.php';
$mkaSessionsCodec=file_get_contents($mkaSessionsOriginal);
eval('?>'.mka_activity_decoder_source($mkaSessionsCodec,$mkaSessionsOriginal));
$mkaSessionsCode=mka_web_sessions_patch($mkaActivityDecoded);
chdir(dirname(__DIR__,2));
eval($mkaSessionsCode);
