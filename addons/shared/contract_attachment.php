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
    $ready = mysqli_query($db, "CREATE TABLE IF NOT EXISTS dashboard_am_contract_archive (
        uuid_cliente VARCHAR(64) NOT NULL, document_key CHAR(64) NOT NULL,
        document_name VARCHAR(255) NOT NULL, archived_by VARCHAR(120) NOT NULL,
        archived_at DATETIME NOT NULL, PRIMARY KEY (uuid_cliente,document_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$ready) throw new RuntimeException('Não foi possível preparar o arquivo de contratos.');
}
function mka_contract_archive_map($db) {
    static $map = null;
    if ($map === null) {
        mka_attachment_schema($db); $map = array();
        $q = mysqli_query($db, 'SELECT * FROM dashboard_am_contract_archive');
        if (!$q) throw new RuntimeException('Não foi possível consultar os contratos arquivados.');
        while ($r = mysqli_fetch_assoc($q)) $map[$r['uuid_cliente']][$r['document_key']] = $r;
    }
    return $map;
}
function mka_contract_native_key($file) {
    return hash('sha256', 'native:'.basename($file).':'.filemtime($file).':'.filesize($file));
}
function mka_attachment_latest($db, $uuid) {
    static $rows = null;
    if ($rows === null) {
        mka_attachment_schema($db);
        $rows = array();
        $q = mysqli_query($db, "SELECT a.* FROM dashboard_am_contract_attachment a INNER JOIN (SELECT t.uuid_cliente,MAX(t.id) id FROM dashboard_am_contract_attachment t WHERE NOT EXISTS (SELECT 1 FROM dashboard_am_contract_archive x WHERE x.uuid_cliente=t.uuid_cliente AND x.document_key=SHA2(CONCAT('upload:',t.id),256)) GROUP BY t.uuid_cliente) latest ON a.id=latest.id");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[$r['uuid_cliente']] = $r;
    }
    if (!isset($rows[$uuid])) return null;
    $r = $rows[$uuid];
    return array('manual_attachment'=>1, 'start_date'=>$r['start_date'], 'end_date'=>$r['end_date'],
        'duration_months'=>0, 'activated_at'=>$r['uploaded_at'], 'activated_by'=>$r['uploaded_by'],
        'pdf_url'=>'/admin/addons/busca_inteligente/contract_attachment.php?uuid='.rawurlencode($uuid).'&view='.(int)$r['id']);
}
