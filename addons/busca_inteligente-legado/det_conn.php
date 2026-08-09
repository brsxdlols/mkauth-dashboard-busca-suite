<?php include('nav/header.php'); ?>

<body class="">

    <?php include('../../topo.php'); ?>

    <ul class="nav nav-tabs justify-content-center py-2">
        <li class="nav-item">
            <a href="#" class="nav-link" onClick="window.history.back()">
            <i class="fa-solid fa-circle-chevron-left fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php" class="nav-link active" aria-current="page"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
        </li>
        <li class="nav-item">
            <a href="cli_conn_alerta.php" class="nav-link">
            <i class="fa-solid fa-circle-exclamation fs-4 text-warning"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="chamados_abertos.php" class="nav-link">
            <i class="fa-solid fa-headset fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="score.php" class="nav-link">
            <i class="fa-solid fa-ranking-star fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="relcontratos.php" class="nav-link">
            <i class="fa-solid fa-file-signature fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="cfg.php" class="nav-link">
            <i class="fa-solid fa-gear fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onClick="window.print()" >
            <i class="fa-solid fa-print fs-4"></i>
            </a>
        </li>

    </ul>
<?php


    $ler_busca_inteligente_cfg = mysqli_query($link, "SELECT * FROM busca_inteligente_cfg");
    while($cfg = mysqli_fetch_array($ler_busca_inteligente_cfg)){
        $num_conexoes = $cfg['num_conexoes'];
        $tempo_queda = $cfg['tempo_queda'];
        $tempo_queda *= 60;
        $porta_acesso = $cfg['porta_acesso'];
        $porta_acesso2 = $cfg['porta_acesso2'];
        $porta_acesso3 = $cfg['porta_acesso3'];
    }

    $login_cliente = $_GET['login'];

    $query_conn = mysqli_query($link, "SELECT * FROM radacct WHERE LOWER(TRIM(username)) LIKE '$login_cliente' 
    OR username IN (SELECT username FROM sis_adicional WHERE login LIKE '$login_cliente') ORDER BY acctstoptime DESC LIMIT $num_conexoes");

    if (!$query_conn){
        echo mysqli_error($link);
    }
    $query_conn_online = mysqli_query($link, "SELECT * FROM radacct WHERE (LOWER(TRIM(username)) LIKE '$login_cliente'
    OR username IN (SELECT username FROM sis_adicional WHERE login LIKE '$login_cliente')) AND acctstoptime IS NULL ORDER BY username");
        if (!$query_conn_online){
            echo mysqli_error($link);
        }

?>

<table id="conn_cliente">
    <tr class="linha_titulo">
        <td>Login / Nome</td>
        <td colspan="2">Conexões</td>
        <td colspan="2">Informações</td>
    </tr>

<?php

    function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    function secondsToTime($seconds) 
    { 
        $dtF = new \DateTime('@0'); 
        $dtT = new \DateTime("@$seconds"); 
        return $dtF->diff($dtT)->format('%ad - %H:%I:%S'); 
    } 

    function difDatas($data_inicio, $data_fim){
        $d1 = new DateTime($data_inicio);
        $d2 = new DateTime($data_fim);
    
        // Resgata diferen�a entre as datas
        //$dateInterval = ;
        return $d1->diff($d2)->format('%a dias %H horas e %I minutos');
        //echo "</br>";
    }

    while ($row = mysqli_fetch_array($query_conn_online)) {
        $username = $row['username'];
        $porta_conn = $row['nasportid'];
        $inicio_conn = $row['acctstarttime'];
        //$final_conn = $row['acctstoptime'];
        $tempo_conn = $row['acctsessiontime'];
        $in_conn = $row['acctinputoctets'];
        $out_conn = $row['acctoutputoctets'];
        $ip_conn = $row['framedipaddress'];

        $inicio_conn = date('d/m/Y - H:i:s', strtotime($inicio_conn));

        /*if ($final_conn != ''){
            $final_conn = date('d/m/Y - H:i:s', strtotime($final_conn));
        }*/

        $tempo_conn = secondsToTime($tempo_conn);
       
        $in_conn = formatBytes($in_conn);
        $out_conn = formatBytes($out_conn);

        $query_nome_completo = mysqli_query($link, "SELECT nome FROM sis_cliente WHERE login LIKE '$username'");
        while($row2 = mysqli_fetch_array($query_nome_completo)){
            $nome_completo = $row2['nome'];
        }

        echo "
            <tr class='linha_conexoes'>
                <td>$username</br>$nome_completo</td>
                <td class='width_auto'>
                Inicio: </br>
                Fim: </br>
                Duração: 
                </td>
                <td>
                $inicio_conn</br>
                <span class='conn_boa'>ONLINE</span></br>
                $tempo_conn
                </td>
                <td class='width_auto'>
                IP: </br>
                Up/Down: </br>
                Porta: </br>
                </td>
                <td class='link'>
                <img src='img/icon_web.png' class='icon'/>
                $ip_conn | 
                <a href='http://$ip_conn:$porta_acesso' target='_blank' title='Acessar $ip_conn:$porta_acesso'>$porta_acesso</a>
                <a href='http://$ip_conn:$porta_acesso2' target='_blank' title='Acessar $ip_conn:$porta_acesso2'>$porta_acesso2</a>
                <a href='http://$ip_conn:$porta_acesso3' target='_blank' title='Acessar $ip_conn:$porta_acesso3'>$porta_acesso3</a>
                </br>
                $in_conn / $out_conn</br>
                $porta_conn
                </td>
            </tr>
        ";
    }

    $alerta = 0;
    while ($row = mysqli_fetch_array($query_conn)) {
        $username = $row['username'];
        $porta_conn = $row['nasportid'];
        $inicio_conn = $row['acctstarttime'];
        $final_conn = $row['acctstoptime'];
        $tempo_conn = $row['acctsessiontime'];
        $in_conn = $row['acctinputoctets'];
        $out_conn = $row['acctoutputoctets'];
        $ip_conn = $row['framedipaddress'];

        $data_queda_conn = date('Y/m/d H:i:s', strtotime($final_conn));
        $data_atual = date('Y/m/d H:i:s');

        $inicio_conn = date('d/m/Y - H:i:s', strtotime($inicio_conn));
        if ($final_conn != ''){
            $final_conn = date('d/m/Y - H:i:s', strtotime($final_conn));
        }

        $tempo_offline[] = difDatas($data_queda_conn,$data_atual);

        $tempo_conn_alerta = $tempo_conn;
        
        if($tempo_conn_alerta <= $tempo_queda){
            
            $alerta += 1;
        } 
        
        $tempo_conn = secondsToTime($tempo_conn);

        $in_conn = formatBytes($in_conn);
        $out_conn = formatBytes($out_conn);

        echo "
            <tr class='linha_conexoes'>
                <td>$username</td>
                <td class='width_auto'>
                Inicio: </br>
                Fim: </br>
                Duração: 
                </td>
                <td>";
                
                if ($tempo_conn_alerta <= 3600){
                    echo "<span class='conn_alerta'>
                    $inicio_conn</br>
                    $final_conn</br>
                    $tempo_conn
                    </span>";
                }elseif ($tempo_conn_alerta >= 604800){
                    echo "<span class='conn_boa'>
                    $inicio_conn</br>
                    $final_conn</br>
                    $tempo_conn
                    </span>";
                }else{
                    echo "
                    $inicio_conn</br>
                    $final_conn</br>
                    $tempo_conn
                    ";
                }
                
        echo "
                </td>
                <td class='width_auto'>
                IP: </br>
                Up/Down: </br>
                Porta: </br>
                </td>
                <td class='link'>
                <img src='img/icon_web.png' class='icon'/>
                $ip_conn | 
                <a href='http://$ip_conn:$porta_acesso' target='_blank' title='Acessar $ip_conn:$porta_acesso'>$porta_acesso</a>
                <a href='http://$ip_conn:$porta_acesso2' target='_blank' title='Acessar $ip_conn:$porta_acesso2'>$porta_acesso2</a>
                <a href='http://$ip_conn:$porta_acesso3' target='_blank' title='Acessar $ip_conn:$porta_acesso3'>$porta_acesso3</a>
                </br>
                $in_conn / $out_conn</br>
                $porta_conn
                </td>
            </tr>
        ";
    }

    if (mysqli_num_rows($query_conn) == 0){
        echo "<span class='conn_alerta'>Esse cliente ainda não tem conexões!</span>";
    }else if(mysqli_num_rows($query_conn_online) == 1){
        echo "Esse cliente está <span class='conn_boa'>ONLINE!</span>";
    }else if (mysqli_num_rows($query_conn_online) > 1){
        echo "Esse cliente está <span class='conn_boa'>ONLINE!</span>";
    }
    else{
        echo "</br>O cliente está <span class='conn_alerta'>OFFLINE</span> há $tempo_offline[0]";
    }
    if ($alerta == 0){
        // Nenhuma interação
    }
    else{
        echo "</br>Houve <span class='conn_alerta'>$alerta quedas</span> na últimas $num_conexoes conexões desse cliente";
    }
    
    

    mysqli_close($link);

?>

</table>


<?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>

    </body>
</html>