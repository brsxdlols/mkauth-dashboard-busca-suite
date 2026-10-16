<?php
require_once __DIR__.'/installations.php';
require_once __DIR__.'/support_attendance.php';
function mka_visit_reminders_schema($db) {
    $db->query("CREATE TABLE IF NOT EXISTS suite_visit_reminders (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,operator VARCHAR(64) NOT NULL,
        kind VARCHAR(16) NOT NULL,record_id INT NOT NULL,visit DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,dismissed_at DATETIME NULL,
        UNIQUE KEY one_visit(operator,kind,record_id,visit),KEY pending(operator,dismissed_at,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mka_visit_reminders_collect($db,$operator,$ticketAllowed,$installationAllowed,$allInstallations,$now=null) {
    if($operator==='')throw new DomainException('Sessão administrativa necessária.');
    $now=$now??date('Y-m-d H:i:s');$until=(new DateTimeImmutable($now))->modify('+10 minutes')->format('Y-m-d H:i:s');
    $sources=array();
    if($ticketAllowed)$sources[]=array('ticket','sis_suporte s INNER JOIN sis_cliente c ON c.login=s.login',"s.status='aberto' AND c.cli_ativado='s' AND (".mka_attendance_scope($db,$operator).')','s.nome','s.chamado','chamados_abertos.php');
    if($installationAllowed)$sources[]=array('installation','sis_solic s',mka_installation_where(mka_installation_scope($db,$operator,$allInstallations)),'s.nome','s.id','instalacoes.php');
    foreach($sources as &$source)if($source[0]==='ticket')$source[2].=" AND NOT (TIME(s.visita)='00:00:15' AND EXISTS(SELECT 1 FROM sis_msg pm WHERE pm.chamado=s.chamado AND pm.msg LIKE '%[Periodo da visita:%sem horario definido]%'))";unset($source);
    $events=array();
    foreach($sources as $source){
        list($kind,$from,$scope,$name,$reference,$page)=$source;
        $q=$db->prepare("INSERT IGNORE INTO suite_visit_reminders(operator,kind,record_id,visit) SELECT ?,?,s.id,s.visita FROM $from WHERE ($scope) AND s.visita>=? AND s.visita<=?");
        $q->bind_param('ssss',$operator,$kind,$now,$until);$q->execute();
        // There is no time cutoff on pending reminders: passing the visit time never dismisses them.
        // Validate current access and schedule so a changed/cancelled visit cannot expose stale details.
        $q=$db->prepare("SELECT r.id,r.kind,r.record_id,r.visit,$name name,$reference reference FROM $from INNER JOIN suite_visit_reminders r ON r.record_id=s.id WHERE r.operator=? AND r.kind=? AND r.dismissed_at IS NULL AND r.visit=s.visita AND ($scope) ORDER BY r.visit,r.id LIMIT 100");
        $q->bind_param('ss',$operator,$kind);$q->execute();
        foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row){$row['url']='/admin/addons/busca_inteligente/'.$page;$row['visit_label']=date('d/m/Y H:i',strtotime($row['visit']));$events[]=$row;}
    }
    $q=$db->prepare("SELECT id,kind,visit FROM suite_visit_reminders WHERE operator=? AND kind IN ('demo_ticket','demo_install') AND dismissed_at IS NULL ORDER BY id LIMIT 20");$q->bind_param('s',$operator);$q->execute();
    foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row){
        $installation=$row['kind']==='demo_install';if(($installation&&!$installationAllowed)||(!$installation&&!$ticketAllowed))continue;
        $row['kind']=$installation?'installation':'ticket';$row['name']='Cliente de teste · Demonstração';$row['visit_label']=date('d/m/Y H:i',strtotime($row['visit']));$events[]=$row;
    }
    usort($events,function($a,$b){return strcmp($a['visit'],$b['visit'])?:((int)$a['id']<=>(int)$b['id']);});return $events;
}
function mka_visit_reminder_dismiss($db,$operator,$id) {
    $q=$db->prepare('UPDATE suite_visit_reminders SET dismissed_at=COALESCE(dismissed_at,NOW()) WHERE id=? AND operator=?');$q->bind_param('is',$id,$operator);$q->execute();
}
