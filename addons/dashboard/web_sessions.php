<?php
require __DIR__.'/../shared/notification_bootstrap.php';
require_once __DIR__.'/../shared/web_session_counts.php';
require_once __DIR__.'/../shared/web_session_management.php';
header('Cache-Control: no-store, private');
$scope=($_GET['scope']??'mine')==='all'?'all':'mine';
$actor=(string)($_SESSION['MKA_Usuario']??($_SESSION['MM_Usuario']??''));
$canManage=function_exists('permissao')&&permissao('perm_config');
$currentFile='sess_'.session_id();
if($_SERVER['REQUEST_METHOD']==='POST') {
    header('Content-Type: application/json; charset=utf-8');
    if(!isset($_SESSION['suite_web_session_csrf'])||!hash_equals($_SESSION['suite_web_session_csrf'],(string)($_POST['csrf']??''))){http_response_code(403);echo json_encode(array('error'=>'Confirmação expirada. Atualize a lista.'));exit;}
    $token=(string)($_POST['token']??'');$target=$_SESSION['suite_web_session_actions'][$token]??null;
    if(!is_array($target)||($target['expires']??0)<time()){http_response_code(409);echo json_encode(array('error'=>'Ação expirada. Atualize a lista.'));exit;}
    $inventory=mka_web_session_inventory();unset($_SESSION['suite_web_session_actions'][$token]);session_write_close();
    try {
        if($inventory===null)throw new RuntimeException('Lista indisponível. Atualize a página.');
        mka_web_session_remove_file($inventory['directory'],$target['file'],$target['user'],$actor,$canManage,$currentFile);
        error_log('[suite-web-sessions] '.json_encode(array('actor'=>$actor,'target_user'=>$target['user'],'action'=>'logout')));
        echo json_encode(array('ok'=>true,'message'=>'Sessão encerrada.'));
    } catch(Throwable $error){http_response_code(409);echo json_encode(array('error'=>$error->getMessage()));}
    exit;
}
$native=mka_web_session_native_url();if($native!==null){header('Location: '.$native.'?scope='.$scope);exit;}
header('Content-Type: text/html; charset=utf-8');
$inventory=mka_web_session_inventory();if($inventory===null){http_response_code(503);exit('Lista de sessões indisponível.');}
if(empty($_SESSION['suite_web_session_csrf']))$_SESSION['suite_web_session_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['suite_web_session_csrf'];$actions=array_filter($_SESSION['suite_web_session_actions']??array(),function($action){return is_array($action)&&($action['expires']??0)>=time();});
$entries=array_values(array_filter($inventory['entries'],function($entry)use($scope,$actor){return $scope==='all'||$entry['user']===$actor;}));
usort($entries,function($a,$b){return $b['updated']<=>$a['updated'];});
function mka_session_text($text){return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo $scope==='all'?'Conexões de usuários':'Minhas sessões'; ?> — MK-Auth</title>
<style>body{margin:14px;font:14px/1.55 Arial,sans-serif;color:#29425b}.session{display:flex;gap:16px;padding:18px;margin:0 0 18px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 3px #0001;overflow-wrap:anywhere}.device{color:#555;flex:0 0 42px;padding-top:5px}.device svg{width:42px;height:38px}.details{min-width:0}h1{font-size:18px}p{color:#60758b}.logout{border:0;border-radius:4px;background:#ff3860;color:white;padding:7px 10px;cursor:pointer;margin-top:8px}.current{display:inline-block;margin-top:8px;background:#eef5ff;padding:3px 7px;border-radius:5px}.note{font-size:12px}.message:empty{display:none}.message{padding:10px;border:1px solid #cad9eb;background:#f1f6ff;margin-bottom:12px}@media(max-width:360px){.session{padding:12px;gap:10px}}</style><script src="web_sessions.js?v=20261126-2" defer></script></head><body>
<h1><?php echo $scope==='all'?'Conexões de usuários':'Minhas sessões'; ?></h1>
<p><?php echo count($entries); ?> sessão(ões)<?php if($scope==='all'){echo ' · '.count($inventory['users']).' usuário(s) distinto(s)';} ?></p><div role="status" aria-live="polite" class="message" id="session-message"></div>
<?php foreach($entries as$entry){$current=$entry['file']===$currentFile;$allowed=ini_get('session.save_handler')==='files'&&!$current&&($canManage||$entry['user']===$actor);$token=null;if($allowed){$token=bin2hex(random_bytes(24));$actions[$token]=array('file'=>$entry['file'],'user'=>$entry['user'],'expires'=>time()+300);} ?>
<article class="session"><span class="device" aria-hidden="true"><svg viewBox="0 0 48 40" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="42" height="27" rx="2"/><path d="M24 30v7M14 37h20"/></svg></span><div class="details"><strong>Usuário:</strong> <?php echo mka_session_text($entry['user']); ?><br>
<strong>SO:</strong> <?php echo mka_session_text(mka_web_session_os($entry['agent'])); ?><br>
<strong>Navegador:</strong> <?php echo mka_session_text($entry['agent']!==''?$entry['agent']:'Aguardando novo acesso ao painel'); ?><br>
<strong>Última atividade:</strong> <?php echo date('d/m H:i',(int)$entry['updated']); ?><br>
<strong>IP:</strong> <?php echo mka_session_text($entry['ip']!==''?$entry['ip']:'Aguardando novo acesso ao painel'); ?><br>
<?php if($current){ ?><span class="current">Esta sessão</span><?php }elseif($allowed){ ?><form method="post" data-session-logout data-session-user="<?php echo mka_session_text($entry['user']); ?>"><input type="hidden" name="csrf" value="<?php echo mka_session_text($csrf); ?>"><input type="hidden" name="token" value="<?php echo mka_session_text($token); ?>"><button type="submit" class="logout">Deslogar</button></form><?php } ?>
</div></article><?php } ?>
<?php $_SESSION['suite_web_session_actions']=array_slice($actions,-200,null,true);session_write_close(); ?>
<p class="note">Cada bloco representa uma sessão. Um usuário pode estar conectado em mais de um dispositivo. Os dados de sessões antigas são preenchidos quando o dispositivo volta a acessar o painel.</p></body></html>
