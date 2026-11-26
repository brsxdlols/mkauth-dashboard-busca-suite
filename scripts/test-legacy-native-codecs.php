<?php
define('MKA_NATIVE_DISCONNECT_TEST',true);
require __DIR__.'/../addons/shared/native_disconnect_clock.php';
$dir=sys_get_temp_dir().'/mka-codec-'.bin2hex(random_bytes(8));mkdir($dir,0700);
$fixture= <<<'PHP'
class MKAC_FREERADIUS {public static function fixture(){global $db;$id=1;$db->query("UPDATE radacct
 SET acctstoptime = DATE_ADD(acctstarttime, interval acctsessiontime second), acctterminatecause = 'Lost-Service'
 WHERE radacctid = '{$id}'");}}
PHP;
$legacy='function FFFLEGACYCODEC($cc){$cd=base64_decode($cc);eval($cd);return $cc;}';
$modern='$unused=1;';
try{
 foreach(array('legacy'=>$legacy,'modern'=>$modern)as$kind=>$first){
  $stage=$kind==='legacy'?"FFFLEGACYCODEC('".base64_encode($fixture)."');":'$body=base64_decode(\''.base64_encode($fixture).'\');eval($body);';
  $template="<?php eval(base64_decode('".base64_encode($first)."'));eval(base64_decode(substr(file_get_contents(__cFILEabc__),%d)));return;?>";
  $offset=0;do{$header=sprintf($template,$offset);$previous=$offset;$offset=strlen($header);}while($offset!==$previous);
  $source=$header.base64_encode($stage);
  $path=$dir.'/'.$kind.'.class';file_put_contents($path,$source);
  unset($GLOBALS['mkaNativeDisconnectBody']);mka_native_disconnect_load($source,$path,true);
  $patched=$GLOBALS['mkaNativeDisconnectBody'];
  if(class_exists('MKAC_FREERADIUS',false)||strpos($patched,'CURRENT_TIMESTAMP()')===false||substr_count($patched,'AND acctstoptime IS NULL')!==1||file_get_contents($path)!==$source)throw new Exception('Codec capture invariant: '.$kind);
  echo 'PASS '.$kind.' capture: original unchanged; no class/business method executed'.PHP_EOL;
 }
 // A changed query must be refused even when its enclosing codec is recognized.
 try{mka_native_disconnect_patch(str_replace('interval acctsessiontime second','interval 1 day',$fixture));throw new LogicException('Unknown query accepted');}catch(RuntimeException$expected){}
 echo 'PASS unknown accounting query refused'.PHP_EOL;
}finally{foreach(glob($dir.'/*')as$p)unlink($p);rmdir($dir);}
