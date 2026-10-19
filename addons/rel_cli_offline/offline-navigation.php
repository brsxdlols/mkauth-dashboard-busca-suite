<?php
function off_suite_navigation($link, $operator) {
    if (empty($GLOBALS['mkaOfflineIntegrated'])) return;
    require_once __DIR__.'/../shared/layout_mode.php';
    require_once __DIR__.'/../shared/support_navigation.php';
    mka_suite_render_top_spacing_style($link);
    $manifest = json_decode(file_get_contents(__DIR__.'/../busca_inteligente/manifest.json'));
    mka_support_navigation($link, $manifest, (string)$operator, 'offline');
}
function off_report_route($settings = false) {
    if (!empty($GLOBALS['mkaOfflineIntegrated'])) return $settings ? 'clientes_offline_cfg.php' : 'clientes_offline.php';
    return $settings ? 'cfg.php' : 'index.php';
}
