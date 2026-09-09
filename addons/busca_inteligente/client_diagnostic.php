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

$queueState = 'warn'; $queueValue = 'Não verificada'; $queueDetail = 'Cliente offline ou concentrador indisponível.';
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
            } else { $queueValue = 'Queue não localizada'; $queueDetail = 'Não foi encontrada simple queue pelo login ou IP ativo.'; }
            $api->disconnect();
        } else { $queueValue = 'API do concentrador indisponível'; $queueDetail = 'Não foi possível validar a queue neste momento.'; }
    }
}

$uptime = 'Offline';
if ($session && !empty($session['acctstarttime'])) { $seconds = max(0, time() - strtotime($session['acctstarttime'])); $days = floor($seconds / 86400); $hours = floor(($seconds % 86400) / 3600); $minutes = floor(($seconds % 3600) / 60); $uptime = ($days ? $days . 'd ' : '') . $hours . 'h ' . $minutes . 'min'; }
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Diagnóstico</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f2f6fb;color:#18324a;font:14px Arial,sans-serif}.wrap{max-width:760px;margin:0 auto;padding:24px}.hero{background:linear-gradient(135deg,#146bd7,#194b87);color:#fff;padding:22px;border-radius:14px;margin-bottom:16px}.hero h1{font-size:21px;margin:0 0 6px}.hero p{margin:0;opacity:.9}.checks{display:grid;grid-template-columns:1fr 1fr;gap:12px}.check{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid #dbe6f1;border-radius:12px;padding:15px;min-height:105px}.state{display:grid;place-items:center;width:30px;height:30px;border-radius:50%;color:#fff;font-weight:bold;flex:none}.ok .state{background:#199659}.warn .state{background:#e69a17}.bad .state{background:#dc3545}.value{font-size:15px;font-weight:700;margin:7px 0 5px;overflow-wrap:anywhere}small{color:#65778a;line-height:1.35}.actions{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-top:18px;background:#fff;border:1px solid #dbe6f1;border-radius:12px;padding:14px}.repair{border:0;border-radius:8px;background:#1769db;color:#fff;font-weight:700;padding:11px 16px;cursor:pointer}.cancel{border:1px solid #cbd8e5;border-radius:8px;background:#fff;color:#29465f;padding:10px 15px;cursor:pointer}@media(max-width:620px){.checks{grid-template-columns:1fr}.wrap{padding:12px}}
</style></head><body><main class="wrap"><section class="hero"><h1><?= diag_h($client['nome']); ?></h1><p>Login: <?= diag_h($client['login']); ?> • Diagnóstico realizado em <?= date('d/m/Y H:i:s'); ?></p></section><section class="checks">
<?php
$speed = trim((string) $client['planodown']) !== '' ? $client['planodown'] : $client['plano15'];
diag_row('Plano contratado', $client['plano'] . ($speed !== '' ? ' — ' . $speed : ''), $client['cli_ativado'] === 's' ? 'ok' : 'warn', $client['cli_ativado'] === 's' ? 'Cadastro do cliente está ativo.' : 'Cadastro do cliente está desativado.');
diag_row('Sessão e uptime', $uptime, $session ? 'ok' : 'warn', $session ? 'Online no IP ' . $session['framedipaddress'] . ' pelo concentrador ' . $session['nasipaddress'] . '.' : 'Nenhuma sessão ativa foi encontrada no RADIUS.');
diag_row('Queue de banda', $queueValue, $queueState, $queueDetail);
diag_row('Financeiro', $overdue > 0 ? $overdue . ' título(s) vencido(s)' : 'Financeiro OK', $overdue > 0 ? 'warn' : 'ok', $open . ' título(s) em aberto; ' . $overdue . ' vencido(s).');
?>
</section><div class="actions"><button class="cancel" type="button" onclick="parent.postMessage({type:'mka-content-modal-close'},'*')">Fechar</button><form method="post" action="../../reparar.<?= diag_h(isset($links_ext) ? $links_ext : 'hhvm'); ?>" onsubmit="return confirm('Deseja executar agora o reparo deste cliente?');"><input type="hidden" name="login[]" value="<?= diag_h($login); ?>"><button class="repair" type="submit">Executar reparo</button></form></div></main></body></html>
