<?php
define('MKA_WEB_SESSIONS_TEST',true);require __DIR__.'/../addons/shared/native_web_sessions.php';
$source= <<<'PHP'
include('sessao_admin.hhvm');$row=MKAC_SESSAO::unzerialize(file_get_contents($file));echo '<strong> SO: </strong> ';echo 'Windows';echo '<a href="executar_sessao.hhvm">Deslogar</a>';
PHP;
$patched=mka_web_sessions_patch($source);
if(strpos($patched,"include('sessao_admin.hhvm')")!==0||strpos($patched,'executar_sessao.hhvm')===false)throw new RuntimeException('Native authentication/logout changed');
$patched=str_replace("include('sessao_admin.hhvm');",'', $patched);
class MKAC_SESSAO{static function unzerialize($unused){return array('MKA_Usuario'=>'alice<script>&"');}}
$file=__FILE__;ob_start();eval($patched);$html=ob_get_clean();
if(strpos($html,'Usu&aacute;rio:')===false||strpos($html,'alice&lt;script&gt;&amp;&quot;')===false||strpos($html,'alice<script>')!==false)throw new RuntimeException('Owner not escaped');
try{mka_web_sessions_patch('unknown native source');throw new LogicException('Unknown source accepted');}catch(RuntimeException $e){}
echo "PASS native owner label, escaping, authentication/logout preservation and incompatible-source rejection\n";
