<?php
if(PHP_SAPI!=='cli')exit(1);
require '/opt/mk-auth/admin/addons/shared/visit_reminders.php';
$operator=$argv[1]??'';$type=$argv[2]??'chamado';
if($operator===''||!in_array($type,array('chamado','instalacao'),true)){fwrite(STDERR,"Uso: php /root/test-visit-reminder.php USUARIO chamado|instalacao\n");exit(1);}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try{
    $db=new mysqli('127.0.0.1','root','vertrigo','mkradius');$db->set_charset('utf8mb4');
    $q=$db->prepare('SELECT 1 FROM sis_acesso WHERE login=? LIMIT 1');$q->bind_param('s',$operator);$q->execute();
    if(!$q->get_result()->fetch_row())throw new RuntimeException('Usuario administrativo nao encontrado.');
    if(!mka_installation_permission($db,$operator,$type==='instalacao'?'perm_instalacao':'perm_chamados'))throw new RuntimeException('Usuario sem permissao para este tipo de visita.');
    mka_visit_reminders_schema($db);$kind=$type==='instalacao'?'demo_install':'demo_ticket';$visit=date('Y-m-d H:i:s',time()+600);
    $q=$db->prepare('INSERT IGNORE INTO suite_visit_reminders(operator,kind,record_id,visit) VALUES(?,?,0,?)');$q->bind_param('sss',$operator,$kind,$visit);$q->execute();
    echo "Card de demonstracao enviado para ".$operator.". Aparece no MK-AUTH aberto em ate 30 segundos e fica ate fechar. Nenhum atendimento ou OS foi criado.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
