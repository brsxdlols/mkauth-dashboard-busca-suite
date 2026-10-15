<?php
function mka_ticket_schedule_schema($db) {
    $db->query("CREATE TABLE IF NOT EXISTS suite_ticket_schedule_changes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ticket_id INT NOT NULL,
        operator VARCHAR(64) NOT NULL, before_data TEXT NOT NULL, after_data TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY ticket_history(ticket_id,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mka_ticket_schedule_version($row) {
    return hash('sha256',json_encode(array($row['id'],$row['visita'],$row['assunto'],$row['prioridade'],$row['tecnico'],$row['status']),JSON_UNESCAPED_UNICODE));
}
function mka_ticket_schedule_row($db,$id,$scope,$lock=false) {
    $q=$db->prepare("SELECT s.id,s.chamado,s.nome,s.assunto,s.abertura,s.visita,s.prioridade,s.tecnico,s.status FROM sis_suporte s INNER JOIN sis_cliente c ON c.login=s.login WHERE s.id=? AND s.status='aberto' AND ($scope)".($lock?' FOR UPDATE':''));
    $q->bind_param('i',$id);$q->execute();$row=$q->get_result()->fetch_assoc();
    if(!$row)throw new DomainException('Chamado indisponível ou fora do acesso permitido.');
    return $row;
}
function mka_ticket_schedule_date($value) {
    if($value===null || $value==='')return null;
    if(!is_string($value))throw new InvalidArgumentException('Data da visita inválida.');
    $date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value);
    if(!$date || $date->format('Y-m-d H:i:s')!==$value || (int)$date->format('Y')<2000 || (int)$date->format('Y')>2199)throw new InvalidArgumentException('Informe uma data e um horário válidos.');
    return $value;
}
function mka_ticket_schedule_save($db,$id,$scope,$operator,$version,$action,$data) {
    if(!in_array($action,array('move','edit'),true))throw new InvalidArgumentException('Operação inválida.');
    $db->begin_transaction();
    try {
        $before=mka_ticket_schedule_row($db,$id,$scope,true);
        if(!is_string($version) || !hash_equals(mka_ticket_schedule_version($before),$version))throw new UnexpectedValueException('O chamado foi alterado por outro usuário. Feche e abra a visita novamente.');
        $after=$before;
        if($action==='move'){
            $day=$data['day']??null;
            if(!is_string($day)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$day)||!$before['visita']||substr($before['visita'],0,4)==='0000')throw new InvalidArgumentException('Esta visita não pode ser movida. Abra o agendamento.');
            $after['visita']=mka_ticket_schedule_date($day.substr($before['visita'],10));
        }else{
            if(!array_key_exists('visit',$data))throw new InvalidArgumentException('Informe o agendamento ou deixe a data vazia.');
            $subject=$data['subject']??null;$priority=$data['priority']??null;
            if(!is_string($subject)||trim($subject)===''||preg_match_all('/./us',$subject)>255)throw new InvalidArgumentException('Preencha o assunto com até 255 caracteres.');
            if(!is_string($priority)||(!in_array($priority,array('baixa','normal','alta'),true)&&$priority!==(string)$before['prioridade']))throw new InvalidArgumentException('Prioridade inválida.');
            $after['assunto']=trim($subject);$after['prioridade']=$priority;$after['visita']=mka_ticket_schedule_date($data['visit']??null);
        }
        if($after['visita']===$before['visita']&&$after['assunto']===$before['assunto']&&$after['prioridade']===$before['prioridade']){$db->commit();return $before;}
        $q=$db->prepare("UPDATE sis_suporte SET visita=?,assunto=?,prioridade=? WHERE id=? AND status='aberto' AND visita <=> ? AND assunto <=> ? AND prioridade <=> ? AND tecnico <=> ?");
        $q->bind_param('sssisssi',$after['visita'],$after['assunto'],$after['prioridade'],$id,$before['visita'],$before['assunto'],$before['prioridade'],$before['tecnico']);$q->execute();
        if($q->affected_rows!==1)throw new UnexpectedValueException('O chamado foi alterado. Abra-o novamente antes de salvar.');
        $old=json_encode($before,JSON_UNESCAPED_UNICODE);$new=json_encode($after,JSON_UNESCAPED_UNICODE);
        $q=$db->prepare('INSERT INTO suite_ticket_schedule_changes(ticket_id,operator,before_data,after_data) VALUES(?,?,?,?)');$q->bind_param('isss',$id,$operator,$old,$new);$q->execute();
        $db->commit();return $after;
    }catch(Throwable $error){$db->rollback();throw $error;}
}
