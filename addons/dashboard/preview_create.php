<?php
require __DIR__.'/../shared/notification_bootstrap.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'||!permissao('perm_config')||empty($_SESSION['suite_finance_summary_csrf'])||!hash_equals($_SESSION['suite_finance_summary_csrf'],(string)($_POST['finance_summary_csrf']??''))){http_response_code(403);exit(json_encode(array('error'=>'Sessão inválida. Atualize as configurações.')));}
if(isset($_POST['suite_layout_mode'])&&!in_array($_POST['suite_layout_mode'],array('novo','multi'),true)){http_response_code(422);exit(json_encode(array('error'=>'A pré-visualização está disponível para os layouts Nova e Multiempresas.')));}
$cfg=array();$allowed=array('exb_ticket_medio','exb_saldo_conta','exb_clientes_ramal','exb_balanco_faturamento','exb_balanco_clientes','exb_balanco_chamados','exb_busca_inteligente','contabilizar_bloq_offline','tbl_logs_sistema','tbl_chamados_abertos','tbl_contas_pagar','popup_clientes_sessao');
foreach($allowed as $key)if(isset($_POST[$key])&&in_array($_POST[$key],array('s','n','sim','nao'),true))$cfg[$key]=(string)$_POST[$key];
if(isset($_POST['suite_layout_mode'])&&in_array($_POST['suite_layout_mode'],array('novo','multi'),true))$cfg['suite_layout_mode']=$_POST['suite_layout_mode'];
if(isset($_POST['qtd_meses_graficos']))$cfg['qtd_meses_graficos']=max(1,min(24,(int)$_POST['qtd_meses_graficos']));
if(isset($_POST['limite_ticket']))$cfg['limite_ticket']=max(1,(int)$_POST['limite_ticket']);
if(isset($_POST['exb_graficos_em_baixo'])&&in_array($_POST['exb_graficos_em_baixo'],array('s','n','h'),true)){$cfg['exb_graficos_em_baixo']=$_POST['exb_graficos_em_baixo'];$cfg['tbl_logs_sistema']=$cfg['exb_graficos_em_baixo']==='h'?'n':'s';}
$cfg['popup_clientes_sessao']='n';
$preferences=array();foreach(array('calls','installations','payables') as $key)if(isset($_POST['section_position'][$key])&&in_array($_POST['section_position'][$key],array('before','after','hidden'),true))$preferences['suite_section_'.$key]=$_POST['section_position'][$key];
if(isset($_POST['finance_summary_enabled'])&&in_array($_POST['finance_summary_enabled'],array('sim','nao'),true))$preferences['suite_finance_summary_enabled']=$_POST['finance_summary_enabled'];
if(isset($_POST['mobile_menu_version'])&&in_array($_POST['mobile_menu_version'],array('nova','legada'),true))$preferences['suite_mobile_menu_version']=$_POST['mobile_menu_version'];
$token=bin2hex(random_bytes(16));$previews=isset($_SESSION['suite_dashboard_previews'])?$_SESSION['suite_dashboard_previews']:array();
foreach($previews as $key=>$item)if(time()-(int)$item['created']>600)unset($previews[$key]);
while(count($previews)>=3)array_shift($previews);
$previews[$token]=array('created'=>time(),'cfg'=>$cfg,'preferences'=>$preferences);$_SESSION['suite_dashboard_previews']=$previews;session_write_close();
echo json_encode(array('url'=>'/admin/addons/dashboard/?preview='.$token));
