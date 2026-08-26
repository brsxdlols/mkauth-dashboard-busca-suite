<?php include('nav/header.php'); ?>
<body class="">
<?php include('../../topo.php'); ?>
<?php require_once __DIR__ . '/../shared/layout_mode.php'; mka_suite_render_top_spacing_style($link); ?>

<nav class="contract-toolbar mka-suite-content-start" aria-label="Navegação de contratos">
    <a href="#" onclick="window.history.back(); return false;"><i class="bi bi-arrow-left-circle-fill"></i><span>Voltar</span></a>
    <a href="index.php"><i class="bi bi-house-door-fill"></i><span><?= mka_contract_escape($Manifest->{'name'} . ' - V ' . $Manifest->{'version'}); ?></span></a>
    <a href="cli_conn_alerta.php"><i class="bi bi-exclamation-circle-fill"></i><span>Alertas</span></a>
    <a href="chamados_abertos.php"><i class="bi bi-headset"></i><span>Chamados</span></a>
    <a href="score.php"><i class="bi bi-bar-chart-fill"></i><span>Score</span></a>
    <a href="relcontratos.php" class="is-active"><i class="bi bi-file-earmark-text-fill"></i><span>Contratos</span></a>
    <a href="cfg.php"><i class="bi bi-gear-fill"></i><span>Configurações</span></a>
    <a href="#" onclick="window.print(); return false;"><i class="bi bi-printer-fill"></i><span>Imprimir</span></a>
</nav>

<?php
mka_contract_ensure_schema($link);
$busca = isset($_GET['busca']) ? trim((string) $_GET['busca']) : '';

$query = "
    SELECT c.uuid_cliente, c.nome, c.login, c.cadastro, c.fone, c.ssid, c.celular, c.celular2, c.plano, c.contrato,
           p.valor, sc.nome AS contrato_nome
    FROM sis_cliente c
    LEFT JOIN sis_plano p ON c.plano = p.nome
    LEFT JOIN sis_contrato sc ON c.contrato = sc.codigo
    WHERE c.cli_ativado = 's'
";

if ($busca !== '') {
    $busca_sql = mysqli_real_escape_string($link, $busca);
    $query .= "
        AND (
            c.nome LIKE '%{$busca_sql}%'
            OR c.login LIKE '%{$busca_sql}%'
            OR c.cadastro LIKE '%{$busca_sql}%'
            OR c.fone LIKE '%{$busca_sql}%'
            OR c.ssid LIKE '%{$busca_sql}%'
            OR c.celular LIKE '%{$busca_sql}%'
            OR c.celular2 LIKE '%{$busca_sql}%'
            OR c.plano LIKE '%{$busca_sql}%'
            OR c.tags LIKE '%{$busca_sql}%'
        )
    ";
}

$query .= " ORDER BY c.nome ASC";
$result = mysqli_query($link, $query);

$rows = array();
$totals = array('active' => 0, 'warning' => 0, 'expired' => 0, 'missing' => 0);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $contract = mka_contract_get_latest($link, $row['uuid_cliente'], $row['login']);
        if (!$contract) {
            $contract = mka_contract_get_native_signed($row['uuid_cliente'], $row['contrato_nome']);
        }
        $status = mka_contract_build_status($contract);
        $totals[$status['status']]++;

        $rows[] = array(
            'uuid' => $row['uuid_cliente'],
            'nome' => $row['nome'],
            'login' => $row['login'],
            'cadastro' => $row['cadastro'],
            'fone' => trim($row['fone'] . ' ' . $row['celular'] . ' ' . $row['celular2']),
            'ssid' => $row['ssid'],
            'plano' => $row['plano'],
            'valor' => 'R$ ' . number_format((float) $row['valor'], 2, ',', '.'),
            'contrato_nome' => $row['contrato_nome'],
            'status' => $status,
        );
    }
}
?>

<style>
    .contract-toolbar { display:flex; align-items:center; justify-content:center; flex-wrap:wrap; gap:8px; margin:10px 0 18px; padding:10px; border:1px solid #dbe5f0; border-radius:16px; background:#fff; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .contract-toolbar a { display:inline-flex; align-items:center; gap:7px; padding:9px 12px; border-radius:11px; color:#36506c; text-decoration:none; font-size:13px; font-weight:700; transition:background .18s ease,color .18s ease,transform .18s ease; }
    .contract-toolbar a:hover { background:#edf5ff; color:#1268db; transform:translateY(-1px); }
    .contract-toolbar a.is-active { background:#1268db; color:#fff; }
    .contract-toolbar i { font-size:16px; }
    .contract-search { display:flex; align-items:flex-end; gap:10px; margin:0 0 16px; padding:14px; border:1px solid #dbe5f0; border-radius:16px; background:#fff; }
    .contract-search label { flex:1 1 auto; margin:0; color:#334155; font-size:13px; font-weight:700; }
    .contract-search input { display:block; width:100%; margin-top:6px; padding:11px 13px; border:1px solid #cbd8e8; border-radius:10px; box-sizing:border-box; font-size:14px; }
    .contract-search button { flex:0 0 auto; min-height:42px; padding:0 20px; border:0; border-radius:10px; background:#1268db; color:#fff; font-weight:700; cursor:pointer; }
    .contract-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin:12px 0 18px; }
    .contract-summary-card { background:#fff; border:1px solid #dbe5f0; border-radius:18px; padding:16px; box-shadow:0 12px 30px rgba(15,23,42,.06); }
    .contract-summary-card h3 { margin:0 0 8px; font-size:13px; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
    .contract-summary-card strong { display:block; font-size:34px; line-height:1; }
    .contract-summary-card p { margin:8px 0 0; color:#475569; font-weight:700; }
    .contract-summary-card.is-active strong { color:#15803d; }
    .contract-summary-card.is-warning strong { color:#b45309; }
    .contract-summary-card.is-expired strong { color:#dc2626; }
    .contract-summary-card.is-missing strong { color:#334155; }
    .contract-status-chip { display:inline-flex; align-items:center; gap:8px; border-radius:999px; padding:6px 12px; font-weight:700; font-size:12px; }
    .contract-status-chip.contract-active { background:#e8f8ef; color:#157347; }
    .contract-status-chip.contract-warning { background:#fff3cd; color:#946200; }
    .contract-status-chip.contract-expired { background:#fde7ea; color:#b42318; }
    .contract-status-chip.contract-missing { background:#edf2f7; color:#445469; }
    .contract-action-link { display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-weight:700; }
    .contract-action-group { display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
    .contract-view-link { display:inline-flex; align-items:center; gap:6px; color:#157347; text-decoration:none; font-weight:700; }
    .contract-table-wrap { width:100%; overflow-x:auto; border:1px solid #dbe5f0; border-radius:16px; background:#fff; }
    .contract-table { width:100%; min-width:1120px; margin:0; border-collapse:collapse; }
    .contract-table th { padding:12px 11px; background:#29313a; color:#fff; text-align:left; font-size:12px; letter-spacing:.03em; }
    .contract-table td { padding:11px; vertical-align:middle; }
    .contract-table tbody tr:nth-child(even) { background:#f6f8fb; }
    .contract-client-link { color:#193755; text-decoration:none; }
    .contract-client-link:hover { color:#1268db; text-decoration:underline; }
    @media (max-width: 900px) { .contract-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 560px) { .contract-summary-grid { grid-template-columns:1fr; } .contract-search { align-items:stretch; flex-direction:column; } .contract-search button { width:100%; } .contract-toolbar span { display:none; } }
</style>

<form action="" method="get" class="contract-search">
    <label>Busca integrada
        <input type="search" name="busca" placeholder="Pesquisar nome, login, plano, tecnologia, telefone, tags ou cadastro" value="<?= mka_contract_escape($busca); ?>" />
    </label>
    <button type="submit"><i class="bi bi-search"></i> Pesquisar</button>
</form>

<div class="contract-summary-grid">
    <div class="contract-summary-card is-active"><h3>Contrato ativo</h3><strong><?= $totals['active']; ?></strong><p>vigência em dia</p></div>
    <div class="contract-summary-card is-warning"><h3>A vencer</h3><strong><?= $totals['warning']; ?></strong><p>renovar logo</p></div>
    <div class="contract-summary-card is-expired"><h3>Expirado</h3><strong><?= $totals['expired']; ?></strong><p>pedindo renovação</p></div>
    <div class="contract-summary-card is-missing"><h3>Sem contrato</h3><strong><?= $totals['missing']; ?></strong><p>aguarda ativação</p></div>
</div>

<div class="contract-table-wrap">
<table class="contract-table small">
    <thead><tr>
        <th>INÍCIO</th>
        <th>VENCIMENTO</th>
        <th>NOME</th>
        <th>TECNOLOGIA</th>
        <th>FONE</th>
        <th>PLANO</th>
        <th>VALOR PLANO</th>
        <th>SITUAÇÃO ATUAL</th>
        <th>AÇÃO</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $row) {
        $status = $row['status'];
        $start = $status['start_date'] ? date('d/m/Y', strtotime($status['start_date'])) : '--';
        $end = $status['end_date'] ? date('d/m/Y', strtotime($status['end_date'])) : '--';
        $label = $status['label'];
        if ($status['status'] === 'expired' && $status['days'] !== null) {
            $label .= ' há ' . abs((int) $status['days']) . ' dias';
        } elseif ($status['status'] === 'warning' && $status['days'] !== null) {
            $label .= ' em ' . abs((int) $status['days']) . ' dias';
        }
    ?>
    <tr>
        <td><?= $start; ?></td>
        <td><?= $end; ?></td>
        <td><a class="contract-client-link" href="../../cliente_alt<?= $ext_mk; ?>?uuid=<?= urlencode($row['uuid']); ?>" target="_blank"><?= mka_contract_escape($row['nome']); ?> <b>[<?= mka_contract_escape($row['login']); ?>]</b></a></td>
        <td><?= mka_contract_escape($row['ssid']); ?></td>
        <td><?= mka_contract_escape(trim($row['fone'])); ?></td>
        <td><?= mka_contract_escape($row['plano']); ?></td>
        <td><?= $row['valor']; ?></td>
        <td><span class="contract-status-chip <?= $status['class']; ?>"><i class="<?= mka_contract_escape($status['icon']); ?>"></i><?= mka_contract_escape($label); ?></span></td>
        <td>
            <div class="contract-action-group">
                <?php if (!empty($status['pdf_url'])) { ?>
                    <a class="contract-view-link" href="<?= mka_contract_escape($status['pdf_url']); ?>" target="_blank"><i class="fa-solid fa-file-pdf"></i>Visualizar</a>
                <?php } ?>
                <a href="#" class="contract-action-link" onclick="abrirJanela('contrato_popup.php?uuid=<?= urlencode($row['uuid']); ?>&login=<?= urlencode($row['login']); ?>&nome=<?= urlencode($row['nome']); ?>', 620, 760); return false;"><i class="fa-solid fa-file-signature"></i><?= $status['status'] === 'missing' ? 'Ativar vigência' : 'Renovar'; ?></a>
            </div>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>
</div>

<?php include('../../baixo.php'); ?>
<script src="../../menu.js.php"></script>
<?php include('../../rodape.php'); ?>
</body>
</html>
