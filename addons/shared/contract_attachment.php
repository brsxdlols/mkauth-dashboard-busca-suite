<?php
function mka_attachment_schema($db) {
    static $ready = false;
    if ($ready) return;
    $ready = mysqli_query($db, "CREATE TABLE IF NOT EXISTS dashboard_am_contract_attachment (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        uuid_cliente VARCHAR(64) NOT NULL, storage_name VARCHAR(80) NOT NULL,
        original_name VARCHAR(255) NOT NULL, mime VARCHAR(40) NOT NULL,
        start_date DATE NULL, end_date DATE NULL, uploaded_by VARCHAR(120) NOT NULL,
        uploaded_at DATETIME NOT NULL, KEY idx_attachment_client (uuid_cliente,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$ready) throw new RuntimeException('Não foi possível preparar o armazenamento dos contratos.');
}
function mka_attachment_latest($db, $uuid) {
    static $rows = null;
    if ($rows === null) {
        mka_attachment_schema($db);
        $rows = array();
        $q = mysqli_query($db, 'SELECT a.* FROM dashboard_am_contract_attachment a INNER JOIN (SELECT uuid_cliente,MAX(id) id FROM dashboard_am_contract_attachment GROUP BY uuid_cliente) latest ON a.id=latest.id');
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[$r['uuid_cliente']] = $r;
    }
    if (!isset($rows[$uuid])) return null;
    $r = $rows[$uuid];
    return array('manual_attachment'=>1, 'start_date'=>$r['start_date'], 'end_date'=>$r['end_date'],
        'duration_months'=>0, 'activated_at'=>$r['uploaded_at'], 'activated_by'=>$r['uploaded_by'],
        'pdf_url'=>'/admin/addons/busca_inteligente/contract_attachment.php?uuid='.rawurlencode($uuid).'&view='.(int)$r['id']);
}
