<?php
require_once('database/db_connect.php');

// Verifica se os dados foram enviados via POST.
if (isset($_POST['login']) && isset($_POST['data_desbloqueio'])) {
    $login = mysqli_real_escape_string($conn, $_POST['login']);
    $dataDesbloqueio = $_POST['data_desbloqueio']; // A data será passada como string no formato 'YYYY-MM-DD'

    // Valida se a data é válida.
    $dataAtual = date('Y-m-d');
    if ($dataDesbloqueio < $dataAtual) {
        die("A data de desbloqueio não pode ser anterior à data atual.");
    }

    // Formata a data para exibição em DD/MM/YYYY
    $dataBr = date('d/m/Y', strtotime($dataDesbloqueio));

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

        // Atualiza no banco com a data no formato YYYY-MM-DD
        $sql = "UPDATE sis_cliente SET rem_obs = '{$dataDesbloqueio}', observacao = 'sim' WHERE id = '{$id}'";

        if (mysqli_query($conn, $sql)) {
            echo "Desbloqueio confirmado com sucesso!<br>";
            echo "Cliente <strong>{$nome}</strong> estará desbloqueado até <strong>{$dataBr}</strong>.<br>";
        } else {
            echo "Erro ao atualizar o desbloqueio: " . mysqli_error($conn);
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
