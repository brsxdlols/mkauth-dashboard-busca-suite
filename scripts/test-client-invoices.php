<?php
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','vertrigo','mkradius');
require $argv[1];
$db->query('CREATE TEMPORARY TABLE sis_cliente(login VARCHAR(255),nome VARCHAR(255),uuid_cliente VARCHAR(48),grupo VARCHAR(40),conta INT DEFAULT 1)');
$db->query("CREATE TEMPORARY TABLE sis_lanc(id INT PRIMARY KEY,login VARCHAR(255),status VARCHAR(32),deltitulo INT NULL,recibo VARCHAR(32),datavenc DATETIME,valor VARCHAR(32),obs VARCHAR(255),uuid_lanc VARCHAR(48) DEFAULT 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee')");
$db->query('CREATE TEMPORARY TABLE vtab_titulos(titulo INT,valor DECIMAL(12,2))');
$db->query('CREATE TEMPORARY TABLE sis_mlanc(idlanc INT,valor DECIMAL(12,2),tipo VARCHAR(30),deltitulo INT)');
$db->query('CREATE TEMPORARY TABLE sis_qrpix(titulo VARCHAR(64),qrcode TEXT)');
$db->query("INSERT INTO sis_cliente VALUES('fixture','Cliente teste','aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','permitido',1),('other','Outro cliente','ffffffff-bbbb-cccc-dddd-eeeeeeeeeeee','restrito',1)");
for($i=1;$i<=53;$i++)$db->query("INSERT INTO sis_lanc(id,login,status,deltitulo,recibo,datavenc,valor,obs) VALUES($i,'fixture','aberto',0,'F$i','2026-01-01','100.50','')");
$db->query("INSERT INTO sis_lanc(id,login,status,deltitulo,recibo,datavenc,valor,obs) VALUES(54,'fixture','pago',0,'P','2026-01-01','90',''),(55,'fixture','aberto',1,'D','2026-01-01','90',''),(56,'other','aberto',0,'O','2026-01-01','90','')");
$db->query("UPDATE sis_lanc SET status='vencido' WHERE id=1");
$db->query("INSERT INTO sis_qrpix VALUES('1','PIX-EXISTING'),('55','DELETED-PIX')");
$db->query("INSERT INTO sis_mlanc VALUES(1,60,'desconto',0),(1,999,'acrescimo',1),(2,15,'acrescimo',0)");
$a=mka_client_open_invoices($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',"c.grupo='permitido'",1);
if($a['total']!==53||count($a['invoices'])!==5||$a['pages']!==11)throw new Exception('Pagination or paid/deleted isolation failed');
if((int)$a['invoices'][0]['id']!==1||(int)$a['invoices'][0]['overdue']!==1)throw new Exception('Explicit overdue status missing');
if(abs((float)$a['invoices'][0]['valor']-40.50)>0.001||(float)$a['invoices'][0]['valor_original']!==100.50||abs((float)$a['invoices'][1]['valor']-115.50)>0.001)throw new Exception('Discount/surcharge or deleted adjustments mismatch');
$b=mka_client_open_invoices($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',"c.grupo='permitido'",999);
if(count($b['invoices'])!==3||$b['page']!==11)throw new Exception('Last page failed');
try{mka_client_open_invoices($db,'ffffffff-bbbb-cccc-dddd-eeeeeeeeeeee',"c.grupo='permitido'",1);throw new Exception('Scope bypass');}catch(DomainException $expected){}
$pix=mka_client_invoice_pix($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',"c.grupo='permitido'",1,false);if($pix['code']!=='PIX-EXISTING'||(int)$a['invoices'][0]['has_pix']!==1)throw new Exception('Existing PIX unavailable');
try{mka_client_invoice_pix($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','1=1',55,false);throw new Exception('Deleted PIX allowed');}catch(DomainException $expected){}
$db->query("UPDATE sis_lanc SET status='pago' WHERE login='fixture'");
if(mka_client_open_invoices($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','1=1')['total']!==0)throw new Exception('Empty result failed');
echo "PASS: open and overdue, paid/deleted exclusion, client/group isolation, pagination and empty state\n";
