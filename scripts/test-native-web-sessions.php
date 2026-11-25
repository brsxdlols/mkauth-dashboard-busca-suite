<?php
define('MKA_WEB_SESSIONS_TEST',true);require __DIR__.'/../addons/shared/native_web_sessions.php';
$source= <<<'PHP'
include('sessao_admin.hhvm');foreach($files as $file){$row=MKAC_SESSAO::unzerialize(file_get_contents($file));echo '<strong> SO: </strong> ';echo 'Windows';echo '<a href="executar_sessao.hhvm?acao=deslogar&file=fixture">Deslogar</a>';}
PHP;
$patched=mka_web_sessions_patch($source);
if(strpos($patched,"include('sessao_admin.hhvm')")!==0||strpos($patched,'executar_sessao.hhvm')===false)throw new RuntimeException('Native authentication/logout changed');
$patched=str_replace("include('sessao_admin.hhvm');",'', $patched);
class MKAC_SESSAO{static function unzerialize($raw){return json_decode($raw,true);}}
function render_sessions_fixture($code,$files){ob_start();eval($code);return ob_get_clean();}
$directory=sys_get_temp_dir().'/sessions-filter-'.bin2hex(random_bytes(8));mkdir($directory,0700);$files=array();
try{
    foreach(array('alice<script>&"','alice<script>&"','bob')as$i=>$user){$file=$directory.'/'.$i;file_put_contents($file,json_encode(array('MKA_Usuario'=>$user)));$files[]=$file;}
    $_SESSION=array('MKA_Usuario'=>'alice<script>&"');$_GET=array();$html=render_sessions_fixture($patched,$files);
    if(substr_count($html,'Usu&aacute;rio:')!==3||strpos($html,'alice&lt;script&gt;&amp;&quot;')===false||strpos($html,'alice<script>')!==false)throw new RuntimeException('Owner list/escaping invalid');
    if(substr_count($html,'onclick="return window.confirm(')!==3||substr_count($html,'href="executar_sessao.hhvm?acao=deslogar&file=fixture"')!==3||strpos($html,'data-mka-session-user="alice&lt;script&gt;&amp;&quot;"')===false)throw new RuntimeException('Native action or escaped confirmation changed');
    if(isset($argv[1]))file_put_contents($argv[1],$html);
    $_GET=array('scope'=>'mine');$mine=render_sessions_fixture($patched,$files);
    if(substr_count($mine,'Usu&aacute;rio:')!==2||strpos($mine,'bob')!==false)throw new RuntimeException('Foreign session leaked into mine');
    $_SESSION=array();if(render_sessions_fixture($patched,$files)!=='')throw new RuntimeException('Empty actor must not match foreign sessions');
    try{mka_web_sessions_patch('unknown native source');throw new LogicException('Unknown source accepted');}catch(RuntimeException $e){}
    echo "PASS owner escaping, mine-only filter, empty-actor rejection and native authentication/logout preservation\n";
}finally{foreach($files as$file)unlink($file);rmdir($directory);}
