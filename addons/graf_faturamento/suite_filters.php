<?php
if(!isset($link)||!($link instanceof mysqli))return;
if(!defined('VERSAO2UPDATE')&&is_file('/opt/mk-auth/include/configure.php'))require_once '/opt/mk-auth/include/configure.php';
$billingCashUser=isset($_GET['caixa_usuario'])&&is_string($_GET['caixa_usuario'])?trim($_GET['caixa_usuario']):'';
$billingCashUsers=array();$billingCashSql='';$billingCashAliasSql='';
$billingCashResult=$link->query("SELECT DISTINCT usuario FROM sis_caixa WHERE usuario IS NOT NULL AND usuario<>'' ORDER BY usuario");
if($billingCashResult)while($billingCashRow=$billingCashResult->fetch_assoc())$billingCashUsers[]=$billingCashRow['usuario'];
if($billingCashUser!==''&&!in_array($billingCashUser,$billingCashUsers,true))$billingCashUser='';
if($billingCashUser!==''){$billingCashSafe=$link->real_escape_string($billingCashUser);$billingCashSql="usuario='$billingCashSafe' AND ";$billingCashAliasSql="c.usuario='$billingCashSafe' AND ";}

if(!function_exists('gf_cash_income_groups')){
 function gf_cash_income_groups($db,$user,$start,$end){
  $query=$db->prepare("SELECT COALESCE(NULLIF(planodecontas,''),'Outros') category,SUM(entrada) amount FROM sis_caixa WHERE usuario=? AND data>=? AND data<=? AND historico NOT LIKE 'estorno titulo%' GROUP BY COALESCE(NULLIF(planodecontas,''),'Outros')");
  $until=$end.' 23:59:59';$query->bind_param('sss',$user,$start,$until);$query->execute();$result=$query->get_result();$values=array();while($row=$result->fetch_assoc())$values[$row['category']]=(float)$row['amount'];$query->close();return $values;
 }
}
