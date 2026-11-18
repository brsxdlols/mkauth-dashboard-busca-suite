<?php
ob_start();
include('config.php');
ob_end_clean();
if (!isset($link) && isset($conn)) $link = $conn;
if (!isset($link) || !$link) { http_response_code(500); exit('Não foi possível conectar ao banco de dados.'); }

$additionalUuid=isset($_GET['additional'])&&is_string($_GET['additional'])?$_GET['additional']:'';
$additional=null;$additionalCsrf='';
if($additionalUuid!==''){
 require_once __DIR__.'/../shared/installations.php';require_once __DIR__.'/../shared/support_attendance.php';require_once __DIR__.'/../shared/additional_connection.php';
 $operator=(string)($_SESSION['MKA_Usuario']??$_SESSION['MM_Usuario']??'');
 if(!mka_installation_permission($link,$operator,'perm_altadicional')&&!mka_installation_permission($link,$operator,'perm_adicionais')){http_response_code(403);exit('Sem permissão para consultar adicionais.');}
 try{$additional=mka_additional_connection($link,$additionalUuid,mka_attendance_scope($link,$operator));}catch(DomainException $e){http_response_code(404);exit('Adicional não encontrado.');}
 if(empty($_SESSION['suite_additional_csrf']))$_SESSION['suite_additional_csrf']=bin2hex(random_bytes(24));$additionalCsrf=$_SESSION['suite_additional_csrf'];
}
$login = isset($_GET['login']) ? trim((string) $_GET['login']) : '';
if($additional)$login=$additional['username'];
if ($login === '' || strlen($login) > 64) { http_response_code(422); exit('Cliente inválido.'); }

function diag_h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function diag_row($label, $value, $state, $detail, $id="") {
    $icon = $state === 'ok' ? '✓' : ($state === 'warn' ? '!' : '×');
    echo '<div id="' . diag_h($id) . '" class="check ' . $state . '"><span class="state">' . $icon . '</span><div><strong>' . diag_h($label) . '</strong><div class="value">' . diag_h($value) . '</div><small>' . diag_h($detail) . '</small></div></div>';
}

$client = null;
if($additional)$client=array('nome'=>$additional['nome'],'login'=>$additional['username'],'plano'=>$additional['plano'],'planodown'=>'','plano15'=>'','cli_ativado'=>$additional['cli_ativado']);
if(!$additional){
$stmt = mysqli_prepare($link, "SELECT nome, login, plano, planodown, plano15, cli_ativado FROM sis_cliente WHERE login = ? LIMIT 1");
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $login); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $client = $res ? mysqli_fetch_assoc($res) : null; mysqli_stmt_close($stmt); }
}
if (!$client) { http_response_code(404); exit('Cliente não encontrado.'); }

$session = null;
$stmt = mysqli_prepare($link, "SELECT radacctid, framedipaddress, nasipaddress, acctstarttime, callingstationid FROM radacct WHERE LOWER(TRIM(username)) = LOWER(TRIM(?)) AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1");
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $login); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $session = $res ? mysqli_fetch_assoc($res) : null; mysqli_stmt_close($stmt); }

$financeLogin=$additional?$additional['parent_login']:$login;
$overdue = 0; $open = 0;
$stmt = mysqli_prepare($link, "SELECT SUM(CASE WHEN status NOT LIKE 'pago' AND deltitulo = 0 THEN 1 ELSE 0 END) total_abertos, SUM(CASE WHEN status NOT LIKE 'pago' AND deltitulo = 0 AND datavenc < CURDATE() THEN 1 ELSE 0 END) total_vencidos FROM sis_lanc WHERE login = ?");
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $financeLogin); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $finance = $res ? mysqli_fetch_assoc($res) : array(); $open = (int) $finance['total_abertos']; $overdue = (int) $finance['total_vencidos']; mysqli_stmt_close($stmt); }

$qosValue = ''; $qosGroup = '';
$stmt = mysqli_prepare($link, "SELECT rug.groupname, rgr.value FROM radusergroup rug LEFT JOIN radgroupreply rgr ON rgr.groupname = rug.groupname AND rgr.attribute = 'Mikrotik-Rate-Limit' WHERE LOWER(TRIM(rug.username)) = LOWER(TRIM(?)) ORDER BY rug.priority, rgr.id LIMIT 1");
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $login); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $qos = $res ? mysqli_fetch_assoc($res) : null; if ($qos) { $qosGroup = $qos['groupname']; $qosValue = $qos['value']; } mysqli_stmt_close($stmt); }

$queueState = $qosValue !== '' ? 'ok' : 'warn';
$queueValue = $qosValue !== '' ? $qosValue : 'Não verificada';
$queueDetail = $qosValue !== '' ? 'QoS entregue pelo RADIUS no grupo ' . $qosGroup . '.' : 'Cliente offline ou concentrador indisponível.';
if ($session && !empty($session['nasipaddress'])) {
    $nasIp = $session['nasipaddress']; $nas = null;
    $stmt = mysqli_prepare($link, "SELECT * FROM nas WHERE nasname = ? LIMIT 1");
    if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $nasIp); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $nas = $res ? mysqli_fetch_assoc($res) : null; mysqli_stmt_close($stmt); }
    if ($nas) {
        require_once __DIR__ . '/api/routeros_api.class.php';
        $api = new RouterosAPI(); $api->debug = false; $api->timeout = 3;
        $apiUser = isset($nas['userapi']) && trim($nas['userapi']) !== '' ? $nas['userapi'] : 'mkauth';
        if (@$api->connect($nasIp, $apiUser, $nas['senha'])) {
            $queues = $api->comm('/queue/simple/print', array('?name' => $login));
            if (empty($queues) && !empty($session['framedipaddress'])) {
                $allQueues = $api->comm('/queue/simple/print');
                foreach ((array) $allQueues as $candidate) {
                    if (isset($candidate['target']) && strpos($candidate['target'], $session['framedipaddress']) !== false) { $queues = array($candidate); break; }
                }
            }
            if (!empty($queues[0])) {
                $queue = $queues[0]; $limit = isset($queue['max-limit']) ? $queue['max-limit'] : 'limite não informado';
                $disabled = isset($queue['disabled']) && $queue['disabled'] === 'true';
                $queueState = $disabled ? 'warn' : 'ok'; $queueValue = (isset($queue['name']) ? $queue['name'] : $login) . ' — ' . $limit;
                $queueDetail = $disabled ? 'Queue localizada, porém está desativada.' : 'Queue de banda localizada e ativa no concentrador.';
            } elseif ($qosValue === '') { $queueValue = 'Queue não localizada'; $queueDetail = 'Não foi encontrada Simple Queue nem atributo de QoS no RADIUS.'; }
            else { $queueDetail .= ' A limitação é dinâmica; por isso não existe Simple Queue fixa.'; }
            $api->disconnect();
        } else { $queueValue = 'API do concentrador indisponível'; $queueDetail = 'Não foi possível validar a queue neste momento.'; }
    }
}

$uptime = 'Offline';
if ($session && !empty($session['acctstarttime'])) { $seconds = max(0, time() - strtotime($session['acctstarttime'])); $days = floor($seconds / 86400); $hours = floor(($seconds % 86400) / 3600); $minutes = floor(($seconds % 3600) / 60); $uptime = ($days ? $days . 'd ' : '') . $hours . 'h ' . $minutes . 'min'; }
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Diagnóstico</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f2f6fb;color:#18324a;font:14px Arial,sans-serif}.wrap{max-width:760px;margin:0 auto;padding:24px}.hero{background:linear-gradient(135deg,#146bd7,#194b87);color:#fff;padding:22px;border-radius:14px;margin-bottom:16px}.hero h1{font-size:21px;margin:0 0 6px}.hero p{margin:0;opacity:.9}.checks{display:grid;grid-template-columns:1fr 1fr;gap:12px}.check{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid #dbe6f1;border-radius:12px;padding:15px;min-height:105px}.state{display:grid;place-items:center;width:30px;height:30px;border-radius:50%;color:#fff;font-weight:bold;flex:none}.ok .state{background:#199659}.warn .state{background:#e69a17}.bad .state{background:#dc3545}.value{font-size:15px;font-weight:700;margin:7px 0 5px;overflow-wrap:anywhere}small{color:#65778a;line-height:1.35}.actions{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-top:18px;background:#fff;border:1px solid #dbe6f1;border-radius:12px;padding:14px}.repair{border:0;border-radius:8px;background:#1769db;color:#fff;font-weight:700;padding:11px 16px;cursor:pointer}.repair:disabled{opacity:.65}.cancel{border:1px solid #cbd8e5;border-radius:8px;background:#fff;color:#29465f;padding:10px 15px;cursor:pointer}.repair-progress{display:none;margin-top:14px;padding:16px;border-radius:12px;background:#fff;border:1px solid #bcd4ec}.repair-progress.show{display:block}.repair-progress.ok{border-color:#85d4a9;background:#f1fff7}.pulse{display:inline-block;width:10px;height:10px;border-radius:50%;background:#1684e8;margin-right:8px;animation:pulse 1s infinite}.repair-progress.ok .pulse{background:#18a05e;animation:none}@keyframes pulse{50%{opacity:.3}}@media(max-width:620px){.checks{grid-template-columns:1fr}.wrap{padding:12px}}
html{height:100%;overflow-y:auto!important;scrollbar-gutter:stable;scrollbar-width:thin;scrollbar-color:#8ba5c6 #edf3fb}body{min-height:100%;overflow:visible!important}::-webkit-scrollbar{width:7px}::-webkit-scrollbar-thumb{background:#8ba5c6;border-radius:8px}::-webkit-scrollbar-track{background:#edf3fb}@media(max-width:620px){.actions{flex-direction:column!important;align-items:stretch!important;gap:12px!important}.actions>span:last-child{display:grid!important;grid-template-columns:1fr 1fr;gap:8px!important;width:100%}.actions>span:first-child{line-height:1.45}.wrap{padding-bottom:16px}.hero h1{overflow-wrap:anywhere}}
@media(max-width:620px){body{padding-bottom:155px!important}.actions{position:fixed;z-index:20;left:0;right:0;bottom:0;margin:0!important;padding:12px!important;border-top:1px solid #cbd8e8;border-radius:0!important;background:#fff!important;box-shadow:0 -4px 15px #193b6014;gap:8px!important}.actions>span:first-child{font-size:11px!important;line-height:1.35!important}.actions button{min-height:38px}.checks{gap:9px}.check{padding:12px}.hero{padding:17px}.hero h1{font-size:19px}}
@media(max-width:620px){html{scrollbar-width:none!important;scrollbar-gutter:auto!important}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none;width:0;height:0}}
@media(max-width:620px){html,body{height:100%!important;overflow:hidden!important;scrollbar-gutter:auto!important}body{padding-bottom:0!important}.diagnostic-scroll{position:fixed;inset:0 0 var(--diagnostic-footer-height,115px) 0;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;scrollbar-width:none;-webkit-overflow-scrolling:touch}.diagnostic-scroll::-webkit-scrollbar{display:none!important;width:0!important}.diagnostic-scroll>.wrap:last-of-type{padding-bottom:12px}}
</style></head><body><div class="diagnostic-scroll"><main class="wrap"><section class="hero"><h1><?= diag_h($client['nome']); ?></h1><p>Login: <?= diag_h($client['login']); ?> • Diagnóstico realizado em <?= date('d/m/Y H:i:s'); ?></p></section><section class="checks">
<?php
$speed = trim((string) $client['planodown']) !== '' ? $client['planodown'] : $client['plano15'];
diag_row('Plano contratado', $client['plano'] . ($speed !== '' ? ' — ' . $speed : ''), $client['cli_ativado'] === 's' ? 'ok' : 'warn', $client['cli_ativado'] === 's' ? 'Cadastro do cliente está ativo.' : 'Cadastro do cliente está desativado.');
diag_row('Sessão e uptime', $uptime, $session ? 'ok' : 'warn', $session ? 'Online no IP ' . $session['framedipaddress'] . ' pelo concentrador ' . $session['nasipaddress'] . '.' : 'Nenhuma sessão ativa foi encontrada no RADIUS.', 'diagnostic-session');
diag_row('QoS / Queue de banda', $queueValue, $queueState, $queueDetail);
diag_row($additional?'Financeiro do titular':'Financeiro', $overdue > 0 ? $overdue . ' título(s) vencido(s)' : 'Financeiro OK', $overdue > 0 ? 'warn' : 'ok', $open . ' título(s) em aberto; ' . $overdue . ' vencido(s).');
?>
</section><div class="actions" style="justify-content:space-between"><span style="color:#52677b;font-size:13px"><strong>Aviso:</strong> o reparo derrubará a conexão do cliente e aguardará a reconexão PPPoE.</span><span style="display:flex;gap:10px;flex:none"><button class="cancel" type="button" onclick="parent.postMessage({type:'mka-content-modal-close'},'*')">Fechar</button><button class="repair" id="runRepair" type="button">Executar reparo</button></span></div><div class="repair-progress" id="repairProgress"><span class="pulse"></span><strong id="repairTitle">Executando reparo...</strong><div id="repairMessage" style="margin-top:7px">Aguarde enquanto o cadastro é atualizado.</div></div></main>
<div class="wrap" style="padding-top:0"><button class="repair" style="width:100%;transition:filter .18s" type="button" data-radius-login="<?= diag_h($login) ?>">LOG RADIUS DESTE CLIENTE</button></div>
</div>
<style>.repair:hover:not(:disabled){filter:brightness(.92)}.repair:focus-visible{outline:3px solid #93c5fd;outline-offset:2px}</style>
<script>(function(){var footer=document.querySelector('.actions');function fit(){document.documentElement.style.setProperty('--diagnostic-footer-height',Math.ceil(footer.getBoundingClientRect().height)+'px');}if(footer){fit();if(window.ResizeObserver)new ResizeObserver(fit).observe(footer);window.addEventListener('resize',fit);}})();</script>
<script src="../shared/radius_modal.js?v=20261006"></script>
<?php $repairConfig=array('login'=>$login,'additional'=>$additionalUuid,'csrf'=>$additionalCsrf,'baseline_session'=>$session?$session['radacctid']:null,'repair_url'=>$additional?'additional_connection_api.php':'../../reparar.'.(isset($links_ext)?$links_ext:'hhvm')); ?>
<script src="../shared/repair_progress.js?v=20261020-1" data-repair-config="<?= htmlspecialchars(json_encode($repairConfig),ENT_QUOTES,'UTF-8'); ?>"></script></body></html>
