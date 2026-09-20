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
            #tabela_fat {float:left; min-width: 500px;}
            .buscar { width: 100%; }
            #form_graf { width: 100%; }

            #btn_buscar, #form_graf input, #form_graf select { padding: 10px; font-size:18px; }
            #btn_buscar { background: Navy; color:#FFF; border: 1.5px solid Navy;}

            .linha_titulo {font-weight: bold; text-align:center; background: Navy; font-size: 14px; }
            .linha_titulo td { width: auto; padding: 4px 3px; color: #FFF; } 

            .linha_resultados { }
            .linha_resultados:nth-last-child(2n) {background:#E0E0F8;}
            .linha_resultados td{ width: auto; font-size: 13px; padding: 4px 3px;}

            .entrada { font-weight: bold; color: DarkGreen; }
            .saida { font-weight: bold; color: DarkRed; }

            .nome_cliente { font-weight: bold;  }
            .nome_cliente a:hover { text-decoration:underline;}

            .center { text-align:center;  }

            #align { text-align: center;}

            #prev_faturamento { width: auto; height: auto; background:#209cee; color:#FFF; float:left; margin: 2px 10px; padding: 10px 40px; }
            #prev_faturamento #prev_titulo { font-weight: bold; font-size: 15px; }
            #prev_faturamento #prev_valor { font-size: 30px; }
            #prev_faturamento #prev_porc_recebido { font-size: 15px; font-weight: bold; }
            
            #container{
            float:left;
            width: 75%;
            height: 450px; 
            }
            
            #container2 {
            float:left;
            width: 25%;
            height: 450px; 
            }

            .highcharts-figure, .highcharts-data-table table {
                width:100%;
                margin: 1em auto;
            }
            .icon { width: 20px; height: 20px; vertical-align: middle;}
            .icon_add { width: 40px; }

            #totais { display:block; }
            .tot { width: 33%; height: auto; border-radius: 10px; padding: 5px 20px; margin: 5px 0px; display: inline-flex; color:#FFF; text-align: left; align: middle;}
            .red { background: #DC143C; }
            .blue { background: #1E90FF; }
            .green { background: #90EE90; color:#000; }
            .tot_values{ font-size: 30px; font-weight: 500; margin:0; padding:0; }
            .tit_small { font-size: 13px;  }
        </style>

                    
        <script type="text/javascript">
                function abrirJanela(pagina, largura, altura) {
                // Definindo centro da tela
                var esquerda = (screen.width - largura)/2;
                var topo = (screen.height - altura)/2;
                // Abre a nova janela
                minhaJanela = window.open(pagina,'','height=' + altura + ', width=' + largura + ', top=' + topo + ', left=' + esquerda);
                }
        </script>

</head>
<body>



<?php include('../../topo.php'); ?>



    <nav class="breadcrumb has-bullet-separator is-centered" aria-label="breadcrumbs">
        <ul>
            <li class="is-active">
            <a href="#" aria-current="page"> <?php echo $Manifest->{'name'}." - V ".$Manifest->{'version'}; echo " - NOME";?></a>
            </li>
            <li><a href="index.php">ANUAL</a></li>
            <li><a href="graf_det.php">DETALHADO</a></li>
            <li><a href="graf_dia.php">DIÁRIO</a></li>
            <li><a href="graf_ramal_cli.php">RAMAL 1</a></li>
            <li><a href="graf_ramal.php">RAMAL 2</a></li>
            <li><a href="graf_5a.php">GRAF 5 ANOS</a></li>
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
        
        $data_atual = date('Y-m-d');
        $data_inicial = isset($_GET['data_inicial']) == '' ? $data_atual : $_GET['data_inicial'];
        //echo $data_inicial;
        //echo "</br>";
        $data_final = isset($_GET['data_final']) == '' ? $data_atual : $_GET['data_final'];

        $historico_busca = isset($_GET['busca']) == '' ? '' : $_GET['busca'];
        $historico_busca=trim($historico_busca);		
        //$palavra_busca = str_replace(" ","%", $historico_busca);

        $mes = date('m', strtotime($data_inicial));
        $ano = date('Y', strtotime($data_inicial));

        //echo $data_final;

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
            $string_graf_anual = "a.datapag LIKE '$ano-01%' OR a.datapag LIKE '$ano-02%' OR a.datapag LIKE '$ano-03%' OR a.datapag LIKE '$ano-04%' OR a.datapag LIKE '$ano-05%' OR a.datapag LIKE '$ano-06%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "2s"){
            $per_ini = 7;
            $per_meses = 12;
            $string_per = "2° Semestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-07%' OR a.datapag LIKE '$ano-08%' OR a.datapag LIKE '$ano-09%' OR a.datapag LIKE '$ano-10%' OR a.datapag LIKE '$ano-11%' OR a.datapag LIKE '$ano-12%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "1q"){
            $per_ini = 1;
            $per_meses = 4;
            $string_per = "1° Quadrimestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-01%' OR a.datapag LIKE '$ano-02%' OR a.datapag LIKE '$ano-03%' OR a.datapag LIKE '$ano-04%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "2q"){
            $per_ini = 5;
            $per_meses = 8;
            $string_per = "2° Quadrimestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-05%' OR a.datapag LIKE '$ano-06%' OR a.datapag LIKE '$ano-07%' OR a.datapag LIKE '$ano-08%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "3q"){
            $per_ini = 9;
            $per_meses = 12;
            $string_per = "3° Quadrimestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-09%' OR a.datapag LIKE '$ano-10%' OR a.datapag LIKE '$ano-11%' OR a.datapag LIKE '$ano-12%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "1t"){
            $per_ini = 1;
            $per_meses = 3;
            $string_per = "1° Trimestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-01%' OR a.datapag LIKE '$ano-02%' OR a.datapag LIKE '$ano-03%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "2t"){
            $per_ini = 4;
            $per_meses = 6;
            $string_per = "2° Trimestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-04%' OR a.datapag LIKE '$ano-05%' OR a.datapag LIKE '$ano-06%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "3t"){
            $per_ini = 7;
            $per_meses = 9;
            $string_per = "3° Trimestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-07%' OR a.datapag LIKE '$ano-08%' OR a.datapag LIKE '$ano-09%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else if ($periodo == "4t"){
            $per_ini = 10;
            $per_meses = 12;
            $string_per = "4° Trimestre - ";
            $string_graf_anual = "a.datapag LIKE '$ano-10%' OR a.datapag LIKE '$ano-11%' OR a.datapag LIKE '$ano-12%'";
            //echo "$per_ini</br>$per_meses</br>";
        }
        else{
            $per_ini = 1;
            $per_meses = 12;
            $string_graf_anual = "a.datapag LIKE '$ano%'";
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

    <form action="" method="get" id="form_graf">
    <table id="table_busca">

        <tr>
            <td><b>Qual ano deseja gerar o gráfico?</b></td>
            <td><b>Período do gráfico:</b></td>
            <td class="buscar"><b>Pesquisar:</b></td>

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
            <td><input type="text" name="busca" class="buscar" value="<?php echo $historico_busca; ?>"/></td>

            <td><input type="submit" name="submit" value="OK" id="btn_buscar"/></td>
        </tr>
        
        </table>

    </form>

    <?php
    
    for($i=$per_ini; $i <= $per_meses; $i++){
        if ($i<10){
            $i="0$i";
        }
        $total[$i] = 0;

        $query_tipo_pag[$i] = mysqli_query($link, "SELECT sum(a.valorpag) tot_pago FROM sis_lanc a LEFT JOIN sis_cliente c ON a.login = c.login
        WHERE (a.datapag LIKE '$ano-$i%') AND (c.nome LIKE '%$historico_busca%') ORDER BY a.datapag");
        


        /*if(!$query_tipo_pag){
            echo mysql_error();
        }*/
        
        while($fat = mysqli_fetch_array($query_tipo_pag[$i])){
            //$faturamento['formapag'] = $faturamento['formapag'];
            $total[$i] += $fat['tot_pago'];
        }
        //echo $total[$i]."<br>";
        $total[$i] = round($total[$i],2);
    }
    $total_geral = array_sum($total);
    /*echo "<pre>";
    print_r($total);
    echo "</pre>";*/

    //echo $total_geral;

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
            text: '<?php echo "Balanço $string_per $ano / $historico_busca";?>'
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
            name: 'Faturamento',
            data: [
            <?php 
                for($i=$per_ini; $i <= $per_meses; $i++){
                    if ($i<10){
                        $i="0$i";
                    }
                    echo "$total[$i],"; 
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

        // gr�fico 1
        Highcharts.chart('container2', {
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
            text: '<?php echo "Balanço $string_per $ano";?>'
        },
        xAxis: {
            categories: [
                <?php
                    echo "'Total Faturado'";
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
                echo "$total_geral"; 
            ?>
            ]
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