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

$link = mysql_connect('127.0.0.1', 'root', 'vertrigo');
mysql_select_db("mkradius", $link);

$result = mysql_query("SELECT * FROM sis_cliente", $link);
$num_rows = mysql_num_rows($result);

echo "$num_rows clientes cadastrados | ";

$result = mysql_query("SELECT * FROM sis_adicional", $link);
$num_rows = mysql_num_rows($result);

echo "$num_rows clientes adicionais cadastrados";


?>
