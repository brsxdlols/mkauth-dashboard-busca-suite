<?php
ob_start(); require __DIR__ . '/config.php'; ob_end_clean();
require_once __DIR__ . '/../shared/client_action_access.php';
require_once __DIR__ . '/../shared/contract_attachment.php';
$uuid = isset($_GET['uuid']) ? trim((string) $_GET['uuid']) : '';
$client = mka_action_client($link, $uuid, !empty($acesso_permitido));
mka_attachment_schema($link);
$storage = '/opt/mk-auth/contract-uploads';
$safeUuid = mysqli_real_escape_string($link, $uuid);
if (isset($_GET['view'])) {
    $id = (int) $_GET['view'];
    $q = mysqli_query($link, "SELECT * FROM dashboard_am_contract_attachment WHERE id=$id AND uuid_cliente='$safeUuid' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    if (!$row || !preg_match('/^[a-f0-9]{48}\.(pdf|jpg|png)$/', $row['storage_name'])) { http_response_code(404); exit('Anexo não encontrado.'); }
    $file = $storage . '/' . $row['storage_name'];
    if (!is_file($file) || is_link($file)) { http_response_code(404); exit('Arquivo não encontrado.'); }
    header('Content-Type: '.$row['mime']);
    header('Content-Length: '.filesize($file));
    header('Content-Disposition: inline; filename="contrato-'.(int)$row['id'].'.'.pathinfo($file, PATHINFO_EXTENSION).'"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    session_write_close(); readfile($file); exit;
}
$error = ''; $saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mka_action_check_token();
    try {
        $start = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $end = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        foreach (array($start,$end) as $date) {
            if ($date === '') continue;
            $parsed = DateTime::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new RuntimeException('Informe datas válidas.');
        }
        if ($end !== '' && ($start === '' || $end < $start)) throw new RuntimeException('Informe o início e um vencimento igual ou posterior a ele.');
        $upload = isset($_FILES['contract']) ? $_FILES['contract'] : null;
        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) throw new RuntimeException('Falha no envio. Verifique o limite de upload do servidor.');
        if (filesize($upload['tmp_name']) > 10*1024*1024 || filesize($upload['tmp_name']) === 0) throw new RuntimeException('Envie um arquivo de até 10 MB, não vazio.');
        $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($upload['tmp_name']);
        $types = array('application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png');
        if (!isset($types[$mime])) throw new RuntimeException('São aceitos somente PDF, JPG e PNG.');
        if ($mime !== 'application/pdf' && !@getimagesize($upload['tmp_name'])) throw new RuntimeException('Imagem inválida.');
        if (!is_dir($storage) || !is_writable($storage)) throw new RuntimeException('Armazenamento indisponível. Execute o instalador atualizado.');
        $name = bin2hex(random_bytes(24)).'.'.$types[$mime];
        if (!move_uploaded_file($upload['tmp_name'], $storage.'/'.$name)) throw new RuntimeException('Não foi possível guardar o arquivo.');
        chmod($storage.'/'.$name, 0640);
        $original = substr(basename($upload['name']),0,240);
        $user = isset($_SESSION['MKA_Usuario']) && $_SESSION['MKA_Usuario'] !== '' ? $_SESSION['MKA_Usuario'] : $_SESSION['MM_Usuario'];
        $start = $start === '' ? null : $start; $end = $end === '' ? null : $end;
        $stmt = mysqli_prepare($link, 'INSERT INTO dashboard_am_contract_attachment (uuid_cliente,storage_name,original_name,mime,start_date,end_date,uploaded_by,uploaded_at) VALUES (?,?,?,?,?,?,?,NOW())');
        if (!$stmt) { unlink($storage.'/'.$name); throw new RuntimeException('Não foi possível registrar o anexo.'); }
        mysqli_stmt_bind_param($stmt,'sssssss',$uuid,$name,$original,$mime,$start,$end,$user);
        if (!mysqli_stmt_execute($stmt)) { unlink($storage.'/'.$name); throw new RuntimeException('Não foi possível registrar o anexo.'); }
        mysqli_stmt_close($stmt); $saved = true;
    } catch (Exception $e) { $error = $e->getMessage(); }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Anexar contrato</title>
<style>*{box-sizing:border-box}body{margin:0;padding:18px;font:15px Arial;background:#eef4fb;color:#20364f}.card{max-width:580px;margin:auto;background:white;border-radius:16px;padding:20px}h1{font-size:22px;margin-top:0}label{display:block;margin:16px 0}input{display:block;width:100%;margin-top:7px;padding:10px;border:1px solid #cbd8e8;border-radius:8px;min-width:0}.dates{display:grid;grid-template-columns:1fr 1fr;gap:12px}button,a.button{display:inline-block;padding:12px;border:0;border-radius:9px;background:#1268db;color:#fff;text-decoration:none;cursor:pointer}.error{color:#a92323}.success{color:#157347}small{line-height:1.5;color:#53677c}@media(max-width:420px){.dates{grid-template-columns:1fr}body{padding:10px}.card{padding:14px}}</style></head><body><main class="card">
<h1>Anexar contrato existente</h1><p><?= mka_contract_escape($client['nome']); ?></p>
<?php if ($saved) { ?><p class="success" role="status">Contrato anexado. Ele já será contabilizado no relatório.</p><a class="button" href="relcontratos.php" target="_top" onclick="if(window.parent!==window){window.parent.postMessage({type:'mka-content-modal-refresh'},window.location.origin);return false;}">Concluir e atualizar</a>
<?php } else { ?>
<?php if ($error !== '') { ?><p class="error" role="alert"><?= mka_contract_escape($error); ?></p><?php } ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= mka_contract_escape(mka_action_token()); ?>">
<label>Contrato (PDF, JPG ou PNG)<input type="file" name="contract" accept="application/pdf,image/jpeg,image/png" required></label>
<small>Até 10 MB, sujeito ao limite do servidor. Envie o documento do contrato já existente. Este envio não é uma assinatura digital.</small>
<div class="dates"><label>Início (opcional)<input type="date" name="start_date" value="<?= mka_contract_escape(isset($_POST['start_date']) ? $_POST['start_date'] : ''); ?>"></label><label>Vencimento (opcional)<input type="date" name="end_date" value="<?= mka_contract_escape(isset($_POST['end_date']) ? $_POST['end_date'] : ''); ?>"></label></div>
<p><small>Sem vencimento informado, será contabilizado como contrato anexado sem prazo definido. Não substitui nem apaga os documentos anteriores.</small></p>
<button type="submit">Salvar contrato</button></form><?php } ?>
</main></body></html>
