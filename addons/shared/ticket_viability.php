<?php
require_once __DIR__.'/agenda_settings.php';
function mka_viability_schema($db){if($db->query("SHOW TABLES LIKE 'suite_ticket_viability'")->num_rows)return;$db->query("CREATE TABLE IF NOT EXISTS suite_ticket_viability(ticket_id INT PRIMARY KEY,operator VARCHAR(64) NOT NULL,request_key VARCHAR(96) NOT NULL,payload_hash CHAR(64) NOT NULL,details TEXT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY request_once(operator,request_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
function mka_viability_scope($db,$alias='s',$operator=null){
 $operator=$operator??(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');if($operator==='')return '1=0';mka_viability_schema($db);$user=$db->real_escape_string($operator);return "EXISTS(SELECT 1 FROM suite_ticket_viability vf WHERE vf.ticket_id=$alias.id AND vf.operator='$user')";
}
function mka_viability_save($db,$operator,$data){
 $fields=array('name','contact','address','district','city','complement','observation');$details=array();foreach($fields as $key){$value=$data[$key]??'';if(!is_string($value)||strlen($value)>($key==='observation'?8000:255))throw new InvalidArgumentException('Dados acima do limite permitido.');$details[$key]=trim($value);}
 $key=$data['key']??'';if(!is_string($key)||!preg_match('/^[a-zA-Z0-9_-]{12,96}$/D',$key))throw new InvalidArgumentException('Identificador inválido.');
 $day=$data['day']??'';$time=$data['time']??'';$period=$data['period']??'';if(!is_string($day)||!is_string($time)||!preg_match('/^20\d\d-\d\d-\d\d$/D',$day)||!preg_match('/^\d\d:\d\d$/D',$time))throw new InvalidArgumentException('Data ou horário inválido.');
 $visit=DateTimeImmutable::createFromFormat('!Y-m-d H:i',$day.' '.$time);if(!$visit||$visit->format('Y-m-d H:i')!==$day.' '.$time)throw new InvalidArgumentException('Data inválida.');
 $lunch=mka_agenda_settings($db);if(!mka_agenda_workday($lunch,$day)||($time!=='00:00'&&mka_agenda_lunch($lunch,$time)))throw new InvalidArgumentException('Horário reservado ou dia sem expediente.');
 if($time==='00:00'&&!in_array($period,array('manha','tarde'),true))throw new InvalidArgumentException('Escolha o período.');
 $technician=$data['technician']??null;if(!is_string($technician)||!ctype_digit($technician))throw new InvalidArgumentException('Selecione o técnico ou Não informar.');$tech=(int)$technician;if($tech){$q=$db->prepare('SELECT id FROM sis_func WHERE id=?');$q->bind_param('i',$tech);$q->execute();if(!$q->get_result()->fetch_row())throw new InvalidArgumentException('Técnico inválido.');}
 $priority=$data['priority']??'normal';if(!is_string($priority)||strlen($priority)>20||trim($priority)==='')throw new InvalidArgumentException('Prioridade inválida.');
 $packed=json_encode($details,JSON_UNESCAPED_UNICODE);$hash=hash('sha256',json_encode(array($details,$day,$time,$period,$tech,$priority)));mka_viability_schema($db);$db->begin_transaction();
 try{
  $q=$db->prepare('SELECT ticket_id,payload_hash FROM suite_ticket_viability WHERE operator=? AND request_key=? FOR UPDATE');$q->bind_param('ss',$operator,$key);$q->execute();$prior=$q->get_result()->fetch_assoc();if($prior){if(!hash_equals($prior['payload_hash'],$hash))throw new InvalidArgumentException('Envio já utilizado com outros dados.');$db->commit();return (int)$prior['ticket_id'];}
  $uuid=bin2hex(random_bytes(16));$reference=date('dmYHis').random_int(1000,9999);$name=$details['name']!==''?$details['name']:'Viabilidade · Sem nome informado';$subject='Suporte de viabilidade';$opening=date('Y-m-d H:i:s');$scheduled=$visit->format('Y-m-d H:i').':15';
  $q=$db->prepare("INSERT INTO sis_suporte(uuid_suporte,chamado,nome,login,assunto,abertura,visita,prioridade,tecnico,atendente,login_atend,status,ramal,reply) VALUES(?,?,?,'',?,?,?,?,?,?,?,'aberto','todos','nao')");$q->bind_param('sssssssiss',$uuid,$reference,$name,$subject,$opening,$scheduled,$priority,$tech,$operator,$operator);$q->execute();$id=(int)$db->insert_id;
  $notes='Suporte de viabilidade';$labels=array('name'=>'Nome','contact'=>'Contato','address'=>'Endereço','district'=>'Bairro','city'=>'Cidade','complement'=>'Complemento','observation'=>'Observação');foreach($labels as $field=>$label)if($details[$field]!=='')$notes.="\n".$label.': '.$details[$field];if($time==='00:00')$notes.="\n[Periodo da visita: ".$period.'; sem horario definido]';
  $q=$db->prepare("INSERT INTO sis_msg(login,atendente,chamado,msg,tipo,msg_data) VALUES(?,?,?,?,'provedor',NOW())");$q->bind_param('ssss',$operator,$operator,$reference,$notes);$q->execute();
  $q=$db->prepare('INSERT INTO suite_ticket_viability(ticket_id,operator,request_key,payload_hash,details) VALUES(?,?,?,?,?)');$q->bind_param('issss',$id,$operator,$key,$hash,$packed);$q->execute();$db->commit();return $id;
 }catch(Throwable $e){$db->rollback();throw $e;}
}
