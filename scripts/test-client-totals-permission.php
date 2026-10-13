<?php
// Run with php -n: no native permission function exists in Busca.
if(PHP_SAPI!=='cli')exit(1);
function fixture_mysqli_query($db,$sql){return false;}
function fixture_mysqli_fetch_assoc($r){return false;}
function fixture_mysqli_num_rows($r){return 2;}
function mka_perf_query(){return true;}
function mka_perf_cards(){}
$link=null;$grupos='';$isMultiBusiness=true;
foreach(array(false,true) as $acesso_permitido){
 ob_start();$source=file_get_contents(__DIR__.'/../addons/busca_inteligente/search_stats.php');$source=str_replace(array('mysqli_query(', 'mysqli_fetch_assoc(', 'mysqli_num_rows('),array('fixture_mysqli_query(', 'fixture_mysqli_fetch_assoc(', 'fixture_mysqli_num_rows('),$source);eval('?>'.$source);$html=ob_get_clean();
 if(strpos($html,'Bloqueados')===false || strpos($html,'Sem títulos')===false)throw new RuntimeException('Filters disappeared.');
 preg_match_all('/class="search-stat-value">([^<]*)</',$html,$values);
 if(count($values[1])<8)throw new RuntimeException('Cards missing.');
 foreach($values[1] as $i=>$value)if($acesso_permitido || $i>=count($values[1])-2 ? $value==='' : $value!=='')throw new RuntimeException('Count visibility incorrect.');
 echo 'PASS totals '.($acesso_permitido?'visible':'hidden').'; billing counts remain visible; filters rendered without Dashboard permission function.'.PHP_EOL;
}

