<?php
ob_start(); require __DIR__.'/config.php'; ob_end_clean();
require_once __DIR__.'/../shared/client_action_access.php';
$uuid = isset($_GET['uuid']) ? trim((string)$_GET['uuid']) : '';
$client = mka_action_client($link, $uuid, !empty($acesso_permitido));
mka_attachment_schema($link);
$user = !empty($_SESSION['MKA_Usuario']) ? $_SESSION['MKA_Usuario'] : $_SESSION['MM_Usuario'];
$safe = mysqli_real_escape_string($link,$uuid);
$documents = array();
$q = mysqli_query($link,"SELECT * FROM dashboard_am_contract_attachment WHERE uuid_cliente='$safe' ORDER BY id DESC");
if (!$q) { http_response_code(503); exit('Não foi possível consultar os contratos.'); }
while ($r = mysqli_fetch_assoc($q)) {
    $key = hash('sha256','upload:'.$r['id']);
    $documents[$key] = array('name'=>$r['original_name'],'url'=>'contract_attachment.php?uuid='.rawurlencode($uuid).'&view='.(int)$r['id']);
}
$dir = '/opt/mk-auth/admin/arquivos/'.$uuid;
if (!is_link($dir)) foreach ((array)glob($dir.'/contrato_*.pdf') as $file) {
    if (!is_file($file) || is_link($file)) continue;
    $documents[mka_contract_native_key($file)] = array('name'=>basename($file),'url'=>'/admin/arquivos/'.rawurlencode($uuid).'/'.rawurlencode(basename($file)));
}
$map = mka_contract_archive_map($link);
$archived = isset($map[$uuid]) ? $map[$uuid] : array();
$error = ''; $saved = false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    mka_action_check_token();
    try {
        if (empty($_POST['ack'])) throw new RuntimeException('Confirme que leu o aviso.');
        $selected = isset($_POST['documents']) && is_array($_POST['documents']) ? array_unique($_POST['documents']) : array();
        if (!$selected) throw new RuntimeException('Selecione pelo menos um contrato atual.');
        foreach ($selected as $key) if (!is_string($key) || !isset($documents[$key]) || isset($archived[$key])) throw new RuntimeException('A lista mudou. Reabra o formulário.');
        if (!isset($_SESSION['mka_archive_attempts'])) $_SESSION['mka_archive_attempts']=array();
        $_SESSION['mka_archive_attempts']=array_values(array_filter($_SESSION['mka_archive_attempts'],function($t){return $t>time()-900;}));
        if (count($_SESSION['mka_archive_attempts'])>=5) throw new RuntimeException('Limite de tentativas. Aguarde 15 minutos.');
        $_SESSION['mka_archive_attempts'][]=time();
        $safeUser=mysqli_real_escape_string($link,$user);
        $q=mysqli_query($link,"SELECT sha,ativo FROM sis_acesso WHERE login='$safeUser' LIMIT 1");
        $auth=$q ? mysqli_fetch_assoc($q) : null;
        $password=isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $valid=$auth && $auth['ativo']==='sim' && password_verify($password,$auth['sha']);
        unset($password,$_POST['password']);
        if (!$valid) throw new RuntimeException('Senha inválida ou usuário inativo. Use a senha do usuário conectado ao MK-AUTH.');
        $lockName='mka-contract-'.substr(hash('sha256',$uuid),0,40);
        $q=mysqli_query($link,"SELECT GET_LOCK('$lockName',5) acquired");
        $lock=$q ? mysqli_fetch_assoc($q) : null;
        if (!$lock || (int)$lock['acquired']!==1) throw new RuntimeException('Outra operação está em andamento. Tente novamente.');
        mysqli_begin_transaction($link);
        try {
            $stmt=mysqli_prepare($link,'INSERT INTO dashboard_am_contract_archive (uuid_cliente,document_key,document_name,archived_by,archived_at) VALUES (?,?,?,?,NOW())');
            if (!$stmt) throw new RuntimeException('Falha ao registrar o arquivamento.');
            foreach ($selected as $key) {
                $name=$documents[$key]['name'];
                mysqli_stmt_bind_param($stmt,'ssss',$uuid,$key,$name,$user);
                if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Contrato já arquivado ou falha ao salvar. Reabra a lista.');
            }
            mysqli_stmt_close($stmt);
            if (!mysqli_commit($link)) throw new RuntimeException('Não foi possível concluir o arquivamento.');
            $_SESSION['mka_archive_attempts']=array(); $saved=true;
        } catch (Exception $e) { mysqli_rollback($link); throw $e; }
        finally { mysqli_query($link,"SELECT RELEASE_LOCK('$lockName')"); }
    } catch (Exception $e) { $error=$e->getMessage(); }
}
header('Cache-Control: private, no-store');
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Arquivar contratos</title>
<style>*{box-sizing:border-box}body{margin:0;padding:16px;background:#eef4fb;color:#20364f;font:15px Arial}main{background:white;border-radius:16px;padding:20px;max-width:620px;margin:auto}h1{font-size:22px}.warning{background:#fff3cd;padding:14px;border-radius:10px;line-height:1.5}label{display:block;margin:14px 0;overflow-wrap:anywhere}input[type=password]{display:block;width:100%;padding:12px;margin-top:8px;border:1px solid #bccbdd;border-radius:8px}button,.button{display:inline-block;background:#1268db;color:white;padding:12px;border:0;border-radius:8px;text-decoration:none;cursor:pointer}.error{color:#ac2020}li{margin:12px 0;overflow-wrap:anywhere}small{display:block;color:#53677c;margin:6px 0}</style></head><body><main>
<h1>Arquivar contratos</h1><p><?=mka_contract_escape($client['nome'])?></p>
<?php if ($saved) { ?>
<p>Contratos selecionados arquivados. Nenhum arquivo foi apagado.</p>
<a class="button" target="_top" href="relcontratos.php?client=<?=rawurlencode($uuid)?>" onclick="if(window.parent!==window){window.parent.postMessage({type:'mka-content-modal-refresh'},window.location.origin);return false;}">Concluir e atualizar</a>
<?php } else { ?>
<p class="warning"><strong>Atenção:</strong> os documentos selecionados deixarão de ser contratos atuais nesta suite, mas serão preservados no histórico abaixo. Para anexar um novo, arquive todos os documentos atuais. Isso não cancela o serviço, a assinatura nem altera as datas de vigência cadastradas.</p>
<?php if ($error!=='') { ?><p class="error" role="alert"><?=mka_contract_escape($error)?></p><?php } ?>
<form method="post"><input type="hidden" name="csrf" value="<?=mka_contract_escape(mka_action_token())?>">
<h2>Contratos atuais</h2>
<?php $current=0; foreach ($documents as $key=>$doc) { if (isset($archived[$key])) continue; $current++; ?>
<label><input type="checkbox" name="documents[]" value="<?=mka_contract_escape($key)?>"> <?=mka_contract_escape($doc['name'])?> <a target="_blank" rel="noopener" href="<?=mka_contract_escape($doc['url'])?>">Visualizar</a></label>
<?php } if ($current) { ?>
<label><input type="checkbox" name="ack" value="1" required> Li o aviso e quero arquivar os contratos selecionados.</label>
<label>Senha do usuário <?=mka_contract_escape($user)?><input type="password" name="password" autocomplete="current-password" required></label>
<button type="submit">Confirmar arquivamento</button>
<?php } else { ?><p>Nenhum documento atual. Você já pode anexar um novo na aba Contratos.</p><?php } ?></form>
<h2>Histórico arquivado</h2><ul>
<?php foreach ($archived as $key=>$item) { ?><li><?=mka_contract_escape($item['document_name'])?><small>Arquivado em <?=mka_contract_escape($item['archived_at'])?> por <?=mka_contract_escape($item['archived_by'])?></small><?php if(isset($documents[$key])) { ?><a href="<?=mka_contract_escape($documents[$key]['url'])?>" target="_blank" rel="noopener">Visualizar documento preservado</a><?php } ?></li><?php } ?>
</ul><?php } ?></main></body></html>
