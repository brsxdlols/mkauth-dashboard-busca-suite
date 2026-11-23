<?php
// Draft values affect connection-local temporary tables only, never saved settings.
if(!isset($conn)||empty($_GET['preview']))return;
$token=(string)$_GET['preview'];
$draft=isset($_SESSION['suite_dashboard_previews'][$token])?$_SESSION['suite_dashboard_previews'][$token]:null;
if(!preg_match('/^[a-f0-9]{32}$/D',$token)||!is_array($draft)||time()-(int)$draft['created']>600||!permissao('perm_config')){http_response_code(403);exit('Pré-visualização expirada. Abra novamente pelas configurações.');}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit('A pré-visualização é somente leitura.');}
if(isset($dashboardPreviewToken))return;
$dashboardPreviewToken=$token;
foreach(array('dashboard_am_sis_cfg','sis_opcao') as $table){
 $stage='suite_preview_'.$table;
 if(!$conn->query("CREATE TEMPORARY TABLE $stage LIKE $table")||!$conn->query("INSERT INTO $stage SELECT * FROM $table")||!$conn->query("CREATE TEMPORARY TABLE $table LIKE $stage")||!$conn->query("INSERT INTO $table SELECT * FROM $stage")){http_response_code(500);exit('Não foi possível preparar a pré-visualização.');}
 $conn->query("DROP TEMPORARY TABLE $stage");
}
foreach($draft['cfg'] as $column=>$value){$stmt=$conn->prepare("UPDATE dashboard_am_sis_cfg SET `$column`=?");$stmt->bind_param('s',$value);$stmt->execute();}
foreach($draft['preferences'] as $key=>$value){$stmt=$conn->prepare('INSERT INTO sis_opcao(nome,valor) VALUES(?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)');$stmt->bind_param('ss',$key,$value);$stmt->execute();}
