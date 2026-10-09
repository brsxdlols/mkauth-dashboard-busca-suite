<?php
// Resolve both primary and additional logins to the owning customer, within scope.
function radius_live_clients($conn, $logins, $full, $groups) {
    $keys = array_values(array_unique(array_filter(array_map(function ($value) {
        return strtolower(trim((string)$value));
    }, $logins))));
    if (!$keys) return array();
    $marks = implode(',', array_fill(0, count($keys), '?'));
    $sql = "SELECT LOWER(TRIM(c.login)) login_key,c.nome,c.uuid_cliente,c.grupo,c.cli_ativado,c.bloqueado,c.pgcorte,c.pgaviso FROM sis_cliente c WHERE LOWER(TRIM(c.login)) IN ($marks)
        UNION SELECT LOWER(TRIM(a.username)),c.nome,c.uuid_cliente,c.grupo,c.cli_ativado,c.bloqueado,c.pgcorte,c.pgaviso FROM sis_adicional a
        INNER JOIN sis_cliente c ON LOWER(TRIM(a.login))=LOWER(TRIM(c.login)) WHERE LOWER(TRIM(a.username)) IN ($marks)";
    $params = array_merge($keys, $keys);
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $clients = array();
    $extension = is_file(__DIR__.'/../../cliente_det.hhvm') ? 'hhvm' : 'php';
    while ($client = $result->fetch_assoc()) {
        if (!$full && !in_array((string)$client['grupo'], $groups, true)) continue;
        if (empty($client['uuid_cliente'])) continue;
        $clients[$client['login_key']] = array('name' => $client['nome'],
            'disabled' => $client['cli_ativado'] === 'n',
            'blocked' => $client['bloqueado'] === 'sim',
            'missing_pages' => $client['bloqueado'] === 'sim' && ($client['pgcorte'] !== 'sim' || $client['pgaviso'] !== 'sim'),
            'url' => '/admin/cliente_det.'.$extension.'?uuid='.rawurlencode($client['uuid_cliente']));
    }
    return $clients;
}
