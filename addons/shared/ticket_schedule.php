<?php
require_once __DIR__.'/agenda_settings.php';
require_once __DIR__.'/ticket_viability.php';
class MkaTicketBookingConflict extends RuntimeException {
    public $visits;
    public function __construct($visits){$this->visits=(int)$visits;parent::__construct('Já há visitas nesta faixa de horário. Confirme a sobreposição e organize equipes diferentes para atender os chamados.');}
}
function mka_ticket_schedule_schema($db) {
    mka_agenda_schema($db);
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
    $scope="((c.login IS NOT NULL AND ($scope)) OR ".mka_viability_scope($db).")";
    $q=$db->prepare("SELECT s.id,s.chamado,s.nome,s.assunto,s.abertura,s.visita,s.prioridade,s.tecnico,s.status FROM sis_suporte s LEFT JOIN sis_cliente c ON c.login=s.login WHERE s.id=? AND s.status='aberto' AND ($scope)".($lock?' FOR UPDATE':''));
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
function mka_ticket_schedule_outside_hours($visit) {
    if(!$visit)return false;
    $time=substr($visit,11,5);
    return $time<'08:00'||$time>'18:00';
}
function mka_ticket_schedule_slots($db,$id,$scope,$day) {
    $scope="((c.login IS NOT NULL AND ($scope)) OR ".mka_viability_scope($db).")";
    mka_ticket_schedule_row($db,$id,$scope);
    if(!is_string($day)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$day))throw new InvalidArgumentException('Dia inválido.');
    $from=mka_ticket_schedule_date($day.' 00:00:00');$until=(new DateTimeImmutable($from))->modify('+1 day')->format('Y-m-d H:i:s');
    $q=$db->prepare("SELECT HOUR(s.visita) AS hour,COUNT(*) AS visits FROM sis_suporte s LEFT JOIN sis_cliente c ON c.login=s.login WHERE ($scope) AND s.status='aberto' AND s.id<>? AND s.visita>=? AND s.visita<? GROUP BY HOUR(s.visita)");
    $q->bind_param('iss',$id,$from,$until);$q->execute();$hours=array_fill(0,24,0);
    foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row)$hours[(int)$row['hour']]=(int)$row['visits'];
    $slots=array();for($hour=8;$hour<=18;$hour++)$slots[]=array('time'=>sprintf('%02d:00',$hour),'visits'=>$hours[$hour]);
    return array('slots'=>$slots,'hours'=>$hours);
}
function mka_ticket_schedule_conflicts($db,$id,$scope,$visit) {
    $scope="((c.login IS NOT NULL AND ($scope)) OR ".mka_viability_scope($db).")";
    if(!$visit)return 0;
    $from=substr($visit,0,13).':00:00';$until=(new DateTimeImmutable($from))->modify('+1 hour')->format('Y-m-d H:i:s');
    $q=$db->prepare("SELECT COUNT(*) FROM sis_suporte s LEFT JOIN sis_cliente c ON c.login=s.login WHERE ($scope) AND s.status='aberto' AND s.id<>? AND s.visita>=? AND s.visita<?");$q->bind_param('iss',$id,$from,$until);$q->execute();return (int)$q->get_result()->fetch_row()[0];
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
            $time=$data['time']??substr($before['visita'],11);
            if(!is_string($time)||!preg_match('/^\d{2}:\d{2}(?::\d{2})?$/D',$time))throw new InvalidArgumentException('Horário inválido.');
            if(strlen($time)===5)$time.=':00';
            $after['visita']=mka_ticket_schedule_date($day.' '.$time);
        }else{
            if(!array_key_exists('visit',$data))throw new InvalidArgumentException('Informe o agendamento ou deixe a data vazia.');
            $subject=$data['subject']??null;$priority=$data['priority']??null;
            if(!is_string($subject)||trim($subject)===''||preg_match_all('/./us',$subject)>255)throw new InvalidArgumentException('Preencha o assunto com até 255 caracteres.');
            if(!is_string($priority))throw new InvalidArgumentException('Prioridade inválida.');
            if(!in_array($priority,array('baixa','normal','media','média','alta','urgente'),true)&&$priority!==(string)$before['prioridade']){$pq=$db->prepare('SELECT priority FROM suite_priority_colors WHERE priority=?');$pq->bind_param('s',$priority);$pq->execute();if(!$pq->get_result()->fetch_row())throw new InvalidArgumentException('Prioridade inválida.');}
            $after['assunto']=trim($subject);$after['prioridade']=$priority;$after['visita']=mka_ticket_schedule_date($data['visit']??null);
        }
        if($after['visita']!==$before['visita']&&$after['visita']&&!mka_agenda_workday(mka_agenda_settings($db),substr($after['visita'],0,10)))throw new InvalidArgumentException('Este dia não está habilitado para ordens de serviço.');
        if($after['visita']!==$before['visita']&&$after['visita']&&mka_agenda_lunch(mka_agenda_settings($db),substr($after['visita'],11,5)))throw new InvalidArgumentException('Este horário está reservado para almoço. Escolha outro horário.');
        if($after['visita']!==$before['visita'] && mka_ticket_schedule_outside_hours($after['visita']) && ($data['outside_confirmed']??false)!==true)throw new InvalidArgumentException('Horário fora do expediente (08h às 18h). Confirme que deseja agendar mesmo assim.');
        if($after['visita']!==$before['visita']){$conflicts=mka_ticket_schedule_conflicts($db,$id,$scope,$after['visita']);if($conflicts>0 && (!is_int($data['conflicts_confirmed']??null)||$data['conflicts_confirmed']<$conflicts))throw new MkaTicketBookingConflict($conflicts);}
        if($after['visita']===$before['visita']&&$after['assunto']===$before['assunto']&&$after['prioridade']===$before['prioridade']){$db->commit();return $before;}
        $q=$db->prepare("UPDATE sis_suporte SET visita=?,assunto=?,prioridade=? WHERE id=? AND status='aberto' AND visita <=> ? AND assunto <=> ? AND prioridade <=> ? AND tecnico <=> ?");
        $q->bind_param('sssisssi',$after['visita'],$after['assunto'],$after['prioridade'],$id,$before['visita'],$before['assunto'],$before['prioridade'],$before['tecnico']);$q->execute();
        if($q->affected_rows!==1)throw new UnexpectedValueException('O chamado foi alterado. Abra-o novamente antes de salvar.');
        $old=json_encode($before,JSON_UNESCAPED_UNICODE);$new=json_encode($after,JSON_UNESCAPED_UNICODE);
        $q=$db->prepare('INSERT INTO suite_ticket_schedule_changes(ticket_id,operator,before_data,after_data) VALUES(?,?,?,?)');$q->bind_param('isss',$id,$operator,$old,$new);$q->execute();
        $db->commit();return $after;
    }catch(Throwable $error){$db->rollback();throw $error;}
}
