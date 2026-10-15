<?php
function mka_attendance_schema($db) {
    $db->query("CREATE TABLE IF NOT EXISTS suite_support_attendance (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        client_uuid VARCHAR(64) NOT NULL, login VARCHAR(255) NOT NULL, client_name VARCHAR(255) NOT NULL,
        operator VARCHAR(64) NOT NULL, subject VARCHAR(255) NOT NULL, notes TEXT NOT NULL,
        outcome VARCHAR(12) NOT NULL, request_key VARCHAR(96) NOT NULL, payload_hash CHAR(64) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY request_once(operator,request_key), KEY client_history(client_uuid,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach(array('version'=>'INT NOT NULL DEFAULT 1','updated_at'=>'DATETIME NULL','updated_by'=>'VARCHAR(64) NULL') as $name=>$definition){
        $found=$db->query("SHOW COLUMNS FROM suite_support_attendance LIKE '$name'");
        if($found->num_rows===0)$db->query("ALTER TABLE suite_support_attendance ADD COLUMN `$name` $definition");
    }
    $db->query("CREATE TABLE IF NOT EXISTS suite_support_attendance_changes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, attendance_id BIGINT UNSIGNED NOT NULL,
        operator VARCHAR(64) NOT NULL, action VARCHAR(12) NOT NULL, before_data MEDIUMTEXT NOT NULL, after_data MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY attendance_history(attendance_id,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mka_attendance_update($db,$id,$operator,$scope,$canAdmin,$version,$action,$subject,$notes,$outcome) {
    if(!in_array($action,array('edit','reopen','finish'),true))throw new InvalidArgumentException('Operacao invalida.');
    $db->begin_transaction();
    try{
        $statement=$db->prepare("SELECT a.* FROM suite_support_attendance a INNER JOIN sis_cliente c ON c.uuid_cliente=a.client_uuid WHERE a.id=? AND ($scope) FOR UPDATE");
        $statement->bind_param('i',$id);$statement->execute();$before=$statement->get_result()->fetch_assoc();
        if(!$before || (!$canAdmin && $before['operator']!==$operator))throw new DomainException('Sem permissao para alterar este atendimento.');
        if((int)$before['version']!==(int)$version)throw new UnexpectedValueException('Este registro foi alterado. Abra-o novamente antes de salvar.');
        if($action==='reopen'){$subject=$before['subject'];$notes=$before['notes'];$outcome='open';}
        elseif($action==='edit')$outcome=$before['outcome'];
        elseif($before['outcome']!=='open' || !in_array($outcome,array('resolved','finished','service'),true))throw new InvalidArgumentException('Reabra o atendimento antes de conclui-lo novamente.');
        if(trim($subject)==='' || preg_match_all('/./us',$subject)>255 || trim($notes)==='' || preg_match_all('/./us',$notes)>8000)throw new InvalidArgumentException('Preencha assunto e relato dentro dos limites indicados.');
        $statement=$db->prepare('UPDATE suite_support_attendance SET subject=?,notes=?,outcome=?,updated_by=?,updated_at=NOW(),version=version+1 WHERE id=? AND version=?');
        $statement->bind_param('ssssii',$subject,$notes,$outcome,$operator,$id,$version);$statement->execute();
        if($statement->affected_rows!==1)throw new UnexpectedValueException('Registro alterado por outro operador.');
        $old=json_encode(array('subject'=>$before['subject'],'notes'=>$before['notes'],'outcome'=>$before['outcome'],'version'=>(int)$before['version']),JSON_UNESCAPED_UNICODE);
        $new=json_encode(array('subject'=>$subject,'notes'=>$notes,'outcome'=>$outcome,'version'=>(int)$version+1),JSON_UNESCAPED_UNICODE);
        $statement=$db->prepare('INSERT INTO suite_support_attendance_changes(attendance_id,operator,action,before_data,after_data) VALUES(?,?,?,?,?)');
        $statement->bind_param('issss',$id,$operator,$action,$old,$new);$statement->execute();
        $db->commit();return array('id'=>$id,'outcome'=>$outcome);
    }catch(Throwable $error){$db->rollback();throw $error;}
}
function mka_attendance_scope($db,$user) {
    $statement=$db->prepare('SELECT cli_grupos FROM sis_acesso WHERE login=? LIMIT 1');
    $statement->bind_param('s',$user);$statement->execute();$access=$statement->get_result()->fetch_assoc();
    if(!$access)return '1=0';
    $raw=trim((string)$access['cli_grupos']);$groups=array_map('trim',explode(',',$raw));
    if($raw==='' || in_array('full_clientes',$groups,true))return '1=1';
    $quoted=array();foreach($groups as $group)if($group!=='' && $group!=='ped_fil')$quoted[]="'".$db->real_escape_string($group)."'";
    return $quoted?'c.grupo IN ('.implode(',',$quoted).')':'1=0';
}
function mka_attendance_save($db,$client,$operator,$subject,$notes,$outcome,$key) {
    if(!in_array($outcome,array('resolved','finished','service'),true) || !preg_match('/^[a-zA-Z0-9_-]{12,96}$/D',$key)
        || trim($subject)==='' || preg_match_all('/./us',$subject)>255 || trim($notes)==='' || preg_match_all('/./us',$notes)>8000)
        throw new InvalidArgumentException('Preencha assunto e relato dentro dos limites indicados.');
    $hash=hash('sha256',json_encode(array($client['uuid_cliente'],$subject,$notes,$outcome)));
    $statement=$db->prepare('INSERT INTO suite_support_attendance(client_uuid,login,client_name,operator,subject,notes,outcome,request_key,payload_hash) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
    $statement->bind_param('sssssssss',$client['uuid_cliente'],$client['login'],$client['nome'],$operator,$subject,$notes,$outcome,$key,$hash);
    if(!$statement->execute())throw new RuntimeException('Nao foi possivel registrar o atendimento.');
    $id=(int)$db->insert_id;
    $statement=$db->prepare('SELECT id,outcome,payload_hash FROM suite_support_attendance WHERE id=? AND operator=?');
    $statement->bind_param('is',$id,$operator);$statement->execute();$saved=$statement->get_result()->fetch_assoc();
    if(!$saved || !hash_equals($saved['payload_hash'],$hash))throw new InvalidArgumentException('Este envio ja foi usado com outros dados. Reabra o atendimento.');
    return $saved;
}
