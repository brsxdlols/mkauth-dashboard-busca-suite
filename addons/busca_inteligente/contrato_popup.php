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
    $duration_mode = isset($_POST['duration_mode']) ? trim((string) $_POST['duration_mode']) : 'months';
    $custom_end_date = isset($_POST['custom_end_date']) ? trim((string) $_POST['custom_end_date']) : '';
    $start_date = isset($_POST['start_date']) ? trim((string) $_POST['start_date']) : date('Y-m-d');

    if ($uuid_cliente !== '' && $login_cliente !== '') {
        $explicit_end_date = null;
        if ($duration_mode === 'custom') {
            $start_timestamp = strtotime($start_date);
            $end_timestamp = strtotime($custom_end_date);
            if ($custom_end_date === '' || $start_timestamp === false || $end_timestamp === false || $end_timestamp < $start_timestamp) {
                $message = 'Escolha uma data de vencimento igual ou posterior ao início da vigência.';
                $message_class = 'error';
            } else {
                $explicit_end_date = $custom_end_date;
            }
        }

        if ($message_class !== 'error') {
            mka_contract_upsert($link, $uuid_cliente, $login_cliente, $duration_months, $usuario_acao, $start_date, '', $explicit_end_date);
            $message = 'Contrato atualizado com sucesso.';
        }
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
$embedded_view = isset($_GET['embed']) && $_GET['embed'] === '1';
?>
<!DOCTYPE html>
<html lang="pt-BR"<?= $embedded_view ? ' class="is-embedded"' : ''; ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato do Cliente</title>
    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <style>
        body { margin: 0; background: #eef4fb; font-family: Arial, Helvetica, sans-serif; color: #17324d; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; }
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
        .field[hidden] { display: none !important; }
        .custom-end-field { grid-column: 1 / -1; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
        .btn { border: 0; border-radius: 14px; font-size: 16px; font-weight: 700; padding: 12px 18px; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #edf2f7; color: #334155; }
        .flash { margin-bottom: 16px; padding: 12px 14px; border-radius: 14px; font-weight: 700; }
        .flash.success { background: #e8f8ef; color: #157347; }
        .flash.error { background: #fde7ea; color: #b42318; }
        html.is-embedded,
        body.is-embedded { height: 100%; overflow: hidden; }
        body.is-embedded .wrap { min-height: 0; padding: 14px 18px 16px; align-items: flex-start; }
        body.is-embedded .card { max-width: none; border-radius: 18px; box-shadow: none; }
        body.is-embedded .head { padding: 16px 20px; }
        body.is-embedded .head h1 { margin: 7px 0 4px; font-size: 27px; }
        body.is-embedded .body { padding: 18px 20px 20px; }
        body.is-embedded .status { padding: 8px 12px; margin-bottom: 12px; }
        body.is-embedded .grid { gap: 10px; margin-bottom: 12px; }
        body.is-embedded .field { padding: 11px 14px; }
        body.is-embedded .field input,
        body.is-embedded .field select { padding: 10px 12px; }
        body.is-embedded .actions { margin-top: 12px; }
        @media (max-width: 640px) {
            .head h1 { font-size: 26px; }
            .grid { grid-template-columns: 1fr; }
            .actions { flex-direction: column-reverse; }
        }
        @media (min-width: 480px) {
            body.is-embedded .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            body.is-embedded .actions { flex-direction: row; }
        }
    </style>
</head>
<body<?= $embedded_view ? ' class="is-embedded"' : ''; ?>>
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
                            <select name="duration_mode" id="durationMode">
                                <?php foreach ($durations as $duration) { ?>
                                    <option value="months:<?= $duration; ?>"><?= $duration; ?> <?= $duration === 1 ? 'mês' : 'meses'; ?></option>
                                <?php } ?>
                                <option value="custom">Especificar data</option>
                            </select>
                            <input type="hidden" name="duration_months" id="durationMonths" value="<?= isset($durations[0]) ? (int) $durations[0] : 12; ?>">
                        </label>
                        <label class="field custom-end-field" id="customEndField" hidden>
                            <small>Vencimento final escolhido</small>
                            <input type="date" name="custom_end_date" id="customEndDate" min="<?= date('Y-m-d'); ?>">
                        </label>
                    </div>

                    <div class="actions">
                        <button type="button" class="btn btn-secondary" onclick="mkaCloseContractView()">Fechar</button>
                        <button type="submit" class="btn btn-primary"><?= $status_info['status'] === 'missing' ? 'Ativar contrato' : 'Renovar contrato'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var mode = document.getElementById('durationMode');
            var months = document.getElementById('durationMonths');
            var customField = document.getElementById('customEndField');
            var customDate = document.getElementById('customEndDate');
            var startDate = document.querySelector('input[name="start_date"]');

            function syncDurationMode() {
                var isCustom = mode.value === 'custom';
                customField.hidden = !isCustom;
                customDate.required = isCustom;
                if (!isCustom) months.value = mode.value.split(':')[1] || '12';
            }

            function syncMinimumDate() {
                customDate.min = startDate.value || '<?= date('Y-m-d'); ?>';
                if (customDate.value && customDate.value < customDate.min) customDate.value = customDate.min;
            }

            mode.addEventListener('change', syncDurationMode);
            startDate.addEventListener('change', syncMinimumDate);
            syncDurationMode();
            syncMinimumDate();
        }());

        function mkaCloseContractView() {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({type: 'mka-content-modal-close'}, window.location.origin);
                return;
            }
            window.close();
        }
    </script>
    <?php if ($message !== '' && $message_class === 'success') { ?>
    <script>
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({type: 'mka-content-modal-refresh'}, window.location.origin);
        } else if (window.opener && !window.opener.closed) {
            try { window.opener.location.reload(); } catch (error) {}
        }
    </script>
    <?php } ?>
</body>
</html>
