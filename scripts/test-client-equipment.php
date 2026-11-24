<?php
// Opt-in integration tests. All writes are in a randomly named, disposable database.
if(PHP_SAPI!=='cli' || getenv('MKA_EQUIPMENT_TEST')!=='1')exit("Set MKA_EQUIPMENT_TEST=1 for isolated database tests.\n");
require __DIR__.'/installer-database.php';require __DIR__.'/../addons/client_equipment/model.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
function fails($fn,$class){try{$fn();}catch(Throwable $e){check($e instanceof $class,'Unexpected error: '.get_class($e).': '.$e->getMessage());return;}throw new RuntimeException('Expected rejection');}
function fixture_db($schema){$db=mka_installer_database_connect();$db->select_db($schema);$db->set_charset('utf8mb4');return $db;}
if(($argv[1]??'')==='--worker'){
    $schema=$argv[2];if(!preg_match('/^suite_equipment_test_[a-f0-9]{12}$/D',$schema))exit(2);
    $db=fixture_db($schema);try{mka_equipment_save($db,$argv[3],$argv[4],'test',0,str_repeat($argv[5],32),array(array('product_id'=>3)));echo 'saved';}catch(UnexpectedValueException $e){echo 'rejected';}exit;
}
$root=mka_installer_database_connect();$schema='suite_equipment_test_'.bin2hex(random_bytes(6));$root->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4');
try {
 $db=fixture_db($schema);$engine=getenv('MKA_EQUIPMENT_TEST_ENGINE')?:'InnoDB';if(!in_array($engine,array('InnoDB','TokuDB'),true))throw new RuntimeException('Invalid engine');
 foreach(array(
  "CREATE TABLE sis_cliente(id INT AUTO_INCREMENT PRIMARY KEY,uuid_cliente VARCHAR(64) NOT NULL UNIQUE,login VARCHAR(32) NOT NULL UNIQUE,equipamento VARCHAR(20),mac_serial VARCHAR(255),comodato VARCHAR(3))",
  "CREATE TABLE sis_produto(id INT PRIMARY KEY,nome VARCHAR(255),ativo VARCHAR(3))",
  "CREATE TABLE sis_estoque(id INT AUTO_INCREMENT PRIMARY KEY,idprod INT,estoque INT,qtdmin INT)",
  "CREATE TABLE sis_prodcliente(id INT AUTO_INCREMENT PRIMARY KEY,idprod INT,qtdcli INT,datains DATETIME,usuario VARCHAR(32),cliente VARCHAR(32),KEY client(cliente))",
  "CREATE TABLE sis_prodhistorico(id INT AUTO_INCREMENT PRIMARY KEY,idprod INT,usuario VARCHAR(32),historico VARCHAR(255),data DATETIME)"
 )as$sql)$db->query($sql.' ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4');
 mka_equipment_schema($db);
 $db->query("INSERT INTO sis_cliente(uuid_cliente,login,equipamento,mac_serial,comodato) VALUES('client-a-00000000','a','ONU antiga','antigo','sim'),('client-b-00000000','b','nenhum','','nao'),('client-c-00000000','c','nenhum','','nao')");
 $db->query("INSERT INTO sis_produto VALUES(1,'Roteador <Intelbras>','sim'),(2,'Câmera IP','sim'),(3,'Última ONU','sim'),(4,'Inativo','nao')");
 $db->query('INSERT INTO sis_estoque(idprod,estoque,qtdmin) VALUES(1,3,0),(2,2,0),(3,1,0),(4,5,0)');
 $uuid='client-a-00000000';$seq=0;$revision=0;
 $save=function($rows,$key=null)use($db,$uuid,&$seq,&$revision){$key=$key?:str_pad(dechex(++$seq),32,'0',STR_PAD_LEFT);$revision=mka_equipment_save($db,$uuid,'a','test',$revision,$key,$rows);return $key;};
 $stock=function($id)use($db){return (int)$db->query('SELECT estoque FROM sis_estoque WHERE idprod='.(int)$id)->fetch_assoc()['estoque'];};
 $items=function()use($db,$uuid){return mka_equipment_state($db,array('uuid_cliente'=>$uuid,'login'=>'a'))['items'];};
 $key=$save(array(array('product_id'=>1,'serial'=>'A'),array('product_id'=>1,'serial'=>'B')));
 check($stock(1)===1 && count($items())===2,'Repeated product consumes two units');
 check(mka_equipment_save($db,$uuid,'a','test',0,$key,array(array('product_id'=>1,'serial'=>'A'),array('product_id'=>1,'serial'=>'B')))===1 && $stock(1)===1,'Network retry debited twice');
 $rows=$items();$rows[0]['serial']='<script>alert(1)</script>'; $save($rows);check($stock(1)===1,'Serial edit moved stock');
 $html=mka_equipment_contract_html($db,'a');check(strpos($html,'&lt;script&gt;')!==false && strpos($html,'<script>')===false && substr_count($html,'Roteador &lt;Intelbras&gt;')===2,'Contract escaping or repeated units failed');
 check(strpos($html,'ONU antiga')!==false && substr_count($html,'<tr>')===4,'Legacy equipment omitted alongside linked units');
 mka_equipment_query($db,'UPDATE sis_cliente SET equipamento=?,mac_serial=? WHERE login=?','sss',array('Roteador <Intelbras>',$rows[0]['serial'],'a'));
 check(substr_count(mka_equipment_contract_html($db,'a'),'<tr>')===3,'Native mirror counted twice in the contract');
 $db->query("UPDATE sis_cliente SET equipamento='Roteador_<Intelbras>' WHERE login='a'");
 check(substr_count(mka_equipment_contract_html($db,'a'),'<tr>')===3,'Equipment name alias counted twice');
 $db->query("UPDATE sis_cliente SET equipamento='ONU antiga',mac_serial='antigo' WHERE login='a'");
 $ten=array();for($i=0;$i<10;$i++)$ten[]=array('name'=>'Roteador repetido','serial'=>'EXEMPLO-'.$i,'comodato'=>$i%2?'nao':'sim');
 $tenHtml=mka_equipment_contract_table($ten);check(substr_count($tenHtml,'<tr>')===11 && substr_count($tenHtml,'<td>1</td>')===10 && substr_count($tenHtml,'<td>Sim</td>')===5 && substr_count($tenHtml,'<td>Não</td>')===5,'Ten equipment units were grouped or comodato values lost');
 $rows=$items();array_pop($rows);$save($rows);check($stock(1)===2 && count($items())===1,'Remove did not return one');
 $rows=$items();$rows[0]['product_id']=2;$save($rows);check($stock(1)===3 && $stock(2)===1,'Product swap incorrect');
 $rows=$items();$rows[]=array('product_id'=>2);$rows[]=array('product_id'=>2);$before=$revision;
 fails(function()use($save,$rows){$save($rows);},'UnexpectedValueException');check($stock(2)===1 && count($items())===1 && $revision===$before,'Insufficient stock partially changed state');
 fails(function()use($db,$uuid){mka_equipment_save($db,$uuid,'a','test',0,str_repeat('c',32),array());},'UnexpectedValueException');check($stock(2)===1,'Stale revision changed stock');
 fails(function()use($db){mka_equipment_save($db,'client-b-00000000','b','test',0,str_repeat('d',32),array(array('id'=>99999,'product_id'=>1)));},'UnexpectedValueException');
 fails(function()use($save){$save(array(array('product_id'=>4)));},'UnexpectedValueException');
 // Native removal already returned the unit: deleting the orphan metadata must not return again.
 $row=$items()[0];$db->query('UPDATE sis_estoque SET estoque=estoque+1 WHERE idprod=2');$db->query('DELETE FROM sis_prodcliente WHERE id='.(int)mka_equipment_rows($db,$uuid,'a')[0]['allocation_id']);
 $save(array());check($stock(2)===2 && count($items())===0,'Native removal credited twice');
 // Unrelated native allocations belong to their existing workflow.
 $db->query("INSERT INTO sis_prodcliente(idprod,qtdcli,usuario,cliente,datains) VALUES(1,7,'native','a',NOW())");$save(array(array('product_id'=>1)));$save(array());
 check((int)$db->query('SELECT SUM(qtdcli) n FROM sis_prodcliente')->fetch_assoc()['n']===7 && $stock(1)===3,'Unrelated native product modified');
 check(strpos(mka_equipment_contract_html($db,'a'),'ONU antiga')!==false,'Legacy equipment fallback lost');
 // A vendor expander may erase unknown variables; the equipment marker must survive it.
 define('MKA_EQUIPMENT_CODEC_TEST',true);require __DIR__.'/../addons/client_equipment/contract_runtime.php';
 class MKAC_VARS {public function __construct($kind,$login){} public function expand($html){return preg_replace('/%[a-z_]+%/','',$html);}}
 $native="include('sessao_admin.hhvm');".'$vars=new MKAC_VARS(\'cliente\',$login);$html=$vars->expand($html);';
 $patched=mka_equipment_patch_contract($native);$patched=str_replace("include('sessao_admin.hhvm');",'',$patched);
 $html='%equipamentos%';$login='a';$LOADMYSQL=$db;eval($patched);check(strpos($html,'ONU antiga')!==false&&strpos($html,'%equipamentos%')===false,'Native expander removed equipment variable');
 // Error after the stock UPDATE must roll back native and suite tables together.
 $db->query("CREATE TRIGGER equipment_test_failure BEFORE INSERT ON sis_prodhistorico FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='isolated failure injection'");
 fails(function()use($save){$save(array(array('product_id'=>1)));},'Throwable');$db->query('DROP TRIGGER equipment_test_failure');
 check($stock(1)===3 && count($items())===0,'Database failure left partial debit');
 // Two real connections compete for the last unit. Only one may succeed.
 $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --worker '.escapeshellarg($schema);
 $jobs=array();foreach(array(array('client-b-00000000','b','e'),array('client-c-00000000','c','f'))as$args){$pipes=array();$p=proc_open($cmd.' '.implode(' ',array_map('escapeshellarg',$args)),array(1=>array('pipe','w'),2=>array('pipe','w')),$pipes);$jobs[]=array($p,$pipes);}
 $out=array();foreach($jobs as$job){$out[]=stream_get_contents($job[1][1]);$err=stream_get_contents($job[1][2]);fclose($job[1][1]);fclose($job[1][2]);check(proc_close($job[0])===0,'Concurrent worker failed: '.$err);}
 sort($out);check($out===array('rejected','saved')&&$stock(3)===0,'Concurrent allocation oversold stock');
 echo "PASS: repeated units; retry; serial edit; contract escaping; removal; swap; insufficient stock; stale revision; foreign row; inactive product; native removal; unrelated products; legacy; rollback; concurrent last unit (".$engine.").\n";
 $db->close();
} finally {$root->query('DROP DATABASE `'.$schema.'`');$root->close();}
