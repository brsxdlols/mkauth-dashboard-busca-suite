<?php
require_once('database/db_connect.php');
require_once __DIR__ . '/../shared/layout_mode.php';
if (!isset($_GET['login'])) {
    die("Login do cliente não fornecido.");
}

$login_cliente = $_GET['login'];
$trust_settings = mka_suite_get_trust_unlock_settings($conn);
$unlock_mode = $trust_settings['mode'];
$fixed_days = $trust_settings['fixed_days'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desbloqueio de Confiança</title>
    <link rel="icon" href="img/desbloqueio_favicon.ico" type="image/x-icon">
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(180deg, #f6f9fc 0%, #edf3f9 100%);
            color: #17324a;
        }

        .unlock-card {
            width: min(100%, 460px);
            background: #ffffff;
            border: 1px solid rgba(209, 219, 230, 0.9);
            border-radius: 18px;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.16);
            overflow: hidden;
        }

        .unlock-head {
            padding: 18px 22px 14px;
            background: linear-gradient(135deg, #fff7d9 0%, #fff1b8 100%);
            border-bottom: 1px solid rgba(229, 194, 84, 0.28);
        }

        .unlock-eyebrow {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #9a6a00;
        }

        .unlock-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #17324a;
        }

        .unlock-body {
            padding: 20px 22px 22px;
        }

        .unlock-copy {
            margin: 0 0 14px;
            line-height: 1.5;
            color: #496176;
        }

        .unlock-login {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f6f9fc;
            color: #17324a;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #355167;
        }

        input[type="date"], select {
            width: 100%;
            height: 44px;
            padding: 10px 12px;
            border: 1px solid #cfd9e3;
            border-radius: 12px;
            font-size: 15px;
            color: #17324a;
            background: #fff;
        }

        .unlock-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        button {
            border: 0;
            border-radius: 12px;
            padding: 11px 18px;
            background: #2563eb;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.22);
        }

        button:hover {
            background: #1d4ed8;
        }

        .unlock-option { margin-top: 14px; }
        .unlock-fixed { padding: 13px 14px; border-radius: 12px; background: #eef5ff; color: #244867; font-weight: 600; }
        .unlock-choice { display: flex; gap: 16px; margin: 4px 0 12px; }
        .unlock-choice label { display: inline-flex; align-items: center; gap: 6px; margin: 0; cursor: pointer; }
    </style>
</head>
<body>
    <div class="unlock-card">
        <div class="unlock-head">
            <p class="unlock-eyebrow">Busca Inteligente</p>
            <h1 class="unlock-title">Desbloqueio de Confiança</h1>
        </div>
        <div class="unlock-body">
            <p class="unlock-copy">Defina por quanto tempo este cliente ficará liberado em confiança. A observação será removida automaticamente ao final do período.</p>
            <p class="unlock-login">Login do cliente: <strong><?= htmlspecialchars($login_cliente); ?></strong></p>
            <form action="processar_obs.php" method="POST">
                <input type="hidden" name="login" value="<?= htmlspecialchars($login_cliente); ?>">
                <?php if ($unlock_mode === 'days') { ?>
                    <label for="dias_desbloqueio">Período do desbloqueio</label>
                    <select id="dias_desbloqueio" name="dias_desbloqueio" required>
                        <?php for ($day = 1; $day <= 10; $day++) { ?>
                            <option value="<?= $day; ?>"><?= $day; ?> <?= $day === 1 ? 'dia' : 'dias'; ?></option>
                        <?php } ?>
                    </select>
                <?php } elseif ($unlock_mode === 'fixed') { ?>
                    <label>Período definido pelo administrador</label>
                    <div class="unlock-fixed"><?= $fixed_days; ?> <?= $fixed_days === 1 ? 'dia' : 'dias'; ?></div>
                <?php } elseif ($unlock_mode === 'all') { ?>
                    <div class="unlock-choice">
                        <label><input type="radio" name="unlock_choice" value="days" checked> Escolher dias</label>
                        <label><input type="radio" name="unlock_choice" value="date"> Escolher data</label>
                    </div>
                    <div class="unlock-option" id="unlock-days-option">
                        <label for="dias_desbloqueio">Período do desbloqueio</label>
                        <select id="dias_desbloqueio" name="dias_desbloqueio">
                            <?php for ($day = 1; $day <= 10; $day++) { ?>
                                <option value="<?= $day; ?>"><?= $day; ?> <?= $day === 1 ? 'dia' : 'dias'; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="unlock-option" id="unlock-date-option" hidden>
                        <label for="data_desbloqueio">Data final do desbloqueio</label>
                        <input type="date" id="data_desbloqueio" name="data_desbloqueio" value="<?= date('Y-m-d', strtotime('+1 day')); ?>" min="<?= date('Y-m-d'); ?>">
                    </div>
                <?php } else { ?>
                    <label for="data_desbloqueio">Data final do desbloqueio</label>
                    <input type="date" id="data_desbloqueio" name="data_desbloqueio" value="<?= date('Y-m-d', strtotime('+1 day')); ?>" min="<?= date('Y-m-d'); ?>" required>
                <?php } ?>
                <div class="unlock-actions">
                    <button type="submit">Confirmar desbloqueio</button>
                </div>
            </form>
        </div>
    </div>
    <?php if ($unlock_mode === 'all') { ?>
        <script>
            document.querySelectorAll('input[name="unlock_choice"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    document.getElementById('unlock-days-option').hidden = this.value !== 'days';
                    document.getElementById('unlock-date-option').hidden = this.value !== 'date';
                });
            });
        </script>
    <?php } ?>
</body>
</html>
