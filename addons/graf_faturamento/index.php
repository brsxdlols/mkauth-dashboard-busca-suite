<!-- 
    RELAT�RIO DE FATURAMENTO
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php require_once('config.php'); ?>

<!DOCTYPE html>
<?php
    if(isset($_SESSION['MM_Usuario'])){
        echo '<html lang="pt-BR">'; // Fix versão antiga MK-AUTH
    }else{
        echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
    }
?>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="utf-8">
        <title>MK - AUTH :: <?php echo $Manifest->{'name'}." - V ". $Manifest->{'version'};  ?></title>

        <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
        <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />

        <script src="../../scripts/jquery.js"></script>
        <script src="../../scripts/mk-auth.js"></script>

        <style type="text/css">
            #tabela_fat {float:left;}

            #table_busca { margin:0 auto; }
            
            #btn_buscar, #form_graf input, #form_graf select { padding: 10px; font-size:18px; }
            #btn_buscar { background: Navy; color:#FFF; border: 1.5px solid Navy;}
            
            .block { display:inline-block; width: 100%;}

            #container {
                display:block;
                height: 600px; 
                min-width: 500px;
            }

            #container10{
                float:left;
                height: 400px; 
                /*min-width: 500px;*/
                width: 20%;
            }

            #container7{
                float:left;
                min-width: 500px;
                height: 400px;
                width: 80%;
            }

            #container9{
                min-width: 500px;
                height: 400px;
                display:block;
            }

            #container2, #container3, #container4, #container5{
                width: 50%;
                height: 550px; 
                float:left;
            }

            #container6{
                width: 100%;
                height: 400px;
                display:block;
            }

            #container8{
                width: 75%;
                height: 400px;
                float:left;
            }

            #container12{
                width: 25%;
                height: 400px;
                float:left;
            }

            .highcharts-figure{
                width:100%;
                margin: 1em auto;
            }
        </style>
</head>
<body>



<?php include('../../topo.php'); ?>


    <nav class="breadcrumb has-bullet-separator is-centered" aria-label="breadcrumbs">
        <ul>
            <li class="is-active">
            <a href="#" aria-current="page"> <?php echo $Manifest->{'name'}." - V ".$Manifest->{'version'}; echo " - ANUAL"; ?></a>
            </li>
            <li><a href="graf_det.php">DETALHADO</a></li>
            <li><a href="graf_dia.php">DIÁRIO</a></li>
            <li><a href="graf_ramal_cli.php">RAMAL 1</a></li>
            <li><a href="graf_ramal.php">RAMAL 2</a></li>
            <li><a href="graf_5a.php">GRAF 5 ANOS</a></li>
            <li><a href="graf_nome.php">NOME</a></li>
            <li><a href="graf_cidade_cli.php">CIDADE</a></li>
            <li><a href="graf_bairro_cli.php">BAIRRO</a></li>
            <li><a href="graf_periodo.php">ULT MESES</a></li>
            <li>
                <img src="img/icon_print.png" class="icon" title="Imprimir" onClick="window.print()"></img>
            </li>
        </ul>
    </nav>

    <?php include('config.php'); ?>

    <?php 
        if ($acesso_permitido)
        {
    ?>

    <?php
        
        /*$tot_entrada_ = 0;
        $tot_saida_ = 0;

        $estorno_entrada_  = 0;
        $estorno_saida_ = 0;

        $desc_emprestimo_ticket_ = 0;


        $tot_entrada = 0;
        $tot_saida = 0;

        $estorno_entrada = 0;
        $estorno_saida = 0;*/

        $ano_atual = date('Y');
        $mes_atual = date('m');

        $periodo_ini = "";

        switch($mes_atual){
            case "01":
            case "02":
            case "03":
            case "04":
            case "05":
            case "06":
                $periodo_ini = "1s";
                break;
            default:
                $periodo_ini = "2s"; 
                break;           
        }

        $ano = isset($_GET['ano']) == "" ? $ano_atual : $_GET['ano'];
        
        $periodo = isset($_GET['periodo']) == "" ? $periodo_ini : $_GET['periodo'];

        $string_per = "";

        if ($periodo == "1s"){
            $per_ini = 1;
            $per_meses = 6;
            $string_per = "1° Semestre - ";
            $string_graf_anual = "data LIKE '$ano-01%' OR data LIKE '$ano-02%' OR data LIKE '$ano-03%' OR data LIKE '$ano-04%' OR data LIKE '$ano-05%' OR data LIKE '$ano-06%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "2s"){
            $per_ini = 7;
            $per_meses = 12;
            $string_per = "2° Semestre - ";
            $string_graf_anual = "data LIKE '$ano-07%' OR data LIKE '$ano-08%' OR data LIKE '$ano-09%' OR data LIKE '$ano-10%' OR data LIKE '$ano-11%' OR data LIKE '$ano-12%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "1q"){
            $per_ini = 1;
            $per_meses = 4;
            $string_per = "1° Quadrimestre - ";
            $string_graf_anual = "data LIKE '$ano-01%' OR data LIKE '$ano-02%' OR data LIKE '$ano-03%' OR data LIKE '$ano-04%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "2q"){
            $per_ini = 5;
            $per_meses = 8;
            $string_per = "2° Quadrimestre - ";
            $string_graf_anual = "data LIKE '$ano-05%' OR data LIKE '$ano-06%' OR data LIKE '$ano-07%' OR data LIKE '$ano-08%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "3q"){
            $per_ini = 9;
            $per_meses = 12;
            $string_per = "3° Quadrimestre - ";
            $string_graf_anual = "data LIKE '$ano-09%' OR data LIKE '$ano-10%' OR data LIKE '$ano-11%' OR data LIKE '$ano-12%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "1t"){
            $per_ini = 1;
            $per_meses = 3;
            $string_per = "1° Trimestre - ";
            $string_graf_anual = "data LIKE '$ano-01%' OR data LIKE '$ano-02%' OR data LIKE '$ano-03%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "2t"){
            $per_ini = 4;
            $per_meses = 6;
            $string_per = "2° Trimestre - ";
            $string_graf_anual = "data LIKE '$ano-04%' OR data LIKE '$ano-05%' OR data LIKE '$ano-06%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "3t"){
            $per_ini = 7;
            $per_meses = 9;
            $string_per = "3° Trimestre - ";
            $string_graf_anual = "data LIKE '$ano-07%' OR data LIKE '$ano-08%' OR data LIKE '$ano-09%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "4t"){
            $per_ini = 10;
            $per_meses = 12;
            $string_per = "4° Trimestre - ";
            $string_graf_anual = "data LIKE '$ano-10%' OR data LIKE '$ano-11%' OR data LIKE '$ano-12%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else{
            $per_ini = 1;
            $per_meses = 12;
            $string_graf_anual = "data LIKE '$ano%'";
            //$string_per = "";
            //echo "$per_ini</br>$per_meses</br>";
        }

        $lista_per = array(
            "1a" => "Anual",
            "1s" => "1° Semestre",
            "2s" => "2° Semestre",
            "1q" => "1° Quadrimestre",
            "2q" => "2° Quadrimestre",
            "3q" => "3° Quadrimestre",
            "1t" => "1° Trimestre",
            "2t" => "2° Trimestre",
            "3t" => "3° Trimestre",
            "4t" => "4° Trimestre",
        );

        //Função parar verificação de String iniciada por um termo
        // Case sensitive
        function startsWith($string, $startString) { 
            $len = strlen($startString); 
            return (strtolower(substr($string, 0, $len)) === $startString); 
        } 

    ?>


    <form action="" method="get" id="form_graf">
    <table id="table_busca">

        <tr>
            <td><b>Qual ano deseja gerar o gráfico?</b></td>
            <td><b>Período do gráfico:</b></td>
            <td></td>
        </tr>
        <tr>
            <td><input type="number" name="ano" min="1990" value="<?php echo $ano; ?>"/></td>
            <td>
                <select name="periodo">
                <?php
                    foreach ($lista_per as $key => $value) {
                        $selected = ($periodo == $key) ? "selected=\"selected\"" : null;
                        echo "<option value=\"$key\" $selected >$value</option>";
                }
                ?>
                </select>
            </td>
            <td><input type="submit" name="submit" value="OK" id="btn_buscar"/></td>
        </tr>
        
        </table>

    </form>

    <?php    

 
    //$starttime = microtime(true);
    for($i=$per_ini; $i <= $per_meses; $i++){
        if ($i<10){
            $i="0$i";
        }

        //Tot Entrada e Saida
        $query_mes[$i] = mysqli_query($link, "SELECT sum(entrada) tot_entrada, sum(saida) tot_saida FROM sis_caixa 
        WHERE 
        data LIKE '$ano-$i%' 
        ORDER BY data");

        $tot_entrada_[$i] = 0;
        $tot_saida_[$i] = 0;

        $cont_ticket_[$i] = 0;

        while ($row = mysqli_fetch_array($query_mes[$i])){            
            $tot_entrada_[$i] = $row['tot_entrada'];
            $tot_saida_[$i] = $row['tot_saida'];

            $query_ticket_[$i] = mysqli_query($link, "SELECT count(login) ticket FROM sis_lanc WHERE datapag LIKE '$ano-$i%'");
            while($cont = mysqli_fetch_array($query_ticket_[$i])){
                $cont_ticket_[$i] = $cont['ticket'];
            }
        }

        //Tot Estorno
        $query_mes[$i] = mysqli_query($link, "SELECT sum(entrada) tot_estorno_entrada, sum(saida) tot_estorno_saida FROM sis_caixa 
        WHERE historico LIKE 'estorno%' AND
        data LIKE '$ano-$i%' 
        ORDER BY data");

        $estorno_entrada_[$i] = 0;
        $estorno_saida_[$i] = 0;

        while ($row = mysqli_fetch_array($query_mes[$i])){
            $estorno_entrada_[$i] += $row['tot_estorno_entrada'];
            $estorno_saida_[$i] += $row['tot_estorno_saida'];
        }

        //Desconto Emprestimo Ticket
        $query_mes[$i] = mysqli_query($link, "SELECT sum(entrada) tot_emprestimo FROM sis_caixa 
        WHERE (historico LIKE 'emprestimo%' OR historico LIKE 'credito emprestimo%') AND
        data LIKE '$ano-$i%' 
        ORDER BY data");

        $desc_emprestimo_[$i] = 0;

        while ($row = mysqli_fetch_array($query_mes[$i])){           
            $desc_emprestimo_[$i] = $row['tot_emprestimo'];
        }

        $query_saldo_conta[$i] = mysqli_query($link, "SELECT (sum(entrada) - sum(saida)) saldo_conta  FROM sis_caixa WHERE data <= '$ano-$i-31 23:59:59'");

        if(!$query_saldo_conta[$i]){
            echo mysqli_error($link);
        }

        while($row = mysqli_fetch_array($query_saldo_conta[$i])){
            $saldo_conta_[$i] = $row['saldo_conta'];
        }
        
        $tot_entrada_[$i] = $tot_entrada_[$i] - $estorno_entrada_[$i] - $estorno_saida_[$i];
        $tot_entrada_[$i] = round($tot_entrada_[$i]);

        $tot_saida_[$i] = $tot_saida_[$i] - $estorno_entrada_[$i] - $estorno_saida_[$i];
        $tot_saida_[$i] = round($tot_saida_[$i]);
        
        $saldo_[$i] = $tot_entrada_[$i] - $tot_saida_[$i];
        $saldo_[$i] = round($saldo_[$i]);

        if ($cont_ticket_[$i] != 0){
            $ticket_medio_[$i] = number_format(($tot_entrada_[$i] - $desc_emprestimo_[$i]) / $cont_ticket_[$i], 2);
        }else{
            $ticket_medio_[$i] = 0;
        }

        //Entrada sem emprestimo
        $tot_entrada_sem_emprestimo[$i] = $tot_entrada_[$i] - $desc_emprestimo_[$i];
        //Entrada com emprestimo
        $tot_entrada_com_emprestimo[$i] = $tot_entrada_[$i];

        //Total Emprestimos
        $total_emprestimo_[$i] = $tot_entrada_com_emprestimo[$i] - $tot_entrada_sem_emprestimo[$i];
        
        //Condição para exibição.
        if($desc_emprestimo_[$i] == 0){
            $tot_entrada_com_emprestimo[$i] = 0;
            $total_emprestimo_[$i] = 0;
        }

        // Total graf Anual
        $tot_entrada += $tot_entrada_[$i];   
        $tot_saida += $tot_saida_[$i];
        $saldo = $tot_entrada - $tot_saida;

        $tot_geral_entrada_sem_emprestimo += $tot_entrada_sem_emprestimo[$i];
        $tot_geral_emprestimos += $total_emprestimo_[$i];
    }

    $mes_nome = array(
        "01" => "JAN",
        "02" => "FEV",
        "03" => "MAR",
        "04" => "ABR",
        "05" => "MAI",
        "06" => "JUN",
        "07" => "JUL",
        "08" => "AGO",
        "09" => "SET",
        "10" => "OUT",
        "11" => "NOV",
        "12" => "DEZ",

    );
    ?>




<?php

        //$starttime = microtime(true);

        /*echo $starttime;
        echo "</br>";*/

        /*
        $endtime = microtime(true);

        $tempo_sql = (($endtime - $starttime) * 1000);
        $tempo_sql = number_format($tempo_sql, 2);

        echo "<b>Tempo de Consulta no Banco de Dados:</b> $tempo_sql milissegundos";        
        echo "</br>";*/
        mysqli_close($link);

?>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <figure class="highcharts-figure">
        <div id="container"></div>
        <p class="highcharts-description">
        <script>

        // gr�fico 1
        Highcharts.chart('container', {
        plotOptions: {
            column: { 
                dataLabels: { 
                    enabled: true 
                } 
            },
            line: {
                valueSuffix: '%',
                dataLabels: { 
                    enabled: true 
                }, enableMouseTracking: false
                
            }, 
        },
        title: {
            text: '<?php echo "Balanço $string_per $ano";?>'
        },
        xAxis: {
            categories: [
                <?php
                    foreach($mes_nome as $key => $value){
                        if($key >= $per_ini && $key <= $per_meses){
                            echo "'$value',";
                        }
                    }
                ?>
            ]
        },
        credits: {
            enabled: false
        },
        series: [{
            type: 'column',
            name: 'Entradas',
            data: [
            <?php 
                for($i=$per_ini; $i <= $per_meses; $i++){
                    if ($i<10){
                        $i="0$i";
                    }
                    echo "$tot_entrada_[$i],"; 
                }
            ?>
            ]
        }, {
            type: 'column',
            name: 'Saidas',
            data: [
            <?php 
                    for($i=$per_ini; $i <= $per_meses; $i++){
                        if ($i<10){
                            $i="0$i";
                        }
                        echo "$tot_saida_[$i],"; 
                    }
            ?>
            ]
        }, {
            type: 'line',
            name: 'Saldo',
            data: [
            <?php 
                    for($i=$per_ini; $i <= $per_meses; $i++){
                        if ($i<10){
                            $i="0$i";
                        }
                        echo "$saldo_[$i],"; 
                    }
            ?>
            ]
        }]
    });

    </script>
    </p>
</figure>



<figure class="highcharts-figure">
        <div id="container7"></div>
        <p class="highcharts-description">
        <script>

        // gr�fico 1
        Highcharts.chart('container7', {
        /*chart: {
            type: 'column',
        },*/
        plotOptions: {
            line: {
                dataLabels: { 
                    enabled: true 
                }, enableMouseTracking: false
            }, 
        },
        title: {
            text: '<?php echo "Balanço $string_per $ano";?>'
        },
        xAxis: {
            categories: [
                <?php
                    foreach($mes_nome as $key => $value){
                        if($key >= $per_ini && $key <= $per_meses){
                            echo "'$value',";
                        }
                    }
                ?>
            ]
        },
        credits: {
            enabled: false
        },
        series: [{
            type: 'line',
            name: 'Entradas',
            data: [
            <?php 
                for($i=$per_ini; $i <= $per_meses; $i++){
                    if ($i<10){
                        $i="0$i";
                    }
                    echo "$tot_entrada_[$i],"; 
                }
            ?>
            ]
        }, {
            type: 'line',
            name: 'Saidas',
            data: [
            <?php 
                    for($i=$per_ini; $i <= $per_meses; $i++){
                        if ($i<10){
                            $i="0$i";
                        }
                        echo "$tot_saida_[$i],"; 
                    }
            ?>
            ]
        }]
    });

    </script>
    </p>
</figure>

<figure class="highcharts-figure">
        <div id="container10"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 4
            Highcharts.chart('container10', {
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Balanço <?php echo "$string_per $ano";?>'
        },
        xAxis: {
            categories: ['Balanço']
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        accessibility: {
            point: {
                valueSuffix: '%'
            }
        },
        credits: {
            enabled: false
        },
        series: [{
            type: 'pie',
            name: 'Balanço',
            data: [{
                name: 'Entradas',
                y: <?php echo "$tot_entrada"; ?>,
                color: Highcharts.getOptions().colors[0] // Jane's color
            }, {
                name: 'Saidas',
                y: <?php echo "$tot_saida"; ?>,
                color: Highcharts.getOptions().colors[1] // John's color
            }, {
                name: 'Lucro',
                y: <?php echo "$saldo"; ?>,
                color: Highcharts.getOptions().colors[2] // Joe's color
            }],
            showInLegend: true,
            dataLabels: {
                enabled: false
            }
        }]
    });

    </script>
    </p>
</figure>

<div class="block">

    <figure class="highcharts-figure">
        <div id="container2"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 2
            Highcharts.chart('container2', {
                /*chart: {
                    type: 'column',
                },*/
                plotOptions: {
                    column: { 
                        dataLabels: { 
                            enabled: true 
                        } 
                    }
                },
                title: {
                    text: 'Faturamento <?php echo "$string_per $ano";?>'
                },
                xAxis: {
                    categories: [
                        <?php
                            foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Entradas',
                    data: [
                    <?php 
                        for($i=$per_ini; $i <= $per_meses; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$tot_entrada_[$i],"; 
                        }
                    ?>
                    ]
                }]
            });
        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
        <div id="container3"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 3
            Highcharts.chart('container3', {
                /*chart: {
                    type: 'column',
                },*/
                plotOptions: {
                    bar: { 
                        dataLabels: { 
                            enabled: true 
                        } 
                    } 
                },
                title: {
                    text: 'Despesas <?php echo "$string_per $ano";?>'
                },
                xAxis: {
                    categories: [
                        <?php
                            foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'bar',
                    name: 'Saidas',
                    data: [
                    <?php 
                        for($i=$per_ini; $i <= $per_meses; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$tot_saida_[$i],"; 
                        }
                    ?>
                    ],
                    color: Highcharts.getOptions().colors[8] // color Red
                }]
            });
        </script>
        </p>
    </figure>

    <figure class="highcharts-figure">
        <div id="container5"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 5
            Highcharts.chart('container5', {
                /*chart: {
                    type: 'column',
                },*/
                plotOptions: {
                    column: { 
                        dataLabels: { 
                            enabled: true 
                        } 
                    } 
                },
                title: {
                    text: 'Saldo - <?php echo "$string_per $ano";?> Lucro / Prejuízo'
                },
                xAxis: {
                    categories: [
                        <?php
                            foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Saldo Mensal',
                    data: [
                    <?php 
                        for($i=$per_ini; $i <= $per_meses; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$saldo_[$i],"; 
                        }
                    ?>
                    ],
                    color: Highcharts.getOptions().colors[2]
                }]
            });
        </script>
        </p>
    </figure>    


    <figure class="highcharts-figure">
        <div id="container4"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 4
            Highcharts.chart('container4', {
        /*chart: {
            type: 'column',
        },*/
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Balanço <?php echo "$string_per $ano";?>'
        },
        xAxis: {
            categories: ['Balanço']
        },
        credits: {
            enabled: false
        },
        series: [{
            type: 'column',
            name: 'Entradas',
            data: [<?php echo "$tot_entrada,"; ?>]
        }, {
            type: 'column',
            name: 'Saidas',
            data: [<?php echo "$tot_saida,"; ?>]
        }, {
            type: 'column',
            name: 'Saldo',
            data: [<?php echo "$saldo,"; ?>]
        }]
    });

    </script>
    </p>
</figure>

</div>

<figure class="highcharts-figure">
        <div id="container6"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 6
            Highcharts.chart('container6', {
                chart: {
                    type: 'line',
                },
                plotOptions: {
                    line: { 
                        dataLabels: { 
                            enabled: true 
                        } 
                    } 
                },
                title: {
                    text: 'Ticket Médio Mensal - <?php echo "$ano";?>'
                },
                xAxis: {
                    categories: [
                        <?php
                            foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: 'Ticket Médio',
                    data: [
                    <?php 
                        for($i=$per_ini; $i <= $per_meses; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$ticket_medio_[$i],"; 
                            
                        }
                    ?>
                    ]
                }]
            });
        </script>
        </p>
    </figure>    


    <figure class="highcharts-figure">
            <div id="container9"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 1
            Highcharts.chart('container9', {
            /*chart: {
                type: 'column',
            },*/
            plotOptions: {
                line: { 
                    dataLabels: { 
                        enabled: true 
                    } 
                }
            },
            title: {
                text: '<?php echo "Saldo em Conta $string_per $ano";?>'
            },
            xAxis: {
                categories: [
                    <?php
                        foreach($mes_nome as $key => $value){
                            if($key >= $per_ini && $key <= $per_meses){
                                echo "'$value',";
                            }
                        }
                    ?>
                ]
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'line',
                name: 'Saldo em Conta',
                data: [
                <?php 
                    for($i=$per_ini; $i <= $per_meses; $i++){
                        if ($i<10){
                            $i="0$i";
                        }
                        echo "$saldo_conta_[$i],"; 
                    }
                ?>
                ]
            }]
        });

        </script>
        </p>
    </figure>

    <?php 
        if($exibir_graf_emprestimos){
    ?>
    <figure class="highcharts-figure">
        <div id="container8"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 5
            Highcharts.chart('container8', {
                /*chart: {
                    type: 'column',
                },*/
                plotOptions: {
                    column: { 
                        dataLabels: { 
                            enabled: true 
                        }
                    },
                    line: { 
                        dataLabels: { 
                            enabled: true 
                        }, enableMouseTracking: false
                    }
                },
                title: {
                    text: 'Emprestimos Realizados - <?php echo "$string_per $ano";?>'
                },
                xAxis: {
                    categories: [
                        <?php
                            foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Faturamento Liquido',
                    data: [
                    <?php 
                        for($i=$per_ini; $i <= $per_meses; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$tot_entrada_sem_emprestimo[$i],"; 
                        }
                    ?>
                    ]
                },
                {
                    type: 'column',
                    name: 'Faturamento Bruto',
                    data: [
                    <?php 
                        for($i=$per_ini; $i <= $per_meses; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$tot_entrada_com_emprestimo[$i],"; 
                        }
                    ?>
                    ]
                },
                {
                    type: 'line',
                    name: 'Emprestimos',
                    data: [
                    <?php 
                        for($i=$per_ini; $i <= $per_meses; $i++){
                            if ($i<10){
                                $i="0$i";
                            }
                            echo "$total_emprestimo_[$i],"; 
                        }
                    ?>
                    ]
                },
                ]
            });
        </script>
        </p>
    </figure>    

    <figure class="highcharts-figure">
        <div id="container12"></div>
            <p class="highcharts-description">
            <script>

            // gr�fico 5
            Highcharts.chart('container12', {
                /*chart: {
                    type: 'column',
                },*/
                plotOptions: {
                    column: { 
                        dataLabels: { 
                            enabled: true 
                        }
                    },
                    line: { 
                        dataLabels: { 
                            enabled: true 
                        }, enableMouseTracking: false
                    }
                },
                title: {
                    text: 'Balanco - <?php echo "$string_per $ano";?>'
                },
                xAxis: {
                    categories: [
                        <?php
                            foreach($mes_nome as $key => $value){
                                if($key >= $per_ini && $key <= $per_meses){
                                    echo "'$value',";
                                }
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Faturamento Liquido',
                    data: [
                    <?php 
                        echo "$tot_geral_entrada_sem_emprestimo"; 
                        
                    ?>
                    ]
                },
                {
                    type: 'column',
                    name: 'Emprestimos',
                    data: [
                    <?php 

                        echo "$tot_geral_emprestimos"; 
                    ?>
                    ]
                },
                {
                    type: 'column',
                    name: 'Faturamento Bruto',
                    data: [
                    <?php 

                        echo "$tot_entrada"; 
                    ?>
                    ]
                }
                ]
            });
        </script>
        </p>
    </figure>    

    <?php 
        } //Fim config exibicao

        //Fim Permissao
        } 
    ?>
    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script>

    </body>
</html>