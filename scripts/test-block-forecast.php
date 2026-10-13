<?php
require __DIR__.'/../addons/shared/block_forecast.php';
function check($condition,$name){if(!$condition){fwrite(STDERR,'FAIL '.$name."\n");exit(1);}echo 'PASS '.$name."\n";}
$client=array('dias_corte'=>7,'observacao'=>'nao','isento'=>'nao','cli_ativado'=>'s');
$options=array('auto_corte'=>'sim','auto_desbloqueio'=>'sim');
$titles=array('open_count'=>2,'oldest_due'=>'2026-10-26');
$f=mka_block_forecast($client,$titles,$options,'2026-10-13');
check($f['cut_date']==='2026-11-02' && $f['eligible_date']==='2026-11-03' && !$f['eligible_now'],'future open invoice and month rollover');
$f=mka_block_forecast($client,$titles,$options,'2026-11-02');check(!$f['eligible_now'],'cutoff day not exceeded');
$f=mka_block_forecast($client,$titles,$options,'2026-11-03');check($f['eligible_now'],'day after cutoff');
$f=mka_block_forecast($client,array('open_count'=>0,'oldest_due'=>null),$options,'2026-10-13');check($f['eligible_date']===null && !$f['eligible_now'],'no open invoices');
$client['dias_corte']=null;$f=mka_block_forecast($client,$titles,$options,'2026-10-13');check($f['eligible_date']===null,'missing cutoff');
$client['dias_corte']=0;$f=mka_block_forecast($client,$titles,$options,'2026-10-13');check($f['eligible_date']==='2026-10-27','zero cutoff');
$client['observacao']='sim';$client['isento']='sim';$options['auto_corte']='nao';$f=mka_block_forecast($client,$titles,$options,'2026-10-13');check($f['observation'] && $f['exempt'] && $f['automatic_block']==='nao','exceptions reported');
