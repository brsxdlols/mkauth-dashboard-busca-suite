<?php
// NOSSA NET: optional document policy, using native authentication and permissions.
function nn_cpf_get_policy() {
    global $LOADMYSQL;
    if (!($LOADMYSQL instanceof mysqli)) return false;
    $result = $LOADMYSQL->query("SELECT valor FROM sis_opcao WHERE nome='nn_permitir_sem_cpf' LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    return $row && $row['valor'] === 'sim';
}
function nn_cpf_csrf() {
    return hash_hmac('sha256', 'nn-cpf-policy|' . session_id(), KEY2PROVEDOR);
}
function nn_cpf_guard_client() {
    $action = isset($_GET['acao']) ? $_GET['acao'] : '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($action, array('ins.cliente', 'alt.cliente'), true)) return;
    if (nn_cpf_get_policy()) return;
    if (!isset($_POST['cpf_cnpj']) || !is_string($_POST['cpf_cnpj']) || trim($_POST['cpf_cnpj']) === '') {
        http_response_code(422);
        header('Content-Type: text/html; charset=utf-8');
        echo '<p>Informe o CPF/CNPJ do cliente. A permiss&atilde;o para documento em branco est&aacute; desabilitada em Op&ccedil;&otilde;es &rarr; Recursos.</p>';
        exit;
    }
}
function nn_cpf_config_permission($user) {
    $method = $GLOBALS['nn_cpf_permission_method'] ?? 'FKHMTAHMUAUKTMTMKKUMUAHKTAKMMMTAU';
    return is_string($method) && is_callable(array('MKAC_PERMISSAO',$method))
        && call_user_func(array('MKAC_PERMISSAO',$method),$user,'perm_config');
}
function nn_cpf_save_policy() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['acao']) || $_GET['acao'] !== 'opcoes' || !isset($_POST['nn_permitir_sem_cpf'])) return;
    $user = isset($_SESSION['MKA_Usuario']) ? $_SESSION['MKA_Usuario'] : '';
    if (!$user || !nn_cpf_config_permission($user)) {
        http_response_code(403); exit('Permissao insuficiente para alterar os recursos do sistema.');
    }
    $token = isset($_POST['nn_cpf_token']) && is_string($_POST['nn_cpf_token']) ? $_POST['nn_cpf_token'] : '';
    if (!hash_equals(nn_cpf_csrf(), $token)) { http_response_code(403); exit('Sessao expirada. Reabra a pagina de Recursos e tente novamente.'); }
    $value = $_POST['nn_permitir_sem_cpf'];
    if (!is_string($value) || !in_array($value, array('sim', 'nao'), true)) { http_response_code(400); exit('Opcao de CPF/CNPJ invalida.'); }
    global $LOADMYSQL;
    $statement = $LOADMYSQL->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('nn_permitir_sem_cpf',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
    $statement->bind_param('s', $value);
    if (!$statement->execute()) throw new RuntimeException('Nao foi possivel salvar a opcao de CPF/CNPJ.');
}
function nn_cpf_filter_payload($code) {
    $mode = isset($GLOBALS['nn_cpf_entry']) ? $GLOBALS['nn_cpf_entry'] : '';
    if ($mode !== 'executar_cliente' && $mode !== 'executar_provedor') return $code;
    $needle = 'switch(true){';
    $pos = strpos($code, $needle);
    if ($pos === false) { throw new RuntimeException('Controlador nativo incompativel com o patch CPF.'); }
    $hook = $mode === 'executar_cliente' ? 'nn_cpf_guard_client();' : 'nn_cpf_save_policy();';
    return substr_replace($code, $hook, $pos, 0);
}
function nn_cpf_render($html) {
    $mode = isset($GLOBALS['nn_cpf_entry']) ? $GLOBALS['nn_cpf_entry'] : '';
    if (!class_exists('MKAC_OPCAO') || !isset($_SESSION['MKA_Usuario'])) return $html;
    if ($mode !== 'conf_opcoes' && $mode !== 'cliente_ins' && $mode !== 'cliente_alt') return $html;
    if (strpos($html, 'id="form"') === false) return $html;
    $config = array('allowEmpty' => nn_cpf_get_policy(), 'mode' => $mode);
    if ($mode === 'conf_opcoes') $config['csrf'] = nn_cpf_csrf();
    $script = '<script>window.nnCpfPolicy=' . json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script><script src="/admin/addons/nn_cpf_opcional/policy.js?v=20261026-1"></script>';
    if (stripos($html, '</body>') !== false) return str_ireplace('</body>', $script . '</body>', $html);
    return $html . $script;
}
