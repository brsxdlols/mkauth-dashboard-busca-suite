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

            #container3, #container4{
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
            <a href="#" aria-current="page"> <?php echo $Manifest->{'name'}." - V ".$Manifest->{'version'}; echo " - RAMAL"; ?></a>
            </li>
            <li><a href="index.php">ANUAL</a></li>
            <li><a href="graf_det.php">DETALHADO</a></li>
            <li><a href="graf_dia.php">DIÁRIO</a></li>
            <li><a href="graf_ramal_cli.php">RAMAL 1</a></li>
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
        $ano_atual = date('Y');
        $ano = isset($_GET['ano']) == "" ? $ano_atual : $_GET['ano'];
        
        $mes_atual = date('m');

        $periodo_ini = "";

        switch($mes_atual){
            case "01":
            case "02":
            case "03":
                $periodo_ini = "1t";
                break;
            case "04":
            case "05":
            case "06":
                $periodo_ini = "2t";
                break;
            case "07":
            case "08":
            case "09":
                $periodo_ini = "3t";
                break;  
            default:
                $periodo_ini = "4t"; 
                break;           
        }

        $periodo = isset($_GET['periodo']) == "" ? $periodo_ini : $_GET['periodo'];

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
            "%" =>"TODOS",
            );
        
        /*$forma_pag = isset($_GET['formapag']) == "" ? "%" : $_GET['formapag'];
            
        $query_motivo_chamado = mysql_query("SELECT DISTINCT formapag FROM sis_lanc ORDER BY formapag");
        $forma_pagamento["%"] = "TODOS";
        while ($pag = mysql_fetch_array($forma_pag)){
            $forma_pagamento[$pag['formapag']] = $pag['formapag'];
        }*/

        
        $ramal = isset($_GET['ramal']) == "" ? "%" : $_GET['ramal'];
            
        $query_ramal = mysqli_query($link, "SELECT nasname, shortname FROM nas");
        $ramal_cliente["%"] = "TODOS";
        while ($lista_ramal = mysqli_fetch_array($query_ramal)){
            $ramal_cliente[$lista_ramal['nasname']] = $lista_ramal['shortname'];

        }

        //print_r($ramal_cliente);


        //Fun��o parar verifica��o de String iniciada por um termo
        // Case sensitive
        function startsWith($string, $startString) { 
            $len = strlen($startString); 
            return (strtolower(substr($string, 0, $len)) === $startString); 
        } 

    ?>

    
    <form action="" method="get" id="form_graf">
    <table id="table_busca">
        <tr>
            <td colspan="4"><b>Gráfico de RAMAL 2 é baseado nas ultimas conexões do mês de cada cliente</b></td>
        </tr>
        <tr>
            <td><b>Qual ano deseja gerar o gráfico?</b></td>
            <td><b>Período do Gráfico:</b></td>
            <td><b>Ramal</b></td>
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
            <td>
                <select name="ramal">
                <?php
                    foreach ($ramal_cliente as $key => $value) {
                        $selected = ($ramal == $key) ? "selected=\"selected\"" : null;
                        echo "<option value=\"$key\" $selected >$value</option>";
                }
                ?>
                </select>
            </td>
            <td><input type="submit" name="submit" value="OK" id="btn_buscar"/></td>
        </tr>
        
        </table>

    </form>
                
                <!--<table>
                <tr>
                <td>Titulo</td>
                <td>Login</td>
                <td>Data Pagamento</td>
                <td>Valor</td>
                <td>Ramal</td>
                </tr>-->
    <?php    
          
          $starttime = microtime(true);
  
          unset($ramal_cliente['%']); //Retirar item % da Array

          if ($ramal == '%'){
            foreach($ramal_cliente as $key => $value){
                include('query_ramal.php');
            }
          }else {
              $key = $ramal;
            include('query_ramal.php');
          }



        //print_r($fat_por_ramal_ano);


    $endtime = microtime(true);


    $tempo_sql = ($endtime - $starttime);
    $tempo_sql = number_format($tempo_sql, 4);
    
    echo "<b>Tempo de Consulta no Banco de Dados:</b> $tempo_sql segundos";        


    /*echo "</br>";
    print_r($fat_por_ramal);*/

    mysqli_close($link);

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

    


    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <figure class="highcharts-figure">
        <div id="container"></div>
        <p class="highcharts-description">
        <script>

        // Gr�fico 1
        Highcharts.chart('container', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { 
                dataLabels: { 
                    enabled: true 
                } 
            },
        },
        title: {
            text: '<?php echo "Balanço $string_per $ano";?>'
        },
        xAxis: {
            categories: [
                <?php
                    for($i=$per_ini; $i <= $per_meses; $i++){
                        if ($i<10){
                            $i="0$i";
                        }
                        echo "'$lista_mes[$i]',";

                        /*foreach ($ramal_cliente as $key => $value) {
                            if($fat_por_ramal[$i][$value] != 0)
                                echo "'$lista_mes[$i]',";
                        }*/

                    }                    
                ?>
            ]
        },
        credits: {
            enabled: false
        },
        series: [
            <?php
                if($ramal == '%'){
                    foreach ($ramal_cliente as $key => $value) 
                    {
                        echo "
                        {
                            name: '$value',
                            data: [
                            ";
                                for($i=$per_ini; $i <= $per_meses; $i++){
                                    if ($i<10){
                                        $i="0$i";
                                    }
                                    foreach ($fat_por_ramal[$i] as $key2 => $value2) 
                                    {
                                        if($value == $key2)
                                            echo "$value2,"; 
                                    }
                                }
                            echo "
                            ]
                        },
                        ";
                    }
                }
                else{
                    echo "
                    {
                        name: '$ramal_cliente[$ramal]',
                        data: [
                        ";
                            for($i=$per_ini; $i <= $per_meses; $i++){
                                if ($i<10){
                                    $i="0$i";
                                }
                                foreach ($fat_por_ramal[$i] as $key2 => $value2) 
                                {
                                        echo "$value2,"; 
                                }
                            }
                        echo "
                        ]
                    },
                    ";
                }

            ?>
        ]
    });

    </script>
    </p>
</figure>

<figure class="highcharts-figure">
    <div id="container3"></div>
    <p class="highcharts-description">
    <script>
    
        Highcharts.chart('container3', {
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: 'Faturamento por Ramal'
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
                foreach ($fat_por_ramal_ano as $key => $value) {
                            echo "
                            { 
                                name: '$key',
                                y: $value
                            },
                            
                            ";
                    }
                ?>
            ]
        }]
    });
    
    </script>

    <figure class="highcharts-figure">
        <div id="container4"></div>
            <p class="highcharts-description">
            <script>

            // Gr�fico 4
            Highcharts.chart('container4', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Faturamento Total em <?php echo "$string_per $ano";?>'
        },
        xAxis: {
            categories: [
                'Total'
            ]
        },
        credits: {
            enabled: false
        },
        series: [
                <?php 
                foreach ($fat_por_ramal_ano as $key => $value) 
                {
                    echo "
                    {
                    name: '$key',
                    data: [
                        ";
                        foreach ($fat_por_ramal_ano as $key2 => $value2) 
                        {
                            if($key == $key2)
                                echo "$value,";          
                        }
                    echo "
                    ]
                    },
                    ";
                }
                ?>
        ]
    });

    </script>
    </p>
</figure>



    <?php 
        echo "</table>";

        //Fim Permissao
        } 
    ?>
    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script>

    </body>
</html>