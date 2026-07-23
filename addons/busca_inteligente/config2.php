<?php
$host = "127.0.0.1";
$user = "root";
$password = "vertrigo";
$dbname = "mkradius";

$link = mysqli_connect($host, $user, $password, $dbname);

if (!$link) {
    die("Erro na conexão: " . mysqli_connect_error());
}
?>
