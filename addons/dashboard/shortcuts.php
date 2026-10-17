<?php
require __DIR__.'/config.php';
if(!permissao('perm_config')){http_response_code(403);exit('Acesso negado.');}
if(empty($_SESSION['dashboard_shortcuts_csrf']))$_SESSION['dashboard_shortcuts_csrf']=bin2hex(random_bytes(24));
$row=$conn->query('SELECT link,texto,tot_acesso_rapido FROM dashboard_am_sis_cfg WHERE id=1')->fetch_assoc();
$links=explode(',',(string)$row['link']);$names=explode(',',(string)$row['texto']);$error='';$saved=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals($_SESSION['dashboard_shortcuts_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Sua sessão expirou. Reabra os atalhos.');
  $inputLinks=$_POST['link']??array();$inputNames=$_POST['texto']??array();
  if(!is_array($inputLinks)||!is_array($inputNames)||count($inputLinks)>50)throw new RuntimeException('Informe até 50 atalhos.');
  $links=array();$names=array();
  foreach($inputLinks as $i=>$url){
   if(!is_string($url)||!isset($inputNames[$i])||!is_string($inputNames[$i]))throw new RuntimeException('Atalho inválido.');
   $url=trim($url);$name=trim($inputNames[$i]);if($url===''&&$name==='')continue;
   if($url===''||$name===''||strlen($url)>1000||strlen($name)>150||strpos($url,',')!==false||strpos($name,',')!==false||preg_match('/[\x00-\x1f]/',$url.$name))throw new RuntimeException('Preencha nome e endereço, sem vírgulas.');
   if(strpos($url,'//')===0||strpos($url,chr(92))!==false||(preg_match('/^[a-z][a-z0-9+.-]*:/i',$url)&&!preg_match('~^https?://~i',$url)))throw new RuntimeException('Use um caminho do sistema ou endereço http/https.');
   if(strpos($url,'/admin/')===0)$url=substr($url,7);
   $links[]=$url;$names[]=$name;
  }
  $packedLinks=implode(',',$links);$packedNames=implode(',',$names);$count=max(15,count($links));
  $stmt=$conn->prepare('UPDATE dashboard_am_sis_cfg SET link=?,texto=?,tot_acesso_rapido=? WHERE id=1');$stmt->bind_param('ssi',$packedLinks,$packedNames,$count);if(!$stmt->execute())throw new RuntimeException('Não foi possível salvar os atalhos.');$saved=true;
 }catch(Throwable $e){$error=$e->getMessage();}
}
function escShortcut($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Atalhos</title>
<style>*{box-sizing:border-box}body{margin:0;padding:20px;background:#f5f8fc;color:#1e3653;font:14px system-ui}p{margin:0 0 18px;color:#60728a}.rows{display:grid;gap:12px}.shortcut{display:grid;grid-template-columns:1fr 1.5fr auto;gap:12px;padding:15px;background:#fff;border:1px solid #dce5f2;border-radius:12px}label{display:grid;gap:6px;font-size:12px;font-weight:600}input{width:100%;min-width:0;padding:11px;border:1px solid #cbd9ec;border-radius:8px;color:#1e3653;font:14px system-ui}button{padding:10px 14px;border:1px solid #cbd9ec;border-radius:8px;background:white;color:#245485;cursor:pointer;font:600 13px system-ui}.remove{align-self:end;color:#b4233b;border-color:#f0c9cf}.actions{display:flex;justify-content:space-between;gap:12px;position:sticky;bottom:0;background:#f5f8fc;padding:16px 0 0}.save{background:#1565e8;color:white;border-color:#1565e8}.error{color:#a21e32;padding:12px;background:#fff0f2;border-radius:8px;margin-bottom:12px}@media(max-width:600px){.shortcut{grid-template-columns:1fr}.remove{justify-self:start}}
</style><body><p>Escolha os nomes e endereços dos links da Dashboard.</p>
<?php if($saved){?><p role="status">Atalhos salvos.</p><script>window.parent.postMessage('dashboard-shortcuts-saved',location.origin);</script><?php } ?>
<?php if($error!==''){?><div class="error" role="alert"><?=escShortcut($error)?></div><?php } ?>
<form method="post"><input type="hidden" name="csrf" value="<?=escShortcut($_SESSION['dashboard_shortcuts_csrf'])?>"><div class="rows" id="rows">
<?php for($i=0;$i<count($links);$i++){if($links[$i]===''&&($names[$i]??'')==='')continue;?><div class="shortcut"><label>Nome do atalho<input name="texto[]" value="<?=escShortcut($names[$i]??'')?>" required maxlength="150"></label><label>Endereço<input name="link[]" value="<?=escShortcut($links[$i])?>" required maxlength="1000" placeholder="suporte_aberto.hhvm ou https://..."></label><button type="button" class="remove">Remover</button></div><?php } ?>
</div><div class="actions"><button type="button" id="add">Adicionar atalho</button><button class="save" type="submit">Salvar atalhos</button></div></form>
<template id="row"><div class="shortcut"><label>Nome do atalho<input name="texto[]" required maxlength="150"></label><label>Endereço<input name="link[]" required maxlength="1000" placeholder="suporte_aberto.hhvm ou https://..."></label><button type="button" class="remove">Remover</button></div></template>
<script>document.getElementById('add').onclick=function(){var rows=document.getElementById('rows');if(rows.children.length>=50)return;rows.appendChild(document.getElementById('row').content.cloneNode(true));rows.lastElementChild.querySelector('input').focus();};document.getElementById('rows').onclick=function(e){if(e.target.classList.contains('remove'))e.target.closest('.shortcut').remove();};</script></body></html>
