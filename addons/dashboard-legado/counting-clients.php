<?php
// Definimos o nome de usuário e senha de acesso
$usuario = "manager";
$senha = "manager%php%vh%web";
// Criamos uma função que exibirá uma mensagem de erro caso os dados estejam errados
function erro(){
    // Definindo Cabeçalhos
    header('WWW-Authenticate: Basic realm="Administracao"');
    header('HTTP/1.0 401 Unauthorized');
    // Mensagem que será exibida
    echo '<h1>Voce não tem permissão para acessar essa área</h1>';
    // Pára o carregamento da página
    exit;
}
// Se as informações não foram setadas
if (!isset($_SERVER['PHP_AUTH_USER']) or !isset($_SERVER['PHP_AUTH_PW'])) {
    erro();
} 
// Se as informações foram setadas
else {
    // Se os dados informados forem diferentes dos definidos
    if ($_SERVER['PHP_AUTH_USER'] != $usuario or $_SERVER['PHP_AUTH_PW'] != $senha) {
        erro();
    }
}

// A partir do PHP 7 a extensão mysql_ foi removida.  Para garantir
// compatibilidade com versões mais recentes do PHP utilize mysqli ou PDO.
// Conecta‑se ao banco de dados utilizando a extensão mysqli.
$mysqli = new mysqli('127.0.0.1', 'root', 'vertrigo', 'mkradius');

// Verifica se houve erro na conexão.
if ($mysqli->connect_error) {
    header('Content-Type: text/plain; charset=utf-8');
    printf("Erro na conexão com o banco de dados: %s", $mysqli->connect_error);
    exit;
}

// Consulta o número de clientes cadastrados.
$resultClientes = $mysqli->query("SELECT COUNT(*) AS total FROM sis_cliente");
$numClientes   = 0;
if ($resultClientes) {
    $row = $resultClientes->fetch_assoc();
    $numClientes = (int)$row['total'];
    $resultClientes->free();
}
echo $numClientes . " clientes cadastrados | ";

// Consulta o número de clientes adicionais cadastrados.
$resultAdicionais = $mysqli->query("SELECT COUNT(*) AS total FROM sis_adicional");
$numAdicionais   = 0;
if ($resultAdicionais) {
    $row = $resultAdicionais->fetch_assoc();
    $numAdicionais = (int)$row['total'];
    $resultAdicionais->free();
}
echo $numAdicionais . " clientes adicionais cadastrados";

// Encerra a conexão
$mysqli->close();


?>
