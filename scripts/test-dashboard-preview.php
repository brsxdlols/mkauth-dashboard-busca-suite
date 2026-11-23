<?php
// Opt-in MariaDB integration test. Only connection-local temporary tables are written.
$db=getenv('MKA_PREVIEW_TEST_DB');
if(!$db){echo "SKIP: set MKA_PREVIEW_TEST_DB and MKA_PREVIEW_TEST_PASSWORD\n";exit(0);}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
function connection(){return new mysqli(getenv('MKA_PREVIEW_TEST_HOST')?:'127.0.0.1',getenv('MKA_PREVIEW_TEST_USER')?:'root',getenv('MKA_PREVIEW_TEST_PASSWORD')?:'',getenv('MKA_PREVIEW_TEST_DB'));}
function snapshot($c){return json_encode(array($c->query('SELECT * FROM dashboard_am_sis_cfg ORDER BY id')->fetch_all(MYSQLI_ASSOC),$c->query('SELECT * FROM sis_opcao ORDER BY nome')->fetch_all(MYSQLI_ASSOC)));}
function permissao($name){return true;}
$conn=connection();$before=snapshot($conn);$token=str_repeat('a',32);
$_SERVER['REQUEST_METHOD']='GET';$_GET=array('preview'=>$token);
$_SESSION=array('suite_dashboard_previews'=>array($token=>array('created'=>time(),'cfg'=>array('suite_layout_mode'=>'multi','qtd_meses_graficos'=>3,'exb_balanco_clientes'=>'n','exb_balanco_faturamento'=>'n'),'preferences'=>array('suite_section_calls'=>'hidden'))));
require isset($argv[1])?$argv[1]:__DIR__.'/../addons/dashboard/preview_apply.php';
$row=$conn->query('SELECT suite_layout_mode,qtd_meses_graficos FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1')->fetch_assoc();
if($row['suite_layout_mode']!=='multi'||(int)$row['qtd_meses_graficos']!==3)throw new RuntimeException('Draft settings not applied');
$pref=$conn->query("SELECT valor FROM sis_opcao WHERE nome='suite_section_calls'")->fetch_assoc();
if($pref['valor']!=='hidden')throw new RuntimeException('Draft section preference not applied');
foreach(array('client_growth.php','billing_preview.php') as $file){ob_start();include dirname(isset($argv[1])?$argv[1]:__DIR__.'/../addons/dashboard/preview_apply.php').'/'.$file;$body=ob_get_clean();if(trim($body)!=='')throw new RuntimeException('Hidden chart rendered');}
$conn->close();$verify=connection();if(snapshot($verify)!==$before)throw new RuntimeException('Saved settings changed');$verify->close();
echo "PASS: draft values applied, saved configuration and preferences unchanged\n";
