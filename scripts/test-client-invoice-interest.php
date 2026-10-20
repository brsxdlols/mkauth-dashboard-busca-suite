<?php
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
foreach(array('formatacao','data','math','titulo') as $library)require_once '/opt/mk-auth/libs/'.$library.'.class';
require $argv[1];$db=new mysqli('127.0.0.1','root','vertrigo','mkradius');
$db->query('CREATE TEMPORARY TABLE sis_cliente(login VARCHAR(80),uuid_cliente VARCHAR(48),grupo VARCHAR(40),conta INT)');
$db->query('CREATE TEMPORARY TABLE sis_lanc(id INT,login VARCHAR(80),status VARCHAR(20),deltitulo INT,datavenc DATE)');
$db->query('CREATE TEMPORARY TABLE sis_boleto(id INT,multa VARCHAR(20),juros VARCHAR(20))');
$db->query('CREATE TEMPORARY TABLE vtab_titulos(titulo INT,valor DECIMAL(12,2),tipo VARCHAR(30))');
$db->query('CREATE TEMPORARY TABLE sis_mlanc(idlanc INT,valor DECIMAL(12,2),tipo VARCHAR(30),deltitulo INT)');
$db->query("INSERT INTO sis_cliente VALUES('fixture','aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','allowed',1),('other','ffffffff-bbbb-cccc-dddd-eeeeeeeeeeee','denied',1)");
$db->query("INSERT INTO sis_boleto VALUES(1,'2.00','0.10')");
$late=date('Y-m-d',strtotime('-10 days'));$future=date('Y-m-d',strtotime('+3 days'));
$db->query("INSERT INTO sis_lanc VALUES(1,'fixture','vencido',0,'$late'),(2,'fixture','aberto',0,'$future'),(3,'fixture','vencido',1,'$late'),(4,'other','vencido',0,'$late')");
$db->query("INSERT INTO vtab_titulos VALUES(1,100,'mensalidade'),(2,100,'mensalidade')");
$db->query("INSERT INTO sis_mlanc VALUES(1,20,'acrescimo',0),(1,5,'desconto',0),(1,999,'acrescimo',1)");
$uuid='aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';$c=mka_client_invoice_interest($db,$uuid,"c.grupo='allowed'",1);
if($c['days_late']!==10||$c['base']!=='115.00'||$c['fine']!=='2.30'||$c['interest']!=='1.15'||$c['total']!=='118.45')throw new Exception('Native calculation/adjustments mismatch: '.json_encode($c));
$c=mka_client_invoice_interest($db,$uuid,"c.grupo='allowed'",2);if($c['days_late']!==0||$c['fine']!=='0.00'||$c['interest']!=='0.00')throw new Exception('Future invoice charged');
foreach(array(3,4) as $id){try{mka_client_invoice_interest($db,$uuid,'1=1',$id);throw new Exception('Deleted/cross-client invoice allowed');}catch(DomainException $ok){}}
try{mka_client_invoice_interest($db,$uuid,"c.grupo='denied'",1);throw new Exception('Group scope bypass');}catch(DomainException $ok){}
$db->query("UPDATE sis_boleto SET multa='0.00',juros='0.00'");$c=mka_client_invoice_interest($db,$uuid,'1=1',1);if($c['total']!=='115.00')throw new Exception('Zero rates failed');
echo "PASS: native interest, days, account rates, base adjustments, future date, zero fees, deleted/client/group isolation\n";
