<?php
require $argv[1];
$root=sys_get_temp_dir().'/mka-invoice-routes-'.bin2hex(random_bytes(6));mkdir($root,0700);
try{
 $uuid='aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';touch($root.'/titulo.hhvm');touch($root.'/titulo_info.hhvm');touch($root.'/prepara_boleto.hhvm');
 if(mka_client_invoice_action_url('receive',47939,$uuid,$root)!=='/admin/titulo.hhvm?tipo=baixar&id=47939')throw new Exception('Legacy receive route');
 if(mka_client_invoice_action_url('edit',47939,$uuid,$root)!=='/admin/titulo.hhvm?tipo=alttitulo&id=47939')throw new Exception('Legacy edit route');
 touch($root.'/receber.php');touch($root.'/titulo_alt.hhvm');
 if(mka_client_invoice_action_url('receive',47939,$uuid,$root)!=='/admin/receber.php?titulo='.$uuid)throw new Exception('Mixed extension receive route');
 if(mka_client_invoice_action_url('edit',47939,$uuid,$root)!=='/admin/titulo_alt.hhvm?titulo='.$uuid)throw new Exception('Modern edit route');
 if(mka_client_invoice_action_url('receive',47939,'invalid',$root)!==null)throw new Exception('Invalid UUID accepted');
 if(mka_client_invoice_action_url('unknown',47939,$uuid,$root)!==null)throw new Exception('Unexpected action accepted');
 touch($root.'/send_titulo.php');
 if(mka_client_invoice_action_url('send',47939,$uuid,$root)!=='/admin/send_titulo.php')throw new Exception('Native send composer route');
 echo "PASS legacy numeric IDs, modern UUIDs, mixed extensions and missing/invalid routes\n";
}finally{foreach(glob($root.'/*') as $file)unlink($file);rmdir($root);}
