<?php
include('nav/header.php');
require_once __DIR__ . '/../shared/layout_mode.php';
?>

<body class="">

    <?php include('../../topo.php'); ?>
    <?php mka_suite_render_top_spacing_style($link); ?>

    <style>
        .suite-toolbar{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:8px;margin:10px 15px 18px;padding:10px;border:1px solid #dbe5f0;border-radius:16px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.06)}.suite-toolbar a{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border-radius:11px;color:#36506c;text-decoration:none;font-size:13px;font-weight:700;transition:.18s}.suite-toolbar a:hover{background:#edf5ff;color:#1268db}.suite-toolbar a.is-active{background:#1268db;color:#fff}.suite-toolbar i{font-size:16px}
        .suite-toolbar .nav-item{display:flex;margin:0;padding:0;list-style:none}.suite-toolbar .nav-item::marker{content:""}
        .config-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:0 15px 20px}.config-card{padding:18px;border:1px solid #dbe5f0;border-radius:16px;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.05)}.config-card legend{width:auto;margin:0 0 14px;font-size:18px;font-weight:750;color:#27364a}.config-card table{margin:0}.config-card td{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:8px 0!important;border-color:#edf2f7}.config-card input,.config-card select{width:min(210px,48%);height:40px;padding:0 10px;border:1px solid #cbd8e6;border-radius:9px;background:#fff}.config-card input:focus,.config-card select:focus{outline:0;border-color:#1268db;box-shadow:0 0 0 3px rgba(18,104,219,.12)}.config-save{display:inline-flex!important;width:auto!important;height:42px!important;padding:0 20px!important;border:0!important;border-radius:10px!important;background:#1268db!important;color:#fff!important;font-weight:700}.command-box{margin-bottom:18px;padding:14px;border-radius:12px;background:#f5f8fc;color:#475569}.command-box strong{display:block;margin-bottom:6px;color:#27364a}
        @media(max-width:900px){.config-grid{grid-template-columns:1fr}}@media(max-width:520px){.suite-toolbar span{display:none}.config-grid{margin-inline:8px}.config-card td{align-items:flex-start;flex-direction:column}.config-card input,.config-card select{width:100%}}
    </style>
    <nav class="suite-toolbar no_print mka-suite-content-start" aria-label="Navegação das configurações">
        <li class="nav-item">
            <a href="#" onClick="window.history.back()"><i class="fa-solid fa-circle-chevron-left"></i><span>Voltar</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php"><i class="fa-solid fa-house"></i><span><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></span></a>
        </li>
        <li class="nav-item">
            <a href="cli_conn_alerta.php"><i class="fa-solid fa-circle-exclamation"></i><span>Alertas</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="chamados_abertos.php"><i class="fa-solid fa-headset"></i><span>Chamados</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="score.php"><i class="fa-solid fa-ranking-star"></i><span>Score</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="relcontratos.php"><i class="fa-solid fa-file-signature"></i><span>Contratos</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="cfg.php" class="is-active"><i class="fa-solid fa-gear"></i><span>Configurações</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" onClick="window.print()"><i class="fa-solid fa-print"></i><span>Imprimir</span>
            </a>
        </li>

    </nav>

    <?php

    mka_suite_ensure_layout_column($link);
    $suite_layout_mode = mka_suite_get_layout_mode($link);
    $suite_live_search = mka_suite_get_live_search($link);

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
        $suite_layout_mode = isset($cfg['suite_layout_mode']) ? mka_suite_normalize_layout_mode($cfg['suite_layout_mode']) : $suite_layout_mode;
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
    $suite_layout_mode = isset($_POST['suite_layout_mode']) ? $_POST['suite_layout_mode'] : $suite_layout_mode;
    $suite_layout_mode = mka_suite_normalize_layout_mode($suite_layout_mode);
    $suite_live_search = isset($_POST['suite_live_search']) ? mka_suite_normalize_live_search($_POST['suite_live_search']) : $suite_live_search;
    $suite_top_spacing = isset($_POST['suite_top_spacing']) ? mka_suite_normalize_top_spacing($_POST['suite_top_spacing']) : mka_suite_get_top_spacing($link);
    $suite_header_spacing = isset($_POST['suite_header_spacing']) ? mka_suite_normalize_header_spacing($_POST['suite_header_spacing']) : mka_suite_get_header_spacing($link);


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

    <div class="config-grid">
        <div class="config-card">
            <form action="" method="post" id="">

                <legend class="lead text-center">Configurações do Busca Inteligente</legend>

                    <table class="table table-sm small">

                        <tr>
                            <td class="">Layout Dashboard + Busca
                                <select class="" name="suite_layout_mode">
                                    <?php
                                    if ($suite_layout_mode === "legado") {
                                        echo "
            <option value='novo'>Novo</option>
            <option value='legado' selected>Legado</option>";
                                    } else {
                                        echo "
            <option value='novo' selected>Novo</option>
            <option value='legado'>Legado</option>";
                                    }

                                    ?>

                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td class="">Filtro instantâneo na lista
                                <select class="" name="suite_live_search">
                                    <option value="s" <?php echo $suite_live_search === 's' ? 'selected' : ''; ?>>Sim</option>
                                    <option value="n" <?php echo $suite_live_search === 'n' ? 'selected' : ''; ?>>Não</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td>Espaço antes dos menus da Busca (px) <input type="number" name="suite_top_spacing" min="0" max="120" step="1" value="<?php echo (int) $suite_top_spacing; ?>"></td>
                        </tr>

                        <tr>
                            <td>Ajuste menu → informações (px) <input type="number" name="suite_header_spacing" min="-20" max="60" step="1" value="<?php echo (int) $suite_header_spacing; ?>"></td>
                        </tr>

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
                            <td colspan="2"><input type="submit" name="OK" class="config-save" value="Salvar configurações" onClick="configUpdate()"></td>

                        </tr>

                    </table>

                </fieldset>

            </form>

        </div>
        <div class="config-card">
            <!-- Score Clientes -->
            <form action="" method="post" id="">
                <div class="command-box"><strong>Comandos da busca</strong>sem carne, sem título, venc+5, atrasado<br>on, off, bloqueado, desativado, observação (obs)</div>
                <legend>Configurações do Score de Clientes</legend>
                <table class="table table-sm small">
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
                        <td><input type="submit" name="OK" class="config-save" value="Salvar score" onClick="configUpdate()"></td>

                    </tr>


        
       
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

        mka_suite_set_layout_mode($link, $suite_layout_mode);
        mka_suite_set_live_search($link, $suite_live_search);
        mka_suite_set_top_spacing($link, $suite_top_spacing);
        mka_suite_set_header_spacing($link, $suite_header_spacing);
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
