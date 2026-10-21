<?php
function mka_invoice_notes_schema($db){
 $db->query("CREATE TABLE IF NOT EXISTS suite_invoice_financial_notes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,invoice_id INT NOT NULL,client_uuid VARCHAR(64) NOT NULL,login VARCHAR(64) NOT NULL,
 note TEXT NOT NULL,promised_on DATE NULL,protected_until DATE NULL,kind VARCHAR(12) NOT NULL,operator VARCHAR(64) NOT NULL,
 request_key VARCHAR(96) NOT NULL,payload_hash CHAR(64) NOT NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY request_once(operator,request_key),KEY invoice_history(invoice_id,id),KEY client_history(client_uuid,id)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mka_invoice_notes_available($db){try{return (bool)@$db->query('SELECT 1 FROM suite_invoice_financial_notes LIMIT 0');}catch(Throwable $e){return false;}}
function mka_invoice_note_client($db,$uuid,$id,$scope,$lock=false){
 $q=$db->prepare("SELECT c.id,c.uuid_cliente,c.login,c.nome,c.observacao,c.rem_obs,l.id invoice_id FROM sis_cliente c INNER JOIN sis_lanc l ON l.login=c.login WHERE c.uuid_cliente=? AND l.id=? AND ($scope) AND l.status IN ('aberto','vencido') AND COALESCE(l.deltitulo,0)=0 LIMIT 1".($lock?' FOR UPDATE':''));
 $q->bind_param('si',$uuid,$id);$q->execute();$row=$q->get_result()->fetch_assoc();$q->close();
 if(!$row)throw new DomainException('Fatura não disponível para este cliente.');return $row;
}
function mka_invoice_note_history($db,$uuid,$id){
 if(!mka_invoice_notes_available($db))return array();
 $q=$db->prepare('SELECT id,note,promised_on,protected_until,kind,operator,created_at FROM suite_invoice_financial_notes WHERE client_uuid=? AND invoice_id=? ORDER BY id DESC LIMIT 20');$q->bind_param('si',$uuid,$id);$q->execute();return $q->get_result()->fetch_all(MYSQLI_ASSOC);
}
function mka_invoice_note_latest($db,$uuid,$ids){
 if(!$ids||!mka_invoice_notes_available($db))return array();$ids=implode(',',array_map('intval',$ids));
 $q=$db->prepare("SELECT n.* FROM suite_invoice_financial_notes n WHERE n.client_uuid=? AND n.invoice_id IN ($ids) AND n.id=(SELECT MAX(n2.id) FROM suite_invoice_financial_notes n2 WHERE n2.invoice_id=n.invoice_id)");$q->bind_param('s',$uuid);$q->execute();$result=array();foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row)$result[(int)$row['invoice_id']]=$row;return $result;
}
function mka_invoice_note_counts($db,$uuids){
 if(!$uuids||!mka_invoice_notes_available($db))return array();$safe=array();foreach($uuids as $uuid)$safe[]="'".$db->real_escape_string($uuid)."'";
 $q=$db->query("SELECT n.client_uuid,COUNT(DISTINCT n.invoice_id) total FROM suite_invoice_financial_notes n INNER JOIN sis_lanc l ON l.id=n.invoice_id INNER JOIN sis_cliente c ON c.uuid_cliente=n.client_uuid AND c.login=l.login WHERE n.client_uuid IN (".implode(',',$safe).") AND n.kind='note' AND l.status IN ('aberto','vencido') AND COALESCE(l.deltitulo,0)=0 AND n.id=(SELECT MAX(n2.id) FROM suite_invoice_financial_notes n2 WHERE n2.invoice_id=n.invoice_id) GROUP BY n.client_uuid");$result=array();while($row=$q->fetch_assoc())$result[$row['client_uuid']]=(int)$row['total'];return $result;
}
function mka_invoice_note_filter_sql($db){
 if(!mka_invoice_notes_available($db))return '0=1';
 return "EXISTS(SELECT 1 FROM suite_invoice_financial_notes fn INNER JOIN sis_lanc fl ON fl.id=fn.invoice_id WHERE fn.client_uuid=c.uuid_cliente AND fl.login=c.login AND fn.kind='note' AND fl.status IN ('aberto','vencido') AND COALESCE(fl.deltitulo,0)=0 AND fn.id=(SELECT MAX(fn2.id) FROM suite_invoice_financial_notes fn2 WHERE fn2.invoice_id=fn.invoice_id))";
}
function mka_invoice_note_filter_query($sql,$predicate){
 $filtered=preg_replace('/\bWHERE\b/i','WHERE ('.$predicate.') AND ',$sql,1,$count);
 if($count!==1)throw new RuntimeException('Consulta de clientes sem filtro inicial.');return $filtered;
}
function mka_invoice_note_validate($input,$today,$settings){
 $text=trim((string)($input['note']??''));$kind=($input['action']??'save')==='resolve'?'resolved':'note';
 if($kind==='resolved'&&$text==='')$text='Aviso financeiro concluído.';
 if($text===''||preg_match_all('/./us',$text)>4000)throw new InvalidArgumentException('Informe a nota com até 4000 caracteres.');
 $date=trim((string)($input['promised_on']??''));$protect=($input['protect']??'0')==='1';$remove=($input['remove_protection']??'0')==='1';if($remove&&$protect)throw new InvalidArgumentException('Escolha conceder ou remover o prazo.');
 if($date!==''){$d=DateTime::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date)throw new InvalidArgumentException('Data de pagamento inválida.');}
 if($protect){
  if($kind!=='note'||$date===''||$date<$today)throw new InvalidArgumentException('Escolha uma previsão a partir de hoje para conceder prazo.');
  $days=(int)(new DateTime($today))->diff(new DateTime($date))->format('%r%a');
  if($days>365)throw new InvalidArgumentException('O prazo deve ser de até 365 dias.');
  if($settings['mode']==='fixed'&&$days!==(int)$settings['fixed_days'])throw new InvalidArgumentException('O desbloqueio está configurado para '.$settings['fixed_days'].' dia(s). Ajuste a previsão ou a configuração.');
  if($settings['mode']==='days'&&($days<1||$days>10))throw new InvalidArgumentException('O desbloqueio está configurado para períodos de 1 a 10 dias.');
 }
 return array('note'=>$text,'kind'=>$kind,'promised_on'=>$date===''?null:$date,'protect'=>$protect,'remove_protection'=>$remove);
}
function mka_invoice_note_save($db,$uuid,$id,$scope,$operator,$input,$canProtect,$settings){
 $data=mka_invoice_note_validate($input,date('Y-m-d'),$settings);$key=(string)($input['request_key']??'');
 if(!preg_match('/^[a-zA-Z0-9_-]{16,96}$/D',$key))throw new InvalidArgumentException('Identificador da operação inválido.');
 if(($data['protect']||$data['remove_protection'])&&!$canProtect)throw new DomainException('Sem permissão para conceder prazo de confiança.');
 $hash=hash('sha256',json_encode(array($uuid,$id,$data)));$db->begin_transaction();
 try{
  $client=mka_invoice_note_client($db,$uuid,$id,$scope,true);
  $q=$db->prepare('SELECT id,payload_hash FROM suite_invoice_financial_notes WHERE operator=? AND request_key=? LIMIT 1');$q->bind_param('ss',$operator,$key);$q->execute();$existing=$q->get_result()->fetch_assoc();
  if($existing){if($existing['payload_hash']!==$hash)throw new UnexpectedValueException('Esta operação já foi usada. Reabra a nota.');$db->commit();return (int)$existing['id'];}
  $until=null;
  if($data['remove_protection']){
   if(($input['expected_protection_until']??'')!==(string)$client['rem_obs'])throw new UnexpectedValueException('O prazo mudou. Reabra a nota antes de remover.');
   $q=$db->prepare("UPDATE sis_cliente SET observacao='nao',rem_obs=NULL WHERE uuid_cliente=?");$q->bind_param('s',$uuid);$q->execute();
   $data['note'].=' Prazo de confiança do cadastro removido por solicitação do operador.';
  }
  if($data['protect']){
   if($client['observacao']==='sim'&&(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/',(string)$client['rem_obs'])||substr($client['rem_obs'],0,10)==='0000-00-00'))throw new UnexpectedValueException('O cadastro já está em observação sem data final. Registre apenas a nota ou revise a observação existente.');
   $days=max(1,(int)$settings['recent_days']);$q=$db->prepare("SELECT id FROM dashboard_am_trust_unlock_audit WHERE client_login=? AND created_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01') AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) ORDER BY id DESC LIMIT 1");$q->bind_param('s',$client['login']);$q->execute();
   if($q->get_result()->fetch_row()&&($input['confirm_recent']??'')!=='1')throw new UnexpectedValueException('Este cliente recebeu prazo recentemente. Confirme o aviso antes de prosseguir.');
   $until=$data['promised_on'];if($client['observacao']==='sim'&&substr((string)$client['rem_obs'],0,10)>$until)$until=substr($client['rem_obs'],0,10);
   $q=$db->prepare("UPDATE sis_cliente SET observacao='sim',rem_obs=? WHERE uuid_cliente=?");$q->bind_param('ss',$until,$uuid);$q->execute();
   $q=$db->prepare('INSERT INTO dashboard_am_trust_unlock_audit(client_id,client_login,client_name,unlocked_until,performed_by) VALUES(?,?,?,?,?)');$q->bind_param('issss',$client['id'],$client['login'],$client['nome'],$until,$operator);$q->execute();
  }
  $q=$db->prepare('INSERT INTO suite_invoice_financial_notes(invoice_id,client_uuid,login,note,promised_on,protected_until,kind,operator,request_key,payload_hash) VALUES(?,?,?,?,?,?,?,?,?,?)');$q->bind_param('isssssssss',$id,$uuid,$client['login'],$data['note'],$data['promised_on'],$until,$data['kind'],$operator,$key,$hash);$q->execute();$result=(int)$db->insert_id;$db->commit();return $result;
 }catch(Throwable $e){$db->rollback();throw $e;}
}
