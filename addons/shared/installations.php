<?php
require_once __DIR__.'/ticket_schedule.php';
require_once __DIR__.'/ticket_board.php';
// Installation requests have their own native ownership permission; prospects may not be clients yet.
function mka_installation_scope($db,$operator,$all) {
    if($operator==='')return '0=1';
    if($all)return '1=1';
    return "(s.login_atend='".$db->real_escape_string($operator)."' OR s.login_atend='full_users')";
}
function mka_installation_where($scope) { return "($scope) AND s.tipo<>'assinatura' AND s.status<>'concluido'"; }
function mka_installation_version($row) {
    return hash('sha256',json_encode(array($row['id'],$row['uuid_solic'],$row['visita'],$row['status'],$row['tecnico'],$row['nome'],$row['login']),JSON_UNESCAPED_UNICODE));
}
function mka_installation_record($row) {
    $row['chamado']=$row['id'];$row['assunto']='Instalação';$row['prioridade']=$row['status'];
    $row['version']=mka_installation_version($row);$row['timing']=mka_ticket_timing($row['visita']);return $row;
}
function mka_installation_row($db,$id,$scope,$lock=false) {
    $q=$db->prepare('SELECT s.id,s.uuid_solic,s.nome,s.login,s.tecnico,s.visita,s.status FROM sis_solic s WHERE s.id=? AND '.mka_installation_where($scope).($lock?' FOR UPDATE':''));
    $q->bind_param('i',$id);$q->execute();$row=$q->get_result()->fetch_assoc();
    if(!$row)throw new DomainException('Instalação indisponível ou fora do acesso permitido.');return mka_installation_record($row);
}
function mka_installation_slots($db,$id,$scope,$day) {
    mka_installation_row($db,$id,$scope);
    if(!is_string($day)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$day))throw new InvalidArgumentException('Dia inválido.');
    $from=mka_ticket_schedule_date($day.' 00:00:00');$until=(new DateTimeImmutable($from))->modify('+1 day')->format('Y-m-d H:i:s');
    $q=$db->prepare('SELECT HOUR(s.visita) hour,COUNT(*) visits FROM sis_solic s WHERE '.mka_installation_where($scope).' AND s.id<>? AND s.visita>=? AND s.visita<? GROUP BY HOUR(s.visita)');
    $q->bind_param('iss',$id,$from,$until);$q->execute();$hours=array_fill(0,24,0);
    foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row)$hours[(int)$row['hour']]=(int)$row['visits'];
    $slots=array();for($i=8;$i<=18;$i++)$slots[]=array('time'=>sprintf('%02d:00',$i),'visits'=>$hours[$i]);return array('slots'=>$slots,'hours'=>$hours);
}
function mka_installation_schema($db) {
    $db->query('CREATE TABLE IF NOT EXISTS suite_installation_schedule_changes (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,installation_id INT NOT NULL,operator VARCHAR(64) NOT NULL,before_data TEXT NOT NULL,after_data TEXT NOT NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,KEY installation_history(installation_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
function mka_installation_move($db,$id,$scope,$operator,$data) {
    $db->begin_transaction();try {
        $before=mka_installation_row($db,$id,$scope,true);
        if(!is_string($data['version']??null)||!hash_equals($before['version'],$data['version']))throw new UnexpectedValueException('A instalação foi alterada. Feche e abra a visita novamente.');
        $day=$data['day']??null;$time=$data['time']??substr((string)$before['visita'],11);
        if(!is_string($day)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$day)||!$before['visita']||substr($before['visita'],0,4)==='0000'||!is_string($time)||!preg_match('/^\d{2}:\d{2}(?::\d{2})?$/D',$time))throw new InvalidArgumentException('Informe um dia e horário válidos.');
        if(strlen($time)===5)$time.=':00';$visit=mka_ticket_schedule_date($day.' '.$time);
        if($visit===$before['visita']){$db->commit();return $before;}
        if(mka_ticket_schedule_outside_hours($visit)&&($data['outside_confirmed']??false)!==true)throw new InvalidArgumentException('Confirme o agendamento fora do expediente (08h às 18h).');
        $slots=mka_installation_slots($db,$id,$scope,$day);$count=$slots['hours'][(int)substr($time,0,2)];
        if($count>0&&(!is_int($data['conflicts_confirmed']??null)||$data['conflicts_confirmed']<$count))throw new MkaTicketBookingConflict($count);
        $q=$db->prepare('UPDATE sis_solic SET visita=? WHERE id=? AND visita <=> ? AND status=? AND tecnico <=> ?');$q->bind_param('sisss',$visit,$id,$before['visita'],$before['status'],$before['tecnico']);$q->execute();
        if($q->affected_rows!==1)throw new UnexpectedValueException('A instalação mudou. Abra-a novamente antes de salvar.');
        $after=mka_installation_row($db,$id,$scope);$old=json_encode($before,JSON_UNESCAPED_UNICODE);$new=json_encode($after,JSON_UNESCAPED_UNICODE);
        $q=$db->prepare('INSERT INTO suite_installation_schedule_changes(installation_id,operator,before_data,after_data) VALUES(?,?,?,?)');$q->bind_param('isss',$id,$operator,$old,$new);$q->execute();$db->commit();return $after;
    }catch(Throwable $e){$db->rollback();throw $e;}
}
