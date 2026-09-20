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
            .buscar { width: 100%;  }
            #form_graf { width: auto; margin: 0 auto; }

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
            width: 65%;
            height: 500px; 
            }
            #container5 {
            float:left;
            width: 35%;
            height: 500px; 
            }

            #container3, #container6 {
            float:left; 
            width: 50%;
            height: 500px; 
            }
            
            #container2 {
            float:left;
            width: 100%;
            height: 500px; 
            }

            #container4 {
            float:left;
            width: 80%;
            height: 500px; 
            }

            #container11{
                float:left;
                width: 20%;
                height: 500px; 
            }
            

            .highcharts-figure, .highcharts-data-table table {
                width:100%;
                margin: 1em auto;
            }

            .icon_add { width: 40px; }
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
            <a href="#" aria-current="page"> <?php echo $Manifest->{'name'}." - V ".$Manifest->{'version'}; echo " - DETALHADO";?></a>
            </li>
            <li><a href="index.php">ANUAL</a></li>
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
        
        function difDatas($data_inicio, $data_fim){
            $d1 = new DateTime($data_inicio);
            $d2 = new DateTime($data_fim);
        
            // Resgata diferen�a entre as datas
            //$dateInterval = ;
            return $d1->diff($d2)->format('%a')+1;
            //echo "</br>";
        }

        $data_ini = date('Y-m');

        $data_atual = date('Y-m-d');
        $data_inicial = isset($_GET['data_inicial']) == '' ? "$data_ini-01" : $_GET['data_inicial'];
        //echo $data_inicial;
        //echo "</br>";
        $data_final = isset($_GET['data_final']) == '' ? $data_atual : $_GET['data_final'];

        $data_ini_graf = date('d/m/Y', strtotime($data_inicial));
        $data_end_graf = date('d/m/Y', strtotime($data_final));

        //echo $data_final;

        //Função parar verificação de String iniciada por um termo
        // Case sensitive
        function startsWith($string, $startString) { 
            $len = strlen($startString); 
            return (strtolower(substr($string, 0, $len)) === $startString); 
        }
    ?>


    <form action="" method="get">
        <table id="form_graf">
            <tr>
                <td><b>Data Inicial:</b></td>
                <td><b>Data Final:</b></td>
                <td></td>
            </tr>
            <tr>
                <td><input type="date" name="data_inicial" value="<?php echo $data_inicial; ?>"/></td>
                <td><input type="date" name="data_final" value="<?php echo $data_final; ?>"/></td>
                <td><input type="submit" name="submit" id="btn_buscar" value="OK" /></td>
            </tr>
        </table>
    </form>

    <?php
            
    //Grafico Detalhado
    $query_mysql = mysqli_query($link, "SELECT usuario, data, historico, complemento, entrada, saida, planodecontas FROM sis_caixa WHERE historico NOT LIKE 'estorno titulo%' AND data BETWEEN '$data_inicial' AND '$data_final 23:59:59' ORDER BY data");

    
    echo "<table>
    <tr class='linha_titulo'>
    <td colspan='4'>Despesas Detalhadas</td>
    </tr>
    <tr class='linha_titulo'>
    <td>Data</<td>
    <td>Descricao</<td>
    <td>Plano</<td>
    <td>Valor</<td>
    </tr>";

    while ($row = mysqli_fetch_array($query_mysql)){
        //print_r($row);
        //echo "</br>";
        $data_mov = $row['data'];
        
        $data_mov = date('Y-m-d H:i', strtotime($data_mov));

        $user_mov = $row['usuario'];
        $historico = $row['historico'];
        $plano_contas = $row['planodecontas'];

        if($plano_contas == "outros")
            $plano_contas = "Outros";

        $complemento = $row['complemento'];
        
        $entrada = $row['entrada'];
        $saida = $row['saida'];

        $query_contas_detalhes = mysqli_query($link, "SELECT id, valorpago, historico, planodecontas, datapg FROM sis_contaspagar WHERE valorpago LIKE '$saida' AND datapg LIKE '$data_mov%' AND status LIKE 'liquidado'");

        if(!$query_contas_detalhes){
            echo mysqli_error($link);
        }

        while($row2 = mysqli_fetch_array($query_contas_detalhes)){
            $conta_id = $row2['id'];
            $conta_valorpg = $row2['valorpago'];


            $conta_historico = $row2['historico'];
            $conta_planodecontas = $row2['planodecontas'];

            if($conta_planodecontas == "outros")
                $conta_planodecontas = "Outros";
        
            $conta_datapg = $row2['datapg'];
            //$conta_datapg = date('Y-m-d H-i-00', strtotime($conta_datapg));

            if(startsWith($conta_historico, "cartao")){
                $conta_planodecontas = "CARTAO_CREDITO";
                $array_tipo_conta[$conta_planodecontas] += $row2['valorpago'];
            }
            elseif(startsWith($conta_historico, "salario"))
            {
                $conta_planodecontas = "salario";
                $array_tipo_conta[$conta_planodecontas] += $row2['valorpago'];
            }
            else{
                $array_tipo_conta[$conta_planodecontas] += $row2['valorpago'];
            }
        
            echo "
            <tr class='linha_resultados'>
            <td>$conta_datapg</td>
            <td>$conta_historico</td>
            <td>$conta_planodecontas</td>
            <td>$conta_valorpg</td>
            </tr>";

            /*echo "<pre>";
            print_r($row2);
            echo "</pre>";*/
        }

        
        if(startsWith($historico, "estorno do pagamento")){
            $cont = strlen($historico) - 21;
            $num_pg = intval(substr($historico, 21, $cont));
            
            $query_contas_det = mysqli_query($link, "SELECT planodecontas FROM sis_contaspagar WHERE id LIKE '$num_pg'");

            while($row3 = mysqli_fetch_array($query_contas_det)){
                $c_plano = $row3['planodecontas'];

                if($c_plano == "outros")
                    $c_plano = "Outros";

                $array_tipo_conta[$c_plano] -= $entrada;
            }
        }
        /*else if(startsWith($historico, "pagamento da conta salario")){
            $array_tipo_conta['salario'] += $saida;

            echo "
            <tr class='linha_resultados'>
            <td>$data_mov</td>
            <td>$historico</td>
            <td>salario</td>
            <td>$saida</td>
            </tr>";
        }*/
        else if(startsWith($historico, "tarifa") && $saida > 0){
            $array_tipo_conta['tarifas'] += $saida;

            echo "
            <tr class='linha_resultados'>
            <td>$data_mov</td>
            <td>$historico</td>
            <td>tarifas</td>
            <td>$saida</td>
            </tr>";
        }
        else if($data_mov != $conta_datapg && $saida != $conta_valorpg)
        {
            $array_tipo_conta[$plano_contas] += $saida;   

            if($entrada == 0 && $saida > 0){
                echo "
                <tr class='linha_resultados'>
                <td>$data_mov</td>
                <td>$historico</td>
                <td>$plano_contas</td>
                <td>$saida</td>
                </tr>";
            }

        }    

    }
    /*echo "<pre>";
    print_r($array_tipo_conta);
    echo "</pre>";*/

/*
    foreach($array_tipo_conta as $key => $value){
        if($key == "Outros"){
            //unset($array_tipo_conta[$key]);
            $array_tipo_conta['Outros'] += $value;
        }elseif ($key == "outros"){
            //unset($array_tipo_conta[$key]);
            $array_tipo_conta['Outros'] += $value;
        }
        
    }*/



    $tot_despesas = round(array_sum($array_tipo_conta));


    //echo "Total salários: R$ $tot_salarios";
 
    echo "
    <tr class='linha_resultados'>
    <td></td>
    <td></td>
    <td>Total: </td>
    <td>R$ $tot_despesas</td>
    </tr>";

    // Entradas no Sistema
    
    /*$lista_tipo_pagamento = mysqli_query($link, "SELECT DISTINCT formapag FROM sis_lanc ORDER BY formapag");
    while($lista = mysqli_fetch_array($lista_tipo_pagamento)){
        $lista_tipo_pag[] = $lista['formapag'];
    }*/

    $query_tipo_pag = mysqli_query($link, "SELECT a.id, a.formapag, a.valorpag FROM sis_lanc a
    WHERE (a.formapag NOT LIKE 'boleto' AND a.datapag BETWEEN '$data_inicial' AND '$data_final 23:59:59')
    OR (a.formapag LIKE 'boleto' AND a.id IN (SELECT titulo FROM sis_rettitulos WHERE titulo = a.id AND datta BETWEEN '$data_inicial' AND '$data_final 23:59:59')) ORDER BY a.formapag");
    
    if(!$query_tipo_pag){
        echo mysqli_error($link);
    }

    while($tipo_pag = mysqli_fetch_array($query_tipo_pag)){
        /*$formapag['formapag'] = $tipo_pag['formapag'];
        $valorpag = $tipo_pag['valorpag'];*/

        /*echo "<pre>";
        print_r($tipo_pag);
        echo "</pre>";*/
        $pag_por_tipo_pag_[$tipo_pag['formapag']] += $tipo_pag['valorpag'];

        /*foreach($lista_tipo_pag as $key => $value){
            if($formapag['formapag'] == $value){
            }
        }*/

    }

    $query_entradas_manuais = mysqli_query($link, "SELECT entrada, planodecontas FROM sis_caixa WHERE tipomov LIKE 'man' AND entrada > 0 AND data BETWEEN '$data_inicial' AND '$data_final 23:59:59' ORDER BY data");


    while($in_manual = mysqli_fetch_array($query_entradas_manuais)){
        /*echo "<pre>";
        print_r($in_manual);
        echo "</pre>";*/
        $in_manual_valor = $in_manual['entrada'];
        $in_manual_planodecontas = $in_manual['planodecontas'];

        $pag_por_tipo_pag_[$in_manual_planodecontas] += $in_manual_valor;
    }

    /*$query_estorno = mysqli_query($link, "SELECT saida FROM sis_caixa WHERE historico LIKE 'estorno titulo%' AND data BETWEEN '$data_inicial' AND '$data_final 23:59:59' ORDER BY data");


    while($row4 = mysqli_fetch_array($query_estorno)){
        echo "<pre>";
        print_r($row4);
        echo "</pre>";
        //$estorno_entrada = $row4['entrada'];
        $estorno_saida = $row4['saida'];

        //$pag_por_tipo_pag_['estorno_entrada'] += $estorno_entrada;

        $pag_por_tipo_pag_['dinheiro'] -= $estorno_saida;

    }*/

    $tot_entradas = round(array_sum($pag_por_tipo_pag_));

    $tot_saldo = $tot_entradas - $tot_despesas;

    //Calculo do Lucro Bruto
    $tot_despesas_sem_salarios = round(array_sum($array_tipo_conta) - $array_tipo_conta["salario"]);
    $tot_saldo_sem_salarios = $tot_entradas - $tot_despesas_sem_salarios;

    //echo "Entradas: R$ $tot_entradas Saidas: R$ $tot_despesas Saldo: R$ $tot_saldo ";

    // Media Diaria
    $div = difDatas($data_inicial,$data_final);
    $med_tot_entradas = round($tot_entradas / $div,2);
    $med_tot_saidas = round($tot_despesas / $div, 2);
    $med_tot_saldo = round($med_tot_entradas - $med_tot_saidas,2);

    //Contas a pagar
    $query_contas_apagar = mysqli_query($link, "SELECT vencimento, valor, historico, planodecontas FROM sis_contaspagar WHERE (status LIKE 'aberto' OR status like 'vencido') AND vencimento BETWEEN '$data_inicial' AND '$data_final 23:59:59' ORDER BY vencimento");

    if(!$query_contas_apagar){
        echo mysqli_error($link);
    }

    while($row4 = mysqli_fetch_array($query_contas_apagar)){
        $c_open_venc = $row4['vencimento'];
        $c_open_valor = $row4['valor'];
        $c_open_historico = $row4['historico'];
        $c_open_planodecontas = $row4['planodecontas'];

        if($c_open_planodecontas == "outros")
        {
            $c_open_planodecontas = "Outros";
        }   
    
        $array_c_open[$c_open_planodecontas] += $c_open_valor;

        

    /*echo "<pre>";
    print_r($row4);
    echo "</pre>";*/

    }

    mysqli_close($link);

    // total de contas a pagar
    $tot_c_open = array_sum($array_c_open);
    
    ?>
    
    
    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>


    <figure class="highcharts-figure">
        <div id="container2"></div>
        <p class="highcharts-description">
        <script>
            // Gráfico de Despesas
    Highcharts.chart('container2', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: '<?php echo "Despesas Detalhadas -  $data_ini_graf - $data_end_graf"; ?>'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            foreach ($array_tipo_conta as $key => $value) {
                if($value != 0){
                    echo "
                    {
                        name: '$key',
                        data: [$value]
                    },
                        ";
                }
            }
        ?>
        
        ]
    });

    </script>
    </p>
    </figure>



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
            text: '<?php echo "Entradas Detalhadas -  $data_ini_graf - $data_end_graf"; ?>'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            foreach ($pag_por_tipo_pag_ as $key => $value) {
                if($value != 0){
                    echo "
                    {
                        name: '$key',
                        data: [$value]
                    },
                    ";
                }
            }
        ?>
        
        ]
    });

    </script>
    </p>
    </figure>

    <figure class="highcharts-figure">
        <div id="container5"></div>
        <p class="highcharts-description">
        <script>
            // Média Diaria
    Highcharts.chart('container5', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: '<?php echo "Média Diária -  $data_ini_graf - $data_end_graf"; ?>'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            echo "
            {
                name: 'Entradas',
                data: [$med_tot_entradas]
            },
            {
                name: 'Saidas',
                data: [$med_tot_saidas]
            },
            {
                name: 'Lucro',
                data: [$med_tot_saldo]
            }
            ";
        ?>
        
        ]
    });

    </script>
    </p>
    </figure>

    <figure class="highcharts-figure">
        <div id="container6"></div>
        <p class="highcharts-description">
        <script>
            // Totais
    Highcharts.chart('container6', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Totais com Lucro Bruto (sem pagamento de salarios)'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            echo "
            {
                name: 'Entradas',
                data: [$tot_entradas]
            },
            {
                name: 'Saidas',
                data: [$tot_despesas_sem_salarios]
            },
            {
                name: 'Lucro',
                data: [$tot_saldo_sem_salarios]
            }
            ";
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
            // Totais
    Highcharts.chart('container3', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Totais com Lucro Liquido'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            echo "
            {
                name: 'Entradas',
                data: [$tot_entradas]
            },
            {
                name: 'Saidas',
                data: [$tot_despesas]
            },
            {
                name: 'Lucro',
                data: [$tot_saldo]
            }
            ";
        ?>
        
        ]
    });

    </script>
    </p>
    </figure>



    
    <figure class="highcharts-figure">
        <div id="container4"></div>
        <p class="highcharts-description">
        <script>
            
            // Gráfico de Contas a Pagar
    Highcharts.chart('container4', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: '<?php echo "DESENVOLVIMENTO - Contas a Pagar -  $data_ini_graf - $data_end_graf"; ?>'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            foreach ($array_c_open as $key => $value) {
                if($value != 0){
                    echo "
                    {
                        name: '$key',
                        data: [$value]
                    },
                        ";
                }
            }
        ?>
        
        ]
    });
    </script>
    </p>
    </figure>

    <figure class="highcharts-figure">
        <div id="container11"></div>
        <p class="highcharts-description">
        <script>
            // Totais
    Highcharts.chart('container11', {
        chart: {
            type: 'column',
        },
        plotOptions: {
            column: { dataLabels: { enabled: true } }
        },
        title: {
            text: 'Totais contas a pagar'
        },
        xAxis: {
            categories: ['']
        },
        credits: {
            enabled: false
        },
        series: [
        <?php
            echo "
            {
                name: 'Totais',
                data: [$tot_c_open]
            }
            ";
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