<?php

if (!function_exists('mka_contract_escape')) {
    function mka_contract_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mka_contract_ensure_schema')) {
    function mka_contract_ensure_schema($db)
    {
        static $schema_ready = false;
        if ($schema_ready || !$db) {
            return;
        }

        mysqli_query($db, "
            CREATE TABLE IF NOT EXISTS sis_contrato_historico (
                id INT NOT NULL AUTO_INCREMENT,
                uuid_cliente VARCHAR(64) NOT NULL,
                login VARCHAR(120) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                duration_months INT NOT NULL DEFAULT 12,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                activated_at DATETIME NOT NULL,
                activated_by VARCHAR(120) NOT NULL DEFAULT '',
                notes TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_uuid_cliente (uuid_cliente),
                KEY idx_login (login),
                KEY idx_end_date (end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $schema_ready = true;
    }
}

if (!function_exists('mka_contract_allowed_durations')) {
    function mka_contract_allowed_durations()
    {
        return array(1, 6, 12, 24, 36, 48);
    }
}

if (!function_exists('mka_contract_get_latest')) {
    function mka_contract_get_latest($db, $uuid_cliente, $login_cliente)
    {
        mka_contract_ensure_schema($db);

        $uuid = mysqli_real_escape_string($db, (string) $uuid_cliente);
        $login = mysqli_real_escape_string($db, (string) $login_cliente);
        $where = array();

        if ($uuid !== '') {
            $where[] = "uuid_cliente = '{$uuid}'";
        }
        if ($login !== '') {
            $where[] = "login = '{$login}'";
        }
        if (empty($where)) {
            return null;
        }

        $sql = "
            SELECT *
            FROM sis_contrato_historico
            WHERE " . implode(' OR ', $where) . "
            ORDER BY end_date DESC, id DESC
            LIMIT 1
        ";

        $query = mysqli_query($db, $sql);
        if (!$query) {
            return null;
        }

        $row = mysqli_fetch_assoc($query);
        return $row ?: null;
    }
}

if (!function_exists('mka_contract_build_status')) {
    function mka_contract_build_status($contract_row, $today = null)
    {
        $today = $today ?: date('Y-m-d');

        if (!$contract_row || empty($contract_row['end_date'])) {
            return array(
                'status' => 'missing',
                'label' => 'Sem contrato',
                'days' => null,
                'class' => 'contract-missing',
                'icon' => 'fa-regular fa-file-circle-xmark',
                'start_date' => '',
                'end_date' => '',
                'duration_months' => 0,
            );
        }

        $end_date = $contract_row['end_date'];
        $days = 0;
        try {
            $d1 = new DateTime($today);
            $d2 = new DateTime($end_date);
            $days = ($d1 < $d2) ? $d1->diff($d2)->days : ($d1->diff($d2)->days * -1);
        } catch (Exception $e) {
            $days = 0;
        }

        $status = 'active';
        $label = 'Contrato ativo';
        $class = 'contract-active';
        $icon = 'fa-solid fa-file-shield';

        if ($days < 0) {
            $status = 'expired';
            $label = 'Contrato vencido';
            $class = 'contract-expired';
            $icon = 'fa-solid fa-file-circle-xmark';
        } elseif ($days <= 60) {
            $status = 'warning';
            $label = 'Contrato a vencer';
            $class = 'contract-warning';
            $icon = 'fa-solid fa-file-circle-exclamation';
        }

        return array(
            'status' => $status,
            'label' => $label,
            'days' => $days,
            'class' => $class,
            'icon' => $icon,
            'start_date' => $contract_row['start_date'],
            'end_date' => $contract_row['end_date'],
            'duration_months' => isset($contract_row['duration_months']) ? (int) $contract_row['duration_months'] : 0,
        );
    }
}

if (!function_exists('mka_contract_render_inline')) {
    function mka_contract_render_inline($status_info)
    {
        $label = isset($status_info['label']) ? $status_info['label'] : 'Sem contrato';
        $days = isset($status_info['days']) ? $status_info['days'] : null;
        $end_date = !empty($status_info['end_date']) ? date('d/m/Y', strtotime($status_info['end_date'])) : '';

        if ($status_info['status'] === 'expired' && $days !== null) {
            $label .= ' há ' . abs((int) $days) . ' dias';
            if ($end_date !== '') {
                $label .= ' (' . $end_date . ')';
            }
        } elseif ($status_info['status'] === 'warning' && $days !== null) {
            $label .= ' em ' . abs((int) $days) . ' dias';
            if ($end_date !== '') {
                $label .= ' (' . $end_date . ')';
            }
        } elseif ($status_info['status'] === 'active' && $end_date !== '') {
            $label .= ' até ' . $end_date;
        }

        return "<b>Contrato:</b> <span class=\"{$status_info['class']}\">" . mka_contract_escape($label) . "</span>";
    }
}

if (!function_exists('mka_contract_upsert')) {
    function mka_contract_upsert($db, $uuid_cliente, $login_cliente, $duration_months, $activated_by, $start_date = null, $notes = '')
    {
        mka_contract_ensure_schema($db);

        $duration = (int) $duration_months;
        if (!in_array($duration, mka_contract_allowed_durations(), true)) {
            $duration = 12;
        }

        $start_date = $start_date ?: date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$duration} month", strtotime($start_date)));

        $uuid = mysqli_real_escape_string($db, (string) $uuid_cliente);
        $login = mysqli_real_escape_string($db, (string) $login_cliente);
        $activated_by = mysqli_real_escape_string($db, (string) $activated_by);
        $notes = mysqli_real_escape_string($db, (string) $notes);
        $start = mysqli_real_escape_string($db, (string) $start_date);
        $end = mysqli_real_escape_string($db, (string) $end_date);

        mysqli_query($db, "
            INSERT INTO sis_contrato_historico
                (uuid_cliente, login, status, duration_months, start_date, end_date, activated_at, activated_by, notes)
            VALUES
                ('{$uuid}', '{$login}', 'active', {$duration}, '{$start}', '{$end}', NOW(), '{$activated_by}', '{$notes}')
        ");

        return mysqli_insert_id($db);
    }
}
