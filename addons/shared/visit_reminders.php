<?php
require_once __DIR__.'/ticket_viability.php';
require_once __DIR__.'/installations.php';
require_once __DIR__.'/support_attendance.php';
function mka_visit_reminders_schema($db) {
    $db->query("CREATE TABLE IF NOT EXISTS suite_visit_reminders (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,operator VARCHAR(64) NOT NULL,
        kind VARCHAR(16) NOT NULL,record_id INT NOT NULL,visit DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,dismissed_at DATETIME NULL,
        UNIQUE KEY one_visit(operator,kind,record_id,visit),KEY pending(operator,dismissed_at,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS suite_visit_reminder_preferences (
        operator VARCHAR(64) NOT NULL,kind VARCHAR(16) NOT NULL,
        enabled TINYINT NOT NULL DEFAULT 1,advance_minutes TINYINT NOT NULL DEFAULT 10,
        PRIMARY KEY(operator,kind)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mka_visit_reminder_preferences($db,$operator,$kind) {
    $q=$db->prepare('SELECT enabled,advance_minutes FROM suite_visit_reminder_preferences WHERE operator=? AND kind=?');
    $q->bind_param('ss',$operator,$kind);$q->execute();$row=$q->get_result()->fetch_assoc();
    return array('enabled'=>$row?(bool)$row['enabled']:true,'advance_minutes'=>$row?(int)$row['advance_minutes']:10);
}
function mka_visit_reminder_preferences_save($db,$operator,$kind,$data) {
    if($operator===''||!in_array($kind,array('ticket','installation'),true)||!is_bool($data['enabled']??null)||!in_array($data['advance_minutes']??null,array(0,5,10),true))throw new InvalidArgumentException('Escolha se deseja mostrar o pop-up e um horário válido.');
    $enabled=$data['enabled']?1:0;$minutes=$data['advance_minutes'];
    $q=$db->prepare('INSERT INTO suite_visit_reminder_preferences(operator,kind,enabled,advance_minutes) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),advance_minutes=VALUES(advance_minutes)');
    $q->bind_param('ssii',$operator,$kind,$enabled,$minutes);$q->execute();
}

function mka_visit_reminders_collect($db,$operator,$ticketAllowed,$installationAllowed,$allInstallations,$now=null) {
    if($operator==='')throw new DomainException('Sessão administrativa necessária.');
    $now=$now??date('Y-m-d H:i:s');
    $ticketPreferences=mka_visit_reminder_preferences($db,$operator,'ticket');$installationPreferences=mka_visit_reminder_preferences($db,$operator,'installation');
    $ticketAllowed=$ticketAllowed&&$ticketPreferences['enabled'];$installationAllowed=$installationAllowed&&$installationPreferences['enabled'];
    // Allow the first poll after the scheduled minute to discover an exact-time reminder.
    $from=(new DateTimeImmutable($now))->modify('-60 seconds')->format('Y-m-d H:i:s');
    $sources=array();
    if($ticketAllowed)$sources[]=array('ticket','sis_suporte s LEFT JOIN sis_cliente c ON c.login=s.login',"s.status='aberto' AND ((c.cli_ativado='s' AND (".mka_attendance_scope($db,$operator).')) OR '.mka_viability_scope($db,'s',$operator).')','s.nome','s.chamado','chamados_abertos.php');
    if($installationAllowed)$sources[]=array('installation','sis_solic s',mka_installation_where(mka_installation_scope($db,$operator,$allInstallations)),'s.nome','s.id','instalacoes.php');
    foreach($sources as &$source)if($source[0]==='ticket')$source[2].=" AND NOT (TIME(s.visita)='00:00:15' AND EXISTS(SELECT 1 FROM sis_msg pm WHERE pm.chamado=s.chamado AND pm.msg LIKE '%[Periodo da visita:%sem horario definido]%'))";unset($source);
    $events=array();
    foreach($sources as $source){
        list($kind,$sourceFrom,$scope,$name,$reference,$page)=$source;
        $preferences=$kind==='installation'?$installationPreferences:$ticketPreferences;
        $until=(new DateTimeImmutable($now))->modify('+'.$preferences['advance_minutes'].' minutes')->format('Y-m-d H:i:s');
        $q=$db->prepare("INSERT IGNORE INTO suite_visit_reminders(operator,kind,record_id,visit) SELECT ?,?,s.id,s.visita FROM $sourceFrom WHERE ($scope) AND s.visita>=? AND s.visita<=?");
        $q->bind_param('ssss',$operator,$kind,$from,$until);$q->execute();
        // There is no time cutoff on pending reminders: passing the visit time never dismisses them.
        // Validate current access and schedule so a changed/cancelled visit cannot expose stale details.
        $q=$db->prepare("SELECT r.id,r.kind,r.record_id,r.visit,$name name,$reference reference FROM $sourceFrom INNER JOIN suite_visit_reminders r ON r.record_id=s.id WHERE r.operator=? AND r.kind=? AND r.dismissed_at IS NULL AND r.visit=s.visita AND r.visit<=? AND ($scope) ORDER BY r.visit,r.id LIMIT 100");
        $q->bind_param('sss',$operator,$kind,$until);$q->execute();
        foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row){$row['advance_minutes']=$preferences['advance_minutes'];$row['url']='/admin/addons/busca_inteligente/'.$page;$row['visit_label']=date('d/m/Y H:i',strtotime($row['visit']));$events[]=$row;}
    }
    $q=$db->prepare("SELECT id,kind,visit FROM suite_visit_reminders WHERE operator=? AND kind IN ('demo_ticket','demo_install') AND dismissed_at IS NULL ORDER BY id LIMIT 20");$q->bind_param('s',$operator);$q->execute();
    foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row){
        $installation=$row['kind']==='demo_install';if(($installation&&!$installationAllowed)||(!$installation&&!$ticketAllowed))continue;
        $row['advance_minutes']=($installation?$installationPreferences:$ticketPreferences)['advance_minutes'];$row['kind']=$installation?'installation':'ticket';$row['name']='Cliente de teste · Demonstração';$row['visit_label']=date('d/m/Y H:i',strtotime($row['visit']));$events[]=$row;
    }
    usort($events,function($a,$b){return strcmp($a['visit'],$b['visit'])?:((int)$a['id']<=>(int)$b['id']);});return $events;
}
function mka_visit_reminder_dismiss($db,$operator,$id) {
    $q=$db->prepare('UPDATE suite_visit_reminders SET dismissed_at=COALESCE(dismissed_at,NOW()) WHERE id=? AND operator=?');$q->bind_param('is',$id,$operator);$q->execute();
}
