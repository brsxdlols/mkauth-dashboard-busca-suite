<?php
if (!isset($acesso_permitido) || !$acesso_permitido) return;

$statsNow = isset($now) && $now !== '' ? $now : date('Y-m-d');
$stats = array('clients'=>0,'additional'=>0,'free'=>0,'observation'=>0,'blocked'=>0,'late'=>0,'online'=>0,'offline'=>0,'no_booklet'=>0,'no_titles'=>0,'manual'=>0);
$statsClient = @mysqli_query($link, "SELECT COUNT(*) clients,SUM(c.bloqueado='nao') free_count,SUM(c.observacao='sim') observation_count,SUM(c.bloqueado='sim') blocked_count,SUM(c.parc_abertas='0' AND c.isento='nao' AND c.tipo_cob='carne') no_booklet,SUM(c.tit_abertos='0' AND c.isento='nao' AND c.tipo_cob='titulo') no_titles FROM sis_cliente c WHERE $grupos c.cli_ativado='s'");
if($statsClient&&($sr=mysqli_fetch_assoc($statsClient))){$stats['clients']=(int)$sr['clients'];$stats['free']=(int)$sr['free_count'];$stats['observation']=(int)$sr['observation_count'];$stats['blocked']=(int)$sr['blocked_count'];$stats['no_booklet']=(int)$sr['no_booklet'];$stats['no_titles']=(int)$sr['no_titles'];}
$statsAdditional=@mysqli_query($link,"SELECT COUNT(*) total FROM sis_adicional a LEFT JOIN sis_cliente c ON a.login=c.login WHERE $grupos c.cli_ativado='s'");if($statsAdditional&&($sr=mysqli_fetch_assoc($statsAdditional)))$stats['additional']=(int)$sr['total'];
$statsLate=@mysqli_query($link,"SELECT COUNT(DISTINCT l.login) total FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login=c.login WHERE $grupos c.cli_ativado='s' AND l.status<>'pago' AND l.deltitulo=0 AND l.datavenc<='$statsNow'");if($statsLate&&($sr=mysqli_fetch_assoc($statsLate)))$stats['late']=(int)$sr['total'];
// Normalize each login set once; UNION and DISTINCT prevent duplicate session counts.
if (empty($isMultiBusiness)) {
$statsOnline=@mysqli_query($link,"SELECT COUNT(*) total FROM (SELECT DISTINCT LOWER(TRIM(username)) login_key FROM radacct WHERE acctstoptime IS NULL) online_users INNER JOIN (SELECT LOWER(TRIM(c.login)) login_key FROM sis_cliente c WHERE $grupos c.cli_ativado='s' UNION SELECT LOWER(TRIM(a.username)) login_key FROM sis_adicional a LEFT JOIN sis_cliente c ON a.login=c.login WHERE $grupos c.cli_ativado='s') allowed_users ON allowed_users.login_key=online_users.login_key");if($statsOnline&&($sr=mysqli_fetch_assoc($statsOnline)))$stats['online']=(int)$sr['total'];
}
$manualBlockColumn=@mysqli_query($link,"SHOW COLUMNS FROM sis_cliente LIKE 'tipobloq'");if($manualBlockColumn&&mysqli_num_rows($manualBlockColumn)>0){$manualBlockResult=@mysqli_query($link,"SELECT COUNT(*) total FROM sis_cliente c WHERE $grupos c.cli_ativado='s' AND c.bloqueado='sim' AND c.tipobloq='man'");if($manualBlockResult&&($sr=mysqli_fetch_assoc($manualBlockResult)))$stats['manual']=(int)$sr['total'];}
$statsTotal=$stats['clients']+$stats['additional'];$stats['offline']=max(0,$statsTotal-$stats['online']);
$percent=function($value,$base){return $base>0?number_format(($value/$base)*100,2,',','.').'%' :'0,00%';};
$searchStats=array(
 array('Total',$statsTotal,'100,00%','index.php','is-primary','fa-users'),array('Adicionais',$stats['additional'],$percent($stats['additional'],$statsTotal),'?busca=adicionais','is-light','fa-user-plus'),array('Livres',$stats['free'],$percent($stats['free'],$stats['clients']),'?busca=','is-info','fa-user-check'),array('Observação',$stats['observation'],$percent($stats['observation'],$stats['clients']),'?busca=obs','is-observation','fa-eye'),array('Bloqueados',$stats['blocked'],$percent($stats['blocked'],$stats['clients']),'?busca=bloq','is-danger','fa-user-lock'),array('Atraso',$stats['late'],$percent($stats['late'],$stats['clients']),'?busca=atrasado','is-warning','fa-clock'),array('Online',$stats['online'],$percent($stats['online'],$statsTotal),'?busca=on','is-success','fa-wifi'),array('Offline',$stats['offline'],$percent($stats['offline'],$statsTotal),'?busca=off','is-dark','fa-plug-circle-xmark'),array('Sem carnê',$stats['no_booklet'],$percent($stats['no_booklet'],$statsTotal),'?busca=sem+carne','is-outline-danger','fa-file-circle-xmark'),array('Sem títulos',$stats['no_titles'],$percent($stats['no_titles'],$statsTotal),'?busca=sem+tit','is-outline-danger','fa-receipt'),array('Bloqueio manual',$stats['manual'],$percent($stats['manual'],$stats['clients']),'?busca=bloqueado+manualmente','is-manual','fa-user-shield')
);
$currentStatSearch = array_key_exists('busca', $_GET) ? strtolower(trim((string)$_GET['busca'])) : null;
if (!empty($isMultiBusiness)) $searchStats = array_merge(array_slice($searchStats, 0, 6), array_slice($searchStats, 8, 2));
$stats['disabled'] = 0;
$statsDisabled = mysqli_query($link, "SELECT COUNT(*) total FROM sis_cliente c WHERE $grupos c.cli_ativado='n'");
if ($statsDisabled && ($sr = mysqli_fetch_assoc($statsDisabled))) $stats['disabled'] = (int)$sr['total'];
// Disabled customers are separate from the existing active-customer totals.
$searchStats[] = array('Desativados', $stats['disabled'], $percent($stats['disabled'], $stats['clients'] + $stats['disabled']), '?busca=desativado', 'is-disabled', 'fa-user-slash');
// Keep billing exceptions last; manual block follows Offline in provider mode.
$billingStats = array(); $orderedStats = array();
foreach ($searchStats as $stat) {
    if ($stat[3] === '?busca=sem+carne' || $stat[3] === '?busca=sem+tit') $billingStats[] = $stat;
    else $orderedStats[] = $stat;
}
$searchStats = array_merge($orderedStats, $billingStats);
?>
<style>.search-stat-card.is-disabled{--accent:#6b7280;--card-bg:linear-gradient(145deg,#7b818b,#626975);color:#fff}</style>
<div class="search-stat-grid no_print" aria-label="Resumo de clientes">
<?php foreach($searchStats as $stat){
    $targetStatSearch = null;
    if (strpos($stat[3], '?') !== false) { $statQuery = array(); parse_str((string)parse_url($stat[3], PHP_URL_QUERY), $statQuery); $targetStatSearch = isset($statQuery['busca']) ? strtolower(trim((string)$statQuery['busca'])) : ''; }
    $statSelected = ($targetStatSearch === null) ? ($currentStatSearch === null) : ($currentStatSearch !== null && $currentStatSearch === $targetStatSearch);
?><a class="search-stat-card <?= $stat[4]; ?><?= $statSelected ? ' is-selected' : ''; ?>" href="<?= $stat[3]; ?>"<?= $statSelected ? ' aria-current="true"' : ''; ?>><span class="search-stat-icon"><i class="fa-solid <?= $stat[5]; ?>"></i></span><span class="search-stat-content"><span class="search-stat-number-row"><strong class="search-stat-value"><?= (int)$stat[1]; ?></strong><small class="search-stat-percent">/ <?= htmlspecialchars($stat[2],ENT_QUOTES,'UTF-8'); ?></small></span><span class="search-stat-label"><?= htmlspecialchars($stat[0],ENT_QUOTES,'UTF-8'); ?></span></span></a><?php } ?>
</div>
