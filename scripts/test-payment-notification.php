<?php
// CLI-only visual preview: no invoice, payment or customer is modified.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$user=$argv[1]??'';
if(!preg_match('/^[a-zA-Z0-9_.@-]{1,64}$/',$user)){fwrite(STDERR,"Uso: php test-payment-notification.php usuario\n");exit(1);}
$dir='/opt/mk-auth/admin/addons/shared/payment-previews';
if(!is_dir($dir) && !mkdir($dir,0755,true))exit(1);
$event=array('id'=>'preview-'.bin2hex(random_bytes(8)),'name'=>'Cliente de demonstração','amount'=>'99.90','due'=>date('Y-m-d'),'paid'=>date('Y-m-d H:i:s'),'plan'=>'Internet Fibra 600 Mega','description'=>'Mensalidade de demonstração','demo'=>true,'expires'=>time()+90);
$path=$dir.'/'.hash('sha256',$user).'.json';
if(file_put_contents($path,json_encode($event),LOCK_EX)===false)exit(1);
chmod($path,0644);
echo "Teste visual enviado para $user. Deixe uma aba do MK-AUTH visível. Expira em 90 segundos; nenhuma cobrança foi alterada.\n";
