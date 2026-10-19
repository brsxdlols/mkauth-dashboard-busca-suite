<!-- 
    BUSCA INTELIGENTE
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php require_once('config.php'); require_once('offline-filters.php'); require_once __DIR__.'/offline-navigation.php'; require_once('offline-settings.php'); require_once('offline-tickets.php'); require_once('offline-disconnect.php'); $off_display = off_display_settings($link); $off_fragment = off_arg('_fragment') === '1'; if ($off_fragment && session_status() === PHP_SESSION_ACTIVE) session_write_close(); ?>

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

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

    <link href="/admin/addons/rel_cli_offline/css/css.css" rel="stylesheet" type="text/css" /><link href="/admin/addons/rel_cli_offline/offline-clean.css?v=20261017-2" rel="stylesheet" />


<link rel="stylesheet" href="/admin/estilos/bi-icons.css"></head>

<body>
    <?php

    if (!$off_fragment) {
    $create_table_busca_inteligente_cfg = mysqli_query($link, "CREATE TABLE IF NOT EXISTS sis_rel_con_ms_cfg (
        id int NOT NULL AUTO_INCREMENT,
        mail_host VARCHAR(100) DEFAULT NULL,
        mail_username VARCHAR(100) DEFAULT NULL,
        mail_password VARCHAR(50) DEFAULT NULL,
        mail_port VARCHAR(5) DEFAULT '587',
        link_logo VARCHAR(200) DEFAULT 'https://',
        PRIMARY KEY (id)
    )");

    $verifica_tabela_vazia = mysqli_query($link, "SELECT * FROM sis_rel_con_ms_cfg");
    if (mysqli_num_rows($verifica_tabela_vazia) == 0) {
        $ins_table_busca_inteligente_cfg = mysqli_query($link, "INSERT INTO sis_rel_con_ms_cfg VALUES (1, 'smtp.com.br', 'meuemail@email.com.br', '', '587', 'http://')");
        if (!$ins_table_busca_inteligente_cfg) {
            echo mysqli_error($link);
        }
    }

    }
    $data_atual2 = date('Y-m-d H:i:s');

    $busca = off_arg('busca');

    $organizar = off_arg('organizar', 'c.acctstoptime ASC');

    $lista_organizar = array(
        "c.acctstoptime DESC" => "Últimas desconexões",
        "c.acctstoptime ASC" => "Maior tempo offline",
        "cli.nome" => "Nome",
        "cli.endereco" => "Endereço",
        "cli.cidade" => "Cidade",
        "reconexoes_desc" => "Mais reconexões (24h)",
        "chamados_desc" => "Mais chamados antes da queda",
    );
    if (!isset($lista_organizar[$organizar])) $organizar = 'c.acctstoptime ASC';
    $offSqlOrder = in_array($organizar, array('reconexoes_desc','chamados_desc'), true) ? 'c.acctstoptime ASC' : $organizar;
    $offReconFilter = off_arg('reconexoes','todos'); $offTicketFilter = off_arg('chamados','todos');
    if (!in_array($offReconFilter,array('todos','com','sem'),true)) $offReconFilter='todos';
    if (!in_array($offTicketFilter,array('todos','com','sem'),true)) $offTicketFilter='todos';
    ?>

    <?php
    if (!$off_fragment) { include('../../topo.php'); off_suite_navigation($link, $usuario_logado); }

    ?>





    <?php

    $status_cap = off_arg('status', '%'); if (!in_array($status_cap, array('%','nao','sim'), true)) $status_cap = '%';
    $offWhere = off_period_sql($link);
    $offTerms = preg_split('/\s+/u', $busca, -1, PREG_SPLIT_NO_EMPTY); $offParts = array();
    foreach ($offTerms as $term) {
        $term = mysqli_real_escape_string($link, str_replace(array('!', '%', '_'), array('!!','!%','!_'), $term));
        $columns = array('c.username','cli.nome','cli.endereco','cli.bairro','cli.cidade'); $matches = array();
        foreach ($columns as $column) $matches[] = "$column LIKE '%$term%' ESCAPE '!'";
        $offParts[] = '('.implode(' OR ', $matches).')';
    }
    $offSearch = $offParts ? implode(' AND ', $offParts) : '1=1';


    $query = "SELECT c.username, c.acctstoptime, cli.uuid_cliente, cli.coordenadas, cli.caixa_herm, cli.porta_splitter, cli.porta_olt, cli.nome, cli.endereco, cli.bairro, cli.cidade, cli.estado, cli.bloqueado, cli.data_bloq, TIMESTAMPDIFF(SECOND, c.acctstoptime, NOW()) AS offline_seconds
    FROM sis_cliente cli
    INNER JOIN radacct c ON c.radacctid = (SELECT recent.radacctid FROM radacct recent WHERE recent.username = cli.login ORDER BY recent.radacctid DESC LIMIT 1)
    LEFT JOIN (SELECT DISTINCT username FROM radacct WHERE acctstoptime IS NULL) live ON live.username=cli.login
    WHERE 
    $offSearch AND
    cli.cli_ativado = 's' AND
    live.username IS NULL
    
    AND c.acctstoptime IS NOT NULL AND c.acctstoptime <= NOW()
    $offWhere
    ORDER BY $offSqlOrder, c.username";

    $result = mysqli_query($link, "$query");
    //$result_limit = mysqli_query($link, "$query LIMIT $inicio,$registros_por_pagina");


    if (!$result) {
        error_log(mysqli_error($link)); echo '<p role="alert">Não foi possível consultar o relatório.</p>'; exit;
    }

    $offTotals = array('nao'=>0,'sim'=>0); $offRows = array();
    while ($item = mysqli_fetch_assoc($result)) {
        $offTotals[$item['bloqueado'] === 'nao' ? 'nao' : 'sim']++;
        $offRows[] = $item;
    }
    ?>

    <?php $offTickets = off_ticket_summaries($link, $offRows); $offDisconnect = off_disconnect_summaries($link, $offRows);
    $offReconnectCounts = off_reconnections($link, $offRows);
    $offFilteredRows = array(); $offTotals = array('nao'=>0,'sim'=>0);
    foreach ($offRows as $offItem) {
        $offLogin = $offItem['username'];
        $offItem['_reconnections'] = $offReconnectCounts[$offLogin] ?? null;
        $offDisconnect[$offLogin]['reconnections'] = $offItem['_reconnections'];
        $offItem['_recent_calls'] = isset($offTickets[$offLogin]) && $offTickets[$offLogin]['state'] === 'ok' ? (int)$offTickets[$offLogin]['recent'] : null;
        if (!off_metric_matches($offItem['_reconnections'],$offReconFilter) || !off_metric_matches($offItem['_recent_calls'],$offTicketFilter)) continue;
        $offFilteredRows[] = $offItem; $offTotals[$offItem['bloqueado'] === 'nao' ? 'nao' : 'sim']++;
    }
    $offRows = $offFilteredRows;
    if ($organizar === 'reconexoes_desc') $offRows = off_metric_order($offRows,'_reconnections');
    if ($organizar === 'chamados_desc') $offRows = off_metric_order($offRows,'_recent_calls');
    ?>
    <main id="offline-report"><header class="off-heading"><div><span>RELATÓRIO DE CONEXÕES</span><h1>Clientes offline</h1><p>Encontre quem desconectou por região e período.</p></div><div class="off-tools no_print"><a href="<?= off_h(off_report_route(true)) ?>">Configurações</a><button type="button" onclick="window.print()">Imprimir</button></div></header>
    <p class="off-alert-caption"><?php if ($off_display['enabled']): ?>Destaque vermelho: offline há <?= off_h($off_display['hours']) ?> horas ou mais.<?php else: ?>Destaque vermelho por tempo desativado.<?php endif; ?> <a href="<?= off_h(off_report_route(true)) ?>">Configurar destaque</a></p>
    <div id="off-query-error"><?php if ($off_error !== '') echo '<p class="off-error" role="alert">'.off_h($off_error).'</p>'; ?></div>
    <div class="off-cards"><?php foreach(array('%'=>array('Clientes encontrados', $offTotals['nao']+$offTotals['sim']), 'nao'=>array('Ativos e offline',$offTotals['nao']), 'sim'=>array('Bloqueados e offline',$offTotals['sim'])) as $cardStatus=>$card): ?><a class="off-card <?= $status_cap === $cardStatus ? 'is-selected' : '' ?>" href="<?= off_h(off_card_url($cardStatus)) ?>" data-off-status="<?= off_h($cardStatus) ?>" aria-current="<?= $status_cap === $cardStatus ? 'true' : 'false' ?>"><span><?= $card[0] ?></span><strong><?= $card[1] ?></strong></a><?php endforeach; ?></div>
    <form action="" method="get" class="off-filters">
        <div class="off-search-grid no_print">
        <label for="pesquisar">Pesquisar<input type="search" id="pesquisar" name="busca" placeholder="Nome, login, rua, bairro ou cidade" list="off-suggestions" value="<?= off_h($busca) ?>"></label>
        <label>Status<select name="status"><?php foreach(array('%'=>'Todos','nao'=>'Ativo','sim'=>'Bloqueado') as $key=>$label): ?><option value="<?= off_h($key) ?>" <?= $status_cap === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <label>Organizar por<select name="organizar"><?php foreach($lista_organizar as $key=>$label): ?><option value="<?= $key ?>" <?= $organizar === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        </div>
    <div class="off-extra-filters no_print"><label>Reconexões nas 24h antes da queda<select name="reconexoes"><?php foreach(array('todos'=>'Todas','com'=>'Com reconexões (mais de 0)','sem'=>'Sem reconexões registradas') as $key=>$label): ?><option value="<?= $key ?>" <?= $offReconFilter === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label><label>Chamados nos 30 dias antes da queda<select name="chamados"><?php foreach(array('todos'=>'Todos','com'=>'Com chamados no período','sem'=>'Sem chamados no período') as $key=>$label): ?><option value="<?= $key ?>" <?= $offTicketFilter === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label></div>
    <?php off_filter_form(); ?>
    </form>
    <p id="off-live-status" class="off-live-status no_print" role="status" aria-live="polite"></p>
    <div id="off-results">
    <form action="../../reparar<?php echo $ext_mk; ?>" method='post'>

    <div class="off-map-actions no_print" data-off-map-action="/admin/addons/rel_cli_offline/mapa.php"><button type="button" data-off-select-all>Selecionar todos</button><button type="button" data-off-map-selected disabled>Mostrar selecionados no mapa</button><span data-off-selection role="status" aria-live="polite">0 selecionados</span></div>
    <div class='off-table-scroll'><table id='lista' class='width-100'>




    <tr class='linha_titulo'>
    <td><input type='checkbox' id='selecctall' aria-label='Selecionar todos os clientes da lista'/></td>
    <td>NOME - [LOGIN]</td>
    <td>ENDEREÇO</td>
    <td>BAIRRO</td>
    <td>CIDADE / UF</td>
    <td>DESCONECTOU EM</td><td>TEMPO OFFLINE</td><td class="no_print">MAPA</td>
    </tr>

    <?php

    $email_ok = 0; $cc_erro_email = 0; $off_suggestions = array();
    //$i = 0;
    foreach ($offRows as $row) {
        $final_conn = $row['acctstoptime'];
        $off_row_alert = off_display_alert($row['offline_seconds'], $off_display) ? ' off-time-alert' : '';
        foreach (array('nome','endereco','bairro','cidade') as $field) if (!empty($row[$field])) $off_suggestions[$row[$field]] = true;
        $username = off_h($row['username']);
        $offPair = off_coordinate_pair($row['coordenadas']);
        $offHasCoords = $offPair !== null ? '1' : '0';
        $offLat = $offPair !== null ? $offPair[0] : ''; $offLon = $offPair !== null ? $offPair[1] : ''; $offMapName = off_h($row['nome']);
        $offMapCell = $offHasCoords === '1' ? "<button type='button' class='off-map-one' data-off-map-login='$username' data-off-map-lat='$offLat' data-off-map-lon='$offLon' data-off-map-name='$offMapName'>Ver no mapa</button>" : "<span class='off-no-coords'>Sem coordenadas</span>";

        $nome_completo = off_h($row['nome']);

        $cli_uuid = rawurlencode($row['uuid_cliente']);

        $cli_end = off_h($row['endereco']);
        $offNetwork = '<small class="off-network">CTO: '.off_h(trim((string)$row['caixa_herm']) !== '' ? $row['caixa_herm'] : 'Não informada').' · Porta CTO: '.off_h(trim((string)$row['porta_splitter']) !== '' ? $row['porta_splitter'] : 'Não informada').'</small>';
        if (trim((string)$row['porta_olt']) !== '') $offNetwork .= '<small class="off-network">Porta OLT: '.off_h($row['porta_olt']).'</small>';
        $offDiagnostic = off_disconnect_html($offDisconnect[$row['username']] ?? array('state'=>'unavailable'), $row['nome']);
        $offTicketCell = off_ticket_compact_html($offTickets[$row['username']] ?? array('state'=>'unavailable'), $row['username'], $ext_mk, $row['nome']);
        $cli_bairro = off_h($row['bairro']);
        $cli_cidade = off_h($row['cidade']);


        $cli_uf = off_h($row['estado']);

        $cli_bloqueado = $row['bloqueado'];
        $offRowHidden = $status_cap !== '%' && $cli_bloqueado !== $status_cap ? 'hidden' : '';
        $cli_data_bloq = $row['data_bloq'];


        $tempo_off = difDatas($data_atual2, $final_conn); $disconnected = date('d/m/Y H:i', strtotime($final_conn));

        if ($cli_bloqueado == 'nao') {

            $email_ok += 1;
            $send_email = "
                <tr class='linha_resultados$off_row_alert' data-off-client-status='$cli_bloqueado' $offRowHidden>
                    <td><input type='checkbox' class='checkbox1' data-off-has-coords='$offHasCoords' name='login[$username]' value='$username'></td>
    
                    <td><a href='../../cliente_det$ext_mk?uuid=$cli_uuid' target='_blank' title='Ver detalhes do cliente' rel='noopener'>$nome_completo</a><span class='off-client-login'>[$username]</span><div class='off-client-tools'>$offTicketCell$offDiagnostic</div></td>
    
                    <td>$cli_end$offNetwork</td>
                    <td>$cli_bairro</td>
                    <td>$cli_cidade / $cli_uf</td>
    
                    <td>$disconnected</td><td><span class='off-duration'>$tempo_off</span></td><td class='no_print'>$offMapCell</td>
    
                </tr>
            ";
        } else {
            $cc_erro_email += 1;
            $send_email = "
                <tr class='linha_resultados red$off_row_alert' data-off-client-status='$cli_bloqueado' $offRowHidden>
                <td><input type='checkbox' class='checkbox1' data-off-has-coords='$offHasCoords' name='login[$username]' value='$username'></td>
    
                <td><a href='../../cliente_det$ext_mk?uuid=$cli_uuid' target='_blank' title='Ver detalhes do cliente' rel='noopener' class='red'>$nome_completo</a><span class='off-client-login'>[$username]</span><div class='off-client-tools'>$offTicketCell$offDiagnostic</div></td>
    
                    <td>$cli_end$offNetwork</td>
                    <td>$cli_bairro</td>
                    <td>$cli_cidade / $cli_uf</td>
    
                    <td>$disconnected</td><td><span class='off-duration'>$tempo_off</span></td><td class='no_print'>$offMapCell</td>
                </tr>
            ";
        }

        echo "$send_email";

        //$i++;
    }

    //arsort($final_conn);

    //debug($final_conn);

    //echo "      $i<br>      ";



    /*foreach ($final_conn as $username  => $final_conexao) {

    }*/


    echo "

    </table></div>
    <input type='image' src='/admin/addons/rel_cli_offline/img/icon_menu_editar_cliente.png'  class='icon_menu' name='editar_clientes' id='btn_enviar' title='Editar os clientes selecionados' formaction='../../clientes_upd$ext_mk' />
  
    <input type='image' src='/admin/addons/rel_cli_offline/img/icon_menu_email.png'  class='icon_menu' name='mensagem_para_clientes' id='btn_enviar' title='Enviar mensagem para os clientes selecionados' formaction='../../send_cliente$ext_mk' />

    <input type='image' src='/admin/addons/rel_cli_offline/img/icon_menu_reparar_2.png' class='icon_menu' name='reparar_clientes' id='btn_enviar' title='Reparar os clientes selecionados' />

    
    </form>
    ";

    // Paginaçço
    /*$limite = mysqli_num_rows($result_limit);
    $tot_paginas = $tot_resultados / $registros_por_pagina;
        
    // agora vamos criar os botçes "Anterior e prçximo"
    $anterior = $pc -1;
    $proximo = $pc +1;*/


    if (count($offRows) === 0) echo '<p class="off-empty">Nenhum cliente offline encontrado com esses filtros.</p>';
    echo '<datalist id="off-suggestions">'; foreach (array_keys($off_suggestions) as $item) echo '<option value="'.off_h($item).'">'; echo '</datalist>';
    mysqli_close($link);




    ?>



    </div>
    <dialog id="off-map-modal" class="off-map-modal no_print" aria-labelledby="off-map-title"><header><div><h2 id="off-map-title">Localização do cliente</h2><p>Mapa de clientes · tempo desconectado e coordenadas do cadastro</p></div><button type="button" data-off-map-close aria-label="Fechar mapa">Fechar ×</button></header><iframe id="off-map-frame" title="Localização do cliente no OpenStreetMap" referrerpolicy="no-referrer" loading="lazy"></iframe><footer><a id="off-map-external" target="_blank" rel="noopener noreferrer">Abrir no OpenStreetMap</a></footer></dialog>
    <dialog id="off-analysis-modal" class="off-analysis-modal no_print" aria-labelledby="off-analysis-title"><header><div><h2 id="off-analysis-title">Análise das desconexões</h2><p data-off-analysis-client></p></div><button type="button" data-off-analysis-close>Fechar ×</button></header><div id="off-analysis-body"></div></dialog>
    </main><?php if (!$off_fragment) include('../../baixo.php'); ?>

    <script src="../../menu.js.php"></script><script src="/admin/addons/rel_cli_offline/offline-live.js?v=20261019-8"></script>

</body>

</html>