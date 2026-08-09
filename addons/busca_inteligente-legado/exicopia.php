<script>
    $(document).ready(function() {
        $('#selectall').click(function(event) { //on click 
            if (this.checked) { // check select status
                $('.login_select').each(function() { //loop through each checkbox
                    this.checked = true; //select all checkboxes with class "checkbox1"               
                });
            } else {
                $('.login_select').each(function() { //loop through each checkbox
                    this.checked = false; //deselect all checkboxes with class "checkbox1"                       
                });
            }
        });
    });
</script>
<?php

//Contagem dos clientes adicionais na pesquisa

$caracters_remove = array("-", "(", ")");

$count_adicional = 0;
while ($get_login = mysqli_fetch_assoc($result)) {
    $login_[] = $get_login['login'];
}

foreach ($login_ as $res) {
    $query_count_adicional = mysqli_query($link, "SELECT username FROM sis_adicional WHERE login LIKE '$res' ORDER BY username");

    if (mysqli_num_rows($query_count_adicional) != 0)
        $count_adicional += mysqli_num_rows($query_count_adicional);
}

$tot_resultados = mysqli_num_rows($result);

// Paginaçço
$limite = mysqli_num_rows($result_limit);
$tot_paginas = $tot_resultados / $registros_por_pagina;

// agora vamos criar os botçes "Anterior e próximo"
$anterior = $pc - 1;
$proximo = $pc + 1;

$busca2 = "";
if (isset($_GET['busca'])) {
    $busca2 = $_GET['busca'];
}
$busca2 = str_replace("+", "%2B", $busca2);

//$url_REF = "?busca=$busca2&organizar=name&num_registros=$registros_por_pagina&pagina=$pc";

$tot_clientes = $tot_resultados + $count_adicional;

if ($acesso_permitido) {
?>
    <b>Resultados Encontrados = <?= $tot_clientes; ?></b>

<?php
}


?>
<div class='row bg-primary text-light text-center'>
    <div class='col-auto no_print'>
        <input type='checkbox' name='selectall' id='selectall' />
    </div>
    <div class='col-auto'> </div>
    <div class='col-3'>

        <?php
        if ($organizar == 'nome') {
        ?>
            <a class="link-light" href='?busca=<?= $busca2; ?>&organizar=nome DESC&num_registros=<?= $registros_por_pagina; ?>&pagina=<?= $pc; ?>'>
                <p class='center'>Nome Completo</p>
            </a>
        <?php
        } else {

        ?>
            <a class="link-light" href='?busca=<?= $busca2; ?>&organizar=nome&num_registros=<?= $registros_por_pagina; ?>&pagina=<?= $pc; ?>'>
                <p class='center'>Nome Completo</p>
            </a>
        <?php
        }

        ?>

    </div>

    <div class='col-3'>
        <?php
        if ($organizar == 'endereco') {
        ?>
            <a class="link-light" href='?busca=<?= $busca2; ?>&organizar=endereco DESC&num_registros=<?= $registros_por_pagina; ?>&pagina=<?= $pc; ?>'>
                <p class='center'>Endereço</p>
            </a>
        <?php
        } else {
        ?>
            <a class="link-light" href='?busca=<?= $busca2; ?>&organizar=endereco&num_registros=<?= $registros_por_pagina; ?>&pagina=<?= $pc; ?>'>
                <p class='center'>Endereço</p>
            </a>
        <?php
        }
        ?>

    </div>
    <div class='col-1'>
        <p class='center'>Contato</p>
    </div>
    <div class='col-3'>
        <p class='center'>Dados</p>
    </div>
</div>

<?php
if ($check_online == 'mkauth') {
    // INFO DE CLIENTES ONLINE COM MKAUTH
    $query_cliente_on = mysqli_query($link, "SELECT username, nasipaddress, framedipaddress FROM radacct WHERE acctstoptime IS NULL");

    while ($row2 = mysqli_fetch_assoc($query_cliente_on)) {
        $username_on[trim(strtolower($row2['username']))] = strtolower($row2['username']);

        $nas_ip[trim(strtolower($row2['username']))] = $row2['nasipaddress'];
        $ip_conn[trim(strtolower($row2['username']))] = $row2['framedipaddress'];
    }
} else {
    // INFO DE CLIENTES ONLINE COM MIKROTIK VIA API

    require('api/routeros_api.class.php');

    $query_nas = mysqli_query($link, "SELECT * FROM nas");

    while ($nas = mysqli_fetch_assoc($query_nas)) {
        $ramal_ip[] = $nas['nasname'];

        $ramal_ip2 = $nas['nasname'];
        $login_router[$ramal_ip2] = $nas['userapi'] == '' ? 'mkauth' : $nas['userapi'];
        $pass_router[$ramal_ip2]  = $nas['senha'];
    }

    $API = new RouterosAPI();

    $API->debug = false;

    foreach ($ramal_ip as $key => $ip) {
        if ($API->connect($ip, $login_router[$ip], $pass_router[$ip])) {
            $busca_cliente = $API->comm('/ppp/active/print', false);

            /*echo "<pre>";
                print_r($busca_cliente);
                echo "</pre>";*/
            foreach ($busca_cliente as $key => $value) {
                //$ip_conn[$value['name']] = $value['address'];

                $username_on[trim(strtolower($value['name']))] = $value['name'];

                $nas_ip[trim(strtolower($value['name']))] = $ip;
                $ip_conn[trim(strtolower($value['name']))] = $value['address'];
            }

            $API->disconnect();
        }
    }
}

// debug($username_on);

// Titulos Vencidos
// $now = date('Y-m-d');

$qTitulos = mysqli_query($link, "SELECT l.login FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login = c.login WHERE l.status NOT LIKE 'pago' AND l.deltitulo = 0 AND l.datavenc <= '$now' AND c.cli_ativado = 's'");
while ($row = mysqli_fetch_assoc($qTitulos)) {
    $tit[$row['login']] += 1;
}

// arsort($tit);
// debug($tit);

?>

<form method='POST' action='' target='_blank'>

    <?php

    $cont = 1;

    while ($row = mysqli_fetch_assoc($result_limit)) {
        $nome_cliente = $row['nome'];

        $cpf_cnpj_cliente = $row['cpf_cnpj'];
        $login_cliente = $row['login'];
        $senha_cliente = $row['senha'];
        $end_cliente = $row['endereco'];


        $numero_casa = $row['numero'];
        $bairro_cliente = $row['bairro'];
        $complemento_cliente = isset($row['complemento']) == '' ? '' : $row['complemento'] . "<br>";

        $cidade_cliente = $row['cidade'];


        $uf_cliente = $row['estado'];
        $plano_cliente = $row['plano'];
        $venc_cliente = $row['venc'];

        $fones_cliente = $row['fone'];
        $fones_cliente = str_replace($caracters_remove, '', $fones_cliente);

        $fones_cliente2 = $row['celular'];
        $fones_cliente2 = str_replace($caracters_remove, '', $fones_cliente2);

        $fones_cliente3 = $row['celular2'];
        $fones_cliente3 = str_replace($caracters_remove, '', $fones_cliente3);


        $titulos_vencidos = $row['tit_vencidos'];
        $bloqueado = $row['bloqueado'];
        $data_bloq = $row['data_bloq'];
        $observacao = $row['observacao'];
        $obs_data = $row['rem_obs'];
        $cli_ativado = $row['cli_ativado'];
        $data_desativado = $row['data_desativacao'];
        $comodato = $row['comodato'];
        $equipamento = $row['equipamento'];
        $data_cad = $row['data_ins'];

        $dataCon = $row['cadastro'];

        $diaCad = substr($dataCon, 0, 2);
        $mesCad = substr($dataCon, 3, 2);
        $anoCad = substr($dataCon, 6, 4);

        $dataContrato = "$anoCad-$mesCad-$diaCad";

        $dataRenovacao = date('Y-m-d', strtotime('+1 year', strtotime($dataContrato)));

        $last_update = $row['last_update'];
        $uuid_cliente = $row['uuid_cliente'];
        $switch_cliente = $row['switch'];
        $loc_cliente = $row['coordenadas'];
        $cli_email = $row['email'];
        $cli_parc_abertas = $row['parc_abertas'];
        $cli_tit_abertos = $row['tit_abertos'];

        $caixa_herm = $row['caixa_herm'];
        $porta_splitter = $row['porta_splitter'];

        $color = $bloqueado == "sim" ? "bloqueado" : "observacao";

        // Score Integration kKKKKkkkkKKKKK
        $cliente_tempo = 0;
        $score = $score_base;
        // echo $score.", ";

        $cliente_tempo = (date('Y') - $anoCad) * $ano_fidelizacao;

        /*if ($cliente_tempo > $ano_fidelizacao * 5) {
        $cliente_tempo = $ano_fidelizacao * 5;
    }*/

        $cliente_tempo = $cliente_tempo > $ano_fidelizacao * 5 ? $cliente_tempo = $ano_fidelizacao * 5 : $cliente_tempo;

        $score += $cliente_tempo;

        $query_titulos = mysqli_query($link, "SELECT datapag, datavenc FROM sis_lanc WHERE login LIKE '$login_cliente' AND deltitulo = '0' AND datapag IS NOT NULL ORDER BY id DESC LIMIT 12");

        $scorePay = 0;

        while ($row2 = mysqli_fetch_assoc($query_titulos)) {

            $data_pagamento = $row2['datapag'];

            // Data de vencimento com +1 dia para evitar problemas com pagamentos via API
            $data_vencimento = date('Y/m/d', strtotime("+1 day", strtotime($row2['datavenc'])));
            $data_vencimento = strtotime($data_vencimento);

            $data_pagamento = date('Y/m/d', strtotime($row2['datapag']));
            $data_pagamento = strtotime($data_pagamento);

            if ($data_vencimento < $data_pagamento) {
                $scorePay += $tit_apos_venc;
            } else if ($data_vencimento > $data_pagamento) {
                $scorePay += $tit_antes_venc;
            } else {
                $scorePay += $tit_dia_venc;
            }
        }

        // echo $scorePay;

        switch ($titulos_vencidos) {
            case 0:
                $score += $scorePay + $tit_venc_0;
                break;
            case 1:
                $score += $scorePay + $tit_venc_1;
                break;
            case 2:
                $score += $scorePay + $tit_venc_2;
                break;
            case 3:
                $score += $scorePay + $tit_venc_3;
                break;
            case 4:
                $score += $scorePay + $tit_venc_4;
                break;
            case 5:
                $score += $scorePay + $tit_venc_5;
                break;
            case 6:
                $score += $scorePay + $tit_venc_6;
                break;
            default:
                $score += $scorePay + $tit_venc_7;
                break;
        }

        // echo $score;

        if ($score < ($max_score / 3)) {
            $showScore = "<span class='alert_red'>$score</span>";
        } else if ($score >= ($max_score / 3) && $score < ($max_score / 2.5)) {
            $showScore = "<span class='alert_orange_red'>$score</span>";
        } else if ($score >= ($max_score / 2.5) && $score < ($max_score / 2)) {
            $showScore = "<span class='alert_orange'>$score</span>";
        } else if ($score >= ($max_score / 2) && $score < ($max_score / 1.4)) {
            $showScore = "<span class='alert_yellow'>$score</span>";
        } else if ($score >= ($max_score / 1.4) && $score < ($max_score / 1.05)) {
            $showScore = "<span class='alert_green'>$score</span>";
        } else {
            $showScore = "<span class='alert_green2'>$score
        <img src='img/icon_trofeu_2.png' title='Melhores Clientes' class='icon'/>
        </span>";
        }


        // The end implementation for Score

        $bgColor = $cont % 2 == 0 ? "bg-body-secondary" : "bg-light";
        //echo $bgColor;

    ?>

        <div class='row row-cols-1 row-cols-sm-2 row-cols-md-4 small <?= $bgColor; ?>'>

            <div class='col-1 col-sm-1 col-md-1'>
                <input type='checkbox' name='login[]' class='login_select' value='<?= $login_cliente; ?>'>
            </div>

            <?php

            $query_cli_chamados = mysqli_query($link, "SELECT status FROM sis_suporte WHERE login LIKE '$login_cliente' AND status = 'aberto'");

            $num_chamados_aberto = mysqli_num_rows($query_cli_chamados);

            if ($cli_ativado == "n") {
                if ($data_desativado != '') {
                    $data_desativado = date('d/m/Y - H:i:s', strtotime($data_desativado));
                }
            ?>

                <div class='col-auto col-sm-1 col-md-1'>
                    <i class="fa-solid fa-user text-secondary fs-4"></i>
                </div>
                <div class='col-auto col-sm-6 col-md-3'>
                    <p class='desativado'>

                        <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                    </p>

                    <p class='final_conn'><b>Desativado em:</b> <?= $data_desativado; ?></p>
                    <?php
                } else {
                    $data_bloq = date('d/m/Y - H:i:s', strtotime($data_bloq));
                    $obs_data = date('d/m/Y', strtotime($obs_data));

                    if (strcasecmp($username_on[strtolower($login_cliente)], strtolower($login_cliente)) == 0) {

                        if ($bloqueado == "sim") {
                    ?>
                            <div class='col-auto col-sm-1 col-md-1'>
                                <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>' target='_blank'>
                                    <!-- <img src='img/icon_on_bloq.png' class='icon_g mouse_hover' title='Acessar Cliente Online e Bloqueado http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>'> -->

                                    <i class="fa-solid fa-user-lock text-success fs-4"></i>
                                </a>
                            <?php
                        } else {
                            ?>

                                <div class='col-auto col-sm-1 col-md-1'>
                                    <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>' target='_blank'>
                                        <!-- <img src='img/icon_online.png' class='icon mouse_hover' title='Acessar http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>'> -->
                                        <i class="fa-solid fa-user text-success fs-4"></i>

                                    </a>
                                <?php
                            }

                            if ($num_chamados_aberto > 0) {
                                ?>
                                    <p class='titulos_vencidos'>

                                        <a href='../../suporte_aberto.<?= $links_ext; ?>?login=<?= $login_cliente; ?>' title='CHAMADOS ABERTOS: <?= $login_cliente; ?>'>
                                            <i class="fa-solid fa-headset fs-4"></i> <?= $num_chamados_aberto; ?></a>
                                    </p>

                                <?php
                            }

                            if ($tit[strtolower(trim($login_cliente))] > 0) {
                                ?>
                                    <p class='titulos_vencidos'>

                                        <a href='../../cliente_det.<?= $links_ext ?>?uuid=<?= $uuid_cliente; ?>' title='TITULOS VENCIDOS: <?= $nome_cliente; ?>'>
                                            <i class="fa-solid fa-file-invoice-dollar text-danger fs-4"></i>
                                            <?= $tit[strtolower(trim($login_cliente))]; ?>
                                        </a>
                                    </p>
                                <?php
                            }

                                ?>
                                <p>
                                    <?php
                                    if ($cli_parc_abertas == 2 || $cli_tit_abertos == 2) {
                                    ?>

                                        <img src='img/icon_dois.png' class='icon_menu align_middle' title='Faltam 2 parcelas do carnê'>
                                    <?php
                                    } elseif ($cli_parc_abertas == 1 || $cli_tit_abertos == 1) {
                                    ?>
                                        <img src='img/icon_um.png' class='icon_menu align_middle' title='Falta 1 parcela do carnê'>
                                    <?php
                                    } elseif ($cli_parc_abertas == 0 && $cli_tit_abertos == 0) {
                                    ?>
                                        <i class="fa-solid fa-circle-exclamation fs-4 text-warning" title="O cliente não tem carnê"></i>
                                    <?php
                                    }
                                    ?>
                                </p>
                                </div>

                                <?php
                                if ($bloqueado == "sim") {
                                ?>
                                    <div class='col-auto col-sm-6 col-md-3'>
                                        <p class='bloqueado'>
                                            <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                            <a href='#' onclick="abrirJanela('teste_obs.php?login=<?= $login_cliente; ?>', 200, 200);"><img src='img/icon_desbloquear.png' class='icon_g no_print' title='Liberar por 3 dias' /></a>

                                        </p>

                                        <p class='info_add'><b>Bloqueado em:</b> <?= $data_bloq; ?></p>
                                    <?php
                                } elseif ($observacao == "sim") {
                                    ?>
                                        <div class='col-auto col-sm-6 col-md-3'>

                                            <p class='observacao'>
                                                <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                            </p>

                                            <p class='info_add'><b>Obs será removida em:</b> <?= $obs_data; ?></p>

                                        <?php
                                    } else {
                                        ?>
                                            <div class='col-auto col-sm-6 col-md-3'>

                                                <p><a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a></p>

                                            <?php
                                        }
                                    } else {

                                        $query_cliente_off = mysqli_query($link, "SELECT DISTINCT acctstoptime FROM radacct WHERE username = '$login_cliente' ORDER BY acctstoptime DESC");

                                        $row3 = mysqli_fetch_row($query_cliente_off);
                                        $final_conn = date('d/m/Y - H:i:s', strtotime($row3[0]));

                                        if ($bloqueado == "sim") {
                                            ?>
                                                <div class='col-auto col-sm-1 col-md-1'>
                                                    <a href='#'>
                                                        <i class="fa-solid fa-user-lock text-danger fs-4"></i>
                                                    </a>
                                                <?php
                                            } else {
                                                ?>
                                                    <div class='col-auto col-sm-1 col-md-1'>
                                                        <a href='#'>
                                                            <i class="fa-solid fa-user text-danger fs-4"></i>
                                                        </a>
                                                    <?php
                                                }

                                                if ($num_chamados_aberto > 0) {
                                                    ?>
                                                        <p class='titulos_vencidos'>

                                                            <a href='../../suporte_aberto.<?= $links_ext; ?>?login=<?= $login_cliente; ?>' title='CHAMADOS ABERTOS: <?= $login_cliente; ?>'>
                                                                <i class="fa-solid fa-headset fs-4"></i> <?= $num_chamados_aberto; ?>
                                                            </a>
                                                        </p>
                                                    <?php
                                                }

                                                if ($tit[strtolower(trim($login_cliente))] > 0) {
                                                    ?>
                                                        <p class='titulos_vencidos'>

                                                            <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='TITULOS VENCIDOS: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-file-invoice-dollar text-danger fs-4"></i> <?= $tit[strtolower(trim($login_cliente))]; ?>
                                                            </a>
                                                        </p>
                                                    <?php
                                                }
                                                    ?>

                                                    <p>
                                                        <?php
                                                        if ($cli_parc_abertas == 2 || $cli_tit_abertos == 2) {
                                                        ?>
                                                            <img src='img/icon_dois.png' class='icon_menu align_middle' title='Faltam 2 parcelas do carnê'>
                                                        <?php
                                                        } elseif ($cli_parc_abertas == 1 || $cli_tit_abertos == 1) {
                                                        ?>
                                                            <img src='img/icon_um.png' class='icon_menu align_middle' title='Falta 1 parcela do carnê'>
                                                        <?php
                                                        } elseif ($cli_parc_abertas == 0 && $cli_tit_abertos == 0) {
                                                        ?>
                                                            <i class="fa-solid fa-circle-exclamation fs-4 text-warning" title="O cliente não tem carnê"></i>
                                                        <?php
                                                        }
                                                        ?>
                                                    </p>
                                                    </div>

                                                    <?php
                                                    if ($bloqueado == "sim") {
                                                    ?>
                                                        <div class='col-auto col-sm-6 col-md-3'>
                                                            <p class='bloqueado'>
                                                                <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                                                <a href='#' onclick="abrirJanela('teste_obs.php?login=<?= $login_cliente; ?>', 200, 200);"><img src='img/icon_desbloquear.png' class='icon_g no_print' title='Liberar por 3 dias' /></a>
                                                            </p>

                                                            <p class='final_conn no_print'><b>Caiu em:</b> <?= $final_conn; ?></p>

                                                            <p class='info_add'><b>Bloqueado em:</b> <?= $data_bloq; ?></p>
                                                        <?php
                                                    } elseif ($observacao == "sim") {
                                                        ?>
                                                            <div class='col-auto col-sm-6 col-md-3'>

                                                                <p class='observacao'>
                                                                    <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                                                </p>

                                                                <p class='final_conn'><b>Caiu em:</b> <?= $final_conn; ?></p>

                                                                <p class='info_add'><b>Obs será removida em:</b> <?= $obs_data; ?></p>

                                                            <?php
                                                        } else {
                                                            ?>
                                                                <div class='col-auto col-sm-6 col-md-3'>

                                                                    <p>
                                                                        <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                                                    </p>

                                                                    <p class='final_conn'><b>Caiu em:</b> <?= $final_conn; ?></p>
                                                            <?php
                                                        }
                                                    }
                                                }

                                                if ($organizar == 'c.data_ins DESC') {
                                                    $data_cad = date('d/m/Y - H:i:s', strtotime($data_cad));
                                                            ?>

                                                            <p class='info_add'><b>Data cadastro:</b> <?= $data_cad; ?></p>

                                                        <?php
                                                    }
                                                    if ($organizar == 'c.last_update DESC') {
                                                        $last_update = date('d/m/Y - H:i:s', strtotime($last_update));
                                                        ?>
                                                            <p class='info_add'><b>Ultima alteração:</b> <?= $last_update; ?></p>

                                                        <?php
                                                    }

                                                        ?>

                                                        <p class='op_cliente no_print'>
                                                            <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-money-check-dollar fs-5"></i>
                                                            </a> |
                                                            <a href='../../cliente_alt.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='ALTERAR CLIENTE: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-user-pen fs-5"></i>
                                                            </a> |
                                                            <a href='det_conn.php?login=<?= $login_cliente; ?>' title='CONXÕES CLIENTE: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-globe fs-5"></i>
                                                            </a> |
                                                            <a href='../../suporte_ins.<?= $links_ext; ?>?login=<?= $login_cliente; ?>' title='VER CHAMADOS: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-headset fs-5"></i>
                                                            </a> |

                                                            <a href='#' onclick="javascript:abrirJanela('../../cliente_info.<?= $links_ext; ?>?login=<?= $login_cliente; ?>', 600, 900);">
                                                                <i class="fa-solid fa-circle-info fs-5"></i>
                                                            </a> |
                                                            <?php

                                                            if ($cli_email != '') {
                                                            ?>
                                                                <a href='mailto:<?= $cli_email; ?>'>
                                                                    <i class="fa-solid fa-envelope fs-5"></i>
                                                                </a> |
                                                            <?php
                                                            }


                                                            ?>


                                                            <?php

                                                            $query_cli_adicional = mysqli_query($link, "SELECT username, nome, uuid_adicional FROM sis_adicional WHERE login LIKE '$login_cliente' ORDER BY username");

                                                            $num_cli_adicional = mysqli_num_rows($query_cli_adicional);
                                                            if ($num_cli_adicional > 0) {
                                                            ?>
                                                        <p><a href='../../adicionais.<?= $links_ext; ?>?uuid_cliente=<?= $uuid_cliente; ?>' title='CLIENTES ADICIONAIS: <?= $nome_cliente; ?>'><b>Adicionais:</b></a>
                                                            <?php

                                                                while ($add = mysqli_fetch_assoc($query_cli_adicional)) {
                                                                    $username_cli_adicional = $add['username'];
                                                                    $nome_cli_adicional = $add['nome'];
                                                                    $uuid_cli_adicional = $add['uuid_adicional'];

                                                                    /*$query_add_on = mysqli_query($link, "SELECT username FROM radacct WHERE username LIKE '$username_cli_adicional' AND acctstoptime IS NULL");

            while($add_on = mysqli_fetch_assoc($query_add_on)){
                
            }*/

                                                                    $add_online = $username_on[strtolower($username_cli_adicional)];

                                                                    $conn_add = " <a href='det_conn.php?login=$username_cli_adicional' title='CONXÕES CLIENTE: $nome_cliente'>
            <img src='img/icon_menu_conexoes.png' class='icon_p' title='Conexoes'>
            </a> ";

                                                                    if (strcasecmp(strtolower($add_online), strtolower($username_cli_adicional)) == 0) {
                                                            ?>
                                                                    <a href='../../adicional_alt.<?= $links_ext; ?>?uuid=<?= $uuid_cli_adicional; ?>' title='VER ADICIONAL: <?= $nome_cli_adicional; ?>'>
                                                                        <img src='img/icon_bola_on.png' class='icon_p' title='Cliente Online'><?= $username_cli_adicional; ?></a>


                                                                <?php
                                                                    } else {
                                                                ?>
                                                                    <a href='../../adicional_alt.<?= $links_ext; ?>?uuid=<?= $uuid_cli_adicional; ?>' title='VER ADICIONAL: <?= $nome_cli_adicional; ?>'>
                                                                        <img src='img/icon_bola_off.png' class='icon_p' title='Cliente Offline'><?= $username_cli_adicional; ?></a> <?= $conn_add; ?>|
                                                            <?php
                                                                    }
                                                                }
                                                            } else {
                                                            ?>

                                                            <a href='../../adicionais.<?= $links_ext; ?>?uuid_cliente=<?= $uuid_cliente; ?>' title='Novo Cliente Adicional'><b>

                                                                </b>
                                                                <i class="fa-solid fa-user-plus fs-5"></i></a>
                                                        <?php
                                                            }

                                                        ?>
                                                        </p>
                                                                </div>

                                                                <div class='col-12 col-sm-4 col-md-3 text-center'>
                                                                    <p class=''>
                                                                        <?= $end_cliente; ?> <b>n°.</b> <?= $numero_casa; ?>

                                                                        <?= $bairro_cliente; ?>
                                                                        <?= $complemento_cliente; ?>
                                                                        <span class='no_print'><?= $cidade_cliente; ?> / <?= $uf_cliente; ?></span><br>

                                                                        <?php

                                                                        echo "<b>Score:</b> {$showScore} ";
                                                                        echo diffDate(date('Y-m-d'), $dataRenovacao);

                                                                        ?>
                                                                    </p>
                                                                </div>

                                                                <!-- <hr class="d-block d-sm-none"> -->

                                                                <div class='col-4 col-sm-4 col-md-1'>
                                                                    <p>
                                                                        <?php
                                                                        if ($fones_cliente != '') {
                                                                        ?>
                                                                            <a href='<?= $link_whats . $fones_cliente; ?>' title='Whatsapp para <?= $fones_cliente; ?>' target='_blank'><?= $fones_cliente; ?>
                                                                                <i class="fa-brands fa-square-whatsapp fs-5 text-success"></i>
                                                                            </a>
                                                                            </br>

                                                                        <?php
                                                                        }
                                                                        ?>

                                                                        <?php
                                                                        if ($fones_cliente2 != '') {
                                                                        ?>
                                                                            <a href='<?= $link_whats . $fones_cliente2; ?>' title='Whatsapp para <?= $fones_cliente2; ?>' target='_blank'><?= $fones_cliente2; ?>
                                                                                <i class="fa-brands fa-square-whatsapp fs-5 text-success"></i>
                                                                            </a>
                                                                            </br>

                                                                        <?php
                                                                        }
                                                                        ?>

                                                                        <?php
                                                                        if ($fones_cliente3 != '') {
                                                                        ?>
                                                                            <a href='<?= $link_whats . $fones_cliente3; ?>' title='Whatsapp para <?= $fones_cliente3; ?>' target='_blank'><?= $fones_cliente3; ?>
                                                                                <i class="fa-brands fa-square-whatsapp fs-5 text-success"></i>
                                                                            </a>
                                                                            </br>

                                                                        <?php
                                                                        }
                                                                        ?>

                                                                    </p>
                                                                </div>

                                                                <style>
                                                                    .small2 {
                                                                        font-size: 0.8em;
                                                                    }
                                                                </style>
                                                                <div class='col-8 col-sm-8 col-md-3'>
                                                                    <p class='dados_cliente'>
                                                                        <b>Login:</b> <?= $login_cliente; ?> /
                                                                        <b>Senha:</b> <?= $senha_cliente; ?>
                                                                    </p>

                                                                    <p class='dados_cliente <?= $color ?>'>

                                                                    <?php
                                                                        if ($observacao == "sim" && !empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>
<span class="bg-success text-white px-1 py-0 rounded-pill small2">
                                                                                <b>IP:</b> <?= $ip_conn[strtolower($login_cliente)]; ?>
                                                                                OBSERVACAO </span>

                                                                        <?php
                                                                        }
                                                                        else if ($bloqueado == "nao" && !empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>
                                                                            <span class="bg-success text-white px-1 py-0 rounded-pill small2">
                                                                                <b>IP:</b> <?= $ip_conn[strtolower($login_cliente)]; ?>
                                                                                CONECTADO </span>
                                                                        <?php
                                                                        } else if ($bloqueado == "sim" && !empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>

                                                                            <span class="bg-success text-white px-1 py-0 rounded-pill small2">
                                                                                <b>IP:</b> <?= $ip_conn[strtolower($login_cliente)]; ?>
                                                                                BLOQUEADO </span>

                                                                        <?php
                                                                        } else if (empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>
                                                                            <span class="bg-danger text-white px-1 py-0 rounded-pill small2">IP: DESCONECTADO</span>

                                                                        <?php

                                                                        } else {
                                                                            
                                                                        }
                                                                                                ?>

                                                                        <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>' target='_blank' title='Acessar porta <?= $porta_acesso; ?>'> <?= $porta_acesso; ?></a>

                                                                        <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso2}"; ?>' target='_blank' title='Acessar porta <?= $porta_acesso2; ?>'> <?= $porta_acesso2; ?></a>

                                                                        <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso3}"; ?>' target='_blank' title='Acessar porta <?= $porta_acesso3; ?>'> <?= $porta_acesso3; ?></a>


                                                                    </p>

                                                                    <p class='dados_cliente'>
                                                                        <b>Plano:</b> <?= $plano_cliente; ?>
                                                                    </p>


                                                                    <p class='dados_cliente'>

                                                                        <?php
                                                                        if ($comodato == "sim") {
                                                                        ?>
                                                                            <b>Equipamento:</b> <?= $equipamento; ?>
                                                                        <?php
                                                                        }
                                                                        if ($switch_cliente != '') {
                                                                        ?>
                                                                            <b>Switch:</b> <?= $switch_cliente; ?>
                                                                        <?php
                                                                        }

                                                                        if ($caixa_herm != "") {
                                                                        ?>
                                                                            <b>CTO:</b> <?= $caixa_herm; ?>
                                                                            <?php
                                                                            if ($porta_splitter != "") {
                                                                            ?>
                                                                                <b>PORTA:</b> <?= $porta_splitter; ?>
                                                                        <?php
                                                                            }
                                                                        }

                                                                        ?>

                                                                    </p>
                                                                </div>

                                                            </div>
                                                            <!-- <hr class="m-0 p-0 fw-bold"/> -->
                                                        <?php
                                                        $cont++;
                                                    }
                                                        ?>

                                                        <div class='opcoes no_print'>
                                                            <input type='image' src='img/icon_menu_add_cliente.png' formaction='../../cliente_ins.<?= $links_ext; ?>' title='Adicionar Cliente' />

                                                            <input type='image' src='img/icon_menu_deletar.png' formaction='../../cliente_del.<?= $links_ext; ?>' title='Deletar Clientes' />
                                                            <input type='image' src='img/icon_menu_editar.png' formaction='../../clientes_upd.<?= $links_ext; ?>' title='Editar Clientes' />
                                                            <input type='image' src='img/icon_menu_desativar.png' formaction='../../cliente_onoff.<?= $links_ext; ?>' title='Desativar Clientes' />

                                                            <input type='image' src='img/icon_menu_mapa.png' formaction='../../clientes_map.google.<?= $links_ext; ?>' title='Ver no Mapa' />
                                                            <input type='image' src='img/icon_menu_email2.png' formaction='../../send_cliente.<?= $links_ext; ?>' title='Enviar Mensagem' />
                                                            <input type='image' src='img/icon_menu_reparar.png' formaction='../../reparar.<?= $links_ext; ?>' title='Reparar Clientes' />

                                                        </div>



                                                        <?php
                                                        //$tot_resultados += $num_cli_adicional;

                                                        // $endtime = microtime(true);

                                                        // $tempo_sql = (($endtime - $starttime) * 1000);
                                                        // $tempo_sql = number_format($tempo_sql, 2);

                                                        // echo "<p class='no_print'><b>Tempo de Consulta no Banco de Dados:</b> $tempo_sql milissegundos</p>";

                                                        //echo "</table>";

                                                        $url = "?busca=$busca2&organizar=$organizar&num_registros=$registros_por_pagina";




                                                        ?>
                                                        <br>
                                                        <nav class="">
                                                            <ul class="pagination d-flex flex-wrap justify-content-center lead">

                                                                <?php
                                                                if ($pc > 1) {
                                                                ?>
                                                                    <li class="page-item">
                                                                        <span class="page-link"><a href="<?= $url; ?>&pagina=<?= $anterior; ?>">Anterior</a> </span>
                                                                    </li>
                                                                    <?php
                                                                }

                                                                for ($i = 1; $i <= ceil($tot_paginas); $i++) {
                                                                    if ($pagina == $i) {
                                                                    ?>
                                                                        <li class="page-item active"><a class="page-link" href="<?= $url; ?>&pagina=<?= $i; ?>"><?= $i; ?></a></li>
                                                                    <?php
                                                                    } else {
                                                                    ?>
                                                                        <li class="page-item"><a class="page-link" href="<?= $url; ?>&pagina=<?= $i; ?>"><?= $i; ?></a></li>

                                                                    <?php
                                                                    }
                                                                }
                                                                if ($pc < $tot_paginas) {
                                                                    ?>
                                                                    <li class="page-item">
                                                                        <span class="page-link"><a href="<?= $url; ?>&pagina=<?= $proximo; ?>">Próxima</a> </span>
                                                                    </li>

                                                                <?php
                                                                }

                                                                ?>

                                                            </ul>
                                                        </nav>

                                                        </div>
</form>