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
        echo '<html lang="pt-BR">'; // Fix vers�o antiga MK-AUTH
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

            #container {
                display:block;
                height: 600px; 
                min-width: 500px;
            
            }

            #container2, #container3, #container4, #container5, #container6, #container7{
                width: 48%;
                height: 550px; 
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
            <a href="#" aria-current="page"> <?php echo $Manifest->{'name'}." - V ".$Manifest->{'version'}; echo " - 5 ANOS"?></a>
            </li>
            <li><a href="index.php">ANUAL</a></li>
            <li><a href="graf_det.php">DETALHADO</a></li>
            <li><a href="graf_dia.php">DIÁRIO</a></li>
            <li><a href="graf_ramal_cli.php">RAMAL 1</a></li>
            <li><a href="graf_ramal.php">RAMAL 2</a></li>
            <li><a href="graf_nome.php">NOME</a></li>
            <li><a href="graf_cidade_cli.php">CIDADE</a></li>
            <li><a href="graf_bairro_cli.php">BAIRRO</a></li>
            <li><a href="graf_periodo.php">ULT MESES</a></li>
            <li>
                <img src="img/icon_print.png" class="icon" title="Imprimir" onClick="window.print()"></img>
            </li>
        </ul>
    </nav>


    <?php 
        if ($acesso_permitido)
        {
    ?>

    <?php
        $ano_atual = date('Y');
        $mes_atual = date('m');

        $ano = isset($_GET['ano']) == "" ? $ano_atual : $_GET['ano'];
        $qnt_anos = isset($_GET['qnt_anos']) == "" ? 5 : $_GET['qnt_anos'];
    ?>

    <form action="" method="get" id="form_graf">
    <table id="table_busca">

        <tr>
            <td><b>Qual ano final do gráfico?</b></td>
            <td><b>Gerar quantos anos?</b></td>
            <td></td>
        </tr>
        <tr>
            <td><input type="number" name="ano" min="1990" value="<?php echo $ano; ?>"/></td>
            <td><input type="number" name="qnt_anos" min="1" value="<?php echo $qnt_anos; ?>"/></td>
            <td><input type="submit" name="submit" value="OK" id="btn_buscar"/></td>
        </tr>
        
        </table>

    </form>

    <?php    

 
    //$starttime = microtime(true);

    $tot_anos = $qnt_anos - 1;
    for($i=$ano-$tot_anos; $i <= $ano; $i++){
        //Tot Entrada e Saida
        $query_mes[$i] = mysqli_query($link, "SELECT sum(entrada) tot_entrada, sum(saida) tot_saida FROM sis_caixa 
        WHERE 
        data LIKE '$i-%' 
        ORDER BY data");

        if (!$query_mes[$i]){
            echo mysqli_error($link);
            echo "</br>";
        }

        $tot_entrada_[$i] = 0;
        $tot_saida_[$i] = 0;

        while ($row = mysqli_fetch_array($query_mes[$i])){            
            $tot_entrada_[$i] = $row['tot_entrada'];
            $tot_saida_[$i] = $row['tot_saida'];
        }

        //Tot Estorno
        $query_mes[$i] = mysqli_query($link, "SELECT sum(entrada) tot_estorno_entrada, sum(saida) tot_estorno_saida FROM sis_caixa 
        WHERE historico LIKE 'estorno%' AND
        data LIKE '$i-%' 
        ORDER BY data");

        if (!$query_mes[$i]){
            echo mysqli_error($link);
            echo "</br>";
        }

        $estorno_entrada_[$i] = 0;
        $estorno_saida_[$i] = 0;

        while ($row = mysqli_fetch_array($query_mes[$i])){
            $estorno_entrada_[$i] += $row['tot_estorno_entrada'];
            $estorno_saida_[$i] += $row['tot_estorno_saida'];
        }
        
        $tot_entrada_[$i] = $tot_entrada_[$i] - $estorno_entrada_[$i] - $estorno_saida_[$i];
        $tot_entrada_[$i] = round($tot_entrada_[$i]);

        $tot_saida_[$i] = $tot_saida_[$i] - $estorno_entrada_[$i] - $estorno_saida_[$i];
        $tot_saida_[$i] = round($tot_saida_[$i]);

        $saldo_[$i] = $tot_entrada_[$i] - $tot_saida_[$i];
        $saldo_[$i] = round($saldo_[$i]);

        if ($ano_atual != $i){
            $media_entrada_[$i] = round($tot_entrada_[$i] / 12);
            $media_saida_[$i] = round($tot_saida_[$i] / 12);
            $media_saldo_[$i] = round($saldo_[$i] / 12);
        }else{
            /*if(date('m') != 1 && date('m') != 12)
            {
                $mes_atual -= 1;
            }*/
            $media_entrada_[$i] = round($tot_entrada_[$i] / $mes_atual);
            $media_saida_[$i] = round($tot_saida_[$i] / $mes_atual);
            $media_saldo_[$i] = round($saldo_[$i] / $mes_atual);

        }
    }
    ?>


</table>


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

        // Gr�fico 1
        Highcharts.chart('container', {
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
            }, 
        },
        title: {
            text: '<?php echo "Balanço 5 anos";?>'
        },
        xAxis: {
            categories: [
                <?php
                    for($i=$ano-$tot_anos; $i <= $ano; $i++){
                        echo "$i,";
                    }
                ?>
            ]
        },
        credits: {
            enabled: false
        },
        series: [{
            type: 'column',
            name: 'Faturamento',
            data: [
            <?php 
                for($i=$ano-$tot_anos; $i <= $ano; $i++){
                    echo "$tot_entrada_[$i],"; 
                }
            ?>
            ]
        }, {
            type: 'column',
            name: 'Despesas',
            data: [
            <?php 
                    for($i=$ano-$tot_anos; $i <= $ano; $i++){
                        echo "$tot_saida_[$i],"; 
                    }
            ?>
            ]
        }, {
            type: 'column',
            name: 'Lucro / Prejuízo',
            data: [
            <?php 
                    for($i=$ano-$tot_anos; $i <= $ano; $i++){
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
        <div id="container2"></div>
            <p class="highcharts-description">
            <script>

            // Gr�fico 2
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
                    text: 'Faturamento'
                },
                xAxis: {
                    categories: [
                        <?php
                            for($i=$ano-$tot_anos; $i <= $ano; $i++){
                                echo "$i,";
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Faturamento',
                    data: [
                    <?php 
                        for($i=$ano-$tot_anos; $i <= $ano; $i++){
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
        <div id="container4"></div>
            <p class="highcharts-description">
            <script>

            // Gr�fico 2
            Highcharts.chart('container4', {
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
                    text: 'Faturamento Media Mensal'
                },
                xAxis: {
                    categories: [
                        <?php
                            for($i=$ano-$tot_anos; $i <= $ano; $i++){
                                echo "$i,";
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'column',
                    name: 'Media Mensal Entradas',
                    data: [
                    <?php 
                        for($i=$ano-$tot_anos; $i <= $ano; $i++){
                            echo "$media_entrada_[$i],"; 
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

            // Gr�fico 3
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
                    text: 'Despesas'
                },
                xAxis: {
                    categories: [
                        <?php
                            for($i=$ano-$tot_anos; $i <= $ano; $i++){
                                echo "$i,";
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'bar',
                    name: 'Despesas',
                    data: [
                    <?php 
                        for($i=$ano-$tot_anos; $i <= $ano; $i++){
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
        <div id="container6"></div>
            <p class="highcharts-description">
            <script>

            // Gr�fico 3
            Highcharts.chart('container6', {
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
                    text: 'Despesas Media Mensal'
                },
                xAxis: {
                    categories: [
                        <?php
                            for($i=$ano-$tot_anos; $i <= $ano; $i++){
                                echo "$i,";
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'bar',
                    name: 'Media Mensal Saidas',
                    data: [
                    <?php 
                        for($i=$ano-$tot_anos; $i <= $ano; $i++){
                            echo "$media_saida_[$i],"; 
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

            // Gr�fico 5
            Highcharts.chart('container5', {
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
                    text: 'Saldo - Lucro / Prejuízo'
                },
                xAxis: {
                    categories: [
                        <?php
                            for($i=$ano-$tot_anos; $i <= $ano; $i++){
                                echo "$i,";
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'line',
                    name: 'Lucro / Prejuízo',
                    data: [
                    <?php 
                        for($i=$ano-$tot_anos; $i <= $ano; $i++){
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
        <div id="container7"></div>
            <p class="highcharts-description">
            <script>

            // Gr�fico 2
            Highcharts.chart('container7', {
                /*chart: {
                    type: 'line',
                },*/
                plotOptions: {
                    line: { 
                        dataLabels: { 
                            enabled: true 
                        } 
                    }
                },
                title: {
                    text: 'Saldo Lucro/Prejuízo - Media Mensal'
                },
                xAxis: {
                    categories: [
                        <?php
                            for($i=$ano-$tot_anos; $i <= $ano; $i++){
                                echo "$i,";
                            }
                        ?>
                    ]
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'line',
                    name: 'Saldo Lucro/Prejuízo - Media Mensal',
                    data: [
                    <?php 
                        for($i=$ano-$tot_anos; $i <= $ano; $i++){
                            echo "$media_saldo_[$i],"; 
                        }
                    ?>
                    ], color: Highcharts.getOptions().colors[2]
                }]
            });
        </script>
        </p>
    </figure>

    <?php 

        //Fim Permissao
        } 
    ?>
    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script>

    </body>
</html>