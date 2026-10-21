<?php
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli('127.0.0.1','root',getenv('MK_TEST_DB_PASSWORD'),'mkradius');
require $argv[1];
function check_note($value,$message){if(!$value)throw new RuntimeException($message);}
$db->query('CREATE TEMPORARY TABLE sis_cliente(id INT,uuid_cliente VARCHAR(64),login VARCHAR(64),nome VARCHAR(128),grupo VARCHAR(40),observacao VARCHAR(3),rem_obs DATE NULL) ENGINE=InnoDB');
$db->query('CREATE TEMPORARY TABLE sis_lanc(id INT,login VARCHAR(64),status VARCHAR(16),deltitulo INT) ENGINE=InnoDB');
$db->query('CREATE TEMPORARY TABLE dashboard_am_trust_unlock_audit(id INT AUTO_INCREMENT PRIMARY KEY,client_id INT,client_login VARCHAR(64),client_name VARCHAR(128),unlocked_until DATE,performed_by VARCHAR(64),created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
$db->query('CREATE TEMPORARY TABLE suite_invoice_financial_notes(id BIGINT AUTO_INCREMENT PRIMARY KEY,invoice_id INT,client_uuid VARCHAR(64),login VARCHAR(64),note TEXT,promised_on DATE NULL,protected_until DATE NULL,kind VARCHAR(12),operator VARCHAR(64),request_key VARCHAR(96),payload_hash CHAR(64),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY request_once(operator,request_key),KEY invoice_history(invoice_id,id)) ENGINE=InnoDB');
$db->query("INSERT INTO sis_cliente VALUES(1,'fixture-client','fixture','Teste','allowed','nao',NULL),(2,'other-client','other','Outro','restricted','nao',NULL)");
$db->query("INSERT INTO sis_lanc VALUES(1,'fixture','vencido',0),(2,'fixture','pago',0),(3,'fixture','aberto',1),(4,'other','vencido',0)");
$settings=array('mode'=>'date','fixed_days'=>1,'recent_days'=>7);$scope="c.grupo='allowed'";$date=date('Y-m-d',strtotime('+7 days'));
$input=array('note'=>'Cliente vai pagar no dia combinado.','promised_on'=>$date,'protect'=>'0','request_key'=>'fixture_note_0001');
$id=mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$input,false,$settings);
check_note((int)$id>0,'Saved');check_note($db->query('SELECT observacao FROM sis_cliente WHERE id=1')->fetch_row()[0]==='nao','Note alone does not grant trust');
check_note(mka_invoice_note_counts($db,array('fixture-client'))['fixture-client']===1,'Search badge count');
check_note(count(mka_invoice_note_history($db,'fixture-client',1))===1,'History');
check_note(mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$input,false,$settings)===$id,'Idempotent retry');
$input['protect']='1';$input['request_key']='fixture_note_0002';
try{mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$input,false,$settings);throw new RuntimeException('Permission leak');}catch(DomainException $e){}
mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$input,true,$settings);$row=$db->query('SELECT observacao,rem_obs FROM sis_cliente WHERE id=1')->fetch_assoc();check_note($row['observacao']==='sim'&&$row['rem_obs']===$date,'Native confidence fields');
check_note($db->query('SELECT COUNT(*) FROM dashboard_am_trust_unlock_audit')->fetch_row()[0]==1,'Confidence audit');
$input['request_key']='fixture_note_0003';
try{mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$input,true,$settings);throw new RuntimeException('Recent confirmation missing');}catch(UnexpectedValueException $e){}
$later=date('Y-m-d',strtotime('+20 days'));$db->query("UPDATE sis_cliente SET rem_obs='$later' WHERE id=1");$input['confirm_recent']='1';mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$input,true,$settings);
check_note($db->query('SELECT rem_obs FROM sis_cliente WHERE id=1')->fetch_row()[0]===$later,'Do not shorten existing trust');
$resolve=array('action'=>'resolve','request_key'=>'fixture_note_0004');mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$resolve,false,$settings);check_note(mka_invoice_note_counts($db,array('fixture-client'))===array(),'Resolved badge removed');check_note($db->query('SELECT rem_obs FROM sis_cliente WHERE id=1')->fetch_row()[0]===$later,'Resolve does not cancel trust');
foreach(array(array('fixture-client',2),array('fixture-client',3),array('other-client',4),array('fixture-client',4)) as $case){try{mka_invoice_note_client($db,$case[0],$case[1],$scope);throw new RuntimeException('Scope or invoice state leak');}catch(DomainException $e){}}
try{mka_invoice_note_validate(array('note'=>'x','promised_on'=>'2026-02-30','protect'=>'1'),date('Y-m-d'),$settings);throw new RuntimeException('Invalid date');}catch(InvalidArgumentException $e){}

$input=array('note'=>'Novo aviso para filtrar','request_key'=>'fixture_note_0005');mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$input,false,$settings);
$filtered=mka_invoice_note_filter_query('SELECT c.login FROM sis_cliente c WHERE 1=1 ORDER BY c.login',mka_invoice_note_filter_sql($db));$matches=$db->query($filtered)->fetch_all(MYSQLI_ASSOC);check_note(count($matches)===1&&$matches[0]['login']==='fixture','Financial notice filter only pending open invoices');
echo "Financial notes: history, scoped ownership, paid/deleted exclusion, idempotency, explicit trust permission, recent confirmation, preserved longer trust and resolved badges OK\n";

$remove=array('action'=>'resolve','request_key'=>'fixture_remove_0001','remove_protection'=>'1','expected_protection_until'=>$later);
try{mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$remove,false,$settings);throw new RuntimeException('Remove permission leak');}catch(DomainException $e){}
$stale=$remove;$stale['expected_protection_until']='2000-01-01';try{mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$stale,true,$settings);throw new RuntimeException('Stale deadline removed');}catch(UnexpectedValueException $e){}
$removed=mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$remove,true,$settings);check_note(mka_invoice_note_save($db,'fixture-client',1,$scope,'operator',$remove,true,$settings)===$removed,'Removal idempotent');
$row=$db->query('SELECT observacao,rem_obs FROM sis_cliente WHERE id=1')->fetch_assoc();check_note($row['observacao']==='nao'&&$row['rem_obs']===null,'Explicit removal clears trust');echo "Explicit trust removal: permissions, stale deadline, audit and retry OK\n";
