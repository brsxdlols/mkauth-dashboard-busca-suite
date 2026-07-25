<?php
if (!isset($_GET['login'])) {
    die("Login do cliente não fornecido.");
}

$login_cliente = $_GET['login'];
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
            width: min(100%, 380px);
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

        input[type="date"] {
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
    </style>
</head>
<body>
    <div class="unlock-card">
        <div class="unlock-head">
            <p class="unlock-eyebrow">Busca Inteligente</p>
            <h1 class="unlock-title">Desbloqueio de Confiança</h1>
        </div>
        <div class="unlock-body">
            <p class="unlock-copy">Defina até quando este cliente ficará liberado em confiança. A observação será removida automaticamente na data escolhida.</p>
            <p class="unlock-login">Login do cliente: <strong><?= htmlspecialchars($login_cliente); ?></strong></p>
            <form action="processar_obs.php" method="POST">
                <input type="hidden" name="login" value="<?= htmlspecialchars($login_cliente); ?>">
                <label for="data_desbloqueio">Data final do desbloqueio</label>
                <input type="date" id="data_desbloqueio" name="data_desbloqueio" value="<?= date('Y-m-d'); ?>" min="<?= date('Y-m-d'); ?>" required>
                <div class="unlock-actions">
                    <button type="submit">Confirmar desbloqueio</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
