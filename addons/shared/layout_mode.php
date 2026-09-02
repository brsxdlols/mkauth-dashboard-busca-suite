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
            'suite_live_search' => "VARCHAR(1) NOT NULL DEFAULT 's'",
            'trust_unlock_mode' => "VARCHAR(12) NOT NULL DEFAULT 'date'",
            'trust_unlock_fixed_days' => "INT NOT NULL DEFAULT 1",
            'trust_unlock_recent_days' => "INT NOT NULL DEFAULT 7",
            'radius_alert_enabled' => "VARCHAR(1) NOT NULL DEFAULT 's'",
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

if (!function_exists('mka_suite_normalize_trust_unlock_mode')) {
    function mka_suite_normalize_trust_unlock_mode($mode)
    {
        $mode = strtolower(trim((string) $mode));
        return in_array($mode, array('days', 'date', 'fixed', 'all'), true) ? $mode : 'date';
    }
}

if (!function_exists('mka_suite_get_trust_unlock_settings')) {
    function mka_suite_get_trust_unlock_settings($conn = null)
    {
        if (!($conn instanceof mysqli)) $conn = mka_suite_connect_db();
        $settings = array('mode' => 'date', 'fixed_days' => 1, 'recent_days' => 7);
        if (!($conn instanceof mysqli)) return $settings;
        mka_suite_ensure_layout_column($conn);
        $query = @mysqli_query($conn, "SELECT trust_unlock_mode, trust_unlock_fixed_days, trust_unlock_recent_days FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            $settings['mode'] = mka_suite_normalize_trust_unlock_mode(isset($row['trust_unlock_mode']) ? $row['trust_unlock_mode'] : 'date');
            $settings['fixed_days'] = max(1, min(10, (int) (isset($row['trust_unlock_fixed_days']) ? $row['trust_unlock_fixed_days'] : 1)));
            $recentDays = (int) (isset($row['trust_unlock_recent_days']) ? $row['trust_unlock_recent_days'] : 7);
            $settings['recent_days'] = in_array($recentDays, array(3, 7, 15), true) ? $recentDays : 7;
        }
        return $settings;
    }
}

if (!function_exists('mka_suite_set_trust_unlock_settings')) {
    function mka_suite_set_trust_unlock_settings($conn, $mode, $fixedDays, $recentDays = 7)
    {
        if (!($conn instanceof mysqli)) return false;
        mka_suite_ensure_layout_column($conn);
        $mode = mka_suite_normalize_trust_unlock_mode($mode);
        $fixedDays = max(1, min(10, (int) $fixedDays));
        $recentDays = in_array((int) $recentDays, array(3, 7, 15), true) ? (int) $recentDays : 7;
        return (bool) @mysqli_query($conn, "UPDATE dashboard_am_sis_cfg SET trust_unlock_mode = '" . mysqli_real_escape_string($conn, $mode) . "', trust_unlock_fixed_days = " . $fixedDays . ", trust_unlock_recent_days = " . $recentDays . " WHERE id = 1");
    }
}

if (!function_exists('mka_suite_ensure_trust_unlock_audit')) {
    function mka_suite_ensure_trust_unlock_audit($conn)
    {
        if (!($conn instanceof mysqli)) return false;
        return (bool) @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_trust_unlock_audit (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id INT NULL,
            client_login VARCHAR(191) NOT NULL,
            client_name VARCHAR(255) NULL,
            unlocked_until DATE NOT NULL,
            performed_by VARCHAR(191) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_trust_login_created (client_login, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}

if (!function_exists('mka_suite_get_recent_trust_unlock')) {
    function mka_suite_get_recent_trust_unlock($conn, $login, $recentDays)
    {
        if (!($conn instanceof mysqli)) return null;
        mka_suite_ensure_trust_unlock_audit($conn);
        $loginSql = mysqli_real_escape_string($conn, trim((string) $login));
        $recentDays = in_array((int) $recentDays, array(3, 7, 15), true) ? (int) $recentDays : 7;
        $query = @mysqli_query($conn, "SELECT * FROM dashboard_am_trust_unlock_audit
            WHERE client_login = '" . $loginSql . "'
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at >= DATE_SUB(NOW(), INTERVAL " . $recentDays . " DAY)
            ORDER BY created_at DESC LIMIT 1");
        return $query && ($row = mysqli_fetch_assoc($query)) ? $row : null;
    }
}

if (!function_exists('mka_suite_get_radius_alert_enabled')) {
    function mka_suite_get_radius_alert_enabled($conn = null)
    {
        if (!($conn instanceof mysqli)) $conn = mka_suite_connect_db();
        if (!($conn instanceof mysqli)) return 's';
        mka_suite_ensure_layout_column($conn);
        $query = @mysqli_query($conn, "SELECT radius_alert_enabled FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) return strtolower((string) $row['radius_alert_enabled']) === 'n' ? 'n' : 's';
        return 's';
    }
}

if (!function_exists('mka_suite_set_radius_alert_enabled')) {
    function mka_suite_set_radius_alert_enabled($conn, $enabled)
    {
        if (!($conn instanceof mysqli)) return false;
        mka_suite_ensure_layout_column($conn);
        $enabled = strtolower((string) $enabled) === 'n' ? 'n' : 's';
        return (bool) @mysqli_query($conn, "UPDATE dashboard_am_sis_cfg SET radius_alert_enabled = '" . $enabled . "' WHERE id = 1");
    }
}

if (!function_exists('mka_suite_normalize_live_search')) {
    function mka_suite_normalize_live_search($value)
    {
        return strtolower(trim((string) $value)) === 'n' ? 'n' : 's';
    }
}

if (!function_exists('mka_suite_get_live_search')) {
    function mka_suite_get_live_search($conn = null)
    {
        if (!($conn instanceof mysqli)) {
            $conn = mka_suite_connect_db();
        }
        if (!($conn instanceof mysqli)) {
            return 's';
        }

        mka_suite_ensure_layout_column($conn);
        $query = @mysqli_query($conn, "SELECT suite_live_search FROM dashboard_am_sis_cfg ORDER BY id DESC LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            return mka_suite_normalize_live_search(isset($row['suite_live_search']) ? $row['suite_live_search'] : 's');
        }

        return 's';
    }
}

if (!function_exists('mka_suite_set_live_search')) {
    function mka_suite_set_live_search($conn, $value)
    {
        if (!($conn instanceof mysqli)) {
            return false;
        }

        mka_suite_ensure_layout_column($conn);
        $value = mka_suite_normalize_live_search($value);
        return (bool) @mysqli_query(
            $conn,
            "UPDATE dashboard_am_sis_cfg SET suite_live_search = '" . mysqli_real_escape_string($conn, $value) . "' WHERE id = 1"
        );
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
