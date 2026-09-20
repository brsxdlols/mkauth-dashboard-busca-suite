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

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/css.css" rel="stylesheet" type="text/css" />

</head>

<body>




    <?php include('../../topo.php'); ?>

    <ul class="nav nav-tabs justify-content-center">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="cli_planos.php">PLANOS</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="cli_filtro.php">REL CLI FILTRO</a>
        </li>
    </ul>


    <?php
    //INICIO PERMISSAO
    if ($acesso_permitido) {
    ?>
        <?php
        $mes = isset($_GET['mes']) == "" ? date('m') : $_GET['mes'];
        $ano = isset($_GET['ano']) == "" ? date('Y') : $_GET['ano'];

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
            "%" => "TODOS",
        );
        ?>

        <form action="" method="get" id="formulario">
            <div class="row g-3 justify-content-center">
                <div class="col-auto">
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

                <div class="col-auto">
                    <div class="form-floating">
                        <input class="form-control" type="number" name="ano" id="ano" value="<?php echo $ano ?>" min="2000" max="2100" />
                        <label for="ano">Ano</label>
                    </div>
                </div>
                <div class="col-auto d-grid">
                    <input class='btn btn-primary' type="submit" name="submit" value="OK" id="btn_buscar" />
                </div>
            </div>
        </form>

            <div class="row">


                <div class="col-12 col-md-4">

                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="bg-success lead text-center text-light" scope="col" colspan="3"><?php echo "<p class='m-0 p-0'>Balanço Ativações $mes / $ano</p>"; ?></th>
                            </tr>
                            <tr class='bg-secondary text-center fw-bold'>
                                <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Data: </p>"; ?></td>
                                <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Cliente: </p>"; ?></td>
                                <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Tipo: </p>"; ?></td>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $query_nome_cli_add = mysqli_query($link, "SELECT ad.username, ad.login, c.nome FROM sis_adicional ad LEFT JOIN sis_cliente c ON ad.login = c.login");
                            while ($row100 = mysqli_fetch_array($query_nome_cli_add)) {
                                $cli_add_nome[$row100['username']] = $row100['nome'];
                            }
                            $result2 = mysqli_query($link, "SELECT nome, data_ins, uuid_cliente FROM sis_cliente WHERE data_ins LIKE '%$ano-$mes%' ORDER BY nome");
                            if (!$result2) {
                                echo "Invalid query<br/>";
                                echo mysqli_error($link);
                            } else {
                                $num_novos = mysqli_num_rows($result2);

                                //echo "<b>Total Clientes Instalados = $num_novos</b></br></br>";

                                while ($row = mysqli_fetch_array($result2)) {
                                    $nome_cli_add = $row['nome'];
                                    $data_cli_add = $row['data_ins'];
                                    $data_cli_add = date('d/m/Y', strtotime($data_cli_add));
                                    $uuid_cliente_add = $row['uuid_cliente'];


                                    /*echo "<a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_add' target='_blank' title='VER CLIENTE: $nome_cli_add \nData Instalação: $data_cli_add'>
                    $nome_cli_add</a><br/>
                    ";*/

                                    $reg_clientes[$data_cli_add] = "
                    <tr class='table-success'>
                    <th scope='row'>$data_cli_add</th>
                    <td><a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_add' target='_blank' title='VER CLIENTE: $nome_cli_add \nData Instalação: $data_cli_add'>
                    $nome_cli_add</a><br/></td>
                    <td>Instalação</td>
                  </tr>";
                                }
                            }

                            $query_add_ins = mysqli_query($link, "SELECT data, registro FROM sis_logs WHERE registro LIKE 'inseriu o adicional:%' AND data LIKE '%$mes/$ano%' ORDER BY registro");

                            if (!$query_add_ins) {
                                echo "Invalid query<br/>";
                                echo mysqli_error($link);
                            } else {
                                $num_add_ins = mysqli_num_rows($query_add_ins);

                                if ($num_add_ins > 0) {
                                    //echo "</br><b>Adicionais Instalados = $num_add_ins</b><br>";

                                    while ($row4 = mysqli_fetch_array($query_add_ins)) {
                                        $data_adicional_add = $row4['data'];
                                        //$data_adicional_add = strtotime('d/m/Y', $data_adicional_add);
                                        
                                        $log = $row4['registro'];
                                        //$log_pos_1 = stripos($log, "inseriu o adicional: ");
                                        $log_pos_2 = stripos($log, " -");

                                        $login_adicional = substr($log, 21, $log_pos_2 - 21);


                                        //echo "$login_adicional [$cli_add_nome[$login_adicional]]<br>";

                                        $reg_clientes[$data_adicional_add] = "    
                        <tr>
                        <th scope='row'>$data_adicional_add</th>
                        <td>
                        $login_adicional [$cli_add_nome[$login_adicional]]
                        </td>
                        <td>Instalação Adicional</td>
                        </tr>
                        ";
                                    }
                                }
                            }

                            $num_novos += $num_add_ins; // Adicionais inseridos

                            ?>

                            <?php
                            $result3 = mysqli_query($link, "SELECT DISTINCT login FROM sis_logs WHERE registro LIKE '%ativou o cliente%' AND registro NOT LIKE '%desativou%' AND data LIKE '%$mes/$ano%' ORDER BY login");

                            if (!$result3) {
                                echo "Invalid query<br/>";
                                echo mysqli_error($link);
                            } else {
                                $num_reativados = mysqli_num_rows($result3);

                                //echo "<b>Total Clientes Reativados = $num_reativados</b></br></br>";

                                while ($row = mysqli_fetch_array($result3)) {
                                    $login_cliente = $row['login'];
                                    $result4 = mysqli_query($link, "SELECT nome,uuid_cliente FROM sis_cliente WHERE login LIKE '$login_cliente'");

                                    if ($row3 = mysqli_fetch_array($result4)) {
                                        $nome_cli_reativado = $row3['nome'];

                                        $uuid_cliente_reativado = $row3['uuid_cliente'];

                                        /*echo "<a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_reativado' target='_blank' title='VER CLIENTE: $nome_cli_reativado'>$nome_cli_reativado</a></br>";*/

                                        echo "
                            
                        <tr>
                        <th scope='row'></th>
                        <td>
                        <a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_reativado' target='_blank' title='VER CLIENTE: $nome_cli_reativado'>$nome_cli_reativado</a>
                        </td>
                        <td>Reativação</td>
                        </tr>
                
                        
                        ";
                                    }
                                }
                            }


                            ?>

                            <tr class="bg-secondary text-center text-light">

                                <?php echo "
<td><b>Total Clientes Instalados = $num_novos</b></td>
<td><b>Total Clientes Reativados = $num_reativados</b></td>
<td></td>
"; ?>

                            </tr>

                        </tbody>
                    </table>
                </div>

                <div class="col-12 col-md-4">

                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="bg-danger lead text-center text-light" scope="col" colspan="3"><?php echo "<p class='m-0 p-0'>Balanço Cancelamentos $mes / $ano <img class='exibir icon_sm align-middle' src='img/icon_ajuda.png'/></p>"; ?></th>
                            </tr>
                            <tr class='bg-secondary text-center fw-bold'>
                                <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Data: </p>"; ?></td>
                                <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Cliente: </p>"; ?></td>
                                <td class="text-light" scope="col"><?php echo "<p class='m-0 p-0'>Tipo: </p>"; ?></td>

                            </tr>
                        </thead>
                        <tbody>
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
                                $num_desativados = mysqli_num_rows($result);

                                if ($num_desativados == 0 && !$cli_del_cancelalado) {
                                    $query_cli_desativado = mysqli_query($link, "SELECT uuid_cliente, nome, login, data_desativacao FROM sis_cliente WHERE data_desativacao LIKE '$ano-$mes%' ORDER BY nome");

                                    if (!$query_cli_desativado) {
                                        echo mysqli_error($link);
                                    }
                                    $num_desativados = mysqli_num_rows($query_cli_desativado);

                                    //echo "<b>Total Clientes Cancelados = $num_desativados</b></br></br>";

                                    while ($row2 = mysqli_fetch_array($query_cli_desativado)) {
                                        $nome_cli_desativado = $row2['nome'];
                                        $login_cli_desativado = $row2['login'];

                                        $motivo_query = mysqli_query($link, "SELECT registro FROM sis_logs WHERE registro LIKE '%desativou pelo motivo%' AND data LIKE '%$mes/$ano%' AND login LIKE '$login_cli_desativado' ORDER BY login");

                                        $motivo_cancelamento = "";
                                        if ($r = mysqli_fetch_array($motivo_query)) {
                                            $registro = $r['registro'];

                                            /*$tot_letras = strlen($registro);
                                            
                                            $log_motivo = stripos($registro, "motivo: ");
                                            $log_motivo2 = stripos($registro, " - IP");

                                            $motivo_cancelamento = substr($registro, $log_motivo + 8);*/
                                            $motivo_cancelamento = $registro;
                                        } else {
                                            $motivo_cancelamento = "Desativado Automaticamente";
                                        }

                                        $uuid_cliente_desativado = $row2['uuid_cliente'];
                                        $data_cli_desativado = $row2['data_desativacao'];
                                        if ($data_cli_desativado != '') {
                                            $data_cli_desativado = date('d/m/Y - H:i:s', strtotime($data_cli_desativado));
                                        }

                                        /*echo "<a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_desativado' target='_blank' title='VER CLIENTE: $nome_cli_desativado \nData Cancelamento: $data_cli_desativado'>$nome_cli_desativado</a></br>";*/


                                        echo "
                            
                        <tr class='table-danger'>
                        <th scope='row'>$data_cli_desativado</th>
                        <td>
                        <a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_desativado' target='_blank' title='VER CLIENTE: $nome_cli_desativado \nData Cancelamento: $data_cli_desativado'>$nome_cli_desativado</a>
                        </td>
                        <td>Cancelamento</td>
                        
                        </tr>
                        <tr class='motivo hide'>
                        <td colspan='3'>LOG: $motivo_cancelamento</td>
                        </tr>
                        ";
                                    }
                                } else {
                                    //echo "<b>Total Clientes Cancelados = $num_desativados</b></br></br>";

                                    while ($row = mysqli_fetch_array($result)) {
                                        $login_cliente2 = $row[0];

                                        $result5 = mysqli_query($link, "SELECT nome, login, uuid_cliente, data_desativacao FROM sis_cliente WHERE login LIKE '$login_cliente2'");

                                        while ($row2 = mysqli_fetch_array($result5)) {
                                            $nome_cli_desativado = $row2['nome'];

                                            $login_cli_desativado = $row2['login'];

                                            $motivo_query = mysqli_query($link, "SELECT registro FROM sis_logs WHERE registro LIKE '%desativou pelo motivo%' AND data LIKE '%$mes/$ano%' AND login LIKE '$login_cli_desativado' ORDER BY login");

                                            if (!$motivo_query) {
                                                echo mysqli_error($link);
                                            }

                                            $motivo_cancelamento = "";
                                            if ($r = mysqli_fetch_array($motivo_query)) {
                                                $registro = $r['registro'];

                                                /*$tot_letras = strlen($registro);
                                                
                                                $log_motivo = stripos($registro, "motivo: ");
                                                $log_motivo2 = stripos($registro, " - IP");

                                                $motivo_cancelamento = substr($registro, $log_motivo + 8);*/
                                                $motivo_cancelamento = $registro;
                                            } else {
                                                $motivo_cancelamento = "Desativado Automaticamente";
                                            }

                                            //Converter UTF-8 do Banco de Dados
                                            /*$nome_cli_desativado = html_entity_decode(htmlentities($nome_cli_desativado, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');*/

                                            $uuid_cliente_desativado = $row2['uuid_cliente'];
                                            $data_cli_desativado = $row2['data_desativacao'];
                                            if ($data_cli_desativado != '') {
                                                $data_cli_desativado = date('d/m/Y - H:i:s', strtotime($data_cli_desativado));
                                            }

                                            /*echo "<a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_desativado' target='_blank' title='VER CLIENTE: $nome_cli_desativado \nData Cancelamento: $data_cli_desativado'>$nome_cli_desativado</a></br>";*/

                                            echo "
                            
                            <tr class='table-danger'>
                            <th scope='row'>$data_cli_desativado</th>
                            <td>
                            <a href='../../cliente_det$ext_mk?uuid=$uuid_cliente_desativado' target='_blank' title='VER CLIENTE: $nome_cli_desativado \nData Cancelamento: $data_cli_desativado'>$nome_cli_desativado</a>
                            </td>
                            <td>Cancelamento</td>

                            </tr>

                            </tr>
                            <tr class='motivo hide'>
                            <td colspan='3'>LOG: $motivo_cancelamento</td>
                            </tr>
                            
                            ";
                                        }
                                    }
                                }
                            }

                            ?>

                            <?php
                            $query_adicional_del = mysqli_query($link, "SELECT registro FROM sis_logs WHERE registro LIKE 'deletou o adicional%' AND data LIKE '%$mes/$ano%' ORDER BY registro");

                            if (!$query_adicional_del) {
                                echo "Invalid query<br/>";
                                echo mysqli_error($link);
                            } else {
                                $num_adicional_del = mysqli_num_rows($query_adicional_del);

                                if ($num_adicional_del > 0) {
                                    //echo "</br><b>Adicionais Cancelados = $num_adicional_del</b><br>";

                                    while ($row5 = mysqli_fetch_array($query_adicional_del)) {
                                        $log = $row5['registro'];
                                        //$log_pos_1 = stripos($log, "deletou o adicional ");
                                        $log_pos_2 = stripos($log, " -");

                                        $login_adicional = substr($log, 20, $log_pos_2 - 20);

                                        //echo "$login_adicional<br>";


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
                            }

                            $num_desativados += $num_adicional_del; // Adicionais removidos

                            $soma_clientes = $num_novos + $num_reativados;
                            $saldo_clientes = $soma_clientes - $num_desativados;

                            mysqli_close($link);

                            ?>

                            <tr class="bg-secondary text-center text-light">

                                <?php echo "
    <td colspan='3'><b>Total Clientes Cancelados = $num_desativados</b></td>
    "; ?>

                            </tr>

                        </tbody>
                    </table>
                </div>

                <div class="col-12 col-md-4">
                    <div id="graf_1"></div>
                </div>
            </div>




            <?php
            $mes = str_replace("%", "Ano", $mes);
            //echo "<p align='center'>BalanÃ¯Â¿Â½o $mes / $ano</p><br/>";
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
                                        enabled: true
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
                                name: 'Instalações',
                                data: [<?php echo $num_novos; ?>],
                                color: Highcharts.getOptions().colors[2]
                            }, {
                                name: 'Reativações',
                                data: [<?php echo $num_reativados; ?>],
                                color: Highcharts.getOptions().colors[0]
                            }, {
                                name: 'Cancelamentos',
                                data: [<?php echo $num_desativados; ?>],
                                color: Highcharts.getOptions().colors[8]
                            }, {
                                name: 'Saldo',
                                data: [<?php echo $saldo_clientes; ?>],
                                color: Highcharts.getOptions().colors[3]
                            }]
                        });
                    </script>
                </p>
            </figure>



    <?php
       

    } //FIM PERMISSAO
    ?>

    <script>
        $(".exibir").click(function() {
            $(".motivo").toggle();
        });
    </script>

    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script>

</body>

</html>