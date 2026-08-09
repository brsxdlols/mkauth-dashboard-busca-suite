<?php
$host = "127.0.0.1";
$user = "root";
$password = "vertrigo";
$dbname = "mkradius";

$link = mysqli_connect($host, $user, $password, $dbname);
if (!$link) {
    http_response_code(500);
    die(json_encode(array("error" => true, "message" => "Erro na conexao com o banco")));
}

if (!defined("PORT_API")) {
    define("PORT_API", 8728);
}

$setUserMonitor = false;
$userAPI = "";
$passAPI = "";
?>
