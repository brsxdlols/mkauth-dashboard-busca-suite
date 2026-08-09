

<?php
$servername = "127.0.0.1";
$username = "root";
$password = "vertrigo";
$dbname = "mkradius";

// Cria conexão com o banco de dados
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Verifica se a conexão foi bem sucedida
if (!$conn) {
    die("Conexão falhou: " . mysqli_connect_error());
}
?>
