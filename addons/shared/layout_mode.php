<?php

if (!function_exists('mka_suite_connect_db')) {
    function mka_suite_connect_db()
    {
        static $conn = null;

        if ($conn instanceof mysqli) {
            return $conn;
        }

        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @mysqli_connect('127.0.0.1', 'root', 'vertrigo', 'mkradius');

        if ($conn instanceof mysqli) {
            @mysqli_set_charset($conn, 'utf8');
            return $conn;
        }

        return null;
    }
}

if (!function_exists('mka_suite_ensure_layout_column')) {
    function mka_suite_ensure_layout_column($conn)
    {
        if (!($conn instanceof mysqli)) {
            return;
        }

        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_sis_cfg (
            id int NOT NULL AUTO_INCREMENT,
            suite_layout_mode VARCHAR(10) NOT NULL DEFAULT 'novo',
            PRIMARY KEY (id)
        )");

        $query = @mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'suite_layout_mode'");
        if ($query && mysqli_num_rows($query) === 0) {
            @mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg
                ADD suite_layout_mode VARCHAR(10) NOT NULL DEFAULT 'novo'
                AFTER popup_clientes_sessao_duracao");
        }

        @mysqli_query($conn, "INSERT IGNORE INTO dashboard_am_sis_cfg (id, suite_layout_mode) VALUES (1, 'novo')");
    }
}

if (!function_exists('mka_suite_normalize_layout_mode')) {
    function mka_suite_normalize_layout_mode($mode)
    {
        $mode = strtolower(trim((string) $mode));
        return $mode === 'legado' ? 'legado' : 'novo';
    }
}

if (!function_exists('mka_suite_get_layout_mode')) {
    function mka_suite_get_layout_mode($conn = null)
    {
        if (!($conn instanceof mysqli)) {
            $conn = mka_suite_connect_db();
        }

        if (!($conn instanceof mysqli)) {
            return 'novo';
        }

        mka_suite_ensure_layout_column($conn);

        $query = @mysqli_query($conn, "SELECT suite_layout_mode FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            return mka_suite_normalize_layout_mode(isset($row['suite_layout_mode']) ? $row['suite_layout_mode'] : 'novo');
        }

        return 'novo';
    }
}

if (!function_exists('mka_suite_set_layout_mode')) {
    function mka_suite_set_layout_mode($conn, $mode)
    {
        if (!($conn instanceof mysqli)) {
            return false;
        }

        mka_suite_ensure_layout_column($conn);
        $mode = mka_suite_normalize_layout_mode($mode);

        return (bool) @mysqli_query(
            $conn,
            "UPDATE dashboard_am_sis_cfg SET suite_layout_mode = '" . mysqli_real_escape_string($conn, $mode) . "' WHERE id = 1"
        );
    }
}

if (!function_exists('mka_suite_dashboard_target')) {
    function mka_suite_dashboard_target($mode)
    {
        return mka_suite_normalize_layout_mode($mode) === 'legado' ? 'dashboard-legado' : 'dashboard';
    }
}

if (!function_exists('mka_suite_busca_target')) {
    function mka_suite_busca_target($mode)
    {
        return mka_suite_normalize_layout_mode($mode) === 'legado' ? 'busca_inteligente-legado' : 'busca_inteligente';
    }
}
