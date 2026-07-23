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
    <title>Escolher Data de Desbloqueio</title>
    <link rel="icon" href="img/desbloqueio_favicon.ico" type="image/x-icon"> <!-- Favicon adicionado aqui -->
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        form {
            display: inline-block;
            margin: auto;
        }
    </style>
</head>
<body>
     <h2 style="font-size: 16px;">Informe a Data do Término do  Desbloqueio em Confiança</h2>
    <p>Login do cliente: <strong><?= htmlspecialchars($login_cliente); ?></strong></p> 
    <form action="processar_obs.php" method="POST">
        <input type="hidden" name="login" value="<?= htmlspecialchars($login_cliente); ?>">  
        <input type="date" id="data_desbloqueio" name="data_desbloqueio" value="<?= date('Y-m-d'); ?>" min="<?= date('Y-m-d'); ?>" required><br><br>
        <button type="submit">Confirmar</button>
    </form>
</body>
</html>
