<?php
require_once __DIR__.'/installations.php';
function mka_support_navigation($db,$manifest,$operator,$active) {
    $escape=function($value){return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
    $items=array(
        array('home','index.php','bi-house-door-fill',($manifest->name??'Início').' - V '.($manifest->version??'')),
        array('alerts','cli_conn_alerta.php','bi-exclamation-circle-fill','Alertas'),
        array('attendance','atendimentos.php','bi-chat-left-text','Atendimentos'),
        array('tickets','chamados_abertos.php','bi-headset','Chamados')
    );
    if(mka_installation_permission($db,$operator,'perm_instalacao'))$items[]=array('installations','instalacoes.php','bi-tools','Instalações');
    $items[]=array('score','score.php','bi-bar-chart-fill','Score');
    $items[]=array('contracts','relcontratos.php','bi-file-earmark-text-fill','Contratos');
    $items[]=array('settings','cfg.php','bi-gear-fill','Configurações');
    echo '<link rel="stylesheet" href="../shared/support_navigation.css?v=20261015-19">';
    echo '<nav class="suite-support-navigation no_print mka-suite-content-start" aria-label="Navegação do suporte"><a href="#" aria-label="Voltar" title="Voltar" onclick="window.history.back();return false;"><i class="bi bi-arrow-left-circle-fill" aria-hidden="true"></i><span>Voltar</span></a>';
    foreach($items as $item)echo '<a href="'.$escape($item[1]).'" aria-label="'.$escape($item[3]).'" title="'.$escape($item[3]).'"'.($active===$item[0]?' class="is-active" aria-current="page"':'').'><i class="bi '.$escape($item[2]).'" aria-hidden="true"></i><span>'.$escape($item[3]).'</span></a>';
    echo '<a href="#" aria-label="Imprimir" title="Imprimir" onclick="window.print();return false;"><i class="bi bi-printer-fill" aria-hidden="true"></i><span>Imprimir</span></a></nav>';
}
