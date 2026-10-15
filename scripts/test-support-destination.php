<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../addons/shared/layout_mode.php';
function support_check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
$name="João D'Ávila & Filhos";$login='cliente+teste@example.test';
$url=mka_suite_support_url('busca',$name,$login,'hhvm');parse_str(parse_url($url,PHP_URL_QUERY),$query);
support_check(parse_url($url,PHP_URL_PATH)==='/admin/addons/busca_inteligente/chamados_abertos.php' && $query['busca']===$name,'search route preserves accented names and punctuation');
$url=mka_suite_support_url('native',$name,$login,'hhvm');parse_str(parse_url($url,PHP_URL_QUERY),$query);
support_check(parse_url($url,PHP_URL_PATH)==='/admin/suporte_ins.hhvm' && $query['login']===$login,'native route preserves login filter and HHVM');
support_check(strpos(mka_suite_support_url('native',$name,$login,'php'),'/admin/suporte_ins.php?')===0,'explicit PHP setting remains supported');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root',getenv('MYSQL_PASS')?:'vertrigo');$schema='codex_support_fixture_'.bin2hex(random_bytes(6));
$db->query('CREATE DATABASE `'.$schema.'`');
try{
 $db->select_db($schema);
 support_check(mka_suite_get_support_destination($db)==='native','new preference preserves the existing native behavior');
 support_check(mka_suite_set_support_destination($db,'busca') && mka_suite_get_support_destination($db)==='busca','search preference saves and reloads');
 support_check(mka_suite_set_support_destination($db,'unexpected') && mka_suite_get_support_destination($db)==='native','unknown submitted preference is normalized');
 support_check(mka_suite_get_dashboard_support_destination($db)==='busca','dashboard defaults to new ticket center');
 support_check(mka_suite_set_dashboard_support_destination($db,'native')&&mka_suite_get_dashboard_support_destination($db)==='native','dashboard native preference persists');
 support_check(mka_suite_get_support_destination($db)==='native','dashboard preference does not alter per-client search preference');
 support_check(mka_suite_dashboard_support_url('busca','hhvm')==='/admin/addons/busca_inteligente/chamados_abertos.php','dashboard new route has no unintended client filter');
 support_check(mka_suite_dashboard_support_url('native','php')==='/admin/suporte_aberto.php'&&mka_suite_dashboard_support_url('native','hhvm')==='/admin/suporte_aberto.hhvm','dashboard native route respects extension');
}finally{$db->query('DROP DATABASE `'.$schema.'`');$db->close();}
