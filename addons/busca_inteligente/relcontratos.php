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
            <a href="index.php" class="nav-link" aria-current="page"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
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
            <a href="relcontratos.php" class="nav-link active">
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

$data_atual = date('Y-m-d');
    
$data_inicial = isset($_GET['data_inicial']) == '' ? $data_atual : $_GET['data_inicial'];

$busca = isset($_GET['busca']) == '' ? $busca : $_GET['busca'];


    /*$query = "SELECT username, acctstarttime, acctstoptime, acctsessiontime, acctinputoctets, acctoutputoctets, framedipaddress, callingstationid, nasipaddress
    FROM radacct c
    WHERE acctstarttime BETWEEN '$data_inicial' AND '$data_final 23:59:59' AND (username LIKE '%$busca%' OR framedipaddress LIKE '%$busca%' OR callingstationid LIKE '%$busca%' OR nasipaddress LIKE '%$busca%') AND nasipaddress LIKE '$local' ORDER BY acctstarttime DESC";*/

    $lista_per = array(
        "dia"=> "DIARIO",
        "mes"=> "MENSAL",
        );

    $per = isset($_GET['per']) == "" ? "mes" : $_GET['per'];

    $mes = isset($_GET['mes']) == "" ? date('m') : $_GET['mes'];
    $ano = isset($_GET['ano']) == "" ? date('Y') : $_GET['ano'];

    $lista_mes = array(
        "01"=> "JAN",
        "02"=> "FEV",
        "03"=> "MAR",
        "04"=> "ABR",
        "05"=> "MAI",
        "06"=> "JUN",
        "07"=> "JUL",
        "08"=> "AGO",
        "09"=> "SET",
        "10"=> "OUT",
        "11"=> "NOV",
        "12"=> "DEZ",
        );
        
    $data_inicial_format = date('d/m/Y', strtotime($data_inicial));
    //$data_final_format = date('d/m/Y', strtotime($data_final));
    
    //echo $data_inicial_format."<br>";
    //echo $data_final_format."<br>";

    if($per == "mes"){
        $query = "SELECT c.uuid_cliente, c.nome, c.login, c.cadastro, c.fone, c.ssid, c.celular, c.celular2, c.plano, p.valor
        FROM sis_cliente c 
        LEFT JOIN sis_plano p
        on c.plano = p.nome
        WHERE c.cli_ativado = 's' 
        AND (c.nome LIKE '%$busca%' OR 
        c.login = '$busca' OR 
        c.cadastro LIKE '%$busca%' OR 
        c.fone LIKE '%$busca%' OR 
        c.ssid LIKE '%$busca%' OR
        c.celular LIKE '%$busca%' OR
        c.celular2 LIKE '%$busca%' OR
        c.plano LIKE '%$busca%' OR
        c.tags LIKE '%$busca%'
        )";
    }else{
        $query = "SELECT data, registro FROM sis_logs WHERE 
        registro LIKE 'bloqueado%' AND
        data LIKE '$data_inicial_format%'";
    }

    $result = mysqli_query($link, "$query");

?>


    <form action="" method="get">
        <table class="form_graf" class="no_print">
            <tr>               
                <td class="buscar"><b>Busca Integrada :</b></td>

                <td></td>
            </tr>
            <tr>
                <td><input type="text" name="busca" class="buscar" placeholder="Pesquisar Nome, Login, Plano, SSID, Telefone, Tags ou Data Cadastro" value="<?php echo $busca; ?>"/></td>
                <td><input type="submit" name="submit" id="btn_buscar" value="OK" /></td>
            </tr>
        </table>
    </form>

    <table class="table table-sm table-hover table-striped small">
        <tr class="table-dark fw-bold">
            <td>DATA CADASTRO</td>
            <td>RENOVAÇÃO</td>
            <td>NOME</td>
            <td>TECNOLOGIA</td>
            <td>FONE</td>
            <td>PLANO</td>
            <td>VALOR PLANO</td>
            <td>SITUAÇÃO ATUAL</td>
        </tr>

    <?php

    $starttime = microtime(true);


    while ($row = mysqli_fetch_array($result)) {
        //debug($row);
        $cli_login = $row['login'];

        $cli_data_cad[$cli_login] = $row['cadastro'];

        $dia_cad = substr($cli_data_cad[$cli_login], 0, 2);
        $mes_cad = substr($cli_data_cad[$cli_login], 3, 2);
        $ano_cad = substr($cli_data_cad[$cli_login], 6, 4);

        $cli_data_cad_final[$cli_login] = "$ano_cad-$mes_cad-$dia_cad";

        //$cli_data_cad_convert = date('Y-d-m', $row['cadastro']);
        $cli_nome[$cli_login] = $row['nome'];

        $cli_uuid[$cli_login] = $row['uuid_cliente'];

        $cli_fone[$cli_login] = $row['fone'] ." ". $row['celular'] ." ". $row['celular2'];

        $cli_plano[$cli_login] = $row['plano'];
        $cli_plano_valor[$cli_login] = "R$ ".number_format($row['valor'], 2, ',', '.');

        $cli_ssid[$cli_login] = $row['ssid'];

        //$data_graf = substr($row['data'], 0, 10);
        //$data = date('d/m/Y', strtotime($row['data']));

        /*$reg = $row['registro'];

        $num_titulo_cont = stripos($reg, " vencido");
        $num_titulo = substr($reg, 31, $num_titulo_cont - 31);
        */


        /*if($observacao[$num_titulo] == "sim"){
            $situacao = "Cliente em Observação até $remove_obs[$num_titulo]";
            $obs += 1;
            echo "<td class='orange'>$nome_cli[$num_titulo] <b>[$login_cli[$num_titulo]]</b></td>";
        }else if($bloqueado[$num_titulo] == "nao"){
            $situacao = "Cliente Liberado";
            $nao += 1;
            echo "<td class='green'>$nome_cli[$num_titulo] <b>[$login_cli[$num_titulo]]</b></td>";
        }else{
            $situacao = "Cliente Bloqueado";
            $sim += 1;
            echo "<td class='red'>$nome_cli[$num_titulo] <b>[$login_cli[$num_titulo]]</b></td>";
        }*/
        
        // Totais para gerar gráfico
        //$tot_bloq_dia[$data_graf] += 1;
    }

    asort($cli_data_cad_final);

    
    
    

    // Inicializacao de variaveis
    $status = ""; $situacao = 0; $c_a_vencer = 0; $c_vencido = 0; $c_em_dia = 0; $cc = 0;

    foreach($cli_data_cad_final as $login => $data_cadastro){

        $data_ren = date('Y-m-d', strtotime('+1 year', strtotime($data_cadastro)));


        $situacao = diffDate2(date('Y-m-d'), $data_ren);

        $data_renovacao = date('d/m/Y', strtotime('+1 year', strtotime($data_cadastro)));
        

        echo "
        <tr class=''>
        <td>$cli_data_cad[$login]</td>
        <td>$data_renovacao</td>
        ";

        if ($situacao < 0){
            $situacao *= (-1);
            $status = "Contrato vencido há $situacao dias";
            $c_vencido += 1;
            echo "<td class='red'><a href='../../cliente_alt$ext_mk?uuid=$cli_uuid[$login]' target='_blank' title='Acessar Cadastro: $cli_nome[$login]'>$cli_nome[$login] <b>[$login]</b></a></td>";
        }
        else if($situacao <= 60){
            $status  = "Contrato irá vencer em $situacao dias";
            $c_a_vencer += 1;
            echo "<td class='orange'><a href='../../cliente_alt$ext_mk?uuid=$cli_uuid[$login]' target='_blank' title='Acessar Cadastro $cli_nome[$login]'>$cli_nome[$login] <b>[$login]</b></a></td>";
        }else{
            $status  = "Contrato OK";
            $c_em_dia += 1;
            echo "<td class='green'><a href='../../cliente_alt$ext_mk?uuid=$cli_uuid[$login]' target='_blank' title='Acessar Cadastro $cli_nome[$login]'>$cli_nome[$login] <b>[$login]</b></a></td>";
        }

        
        echo "
        <td>$cli_ssid[$login]</td>
        <td>$cli_fone[$login]</td>
        <td>$cli_plano[$login]</td>
        <td>$cli_plano_valor[$login]</td>
        <td>$status</td>
        </tr>";

        $cc++;
    }

    $endtime = microtime(true);

    $tempo_sql = (($endtime - $starttime) * 1000);
    $tempo_sql = number_format($tempo_sql, 2);

    echo "<span class='no_print'><b>Tempo de Consulta no Banco de Dados:</b> $tempo_sql milissegundos<br>
    <b>Total de Registros:</b> $cc<span>";        


    //echo $c_vencido;

    /*$tot_resultados = mysql_num_rows($result);

    // Paginaçço
    $limite = mysql_num_rows($result_limit);
    $tot_paginas = $tot_resultados / $registros_por_pagina;
        
    // agora vamos criar os botçes "Anterior e prçximo"
    $anterior = $pc -1;
    $proximo = $pc +1;

    
    $url = "?data_inicial=$data_inicial&data_final=$data_final&busca=$busca&num_registros=$registros_por_pagina";

    echo "<span class='no_print'>";
        if ($pc>1) {
        echo " <a href='$url&pagina=$anterior'><- Anterior</a> ";
        }
        echo "|";
        if ($pc<$tot_paginas) {
        echo " <a href='$url&pagina=$proximo'>Próxima -></a>";
        }
    echo "</span>";*/

    mysqli_close($link);


?>
<!--
<script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <figure class="highcharts-figure">
        <div id="container"></div>
        <p class="highcharts-description">
        <script>

    Highcharts.chart('container', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: '<?php 
            if($per == 'dia'){
                echo "GRÁFICO DIARIO - $data_inicial_format";
            }else{
                echo "GRÁFICO MENSAL - $mes / $ano";
            }
                    
                ?>'
        },
        xAxis: {
            categories: [
                <?php
                    /*foreach ($tot_bloq_dia as $key => $value) {
                        echo "'$key',";
                    }*/
                    
                ?>
                'Total Bloqueados'
            ]
        },
        credits: {
            enabled: false
        },
        series: [
                //name: 'Total Bloqueados por Dia',
                //data: 
                //[
                <?php
                    foreach ($tot_bloq_dia as $key => $value) 
                    {

                        echo "{
                            name: '$key',
                            data: [$value]
                        },";
                    }
                ?>
                //]
    ]
    });

    </script>
    </p>
    </figure>
-->

<script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <figure class="highcharts-figure">
    <div id="container2"></div>
    <p class="highcharts-description">
    <script>
        Highcharts.chart('container2', {
        chart: {
            type: 'pie'
        },
        title: {
            text: 'Controle de Contratos'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        accessibility: {
            point: {
                valueSuffix: '%'
            }
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                }
            }
        },
        credits: {
            enabled: false
        },
        series: [{
            name: 'Brands',
            colorByPoint: true,
            data: [
                <?php 
                    echo "
                    { 
                        name: 'Contrato OK',
                        y: $c_em_dia,
                        color: Highcharts.getOptions().colors[2]
                    },
                    { 
                        name: 'Contrato a vencer',
                        y: $c_a_vencer,
                        color: Highcharts.getOptions().colors[3]
                    },
                    { 
                        name: 'Contrato Vencido',
                        y: $c_vencido,
                        color: Highcharts.getOptions().colors[8]
                    }
                    ";
                ?>
            ]
        }]
    });
    
    </script>
    
        </p>
    </figure>


    </table>

<?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>
<?php include('../../rodape.php'); ?>
    </body>
</html>