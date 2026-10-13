<?php
if(PHP_SAPI!=='cli')exit(1);
function permissao($permission){return false;}
function mka_perf_query(){throw new RuntimeException('Aggregate query ran without Totais permission.');}
$acesso_permitido=true;$isMultiBusiness=false;
ob_start();require __DIR__.'/../addons/busca_inteligente/search_stats.php';$html=ob_get_clean();
if(strpos($html,'Online')===false || strpos($html,'Bloqueados')===false)throw new RuntimeException('Customer filters disappeared.');
if(preg_match('/[0-9%]/',strip_tags($html)) || strpos($html,'search-stat-value')!==false || strpos($html,'search-stat-percent')!==false)throw new RuntimeException('Aggregate values exposed without permission.');
echo "PASS filters remain available; counts, percentages and aggregate queries withheld without native Totais permission.\n";
