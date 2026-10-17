<?php include('addons.class.php'); ?>

<?php

/*
    BUSCA INTELIGENTE
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
*/

//Conexão com o Banco de Dados
$link = mysqli_connect("127.0.0.1", "root", "vertrigo", "mkradius");

if (!$link) {
    echo "Error: Falha ao conectar-se com o banco de dados MySQL." . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}

/*
$database = mysql_select_db('mkradius', $link);
//mysql_set_charset('utf8');
if (!$database) {
    echo 'Erro ao selecionar DB';
} else {
    //echo 'Database conectada';
}
*/

$usuario_logado = $_SESSION['MKA_Usuario'] == '' ? $_SESSION['MM_Usuario'] : $_SESSION['MKA_Usuario'];

// Fix MK-AUTH versoes antigas
if (isset($_SESSION['MM_Usuario'])) {
    echo '<script src="../../scripts/vue.js"></script>';
}

// Fix for MKAUTH 22.02
if (!file_exists("../../login.hhvm")) {
    $ext_mk = '.php';
} else {
    $ext_mk = '.hhvm';
}

function difDatas($data_inicio, $data_fim){
    $d1 = new DateTime($data_inicio);
    $d2 = new DateTime($data_fim);

    // Resgata diferen�a entre as datas
    //$dateInterval = ;
    $retorno = $d1->diff($d2)->format('%a dia(s) %H:%I');
    return $retorno;
}

function debug($pre)
{
    echo "<pre>";
    print_r($pre);
    echo "</pre>";
}
?>