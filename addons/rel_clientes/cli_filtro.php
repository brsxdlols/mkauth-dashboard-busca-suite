<!-- 
    RELATÃ¯Â¿Â½RIO DE CLIENTES
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php require_once('config.php'); ?>

<!DOCTYPE html>
<?php
if (isset($_SESSION['MM_Usuario'])) {
    echo '<html lang="pt-BR">'; // Fix versÃ¯Â¿Â½o antiga MK-AUTH
} else {
    echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
}
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>MK - AUTH :: <?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'};  ?></title>

    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />

    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/css.css" rel="stylesheet" type="text/css" />


    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>


</head>

<body>

    <?php include('../../topo.php'); ?>

    <ul class="nav nav-tabs justify-content-center">
        <li class="nav-item">
            <a class="nav-link" href="index.php"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="cli_planos.php">PLANOS</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">REL CLI FILTRO</a>
        </li>
    </ul>

    <?php
    //INICIO PERMISSAO
    if ($acesso_permitido) {
    ?>
        <?php

        $mes = isset($_GET['mes']) == "" ? date('m') : $_GET['mes'];
        $ano = isset($_GET['ano']) == "" ? date('Y') : $_GET['ano'];

        $filtro_selecionado = isset($_GET['filtro']) == "" ? "cidade" : $_GET['filtro'];

        $teste = isset($_GET[$filtro_selecionado]) == "" ? "%" : $_GET[$filtro_selecionado];

        $lista_mes = array(
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

        $filtragem = array(
            "cidade" => "Cidade",
            "bairro" => "Bairro",
            "estado" => "Estado",
            "plano" => "Plano",
            "vendedor" => "Vendedor",
            "tecnico" => "Técnico"
        );

        $lista_filtro['%'] = "Todos";

        $query_filtros = mysqli_query($link, "SELECT DISTINCT $filtro_selecionado FROM sis_cliente");
        while ($c = mysqli_fetch_array($query_filtros)) {

            $lista_filtro[$c["$filtro_selecionado"]] = $c["$filtro_selecionado"];
        }

        if (!$query_filtros) {
            echo mysqli_error($link);
        }
        ?>
        <form action="" method="get" id="formulario">
            <div class="row g-3 justify-content-center">
                <div class="col-4 col-md-auto">
                    <div class="form-floating">
                        <select class="form-select" name="mes" id="mes">
                            <?php
                            foreach ($lista_mes as $key => $value) {
                                $selected = ($mes == $key) ? "selected=\"selected\"" : null;
                                echo "<option value=\"$key\" $selected >$value</option>";
                            }
                            ?>
                        </select>
                        <label for="mes">Mês</label>
                    </div>
                </div>

                <div class="col-4 col-md-auto">
                    <div class="form-floating">
                        <input class="form-control" type="number" name="ano" id="ano" value="<?php echo $ano ?>" min="2000" max="2100" />
                        <label for="ano">Ano</label>
                    </div>
                </div>

                <div class="col-4 col-md-auto">
                    <div class="form-floating">
                        <select class="form-select" name="filtro" id="filtro">
                            <?php
                            foreach ($filtragem as $key => $value) {
                                $selected = ($filtro_selecionado == $key) ? "selected=\"selected\"" : null;
                                echo "<option value=\"$key\" $selected >$value</option>";
                            }
                            ?>
                        </select>
                        <label for="filtro">Filtro</label>
                    </div>
                </div>

                <div class="col-10 col-md-auto">
                    <div class="form-floating">
                        <select class="form-select" name="<?php echo $filtro_selecionado; ?>" id="<?php echo $filtro_selecionado; ?>">
                            <?php
                            foreach ($lista_filtro as $key => $value) {
                                $selected = ($teste == $key) ? "selected=\"selected\"" : null;
                                echo "<option value=\"$key\" $selected >$value</option>";
                            }
                            ?>
                        </select>
                        <label for="<?php echo $filtro_selecionado; ?>"><?php echo $filtragem[$filtro_selecionado]; ?></label>
                    </div>
                </div>

                <div class="col-2 col-md-auto d-grid">
                    <input class='btn btn-primary' type="submit" name="submit" value="OK" id="btn_buscar" />
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-12 col-md-9">
                <div id="graf_1"></div>

            </div>
            <div class="col-12 col-md-3">
                <div id="graf_2"></div>

            </div>
        </div>
        <?php

        $query_nome_cli_add = mysqli_query($link, "SELECT ad.username, ad.login, c.nome, c.$filtro_selecionado FROM sis_adicional ad LEFT JOIN sis_cliente c ON ad.login = c.login");
        while ($row100 = mysqli_fetch_array($query_nome_cli_add)) {
            $filtro_selecionado_db = $row100["$filtro_selecionado"];
            $username = $row100['username'];
            $cli_add_nome[$username] = $row100['nome'];
            $cli_add_[$filtro_selecionado_db][$username] = $username;
        }

        //debug($cli_add_);

        $c = 0;

        //debug($lista_filtro);

        //echo $filtro_selecionado;

        //echo $teste;
        if ($teste != "%") {
            unset($lista_filtro);
            $lista_filtro["$teste"] = $teste;
        } else {
            unset($lista_filtro['%']);
        }

        //debug($lista_filtro);


        foreach ($lista_filtro as $key => $filtro) {

            //echo $filtro;

            $c++;
            if ($filtro == "") {
                continue;
            }
        ?>

            <table class="table table-sm <?php echo "$filtro_selecionado-$c"; ?>">
                <thead>
                    <tr>
                        <th class="bg-primary lead text-center text-light" scope="col" colspan="3"><?php echo "<p class='m-0 p-0'>$filtro</p>"; ?></th>
                    </tr>
                    <tr class='bg-secondary text-center fw-bold'>
                        <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Data: </p>"; ?></td>
                        <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Cliente: </p>"; ?></td>
                        <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Tipo: </p>"; ?></td>

                    </tr>
                </thead>
                <tbody>

                    <?php


                    $result2 = mysqli_query($link, "SELECT nome, data_ins, uuid_cliente FROM sis_cliente WHERE data_ins LIKE '%$ano-$mes%' AND $filtro_selecionado LIKE '$filtro' ORDER BY nome");
                    //$result2 = mysql_query($link, "SELECT registro, data FROM sis_logs WHERE registro LIKE 'Adicionou o cliente: %' AND data LIKE '%$mes/$ano%' ORDER BY registro");            
                    if (!$result2) {
                        echo "Invalid query<br/>";
                        echo mysqli_error($link);
                    } else {
                        $num_novos[$filtro] = mysqli_num_rows($result2);

                        echo "";

                        while ($row = mysqli_fetch_array($result2)) {
                            /*$data_cli_add = $row['data'];

                    echo "PADRAO: $data_cli_add</br>";

                    $data_cli_add = explode(" ", $data_cli_add);
                    echo "<pre>";
                    print_r($data_cli_add);
                    echo "</pre>";

                    $data_cli_add = explode("/", $data_cli_add[0]);    
                    echo "<pre>";
                    print_r($data_cli_add);
                    echo "</pre>";

                    $data_cli_add = implode("-", array_reverse($data_cli_add));

                    echo "ALTERADO: $data_cli_add</br>";*/

                            $nome_cli_add = $row['nome'];

                            //Converter UTF-8 do Banco de Dados
                            /*$nome_cli_add = html_entity_decode(htmlentities($nome_cli_add, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');*/

                            $data_cli_add = $row['data_ins'];
                            $data_cli_add = date('d/m/Y - H:i:s', strtotime($data_cli_add));
                            $uuid_cliente_add = $row['uuid_cliente'];


                            echo "
                    
                    <tr class='table-success'>
                    <th scope='row'>$data_cli_add</th>
                    <td><a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_add' target='_blank' title='VER CLIENTE: $nome_cli_add \nData Instalação: $data_cli_add'>
                    $nome_cli_add</a><br/></td>
                    <td>Instalação</td>
                  </tr>
                    
                    
                    ";
                        }
                    }
                    ?>



                    <?php
                    $query_add_ins = mysqli_query($link, "SELECT data, registro FROM sis_logs WHERE registro LIKE 'inseriu o adicional:%' AND data LIKE '%$mes/$ano%' ORDER BY registro");

                    if (!$query_add_ins) {
                        echo "Invalid query<br/>";
                        echo mysqli_error($link);
                    } else {
                        $num_add_ins = 0;


                        while ($row4 = mysqli_fetch_array($query_add_ins)) {
                            $data_adicional_add = $row4['data'];
                            $log = $row4['registro'];
                            //$log_pos_1 = stripos($log, "inseriu o adicional: ");
                            $log_pos_2 = stripos($log, " -");

                            $login_adicional = substr($log, 21, $log_pos_2 - 21);

                            if ($cli_add_[$filtro][$login_adicional]) {
                                echo "
                            
                            <tr>
                            <th scope='row'>$data_adicional_add</th>
                            <td>
                            $login_adicional [$cli_add_nome[$login_adicional]]
                            </td>
                            <td>Instalação Adicional</td>
                            </tr>
                            
                            ";
                                $num_add_ins++;
                            }
                        }
                    }

                    $num_novos[$filtro] += $num_add_ins; // Adicionais inseridos

                    ?>

                    <?php
                    $result3 = mysqli_query($link, "SELECT login FROM sis_logs WHERE registro LIKE '%ativou o cliente%' AND registro NOT LIKE '%desativou%' AND data LIKE '%$mes/$ano%' ORDER BY login");

                    if (!$result3) {
                        echo "Invalid query<br/>";
                        echo mysqli_error($link);
                    } else {
                        $num_reativados[$filtro] = 0;


                        while ($row = mysqli_fetch_array($result3)) {
                            // echo "<pre>", print_r($row), "</pre>";

                            $login_cliente = $row['login'];
                            //$id_cli_ativado = $row['id'];

                            //echo $data_cli_reativado; 

                            $result4 = mysqli_query($link, "SELECT nome,uuid_cliente FROM sis_cliente WHERE login LIKE '$login_cliente' AND $filtro_selecionado LIKE '$filtro'");

                            if ($row3 = mysqli_fetch_array($result4)) {
                                $nome_cli_reativado = $row3['nome'];

                                //Converter UTF-8 do Banco de Dados
                                /*$nome_cli_reativado = html_entity_decode(htmlentities($nome_cli_reativado, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');*/

                                $uuid_cliente_reativado = $row3['uuid_cliente'];

                                echo "
                            
                            <tr>
                            <th scope='row'></th>
                            <td>
                            <a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_reativado' target='_blank' title='VER CLIENTE: $nome_cli_reativado'>$nome_cli_reativado</a>
                            </td>
                            <td>Reativação</td>
                            </tr>
                    
                            
                            ";

                                $num_reativados[$filtro]++;
                            }
                        }

                        echo "";
                    }






                    ?>

                    <?php
                    if ($cli_del_cancelalado) {
                        $result = mysqli_query($link, "SELECT DISTINCT login FROM sis_logs WHERE registro LIKE 'deletou o cliente % do sistema%' AND data LIKE '%$mes/$ano%' ORDER BY login");
                    } else {
                        $result = mysqli_query($link, "SELECT DISTINCT login FROM sis_logs WHERE
                (registro LIKE '%desativou o cliente%' AND data LIKE '%$mes/$ano%') 
                OR 
                (registro LIKE '%desativou pelo motivo%' AND data LIKE '%$mes/$ano%') 
                ORDER BY login");
                    }


                    if (!$result) {
                        echo "Invalid query<br/>";
                        echo mysqli_error($link);
                    } else {
                        $num_desativados[$filtro] = 0;



                        while ($row = mysqli_fetch_array($result)) {
                            $login_cliente2 = $row[0];

                            $result5 = mysqli_query($link, "SELECT nome, uuid_cliente, data_desativacao FROM sis_cliente WHERE login LIKE '$login_cliente2' AND $filtro_selecionado LIKE '$filtro'");

                            while ($row2 = mysqli_fetch_array($result5)) {
                                $nome_cli_desativado = $row2['nome'];

                                //Converter UTF-8 do Banco de Dados
                                /*$nome_cli_desativado = html_entity_decode(htmlentities($nome_cli_desativado, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');*/

                                $uuid_cliente_desativado = $row2['uuid_cliente'];
                                $data_cli_desativado = $row2['data_desativacao'];
                                if ($data_cli_desativado != '') {
                                    $data_cli_desativado = date('d/m/Y - H:i:s', strtotime($data_cli_desativado));
                                }

                                echo "
                            
                            <tr class='table-danger'>
                            <th scope='row'>$data_cli_desativado</th>
                            <td>
                            <a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_desativado' target='_blank' title='VER CLIENTE: $nome_cli_desativado \nData Cancelamento: $data_cli_desativado'>$nome_cli_desativado</a>
                            </td>
                            <td>Cancelamento</td>
                            </tr>
                            
                            ";

                                $num_desativados[$filtro]++;
                            }
                        }

                        echo "";
                    }

                    ?>

                    <tr class="bg-secondary text-center text-light">

                        <?php echo "
                                <td><b>Total Clientes Instalados = $num_novos[$filtro]</b></td>
                                <td><b>Total Clientes Reativados = $num_reativados[$filtro]</b></td>
                                <td><b>Total Clientes Cancelados = $num_desativados[$filtro]</b></td>
                                "; ?>

                    </tr>

                    <?php


                    if ($num_novos[$filtro] == 0 && $num_reativados[$filtro] == 0 && $num_desativados[$filtro] == 0) {
                    ?>
                        <script>
                            $(document).ready(function() {
                                $(".<?php echo "$filtro_selecionado-$c"; ?>").addClass("d-none");
                            });
                        </script>
                    <?php
                    }
                    /*$query_adicional_del = mysqli_query($link, "SELECT registro FROM sis_logs WHERE registro LIKE 'deletou o adicional%' AND data LIKE '%$mes/$ano%' ORDER BY registro");
            
            if (!$query_adicional_del) {
                echo "Invalid query<br/>";
                echo mysqli_error($link);
            } else {
                $num_adicional_del = mysqli_num_rows($query_adicional_del);

                if ($num_adicional_del > 0){
                    echo "</br><b>Adicionais Cancelados = $num_adicional_del</b><br>";

                    while($row5 = mysqli_fetch_array($query_adicional_del))
                    {
                        $log = $row5['registro'];
                        //$log_pos_1 = stripos($log, "deletou o adicional ");
                        $log_pos_2 = stripos($log, " -");

                        $login_adicional = substr($log, 20, $log_pos_2 - 20);


                        
                        //echo "$cli_add_$filtro_selecionado[$filtro][$login_adicional]<br>";
                        
                                                echo "
            
        <tr class='table-danger'>
        <th scope='row'></th>
        <td>
        $login_adicional
        </td>
        <td>Cancelamento Adicional</td>
        <td></td>
        </tr>
        
        ";

                    }
                }
            }*/

                    $num_desativados[$filtro] += $num_adicional_del; // Adicionais removidos

                    $soma_clientes[$filtro] = $num_novos[$filtro] + $num_reativados[$filtro];
                    $saldo_clientes[$filtro] = $soma_clientes[$filtro] - $num_desativados[$filtro];


                    $check_exb[$filtro] = $soma_clientes[$filtro] + $num_desativados[$filtro];

                    /*if($check_exb[$filtro] == 0){
                            $num_desativados[$filtro] = 0;
                            $soma_clientes[$filtro] = 0;
                            $saldo_clientes[$filtro]= 0;
                        }*/
                    ?>



                    <?php

                    //debug($num_novos);

                    $mes = str_replace("%", "Ano", $mes);
                    //echo "<p align='center'>BalanÃ¯Â¿Â½o $mes / $ano</p><br/>";
                    ?>


                    </div>


                <?php
            } // Fim Foreach 


            mysqli_close($link);

            $tot_soma_clientes = array_sum($soma_clientes);
            $tot_num_desativados = array_sum($num_desativados);

            $tot_saldo_cliente = $tot_soma_clientes - $tot_num_desativados;

            //debug($check_exb);
                ?>

                <script src="js/highcharts.js"></script>
                <script src="js/exporting.js"></script>

                <figure class="highcharts-figure">

                    <p class="highcharts-description">
                        <script>
                            Highcharts.chart('graf_1', {
                                chart: {
                                    type: 'column',
                                },
                                plotOptions: {
                                    column: {
                                        dataLabels: {
                                            enabled: true,
                                            /*formatter: function() {
                                                if (this.x > 0) {
                                                    return this.x;
                                                }
                                            }*/
                                        }
                                    }
                                },
                                title: {
                                    text: '<?php echo "Balanço $mes / $ano"; ?>'
                                },
                                xAxis: {
                                    categories: [
                                        <?php
                                        foreach ($check_exb as $k => $v) {
                                            if ($v != 0)
                                                echo "'$k',";
                                        }
                                        ?>
                                    ]
                                },
                                credits: {
                                    enabled: false
                                },
                                series: [{
                                    name: 'Instalações + Reativações',
                                    data: [<?php
                                            foreach ($soma_clientes as $k => $v) {
                                                if ($check_exb[$k] != 0)
                                                    echo "$v,";
                                            }
                                            ?>],
                                    color: Highcharts.getOptions().colors[2]
                                }, {
                                    name: 'Cancelamentos',
                                    data: [<?php
                                            foreach ($num_desativados as $k => $v) {
                                                if ($check_exb[$k] != 0)
                                                    echo "$v,";
                                            }
                                            ?>],
                                    color: Highcharts.getOptions().colors[8]
                                }, {
                                    name: 'Saldo',
                                    data: [<?php
                                            foreach ($saldo_clientes as $k => $v) {
                                                if ($check_exb[$k] != 0)
                                                    echo "$v,";
                                            }
                                            ?>],
                                    color: Highcharts.getOptions().colors[3]
                                }]
                            });
                        </script>
                    </p>
                </figure>


                <figure class="highcharts-figure">
                    <p class="highcharts-description">
                        <script>
                            Highcharts.chart('graf_2', {
                                chart: {
                                    type: 'column',
                                },
                                plotOptions: {
                                    column: {
                                        dataLabels: {
                                            enabled: true,
                                            /*formatter: function() {
                                                if (this.y > 0) {
                                                    return this.y;
                                                }
                                            }*/
                                        }
                                    }
                                },
                                title: {
                                    text: '<?php echo "Balanço $mes / $ano"; ?>'
                                },
                                xAxis: {
                                    categories: ['Balanço']
                                },
                                credits: {
                                    enabled: false
                                },
                                series: [{
                                    name: 'Instalações + Reativações',
                                    data: [<?php
                                            echo $tot_soma_clientes;
                                            ?>],
                                    color: Highcharts.getOptions().colors[2]
                                }, {
                                    name: 'Cancelamentos',
                                    data: [<?php
                                            echo $tot_num_desativados;
                                            ?>],
                                    color: Highcharts.getOptions().colors[8]
                                }, {
                                    name: 'Saldo',
                                    data: [<?php
                                            echo $tot_saldo_cliente;
                                            ?>],
                                    color: Highcharts.getOptions().colors[3]
                                }]
                            });
                        </script>
                    </p>
                </figure>

                </tbody>
            </table>

        <?php

    } //FIM PERMISSAO
        ?>




        <?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>

</body>

</html>