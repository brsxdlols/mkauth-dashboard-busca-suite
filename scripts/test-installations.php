<?php
if(PHP_SAPI!=='cli')exit(1);
// Run only in a disposable schema; this script never opens the native mkradius schema.
require_once __DIR__.'/../addons/shared/installations.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$admin=new mysqli('127.0.0.1','root','vertrigo');$schema='suite_install_test_'.bin2hex(random_bytes(5));
$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4');
function check_install($condition,$label){if(!$condition)throw new RuntimeException($label);echo 'PASS '.$label.PHP_EOL;}
try {
    $db=new mysqli('127.0.0.1','root','vertrigo',$schema);$db->set_charset('utf8mb4');
    $db->query("CREATE TABLE sis_solic(id INT PRIMARY KEY,uuid_solic VARCHAR(48),nome VARCHAR(255),login VARCHAR(64),tecnico VARCHAR(255),visita DATETIME,status VARCHAR(20),tipo VARCHAR(20),login_atend VARCHAR(64)) ENGINE=InnoDB");
    $db->query("INSERT INTO sis_solic VALUES(1,'uuid1','Cliente teste','teste','Equipe A','2026-10-16 09:00:00','aberto','instalacao','ana'),(2,'uuid2','Compartilhado','outro','Equipe B','2026-10-17 10:00:00','pendente','instalacao','full_users'),(3,'uuid3','Privado','privado','Equipe C','2026-10-17 10:00:00','aberto','instalacao','joao'),(4,'uuid4','Concluido','fim','Equipe A',NULL,'concluido','instalacao','ana'),(5,'uuid5','Assinatura','web','Equipe A',NULL,'aberto','assinatura','ana')");
    mka_installation_schema($db);$scope=mka_installation_scope($db,'ana',false);
    $count=(int)$db->query('SELECT COUNT(*) FROM sis_solic s WHERE '.mka_installation_where($scope))->fetch_row()[0];check_install($count===2,'native operator scope includes own and full_users, excludes completed and signatures');
    check_install((int)$db->query('SELECT COUNT(*) FROM sis_solic s WHERE '.mka_installation_where(mka_installation_scope($db,'ana',true)))->fetch_row()[0]===3,'all-users permission includes other operators');
    check_install(mka_installation_scope($db,'',true)==='0=1','missing operator fails closed');
    try{mka_installation_row($db,3,$scope);throw new RuntimeException('private record exposed');}catch(DomainException $e){echo "PASS private record denied\n";}
    $row=mka_installation_row($db,1,$scope);$data=array('version'=>$row['version'],'day'=>'2026-10-17','time'=>'10:00');
    try{mka_installation_move($db,1,$scope,'ana',$data);throw new RuntimeException('overlap accepted silently');}catch(MkaTicketBookingConflict $e){check_install($e->visits===1,'overlap counts visible installations only');}
    $data['conflicts_confirmed']=1;$after=mka_installation_move($db,1,$scope,'ana',$data);check_install($after['visita']==='2026-10-17 10:00:00','confirmed overlap saves visit');
    try{mka_installation_move($db,1,$scope,'ana',$data);throw new RuntimeException('stale version accepted');}catch(UnexpectedValueException $e){echo "PASS stale update rejected\n";}
    $data=array('version'=>$after['version'],'day'=>'2026-10-18','time'=>'07:00');
    try{mka_installation_move($db,1,$scope,'ana',$data);throw new RuntimeException('outside hours accepted silently');}catch(InvalidArgumentException $e){echo "PASS outside hours needs confirmation\n";}
    $data['outside_confirmed']=true;$after=mka_installation_move($db,1,$scope,'ana',$data);check_install($after['visita']==='2026-10-18 07:00:00','agreed outside hours save');
    check_install((int)$db->query('SELECT COUNT(*) FROM suite_installation_schedule_changes')->fetch_row()[0]===2,'only successful moves create audit entries');
    check_install($after['nome']==='Cliente teste'&&$after['status']==='aberto'&&$after['tecnico']==='Equipe A','rescheduling preserves native fields');
    $slots=mka_installation_slots($db,1,$scope,'2026-10-17');check_install(count($slots['slots'])===11&&$slots['slots'][0]['time']==='08:00'&&$slots['slots'][10]['time']==='18:00','hourly slots from 08 to 18');
}finally{$admin->query('DROP DATABASE `'.$schema.'`');}
