<?php

if (!function_exists('mka_client_audit_connect')) {
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
}

if (!function_exists('mka_client_audit_ensure_table')) {
    function mka_client_audit_ensure_table($conn)
    {
        if (!($conn instanceof mysqli)) {
            return false;
        }

        return (bool) @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_client_update_audit (
            uuid_cliente VARCHAR(64) NOT NULL,
            login_cliente VARCHAR(64) NOT NULL DEFAULT '',
            usuario VARCHAR(100) NOT NULL,
            alterado_em DATETIME NOT NULL,
            PRIMARY KEY (uuid_cliente),
            KEY idx_login_cliente (login_cliente)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}

if (!function_exists('mka_client_audit_session_user')) {
    function mka_client_audit_session_user()
    {
        foreach (array('MM_Usuario', 'MKA_Usuario', 'usuario', 'user', 'login') as $key) {
            if (isset($_SESSION[$key]) && trim((string) $_SESSION[$key]) !== '') {
                return trim((string) $_SESSION[$key]);
            }
        }
        return '';
    }
}

if (!function_exists('mka_client_audit_register_request')) {
    function mka_client_audit_register_request()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            return;
        }
        if (!isset($_GET['acao']) || $_GET['acao'] !== 'alt.cliente') {
            return;
        }

        $uuid = isset($_POST['uuid_cliente']) ? trim((string) $_POST['uuid_cliente']) : '';
        $login = isset($_POST['login']) ? trim((string) $_POST['login']) : '';
        if ($uuid === '' && $login === '') {
            return;
        }

        $conn = mka_client_audit_connect();
        if (!($conn instanceof mysqli)) {
            return;
        }

        $where = $uuid !== ''
            ? "uuid_cliente = '" . mysqli_real_escape_string($conn, $uuid) . "'"
            : "login = '" . mysqli_real_escape_string($conn, $login) . "'";
        $before = '';
        $query = @mysqli_query($conn, "SELECT uuid_cliente, login, last_update FROM sis_cliente WHERE " . $where . " LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            $uuid = (string) $row['uuid_cliente'];
            $login = (string) $row['login'];
            $before = (string) $row['last_update'];
        }
        @mysqli_close($conn);

        register_shutdown_function(function () use ($uuid, $login, $before) {
            $usuario = mka_client_audit_session_user();
            if ($usuario === '' || $uuid === '') {
                return;
            }

            $audit_conn = mka_client_audit_connect();
            if (!($audit_conn instanceof mysqli)) {
                return;
            }

            $uuid_sql = mysqli_real_escape_string($audit_conn, $uuid);
            $query = @mysqli_query($audit_conn, "SELECT login, last_update FROM sis_cliente WHERE uuid_cliente = '" . $uuid_sql . "' LIMIT 1");
            if (!$query || !($row = mysqli_fetch_assoc($query))) {
                @mysqli_close($audit_conn);
                return;
            }

            $after = (string) $row['last_update'];
            if ($after === '' || $after === $before) {
                @mysqli_close($audit_conn);
                return;
            }

            mka_client_audit_ensure_table($audit_conn);
            $login_sql = mysqli_real_escape_string($audit_conn, (string) $row['login']);
            $usuario_sql = mysqli_real_escape_string($audit_conn, $usuario);
            $after_sql = mysqli_real_escape_string($audit_conn, $after);
            @mysqli_query($audit_conn, "INSERT INTO dashboard_am_client_update_audit
                (uuid_cliente, login_cliente, usuario, alterado_em)
                VALUES ('" . $uuid_sql . "', '" . $login_sql . "', '" . $usuario_sql . "', '" . $after_sql . "')
                ON DUPLICATE KEY UPDATE
                    login_cliente = VALUES(login_cliente),
                    usuario = VALUES(usuario),
                    alterado_em = VALUES(alterado_em)");
            @mysqli_close($audit_conn);
        });
    }
}

mka_client_audit_register_request();

