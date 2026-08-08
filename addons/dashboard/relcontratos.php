<?php include('nav/header.php'); ?>
<body class="">
<?php include('../../topo.php'); ?>

<ul class="nav nav-tabs justify-content-center py-2">
    <li class="nav-item"><a href="#" class="nav-link" onClick="window.history.back()"><i class="fa-solid fa-circle-chevron-left fs-4"></i></a></li>
    <li class="nav-item"><a href="index.php" class="nav-link"><?= $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a></li>
    <li class="nav-item"><a href="cli_conn_alerta.php" class="nav-link"><i class="fa-solid fa-circle-exclamation fs-4 text-warning"></i></a></li>
    <li class="nav-item"><a href="chamados_abertos.php" class="nav-link"><i class="fa-solid fa-headset fs-4"></i></a></li>
    <li class="nav-item"><a href="score.php" class="nav-link"><i class="fa-solid fa-ranking-star fs-4"></i></a></li>
    <li class="nav-item"><a href="relcontratos.php" class="nav-link active"><i class="fa-solid fa-file-signature fs-4"></i></a></li>
    <li class="nav-item"><a href="cfg.php" class="nav-link"><i class="fa-solid fa-gear fs-4"></i></a></li>
    <li class="nav-item"><a href="#" class="nav-link" onClick="window.print()"><i class="fa-solid fa-print fs-4"></i></a></li>
</ul>

<?php
mka_contract_ensure_schema($conn);
$busca = isset($_GET['busca']) ? trim((string) $_GET['busca']) : '';

$query = "
    SELECT c.uuid_cliente, c.nome, c.login, c.cadastro, c.fone, c.ssid, c.celular, c.celular2, c.plano, p.valor
    FROM sis_cliente c
    LEFT JOIN sis_plano p ON c.plano = p.nome
    WHERE c.cli_ativado = 's'
";

if ($busca !== '') {
    $busca_sql = mysqli_real_escape_string($conn, $busca);
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
$result = mysqli_query($conn, $query);

$rows = array();
$totals = array('active' => 0, 'warning' => 0, 'expired' => 0, 'missing' => 0);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $contract = mka_contract_get_latest($conn, $row['uuid_cliente'], $row['login']);
        $status = mka_contract_build_status($contract);
        $totals[$status['status']]++;
        $rows[] = array(
            'uuid' => $row['uuid_cliente'],
            'nome' => $row['nome'],
            'login' => $row['login'],
            'fone' => trim($row['fone'] . ' ' . $row['celular'] . ' ' . $row['celular2']),
            'ssid' => $row['ssid'],
            'plano' => $row['plano'],
            'valor' => 'R$ ' . number_format((float) $row['valor'], 2, ',', '.'),
            'status' => $status,
        );
    }
}
?>

<style>
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
    @media (max-width: 900px) { .contract-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 560px) { .contract-summary-grid { grid-template-columns:1fr; } }
</style>

<form action="" method="get" class="mb-3">
    <table class="form_graf">
        <tr><td class="buscar"><b>Busca Integrada :</b></td><td></td></tr>
        <tr>
            <td><input type="text" name="busca" class="buscar" placeholder="Pesquisar Nome, Login, Plano, SSID, Telefone, Tags ou Data Cadastro" value="<?= mka_contract_escape($busca); ?>" /></td>
            <td><input type="submit" name="submit" id="btn_buscar" value="OK" /></td>
        </tr>
    </table>
</form>

<div class="contract-summary-grid">
    <div class="contract-summary-card is-active"><h3>Contrato ativo</h3><strong><?= $totals['active']; ?></strong><p>vigência em dia</p></div>
    <div class="contract-summary-card is-warning"><h3>A vencer</h3><strong><?= $totals['warning']; ?></strong><p>renovar logo</p></div>
    <div class="contract-summary-card is-expired"><h3>Expirado</h3><strong><?= $totals['expired']; ?></strong><p>pedindo renovação</p></div>
    <div class="contract-summary-card is-missing"><h3>Sem contrato</h3><strong><?= $totals['missing']; ?></strong><p>aguarda ativação</p></div>
</div>

<table class="table table-sm table-hover table-striped small">
    <tr class="table-dark fw-bold">
        <td>INÍCIO</td>
        <td>VENCIMENTO</td>
        <td>NOME</td>
        <td>TECNOLOGIA</td>
        <td>FONE</td>
        <td>PLANO</td>
        <td>VALOR PLANO</td>
        <td>SITUAÇÃO ATUAL</td>
        <td>AÇÃO</td>
    </tr>
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
        <td><a href="../../cliente_alt<?= $ext_mk; ?>?uuid=<?= urlencode($row['uuid']); ?>" target="_blank"><?= mka_contract_escape($row['nome']); ?> <b>[<?= mka_contract_escape($row['login']); ?>]</b></a></td>
        <td><?= mka_contract_escape($row['ssid']); ?></td>
        <td><?= mka_contract_escape(trim($row['fone'])); ?></td>
        <td><?= mka_contract_escape($row['plano']); ?></td>
        <td><?= $row['valor']; ?></td>
        <td><span class="contract-status-chip <?= $status['class']; ?>"><i class="<?= mka_contract_escape($status['icon']); ?>"></i><?= mka_contract_escape($label); ?></span></td>
        <td><a href="#" class="contract-action-link" onclick="abrirJanela('../busca_inteligente/contrato_popup.php?uuid=<?= urlencode($row['uuid']); ?>&login=<?= urlencode($row['login']); ?>&nome=<?= urlencode($row['nome']); ?>', 620, 760); return false;"><i class="fa-solid fa-file-signature"></i><?= $status['status'] === 'missing' ? 'Ativar contrato' : 'Renovar'; ?></a></td>
    </tr>
    <?php } ?>
</table>
</body>
</html>
