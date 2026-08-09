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
    <title>Escolher Dias de Observação</title>
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
    <h2>Dias em Observação 1 a 30</h2>
<form action="processar_obs.php" method="POST">
    <input type="hidden" name="login" value="<?= htmlspecialchars($login_cliente); ?>">
    <label for="dias">Quantidade de Dias:</label><br>
    <input type="number" id="dias" name="dias_desbloqueio" value="1" min="1" max="30" required><br><br>
    <button type="submit">Confirmar</button>
</form>

</body>
</html>
