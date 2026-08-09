<?php
$dashTopProvider = 'MK-AUTH';
$dashTopCompany = '';
$dashTopUser = isset($usuario_logado) ? (string) $usuario_logado : ($_SESSION['MKA_Usuario'] ?? ($_SESSION['MM_Usuario'] ?? ''));
$dashTopDate = date('d/m H:i');
$dashTopLast = '';
$dashTopIp = $_SERVER['REMOTE_ADDR'] ?? '';
$dashTopRam = '';
$dashTopCpu = '';
$dashTopDisk = '';
$dashTopSessions = $dashTopUser !== '' ? '01' : '00';

if (isset($conn) && $conn instanceof mysqli) {
    if ($q = @mysqli_query($conn, 'SELECT nome, razao FROM sis_provedor LIMIT 1')) {
        if ($r = mysqli_fetch_assoc($q)) {
            $dashTopProvider = $r['nome'] ?: $dashTopProvider;
            $dashTopCompany = $r['razao'] ?: $dashTopCompany;
        }
    }

    $safeUser = mysqli_real_escape_string($conn, $dashTopUser);
    if ($safeUser !== '' && $q = @mysqli_query($conn, "SELECT ultacesso, sesid FROM sis_acesso WHERE login = '$safeUser' LIMIT 1")) {
        if ($r = mysqli_fetch_assoc($q)) {
            $dashTopLast = $r['ultacesso'] ?? '';
            $dashTopSessions = !empty($r['sesid']) ? '01' : '00';
        }
    }
}

$free = trim((string) @shell_exec('free | awk \'/Mem:/ {printf "%d%%", $3/$2*100}\''));
$disk = trim((string) @shell_exec('df / | awk \'NR==2 {print $5}\''));
$nproc = (int) trim((string) @shell_exec('nproc 2>/dev/null'));
$load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0];
if ($free !== '') $dashTopRam = $free;
if ($disk !== '') $dashTopDisk = $disk;
if ($nproc > 0) $dashTopCpu = min(100, (int) round(($load[0] / $nproc) * 100)) . '%';
if (!defined('ADMIN2URL')) define('ADMIN2URL', '/admin/');
?>
<style>
html.has-navbar-fixed-top {
    padding-top: 3.25rem !important;
}
#sistema-corpo1 {
    display: none !important;
    height: 0 !important;
}
#sistema-corpo2 {
    margin-top: 0 !important;
    padding-top: 0 !important;
}
#systopo,
#systopo.is-invisible {
    visibility: visible !important;
}
#systopo {
    margin-top: 8px !important;
    margin-bottom: 12px !important;
}
nav.navbar.is-fixed-top:not(#mkauth-dashboard-navbar) {
    display: none !important;
}
#mkauth-dashboard-navbar {
    z-index: 1030;
}
</style>
<nav id="mkauth-dashboard-navbar" class="navbar is-fixed-top is-warning" role="navigation" aria-label="dropdown navigation">
    <div class="navbar-brand">
        <a class="navbar-item is-hidden-desktop" id="navbar-mka" data-target="navMenu"><i class="bi-list"></i></a>
        <a class="navbar-item" href="/admin/index<?php echo $ext_mk; ?>">
            <img src="data:image/png;base64,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" height="28">
        </a>
        <a class="navbar-item is-hidden-desktop" href="/admin/logout<?php echo $ext_mk; ?>" id="mkaExit1"><i class="bi-lock-fill"></i></a>
    </div>
    <div id="navMenu" class="navbar-menu">
        <div class="navbar-start">
            <div class="navbar-item has-dropdown is-hoverable is-mega">
                <div class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-diagram-3-fill is-hidden-desktop-only"></i>&nbsp; PROVEDOR</div>
                <div class="navbar-dropdown">
                    <div class="container is-fluid"><div class="columns">
                        <div class="column" id="menu_provedor"></div><div class="is-divisor-vertical"></div>
                        <div class="column" id="menu_provedor2"></div><div class="is-divisor-vertical"></div>
                        <div class="column" id="menu_provedor3"></div>
                    </div></div>
                </div>
            </div>
            <div class="navbar-item has-dropdown is-hoverable"><a class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-gear-fill is-hidden-desktop-only"></i>&nbsp; OP&Ccedil;&Otilde;ES</a><div class="navbar-dropdown" id="menu_opcoes"></div></div>
            <div class="navbar-item has-dropdown is-hoverable is-mega">
                <div class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-people-fill is-hidden-desktop-only"></i>&nbsp; CLIENTES</div>
                <div class="navbar-dropdown"><div class="container is-fluid"><div class="columns">
                    <div class="column" id="menu_clientes"></div><div class="is-divisor-vertical"></div>
                    <div class="column" id="menu_clientes2"></div><div class="is-divisor-vertical"></div>
                    <div class="column" id="menu_clientes3"></div>
                </div></div></div>
            </div>
            <div class="navbar-item has-dropdown is-hoverable is-mega">
                <div class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-cash-coin is-hidden-desktop-only"></i>&nbsp; FINANCEIRO</div>
                <div class="navbar-dropdown"><div class="container is-fluid"><div class="columns">
                    <div class="column" id="menu_financeiro"></div><div class="is-divisor-vertical"></div>
                    <div class="column" id="menu_financeiro2"></div><div class="is-divisor-vertical"></div>
                    <div class="column" id="menu_financeiro3"></div><div class="is-divisor-vertical"></div>
                    <div class="column" id="menu_financeiro4"></div>
                </div></div></div>
            </div>
            <div class="navbar-item has-dropdown is-hoverable is-mega">
                <div class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-life-preserver is-hidden-desktop-only"></i>&nbsp; SUPORTE</div>
                <div class="navbar-dropdown"><div class="container is-fluid"><div class="columns">
                    <div class="column" id="menu_suporte"></div><div class="is-divisor-vertical"></div>
                    <div class="column" id="menu_suporte2"></div>
                </div></div></div>
            </div>
            <div class="navbar-item has-dropdown is-hoverable"><a class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-house-gear-fill is-hidden-desktop-only"></i>&nbsp; CENTRAL</a><div class="navbar-dropdown" id="menu_central"></div></div>
            <div class="navbar-item has-dropdown is-hoverable"><a class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-browser-chrome is-hidden-desktop-only"></i>&nbsp; HOTSITE</a><div class="navbar-dropdown" id="menu_hotsite"></div></div>
            <div class="navbar-item has-dropdown is-hoverable"><a class="navbar-link is-size-7 has-text-weight-bold"><i class="bi-wifi is-hidden-desktop-only"></i>&nbsp; CONEX&Otilde;ES</a><div class="navbar-dropdown" id="menu_conexoes"></div></div>
        </div>
        <div class="navbar-end"><a class="navbar-item is-hidden-touch" href="/admin/logout<?php echo $ext_mk; ?>" id="mkaExit2"><i class="bi-lock-fill"></i></a></div>
    </div>
</nav>
<script src="../../menu.js<?php echo $ext_mk; ?>"></script>
<script>
jQuery(function($){
    $('#systopo').removeClass('is-invisible').css('visibility','visible');
    $('#UUE_NProvedor').text(<?php echo json_encode($dashTopCompany !== '' ? $dashTopCompany : $dashTopProvider); ?>);
    $('#UUE_Usuario').text(<?php echo json_encode($dashTopUser); ?>);
    $('#UUE_SESSOES').text(<?php echo json_encode($dashTopSessions); ?>);
    $('#UUE_Data').text(<?php echo json_encode($dashTopDate); ?>);
    $('#UUE_Ultacesso').text(<?php echo json_encode($dashTopLast); ?>);
    $('#UUE_IP').text(<?php echo json_encode($dashTopIp); ?>);
    $('#UUE_RAM').text(<?php echo json_encode($dashTopRam); ?>);
    $('#UUE_CPU').text(<?php echo json_encode($dashTopCpu); ?>);
    $('#UUE_DISCO').text(<?php echo json_encode($dashTopDisk); ?>);
    $('#UUE_Internet').text('ONLINE');
});
</script>