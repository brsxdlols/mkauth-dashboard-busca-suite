<?php
// Only ephemeral visual fixtures; never connect to the database or alter clients.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$type=$argv[1] ?? ''; $user=$argv[2] ?? '';
if (!in_array($type,array('conexao','pagamento'),true) || !preg_match('/^[a-zA-Z0-9_.@-]{1,64}$/',$user)) {
    fwrite(STDERR,"Uso: php test-popup.php conexao|pagamento USUARIO_ADMIN\n");exit(1);
}
$event=array('id'=>'demo-'.bin2hex(random_bytes(8)),'demo'=>true,'expires'=>time()+90,'name'=>'Cliente de teste');
if ($type==='conexao') {
    $event+=array('type'=>'login','label'=>'TESTE - CLIENTE CONECTOU','login'=>'teste','datetime'=>date('Y-m-d H:i:s'),'formatted_time'=>date('d/m H:i:s'),'concentrator'=>'','contract_status'=>'active','contract_label'=>'Teste visual','contract_icon'=>'bi bi-info-circle','icon'=>'bi bi-box-arrow-in-right');
} else {
    $event+=array('amount'=>'99.90','due'=>date('Y-m-d'),'paid'=>date('Y-m-d H:i:s'),'plan'=>'Plano de teste','description'=>'Teste visual');
}
$dir=__DIR__.'/'.($type==='conexao'?'session-previews':'payment-previews');
if (!is_dir($dir) && !mkdir($dir,0755,true)) exit(1);
$path=$dir.'/'.hash('sha256',$user).'.json';
if (file_put_contents($path,json_encode($event),LOCK_EX)===false) exit(1);
chmod($path,0644);
echo "Previa enviada para $user. Mantenha a dashboard visivel. Expira em 90 segundos.\n";
