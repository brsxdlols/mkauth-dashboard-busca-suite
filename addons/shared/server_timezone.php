<?php
function mka_server_timezone_catalog(){return json_decode(file_get_contents(__DIR__.'/server_timezones.json'),true);}
function mka_server_timezone_config($db){
    $q=$db->query("SELECT valor FROM sis_opcao WHERE nome='suite_server_timezone' LIMIT 1");$r=$q?$q->fetch_assoc():null;$v=$r?json_decode($r['valor'],true):null;
    return is_array($v)?$v:array('mode'=>'auto','zone'=>'America/Sao_Paulo','enabled'=>false);
}
function mka_server_timezone_save($db,$mode,$zone){
    $catalog=mka_server_timezone_catalog();
    if(!in_array($mode,array('auto','manual'),true)||!isset($catalog['zones'][$zone]))throw new InvalidArgumentException('Fuso ou modo inválido.');
    $value=json_encode(array('mode'=>$mode,'zone'=>$zone,'enabled'=>true,'manual_request'=>true,'revision'=>bin2hex(random_bytes(16))));
    $q=$db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('suite_server_timezone',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");$q->bind_param('s',$value);if(!$q->execute())throw new RuntimeException('Falha ao salvar fuso horário.');
}
function mka_server_timezone_snapshot($db){
    $config=mka_server_timezone_config($db);$catalog=mka_server_timezone_catalog();
    $zone=trim((string)@file_get_contents('/etc/timezone'));
    try{$tz=new DateTimeZone($zone);}catch(Throwable $e){$zone='UTC';$tz=new DateTimeZone('UTC');}
    $clock=(new DateTimeImmutable('@'.time()))->setTimezone($tz);
    $status=json_decode((string)@file_get_contents('/var/lib/mkauth-suite-timezone/status.json'),true);
    $q=$db->query('SELECT NOW() AS `local_time`,UTC_TIMESTAMP() AS `utc_time`,TIMESTAMPDIFF(SECOND,UTC_TIMESTAMP(),NOW()) offset_seconds');$mysql=$q?$q->fetch_assoc():null;
    $p=$db->query('SELECT cidade,estado,cep FROM sis_provedor ORDER BY id LIMIT 1');$provider=$p?$p->fetch_assoc():null;
    return array('epoch'=>time(),'server_zone'=>$zone,'server_time'=>$clock->format('d/m/Y H:i:s'),'offset'=>$clock->format('P'),'php_zone'=>date_default_timezone_get(),'mysql'=>$mysql,'config'=>$config,'status'=>$status,'provider'=>$provider);
}
