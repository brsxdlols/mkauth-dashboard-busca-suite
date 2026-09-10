<?php
ob_start();
include('config.php');
ob_end_clean();
if (!isset($link) && isset($conn)) $link = $conn;
if (!isset($link) || !$link) { http_response_code(500); exit('Não foi possível conectar ao banco de dados.'); }

$login = isset($_GET['login']) ? trim((string) $_GET['login']) : '';
if ($login === '' || strlen($login) > 64) { http_response_code(422); exit('Cliente inválido.'); }

function diag_h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function diag_row($label, $value, $state, $detail) {
    $icon = $state === 'ok' ? '✓' : ($state === 'warn' ? '!' : '×');
    echo '<div class="check ' . $state . '"><span class="state">' . $icon . '</span><div><strong>' . diag_h($label) . '</strong><div class="value">' . diag_h($value) . '</div><small>' . diag_h($detail) . '</small></div></div>';
}

$client = null;
$stmt = mysqli_prepare($link, "SELECT nome, login, plano, planodown, plano15, cli_ativado FROM sis_cliente WHERE login = ? LIMIT 1");
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $login); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $client = $res ? mysqli_fetch_assoc($res) : null; mysqli_stmt_close($stmt); }
if (!$client) { http_response_code(404); exit('Cliente não encontrado.'); }

$session = null;
$stmt = mysqli_prepare($link, "SELECT framedipaddress, nasipaddress, acctstarttime, callingstationid FROM radacct WHERE LOWER(TRIM(username)) = LOWER(TRIM(?)) AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1");
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $login); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $session = $res ? mysqli_fetch_assoc($res) : null; mysqli_stmt_close($stmt); }

$overdue = 0; $open = 0;
$stmt = mysqli_prepare($link, "SELECT SUM(CASE WHEN status NOT LIKE 'pago' AND deltitulo = 0 THEN 1 ELSE 0 END) total_abertos, SUM(CASE WHEN status NOT LIKE 'pago' AND deltitulo = 0 AND datavenc < CURDATE() THEN 1 ELSE 0 END) total_vencidos FROM sis_lanc WHERE login = ?");
if ($stmt) { mysqli_stmt_bind_param($stmt, 's', $login); mysqli_stmt_execute($stmt); $res = mysqli_stmt_get_result($stmt); $finance = $res ? mysqli_fetch_assoc($res) : array(); $open = (int) $finance['total_abertos']; $overdue = (int) $finance['total_vencidos']; mysqli_stmt_close($stmt); }

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
</style></head><body><main class="wrap"><section class="hero"><h1><?= diag_h($client['nome']); ?></h1><p>Login: <?= diag_h($client['login']); ?> • Diagnóstico realizado em <?= date('d/m/Y H:i:s'); ?></p></section><section class="checks">
<?php
$speed = trim((string) $client['planodown']) !== '' ? $client['planodown'] : $client['plano15'];
diag_row('Plano contratado', $client['plano'] . ($speed !== '' ? ' — ' . $speed : ''), $client['cli_ativado'] === 's' ? 'ok' : 'warn', $client['cli_ativado'] === 's' ? 'Cadastro do cliente está ativo.' : 'Cadastro do cliente está desativado.');
diag_row('Sessão e uptime', $uptime, $session ? 'ok' : 'warn', $session ? 'Online no IP ' . $session['framedipaddress'] . ' pelo concentrador ' . $session['nasipaddress'] . '.' : 'Nenhuma sessão ativa foi encontrada no RADIUS.');
diag_row('QoS / Queue de banda', $queueValue, $queueState, $queueDetail);
diag_row('Financeiro', $overdue > 0 ? $overdue . ' título(s) vencido(s)' : 'Financeiro OK', $overdue > 0 ? 'warn' : 'ok', $open . ' título(s) em aberto; ' . $overdue . ' vencido(s).');
?>
</section><div style="margin-top:14px;padding:13px 15px;border:1px solid #f0cc75;border-radius:10px;background:#fff8df;color:#70520b;line-height:1.45"><strong>ℹ Atenção sobre a reconexão</strong><br>O tempo para o cliente voltar a conectar depende de cada equipamento CPE/roteador que realiza a discagem PPPoE. Alguns equipamentos solicitam a reconexão imediatamente; outros podem levar mais tempo conforme sua configuração.</div><div class="actions"><button class="cancel" type="button" onclick="parent.postMessage({type:'mka-content-modal-close'},'*')">Fechar</button><button class="repair" id="runRepair" type="button">Executar reparo</button></div><div class="repair-progress" id="repairProgress"><span class="pulse"></span><strong id="repairTitle">Executando reparo...</strong><div id="repairMessage" style="margin-top:7px">Aguarde enquanto o cadastro é atualizado.</div></div></main>
<script>
(function(){var button=document.getElementById('runRepair'),box=document.getElementById('repairProgress'),title=document.getElementById('repairTitle'),message=document.getElementById('repairMessage'),tries=0;
function poll(){fetch('client_connection_status.php?login='+encodeURIComponent(<?= json_encode($login); ?>),{credentials:'same-origin',cache:'no-store'}).then(function(r){return r.json();}).then(function(s){tries++;if(s.online){box.classList.add('ok');title.textContent='Cliente conectado';message.textContent='IP '+s.ip+' • uptime '+s.uptime+' • concentrador '+s.nas+'. Atualizado em tempo real.';button.disabled=false;button.textContent='Executar reparo novamente';return;}title.textContent='Reparo concluído — aguardando reconexão';message.textContent='Cliente ainda offline. O tempo depende da CPE que realiza a discagem PPPoE. Nova verificação em 3 segundos... ('+tries+'/30)';if(tries<30)setTimeout(poll,3000);else{title.textContent='Reparo concluído';message.textContent='A CPE ainda não solicitou uma nova conexão PPPoE. Você pode fechar o modal e verificar novamente mais tarde.';button.disabled=false;}}).catch(function(){if(tries++<30)setTimeout(poll,3000);else button.disabled=false;});}
button.addEventListener('click',function(){if(!confirm('Deseja executar agora o reparo deste cliente?'))return;button.disabled=true;box.className='repair-progress show';title.textContent='Executando reparo...';message.textContent='Aguarde enquanto o cadastro é atualizado.';var data=new FormData();data.append('login[]',<?= json_encode($login); ?>);fetch('../../reparar.<?= diag_h(isset($links_ext) ? $links_ext : 'hhvm'); ?>',{method:'POST',credentials:'same-origin',body:data}).then(function(r){if(!r.ok)throw new Error();title.textContent='Reparo executado — verificando conexão';message.textContent='Aguardando o cliente conectar novamente...';tries=0;setTimeout(poll,2000);}).catch(function(){title.textContent='Não foi possível executar o reparo';message.textContent='Tente novamente.';button.disabled=false;});});
}());
</script></body></html>
