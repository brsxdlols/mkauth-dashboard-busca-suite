<?php

function mka_client_audit_connect()
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @mysqli_connect('127.0.0.1', 'root', 'vertrigo', 'mkradius');
    if ($conn instanceof mysqli) {
        @mysqli_set_charset($conn, 'utf8');
        return $conn;
    }
    return null;
}

function mka_client_audit_ensure_table($conn)
{
    if (!($conn instanceof mysqli)) {
        return false;
    }
    $ok = (bool) @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_client_update_audit (
        uuid_cliente VARCHAR(64) NOT NULL,
        login_cliente VARCHAR(64) NOT NULL DEFAULT '',
        usuario VARCHAR(100) NOT NULL,
        previous_last_update DATETIME NULL,
        captured_at DATETIME NULL,
        detalhes TEXT NULL,
        PRIMARY KEY (uuid_cliente), KEY idx_login_cliente (login_cliente)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    foreach (array('previous_last_update' => 'DATETIME NULL', 'captured_at' => 'DATETIME NULL', 'detalhes' => 'TEXT NULL') as $column => $definition) {
        $query = @mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_client_update_audit WHERE Field = '" . $column . "'");
        if ($query && mysqli_num_rows($query) === 0) {
            @mysqli_query($conn, "ALTER TABLE dashboard_am_client_update_audit ADD COLUMN `" . $column . "` " . $definition);
        }
    }
    $legacy_column = @mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_client_update_audit WHERE Field = 'alterado_em'");
    if ($legacy_column && mysqli_num_rows($legacy_column) > 0) {
        @mysqli_query($conn, "ALTER TABLE dashboard_am_client_update_audit MODIFY alterado_em DATETIME NULL");
    }
    return $ok;
}

function mka_client_audit_session_user()
{
    foreach (array('MM_Usuario', 'MKA_Usuario', 'usuario', 'user', 'login') as $key) {
        if (isset($_SESSION[$key]) && trim((string) $_SESSION[$key]) !== '') {
            return trim((string) $_SESSION[$key]);
        }
    }
    return '';
}

if (isset($_POST['mka_client_audit']) && $_POST['mka_client_audit'] === 'prepare') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $usuario = mka_client_audit_session_user();
    $uuid = isset($_POST['uuid_cliente']) ? trim((string) $_POST['uuid_cliente']) : '';
    $login = isset($_POST['login']) ? trim((string) $_POST['login']) : '';
    $detalhes = isset($_POST['detalhes']) ? trim((string) $_POST['detalhes']) : '';
    $conn = mka_client_audit_connect();
    if ($usuario !== '' && ($uuid !== '' || $login !== '') && $conn instanceof mysqli) {
        mka_client_audit_ensure_table($conn);
        $where = $uuid !== '' ? "uuid_cliente='" . mysqli_real_escape_string($conn, $uuid) . "'" : "login='" . mysqli_real_escape_string($conn, $login) . "'";
        $query = @mysqli_query($conn, "SELECT uuid_cliente,login,last_update FROM sis_cliente WHERE " . $where . " LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            $uuid_sql = mysqli_real_escape_string($conn, (string) $row['uuid_cliente']);
            $login_sql = mysqli_real_escape_string($conn, (string) $row['login']);
            $usuario_sql = mysqli_real_escape_string($conn, $usuario);
            $detalhes_sql = mysqli_real_escape_string($conn, substr($detalhes, 0, 8000));
            $previous_sql = empty($row['last_update']) ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $row['last_update']) . "'";
            @mysqli_query($conn, "INSERT INTO dashboard_am_client_update_audit (uuid_cliente,login_cliente,usuario,previous_last_update,captured_at,detalhes)
                VALUES ('" . $uuid_sql . "','" . $login_sql . "','" . $usuario_sql . "'," . $previous_sql . ",NOW(),'" . $detalhes_sql . "')
                ON DUPLICATE KEY UPDATE login_cliente=VALUES(login_cliente),usuario=VALUES(usuario),previous_last_update=VALUES(previous_last_update),captured_at=VALUES(captured_at),detalhes=VALUES(detalhes)");
        }
        @mysqli_close($conn);
    }
    http_response_code(204);
    exit;
}
