<?php
include('config.php');
require_once __DIR__ . '/../shared/layout_mode.php';
?>

<!DOCTYPE html>
<?php
if (isset($_SESSION['MM_Usuario'])) {
    echo '<html lang="pt-BR">'; // Fix versão antiga MK-AUTH
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

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

    <link href="css/estilo.css" rel="stylesheet" type="text/css" />
    <style>
        :root {
            --cfg-primary: #1769e8;
            --cfg-primary-soft: #edf5ff;
            --cfg-border: #dce6f2;
            --cfg-text: #17324f;
            --cfg-muted: #6f8195;
            --cfg-bg: #f5f8fc;
        }
        body { background: var(--cfg-bg); }
        .cfg-shell { max-width: 1640px; margin: 0 auto; padding: 24px 18px 42px; }
        .cfg-form { color: var(--cfg-text); }
        .cfg-hero {
            display: flex; align-items: center; justify-content: space-between; gap: 18px;
            margin-bottom: 18px; padding: 22px 24px; border: 1px solid var(--cfg-border);
            border-radius: 18px; background: linear-gradient(135deg, #fff 0%, #f3f8ff 100%);
            box-shadow: 0 10px 30px rgba(29, 67, 111, .07);
        }
        .cfg-hero-icon {
            display: flex; align-items: center; justify-content: center; flex: 0 0 50px;
            width: 50px; height: 50px; border-radius: 14px; color: #fff;
            background: linear-gradient(135deg, #1769e8, #3194ff); font-size: 22px;
        }
        .cfg-hero-copy { flex: 1; }
        .cfg-hero h1 { margin: 0 0 4px; font-size: 24px; font-weight: 700; }
        .cfg-hero p { margin: 0; color: var(--cfg-muted); font-size: 14px; }
        .cfg-badge {
            padding: 7px 12px; border-radius: 999px; background: #e6f7ee;
            color: #17804c; font-size: 12px; font-weight: 700; white-space: nowrap;
        }
        .cfg-card {
            margin-bottom: 18px; padding: 22px; border: 1px solid var(--cfg-border);
            border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(29, 67, 111, .055);
        }
        .cfg-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .cfg-card-head > i {
            display: flex; align-items: center; justify-content: center; width: 38px; height: 38px;
            border-radius: 11px; background: var(--cfg-primary-soft); color: var(--cfg-primary); font-size: 16px;
        }
        .cfg-card-head h2 { margin: 0 0 2px; font-size: 17px; font-weight: 700; }
        .cfg-card-head p { margin: 0; color: var(--cfg-muted); font-size: 12px; }
        .cfg-settings-grid { margin: 0 -5px; }
        .cfg-settings-grid > [class*="col-"] { padding: 5px; margin: 0 !important; }
        .cfg-form .form-control, .cfg-form .form-select {
            min-height: 56px; border-color: var(--cfg-border); border-radius: 11px;
            background-color: #fbfdff; box-shadow: none;
        }
        .cfg-form .form-control:focus, .cfg-form .form-select:focus {
            border-color: #75adf8; box-shadow: 0 0 0 3px rgba(23, 105, 232, .1); background: #fff;
        }
        .cfg-form .form-floating > label { color: var(--cfg-muted); }
        .cfg-diagnostic { margin: 0; }
        .cfg-diagnostic .btn {
            min-height: 48px; border-radius: 11px; border: 0; color: #fff;
            background: linear-gradient(135deg, #1769e8, #2485f5);
            box-shadow: 0 7px 16px rgba(23, 105, 232, .18);
        }
        .cfg-diagnostic .btn:hover { color: #fff; transform: translateY(-1px); }
        .cfg-shortcuts { counter-reset: shortcut; }
        .cfg-shortcut-row {
            counter-increment: shortcut; display: grid !important;
            grid-template-columns: 46px 76px minmax(220px, 1.35fr) 92px minmax(180px, .65fr);
            gap: 0; margin-bottom: 8px !important; padding: 0 !important;
            border: 1px solid var(--cfg-border); border-radius: 11px; overflow: hidden; background: #fbfdff;
        }
        .cfg-shortcut-row:before {
            content: counter(shortcut, decimal-leading-zero); display: flex; align-items: center;
            justify-content: center; color: var(--cfg-primary); background: var(--cfg-primary-soft); font-weight: 700;
        }
        .cfg-shortcut-row .input-group-text, .cfg-shortcut-row .form-control {
            min-height: 44px; border: 0; border-left: 1px solid var(--cfg-border); border-radius: 0; background: transparent;
        }
        .cfg-shortcut-row .input-group-text { color: var(--cfg-muted); font-size: 11px; font-weight: 700; }
        .cfg-shortcut-row .form-control { font-size: 13px; }
        .cfg-shortcut-row > .form-control {
            width: 100% !important;
            min-width: 0 !important;
        }
        .cfg-actions {
            position: sticky; bottom: 10px; z-index: 20; display: flex; justify-content: flex-end;
            gap: 10px; margin: 20px 0; padding: 12px; border: 1px solid var(--cfg-border);
            border-radius: 15px; background: rgba(255, 255, 255, .94); box-shadow: 0 12px 35px rgba(20, 51, 85, .14);
            backdrop-filter: blur(8px);
        }
        .cfg-actions .btn { width: auto; min-width: 170px; min-height: 44px; border-radius: 10px; font-weight: 700; }
        @media (max-width: 991px) {
            .cfg-settings-grid > [class*="col-"] { width: 50%; }
            .cfg-shortcut-row { grid-template-columns: 42px 68px minmax(160px, 1fr); }
            .cfg-shortcut-row .input-group-text:nth-of-type(3), .cfg-shortcut-row .form-control:last-child { grid-column: auto; }
        }
        @media (max-width: 680px) {
            .cfg-shell { padding: 14px 10px 30px; }
            .cfg-hero { align-items: flex-start; padding: 18px; }
            .cfg-badge { display: none; }
            .cfg-card { padding: 15px; border-radius: 14px; }
            .cfg-settings-grid > [class*="col-"] { width: 100%; }
            .cfg-shortcut-row { display: flex !important; flex-wrap: wrap; padding: 8px !important; border-radius: 10px; }
            .cfg-shortcut-row:before { width: 38px; min-height: 42px; border-radius: 8px; }
            .cfg-shortcut-row .input-group-text { display: none; }
            .cfg-shortcut-row .form-control { flex: 1 1 calc(100% - 42px); border-left: 0; }
            .cfg-shortcut-row .form-control:last-child { flex-basis: 100%; border-top: 1px solid var(--cfg-border); }
            .cfg-actions { position: static; flex-direction: column-reverse; }
            .cfg-actions .btn { width: 100%; }
        }
    </style>



</head>

<body>

    <?php include("../../topo.php"); ?>

    <?php
    mka_suite_ensure_layout_column($conn);
    $suite_layout_mode = mka_suite_get_layout_mode($conn);
    $radius_alert_enabled = mka_suite_get_radius_alert_enabled($conn);

    $query_atual_cfg = mysqli_query($conn, "SELECT * FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
    while ($cfg = mysqli_fetch_array($query_atual_cfg)) {
        $exb_ticket_medio = $cfg['exb_ticket_medio'];
        $exb_saldo_conta = $cfg['exb_saldo_conta'];
        $exb_clientes_ramal = $cfg['exb_clientes_ramal'];
        $exb_balanco_faturamento = $cfg['exb_balanco_faturamento'];
        $exb_balanco_clientes = $cfg['exb_balanco_clientes'];
        $exb_balanco_chamados = $cfg['exb_balanco_chamados'];
        $exb_busca_inteligente = $cfg['exb_busca_inteligente'];
        $contabilizar_bloq_offline = $cfg['contabilizar_bloq_offline'];
        $exb_graficos_em_baixo = $cfg['exb_graficos_em_baixo'];
        $tbl_logs_sistema = $cfg['tbl_logs_sistema'];
        $tbl_chamados_abertos = $cfg['tbl_chamados_abertos'];
        $tbl_contas_pagar = $cfg['tbl_contas_pagar'];
        $popup_clientes_sessao = isset($cfg['popup_clientes_sessao']) ? $cfg['popup_clientes_sessao'] : 'n';
        $popup_clientes_sessao_duracao = isset($cfg['popup_clientes_sessao_duracao']) ? (int) $cfg['popup_clientes_sessao_duracao'] : 2;
        $qtd_meses_graficos = $cfg['qtd_meses_graficos'];
        $limite_ticket = $cfg['limite_ticket'];
        $link = $cfg['link'];
        $texto = $cfg['texto'];
        $tot_acesso_rapido = $cfg['tot_acesso_rapido'];
        $suite_layout_mode = isset($cfg['suite_layout_mode']) ? mka_suite_normalize_layout_mode($cfg['suite_layout_mode']) : $suite_layout_mode;
    }

    $exb_ticket_medio = isset($_POST['exb_ticket_medio']) ? $_POST['exb_ticket_medio'] : $exb_ticket_medio;
    $exb_saldo_conta = isset($_POST['exb_saldo_conta']) ? $_POST['exb_saldo_conta'] : $exb_saldo_conta;
    $exb_clientes_ramal = isset($_POST['exb_clientes_ramal']) ? $_POST['exb_clientes_ramal'] : $exb_clientes_ramal;

    $exb_balanco_faturamento = isset($_POST['exb_balanco_faturamento']) ? $_POST['exb_balanco_faturamento'] : $exb_balanco_faturamento;

    $exb_balanco_clientes = isset($_POST['exb_balanco_clientes']) ? $_POST['exb_balanco_clientes'] : $exb_balanco_clientes;

    $exb_balanco_chamados = isset($_POST['exb_balanco_chamados']) ? $_POST['exb_balanco_chamados'] : $exb_balanco_chamados;

    $exb_busca_inteligente = isset($_POST['exb_busca_inteligente']) ? $_POST['exb_busca_inteligente'] : $exb_busca_inteligente;

    $contabilizar_bloq_offline = isset($_POST['contabilizar_bloq_offline']) ? $_POST['contabilizar_bloq_offline'] : $contabilizar_bloq_offline;

    $exb_graficos_em_baixo = isset($_POST['exb_graficos_em_baixo']) ? $_POST['exb_graficos_em_baixo'] : $exb_graficos_em_baixo;

    $tbl_logs_sistema = isset($_POST['tbl_logs_sistema']) ? $_POST['tbl_logs_sistema'] : $tbl_logs_sistema;

    $tbl_chamados_abertos = isset($_POST['tbl_chamados_abertos']) ? $_POST['tbl_chamados_abertos'] : $tbl_chamados_abertos;

    $tbl_contas_pagar = isset($_POST['tbl_contas_pagar']) ? $_POST['tbl_contas_pagar'] : $tbl_contas_pagar;

    $popup_clientes_sessao = isset($_POST['popup_clientes_sessao']) ? $_POST['popup_clientes_sessao'] : $popup_clientes_sessao;
    $popup_clientes_sessao_duracao = isset($_POST['popup_clientes_sessao_duracao']) ? $_POST['popup_clientes_sessao_duracao'] : $popup_clientes_sessao_duracao;
    $suite_layout_mode = isset($_POST['suite_layout_mode']) ? $_POST['suite_layout_mode'] : $suite_layout_mode;
    $radius_alert_enabled = isset($_POST['radius_alert_enabled']) ? ($_POST['radius_alert_enabled'] === 'n' ? 'n' : 's') : $radius_alert_enabled;

    $qtd_meses_graficos = isset($_POST['qtd_meses_graficos']) ? $_POST['qtd_meses_graficos'] : $qtd_meses_graficos;

    $limite_ticket = isset($_POST['limite_ticket']) ? $_POST['limite_ticket'] : $limite_ticket;

    $links = explode(',', $link);
    $textos = explode(',', $texto);
    
    $tot_acesso_rapido = isset($_POST['tot_acesso_rapido']) ? $_POST['tot_acesso_rapido'] : $tot_acesso_rapido;

    /*debug($_POST['link']);   

    debug($_POST['texto']);*/
    $i = 0;
    foreach($links as $v){
        $links[$i] = isset($_POST['link'][$i]) ? $_POST['link'][$i] : $links[$i];
        $textos[$i] = isset($_POST['texto'][$i]) ? $_POST['texto'][$i] : $textos[$i];
        $i++;
    }

    $cfgDefault = function ($value, $default = 's') {
        $value = trim((string) $value);
        return $value === '' ? $default : $value;
    };

    $exb_ticket_medio = $cfgDefault($exb_ticket_medio, 's');
    $exb_saldo_conta = $cfgDefault($exb_saldo_conta, 's');
    $exb_clientes_ramal = $cfgDefault($exb_clientes_ramal, 'n');
    $exb_balanco_faturamento = $cfgDefault($exb_balanco_faturamento, 's');
    $exb_balanco_clientes = $cfgDefault($exb_balanco_clientes, 's');
    $exb_balanco_chamados = $cfgDefault($exb_balanco_chamados, 's');
    $exb_busca_inteligente = $cfgDefault($exb_busca_inteligente, 's');
    $contabilizar_bloq_offline = $cfgDefault($contabilizar_bloq_offline, 's');
    $exb_graficos_em_baixo = $cfgDefault($exb_graficos_em_baixo, 's');
    $tbl_logs_sistema = $cfgDefault($tbl_logs_sistema, 's');
    $tbl_chamados_abertos = $cfgDefault($tbl_chamados_abertos, 's');
    $tbl_contas_pagar = $cfgDefault($tbl_contas_pagar, 'n');
    $popup_clientes_sessao = $cfgDefault($popup_clientes_sessao, 'n');
    $popup_clientes_sessao_duracao = max(1, min(15, (int) $popup_clientes_sessao_duracao));
    $suite_layout_mode = mka_suite_normalize_layout_mode($suite_layout_mode);

    ?>

    <div class='container-fluid cfg-shell'>
        <?php if (permissao('perm_config')) { ?>

            <form action="" method="POST" name="cfg_dashboard" class="cfg-form">
                <div class="cfg-hero">
                    <div class="cfg-hero-icon"><i class="fa fa-sliders"></i></div>
                    <div class="cfg-hero-copy">
                        <h1>Configurações do Dashboard</h1>
                        <p>Personalize os indicadores, alertas e recursos exibidos no sistema.</p>
                    </div>
                    <span class="cfg-badge"><i class="fa fa-check-circle"></i> Sistema configurável</span>
                </div>

                <section class="cfg-card">
                    <div class="cfg-card-head">
                        <i class="fa fa-th-large"></i>
                        <div><h2>Visualização e comportamento</h2><p>Escolha o que será exibido no Dashboard e como os dados serão apresentados.</p></div>
                    </div>
                <div class='row cfg-settings-grid'>

                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="suite_layout_mode" id="suite_layout_mode" aria-label="">
                            <?php
                            if ($suite_layout_mode === 'legado') {
                                echo "
                        <option value='novo'>Novo</option>
                        <option value='legado' selected>Legado</option>
                        ";
                            } else {
                                echo "
                        <option value='novo' selected>Novo</option>
                        <option value='legado'>Legado</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="suite_layout_mode">Layout Dashboard + Busca</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_ticket_medio" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_ticket_medio == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Ticket Medio</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_saldo_conta" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_saldo_conta == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Saldo em Conta</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_clientes_ramal" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_clientes_ramal == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Clientes Ramais</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_balanco_faturamento" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_balanco_faturamento == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Balanço Faturamento</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_balanco_clientes" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_balanco_clientes == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Balanço Clientes</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_balanco_chamados" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_balanco_chamados == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Balanco Chamados</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_busca_inteligente" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_busca_inteligente == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Busca Inteligente Instalado?</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="contabilizar_bloq_offline" id="floatingSelect" aria-label="">
                            <?php
                            if ($contabilizar_bloq_offline == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Contabilizar Clientes Bloqueados nos Offline?</label>
                    </div>

                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="exb_graficos_em_baixo" id="floatingSelect" aria-label="">
                            <?php
                            if ($exb_graficos_em_baixo == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Graficos Abaixo?</label>
                    </div>
                    
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="tbl_logs_sistema" id="floatingSelect" aria-label="">
                            <?php
                            if ($tbl_logs_sistema == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Tabela de Logs do Sistema</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="tbl_chamados_abertos" id="floatingSelect" aria-label="">
                            <?php
                            if ($tbl_chamados_abertos == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Tabela de Chamados Abertos</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="tbl_contas_pagar" id="floatingSelect" aria-label="">
                            <?php
                            if ($tbl_contas_pagar == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Exibir Tabela de Contas a Pagar</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="popup_clientes_sessao" id="floatingSelect" aria-label="">
                            <?php
                            if ($popup_clientes_sessao == 's') {
                                echo "
                        <option value='s' selected>Sim</option>
                        <option value='n'>Não</option>
                        ";
                            } else {
                                echo "
                        <option value='s'>Sim</option>
                        <option value='n' selected>Não</option>
                    ";
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Popup de Clientes ao Logar/Deslogar?</label>
                    </div>
                    <div class="col-2 form-floating mb-2 g-1">
                        <input type="number" name="popup_clientes_sessao_duracao" class="form-control" id="popup_clientes_sessao_duracao" placeholder="2" min="1" max="15" step="1" value="<?php echo (int) $popup_clientes_sessao_duracao; ?>">
                        <label for="popup_clientes_sessao_duracao">Tempo do Popup (s)</label>
                    </div>
                    <div class="col-4 form-floating mb-2 g-1">
                        <select class="form-select" name="radius_alert_enabled" id="radius_alert_enabled">
                            <option value="s" <?php echo $radius_alert_enabled === 's' ? 'selected' : ''; ?>>Sim</option>
                            <option value="n" <?php echo $radius_alert_enabled === 'n' ? 'selected' : ''; ?>>Não</option>
                        </select>
                        <label for="radius_alert_enabled">Exibir alerta de integração Radius?</label>
                    </div>
                    <div class="col-6 form-floating mb-2 g-1">
                        <select class="form-select" name="qtd_meses_graficos" id="floatingSelect" aria-label="">
                            <?php
                            for ($i = 1; $i <= 6; $i++) {
                                if ($i == $qtd_meses_graficos) {
                                    echo "<option value='$i' selected>$i</option>";
                                } else {
                                    echo "<option value='$i'>$i</option>";
                                }
                            }
                            ?>
                        </select>
                        <label for="floatingSelect">Quantidade de Meses nos Gráficos</label>
                    </div>
                    <div class="col-6 form-floating mb-2 g-1">
                        <input type="number" name="limite_ticket" class="form-control" id="limite_ticket" placeholder="Ticket" min="50" max="10000" step="50" value="<?php echo $limite_ticket; ?>">
                        <label for="limite_ticket">Valor Máximo de Mensalidade para Considerar no Ticket Médio R$</label>
                    </div>
                </div>
                </section>

                <section class="cfg-card cfg-diagnostic">
                    <div class="cfg-card-head">
                        <i class="fa fa-plug"></i>
                        <div><h2>Diagnóstico dos Ramais / NAS</h2><p>Valide conectividade, porta e autenticação de todos os concentradores cadastrados.</p></div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-primary w-100" id="test-radius-connections" onclick="return testRadiusConnections(this);"><i class="fa fa-plug"></i> Testar conexões de todos os ramais</button>
                        <div id="radius-test-result" class="mt-2" aria-live="polite"></div>
                    </div>
                </section>

                <section class="cfg-card cfg-shortcuts">
                    <div class="cfg-card-head">
                        <i class="fa fa-bolt"></i>
                        <div><h2>Acesso Rápido</h2><p>Organize os atalhos que ficarão disponíveis na tela principal.</p></div>
                    </div>
                    <div class="row">
                    <div class="col-12 form-floating mb-2 g-1">
                        <input type="number" name="tot_acesso_rapido" class="form-control" id="tot_acesso_rapido" placeholder="" min="15" max="50" step="5" value="<?php echo $tot_acesso_rapido; ?>">
                        <label for="tot_acesso_rapido">Total de Atalhos no Acesso Rapido</label>
                    </div>
                    <?php
                    //debug($links);
                    for ($pos = 0; $pos <= $tot_acesso_rapido-1; $pos++) {
                    ?>
                        <div class="col-12 input-group input-group-sm mb-1 cfg-shortcut-row">
                            <span class="input-group-text">LINK</span>
                            <span class="input-group-text">
                            <?php
                            if(startsWith($links[$pos], strtolower("http"))){
                                echo " http://";
                            }else{
                                echo "admin/";
                            }
                            ?>
                            </span>

                            <input type="text" name="link[<?php echo $pos; ?>]" class="form-control" aria-label="" value="<?php echo "$links[$pos]"; ?>">

                            <span class="input-group-text">ATALHO</span>

                            <input type="text" name="texto[<?php echo $pos; ?>]" class="form-control" aria-label="" value="<?php echo "$textos[$pos]"; ?>">

                        </div>
                    <?php
                    }
                    ?>
                    <!-- <div class="col-6 form-floating mb-2 g-1">
                        <input type="text" name="link" class="form-control" id="link" placeholder="link" value="<?php echo $link; ?>">
                        <label for="link">LINK (Separe com Virgula (,))</label>
                    </div>
                    <div class="col-6 form-floating mb-2 g-1">
                        <input type="text" name="texto" class="form-control" id="texto" placeholder="texto" value="<?php echo $texto; ?>">
                        <label for="texto">TEXTO (Separe com Virgula (,))</label>
                    </div>-->
                    </div>
                </section>

                <div class="cfg-actions">

                    <button type="button" name="retorn" onclick="window.location.href = ('index.php');" class="btn btn-light"><i class="fa fa-arrow-left"></i> Voltar</button>

                    <button type="submit" name="OK" onclick="window.alert('Configurações Salvas');" class="btn btn-primary"><i class="fa fa-save"></i> Salvar configurações</button>
                </div>
                <!-- Rodapé -->
<?php include_once('../../baixo.php'); ?>

<!-- Scripts finais -->
<script src="../../menu.js.hhvm"></script>


            </form>

        <?php

            //debug($_POST['link']);
            //debug($_POST['texto']);
            $posted_links = isset($_POST['link']) && is_array($_POST['link']) ? $_POST['link'] : $links;
            $posted_textos = isset($_POST['texto']) && is_array($_POST['texto']) ? $_POST['texto'] : $textos;
            $links_db = implode(',', $posted_links);
            $textos_db = implode(',', $posted_textos);


            //debug($links_db);

            if (isset($_POST['OK'])) {
                $update_table_dashboard_am_sis_cfg = mysqli_query($conn, "UPDATE dashboard_am_sis_cfg 
            SET 
            exb_ticket_medio = '$exb_ticket_medio',
            exb_saldo_conta = '$exb_saldo_conta',
            exb_clientes_ramal = '$exb_clientes_ramal',
            exb_balanco_faturamento = '$exb_balanco_faturamento',
            exb_balanco_clientes = '$exb_balanco_clientes',
            exb_balanco_chamados = '$exb_balanco_chamados',
            exb_busca_inteligente = '$exb_busca_inteligente',
            contabilizar_bloq_offline = '$contabilizar_bloq_offline',
            exb_graficos_em_baixo = '$exb_graficos_em_baixo',
            tbl_logs_sistema = '$tbl_logs_sistema',
            tbl_chamados_abertos = '$tbl_chamados_abertos',
            tbl_contas_pagar = '$tbl_contas_pagar',
            popup_clientes_sessao = '$popup_clientes_sessao',
            popup_clientes_sessao_duracao = '$popup_clientes_sessao_duracao',
            qtd_meses_graficos = '$qtd_meses_graficos',
            limite_ticket = '$limite_ticket',
            link = '$links_db',
            texto = '$textos_db',
            tot_acesso_rapido = '$tot_acesso_rapido'
            WHERE id = 1");

                if (!$update_table_dashboard_am_sis_cfg) {
                    echo mysqli_error($link);
                }

                mka_suite_set_layout_mode($conn, $suite_layout_mode);
                mka_suite_set_radius_alert_enabled($conn, $radius_alert_enabled);
            }
            if (isset($conn) && $conn instanceof mysqli) {
                mysqli_close($conn);
            }
        } // FIm Permissao
        ?>

    </div>



    <script>
        //$('#systopo').hide();
        function radiusEscape(value) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(value == null ? '' : String(value)));
            return div.innerHTML;
        }

        function testRadiusConnections(button) {
            var result = document.getElementById('radius-test-result');
            button.disabled = true;
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testando todos os ramais...';
            result.innerHTML = '<div class="alert alert-info">Executando ping, porta e autenticação da API. Aguarde...</div>';

            var request = new XMLHttpRequest();
            request.open('POST', 'radius_test.php', true);
            request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            request.onreadystatechange = function () {
                if (request.readyState !== 4) return;
                button.disabled = false;
                button.innerHTML = '<i class="fa fa-plug"></i> Testar conexões de todos os ramais';
                if (request.status < 200 || request.status >= 300) {
                    result.innerHTML = '<div class="alert alert-danger">Falha HTTP ' + request.status + '. Não foi possível executar o teste.</div>';
                    return;
                }
                try {
                    var response = JSON.parse(request.responseText);
                    var rows = '';
                    var routers = response.routers || [];
                    for (var i = 0; i < routers.length; i++) {
                        var router = routers[i];
                        var state = router.ok ? '<span class="text-success"><b>OK</b></span>' : '<span class="text-danger"><b>Falha</b></span>';
                        rows += '<tr><td>' + radiusEscape(router.name || '-') + '</td><td>' + radiusEscape(router.router || '-') + '</td><td>' + state + '</td><td>' + radiusEscape(router.reason || '') + '</td></tr>';
                    }
                    var level = response.ok ? 'success' : (response.executed ? 'warning' : 'danger');
                    result.innerHTML = '<div class="alert alert-' + level + '">' + radiusEscape(response.message || '') + '</div>' +
                        '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Ramal</th><th>IP</th><th>Status</th><th>Diagnóstico</th></tr></thead><tbody>' + rows + '</tbody></table></div>';
                } catch (error) {
                    result.innerHTML = '<div class="alert alert-danger">O servidor retornou uma resposta inválida. Atualize a página e tente novamente.</div>';
                }
            };
            request.onerror = function () {
                button.disabled = false;
                button.innerHTML = '<i class="fa fa-plug"></i> Testar conexões de todos os ramais';
                result.innerHTML = '<div class="alert alert-danger">Falha de comunicação com o servidor.</div>';
            };
            request.send('action=test');
            return false;
        }
    </script>
</body>

</html>
