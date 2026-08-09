<?php
require_once('database/db_connect.php');

// Verifica se os dados foram enviados via POST.
if (isset($_POST['login']) && isset($_POST['dias_desbloqueio'])) {
    $login = mysqli_real_escape_string($conn, $_POST['login']);
    $diasDesbloqueio = (int) $_POST['dias_desbloqueio'];

    // Valida o número de dias.
    if ($diasDesbloqueio < 1 || $diasDesbloqueio > 30) {
        die("<html>
                <head>
                    <title>Erro</title>
                    <link rel='icon' href='img/desbloqueio_favicon.ico' type='image/x-icon'>
                </head>
                <body>
                    Quantidade de dias inválida. Escolha um valor entre 1 e 30 dias.
                </body>
            </html>");
    }

    // Captura o ID e nome do cliente com base no login (exemplo de query).
    $query = "SELECT id, nome FROM sis_cliente WHERE login = '{$login}'";
    $result = mysqli_query($conn, $query);

    echo "<html>
            <head>
                <title>Resultado do Desbloqueio</title>
                <link rel='icon' href='img/desbloqueio_favicon.ico' type='image/x-icon'>
                <script type='text/javascript'>
                    var count = 10; // Tempo em segundos
                    function updateCountdown() {
                        document.getElementById('countdown').innerHTML = 'A página fechará em ' + count + ' segundos.';
                        if (count === 0) {
                            window.close();
                        } else {
                            count--;
                            setTimeout(updateCountdown, 1000); // Atualiza a cada 1 segundo
                        }
                    }
                    window.onload = updateCountdown; // Inicia a contagem regressiva quando a página carregar
                </script>
            </head>
            <body>";

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id = $row['id'];
        $nome = $row['nome'];

        // Calcula a nova data de desbloqueio.
        $data_nova = date('Y-m-d', strtotime("+{$diasDesbloqueio} days"));
        $data_Br = date('d-m-Y', strtotime("+{$diasDesbloqueio} days"));

        // Atualiza no banco.
        $sql = "UPDATE sis_cliente SET rem_obs = '{$data_nova}', observacao = 'sim' WHERE id = '{$id}'";

        if (mysqli_query($conn, $sql)) {
            echo "Desbloqueado com sucesso!<br>";
            echo "Cliente <strong>{$nome}</strong> estará bloqueado novamente em <strong>{$data_Br}</strong>.<br>";
        } else {
            echo "Erro ao desbloquear cliente: " . mysqli_error($conn);
        }

        echo "<p id='countdown'></p>"; // Exibe a contagem regressiva
    } else {
        echo "Cliente não encontrado.<br>";
        echo "<p id='countdown'></p>"; // Exibe a contagem regressiva
    }

    echo "</body></html>";
} else {
    echo "<html>
            <head>
                <title>Erro</title>
                <link rel='icon' href='img/desbloqueio_favicon.ico' type='image/x-icon'>
                <script type='text/javascript'>
                    var count = 10; // Tempo em segundos
                    function updateCountdown() {
                        document.getElementById('countdown').innerHTML = 'A página fechará em ' + count + ' segundos.';
                        if (count === 0) {
                            window.close();
                        } else {
                            count--;
                            setTimeout(updateCountdown, 1000); // Atualiza a cada 1 segundo
                        }
                    }
                    window.onload = updateCountdown; // Inicia a contagem regressiva quando a página carregar
                </script>
            </head>
            <body>
                Dados inválidos.<br>
                <p id='countdown'></p> <!-- Exibe a contagem regressiva -->
            </body>
        </html>";
}

// Fecha a conexão.
mysqli_close($conn);
?>
