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

    function openTrafficModal(login, router, clientName, plano, concentrador) {
        var overlay = document.getElementById('trafficMonitorModal');
        var frame = document.getElementById('trafficMonitorFrame');
        var title = document.getElementById('trafficMonitorClient');
        var subtitle = document.getElementById('trafficMonitorPppoe');

        if (!overlay || !frame || !title || !subtitle) {
            return false;
        }

        title.textContent = clientName || 'Monitor de tráfego';
        subtitle.textContent = 'PPPoE monitorado: ' + login + (plano ? ' | Plano: ' + plano : '') + (concentrador ? ' | Concentrador: ' + concentrador : '');
        frame.src = 'monitor_traffic.php?embed=1&login=' + encodeURIComponent(login) + '&router=' + encodeURIComponent(router) + '&concentrador=' + encodeURIComponent(concentrador || '');
        overlay.classList.add('is-open');
        document.body.classList.add('traffic-modal-open');
        return false;
    }

    function closeTrafficModal() {
        var overlay = document.getElementById('trafficMonitorModal');
        var frame = document.getElementById('trafficMonitorFrame');
        if (overlay) {
            overlay.classList.remove('is-open');
        }
        if (frame) {
            frame.src = 'about:blank';
        }
        document.body.classList.remove('traffic-modal-open');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeTrafficModal();
        }
    });
</script>
<style>
    body.traffic-modal-open {
        overflow: hidden;
    }

    .traffic-monitor-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, 0.52);
        z-index: 3000;
    }

    .traffic-monitor-modal.is-open {
        display: flex;
    }

    .traffic-monitor-dialog {
        width: min(1040px, calc(100vw - 32px));
        max-height: calc(100vh - 32px);
        overflow: hidden;
        border-radius: 14px;
        background: #f3f7fb;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
        border: 1px solid rgba(209, 219, 230, 0.9);
    }

    .traffic-monitor-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 20px;
        background: #ffffff;
        border-bottom: 1px solid #e5edf5;
    }

    .traffic-monitor-title {
        margin: 0;
        font-size: 19px;
        font-weight: 700;
        color: #16324a;
    }

    .traffic-monitor-subtitle {
        margin: 4px 0 0;
        font-size: 12px;
        color: #5c738a;
    }

    .traffic-monitor-close {
        border: 0;
        border-radius: 0;
        background: transparent;
        color: #31465a;
        width: 28px;
        height: 28px;
        padding: 0;
        font-size: 22px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
    }

    .traffic-monitor-close:hover {
        color: #0f172a;
    }

    .traffic-monitor-frame {
        display: block;
        width: 100%;
        height: min(760px, calc(100vh - 110px));
        border: 0;
        background: #f3f7fb;
    }

    .client-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 999px;
        background: #eaf7ef;
        color: #1f8f4e;
        box-shadow: inset 0 0 0 1px rgba(31, 143, 78, 0.16);
    }

    .client-status-badge.is-offline {
        background: #f1f4f8;
        color: #5b6572 !important;
        box-shadow: inset 0 0 0 1px rgba(91, 101, 114, 0.14);
    }

    .client-status-badge.is-locked {
        background: #fff0f0;
        color: #dc3545 !important;
        box-shadow: inset 0 0 0 1px rgba(220, 53, 69, 0.14);
    }

    .client-status-badge.is-locked-online {
        background: #eaf7ef;
        color: #1f8f4e !important;
        box-shadow: inset 0 0 0 1px rgba(31, 143, 78, 0.16);
    }

    .client-status-badge.is-locked-offline {
        background: #eef1f4;
        color: #5b6572 !important;
        box-shadow: inset 0 0 0 1px rgba(17, 24, 39, 0.18);
        position: relative;
    }

    .client-status-badge .status-lock-overlay,
    .client-status-badge .status-clock-overlay {
        position: absolute;
        line-height: 1;
    }

    .client-status-badge .status-lock-overlay {
        right: -5px;
        bottom: -4px;
        font-size: 11px;
        color: #111827;
    }

    .client-status-badge .status-clock-overlay {
        right: -5px;
        top: -4px;
        font-size: 10px;
    }

    .client-status-badge.is-observation-online {
        background: #eaf7ef;
        color: #1f8f4e !important;
        box-shadow: inset 0 0 0 1px rgba(31, 143, 78, 0.16);
        position: relative;
    }

    .client-status-badge.is-observation-online .status-lock-overlay,
    .client-status-badge.is-observation-online .status-clock-overlay {
        color: #1f8f4e;
    }

    .client-status-badge.is-observation-offline {
        background: #f1f4f8;
        color: #5b6572 !important;
        box-shadow: inset 0 0 0 1px rgba(91, 101, 114, 0.14);
        position: relative;
    }

    .client-status-badge.is-observation-offline .status-lock-overlay,
    .client-status-badge.is-observation-offline .status-clock-overlay {
        color: #5b6572;
    }

    .overdue-title-action {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1px;
        min-width: 28px;
        color: #d92d3f !important;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
    }

    .overdue-title-action i,
    .overdue-title-action svg,
    .overdue-title-action .svg-inline--fa {
        width: 22px !important;
        height: 22px !important;
        font-size: 22px !important;
    }

    .overdue-title-action:hover {
        color: #9f1239 !important;
        transform: translateY(-1px);
    }

    .billing-alert-stack {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        margin-top: 6px;
    }

    .ending-booklet-alert,
    .no-booklet-alert,
    .invalid-booklet-alert {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        font-weight: 800;
        line-height: 1;
    }

    .ending-booklet-alert {
        color: #e59f00;
    }

    .ending-booklet-alert i,
    .ending-booklet-alert svg,
    .ending-booklet-alert .svg-inline--fa,
    .no-booklet-alert i,
    .no-booklet-alert svg,
    .no-booklet-alert .svg-inline--fa,
    .invalid-booklet-alert i,
    .invalid-booklet-alert svg,
    .invalid-booklet-alert .svg-inline--fa {
        width: 22px !important;
        height: 22px !important;
        font-size: 22px !important;
        line-height: 22px !important;
        text-align: center;
    }

    .ending-booklet-alert .remaining-title-count {
        margin-top: 1px;
        font-size: 14px;
    }

    .no-booklet-alert {
        color: #ffc107;
    }

    .invalid-booklet-alert {
        color: #dc3545;
    }

    .last-update-audit-wrap {
        position: relative;
        display: inline-block;
    }

    .last-update-user-link {
        font-weight: 700;
        text-decoration: underline dotted;
        text-underline-offset: 2px;
    }

    .last-update-popover {
        position: absolute;
        left: 50%;
        bottom: calc(100% + 9px);
        z-index: 1080;
        width: max-content;
        max-width: min(420px, 80vw);
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #1f2937;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .18);
        white-space: pre-line;
        transform: translateX(-50%);
        text-align: left;
        font-weight: 400;
        line-height: 1.35;
    }

    .last-update-popover::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        border: 7px solid transparent;
        border-top-color: #fff;
        transform: translateX(-50%);
    }

    .last-update-popover[hidden] {
        display: none !important;
    }

    .client-status-badge.is-disabled {
        background: #f3f5f7;
        color: #8a94a1 !important;
        box-shadow: inset 0 0 0 1px rgba(138, 148, 161, 0.14);
    }

    .client-action-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        margin-top: 4px;
    }

    .client-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #f3f7fb;
        color: #43566a;
        text-decoration: none;
        box-shadow: inset 0 0 0 1px rgba(179, 194, 209, 0.75);
        transition: all .15s ease;
    }

    .client-action-btn:hover {
        background: #eaf1f8;
        color: #1b3f64;
    }

    .client-action-btn.is-warning {
        background: #fff6dd;
        color: #9d6b00;
        box-shadow: inset 0 0 0 1px rgba(226, 186, 74, 0.85);
    }

    .client-action-btn.is-danger {
        background: #fff0f0;
        color: #c43a3a;
        box-shadow: inset 0 0 0 1px rgba(233, 136, 136, 0.8);
    }

    .client-action-btn.is-success {
        background: #eaf7ef;
        color: #18794a;
        box-shadow: inset 0 0 0 1px rgba(117, 202, 150, 0.8);
    }

    .client-action-btn.has-counter {
        justify-content: flex-start;
        gap: 7px;
        min-width: 48px;
        padding: 0 8px;
    }

    .badge-parcelas-inline {
        min-width: 16px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
    }

    .map-link-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #eef4ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: inset 0 0 0 1px rgba(29, 78, 216, 0.16);
    }

    .map-link-btn:hover {
        background: #dbeafe;
        color: #1e40af;
        text-decoration: none;
    }

    .client-add-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #42576b;
        text-decoration: none;
    }

    .client-add-status.is-online {
        color: #18794a;
    }

    .client-add-status.is-offline {
        color: #66707c;
    }

    .client-head,
    .client-row {
        --bs-gutter-x: 0;
        align-items: flex-start;
    }

    .client-head {
        margin: 4px 0 6px;
        padding: 10px 8px;
        border-radius: 14px;
        background: #29313a;
        color: #ffffff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.10);
    }

    .client-head .client-check-col {
        text-align: left;
    }

    .client-head a,
    .client-head .link-light {
        color: #ffffff !important;
        text-decoration: none;
    }

    .client-row {
        padding: 10px 8px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 14px;
        transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .client-row:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        background-color: rgba(255, 255, 255, 0.94) !important;
    }

    .client-check-col {
        width: 28px;
        padding-top: 4px;
    }

    .client-status-col {
        width: 42px;
        padding: 0 6px 0 4px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .client-name-col {
        padding-right: 12px;
    }

    .client-address-col {
        padding: 0 12px;
    }

    .client-contact-col {
        padding: 0 12px 0 4px;
        text-align: center;
    }

    .client-data-col {
        padding-left: 12px;
    }

    .client-name-col p,
    .client-address-col p,
    .client-contact-col p,
    .client-data-col p {
        margin-bottom: 6px;
    }

    .client-name-col .op_cliente,
    .client-name-col .client-action-toolbar {
        margin-bottom: 4px;
    }

    .client-name-col .observacao a { color: #d4a017 !important; }

    .client-address-col,
    .client-contact-col,
    .client-data-col {
        font-size: 14px;
        line-height: 1.35;
    }

    .client-meta-stack {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin: 6px 0 8px;
        font-size: 13px;
        line-height: 1.3;
        color: #42576b;
    }

    .client-meta-stack p {
        margin: 0;
    }

    .client-contract-line {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 8px;
    }

    .contract-inline-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 27px;
        padding: 5px 10px;
        border: 1px solid transparent;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        text-decoration: none !important;
        white-space: nowrap;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    a.contract-inline-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 12px rgba(22, 101, 52, .16);
    }

    .contract-inline-badge.contract-active {
        color: #087443;
        background: #e5f8ee;
        border-color: #aee7c8;
    }

    .contract-inline-badge.contract-warning {
        color: #8a5a00;
        background: #fff5d8;
        border-color: #f5d889;
    }

    .contract-inline-badge.contract-expired {
        color: #b42318;
        background: #fdebec;
        border-color: #f4b9bd;
    }

    .contract-inline-badge.contract-missing {
        color: #586b7f;
        background: #edf2f7;
        border-color: #d9e2ec;
    }

    .client-address-col .map-link-btn {
        margin-top: 4px;
    }

    .client-head .center {
        margin: 0;
        text-align: left !important;
    }

    .client-head .client-name-col .center {
        text-align: center !important;
    }

    .client-head .client-address-col .center,
    .client-head .client-contact-col .center,
    .client-head .client-data-col .center {
        padding-left: 8px;
    }

    @media (min-width: 992px) {
        .client-head,
        .client-row {
            display: grid;
            grid-template-columns: 28px 42px minmax(280px, 1.45fr) minmax(360px, 1.95fr) minmax(120px, 0.7fr) minmax(320px, 1.35fr);
            column-gap: 12px;
        }

        .client-head > *,
        .client-row > * {
            width: auto !important;
            max-width: none !important;
            min-width: 0;
        }

        .client-check-col { grid-column: 1; }
        .client-status-col { grid-column: 2; }
        .client-name-col { grid-column: 3; }
        .client-address-col { grid-column: 4; }
        .client-contact-col { grid-column: 5; }
        .client-data-col { grid-column: 6; }

        .client-head {
            align-items: center;
        }

        .client-head .client-name-col .center,
        .client-head .client-address-col .center,
        .client-head .client-contact-col .center,
        .client-head .client-data-col .center {
            text-align: center !important;
            padding-left: 0;
        }

        .client-name-col,
        .client-address-col,
        .client-contact-col,
        .client-data-col {
            padding-left: 0;
            padding-right: 0;
        }

        .client-address-col {
            text-align: center;
        }

        .client-contact-col {
            text-align: center;
        }

        .client-data-col {
            text-align: left;
        }

        .client-contact-col p,
        .client-contact-col a {
            text-align: center;
            justify-content: center;
        }
    }

    @media (max-width: 991.98px) {
        .client-row {
            padding: 10px 6px;
        }

        .client-check-col,
        .client-status-col {
            width: auto;
        }

        .client-name-col,
        .client-address-col,
        .client-contact-col,
        .client-data-col {
            padding: 6px 2px;
        }
    }
</style>
<script>
function mkaShowLastUpdateDetails(link) {
    var wrap = link.closest('.last-update-audit-wrap');
    var popover = wrap ? wrap.querySelector('.last-update-popover') : null;
    if (!popover) return false;
    document.querySelectorAll('.last-update-popover:not([hidden])').forEach(function (item) {
        if (item !== popover) item.hidden = true;
    });
    popover.hidden = !popover.hidden;
    return false;
}

document.addEventListener('click', function (event) {
    if (!event.target.closest('.last-update-audit-wrap')) {
        document.querySelectorAll('.last-update-popover:not([hidden])').forEach(function (item) {
            item.hidden = true;
        });
    }
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

// Paginação
$limite = mysqli_num_rows($result_limit);
$tot_paginas = $tot_resultados / $registros_por_pagina;

// agora vamos criar os botões "Anterior e próximo"
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

<div class='row text-light text-center client-head'>
    <div class='col-auto no_print client-check-col'>
        <input type='checkbox' name='selectall' id='selectall' />
    </div>
    <div class='col-auto client-status-col'> </div>
    <div class='col-12 col-md-3 client-name-col'>

        <?php
        if ($organizar == 'nome') {
        ?>
            <a class="link-light" href='?busca=<?= $busca2; ?>&organizar=nome DESC&num_registros=<?= $registros_por_pagina; ?>&pagina=<?= $pc; ?>'>
            <p style="text-align:right" class='center'>Nome Completo</p>
            </a>
        <?php
        } else {

        ?>
            <a class="link-light" href='?busca=<?= $busca2; ?>&organizar=nome&num_registros=<?= $registros_por_pagina; ?>&pagina=<?= $pc; ?>'>
            <p style="text-align:right" class='center'>Nome Completo</p>
            </a>
        <?php
        }

        ?>

    </div>

    <div class='col-12 col-md-4 client-address-col'>
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
    <div class='col-12 col-md-2 client-contact-col'>
        <p class='center'>Contato</p>
    </div>
    <div class='col-12 col-md-3 client-data-col'>
        <p class='center'>Dados da Conexão</p>
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

    $query_nas_map = mysqli_query($link, "SELECT nasname, shortname FROM nas");
    if ($query_nas_map) {
        while ($nas_map = mysqli_fetch_assoc($query_nas_map)) {
            $nas_label = !empty($nas_map['shortname']) ? $nas_map['shortname'] : $nas_map['nasname'];
            if (!empty($nas_map['nasname'])) {
                $nas_nome[$nas_map['nasname']] = $nas_label;
            }
        }
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
        $nas_nome[$ramal_ip2] = !empty($nas['shortname']) ? $nas['shortname'] : $nas['nasname'];
        if (!empty($nas['nasipaddress'])) {
            $nas_nome[$nas['nasipaddress']] = $nas_nome[$ramal_ip2];
        }
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

$tit = array();
$qTitulos = mysqli_query($link, "SELECT l.login FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login = c.login WHERE l.status NOT LIKE 'pago' AND l.deltitulo = 0 AND l.datavenc <= '$now' AND c.cli_ativado = 's'");
while ($row = mysqli_fetch_assoc($qTitulos)) {
    $titulo_login = strtolower(trim($row['login']));
    if ($titulo_login !== '') {
        $tit[$titulo_login] = isset($tit[$titulo_login]) ? $tit[$titulo_login] + 1 : 1;
    }
}

// arsort($tit);
// debug($tit);

?>

<div id="trafficMonitorModal" class="traffic-monitor-modal" onclick="if(event.target === this){ closeTrafficModal(); }">
    <div class="traffic-monitor-dialog">
        <div class="traffic-monitor-header">
            <div>
                <p id="trafficMonitorClient" class="traffic-monitor-title">Monitor de tráfego</p>
                <p id="trafficMonitorPppoe" class="traffic-monitor-subtitle">PPPoE monitorado</p>
            </div>
            <button type="button" class="traffic-monitor-close" onclick="closeTrafficModal()" aria-label="Fechar monitor">X</button>
        </div>
        <iframe id="trafficMonitorFrame" class="traffic-monitor-frame" src="about:blank" loading="lazy"></iframe>
    </div>
</div>

<form method='POST' action='' target='_blank'>

    <?php

    $cont = 1;

    while ($row = mysqli_fetch_assoc($result_limit)) {
        $nome_cliente = $row['nome'];
        $nome_cliente_res = $row['nome_res'];
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
        $titulo_login_key = strtolower(trim($login_cliente));
        $quantidade_titulos_vencidos = isset($tit[$titulo_login_key]) ? (int) $tit[$titulo_login_key] : 0;
        $tem_titulo_vencido = $quantidade_titulos_vencidos > 0;
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

        $cli_ativado = $row['cli_ativado'];

        


        $last_update = $row['last_update'];
        $uuid_cliente = $row['uuid_cliente'];
        $switch_cliente = $row['switch'];
        $loc_cliente = $row['coordenadas'];
        $cli_email = $row['email'];
        $cli_parc_abertas = $row['parc_abertas'];
        $cli_tit_abertos = $row['tit_abertos'];
        $parcelas_validas = ($cli_parc_abertas >= 0 && $cli_parc_abertas <= 24)
            && ($cli_tit_abertos >= 0 && $cli_tit_abertos <= 24);
        $num_parcelas = $parcelas_validas ? (int) max($cli_parc_abertas, $cli_tit_abertos) : 0;
        $carne_terminando = $parcelas_validas && $num_parcelas >= 1 && $num_parcelas <= 3;
        $sem_carne = $parcelas_validas && $num_parcelas === 0;

        $contract_row = mka_contract_get_latest($link, $uuid_cliente, $login_cliente);
        if (!$contract_row) {
            // Usa a mesma origem da tela de Contratos: PDFs assinados pelo MK-AUTH.
            $contract_row = mka_contract_get_native_signed($uuid_cliente);
        }
        $contract_status = mka_contract_build_status($contract_row);
        $contract_inline = mka_contract_render_inline($contract_status);

        $caixa_herm = $row['caixa_herm'];
        $porta_splitter = $row['porta_splitter'];

        $cpf_cnpj_fmt = trim((string) $cpf_cnpj_cliente) !== '' ? $cpf_cnpj_cliente : '-';
        $email_fmt = trim((string) $cli_email) !== '' ? $cli_email : '-';
        $data_cad_fmt = (!empty($data_cad) && strtotime($data_cad)) ? date('d/m/Y - H:i:s', strtotime($data_cad)) : '-';
        $last_update_fmt = (!empty($last_update) && strtotime($last_update)) ? date('d/m/Y - H:i:s', strtotime($last_update)) : '-';
        $last_update_user = '';
        foreach (array('last_update_user', 'usuario_alteracao', 'ultima_alteracao_usuario', 'alterado_por', 'user_update', 'last_user', 'usuario') as $update_user_field) {
            if (!empty($row[$update_user_field])) {
                $last_update_user = trim((string) $row[$update_user_field]);
                break;
            }
        }
        $last_update_details = isset($row['last_update_details']) ? trim((string) $row['last_update_details']) : '';
        $last_update_display = $last_update_fmt;
        $venc_cliente_fmt = trim((string) $venc_cliente) !== '' ? $venc_cliente : '-';

        $color = $bloqueado == "sim" ? "bloqueado" : ($observacao == "sim" ? "observacao" : "");

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
            $showScore = "<span class='client-score is-critical' title='Score crítico'>$score</span>";
        } else if ($score >= ($max_score / 3) && $score < ($max_score / 2.5)) {
            $showScore = "<span class='client-score is-low' title='Score baixo'>$score</span>";
        } else if ($score >= ($max_score / 2.5) && $score < ($max_score / 2)) {
            $showScore = "<span class='client-score is-attention' title='Score em atenção'>$score</span>";
        } else if ($score >= ($max_score / 2) && $score < ($max_score / 1.4)) {
            $showScore = "<span class='client-score is-regular' title='Score regular'>$score</span>";
        } else if ($score >= ($max_score / 1.4) && $score < ($max_score / 1.05)) {
            $showScore = "<span class='client-score is-good' title='Score bom'>$score</span>";
        } else {
            $showScore = "<span class='client-score is-excellent' title='Score excelente'>$score <i class='bi bi-award-fill client-score-trophy' aria-hidden='true'></i></span>";
        }


        // The end implementation for Score

        $bgColor = $cont % 2 == 0 ? "bg-body-secondary" : "bg-light";
        //echo $bgColor;

    ?>

        <div class='row client-row small <?= $bgColor; ?>'>

            <div class='col-auto client-check-col'>
                <input type='checkbox' name='login[]' class='login_select' value='<?= $login_cliente; ?>'>
            </div>

            <?php

            $query_cli_chamados = mysqli_query($link, "SELECT status FROM sis_suporte WHERE login LIKE '$login_cliente' AND status = 'aberto'");

            $num_chamados_aberto = mysqli_num_rows($query_cli_chamados);

            if ($cli_ativado == "n") {
                if ($data_desativado != '') {
                    $data_desativado = date('d/m/Y - H:i:s', strtotime($data_desativado));
                    $dataRenovacao = date('Y-m-d', strtotime('+1 year', strtotime($data_desativado))); // Usa cli_ativado como base para a renovação
                }
            ?>

                <div class='col-auto client-status-col'>
                    <span class="client-status-badge is-disabled" title="Cliente desativado">
                        <i class="fa-solid fa-user-xmark fs-5"></i>
                    </span>
                </div>
                <div class='col-12 col-md-3 client-name-col'>
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
                            <div class='col-auto client-status-col'>
                                <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>' target='_blank' class="client-status-badge is-locked-online" title="Cliente online e bloqueado">
                                    <i class="fa-solid fa-user-lock fs-5"></i>
                                </a>
                            <?php
                        } elseif ($observacao == "sim") {
                            ?>
                            <div class='col-auto client-status-col'>
                                <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>' target='_blank' class="client-status-badge is-observation-online" title="Cliente online em observação">
                                    <i class="fa-solid fa-user-check fs-5"></i>
                                    <i class="fa-solid fa-lock-open status-lock-overlay"></i>
                                    <i class="fa-solid fa-clock status-clock-overlay"></i>
                                </a>
                            <?php
                        } else {
                            ?>

                                <div class='col-auto client-status-col'>
                                    <a href='http://<?= "{$ip_conn[strtolower($login_cliente)]}:{$porta_acesso}"; ?>' target='_blank' class="client-status-badge" title="Cliente online">
                                        <i class="fa-solid fa-user-check fs-5"></i>
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

                            if ($tem_titulo_vencido || $carne_terminando || $sem_carne || !$parcelas_validas) {
                                ?>
                                <div class="billing-alert-stack">
                                <?php if ($tem_titulo_vencido) { ?>
                                    <a class="overdue-title-action" href='../../cliente_det.<?= $links_ext ?>?uuid=<?= $uuid_cliente; ?>' title='Ver títulos vencidos no financeiro de <?= $nome_cliente; ?>'>
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                        <?= $quantidade_titulos_vencidos; ?>
                                    </a>
                                <?php } ?>
                                <?php if ($carne_terminando) { ?>
                                    <span class="ending-booklet-alert" title="Restam <?= $num_parcelas; ?> títulos no carnê de <?= $nome_cliente; ?>">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span class="remaining-title-count"><?= $num_parcelas; ?></span>
                                    </span>
                                <?php } elseif ($sem_carne) { ?>
                                    <span class="no-booklet-alert" title="O cliente não tem carnê">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                    </span>
                                <?php } elseif (!$parcelas_validas) { ?>
                                    <span class="invalid-booklet-alert" title="Número de parcelas inválido">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                    </span>
                                <?php } ?>
                                </div>
                                <?php
                            }
                                ?>
                                </div>

                                <?php
                                if ($bloqueado == "sim") {
                                ?>
                                    <div class='col-12 col-md-3 client-name-col'>
                                                 <p class='bloqueado'>
                                            <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>

                                        </p>

                                        <p class='info_add'><b>Bloqueado em:</b> <?= $data_bloq; ?></p>
                                    <?php
                                } elseif ($observacao == "sim") {
                                    ?>
                                        <div class='col-12 col-md-3 client-name-col'>

                                            <p class='observacao' style='color:#d4a017;'>
                                                <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                            </p>

                                            <p class='info_add'><b>Obs será removida em:</b> <?= $obs_data; ?></p>

                                        <?php
                                    } else {
                                        ?>
                                            <div class='col-12 col-md-3 client-name-col'>

                                                <p><a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a></p>

                                            <?php
                                        }
                                    } else {

                                        $query_cliente_off = mysqli_query($link, "SELECT DISTINCT acctstoptime FROM radacct WHERE username = '$login_cliente' ORDER BY acctstoptime DESC");

                                        $row3 = mysqli_fetch_row($query_cliente_off);
                                        $final_conn = date('d/m/Y - H:i:s', strtotime($row3[0]));

                                        if ($bloqueado == "sim") {
                                            ?>
                                                <div class='col-auto client-status-col'>
                                                    <span class="client-status-badge is-locked-offline" title="Cliente offline e bloqueado">
                                                        <i class="fa-solid fa-user-slash fs-5"></i>
                                                        <i class="fa-solid fa-lock status-lock-overlay"></i>
                                                    </span>
                                                <?php
                                            } elseif ($observacao == "sim") {
                                                ?>
                                                    <div class='col-auto client-status-col'>
                                                        <span class="client-status-badge is-observation-offline" title="Cliente offline em observação">
                                                            <i class="fa-solid fa-user-check fs-5"></i>
                                                            <i class="fa-solid fa-lock-open status-lock-overlay"></i>
                                                            <i class="fa-solid fa-clock status-clock-overlay"></i>
                                                        </span>
                                                    <?php
                                            } else {
                                                ?>
                                                    <div class='col-auto client-status-col'>
                                                        <span class="client-status-badge is-offline" title="Cliente offline">
                                                            <i class="fa-solid fa-user-slash fs-5"></i>
                                                        </span>
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

                                                if ($tem_titulo_vencido || $carne_terminando || $sem_carne || !$parcelas_validas) {
                                                    ?>
                                                    <div class="billing-alert-stack">
                                                    <?php if ($tem_titulo_vencido) { ?>
                                                        <a class="overdue-title-action" href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='Ver títulos vencidos no financeiro de <?= $nome_cliente; ?>'>
                                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                                            <?= $quantidade_titulos_vencidos; ?>
                                                        </a>
                                                    <?php } ?>
                                                    <?php if ($carne_terminando) { ?>
                                                        <span class="ending-booklet-alert" title="Restam <?= $num_parcelas; ?> títulos no carnê de <?= $nome_cliente; ?>">
                                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                                            <span class="remaining-title-count"><?= $num_parcelas; ?></span>
                                                        </span>
                                                    <?php } elseif ($sem_carne) { ?>
                                                        <span class="no-booklet-alert" title="O cliente não tem carnê">
                                                            <i class="fa-solid fa-circle-exclamation"></i>
                                                        </span>
                                                    <?php } elseif (!$parcelas_validas) { ?>
                                                        <span class="invalid-booklet-alert" title="Número de parcelas inválido">
                                                            <i class="fa-solid fa-circle-exclamation"></i>
                                                        </span>
                                                    <?php } ?>
                                                    </div>
                                                    <?php
                                                }
                                                    ?>
                                                    </div>

                                                    <?php
                                                    if ($bloqueado == "sim") {
                                                    ?>
                                                        <div class='col-12 col-md-3 client-name-col'>
                                                            <p class='bloqueado'>
                                                                <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                                            </p>

                                                            <p class='final_conn no_print'><b>Caiu em:</b> <?= $final_conn; ?></p>

                                                            <p class='info_add'><b>Bloqueado em:</b> <?= $data_bloq; ?></p>
                                                        <?php
                                                    } elseif ($observacao == "sim") {
                                                        ?>
                                                            <div class='col-12 col-md-3 client-name-col'>

                                                            <p class='observacao' style='color:#d4a017;'>
                                                                    <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                                                </p>

                                                                <p class='final_conn'><b>Caiu em:</b> <?= $final_conn; ?></p>

                                                                <p class='info_add'><b>Obs será removida em:</b> <?= $obs_data; ?></p>

                                                            <?php
                                                        } else {
                                                            ?>
                                                                <div class='col-12 col-md-3 client-name-col'>

                                                                    <p>
                                                                        <a href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='VER CLIENTE: <?= $nome_cliente; ?>'><?= $nome_cliente; ?></a>
                                                                    </p>

                                                                    <p class='final_conn'><b>Caiu em:</b> <?= $final_conn; ?></p>
                                                            <?php
                                                        }
                                                    }
                                                }
                                                ?>

                                                            <div class='client-meta-stack'>
                                                                <p class='info_add'><b>CPF/CNPJ:</b> <?= $cpf_cnpj_fmt; ?></p>
                                                                <p class='info_add'><b>E-mail:</b> <?= $email_fmt; ?></p>
                                                                <p class='info_add'><b>Data cadastro:</b> <?= $data_cad_fmt; ?></p>
                                                                <p class='info_add'><b>Última alteração:</b> <?= $last_update_display; ?><?php if ($last_update_user !== '') { ?> por <span class="last-update-audit-wrap"><a href="#" class="last-update-user-link" onclick="return mkaShowLastUpdateDetails(this);" title="Ver o que foi alterado"><?= htmlspecialchars($last_update_user, ENT_QUOTES, 'UTF-8'); ?></a><span class="last-update-popover" hidden><b>Alterações realizadas</b><br><?= htmlspecialchars($last_update_details !== '' ? $last_update_details : 'Detalhes não registrados para esta alteração.', ENT_QUOTES, 'UTF-8'); ?></span></span><?php } ?></p>
                                                                <p class='info_add'><b>Vencimento da fatura:</b> <?= $venc_cliente_fmt; ?></p>
                                                            </div>

                                                        <div class='op_cliente no_print client-action-toolbar'>
                                                            <a class="client-action-btn has-counter<?= $tem_titulo_vencido ? ' is-danger' : ''; ?>" href='../../cliente_det.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='Número de parcelas em aberto: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-file-invoice"></i>
                                                                <span class="badge-parcelas-inline" title="Número de parcelas em aberto"><?= $num_parcelas; ?></span>
                                                            </a>
                                                            <a class="client-action-btn" href='../../cliente_alt.<?= $links_ext; ?>?uuid=<?= $uuid_cliente; ?>' title='ALTERAR CLIENTE: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-user-pen"></i>
                                                            </a>
                                                            <a class="client-action-btn" href='det_conn.php?login=<?= $login_cliente; ?>' title='CONEXÕES CLIENTE: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-router"></i>
                                                            </a>
                                                            <a class="client-action-btn" href='../../suporte_ins.<?= $links_ext; ?>?login=<?= $login_cliente; ?>' title='VER CHAMADOS: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-headset"></i>
                                                            </a>
<?php if ($bloqueado == "sim") { ?>
                                                            <a class="client-action-btn is-warning" href='#' onclick="abrirJanela('teste_obs.php?login=<?= $login_cliente; ?>', 560, 640); return false;" title="Desbloqueio de Confianca x dias">
                                                                <i class="fa-solid fa-lock-open"></i>
                                                            </a>
                                                            <?php } ?>
                                                            <a class="client-action-btn" href='#' onclick="javascript:abrirJanela('../../cliente_info.<?= $links_ext; ?>?cliente=<?= $uuid_cliente; ?>', 600, 900); return false;" title='DETALHES CLIENTE: <?= $nome_cliente; ?>'>
                                                                <i class="fa-solid fa-circle-info"></i>
                                                            </a>
                                                            <a class="client-action-btn" href='#' onclick="abrirJanela('contrato_popup.php?uuid=<?= urlencode($uuid_cliente); ?>&login=<?= urlencode($login_cliente); ?>&nome=<?= urlencode($nome_cliente); ?>', 620, 760); return false;" title="Ativar ou renovar contrato">
                                                                <i class="fa-solid fa-file-signature"></i>
                                                            </a>
                                                            <?php if ($cli_email != '') { ?>
                                                                <a class="client-action-btn" href='mailto:<?= $cli_email; ?>' title='Enviar e-mail para <?= $nome_cliente; ?>'>
                                                                    <i class="fa-solid fa-envelope"></i>
                                                                </a>
                                                            <?php } ?>
                                                            <?php if (!empty($nas_ip[strtolower($login_cliente)])) { ?>
                                                                <a class="client-action-btn is-success" href='#' onclick='return openTrafficModal(<?= json_encode($login_cliente); ?>, <?= json_encode($nas_ip[strtolower($login_cliente)]); ?>, <?= json_encode($nome_cliente); ?>, <?= json_encode($plano_cliente); ?>, <?= json_encode(isset($nas_nome[$nas_ip[strtolower($login_cliente)]]) ? $nas_nome[$nas_ip[strtolower($login_cliente)]] : ""); ?>);' title='Tráfego em tempo real'>
                                                                    <i class="fa-solid fa-chart-line"></i>
                                                                </a>
                                                            <?php } ?>
                                                        </div>

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

                                                                    $conn_add = " <a class='client-action-btn' href='det_conn.php?login=$username_cli_adicional' title='CONEXOES CLIENTE: $nome_cliente'>
                                                                        <i class='fa-solid fa-router'></i>
                                                                         </a> ";

                                                                    if (strcasecmp(strtolower($add_online), strtolower($username_cli_adicional)) == 0) {
                                                            ?>
                                                                    <a class='client-add-status is-online' href='../../adicional_alt.<?= $links_ext; ?>?uuid=<?= $uuid_cli_adicional; ?>' title='VER ADICIONAL: <?= $nome_cli_adicional; ?>'>
                                                                        <i class="fa-solid fa-circle-check"></i><?= $username_cli_adicional; ?></a>


                                                                <?php
                                                                    } else {
                                                                ?>
                                                                    <a class='client-add-status is-offline' href='../../adicional_alt.<?= $links_ext; ?>?uuid=<?= $uuid_cli_adicional; ?>' title='VER ADICIONAL: <?= $nome_cli_adicional; ?>'>
                                                                        <i class="fa-solid fa-circle-minus"></i><?= $username_cli_adicional; ?></a> <?= $conn_add; ?>
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

                                                                <div class='col-12 col-md-4 client-address-col text-center'>
                                                                    <p class=''>
                                                                        <?= $end_cliente; ?> <b>nº</b> <?= $numero_casa; ?>

                                                                        <?= $bairro_cliente; ?>
                                                                        <?= $complemento_cliente; ?>
                                                                        <span class='no_print'><?= $cidade_cliente; ?> / <?= $uf_cliente; ?></span><br>

                                                                        <?php
                                                                        echo "<span class='client-contract-line'><span><b>Score:</b> {$showScore}</span><span>{$contract_inline}</span></span>";

                                                                        $loc_cliente_limpo = trim((string) $loc_cliente);
                                                                        $mapa_link = '';
                                                                        if ($loc_cliente_limpo !== '' && preg_match('/^\s*-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?\s*$/', $loc_cliente_limpo)) {
                                                                            $mapa_link = 'https://www.google.com/maps?q=' . rawurlencode($loc_cliente_limpo);
                                                                        } else {
                                                                            $endereco_mapa = array_filter(array(
                                                                                trim((string) $end_cliente),
                                                                                trim((string) $numero_casa),
                                                                                trim((string) $bairro_cliente),
                                                                                trim((string) $cidade_cliente),
                                                                                trim((string) $uf_cliente)
                                                                            ));
                                                                            if (!empty($endereco_mapa)) {
                                                                                $mapa_link = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(implode(', ', $endereco_mapa));
                                                                            }
                                                                        }

                                                                        ?>
                                                                    </p>
                                                                    <?php if ($mapa_link != '') { ?>
                                                                        <a class="map-link-btn no_print" href="<?= $mapa_link; ?>" target="_blank" rel="noopener noreferrer" title="Abrir localização no Google Maps">
                                                                            <i class="fa-solid fa-location-dot"></i> Localização no mapa
                                                                        </a>
                                                                    <?php } ?>
                                                                </div>

                                                                <!-- <hr class="d-block d-sm-none"> -->

                                                                <div class='col-12 col-md-2 client-contact-col'>
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
                                                                <div class='col-12 col-md-3 client-data-col'>                                                              
                                                                    <p class='dados_cliente'>
                                                                    <b>Nome Resumido:</b> <span style="color: blue;"><?= $nome_cliente_res; ?></span>

                                                                    </p>
                                                                      <p class='dados_cliente'>                                                                   
                                                                        <b>Login:</b> <?= $login_cliente; ?> /
                                                                        <b>Senha:</b> <?= $senha_cliente; ?> 
                                                                    </p>

                                                                    <p class='dados_cliente <?= $color ?>'>

                                                                    <?php
                                                                        if ($observacao == "sim" && !empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>
                                                                               <span class="bg-warning text-dark px-1 py-0 rounded-pill small2">
                                                                                <b>IP:</b> <?= $ip_conn[strtolower($login_cliente)]; ?>
                                                                                OBSERVACAO </span>

                                                                        <?php
                                                                        }
                                                                        else if ($bloqueado == "nao" && !empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>
<span class="bg-success text-white px-1 py-0 rounded-pill small2">
                                                                                <b>IP:</b> <?= $ip_conn[strtolower($login_cliente)]; ?>
                                                                                ONLINE </span>



                                                                        <?php
                                                                        } else if ($bloqueado == "sim" && !empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>

                                                                            <span class="bg-success text-white px-1 py-0 rounded-pill small2">
                                                                                <b>IP:</b> <?= $ip_conn[strtolower($login_cliente)]; ?>
                                                                                BLOQUEADO </span>

                                                                        <?php
                                                                        } else if (empty($ip_conn[strtolower($login_cliente)])) {
                                                                        ?>
                                                                            <span class="bg-danger text-white px-1 py-0 rounded-pill small2" style="background-color: #575656 !important;">IP: OFFLINE</span>

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
