<?php

function mka_manual_block_audit_ensure_table($conn)
{
    if (!($conn instanceof mysqli)) return false;
    return (bool) @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_manual_block_audit (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        uuid_cliente VARCHAR(64) NOT NULL,
        login_cliente VARCHAR(64) NOT NULL DEFAULT '',
        nome_cliente VARCHAR(255) NOT NULL DEFAULT '',
        acao VARCHAR(16) NOT NULL,
        motivo TEXT NOT NULL,
        usuario VARCHAR(100) NOT NULL DEFAULT '',
        criado_em DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_manual_block_uuid (uuid_cliente, id),
        KEY idx_manual_block_login (login_cliente, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}

function mka_manual_block_audit_user()
{
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    foreach (array('MM_Usuario', 'MKA_Usuario', 'usuario', 'user', 'login') as $key) {
        if (isset($_SESSION[$key]) && trim((string) $_SESSION[$key]) !== '') return trim((string) $_SESSION[$key]);
    }
    return 'Usuário não identificado';
}

function mka_manual_block_audit_latest_block($conn, $uuid)
{
    if (!($conn instanceof mysqli)) return null;
    $safe = mysqli_real_escape_string($conn, (string) $uuid);
    $query = @mysqli_query($conn, "SELECT motivo,usuario,criado_em FROM dashboard_am_manual_block_audit WHERE uuid_cliente='{$safe}' AND acao='bloqueio' ORDER BY id DESC LIMIT 1");
    return $query ? mysqli_fetch_assoc($query) : null;
}

