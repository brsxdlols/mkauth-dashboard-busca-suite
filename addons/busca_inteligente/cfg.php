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
            <a href="relcontratos.php" class="nav-link">
            <i class="fa-solid fa-file-signature fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="cfg.php" class="nav-link active">
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

    // Busca Inteligente
    $query_atual_cfg = mysqli_query($link, "SELECT * FROM busca_inteligente_cfg ORDER BY id DESC LIMIT 1");
    while ($cfg = mysqli_fetch_array($query_atual_cfg)) {
        $num_conexoes = $cfg['num_conexoes'];
        $tempo_queda = $cfg['tempo_queda'];
        $porta_acesso = $cfg['porta_acesso'];
        $porta_acesso2 = $cfg['porta_acesso2'];
        $porta_acesso3 = $cfg['porta_acesso3'];
        $link_whats = $cfg['link_whats'];
        $links_ext = $cfg['links_ext'];
        $check_online = $cfg['check_online'];
        $contabilizar_bloq_offline = $cfg['contabilizar_bloq_offline'];
    }

    $num_conexoes = isset($_POST['num_conexoes']) ? $_POST['num_conexoes'] : $num_conexoes;
    $tempo_queda = isset($_POST['tempo_queda']) ? $_POST['tempo_queda'] : $tempo_queda;
    $porta_acesso = isset($_POST['porta_acesso']) ? $_POST['porta_acesso'] : $porta_acesso;
    $porta_acesso2 = isset($_POST['porta_acesso2']) ? $_POST['porta_acesso2'] : $porta_acesso2;
    $porta_acesso3 = isset($_POST['porta_acesso3']) ? $_POST['porta_acesso3'] : $porta_acesso3;
    $link_whats = isset($_POST['link_whats']) ? $_POST['link_whats'] : $link_whats;
    $links_ext = isset($_POST['links_ext']) ? $_POST['links_ext'] : $links_ext;
    $check_online = isset($_POST['check_online']) ? $_POST['check_online'] : $check_online;
    $contabilizar_bloq_offline = isset($_POST['contabilizar_bloq_offline']) ? $_POST['contabilizar_bloq_offline'] : $contabilizar_bloq_offline;


    // Score Clientes
    $query_atual_cfg = mysqli_query($link, "SELECT * FROM score_cliente_cfg ORDER BY id DESC LIMIT 1");
    while ($cfg = mysqli_fetch_array($query_atual_cfg)) {

        $score_base = $cfg['score_base'];
        $ano_fidelizacao = $cfg['ano_fidelizacao'];

        $tit_venc_0 = $cfg['tit_venc_0'];
        $tit_venc_1 = $cfg['tit_venc_1'];
        $tit_venc_2 = $cfg['tit_venc_2'];
        $tit_venc_3 = $cfg['tit_venc_3'];
        $tit_venc_4 = $cfg['tit_venc_4'];
        $tit_venc_5 = $cfg['tit_venc_5'];
        $tit_venc_6 = $cfg['tit_venc_6'];
        $tit_venc_7 = $cfg['tit_venc_7'];

        $tit_apos_venc = $cfg['tit_apos_venc'];
        $tit_dia_venc = $cfg['tit_dia_venc'];
        $tit_antes_venc = $cfg['tit_antes_venc'];
    }

    $score_base = isset($_POST['score_base']) ? $_POST['score_base'] : $score_base;
    $ano_fidelizacao = isset($_POST['ano_fidelizacao']) ? $_POST['ano_fidelizacao'] : $ano_fidelizacao;
    $tit_venc_0 = isset($_POST['tit_venc_0']) ? $_POST['tit_venc_0'] : $tit_venc_0;
    $tit_venc_1 = isset($_POST['tit_venc_1']) ? $_POST['tit_venc_1'] : $tit_venc_1;
    $tit_venc_2 = isset($_POST['tit_venc_2']) ? $_POST['tit_venc_2'] : $tit_venc_2;
    $tit_venc_3 = isset($_POST['tit_venc_3']) ? $_POST['tit_venc_3'] : $tit_venc_3;
    $tit_venc_4 = isset($_POST['tit_venc_4']) ? $_POST['tit_venc_4'] : $tit_venc_4;
    $tit_venc_5 = isset($_POST['tit_venc_5']) ? $_POST['tit_venc_5'] : $tit_venc_5;
    $tit_venc_6 = isset($_POST['tit_venc_6']) ? $_POST['tit_venc_6'] : $tit_venc_6;
    $tit_venc_7 = isset($_POST['tit_venc_7']) ? $_POST['tit_venc_7'] : $tit_venc_7;
    $tit_apos_venc = isset($_POST['tit_apos_venc']) ? $_POST['tit_apos_venc'] : $tit_apos_venc;
    $tit_dia_venc = isset($_POST['tit_dia_venc']) ? $_POST['tit_dia_venc'] : $tit_dia_venc;
    $tit_antes_venc = isset($_POST['tit_antes_venc']) ? $_POST['tit_antes_venc'] : $tit_antes_venc;

    ?>

    <!-- Busca Inteligente -->

    <div class="row g-1">
        <div class="col-12 col-md-6">
            <form action="" method="post" id="">

                <legend class="lead text-center">Configurações do Busca Inteligente</legend>

                    <table class="table table-sm small">

                        <tr>
                            <td class="">Número de Conexões <input type="number" name="num_conexoes" class="" value="<?php echo $num_conexoes; ?>"></td>
                        </tr>
                        <tr>
                            <td class="">Tempo p/ contabilizar Quedas em MIN. <input type="number" name="tempo_queda" class="" value="<?php echo $tempo_queda; ?>"></td>
                        </tr>


                        <tr>
                            <td class="">Porta de Acesso Clientes 1 <input type="number" name="porta_acesso" class="" value="<?php echo $porta_acesso; ?>"></td>
                        </tr>
                        <tr>
                            <td class="">Porta de Acesso Clientes 2 <input type="number" name="porta_acesso2" class="" value="<?php echo $porta_acesso2; ?>"></td>
                        </tr>
                        <tr>
                            <td class="">Porta de Acesso Clientes 3 <input type="number" name="porta_acesso3" class="" value="<?php echo $porta_acesso3; ?>"></td>
                        </tr>
                        <tr>
                            <td class="">Link Whatsapp <input type="text" name="link_whats" class="" value="<?php echo $link_whats; ?>"></td>

                        </tr>
                        <tr>
                            <td class="">Formato de Arquivos MK-AUTH 
                                <select class="" name="links_ext">
                                    <?php
                                    if ($links_ext == "php") {
                                        echo "
            <option value='php' selected>PHP</option>
            <option value='hhvm'>HHVM</option>";
                                    } else {
                                        echo "
            <option value='php'>PHP</option>
            <option value='hhvm' selected>HHVM</option>";
                                    }

                                    ?>

                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td class="">Checagem de Clientes Online
                                <select class="" name="check_online">
                                    <?php
                                    if ($check_online == "mkauth") {
                                        echo "
            <option value='mkauth' selected>MKAUTH</option>
            <option value='mikrotik'>MIKROTIK API</option>";
                                    } else {
                                        echo "
            <option value='mkauth'>MKAUTH</option>
            <option value='mikrotik' selected>MIKROTIK API</option>";
                                    }

                                    ?>

                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td class="">Exibir bloqueados em Lista de Offline?
                                <select class="" name="contabilizar_bloq_offline">
                                    <?php
                                    if ($contabilizar_bloq_offline == "s") {
                                        echo "
            <option value='s' selected>Sim</option>
            <option value='n'>Não</option>";
                                    } else {
                                        echo "
            <option value='s'>Sim</option>
            <option value='n' selected>Não</option>";
                                    }

                                    ?>

                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2"><input type="submit" name="OK" class="btn btn-primary" value="OK" onClick="configUpdate()"></td>

                        </tr>

                    </table>

                </fieldset>

            </form>

        </div>
        <div class="col-12 col-md-6">
            <!-- Score Clientes -->
            <form action="" method="post" id="">
                <table class="table table-sm small">
                <legend>Comandos do Busca</legend>
            <td>&nbsp; sem carne, sem titulo, venc+5, atrasado</td>
        <tr>
           <td>&nbsp; on, off, bloqueado, desativado, observacao (obs)</td>

            <table>
            <legend>Configurações do Score de Clientes</legend>
                    <tr>
                        <td class="">Score Base
                            <input type="number" name="score_base" class="" value="<?php echo $score_base; ?>" min="100">
                        </td>

                    </tr>
                    <tr>
                        <td class="">Score por Ano de Fidelização

                            <input type="number" name="ano_fidelizacao" class="" value="<?php echo $ano_fidelizacao; ?>">
                        </td>
                    </tr>

                    <tr>
                        <td class="">0 Títulos Vencidos <input type="number" name="tit_venc_0" class="" value="<?php echo $tit_venc_0; ?>"></td>
                    </tr>
                    <tr>

                        <td class="">1 Títulos Vencidos <input type="number" name="tit_venc_1" class="" value="<?php echo $tit_venc_1; ?>"></td>
                    </tr>

                    <tr>
                        <td class="">2 Títulos Vencidos <input type="number" name="tit_venc_2" class="" value="<?php echo $tit_venc_2; ?>"></td>
                    </tr>

                    <tr>
                        <td class="">3 Títulos Vencidos <input type="number" name="tit_venc_3" class="" value="<?php echo $tit_venc_3; ?>"></td>
                    </tr>
                    <tr>
                        <td class="">4 Títulos Vencidos <input type="number" name="tit_venc_4" class="" value="<?php echo $tit_venc_4; ?>"></td>
                    </tr>
                    <tr>
                        <td class="">5 Títulos Vencidos <input type="number" name="tit_venc_5" class="" value="<?php echo $tit_venc_5; ?>"></td>

                    </tr>

                    <tr>
                        <td class="">6 Títulos Vencidos <input type="number" name="tit_venc_6" class="" value="<?php echo $tit_venc_6; ?>"></td>
                    </tr>
                    <tr>
                        <td class="">7 ou + Títulos Vencidos <input type="number" name="tit_venc_7" class="" value="<?php echo $tit_venc_7; ?>"></td>


                    </tr>

                    <tr>
                        <td class="">Título pago em atraso <input type="number" name="tit_apos_venc" class="" value="<?php echo $tit_apos_venc; ?>"></td>

                    </tr>
                    <tr>
                        <td class="">Título pago no vencimento <input type="number" name="tit_dia_venc" class="" value="<?php echo $tit_dia_venc; ?>"></td>

                    </tr>
                    <tr>
                        <td class="">Título pago antecipadamente <input type="number" name="tit_antes_venc" class="" value="<?php echo $tit_antes_venc; ?>"></td>


                    </tr>
                    <tr>
                        <td><input type="submit" name="OK" class="btn btn-primary" value="OK" onClick="configUpdate()"></td>

                    </tr>


        
       
        </tr>
    </table>
</fieldset>


                </table>

            </form>

        </div>
    </div>




    <?php

    if (isset($_POST['OK'])) {
        $update_table_busca_inteligente_cfg = mysqli_query($link, "UPDATE busca_inteligente_cfg 
            SET num_conexoes = '$num_conexoes',
            tempo_queda = '$tempo_queda',
            porta_acesso = '$porta_acesso',
            porta_acesso2 = '$porta_acesso2',
            porta_acesso3 = '$porta_acesso3',
            link_whats = '$link_whats',
            links_ext = '$links_ext',
            check_online = '$check_online',
            contabilizar_bloq_offline = '$contabilizar_bloq_offline'
            WHERE id = 1");

        if (!$update_table_busca_inteligente_cfg) {
            echo mysqli_error($link);
        }
    }

    $update_table_score_cliente_cfg = mysqli_query($link, "UPDATE score_cliente_cfg 
        SET 
        score_base = $score_base,
        ano_fidelizacao = $ano_fidelizacao,
        tit_venc_0 = $tit_venc_0,
        tit_venc_1 = $tit_venc_1,
        tit_venc_2 = $tit_venc_2,
        tit_venc_3 = $tit_venc_3,
        tit_venc_4 = $tit_venc_4,
        tit_venc_5 = $tit_venc_5,
        tit_venc_6 = $tit_venc_6,
        tit_venc_7 = $tit_venc_7,
        tit_apos_venc = $tit_apos_venc,
        tit_dia_venc = $tit_dia_venc,
        tit_antes_venc = $tit_antes_venc
        WHERE id = 1");

    if (!$update_table_score_cliente_cfg) {
        echo mysqli_error($link);
    }


    ?>

    <?php

    mysqli_close($link); // Finaliza a conexao com o Banco de Dados

    ?>

    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script>
<?php include('../../rodape.php'); ?>
</body>

</html>