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

        // Additive migrations keep older MK-Auth installations compatible.
        $columns = array(
            'popup_clientes_sessao' => "VARCHAR(1) NOT NULL DEFAULT 'n'",
            'popup_clientes_sessao_duracao' => "INT NOT NULL DEFAULT 2",
            'suite_top_spacing' => "INT NOT NULL DEFAULT 16",
            'suite_header_spacing' => "INT NOT NULL DEFAULT 0",
            'suite_layout_mode' => "VARCHAR(10) NOT NULL DEFAULT 'novo'"
        );

        foreach ($columns as $column => $definition) {
            $query = @mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = '" . $column . "'");
            if ($query && mysqli_num_rows($query) === 0) {
                @mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg ADD COLUMN `" . $column . "` " . $definition);
            }
        }

        @mysqli_query($conn, "INSERT IGNORE INTO dashboard_am_sis_cfg (id, suite_layout_mode) VALUES (1, 'novo')");
    }
}

if (!function_exists('mka_suite_normalize_top_spacing')) {
    function mka_suite_normalize_top_spacing($spacing)
    {
        return max(0, min(120, (int) $spacing));
    }
}

if (!function_exists('mka_suite_get_top_spacing')) {
    function mka_suite_get_top_spacing($conn = null)
    {
        if (!($conn instanceof mysqli)) {
            $conn = mka_suite_connect_db();
        }
        if (!($conn instanceof mysqli)) {
            return 16;
        }
        mka_suite_ensure_layout_column($conn);
        $query = @mysqli_query($conn, "SELECT suite_top_spacing FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            return mka_suite_normalize_top_spacing(isset($row['suite_top_spacing']) ? $row['suite_top_spacing'] : 16);
        }
        return 16;
    }
}

if (!function_exists('mka_suite_set_top_spacing')) {
    function mka_suite_set_top_spacing($conn, $spacing)
    {
        if (!($conn instanceof mysqli)) {
            return false;
        }
        mka_suite_ensure_layout_column($conn);
        $spacing = mka_suite_normalize_top_spacing($spacing);
        return (bool) @mysqli_query($conn, "UPDATE dashboard_am_sis_cfg SET suite_top_spacing = " . $spacing . " WHERE id = 1");
    }
}

if (!function_exists('mka_suite_render_top_spacing_style')) {
    function mka_suite_render_top_spacing_style($conn = null)
    {
        $spacing = mka_suite_get_header_spacing($conn);
        $contentSpacing = mka_suite_get_top_spacing($conn);
        echo '<style id="mka-suite-top-spacing">:root{--mka-suite-header-spacing:' . $spacing . 'px;--mka-suite-content-spacing:' . $contentSpacing . 'px}#systopo{position:relative;top:var(--mka-suite-header-spacing)}.mka-suite-content-start{margin-top:var(--mka-suite-content-spacing)!important}body:not(.mka-suite-dashboard-page) #sistema-corpo1 .navbar-brand{margin-right:0!important}</style>';
    }
}

if (!function_exists('mka_suite_normalize_header_spacing')) {
    function mka_suite_normalize_header_spacing($spacing)
    {
        return max(-20, min(60, (int) $spacing));
    }
}

if (!function_exists('mka_suite_get_header_spacing')) {
    function mka_suite_get_header_spacing($conn = null)
    {
        if (!($conn instanceof mysqli)) {
            $conn = mka_suite_connect_db();
        }
        if (!($conn instanceof mysqli)) {
            return 0;
        }
        mka_suite_ensure_layout_column($conn);
        $query = @mysqli_query($conn, "SELECT suite_header_spacing FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            return mka_suite_normalize_header_spacing(isset($row['suite_header_spacing']) ? $row['suite_header_spacing'] : 0);
        }
        return 0;
    }
}

if (!function_exists('mka_suite_set_header_spacing')) {
    function mka_suite_set_header_spacing($conn, $spacing)
    {
        if (!($conn instanceof mysqli)) {
            return false;
        }
        mka_suite_ensure_layout_column($conn);
        $spacing = mka_suite_normalize_header_spacing($spacing);
        return (bool) @mysqli_query($conn, "UPDATE dashboard_am_sis_cfg SET suite_header_spacing = " . $spacing . " WHERE id = 1");
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
