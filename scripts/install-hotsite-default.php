<?php
function mka_install_hotsite_default($db,$layout,$table='sis_opcao',$backup=null){
 if(!preg_match('/^[a-z0-9_]+$/D',$table))throw new InvalidArgumentException('Tabela invalida');
 if(!is_dir($layout))return '[aviso] Hotsite VPSCLOUD WhatsApp nao encontrado em '.$layout.'. Tema atual preservado.';
 $r=$db->query("SELECT valor FROM `$table` WHERE nome='layhotsite' LIMIT 1");
 if(!$r)throw new RuntimeException('Falha ao consultar tema do hotsite.');
 $row=$r->fetch_assoc();$old=$row?(string)$row['valor']:'';
 if(!in_array($old,array('','padrao','padrao_antigo','layout-vpscloud-whatsapp'),true))return '[hotsite] Tema personalizado preservado: '.$old;
 if($backup!==null){if(!is_dir($backup))mkdir($backup,0700,true);file_put_contents($backup.'/hotsite-before.json',json_encode(array('existed'=>(bool)$row,'value'=>$old)));chmod($backup.'/hotsite-before.json',0600);}
 if(!$db->query("INSERT INTO `$table`(nome,valor) VALUES('layhotsite','layout-vpscloud-whatsapp') ON DUPLICATE KEY UPDATE valor=VALUES(valor)"))throw new RuntimeException('Falha ao selecionar hotsite.');
 return '[hotsite] Layout VPSCLOUD WhatsApp selecionado e conferido.';
}
if(PHP_SAPI==='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
 require_once __DIR__.'/installer-database.php';$db=mka_installer_database_connect();
 echo mka_install_hotsite_default($db,'/var/www/layout/layout-vpscloud-whatsapp','sis_opcao',$argv[1]??null).PHP_EOL;
}
