<?php
require_once('database/db_connect.php');
require_once __DIR__ . '/../shared/layout_mode.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Verifica se os dados foram enviados via POST.
if (isset($_POST['login'])) {
    $loginRaw = trim((string) $_POST['login']);
    $login = mysqli_real_escape_string($conn, $loginRaw);
    $isEmbed = isset($_POST['embed']) && $_POST['embed'] === '1';
    $trustSettings = mka_suite_get_trust_unlock_settings($conn);
    $configuredMode = $trustSettings['mode'];
    $requestedMode = $configuredMode === 'all' ? (isset($_POST['unlock_choice']) ? $_POST['unlock_choice'] : 'days') : $configuredMode;
    $recentUnlock = mka_suite_get_recent_trust_unlock($conn, $loginRaw, $trustSettings['recent_days']);
    if ($recentUnlock && (!isset($_POST['confirm_recent']) || $_POST['confirm_recent'] !== '1')) {
        http_response_code(409);
        die('Este cliente recebeu outro desbloqueio de confiança recentemente. Feche e abra o aviso novamente para confirmar.');
    }
    $performedBy = 'sistema';
    foreach (array('MKA_Usuario', 'MM_Usuario', 'usuario', 'login') as $sessionKey) {
        if (isset($_SESSION[$sessionKey]) && trim((string) $_SESSION[$sessionKey]) !== '') {
            $performedBy = trim((string) $_SESSION[$sessionKey]);
            break;
        }
    }

    if ($configuredMode === 'fixed') {
        $days = $trustSettings['fixed_days'];
        $dataDesbloqueio = date('Y-m-d', strtotime('+' . $days . ' days'));
    } elseif ($requestedMode === 'days') {
        $days = isset($_POST['dias_desbloqueio']) ? (int) $_POST['dias_desbloqueio'] : 0;
        if ($days < 1 || $days > 10) die('O período deve estar entre 1 e 10 dias.');
        $dataDesbloqueio = date('Y-m-d', strtotime('+' . $days . ' days'));
    } else {
        $dataDesbloqueio = isset($_POST['data_desbloqueio']) ? $_POST['data_desbloqueio'] : '';
        $validDate = DateTime::createFromFormat('Y-m-d', $dataDesbloqueio);
        if (!$validDate || $validDate->format('Y-m-d') !== $dataDesbloqueio) die('Data de desbloqueio inválida.');
    }

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
            mka_suite_ensure_trust_unlock_audit($conn);
            $nomeSql = mysqli_real_escape_string($conn, $nome);
            $usuarioSql = mysqli_real_escape_string($conn, $performedBy);
            mysqli_query($conn, "INSERT INTO dashboard_am_trust_unlock_audit
                (client_id, client_login, client_name, unlocked_until, performed_by, created_at)
                VALUES (" . (int) $id . ", '" . $login . "', '" . $nomeSql . "', '" . $dataDesbloqueio . "', '" . $usuarioSql . "', NOW())");
            echo "Desbloqueio confirmado com sucesso!<br>";
            echo "Cliente <strong>{$nome}</strong> estará desbloqueado até <strong>{$dataBr}</strong>.<br>";
            if ($isEmbed) {
                echo "<script>if(window.parent!==window){window.parent.postMessage({type:'mka-trust-unlock-success',login:" . json_encode($loginRaw) . "},'*');}</script>";
            }
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
