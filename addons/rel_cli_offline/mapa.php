<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/offline-filters.php';
$requested = isset($_POST['login']) && is_array($_POST['login']) ? $_POST['login'] : array();
$logins = array();
foreach ($requested as $login) if (is_string($login) && strlen($login) <= 255 && trim($login) !== '') $logins[$login] = $login;
if (count($logins) > 2000) { http_response_code(400); exit('Selecione até 2000 clientes por mapa.'); }
$points = array();
if ($logins) {
    $quoted = array();
    foreach ($logins as $login) $quoted[] = "'".mysqli_real_escape_string($link, $login)."'";
    $sql = "SELECT cli.login,cli.nome,cli.coordenadas,cli.ramal,cli.celular,cli.fone,cli.email,cli.tipo,cli.plano,cli.ssid,cli.porta_olt,cli.caixa_herm,cli.porta_splitter,cli.onu_ont,cli.bloqueado,
        recent.acctstoptime,
        EXISTS(SELECT 1 FROM radacct live WHERE live.username=cli.login AND live.acctstoptime IS NULL LIMIT 1) AS online,
        TIMESTAMPDIFF(SECOND,recent.acctstoptime,NOW()) AS offline_seconds
        FROM sis_cliente cli LEFT JOIN radacct recent ON recent.radacctid=(SELECT last_session.radacctid FROM radacct last_session WHERE last_session.username=cli.login ORDER BY last_session.radacctid DESC LIMIT 1)
        WHERE cli.cli_ativado='s' AND cli.login IN (".implode(',', $quoted).")";
    $result = mysqli_query($link, $sql);
    if (!$result) { error_log(mysqli_error($link)); http_response_code(500); exit('Não foi possível consultar o mapa.'); }
    while ($row = mysqli_fetch_assoc($result)) {
        $coordinates = preg_split('/\s*,\s*/', trim((string)$row['coordenadas']));
        if (count($coordinates)!==2 || !is_numeric($coordinates[0]) || !is_numeric($coordinates[1])) { $row['lat']=null; $row['lon']=null; $points[]=$row; continue; }
        $lat=(float)$coordinates[0]; $lon=(float)$coordinates[1];
        if (!is_finite($lat)||!is_finite($lon)||abs($lat)>90||abs($lon)>180||($lat==0&&$lon==0)) { $row['lat']=null; $row['lon']=null; $points[]=$row; continue; }
        $row['lat']=$lat; $row['lon']=$lon; $points[]=$row;
    }
}
mysqli_close($link);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clientes offline · Mapa</title><link rel="stylesheet" href="vendor/leaflet.css"><link rel="stylesheet" href="offline-map.css?v=20261019-8"></head><body class="<?= isset($_GET['embed']) && $_GET['embed'] === '1' ? 'off-map-embedded' : '' ?>">
<div id="offline-map" aria-label="Mapa dos clientes selecionados"></div>
<aside class="off-client-panel" aria-label="Clientes selecionados"><header class="off-panel-heading"><span>MONITORAMENTO DE CLIENTES</span><strong>Clientes do mapa</strong><p>Localização e tempo de desconexão</p><button type="button" id="off-map-return">← Voltar ao MK-AUTH</button></header><label for="off-client-search">Buscar por nome ou login</label><input id="off-client-search" type="search" placeholder="Digite nome ou login" autocomplete="off"><span id="off-client-count" role="status"></span><div id="off-client-list" tabindex="0" aria-label="Lista de clientes"></div><div id="off-client-details" aria-live="polite"></div></aside>
<aside class="off-map-caption"><strong>Mapa de clientes</strong><span><?= count($points) ?> com coordenadas válidas</span><small>Verde: online · Vermelho: offline · Cinza: bloqueado</small><button type="button" id="off-map-back">Voltar ao relatório</button></aside>
<?php if (!$points): ?><p class="off-map-empty" role="status">Selecione clientes no relatório para mostrar nesta tela.</p><?php endif; ?>
<script type="application/json" id="off-map-data"><?= json_encode($points, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<script src="vendor/leaflet.js"></script><script src="offline-map.js?v=20261019-8"></script></body></html>
