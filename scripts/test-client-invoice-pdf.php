<?php
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
class MKAC_PDF {
 public $MKAKUKTAMKTAUKTAUKTAKAHAUMAAUMAKUMT,$MKAMMAUATAKMHMHMMMTAKMMAHAUKMKHKAAU,$MKAMAATAHATMUMHKUAKATMKMHKUKAMUKAKA;
 public static $calls=0;
 public function FMTMAMMMMKTKAMMMTAUKHAMKUMHMTMTKH(){if($this->MKAKUKTAMKTAUKTAUKTAKAHAUMAAUMAKUMT!=='boleto'||$this->MKAMMAUATAKMHMHMMMTAKMMAHAUKMKHKAAU['titulo']!==1)throw new Exception('Native job arguments invalid');self::$calls++;file_put_contents($this->MKAMAATAHATMUMHKUAKATMKMHKUKAMUKAKA,"%PDF-1.4\nFixture only\n%%EOF\n");}
}
require $argv[1];$directory=$argv[2];mkdir($directory,0700);
$db=new mysqli('127.0.0.1','root','vertrigo','mkradius');
$db->query('CREATE TEMPORARY TABLE sis_cliente(login VARCHAR(64),uuid_cliente VARCHAR(48),grupo VARCHAR(32))');
$db->query('CREATE TEMPORARY TABLE sis_lanc(id INT,login VARCHAR(64),uuid_lanc VARCHAR(48),status VARCHAR(20),deltitulo INT)');
$db->query("INSERT INTO sis_cliente VALUES('fixture','aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','allowed')");
$db->query("INSERT INTO sis_lanc VALUES(1,'fixture','11111111-bbbb-cccc-dddd-eeeeeeeeeeee','aberto',0)");
try{mka_client_invoice_pdf($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',"c.grupo='denied'",1,$directory);throw new Exception('Scope bypass');}catch(DomainException $ok){}
$file=mka_client_invoice_pdf($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','1=1',1,$directory);if(strpos(file_get_contents($file),'%PDF-')!==0||MKAC_PDF::$calls!==1)throw new Exception('PDF generation failed');
mka_client_invoice_pdf($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','1=1',1,$directory);if(MKAC_PDF::$calls!==1)throw new Exception('Fresh PDF regenerated');
$db->query("UPDATE sis_lanc SET status='pago'");try{mka_client_invoice_pdf($db,'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee','1=1',1,$directory);throw new Exception('Paid PDF allowed');}catch(DomainException $ok){}
echo "PASS: PDF authorization, native job parameters, complete PDF signature and fresh cache reuse\n";
