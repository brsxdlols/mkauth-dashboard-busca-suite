<?php include('header.php'); ?>

<body class="">

    <?php include('../../topo.php'); ?>

    <ul class="nav nav-tabs justify-content-center py-2">
        <li class="nav-item">
            <a href="#" class="nav-link" onClick="window.history.back()">
            <i class="fa-solid fa-circle-chevron-left fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php" class="nav-link" aria-current="page"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
        </li>
        <li class="nav-item">
            <a href="cli_conn_alerta.php" class="nav-link active">
            <i class="fa-solid fa-circle-exclamation fs-4 text-warning"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="chamados_abertos.php" class="nav-link">
            <i class="fa-solid fa-headset fs-4"></i>
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

    $data_atual = date('Y-m-d');
    
    $data_open = date('Y-m-d', strtotime('-5 days', strtotime($data_atual)));

    $data_inicial = isset($_GET['data_inicial']) == '' ? $data_open : $_GET['data_inicial'];
    //echo $data_inicial;
    //echo "</br>";
    $data_final = isset($_GET['data_final']) == '' ? $data_atual : $_GET['data_final'];
    $tempo_conn = isset($_GET['tempo_conn']) == '' ? 15 : $_GET['tempo_conn'];
    $qtd_quedas = isset($_GET['qtd_quedas']) == '' ? 20 : $_GET['qtd_quedas'];

    $busca = isset($_GET['busca']) == '' ? $busca : $_GET['busca'];

    /*$query_conn_online = mysqli_query($link, "SELECT * FROM radacct WHERE (username LIKE '$login_cliente' 
    OR username IN (SELECT username FROM sis_adicional WHERE login = '$login_cliente')) AND acctstoptime IS NULL ORDER BY username");
        if (!$query_conn_online){
            echo mysqli_error();
        }*/

    $query_conn = mysqli_query($link, "SELECT username, acctstarttime, acctsessiontime  FROM radacct 
    WHERE username IN (SELECT login FROM sis_cliente WHERE cli_ativado = 's' AND bloqueado = 'nao') 
    AND acctstarttime BETWEEN '$data_inicial' AND '$data_final 23:59:59' 
    AND acctsessiontime < $tempo_conn * 60
    AND username LIKE '%$busca%'
    ORDER BY username");

    if (!$query_conn){
        echo mysqli_error($link);
    }

?>


<form action="" method="get">
        <table id="form_graf">
            <tr>
                <td><b>Data Inicial:</b></td>
                <td><b>Data Final:</b></td>
                <td><b>Tempo de Conexão (MIN):</b></td>
                <td><b>Quant. Mínima de Quedas:</b></td>
                <td class="buscar"><b>Buscar Login:</b></td>
                <td></td>
            </tr>
            <tr>
                <td><input type="date" name="data_inicial" value="<?php echo $data_inicial; ?>"/></td>
                <td><input type="date" name="data_final" value="<?php echo $data_final; ?>"/></td>
                <td><input type="number" name="tempo_conn" min="1" max="240" value="<?php echo $tempo_conn; ?>"/></td>
                <td><input type="number" name="qtd_quedas" min="1" max="100" value="<?php echo $qtd_quedas; ?>"/></td>
                <td><input type="text" name="busca" class="buscar" placeholder="Pesquise pelo Login" value="<?php echo $busca; ?>"/></td>
                <td><input type="submit" name="submit" id="btn_buscar" value="OK" /></td>
            </tr>
        </table>
    </form>



<?php

    $alerta = 0;
    
    while ($row = mysqli_fetch_array($query_conn)) {
        $quedas_cli_[$row['username']] += 1;
    }

    arsort($quedas_cli_); // Ordenando do Maior p/ Menor

    //$tot_clientes = count($quedas_cli_);

?>


<table class="table table-sm table-hover table-striped small">
    <tr class="table-dark fw-bold">
        <td>Cliente</td>
        <td>Endereço</td>
        <td>Login</td>
        <td>Quant. Quedas</td>
    </tr>

<?php
    foreach ($quedas_cli_ as $key => $value) {
        if ($value >= $qtd_quedas){
            $query_cli = mysqli_query($link, "SELECT nome, endereco, bairro, complemento, cidade, estado FROM sis_cliente WHERE login = '$key'");
            while($row = mysqli_fetch_array($query_cli)){
                $nome_cli = $row['nome'];

                //Converter UTF-8 do Banco de Dados
                //$nome_cli = html_entity_decode(htmlentities($nome_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $end_cli = $row['endereco'];

                //Converter UTF-8 do Banco de Dados
                //$end_cli = html_entity_decode(htmlentities($end_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $bairro_cli = $row['bairro'];

                //Converter UTF-8 do Banco de Dados
                //$bairro_cli = html_entity_decode(htmlentities($bairro_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $complemento_cli = $row['complemento'];

                //Converter UTF-8 do Banco de Dados
                //$complemento_cli = html_entity_decode(htmlentities($complemento_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                $cidade_uf_cli = $row['cidade'].' / '.$row['estado'];    

                //Converter UTF-8 do Banco de Dados
                //$cidade_uf_cli = html_entity_decode(htmlentities($cidade_uf_cli, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');
            }
            echo "
            <tr class=''>
                <td class='link'><a href='index.php?busca=$nome_cli' title='Ver Cliente'>$nome_cli</a></td>
                <td>
                $end_cli $complemento_cli - $bairro_cli - $cidade_uf_cli
                </td>
                <td class='link'><a href='det_conn.php?login=$key' title='Ver Conexões'>$key</a></td>
                <td>$value</td>
            </tr>";

            $tot_clientes += 1;
        }
    }
    mysqli_close($link);

    echo "<b>Total Clientes com Desconexões: $tot_clientes</b>";

?>



</table>


<?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>

    </body>
</html>