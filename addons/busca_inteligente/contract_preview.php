<?php
// Read-only preview of the assigned model. Never consult or replace signed files.
ob_start();
require __DIR__ . '/config.php';
ob_end_clean();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, private');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data: https:; script-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
require_once __DIR__ . '/../shared/client_action_access.php';
require_once __DIR__ . '/../shared/native_contract_renderer.php';
$uuids = isset($_GET['uuid']) ? $_GET['uuid'] : array();
if (!is_array($uuids)) $uuids = array($uuids);
if (!$uuids || count($uuids) > 100) { http_response_code(422); exit('Selecione entre 1 e 100 clientes.'); }
$documents = array();
try {
    foreach (array_unique($uuids, SORT_REGULAR) as $uuid) {
        if (!is_string($uuid)) { http_response_code(422); exit('Cliente inválido.'); }
        $client = mka_action_client($link, $uuid, !empty($acesso_permitido));
        $models = mka_native_contract_rows($link, 'SELECT c.texto,c.nome FROM sis_cliente a JOIN sis_contrato c ON c.codigo=a.contrato WHERE a.login=? LIMIT 1', $client['login']);
        $model = $models ? $models[0] : null;
        $documents[] = array('client'=>$client, 'model'=>$model, 'html'=>$model && trim($model['texto']) !== '' ? mka_native_contract_render($link, $client['login'], $model['texto']) : null);
    }
} catch (Throwable $error) {
    http_response_code(422);
    exit('<p>'.mka_native_contract_escape('Não foi possível preparar a prévia. '.$error->getMessage()).'</p>');
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Prévia do contrato</title>
<style>body{margin:0;padding:20px;font:14px Arial,sans-serif;color:#172b3a;background:#fff}.preview-info{padding:12px 0 16px;margin-bottom:20px;border-bottom:1px solid #dbe2eb;color:#43566b}.preview-info h1{font-size:18px;margin:0 0 8px}.preview-info p{margin:5px 0}.preview-document{margin-bottom:32px;overflow-wrap:anywhere}.preview-document img{max-width:100%;height:auto}.preview-document table{max-width:100%}@media print{.preview-info{display:none}.preview-document{break-after:page}.preview-document:last-child{break-after:auto}}</style></head><body>
<?php foreach ($documents as $document) { ?>
<article class="preview-document"><header class="preview-info"><h1>Prévia do contrato</h1><p><?= mka_native_contract_escape($document['client']['nome']); ?> — <?= mka_native_contract_escape($document['client']['login']); ?></p><p><?= $document['model'] ? mka_native_contract_escape($document['model']['nome']) : 'Nenhum modelo de contrato vinculado ao cadastro.'; ?></p><p>Modelo e dados atuais do cadastro. Esta prévia não é um documento assinado.</p></header>
<?= $document['html'] !== null ? $document['html'] : '<p>Vincule um modelo de contrato ao cadastro do cliente para visualizar a prévia.</p>'; ?>
</article>
<?php } ?></body></html>
