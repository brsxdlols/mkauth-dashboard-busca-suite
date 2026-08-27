<?php
include('config.php');

mka_contract_ensure_schema($link);

$uuid_cliente = isset($_GET['uuid']) ? trim((string) $_GET['uuid']) : '';
$login_cliente = isset($_GET['login']) ? trim((string) $_GET['login']) : '';
$nome_cliente = isset($_GET['nome']) ? trim((string) $_GET['nome']) : $login_cliente;
$usuario_acao = isset($_SESSION['MKA_Usuario']) && $_SESSION['MKA_Usuario'] !== '' ? $_SESSION['MKA_Usuario'] : (isset($_SESSION['MM_Usuario']) ? $_SESSION['MM_Usuario'] : 'sistema');

$message = '';
$message_class = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uuid_cliente = isset($_POST['uuid']) ? trim((string) $_POST['uuid']) : $uuid_cliente;
    $login_cliente = isset($_POST['login']) ? trim((string) $_POST['login']) : $login_cliente;
    $nome_cliente = isset($_POST['nome']) ? trim((string) $_POST['nome']) : $nome_cliente;
    $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : 12;
    $start_date = isset($_POST['start_date']) ? trim((string) $_POST['start_date']) : date('Y-m-d');

    if ($uuid_cliente !== '' && $login_cliente !== '') {
        mka_contract_upsert($link, $uuid_cliente, $login_cliente, $duration_months, $usuario_acao, $start_date);
        $message = 'Contrato atualizado com sucesso.';
    } else {
        $message = 'Não foi possível identificar o cliente.';
        $message_class = 'error';
    }
}

$native_contract_name = '';
$uuid_sql = mysqli_real_escape_string($link, $uuid_cliente);
$login_sql = mysqli_real_escape_string($link, $login_cliente);
$native_result = @mysqli_query($link, "
    SELECT sc.nome AS contrato_nome
    FROM sis_cliente c
    LEFT JOIN sis_contrato sc ON c.contrato = sc.codigo
    WHERE c.uuid_cliente = '{$uuid_sql}' OR c.login = '{$login_sql}'
    ORDER BY (c.uuid_cliente = '{$uuid_sql}') DESC
    LIMIT 1
");
if ($native_result && ($native_row = mysqli_fetch_assoc($native_result))) {
    $native_contract_name = isset($native_row['contrato_nome']) ? trim((string) $native_row['contrato_nome']) : '';
}

$latest_contract = mka_contract_get_latest($link, $uuid_cliente, $login_cliente);
if (!$latest_contract) {
    $latest_contract = mka_contract_get_native_signed($uuid_cliente, $native_contract_name);
}
$status_info = mka_contract_build_status($latest_contract);
$durations = mka_contract_allowed_durations();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato do Cliente</title>
    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <style>
        body { margin: 0; background: #eef4fb; font-family: Arial, Helvetica, sans-serif; color: #17324d; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 560px; background: #fff; border-radius: 22px; box-shadow: 0 24px 60px rgba(18, 38, 63, .18); overflow: hidden; }
        .head { padding: 22px 26px; background: linear-gradient(135deg, #fff6cf 0%, #ffe996 100%); border-bottom: 1px solid rgba(188, 145, 0, .18); }
        .eyebrow { font-size: 12px; text-transform: uppercase; letter-spacing: .12em; color: #b07b00; font-weight: 700; }
        .head h1 { margin: 10px 0 6px; font-size: 32px; line-height: 1.05; }
        .head p { margin: 0; color: #5b6880; }
        .body { padding: 26px; }
        .status { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px; font-weight: 700; margin-bottom: 18px; }
        .status.contract-active { background: #e8f8ef; color: #157347; }
        .status.contract-warning { background: #fff3cd; color: #946200; }
        .status.contract-expired { background: #fde7ea; color: #b42318; }
        .status.contract-missing { background: #edf2f7; color: #445469; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .field { background: #f7faff; border: 1px solid #dbe5f0; border-radius: 16px; padding: 14px 16px; }
        .field small { display: block; margin-bottom: 6px; color: #67758f; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .field strong { font-size: 18px; }
        .field input, .field select { width: 100%; border: 1px solid #c8d5e6; border-radius: 12px; font-size: 16px; padding: 12px 14px; box-sizing: border-box; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
        .btn { border: 0; border-radius: 14px; font-size: 16px; font-weight: 700; padding: 12px 18px; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #edf2f7; color: #334155; }
        .flash { margin-bottom: 16px; padding: 12px 14px; border-radius: 14px; font-weight: 700; }
        .flash.success { background: #e8f8ef; color: #157347; }
        .flash.error { background: #fde7ea; color: #b42318; }
        @media (max-width: 640px) {
            .head h1 { font-size: 26px; }
            .grid { grid-template-columns: 1fr; }
            .actions { flex-direction: column-reverse; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <div class="eyebrow">Busca Inteligente</div>
                <h1>Renovação de contrato</h1>
                <p><?= mka_contract_escape($nome_cliente); ?> [<?= mka_contract_escape($login_cliente); ?>]</p>
            </div>
            <div class="body">
                <?php if ($message !== '') { ?>
                    <div class="flash <?= $message_class; ?>"><?= mka_contract_escape($message); ?></div>
                <?php } ?>

                <div class="status <?= $status_info['class']; ?>">
                    <i class="<?= mka_contract_escape($status_info['icon']); ?>"></i>
                    <span><?= mka_contract_escape($status_info['label']); ?></span>
                </div>

                <div class="grid">
                    <div class="field">
                        <small>Início atual</small>
                        <strong><?= $status_info['start_date'] ? date('d/m/Y', strtotime($status_info['start_date'])) : '--'; ?></strong>
                    </div>
                    <div class="field">
                        <small>Vencimento atual</small>
                        <strong><?= $status_info['end_date'] ? date('d/m/Y', strtotime($status_info['end_date'])) : '--'; ?></strong>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="uuid" value="<?= mka_contract_escape($uuid_cliente); ?>">
                    <input type="hidden" name="login" value="<?= mka_contract_escape($login_cliente); ?>">
                    <input type="hidden" name="nome" value="<?= mka_contract_escape($nome_cliente); ?>">

                    <div class="grid">
                        <label class="field">
                            <small>Nova vigência começa em</small>
                            <input type="date" name="start_date" value="<?= date('Y-m-d'); ?>">
                        </label>
                        <label class="field">
                            <small>Prazo do contrato</small>
                            <select name="duration_months">
                                <?php foreach ($durations as $duration) { ?>
                                    <option value="<?= $duration; ?>"><?= $duration; ?> <?= $duration === 1 ? 'mês' : 'meses'; ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    </div>

                    <div class="actions">
                        <button type="button" class="btn btn-secondary" onclick="window.close()">Fechar</button>
                        <button type="submit" class="btn btn-primary"><?= $status_info['status'] === 'missing' ? 'Ativar contrato' : 'Renovar contrato'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php if ($message !== '' && $message_class === 'success') { ?>
    <script>
        if (window.opener && !window.opener.closed) {
            try { window.opener.location.reload(); } catch (error) {}
        }
    </script>
    <?php } ?>
</body>
</html>
