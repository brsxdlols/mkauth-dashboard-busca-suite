
<?php
require('routeros_api.class.php');

$login = $_GET['login'];
$router = $_GET['router'];

if ($router != '') {

    if ($setUserMonitor) {
        $login_router = $userAPI;
        $pass_router  = $passAPI;
    } else {
        $query_nas = mysqli_query($link, "SELECT * FROM nas WHERE nasname LIKE '$router'");

        while ($nas = mysqli_fetch_array($query_nas)) {
            $login_router = $nas['userapi'];
            $pass_router  = $nas['senha'];
        }

        // Memory Leak
        mysqli_free_result($query_nas);

        $login_router = $login_router == '' ? 'mkauth' : $login_router;
    }


    $API = new RouterosAPI();

    $API->debug = false;

    $API->connect($router, $login_router, $pass_router);
    if ($API->connected) {
        $busca_cliente = $API->comm('/ppp/active/print', array(
            "?name" => "$login"
        ));

        $ip_cliente = $busca_cliente[0]['address'];

        $uptime_cliente = $busca_cliente[0]['uptime'];

        //echo $ip_cliente;

        $ping = $API->comm("/ping", array(
            "address" => "$ip_cliente",
            "count" => "1"
        ));

        $latencia[] = $ping[0]['time'];

        $trafego = $API->comm("/interface/monitor-traffic", array(
            "interface" => "<pppoe-$login>",
            "once" => ""
        ));

        if ($trafego['!trap']) {
            $trafego = $API->comm("/interface/monitor-traffic", array(
                "interface" => "$login",
                "once" => ""
            ));
        }

        $rx[] = number_format($trafego[0]["rx-bits-per-second"] / 1024 / 1024, 2);

        $tx[] = number_format($trafego[0]["tx-bits-per-second"] / 1024 / 1024, 2);

        $trafego = json_encode(array("RX" => $rx, "TX" => $tx, "PING" => $latencia, "UPTIME" => $uptime_cliente), JSON_NUMERIC_CHECK);

        print_r($trafego);
    } else {
        $response = array(
            'error' => true, // Adiciona um campo de erro
            'message' => 'ERRO'
        );

        // $msg = json_encode(array("RX" => 0, "TX" => 0, "PING" => 0, "UPTIME" => "ERROR"), JSON_NUMERIC_CHECK);
        echo json_encode($response);
        //return;
    }
} else {
    $query_last_conn = mysqli_query($link, "SELECT * FROM radacct WHERE username LIKE '$login' ORDER BY radacctid DESC LIMIT 1");

    while ($row = mysqli_fetch_array($query_last_conn)) {
        $ramal_ip = $row['nasipaddress'];
        //echo "$ramal_ip<br>";
    }

    // Memory Leak
    mysqli_free_result($query_last_conn);

    if ($setUserMonitor) {
        $login_router = $userAPI;
        $pass_router  = $passAPI;
    } else {
        $query_nas = mysqli_query($link, "SELECT * FROM nas WHERE nasname LIKE '$ramal_ip'");

        while ($nas = mysqli_fetch_array($query_nas)) {
            $login_router = $nas['userapi'];
            $pass_router  = $nas['senha'];
        }

        // Memory Leak
        mysqli_free_result($query_nas);

        // $login_router = $login_router == '' ? 'mkauth' : $login_router;
    }

    // $query_nas = mysqli_query($link, "SELECT * FROM nas WHERE nasname LIKE '$ramal_ip'");

    // while ($nas = mysqli_fetch_array($query_nas)) {
    //     $login_router = $nas['userapi'];

    //     $pass_router  = $nas['senha'];
    // }

    if ($ramal_ip != '') {

        $login_router = $login_router == '' ? 'mkauth' : $login_router;

        $API = new RouterosAPI();

        $API->debug = false;

        $API->connect($ramal_ip, $login_router, $pass_router);
        if ($API->connected) {
            $busca_cliente = $API->comm('/ppp/active/print', array(
                "?name" => "$login"
            ));

            $ip_cliente = $busca_cliente[0]['address'];

            $uptime_cliente = $busca_cliente[0]['uptime'];

            $ping = $API->comm("/ping", array(
                "address" => "$ip_cliente",
                "count" => "1"
            ));

            $latencia[] = $ping[0]['time'];

            $trafego = $API->comm("/interface/monitor-traffic", array(
                "interface" => "<pppoe-$login>",
                "once" => ""
            ));


            if ($trafego['!trap']) {
                $trafego = $API->comm("/interface/monitor-traffic", array(
                    "interface" => "$login",
                    "once" => ""
                ));
            }

            $rx[] = number_format($trafego[0]["rx-bits-per-second"] / 1024 / 1024, 2);

            $tx[] = number_format($trafego[0]["tx-bits-per-second"] / 1024 / 1024, 2);

            $API->disconnect();
        }

        $trafego = json_encode(array("RX" => $rx, "TX" => $tx, "PING" => $latencia, "UPTIME" => $uptime_cliente), JSON_NUMERIC_CHECK);

        print_r($trafego);
    }
}

$API->disconnect();

mysqli_close($link);
?>




