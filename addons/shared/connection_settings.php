<?php
function mka_connection_settings_options($input){
 $result=array('clear'=>$input['clear']??'none','autoip'=>$input['autoip']??'keep','automac'=>$input['automac']??'keep');
 if(!in_array($result['clear'],array('none','ip','mac','both'),true)||!in_array($result['autoip'],array('keep','sim','nao'),true)||!in_array($result['automac'],array('keep','sim','nao'),true))throw new InvalidArgumentException('Opções inválidas.');
 if($result===array('clear'=>'none','autoip'=>'keep','automac'=>'keep'))throw new InvalidArgumentException('Escolha pelo menos uma alteração.');return $result;
}
function mka_connection_settings_rows($db,$scope,$login=null,$lock=false){
 $sql="SELECT c.id,c.login,c.ip,c.mac,c.autoip,c.automac FROM sis_cliente c WHERE ($scope)".($login!==null?' AND c.login=?':'').' ORDER BY c.id'.($lock?' FOR UPDATE':'');
 $q=$db->prepare($sql);if($login!==null)$q->bind_param('s',$login);$q->execute();return $q->get_result()->fetch_all(MYSQLI_ASSOC);
}
function mka_connection_settings_changed($row,$options){
 return (in_array($options['clear'],array('ip','both'),true)&&$row['ip']!==null)||(in_array($options['clear'],array('mac','both'),true)&&$row['mac']!==null)||($options['autoip']!=='keep'&&$row['autoip']!==$options['autoip'])||($options['automac']!=='keep'&&$row['automac']!==$options['automac']);
}
function mka_connection_settings_snapshot($rows){return hash('sha256',json_encode($rows,JSON_INVALID_UTF8_SUBSTITUTE));}
function mka_connection_settings_schema($db){
 $db->query("CREATE TABLE IF NOT EXISTS suite_connection_operations(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,operator VARCHAR(64) NOT NULL,request_key VARCHAR(96) NOT NULL,selection VARCHAR(16) NOT NULL,client_login VARCHAR(64) NULL,options_json TEXT NOT NULL,changed_count INT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY once_only(operator,request_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $db->query("CREATE TABLE IF NOT EXISTS suite_connection_operation_items(operation_id BIGINT UNSIGNED NOT NULL,client_id INT NOT NULL,login VARCHAR(64) NOT NULL,previous_ip VARCHAR(64) NULL,previous_mac VARCHAR(64) NULL,previous_autoip VARCHAR(8) NULL,previous_automac VARCHAR(8) NULL,PRIMARY KEY(operation_id,client_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mka_connection_settings_execute($db,$operator,$scope,$preview){
 $db->begin_transaction();try{
  $q=$db->prepare('SELECT id,changed_count FROM suite_connection_operations WHERE operator=? AND request_key=? LIMIT 1');$q->bind_param('ss',$operator,$preview['key']);$q->execute();$old=$q->get_result()->fetch_assoc();if($old){$db->commit();return array('operation_id'=>(int)$old['id'],'changed'=>(int)$old['changed_count']);}
  $rows=mka_connection_settings_rows($db,$scope,$preview['login'],true);
  if(!hash_equals($preview['snapshot'],mka_connection_settings_snapshot($rows)))throw new UnexpectedValueException('Os cadastros mudaram desde a prévia. Gere uma nova prévia.');
  $options=$preview['options'];$changed=array_values(array_filter($rows,function($row)use($options){return mka_connection_settings_changed($row,$options);}));$total=count($changed);$json=json_encode($options);$selection=$preview['login']===null?'all':'client';
  $q=$db->prepare('INSERT INTO suite_connection_operations(operator,request_key,selection,client_login,options_json,changed_count) VALUES(?,?,?,?,?,?)');$q->bind_param('sssssi',$operator,$preview['key'],$selection,$preview['login'],$json,$total);$q->execute();$operation=(int)$db->insert_id;
  $insert=$db->prepare('INSERT INTO suite_connection_operation_items(operation_id,client_id,login,previous_ip,previous_mac,previous_autoip,previous_automac) VALUES(?,?,?,?,?,?,?)');
  foreach($changed as $row){$insert->bind_param('iisssss',$operation,$row['id'],$row['login'],$row['ip'],$row['mac'],$row['autoip'],$row['automac']);$insert->execute();}
  if($total){$sets=array();if(in_array($options['clear'],array('ip','both'),true))$sets[]='ip=NULL';if(in_array($options['clear'],array('mac','both'),true))$sets[]='mac=NULL';foreach(array('autoip','automac') as $field)if($options[$field]!=='keep')$sets[]=$field."='".$options[$field]."'";$ids=implode(',',array_map(function($row){return (int)$row['id'];},$changed));$db->query('UPDATE sis_cliente SET '.implode(',',$sets).' WHERE id IN ('.$ids.')');}
  $db->commit();return array('operation_id'=>$operation,'changed'=>$total);
 }catch(Throwable $e){$db->rollback();throw $e;}
}
