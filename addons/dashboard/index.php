<?php
include('config.php');
require_once __DIR__ . '/../shared/layout_mode.php';

if (mka_suite_get_layout_mode($conn) === 'legado') {
    header('Location: ../dashboard-legado/');
    exit;
}
?>

<!DOCTYPE html>
<?php


if (isset($_SESSION['MM_Usuario'])) {
    echo '<html lang="pt-BR">'; // Fix versão antiga MK-AUTH
} else {
    echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
   
}

?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">

    <title>MK - AUTH :: <?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'};  ?></title>

    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css">
    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

    <link href="css/estilo.css" rel="stylesheet" type="text/css" />

    <style>
        html,
        body,
        .container-fluid,
        .container,
        .row,
        .card,
        .card-body,
        .card-header {
            background-color: #ffffff;
        }

        body {
            background: #ffffff !important;
            color: #243041;
        }

        html,
        body,
        #pagina,
        #conteudo,
        .container-fluid {
            background: #ffffff !important;
        }

        .dashboard-surface {
            border: 1px solid rgba(203, 213, 225, 0.72);
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 14px 42px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .dashboard-section-title {
            margin: 0;
            padding: 16px 18px 10px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #223048;
        }

        .dashboard-section-body {
            padding: 6px 12px 14px;
        }

        .dashboard-stat-grid {
            display: grid !important;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: 10px;
        }

        .dashboard-stat-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 126px;
            padding: 12px 14px 14px;
            border-radius: 16px;
            text-decoration: none !important;
            color: inherit !important;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
            overflow: hidden;
        }

        .dashboard-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.14);
            filter: saturate(1.03);
        }

        .dashboard-stat-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0) 42%);
            pointer-events: none;
        }

        .dashboard-stat-head,
        .dashboard-stat-foot,
        .dashboard-stat-value {
            position: relative;
            z-index: 1;
        }

        .dashboard-stat-head {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.15;
        }

        .dashboard-stat-value {
            margin: 12px 0 0;
            width: 100%;
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-inline: 2px;
            font-size: clamp(2.45rem, 1.35vw + 1.35rem, 3.35rem);
            line-height: 0.9;
            font-weight: 300;
            letter-spacing: 0;
            text-align: center;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            box-sizing: border-box;
            overflow: hidden;
        }

        .dashboard-stat-value--digits-5 {
            font-size: clamp(2.15rem, 1.15vw + 1.2rem, 2.85rem);
        }

        .dashboard-stat-value--digits-6-plus {
            font-size: clamp(1.85rem, 0.95vw + 1.05rem, 2.4rem);
        }

        .dashboard-stat-foot {
            margin-top: 12px;
            padding-top: 9px;
            font-size: 1.12rem;
            font-weight: 600;
            line-height: 1.15;
            text-align: center;
            border-top: 1px solid rgba(15, 23, 42, 0.10);
        }

        .dashboard-stat-card.is-primary {
            background: linear-gradient(180deg, #2f80ff 0%, #1f6de8 100%);
            color: #ffffff !important;
        }

        .dashboard-stat-card.is-light {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #1f2937 !important;
            border: 1px solid rgba(203, 213, 225, 0.88);
        }

        .dashboard-stat-card.is-info {
            background: linear-gradient(180deg, #35c8eb 0%, #22b8db 100%);
            color: #0f172a !important;
        }

        .dashboard-stat-card.is-observation {
            background: linear-gradient(180deg, #96edba 0%, #80E7AB 100%);
            color: #0f5132 !important;
        }

        .dashboard-stat-card.is-danger {
            background: linear-gradient(180deg, #eb455a 0%, #df3148 100%);
            color: #ffffff !important;
        }

        .dashboard-stat-card.is-warning {
            background: linear-gradient(180deg, #ffcf35 0%, #ffc107 100%);
            color: #2f2500 !important;
        }

        .dashboard-stat-card.is-success {
            background: linear-gradient(180deg, #21945a 0%, #1f8b54 100%);
            color: #ffffff !important;
        }

        .dashboard-stat-card.is-dark {
            background: linear-gradient(180deg, #2b3138 0%, #1f242b 100%);
            color: #ffffff !important;
        }

        .dashboard-stat-card.is-outline-danger {
            background: linear-gradient(180deg, #ffffff 0%, #fffdfd 100%);
            color: #e23f53 !important;
            border: 1px solid rgba(248, 113, 113, 0.72);
        }

        .dashboard-stat-card.is-outline-danger .dashboard-stat-foot {
            border-top-color: rgba(248, 113, 113, 0.22);
        }

        .dashboard-attendance-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .dashboard-quick-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            padding: 8px 8px 2px;
        }

        .dashboard-quick-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none !important;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }

        .dashboard-quick-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.14);
            filter: brightness(1.02);
        }

        .dashboard-quick-link.is-primary {
            background: linear-gradient(180deg, #2f80ff 0%, #1f6de8 100%);
            color: #ffffff !important;
        }

        .dashboard-quick-link.is-success {
            background: linear-gradient(180deg, #23995f 0%, #1d8d56 100%);
            color: #ffffff !important;
        }

        .dashboard-quick-link.is-secondary {
            background: linear-gradient(180deg, #6f7a86 0%, #5d6773 100%);
            color: #ffffff !important;
        }

        .dashboard-ramal-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
        }

        .dashboard-ramal-panel {
            border: 1px solid rgba(203, 213, 225, 0.9);
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
        }

        .dashboard-ramal-title {
            padding: 12px 14px 10px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: #223048;
            border-bottom: 1px solid rgba(203, 213, 225, 0.8);
        }

        .dashboard-ramal-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 6px;
            padding: 8px;
        }

        .dashboard-ramal-stats .dashboard-stat-card {
            min-height: 132px;
            padding: 12px 12px 14px;
            border-radius: 12px;
            aspect-ratio: auto;
        }

        .dashboard-ramal-stats .dashboard-stat-head {
            font-size: 12px;
        }

        .dashboard-ramal-stats .dashboard-stat-value {
            margin-top: 14px;
            font-size: clamp(2rem, 0.8vw + 1.2rem, 2.45rem);
            line-height: 0.94;
            white-space: nowrap;
        }

        .dashboard-ramal-stats .dashboard-stat-foot {
            display: block;
            margin-top: 14px;
            padding-top: 10px;
            font-size: 1rem;
            font-weight: 700;
        }

        .dashboard-hero-space {
            background: #ffffff;
        }

        @media (max-width: 1399.98px) {
            .dashboard-stat-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        @media (max-width: 1199.98px) {
            .dashboard-stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-attendance-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-section-title {
                padding: 14px 14px 8px;
            }

            .dashboard-section-body {
                padding: 4px 8px 12px;
            }

            .dashboard-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .dashboard-attendance-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
            }

            .dashboard-stat-card {
                min-height: 132px;
                padding: 10px 10px 12px;
                border-radius: 14px;
            }

            .dashboard-stat-head {
                font-size: 12px;
            }

            .dashboard-stat-value {
                font-size: clamp(2.4rem, 5vw + 1rem, 3.1rem);
            }

            .dashboard-stat-foot {
                margin-top: 12px;
                padding-top: 8px;
                font-size: 1.08rem;
            }

            .dashboard-ramal-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-ramal-stats .dashboard-stat-card {
                min-height: 148px;
            }

            .dashboard-ramal-stats .dashboard-stat-value {
                font-size: clamp(2rem, 3vw + 0.8rem, 2.75rem);
            }

            .dashboard-ramal-stats .dashboard-stat-foot {
                font-size: 1.02rem;
                margin-top: 12px;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-attendance-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-stat-card {
                min-height: 138px;
            }

            .dashboard-quick-links {
                justify-content: flex-start;
            }

            .dashboard-ramal-stats .dashboard-stat-card {
                min-height: 154px;
                padding: 10px 10px 14px;
            }

            .dashboard-ramal-stats .dashboard-stat-head {
                font-size: 11px;
            }

            .dashboard-ramal-stats .dashboard-stat-value {
                font-size: clamp(1.9rem, 4.8vw + 0.6rem, 2.65rem);
            }

            .dashboard-ramal-stats .dashboard-stat-foot {
                font-size: 1rem;
                margin-top: 12px;
                padding-top: 10px;
            }
        }

        .installation-alert-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7d6 0%, #fff1b1 100%);
            border: 1px solid rgba(229, 194, 84, 0.45);
            box-shadow: 0 10px 24px rgba(168, 120, 0, 0.12);
            color: #5f4300;
            text-decoration: none;
        }

        .installation-alert-link:hover {
            color: #5f4300;
            text-decoration: none;
            box-shadow: 0 12px 28px rgba(168, 120, 0, 0.18);
        }

        .installation-alert-main {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .installation-alert-icon {
            position: relative;
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.72);
            color: #b77900;
            font-size: 24px;
            flex: 0 0 auto;
        }

        .installation-alert-icon::after {
            content: "Novo";
            position: absolute;
            margin-top: 44px;
            margin-left: 38px;
            padding: 2px 6px;
            border-radius: 999px;
            background: #16a34a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 6px 12px rgba(22, 163, 74, 0.18);
        }

        .installation-alert-title {
            margin: 0 0 2px;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.25;
        }

        #solicitacoes-instalacao {
            scroll-margin-top: 110px;
        }

        .installation-highlight-target {
            position: relative;
            border-radius: 14px;
            transition: box-shadow 0.35s ease, background-color 0.35s ease;
        }

        .installation-highlight-target.is-flashing {
            animation: installation-highlight-fade 2.2s ease forwards;
        }

        @keyframes installation-highlight-fade {
            0% {
                background-color: rgba(255, 244, 186, 0.96);
                box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.78), 0 16px 36px rgba(234, 179, 8, 0.20);
            }
            55% {
                background-color: rgba(255, 248, 216, 0.78);
                box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.64), 0 12px 28px rgba(234, 179, 8, 0.14);
            }
            100% {
                background-color: transparent;
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0), 0 0 0 rgba(234, 179, 8, 0);
            }
        }

        .installation-alert-copy {
            margin: 0;
            font-size: 13px;
            line-height: 1.4;
            color: #7a5b10;
        }

        .installation-alert-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.86);
            color: #7a4f00;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        @media (max-width: 767.98px) {
            .installation-alert-link {
                align-items: flex-start;
                flex-direction: column;
            }

            .installation-alert-cta {
                width: 100%;
            }
        }

        .dashboard-session-toast-stack {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1060;
            width: min(360px, calc(100vw - 32px));
            display: grid;
            gap: 12px;
            pointer-events: none;
        }

        .dashboard-session-toast-stack.is-minimized {
            width: auto;
            gap: 8px;
        }

        .dashboard-session-toast-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 0 4px;
            pointer-events: auto;
        }

        .dashboard-session-toast-toolbar.is-hidden {
            display: none;
        }

        .dashboard-session-toast-list {
            display: grid;
            gap: 12px;
        }

        .dashboard-session-toast-stack.is-minimized .dashboard-session-toast-list {
            display: none;
        }

        .dashboard-session-toast-stack.is-minimized .dashboard-session-toast-toolbar {
            justify-content: flex-end;
        }

        .dashboard-session-toast-stack.is-minimized .dashboard-session-toast-toolbar.is-hidden {
            display: flex;
        }

        .dashboard-session-toast {
            position: relative;
            display: flex;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(25, 43, 72, 0.08);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 16px 38px rgba(26, 41, 64, 0.18);
            color: #243041;
            pointer-events: auto;
            opacity: 0;
            transform: translateY(-12px);
            animation: dashboardToastIn 0.28s ease forwards;
            backdrop-filter: blur(10px);
        }

        .dashboard-session-toast.is-login {
            border-left: 5px solid #1e9b5d;
        }

        .dashboard-session-toast.is-logout {
            border-left: 5px solid #f05d5e;
        }

        .dashboard-client-state-toast-stack {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1075;
            width: min(900px, calc(100vw - 28px));
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .dashboard-client-state-toast-stack .dashboard-session-toast { pointer-events: auto; }

        .dashboard-session-toast.is-client-state {
            border: 1px solid #0f5dcc;
            border-left: 5px solid #0b4fae;
            border-radius: 999px;
            padding: 10px 44px 10px 12px;
            align-items: center;
            background: linear-gradient(135deg, #2478e9 0%, #1264d6 100%);
            color: #fff;
            box-shadow: 0 16px 38px rgba(18,100,214,.30);
        }
        .dashboard-session-toast.is-client-state.is-client-state-blocked { border-color:#b91c1c; border-left-color:#7f1d1d; background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%); box-shadow:0 16px 38px rgba(220,38,38,.30); }
        .dashboard-session-toast.is-client-state.is-client-state-unlocked { border-color:#15803d; border-left-color:#14532d; background:linear-gradient(135deg,#22a95b 0%,#168447 100%); box-shadow:0 16px 38px rgba(22,132,71,.30); }
        .dashboard-session-toast.is-client-state.is-client-state-trust { border-color:#d69e00; border-left-color:#a16207; background:linear-gradient(135deg,#facc15 0%,#eab308 100%); box-shadow:0 16px 38px rgba(234,179,8,.30); color:#3f3000; }
        .dashboard-session-toast.is-client-state.is-client-state-trust .dashboard-session-toast-label,
        .dashboard-session-toast.is-client-state.is-client-state-trust .dashboard-session-toast-title,
        .dashboard-session-toast.is-client-state.is-client-state-trust .dashboard-session-toast-meta { color:#3f3000; }
        .dashboard-session-toast.is-client-state.is-client-state-trust .dashboard-session-toast-label { color:rgba(63,48,0,.72); }
        .dashboard-session-toast.is-client-state.is-client-state-trust .dashboard-session-toast-icon { color:#3f3000; background:rgba(255,255,255,.30); }
        .dashboard-session-toast.is-client-state.is-client-state-trust .dashboard-session-toast-close { color:rgba(63,48,0,.72); }

        .dashboard-session-toast.is-client-state .dashboard-session-toast-icon { width:34px; height:34px; border-radius:50%; font-size:15px; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-content { display:grid; grid-template-columns:minmax(210px,1.2fr) minmax(240px,1fr) auto; align-items:center; gap:10px 20px; }
        .dashboard-client-state-identity { min-width:0; overflow:hidden; }
        .dashboard-client-state-identity .dashboard-session-toast-title { display:block; max-width:100%; overflow:hidden; text-overflow:ellipsis; }
        .dashboard-client-state-context { display:flex; align-items:center; gap:12px; min-width:0; overflow:hidden; }
        .dashboard-client-state-context .dashboard-session-toast-meta { width:100%; flex-direction:column; align-items:flex-start; gap:2px; min-width:0; overflow:hidden; }
        .dashboard-client-state-context .dashboard-session-toast-meta span { display:block; width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-label,
        .dashboard-session-toast.is-client-state .dashboard-session-toast-title,
        .dashboard-session-toast.is-client-state .dashboard-session-toast-description,
        .dashboard-session-toast.is-client-state .dashboard-session-toast-meta { margin:0; white-space:nowrap; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-status { margin:0; white-space:nowrap; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-label,
        .dashboard-session-toast.is-client-state .dashboard-session-toast-title,
        .dashboard-session-toast.is-client-state .dashboard-session-toast-description,
        .dashboard-session-toast.is-client-state .dashboard-session-toast-meta { color:#fff; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-label { color:rgba(255,255,255,.78); }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-icon { background:rgba(255,255,255,.18); box-shadow:inset 0 0 0 1px rgba(255,255,255,.22); }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-status { background:rgba(255,255,255,.94); color:#174a83; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-status.is-online { background:#16a34a; color:#fff; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-status.is-offline { background:#475569; color:#fff; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-status.is-disconnected { background:#f59e0b; color:#fff; }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-close { color:rgba(255,255,255,.82); }
        .dashboard-session-toast.is-client-state .dashboard-session-toast-close:hover { color:#fff; }

        @media (max-width:760px) {
            .dashboard-client-state-toast-stack { bottom:12px; width:calc(100vw - 20px); }
            .dashboard-session-toast.is-client-state { border-radius:18px; align-items:flex-start; }
            .dashboard-session-toast.is-client-state .dashboard-session-toast-content { display:flex; align-items:flex-start; justify-content:flex-start; flex-wrap:wrap; gap:6px 10px; }
            .dashboard-client-state-identity { width:100%; }
            .dashboard-client-state-context { flex-wrap:wrap; }
            .dashboard-session-toast.is-client-state .dashboard-session-toast-description { width:100%; white-space:normal; }
        }

        .dashboard-session-toast.is-fading {
            animation: dashboardToastOut 0.55s ease forwards;
        }

        .dashboard-session-toast-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #ffffff;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .dashboard-session-toast.is-login .dashboard-session-toast-icon {
            background: linear-gradient(135deg, #23b26d 0%, #15824e 100%);
        }

        .dashboard-session-toast.is-logout .dashboard-session-toast-icon {
            background: linear-gradient(135deg, #ff7b7d 0%, #e04143 100%);
        }

        .dashboard-session-toast.is-client-state .dashboard-session-toast-icon { background:rgba(255,255,255,.18); }

        .dashboard-session-toast-content {
            min-width: 0;
            flex: 1 1 auto;
        }

        .dashboard-session-toast-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7c8aa5;
        }

        .dashboard-session-toast-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.28;
            color: #22324d;
        }

        .dashboard-session-toast-description {
            margin: 3px 0 0;
            color: #728298;
            font-size: 11px;
            line-height: 1.35;
        }

        .dashboard-session-toast-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            margin-top: 7px;
            font-size: 12px;
            color: #61708d;
        }

        .dashboard-session-toast-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .dashboard-session-toast-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            width: fit-content;
        }

        .dashboard-session-toast-status.is-active {
            background: rgba(31, 143, 78, 0.10);
            color: #18794a;
        }

        .dashboard-session-toast-status.is-blocked {
            background: rgba(239, 68, 68, 0.12);
            color: #c0392b;
        }

        .dashboard-session-toast-status.is-inactive {
            background: rgba(245, 158, 11, 0.14);
            color: #9a6700;
        }

        .dashboard-session-toast-status.is-online { background: rgba(31,143,78,.10); color:#18794a; }
        .dashboard-session-toast-status.is-offline { background:#eef2f6; color:#475569; }
        .dashboard-session-toast-status.is-disconnected { background:#fff3d6; color:#946000; }

        .dashboard-session-toast-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .dashboard-radius-retest {
            border: 0;
            border-radius: 9px;
            background: #2563eb;
            color: #fff;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .dashboard-radius-retest:disabled { opacity: .65; cursor: wait; }
        .dashboard-radius-result { font-size: 11px; color: #61708d; }

        .dashboard-session-toast-close {
            position: absolute;
            top: 8px;
            right: 9px;
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
        }

        .dashboard-session-toast-close:hover { color: #0f172a; }

        .dashboard-session-toast-link {
            border: 0;
            background: transparent;
            color: #5f6f82;
            padding: 0;
            font-size: 12px;
            font-weight: 600;
            text-decoration: underline;
            cursor: pointer;
        }

        .dashboard-session-toast-link:hover {
            color: #153a5b;
        }

        .dashboard-session-toast-link.is-secondary {
            color: #475569;
        }

        .dashboard-session-toast.is-guide {
            border-left: 5px solid #2563eb;
        }

        .dashboard-session-toast.is-guide .dashboard-session-toast-icon {
            background: linear-gradient(135deg, #60a5fa 0%, #2563eb 100%);
        }

        @keyframes dashboardToastIn {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes dashboardToastOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-session-toast-stack {
                top: 68px;
                right: 10px;
                left: 10px;
            width: auto;
            }
        }
    </style>


</head>

<body class="mka-suite-dashboard-page">
    <?php if (!defined('ADMIN2URL')) define('ADMIN2URL', '/admin/'); ?>
    <?php include('../../topo.php'); ?>
    <?php include('mkauth_dashboard_top.php'); ?>
    <?php mka_suite_render_top_spacing_style($conn); ?>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>


    <div class='container-fluid mka-suite-dashboard-start'>
        <?php

        $query_cria_tabelas = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dashboard_am_sis_cfg (
            id int NOT NULL AUTO_INCREMENT,
            exb_ticket_medio VARCHAR(1) NOT NULL DEFAULT 's',
            exb_saldo_conta VARCHAR(1) NOT NULL DEFAULT 's',
            exb_clientes_ramal VARCHAR(1) NOT NULL DEFAULT 'n',
            exb_balanco_faturamento VARCHAR(1) NOT NULL DEFAULT 's',
            exb_balanco_clientes VARCHAR(1) NOT NULL DEFAULT 's',
            exb_balanco_chamados VARCHAR(1) NOT NULL DEFAULT 's',
            exb_busca_inteligente VARCHAR(1) NOT NULL DEFAULT 's',
            contabilizar_bloq_offline VARCHAR(1) NOT NULL DEFAULT 's',
            exb_graficos_em_baixo VARCHAR(1) NOT NULL DEFAULT 'n',
            tbl_logs_sistema VARCHAR(1) NOT NULL DEFAULT 's',
            tbl_chamados_abertos VARCHAR(1) NOT NULL DEFAULT 's',
            tbl_contas_pagar VARCHAR(1) NOT NULL DEFAULT 's',
            popup_clientes_sessao VARCHAR(1) NOT NULL DEFAULT 'n',
            popup_clientes_sessao_duracao INT NOT NULL DEFAULT 2,
            qtd_meses_graficos INT NOT NULL DEFAULT 3,
            limite_ticket INT NOT NULL DEFAULT 1000,
            link TEXT,
            texto TEXT,
            PRIMARY KEY (id)
        )");


        $query = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'link'");

        if (mysqli_num_rows($query) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
        ADD link TEXT,
        ADD texto TEXT
        AFTER limite_ticket");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query2 = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'contabilizar_bloq_offline'");

        if (mysqli_num_rows($query2) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD contabilizar_bloq_offline VARCHAR(1) NOT NULL DEFAULT 's',
            ADD exb_graficos_em_baixo VARCHAR(1) NOT NULL DEFAULT 'n'
            AFTER exb_busca_inteligente");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query3 = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'tot_acesso_rapido'");

        if (mysqli_num_rows($query3) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD tot_acesso_rapido INT NOT NULL DEFAULT '15'
            AFTER texto");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query4 = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'exb_clientes_ramal'");

        if (mysqli_num_rows($query4) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD exb_clientes_ramal VARCHAR(1) NOT NULL DEFAULT 'n'
            AFTER exb_saldo_conta");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query5 = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'popup_clientes_sessao'");

        if (mysqli_num_rows($query5) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD popup_clientes_sessao VARCHAR(1) NOT NULL DEFAULT 'n'
            AFTER tbl_contas_pagar");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query5b = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_am_sis_cfg WHERE field = 'popup_clientes_sessao_duracao'");

        if (mysqli_num_rows($query5b) == 0) {
            $query_alterar_table = mysqli_query($conn, "ALTER TABLE dashboard_am_sis_cfg 
            ADD popup_clientes_sessao_duracao INT NOT NULL DEFAULT 2
            AFTER popup_clientes_sessao");

            if (!$query_alterar_table) {
                echo mysqli_error($conn);
            }
        }

        $query_atual_cfg = mysqli_query($conn, "SELECT * FROM dashboard_am_sis_cfg");
        if (mysqli_num_rows($query_atual_cfg) == 0) {
            $query_cfg_inicial = mysqli_query($conn, "INSERT INTO dashboard_am_sis_cfg (id) VALUES (1)");
        }


        // Ler Configuracoes
        while ($cfg = mysqli_fetch_array($query_atual_cfg)) {
            $exb_ticket_medio = $cfg['exb_ticket_medio'];
            $exb_saldo_conta = $cfg['exb_saldo_conta'];
            $exb_clientes_ramal = $cfg['exb_clientes_ramal'];
            $exb_balanco_faturamento = $cfg['exb_balanco_faturamento'];
            $exb_balanco_clientes = $cfg['exb_balanco_clientes'];
            $exb_balanco_chamados = $cfg['exb_balanco_chamados'];
            $exb_busca_inteligente = $cfg['exb_busca_inteligente'];
            $contabilizar_bloq_offline = $cfg['contabilizar_bloq_offline'];
            $exb_graficos_em_baixo = $cfg['exb_graficos_em_baixo'];
            $tbl_logs_sistema = $cfg['tbl_logs_sistema'];
            $tbl_chamados_abertos = $cfg['tbl_chamados_abertos'];
            $tbl_contas_pagar = $cfg['tbl_contas_pagar'];
            $popup_clientes_sessao = isset($cfg['popup_clientes_sessao']) ? $cfg['popup_clientes_sessao'] : 'n';
            $popup_clientes_sessao_duracao = isset($cfg['popup_clientes_sessao_duracao']) ? (int) $cfg['popup_clientes_sessao_duracao'] : 2;
            $qtd_meses_graficos = $cfg['qtd_meses_graficos'];
            $limite_ticket = $cfg['limite_ticket'];
            $link = $cfg['link'];
            $texto = $cfg['texto'];
        }

        $cfgDefault = function ($value, $default = 's') {
            $value = trim((string) $value);
            return $value === '' ? $default : $value;
        };

        $exb_ticket_medio = $cfgDefault(isset($exb_ticket_medio) ? $exb_ticket_medio : '', 's');
        $exb_saldo_conta = $cfgDefault(isset($exb_saldo_conta) ? $exb_saldo_conta : '', 's');
        $exb_clientes_ramal = $cfgDefault(isset($exb_clientes_ramal) ? $exb_clientes_ramal : '', 'n');
        $exb_balanco_faturamento = $cfgDefault(isset($exb_balanco_faturamento) ? $exb_balanco_faturamento : '', 's');
        $exb_balanco_clientes = $cfgDefault(isset($exb_balanco_clientes) ? $exb_balanco_clientes : '', 's');
        $exb_balanco_chamados = $cfgDefault(isset($exb_balanco_chamados) ? $exb_balanco_chamados : '', 's');
        $exb_busca_inteligente = $cfgDefault(isset($exb_busca_inteligente) ? $exb_busca_inteligente : '', 's');
        $contabilizar_bloq_offline = $cfgDefault(isset($contabilizar_bloq_offline) ? $contabilizar_bloq_offline : '', 's');
        $exb_graficos_em_baixo = $cfgDefault(isset($exb_graficos_em_baixo) ? $exb_graficos_em_baixo : '', 's');
        $tbl_logs_sistema = $cfgDefault(isset($tbl_logs_sistema) ? $tbl_logs_sistema : '', 's');
        $tbl_chamados_abertos = $cfgDefault(isset($tbl_chamados_abertos) ? $tbl_chamados_abertos : '', 's');
        $tbl_contas_pagar = $cfgDefault(isset($tbl_contas_pagar) ? $tbl_contas_pagar : '', 'n');
        $popup_clientes_sessao = $cfgDefault(isset($popup_clientes_sessao) ? $popup_clientes_sessao : '', 'n');
        $popup_clientes_sessao_duracao = isset($popup_clientes_sessao_duracao) ? max(1, min(15, (int) $popup_clientes_sessao_duracao)) : 2;


        // Relação de grupos do usuário logado

        $sql_usuario_grupos = mysqli_query($conn, "SELECT cli_grupos FROM sis_acesso WHERE login LIKE '$usuario_logado' AND cli_grupos NOT LIKE 'full_clientes%'");

        if (!$sql_usuario_grupos) {
            echo mysqli_error($conn);
        }

        while ($row = mysqli_fetch_array($sql_usuario_grupos)) {
            $grupos_permitidos = $row['cli_grupos'];
        }

        if ($grupos_permitidos == "") {
            $grupos = "c.grupo LIKE '%' AND";
        } else {
            $grupos_permitidos = explode(",", $grupos_permitidos);

            $grupos = "(c.grupo = ";
            foreach ($grupos_permitidos as $key => $value) {
                if ($value == "ped_fil") {
                    continue;
                }
                if ($value == "full_clientes") {
                    $grupos = "(c.grupo LIKE '%' OR c.grupo = ";
                    break;
                }
                $grupos .= "'$value' OR c.grupo = ";
            }

            if ($grupos != "(c.grupo = ") {
                $grupos = substr($grupos, 0, -14);
                $grupos .= ") AND ";
            } else {
                $grupos = "";
            }
        }

        ?>


        <?php
        $per_meses = $qtd_meses_graficos;
        $string_per = "$per_meses meses";

        if ($exb_busca_inteligente == 's') {
        ?>
            <script>
                window.dashboardSessionPopupEnabled = <?= ($popup_clientes_sessao === 's') ? 'true' : 'false'; ?>;
                window.dashboardSessionPopupDuration = <?= (int) $popup_clientes_sessao_duracao; ?>;
            </script>

            <datalist id="sugestoes">
                <option value="on">
                <option value="off">
                <option value="adicionais">
                <option value="bloqueado">
                <option value="atrasado">
                <option value="observacao">
                <option value="desativado">
                <option value="sem carne">
                <option value="sem titulo">
                <option value="venc+">
                <option value="conta+">
                <option value="parcelas abertas+">
                    <?php
                    $query_sugestoes = mysqli_query($conn, "SELECT DISTINCT nome FROM sis_cliente ORDER BY nome");
                    while ($s = mysqli_fetch_array($query_sugestoes)) {
                        $s_nome = $s['nome'];
                        echo "<option value='$s_nome'>";
                    }

                    ?>
            </datalist>

            <form action="/admin/addons/busca_inteligente/index.php" method="get" id="" class="form-inline">
                <div class="row g-1">

                    <div class="col form-floating mb-3">
                        <input type="search" name="busca" class="form-control" id="floatingInput" placeholder="Busca Inteligente" value="<?php echo $busca; ?>" list="sugestoes">
                        <label for="floatingInput">(Busca Inteligente) Digite o que procura:</label>
                    </div>
                    <div class="col-auto mb-3">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">Buscar</button>
                        </div>
                    </div>
                </div>
            </form>

            <?php
            $dashboard_session_toasts = array();
            if ($popup_clientes_sessao === 's') {
                $query_dashboard_session_toasts = mysqli_query($conn, "
                    SELECT evento, username, nome, concentrador, data_evento, event_id, bloqueado, cli_ativado
                    FROM (
                        SELECT 
                            'login' AS evento,
                            r.username,
                            COALESCE(NULLIF(c.nome, ''), r.username) AS nome,
                            COALESCE(NULLIF(r.nasipaddress, ''), '-') AS concentrador,
                            r.acctstarttime AS data_evento,
                            CONCAT('login-', r.radacctid) AS event_id,
                            COALESCE(NULLIF(c.bloqueado, ''), 'nao') AS bloqueado,
                            COALESCE(NULLIF(c.cli_ativado, ''), 's') AS cli_ativado
                        FROM radacct r
                        LEFT JOIN sis_cliente c ON c.login = r.username
                        WHERE r.acctstarttime IS NOT NULL
                        AND r.acctstarttime >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)

                        UNION ALL

                        SELECT 
                            'logout' AS evento,
                            r.username,
                            COALESCE(NULLIF(c.nome, ''), r.username) AS nome,
                            COALESCE(NULLIF(r.nasipaddress, ''), '-') AS concentrador,
                            r.acctstoptime AS data_evento,
                            CONCAT('logout-', r.radacctid) AS event_id,
                            COALESCE(NULLIF(c.bloqueado, ''), 'nao') AS bloqueado,
                            COALESCE(NULLIF(c.cli_ativado, ''), 's') AS cli_ativado
                        FROM radacct r
                        LEFT JOIN sis_cliente c ON c.login = r.username
                        WHERE r.acctstoptime IS NOT NULL
                        AND r.acctstoptime >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                    ) AS eventos_recent
                    ORDER BY data_evento DESC
                    LIMIT 4
                ");

                if ($query_dashboard_session_toasts) {
                    while ($row_session_toast = mysqli_fetch_assoc($query_dashboard_session_toasts)) {
                        $dashboard_session_toasts[] = $row_session_toast;
                    }
                }
            }

            if (!empty($dashboard_session_toasts) && false) {
            ?>
                <div class="dashboard-session-toast-stack" id="dashboard-session-toast-stack">
                    <div class="dashboard-session-toast-toolbar" id="dashboard-session-toast-toolbar">
                        <button type="button" class="dashboard-session-toast-link is-secondary" data-clear-session-popups="1">Limpar</button>
                        <button type="button" class="dashboard-session-toast-link is-secondary" data-minimize-session-popups="1">Ocultar</button>
                        <button type="button" class="dashboard-session-toast-link" data-disable-session-popups="1">Desativar notificações</button>
                    </div>
                    <div class="dashboard-session-toast-list" id="dashboard-session-toast-list">
                    <?php foreach ($dashboard_session_toasts as $session_toast) {
                        $is_login = $session_toast['evento'] === 'login';
                        $toast_class = $is_login ? 'is-login' : 'is-logout';
                        $toast_icon = $is_login ? 'bi bi-box-arrow-in-right' : 'bi bi-box-arrow-right';
                        $toast_label = $is_login ? 'Cliente conectou' : 'Cliente desconectou';
                        $toast_title = trim((string) $session_toast['nome']);
                        $toast_login = trim((string) $session_toast['username']);
                        $toast_concentrador = trim((string) $session_toast['concentrador']);
                        $toast_data = !empty($session_toast['data_evento']) ? date('d/m H:i:s', strtotime($session_toast['data_evento'])) : '';
                        $toast_contract_status = 'active';
                        $toast_contract_label = 'Contrato ativo';
                        $toast_contract_icon = 'bi bi-shield-check';

                        if (isset($session_toast['bloqueado']) && $session_toast['bloqueado'] === 'sim') {
                            $toast_contract_status = 'blocked';
                            $toast_contract_label = 'Contrato bloqueado';
                            $toast_contract_icon = 'bi bi-lock-fill';
                        } elseif (isset($session_toast['cli_ativado']) && $session_toast['cli_ativado'] !== 's') {
                            $toast_contract_status = 'inactive';
                            $toast_contract_label = 'Contrato inativo';
                            $toast_contract_icon = 'bi bi-pause-circle';
                        }
                    ?>
                        <div class="dashboard-session-toast <?= $toast_class; ?>" data-event-id="<?= htmlspecialchars((string) $session_toast['event_id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="dashboard-session-toast-icon">
                                <i class="<?= $toast_icon; ?>"></i>
                            </span>
                            <div class="dashboard-session-toast-content">
                                <span class="dashboard-session-toast-label"><?= $toast_label; ?></span>
                                <p class="dashboard-session-toast-title"><?= htmlspecialchars($toast_title, ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="dashboard-session-toast-meta">
                                    <span><i class="bi bi-person-badge"></i><?= htmlspecialchars($toast_login, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($toast_concentrador !== '' && $toast_concentrador !== '-') { ?>
                                        <span><i class="bi bi-hdd-network"></i><?= htmlspecialchars($toast_concentrador, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } ?>
                                    <?php if ($toast_data !== '') { ?>
                                        <span><i class="bi bi-clock"></i><?= $toast_data; ?></span>
                                    <?php } ?>
                                </div>
                                <div class="dashboard-session-toast-status is-<?= $toast_contract_status; ?>">
                                    <i class="<?= $toast_contract_icon; ?>"></i>
                                    <span><?= htmlspecialchars($toast_contract_label, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                </div>
            <?php
            }

            if (permissao('perm_instalacao')) {
                $query_alerta_instalacao = mysqli_query($conn, "
                    SELECT uuid_solic, nome, login, processamento
                    FROM sis_solic
                    WHERE status = 'aberto'
                    AND (datainst <= '$nova_data_1' OR visita <= '$nova_data_1' OR datainst IS NULL)
                    ORDER BY processamento DESC, datainst
                    LIMIT 1
                ");

                $query_total_alerta_instalacao = mysqli_query($conn, "
                    SELECT COUNT(*) AS total
                    FROM sis_solic
                    WHERE status = 'aberto'
                    AND (datainst <= '$nova_data_1' OR visita <= '$nova_data_1' OR datainst IS NULL)
                ");

                $total_alerta_instalacao = 0;
                if ($query_total_alerta_instalacao) {
                    $row_total_alerta_instalacao = mysqli_fetch_assoc($query_total_alerta_instalacao);
                    $total_alerta_instalacao = (int) $row_total_alerta_instalacao['total'];
                }

                if ($total_alerta_instalacao > 0 && $query_alerta_instalacao && ($alerta_instalacao = mysqli_fetch_assoc($query_alerta_instalacao))) {
                    $alerta_nome = trim((string) $alerta_instalacao['nome']);
                    $alerta_login = trim((string) $alerta_instalacao['login']);
                    $alerta_processamento = !empty($alerta_instalacao['processamento']) ? date('d/m/Y', strtotime($alerta_instalacao['processamento'])) : date('d/m/Y');
                    $alerta_titulo = 'Um novo cadastro foi realizado';
                    $alerta_resumo = $total_alerta_instalacao === 1
                        ? "Foi solicitada uma nova instalação pelo site e ela está aguardando atendimento."
                        : "Foram solicitadas $total_alerta_instalacao novas instalações pelo site e elas estão aguardando atendimento.";
            ?>
                    <div class="row mb-2">
                        <div class="col-12">
                            <a href="#solicitacoes-instalacao" class="installation-alert-link" id="installation-alert-link">
                                <div class="installation-alert-main">
                                    <span class="installation-alert-icon">
                                        <i class="bi bi-journal-plus"></i>
                                    </span>
                                    <div>
                                        <p class="installation-alert-title"><?= $alerta_titulo; ?></p>
                                        <p class="installation-alert-copy"><?= $alerta_resumo; ?> Último cadastro: <strong><?= $alerta_nome; ?> [<?= $alerta_login; ?>]</strong> em <?= $alerta_processamento; ?>.</p>
                                    </div>
                                </div>
                                <span class="installation-alert-cta">Ver na dashboard</span>
                            </a>
                        </div>
                    </div>
            <?php
                }
            }
            ?>

            <?php
            $radius_state_file = '/var/lib/mkauth_radius_ppp_reconcile/status.json';
            $radius_alert_items = array();
            $radius_alert_generated_at = '';
            $radius_alert_enabled = mka_suite_get_radius_alert_enabled($conn) === 's';

            if ($radius_alert_enabled && @is_file($radius_state_file) && @is_readable($radius_state_file)) {
                $radius_state_raw = @file_get_contents($radius_state_file);
                $radius_state = json_decode((string) $radius_state_raw, true);

                if (is_array($radius_state)) {
                    if (!empty($radius_state['generated_at'])) {
                        $radius_alert_generated_at = date('d/m/Y H:i:s', strtotime($radius_state['generated_at']));
                    }

                    if (!empty($radius_state['failed_routers']) && is_array($radius_state['failed_routers'])) {
                        foreach ($radius_state['failed_routers'] as $failed_router) {
                            $router_name = trim((string) ($failed_router['name'] ?? ''));
                            $router_ip = trim((string) ($failed_router['router'] ?? ''));
                            if ($router_name === '' && $router_ip === '') {
                                continue;
                            }
                            $router_reason = trim((string) ($failed_router['reason'] ?? ''));
                            $radius_alert_items[] = trim($router_name . ' - ' . $router_ip, ' -') . ($router_reason !== '' ? ': ' . $router_reason : '');
                        }
                    }
                }
            }

            $radius_alert_payload = null;
            if (!empty($radius_alert_items)) {
                $radius_alert_payload = [
                    'id' => 'radius-' . md5(implode('|', $radius_alert_items) . $radius_alert_generated_at),
                    'type' => 'radius',
                    'name' => 'Alerta de integração Radius',
                    'login' => 'Verifique usuário `mkauth`, senha do ramal, porta `8728` ou rota VPN.',
                    'concentrator' => implode(' | ', $radius_alert_items),
                    'formatted_time' => $radius_alert_generated_at !== '' ? $radius_alert_generated_at : date('d/m/Y H:i:s'),
                    'contract_status' => 'inactive',
                    'contract_label' => 'Falha de integração',
                    'contract_icon' => 'fa-solid fa-triangle-exclamation',
                    'show_contract' => true,
                    'radius_alert' => true,
                    'persistent' => true
                ];
            }
            ?>

        <?php
        }

        // Card Clientes
        // echo $grupos;
        $query_clientes_online = mysqli_query($conn, "SELECT r.username FROM radacct r FORCE INDEX (acctstoptime) LEFT JOIN sis_cliente c ON c.login = r.username WHERE $grupos r.acctstoptime IS NULL ");
        while ($row3 = mysqli_fetch_array($query_clientes_online)) {
            $username_on[trim(strtolower($row3['username']))] = trim(strtolower($row3['username']));
        }

        $query_clientes_ativos = mysqli_query($conn, "SELECT c.login, c.bloqueado, c.tit_vencidos, c.observacao FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's'");
        $query_clientes_adicionais = mysqli_query($conn, "SELECT cli_add.login as login_add FROM sis_adicional cli_add LEFT JOIN sis_cliente c ON cli_add.login = c.login WHERE $grupos c.cli_ativado LIKE 's'");

        $cli_ = 0;
        $cli_bloq = 0;
        $cli_on = 0;
        $cli_bloq_online = 0;
        $cli_obs = 0;
        while ($row = mysqli_fetch_array($query_clientes_ativos)) {
            if ($row['bloqueado'] == 'sim') {
                $cli_bloq++;
            }
            if ($row['observacao'] == 'sim') {
                $cli_obs++;
            }
            /*if ($row['tit_vencidos'] > 0) {
                $cli_atraso++;
            }*/
            if ($username_on[trim(strtolower($row['login']))]) {
                $cli_on++;
                if ($row['bloqueado'] == 'sim') {
                    $cli_bloq_online++;
                }
            }
            $cli_++;
        }

        $cli_atraso = 0;

$qTitulos = mysqli_query($conn, "SELECT l.login FROM sis_lanc l LEFT JOIN sis_cliente c ON l.login = c.login WHERE l.status NOT LIKE 'pago' AND l.deltitulo = 0 AND l.datavenc <= '$now' AND c.cli_ativado = 's' GROUP BY l.login");
while ($row = mysqli_fetch_assoc($qTitulos)) {
    $cli_atraso++;
}
// echo $cli_atraso."<br>";
        $c_add = 0;
        while ($row = mysqli_fetch_array($query_clientes_adicionais)) {
            if ($username_on[trim(strtolower($row['login_add']))]) {
                $cli_on++;
            }
            $c_add++;
        }

        $tot_clientes = $cli_ + $c_add;

        $tot_clientes_livres = $cli_ - $cli_bloq;

        if ($contabilizar_bloq_offline == 's') {
            $cli_offline = $tot_clientes - $cli_on;
        } else {
            $cli_offline = $tot_clientes - $cli_on - $cli_bloq + $cli_bloq_online;
            $cli_atraso -= $cli_bloq;
        }

        $cli_sem_carne = mysqli_query($conn, "SELECT login FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's' AND c.parc_abertas LIKE '0' AND c.isento LIKE 'nao' AND c.tipo_cob LIKE 'carne'");
        $tot_sem_carne = mysqli_num_rows($cli_sem_carne);

        $cli_sem_titulo = mysqli_query($conn, "SELECT login FROM sis_cliente c WHERE $grupos c.cli_ativado LIKE 's' AND c.tit_abertos LIKE '0' AND c.isento LIKE 'nao' AND c.tipo_cob LIKE 'titulo'");
        $tot_sem_titulo = mysqli_num_rows($cli_sem_titulo);

        // Porcentagem dos Clientes
        $perc_clientes_livres = number_format($tot_clientes_livres / $cli_ * 100, 2);
        $perc_cliente_sem_adicionais = number_format($cli_ / $tot_clientes * 100, 2);
        $perc_clientes_adicional = number_format($c_add / $tot_clientes * 100, 2);
        $perc_clientes_bloqueado = number_format($cli_bloq / $cli_ * 100, 2);
        $perc_clientes_observacao = number_format($cli_obs / $cli_ * 100, 2);
        $perc_clientes_atrasado = number_format($cli_atraso / $cli_ * 100, 2);
        $perc_clientes_online = number_format($cli_on / $tot_clientes * 100, 2);
        $perc_clientes_offline = number_format($cli_offline / $tot_clientes * 100, 2);
        $perc_clientes_sem_carne = number_format($tot_sem_carne / $tot_clientes * 100, 2);
        $perc_clientes_sem_titulo = number_format($tot_sem_titulo / $tot_clientes * 100, 2);

        $dashboard_stats = array(
            array('label' => 'Total', 'value' => $tot_clientes, 'percent' => '100.00%', 'href' => '/admin/addons/busca_inteligente/index.php', 'theme' => 'is-primary', 'text' => 'text-light'),
            array('label' => 'Adicional', 'value' => $c_add, 'percent' => $perc_clientes_adicional . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=adicionais', 'theme' => 'is-light', 'text' => 'text-dark'),
            array('label' => 'Livres', 'value' => $tot_clientes_livres, 'percent' => $perc_clientes_livres . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=', 'theme' => 'is-info', 'text' => 'text-dark'),
            array('label' => 'Observação', 'value' => $cli_obs, 'percent' => $perc_clientes_observacao . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=obs', 'theme' => 'is-observation', 'text' => 'text-dark'),
            array('label' => 'Bloqueado', 'value' => $cli_bloq, 'percent' => $perc_clientes_bloqueado . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=bloq', 'theme' => 'is-danger', 'text' => 'text-light'),
            array('label' => 'Atraso', 'value' => $cli_atraso, 'percent' => $perc_clientes_atrasado . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=atrasado', 'theme' => 'is-warning', 'text' => 'text-dark'),
            array('label' => 'Online', 'value' => $cli_on, 'percent' => $perc_clientes_online . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=on', 'theme' => 'is-success', 'text' => 'text-light'),
            array('label' => 'Offline', 'value' => $cli_offline, 'percent' => $perc_clientes_offline . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=off', 'theme' => 'is-dark', 'text' => 'text-light'),
            array('label' => 'Sem Carne', 'value' => $tot_sem_carne, 'percent' => $perc_clientes_sem_carne . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=sem carne', 'theme' => 'is-outline-danger', 'text' => 'text-dark'),
            array('label' => 'Sem Títulos', 'value' => $tot_sem_titulo, 'percent' => $perc_clientes_sem_titulo . '%', 'href' => '/admin/addons/busca_inteligente/index.php?busca=sem tit', 'theme' => 'is-outline-danger', 'text' => 'text-dark'),
        );

        ?>

        <?php include('graf_periodo.php'); ?>
        <?php include('cli_periodo.php'); ?>

        <?php include('cli_chamado_per.php'); ?>

        <?php include('cli_planos.php'); ?>

        <div class='row mb-2'>
            <div class='col-12 col-md-12 col-lg-9 mb-2'>
                <div class='dashboard-surface'>
                    <h2 class="dashboard-section-title">Clientes</h2>
                    <div class="dashboard-section-body">
                        <div class='dashboard-stat-grid'>
                            <?php foreach ($dashboard_stats as $stat) { ?>
                                <a href="<?= $stat['href']; ?>" class="dashboard-stat-card <?= $stat['theme']; ?> <?= $stat['text']; ?>">
                                    <div class="dashboard-stat-head"><?= $stat['label']; ?></div>
                                    <?php
                                    $stat_value = permissao('perm_totais') ? (string) $stat['value'] : '';
                                    $stat_digits = strlen(preg_replace('/\D+/', '', $stat_value));
                                    $stat_size_class = $stat_digits >= 6 ? ' dashboard-stat-value--digits-6-plus' : ($stat_digits === 5 ? ' dashboard-stat-value--digits-5' : '');
                                    ?>
                                    <div class="dashboard-stat-value<?= $stat_size_class; ?>"><?= $stat_value; ?></div>
                                    <div class="dashboard-stat-foot"><?= $stat['percent']; ?></div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php

            /*$query_tot_chamados_abertos = mysqli_query($conn, "SELECT s.login FROM sis_suporte s LEFT JOIN sis_cliente c ON s.login = c.login WHERE s.status = 'aberto' AND c.cli_ativado = 's'");
            if (!$query_tot_chamados_abertos) {
                echo mysqli_error($link);
            }

            $tot_chamados = mysqli_num_rows($query_tot_chamados_abertos);*/

            ?>
            <div class='col-12 col-md-12 col-lg-3 mb-2'>
                <div class='dashboard-surface'>
                    <h2 class="dashboard-section-title">Atendimentos</h2>
                    <div class="dashboard-section-body">
                        <div class="dashboard-attendance-grid">
                            <a href="/admin/suporte_aberto.hhvm" class="dashboard-stat-card is-light text-dark text-decoration-none">
                                <div class="dashboard-stat-head">Chamados</div>
                                <div class="dashboard-stat-value" id="tot_chamados"></div>
                                <div class="dashboard-stat-foot" id="perc_chamados">0.00</div>
                            </a>
                            <a href="/admin/instalacoes_abertas.hhvm" class="dashboard-stat-card is-light text-dark text-decoration-none">
                                <div class="dashboard-stat-head">Instalações</div>
                                <div class="dashboard-stat-value" id="tot_instalacoes"></div>
                                <div class="dashboard-stat-foot" id="perc_instalacoes">0.00</div>
                            </a>

                            <?php
                            $dd = date('Y-m');
                            $query = "SELECT ativ.login
                            FROM sis_ativ ativ
                            WHERE 
                            ativ.registro LIKE 'acessou a central do assinante' AND
                            ativ.data LIKE '$dd%' 
                            ";
                            $result = mysqli_query($conn, $query);
                            $totAcessos = mysqli_num_rows($result);
                            ?>
                            <a href="relAcessoCentral.php" class="dashboard-stat-card is-light text-dark text-decoration-none">
                                <div class="dashboard-stat-head">Central</div>
                                <div class="dashboard-stat-value"><?= $totAcessos; ?></div>
                                <div class="dashboard-stat-foot">.</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php

            $query_ramal = mysqli_query($conn, "SELECT nasname, shortname FROM nas");
            //$ramal_cliente["%"] = "TODOS";
            while ($lista_ramal = mysqli_fetch_array($query_ramal)) {
                $ramal_cliente[$lista_ramal['nasname']] = $lista_ramal['shortname'];
            }



            if ((permissao('perm_relFin') || permissao('perm_relFat')) && $exb_clientes_ramal == 's') {

            ?>



                <!-- Ramais -->
                <div class='col-12 col-md-12 col-lg-12'>
                    <div class='dashboard-surface mb-2'>
                        <h2 class="dashboard-section-title">Clientes Ramais</h2>
                        <div class="dashboard-section-body">
                            <div class="dashboard-ramal-grid">

                                <?php
                                $dashboardRamalStats = array();
                                $queryRamalStats = mysqli_query($conn, "
                                    SELECT c.ramal,
                                           COUNT(*) AS total_clientes,
                                           COUNT(active.username) AS total_online
                                    FROM sis_cliente c
                                    LEFT JOIN (
                                        SELECT DISTINCT username
                                        FROM radacct FORCE INDEX (acctstoptime)
                                        WHERE acctstoptime IS NULL
                                    ) active ON active.username = c.login
                                    WHERE c.cli_ativado LIKE 's'
                                    GROUP BY c.ramal
                                ");
                                if ($queryRamalStats) {
                                    while ($ramalStat = mysqli_fetch_assoc($queryRamalStats)) {
                                        $dashboardRamalStats[(string) $ramalStat['ramal']] = array(
                                            'total' => (int) $ramalStat['total_clientes'],
                                            'online' => (int) $ramalStat['total_online'],
                                        );
                                    }
                                }

                                foreach ($ramal_cliente as $key => $value) {
                                    $nomeRamal = $value;
                                    $ramalStat = isset($dashboardRamalStats[(string) $key]) ? $dashboardRamalStats[(string) $key] : array('total' => 0, 'online' => 0);
                                    $totGeral = $ramalStat['total'];
                                    $totOnline = $ramalStat['online'];

                                    $totOffline = $totGeral - $totOnline;

                                    $percTotalRamal = $totGeral > 0 ? '100.00%' : '0.00%';
                                    $percOnlineRamal = $totGeral > 0 ? number_format(($totOnline / $totGeral) * 100, 2, '.', '') . '%' : '0.00%';
                                    $percOfflineRamal = $totGeral > 0 ? number_format(($totOffline / $totGeral) * 100, 2, '.', '') . '%' : '0.00%';

                                ?>
                                    <div class="dashboard-ramal-panel">
                                        <div class="dashboard-ramal-title"><?= $nomeRamal; ?></div>
                                        <div class="dashboard-ramal-stats">
                                            <a href="<?= '/admin/addons/busca_inteligente/index.php?busca=ramal%2B' . $key; ?>" class="dashboard-stat-card is-primary text-light">
                                                <div class="dashboard-stat-head">Total</div>
                                                <div class="dashboard-stat-value"><?= $totGeral; ?></div>
                                                <div class="dashboard-stat-foot"><?= $percTotalRamal; ?></div>
                                            </a>
                                            <a href="<?= '/admin/addons/busca_inteligente/index.php?busca=on%2B' . $key; ?>" class="dashboard-stat-card is-success text-light">
                                                <div class="dashboard-stat-head">Online</div>
                                                <div class="dashboard-stat-value"><?= $totOnline; ?></div>
                                                <div class="dashboard-stat-foot"><?= $percOnlineRamal; ?></div>
                                            </a>
                                            <a href="<?= '/admin/addons/busca_inteligente/index.php?busca=off%2B' . $key; ?>" class="dashboard-stat-card is-dark text-light">
                                                <div class="dashboard-stat-head">Offline</div>
                                                <div class="dashboard-stat-value"><?= $totOffline; ?></div>
                                                <div class="dashboard-stat-foot"><?= $percOfflineRamal; ?></div>
                                            </a>
                                        </div>
                                    </div>

                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            }
            ?>

            <!-- Acesso Rápido -->
            <div class='col-12 col-md-12 col-lg-12'>
                <div class='dashboard-surface mb-2'>
                    <h2 class="dashboard-section-title">Acesso Rápido</h2>
                    <div class="dashboard-section-body">
                        <div class="dashboard-quick-links">

                            <?php if (permissao('perm_config')) {
                            ?>
                                <a href='cfg.php' class='dashboard-quick-link is-primary'><img src='img/icon_config.png' class='align-middle icon_sm_2 me-1' title='Configurações Dash Board' /> Configurações</a>
                            <?php } ?>

                            <?php

                            $links = explode(',', $link);
                            $textos = explode(',', $texto);

                            $indice = 0;
                            foreach ($links as $k) {
                                if ($k != "") {
                                    if (startsWith($k, strtolower("http"))) {
                                        echo "<a href='$k' target='_blank' class='dashboard-quick-link is-success'>$textos[$indice]</a>";
                                    } else {
                                        echo "<a href='/admin/$k' class='dashboard-quick-link is-secondary'>$textos[$indice]</a>";
                                    }
                                }
                                $indice++;
                            }

                            ?>
                        </div>

                    </div>
                </div>
            </div>




        </div>

        <?php
        if ($exb_graficos_em_baixo == 'n') {
            include_once('graficos.php');
        }
        ?>

        <div class='row'>
            <!-- Atividades - Logs do Sistema -->
            <?php if ((permissao('perm_logs')) && $tbl_logs_sistema == 's') { ?>

                <div class='col-12 mb-3'>

                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'>Atividades</th>
                                <th class='fw-bold text-light'>Data</th>
                                <th class='fw-bold text-light'>Usuário</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php

                            $query_sis_logs = mysqli_query($conn, "SELECT registro, data, login FROM sis_logs WHERE tipo NOT LIKE 'central' ORDER BY id DESC LIMIT 15");


                            while ($l = mysqli_fetch_array($query_sis_logs)) {
                                $log_registro = $l['registro'];
                                $log_data = $l['data'];
                                $log_login = $l['login'];
                            ?>


                                <tr class='font-field'>
                                    <td class=''><?php echo $log_registro; ?></td>
                                    <td class=''><?php echo $log_data; ?></td>
                                    <td class=''><?php echo $log_login; ?></td>
                                </tr>

                            <?php
                            }
                            ?>

                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='100%'><a href='<?php echo "/admin/logs_sistema$ext_mk"; ?>' target='' class='fw-bold'>VER MAIS...</a></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <?php } ?>
            <!-- Chamados -->

            <?php if ((permissao('perm_chamados')) && $tbl_chamados_abertos == 's') { ?>

                <div class='col-12 mb-3'>

                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class="fw-bold text-light"></th>
                                <th class='fw-bold text-light'>Chamado</th>
                                <th class='fw-bold text-light'>Abertura</th>
                                <th class='fw-bold text-light'>Nome [Login]</th>
                                <th class='fw-bold text-light'>Assunto</th>
                            </tr>
                        </thead>

                        <tbody>

                            <div class='row text-uppercase font-title'>

                            </div>
                            <?php

                            $query_sis_suporte = mysqli_query($conn, "SELECT s.chamado, s.abertura, s.login, s.assunto, c.nome FROM sis_suporte s LEFT JOIN sis_cliente c ON s.login = c.login WHERE $grupos s.visita <= '$nova_data_1' AND s.status LIKE 'aberto' AND c.cli_ativado LIKE 's' ORDER BY s.abertura");

                            $tot_chamados = mysqli_num_rows($query_sis_suporte);

                            if (permissao('perm_totais')) {
                                echo "<script>$('#tot_chamados').html(\"$tot_chamados\")</script>";
                            }

                            $ccSup = 0;
                            while ($sup = mysqli_fetch_array($query_sis_suporte)) {

                                $sup_chamado = $sup['chamado'];
                                $sup_abertura = date('d/m/Y H:i:s', strtotime($sup['abertura']));
                                $sup_nome = $sup['nome'];

                                $sup_login = $sup['login'];
                                $sup_assunto = $sup['assunto'];
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/chamado<?php echo $ext_mk; ?>?chamado=<?php echo $sup_chamado; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Abrir </a>

                                            <a href="/admin/suporte_info<?php echo $ext_mk; ?>?login=<?php echo $sup_login; ?>&chamado=<?php echo $sup_chamado; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Info</a>
                                        </strong>
                                    </td>

                                    <td class=''><?php echo $sup_chamado; ?></td>
                                    <td class=''><?php echo $sup_abertura; ?></td>
                                    <td class=''><?php echo "$sup_nome <strong>[$sup_login]</strong>"; ?></td>
                                    <td class=''><?php echo $sup_assunto; ?></td>
                                </tr>

                            <?php
                                $ccSup++;
                                if ($ccSup == 15) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table>
                </div>

            <?php } ?>

            <!-- Instalacoes -->

            <?php if ((permissao('perm_instalacao'))) { ?>

                <div class='col-12 mb-3 installation-highlight-target' id='solicitacoes-instalacao'>
                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'></th>
                                <th class='fw-bold text-light'>Instalação</th>
                                <th class='fw-bold text-light'>Nome</th>
                                <th class='fw-bold text-light'>Técnico</th>
								<th class='fw-bold text-light'>Data de Cadastro</th> <!-- Nova coluna -->
                            </tr>
                        </thead>

                        <tbody>

                            <?php

                            $query_sis_instalacao = mysqli_query($conn, "
							SELECT uuid_solic, disp, nome, login, tecnico, processamento AS data_cadastro 
							FROM sis_solic 
							WHERE status = 'aberto' 
							AND (datainst <= '$nova_data_1' OR visita <= '$nova_data_1' OR datainst IS NULL) 
							ORDER BY datainst
								");
								
                            $tot_instalacoes = mysqli_num_rows($query_sis_instalacao);

                            $tot_atendimento = $tot_chamados + $tot_instalacoes;
                            $perc_chamados = number_format($tot_chamados / $tot_atendimento * 100, 2);
                            $perc_instalacoes = number_format($tot_instalacoes / $tot_atendimento * 100, 2);

                            if (permissao('perm_totais')) {
                                echo "<script>$('#tot_instalacoes').html(\"$tot_instalacoes\")</script>";
                            }
                            echo "<script>$('#perc_instalacoes').html(\"$perc_instalacoes%\")</script>";
                            echo "<script>$('#perc_chamados').html(\"$perc_chamados%\")</script>";

                            $ccInst = 0;

                            while ($inst = mysqli_fetch_array($query_sis_instalacao)) {
                                $inst_uuid = $inst['uuid_solic'];
                                $inst_disponivel = $inst['disp'] == "sim" ? "Disponível" : "Indisponível";
                                $inst_nome = $inst['nome'];
                                $inst_login = $inst['login'];
                                $inst_tecnico = $inst['tecnico'];
								$data_cadastro = !empty($inst['data_cadastro']) ? date("d/m/Y", strtotime($inst['data_cadastro'])) : "N/A"; // Formata a data
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/instalar_alt<?php echo $ext_mk; ?>?uuid=<?php echo $inst_uuid; ?>" class="btn-link text-decoration-none">Alterar </a>

                                            <a href="/admin/instalacao_info<?php echo $ext_mk; ?>?uuid=<?php echo $inst_uuid; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Info</a>

                                            <a href="/admin/cliente_ins<?php echo $ext_mk; ?>?new_install=<?php echo $inst_uuid; ?>" class="btn-link text-decoration-none" target="_blank">Incluir Cliente</a>

                                            <a href="#" class="btn-link text-danger text-decoration-none delete-installation-request" data-solic="<?php echo htmlspecialchars($inst_uuid, ENT_QUOTES, 'UTF-8'); ?>" title="Excluir esta solicitação"><i class="bi bi-trash3"></i> Excluir</a>

                                        </strong>
                                    </td>
                                    <td class=''><?php echo $inst_disponivel; ?></td>
                                    <td class=''><?php echo "$inst_nome <strong>[$inst_login]</strong>"; ?></td>
                                    <td class=''><?php echo $inst_tecnico; ?></td>
									<td class=''><?php echo $data_cadastro; ?></td> <!-- Exibe a data de cadastro -->
                                </tr>

                            <?php
                                $ccInst++;
                                if ($ccInst == 500) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table>
                </div>
            <?php } ?>

            <!-- Contas a pagar -->
            <?php if ((permissao('perm_contaspagar')) && $tbl_contas_pagar == 's') { ?>

                <div class='col-12 col'>

                    <table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'></th>
                                <th class='fw-bold text-light'>Título</th>
                                <th class='fw-bold text-light'>Parcela</th>
                                <th class='fw-bold text-light'>Fornecedor / Funcionário</th>
                                <th class='fw-bold text-light'>Vencimento</th>
                                <th class='fw-bold text-light'>Valor</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php

                            $query_sis_contaspagar = mysqli_query($conn, "SELECT c.id AS id_conta, c.nrdocumento, c.parcatual, c.numparcelas, c.historico, c.vencimento, c.valor, f.razaosoc, c.tipodiv, func.nome , c.uuid_contaspagar
                            FROM sis_contaspagar c 
                            LEFT JOIN sis_fornecedor f ON c.fornecedor = f.id 
                            LEFT JOIN sis_func func ON c.fornecedor = func.id
                            WHERE c.status NOT LIKE 'liquidado' AND c.vencimento <= '$nova_data_5'  ORDER BY c.vencimento");


                            $ccCP = 0;
                            while ($cp = mysqli_fetch_array($query_sis_contaspagar)) {
                                $cp_id = $cp['id_conta'];
                                $cp_uuid_contaspagar = $cp['uuid_contaspagar'];
                                $cp_nrdocumento = $cp['nrdocumento'];
                                $cp_parcatual = $cp['parcatual'];
                                $cp_numparcelas = $cp['numparcelas'];

                                if ($cp['tipodiv'] == "for") {
                                    $cp_fornecedor = $cp['razaosoc'] . " - " . $cp['historico'];
                                } else {
                                    $cp_fornecedor = $cp['nome'] . " - " . $cp['historico'];
                                }

                                $cp_vencimento = date('d/m/Y', strtotime($cp['vencimento']));

                                $cp_valor = number_format($cp['valor'], 2, ',', '.');
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/contaspagar_liquidar<?php echo $ext_mk; ?>?uuid=<?php echo $cp_uuid_contaspagar; ?>" class="btn-link text-decoration-none">Liquidar</a>
                                        </strong>
                                    </td>
                                    <td class=''><?php echo $cp_nrdocumento; ?></td>
                                    <td class=''><?php echo "$cp_parcatual / $cp_numparcelas"; ?></td>
                                    <td class=''><?php echo $cp_fornecedor; ?></td>
                                    <td class=''><?php echo $cp_vencimento; ?></td>
                                    <td class=''>R$ <?php echo $cp_valor; ?></td>
                                </tr>

                            <?php
                                $ccCP++;
                                if ($ccCP == 15) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table>
                </div>

            <?php } ?>
        </div>

        <?php

        if ($exb_graficos_em_baixo == 's') {
            include_once('graficos.php');
        }

        // Close conn in MariaDB
        mysqli_close($conn);

        ?>
        <?php include('../../baixo.php'); ?>

        <!-- menu carregado no topo da dashboard -->

        <script>
            // deleta comprovante
            jQuery(document).on('click', '#link_delcompv', function() {
                var id_compv = jQuery(this).attr("data-compv");
                if (confirm('Realmente deseja excluir este comprovante?')) {
                    mka_link('../../executar_comprovante.hhvm?acao=delcomprovante&uuid=' + id_compv);
                    return false;
                }
            });
            // deleta mensagem
            jQuery(document).on('click', '#link_delmsg', function() {
                var uuid_msg = jQuery(this).attr("data-contato");
                if (confirm('Realmente deseja excluir este contato?')) {
                    mka_link('../../executar_mka.hhvm?acao=del.contato&uuid=' + uuid_msg);
                    return false;
                }
            });
            // deleta chamado
            jQuery(document).on('click', '#link_delchamado', function() {
                var chamado = jQuery(this).attr("data-chamado");
                if (confirm('Realmente deseja excluir este chamado?')) {
                    mka_link('../../executar_suporte.hhvm?acao=delhelp&chamado=' + chamado);
                    return false;
                }
            });
            // abre incluir cliente
            jQuery(document).on('click', '#link_insc', function() {
                var uuid_solic = jQuery(this).attr("data-solic");
                if (confirm('Realmente deseja incluir esse cliente?')) {
                    mka_link('../../cliente_ins.hhvm?new_install=' + uuid_solic);
                    return false;
                }
            });
            // abre incluir instalacao
            jQuery(document).on('click', '#link_insi', function() {
                var uuid_solic = jQuery(this).attr("data-solic");
                if (confirm('Realmente deseja transformar em uma nova instalacao?')) {
                    mka_link('../../executar_instalacao.hhvm?acao=install&uuid=' + uuid_solic);
                    return false;
                }
            });
            // abre link excluir
            jQuery(document).on('click', '#link_excluir, .delete-installation-request', function() {
                var uuid_solic = jQuery(this).attr("data-solic");
                if (confirm('Realmente deseja excluir esta solicitacao?')) {
                    mka_link('../../executar_mka.hhvm?acao=delsolic&uuid=' + encodeURIComponent(uuid_solic));
                    return false;
                }
            });

            function ver_eventos(vregistro) {
                // busca sis_logs
                jQuery.ajax({
                    type: "GET",
                    url: "../../logs_ajax.hhvm",
                    data: "registro=" + vregistro + "&tipo=todos",
                    beforeSend: function() {
                        jQuery('#mostralogs').html('<img src="../../img/mkload.gif" hspace="2" vspace="2">');
                    },
                    success: function(txt) {
                        if (txt != 'ERRO') {
                            jQuery('#mostralogs').html(txt);
                        }
                    },
                    error: function(txt) {
                        alerta_baixo1("Desculpe, houve um problema interno");
                    }
                });
            }
            jQuery(document).ready(function() {
                run_shuffle();
                ver_eventos(0);

                function destacarSolicitacoesInstalacao() {
                    var alvo = document.getElementById('solicitacoes-instalacao');
                    if (!alvo) {
                        return;
                    }

                    alvo.classList.remove('is-flashing');
                    void alvo.offsetWidth;
                    alvo.classList.add('is-flashing');

                    window.setTimeout(function() {
                        alvo.classList.remove('is-flashing');
                    }, 2300);
                }

                jQuery(document).on('click', '#installation-alert-link', function(event) {
                    var alvo = document.getElementById('solicitacoes-instalacao');
                    if (!alvo) {
                        return;
                    }

                    event.preventDefault();
                    history.replaceState(null, '', '#solicitacoes-instalacao');
                    alvo.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    window.setTimeout(destacarSolicitacoesInstalacao, 500);
                });

                if (window.location.hash === '#solicitacoes-instalacao') {
                    window.setTimeout(destacarSolicitacoesInstalacao, 250);
                }

                function ensureToastStack() {
                    var stack = document.getElementById('dashboard-session-toast-stack');
                    if (!stack) {
                        stack = document.createElement('div');
                        stack.id = 'dashboard-session-toast-stack';
                        stack.className = 'dashboard-session-toast-stack';
                        document.body.appendChild(stack);
                    }
                    return stack;
                }

                function ensureClientStateToastStack() {
                    var stack = document.getElementById('dashboard-client-state-toast-stack');
                    if (!stack) {
                        stack = document.createElement('div');
                        stack.id = 'dashboard-client-state-toast-stack';
                        stack.className = 'dashboard-client-state-toast-stack';
                        document.body.appendChild(stack);
                    }
                    return stack;
                }

                function ensureToastToolbar(stack) {
                    var toolbar = document.getElementById('dashboard-session-toast-toolbar');
                    if (!toolbar) {
                        toolbar = document.createElement('div');
                        toolbar.id = 'dashboard-session-toast-toolbar';
                        toolbar.className = 'dashboard-session-toast-toolbar';
                        toolbar.innerHTML =
                            '<button type="button" class="dashboard-session-toast-link is-secondary" data-clear-session-popups="1">Limpar</button>' +
                            '<button type="button" class="dashboard-session-toast-link is-secondary" data-minimize-session-popups="1">Minimizar</button>' +
                            '<button type="button" class="dashboard-session-toast-link" data-disable-session-popups="1">Desativar notificações</button>';
                        stack.appendChild(toolbar);
                    }
                    return toolbar;
                }

                function ensureToastList(stack) {
                    var list = document.getElementById('dashboard-session-toast-list');
                    if (!list) {
                        list = document.createElement('div');
                        list.id = 'dashboard-session-toast-list';
                        list.className = 'dashboard-session-toast-list';
                        stack.appendChild(list);
                    }
                    return list;
                }

                function toggleToastToolbar() {
                    var stack = document.getElementById('dashboard-session-toast-stack');
                    var toolbar = document.getElementById('dashboard-session-toast-toolbar');
                    var list = document.getElementById('dashboard-session-toast-list');
                    if (!toolbar || !list) {
                        return;
                    }

                    if ((list.children.length > 0 || (stack && stack.classList.contains('is-minimized'))) && window.dashboardSessionPopupEnabled) {
                        toolbar.classList.remove('is-hidden');
                    } else {
                        toolbar.classList.add('is-hidden');
                    }
                }

                function setToastStackMinimized(minimized) {
                    var stack = ensureToastStack();
                    ensureToastToolbar(stack);

                    if (minimized) {
                        stack.classList.add('is-minimized');
                        sessionStorage.setItem('dashboard-session-popups-minimized', '1');
                    } else {
                        stack.classList.remove('is-minimized');
                        sessionStorage.removeItem('dashboard-session-popups-minimized');
                    }

                    var minimizeButton = stack.querySelector('[data-minimize-session-popups="1"]');
                    if (minimizeButton) {
                        minimizeButton.textContent = minimized ? 'Mostrar' : 'Minimizar';
                    }

                    toggleToastToolbar();
                }

                function scheduleToastRemoval(item) {
                    if (item.getAttribute('data-persistent') === '1' || item.getAttribute('data-removal-scheduled') === '1') {
                        return;
                    }

                    var configuredSeconds = Number.parseInt(window.dashboardSessionPopupDuration, 10);
                    if (!Number.isFinite(configuredSeconds)) {
                        configuredSeconds = 2;
                    }
                    configuredSeconds = Math.max(1, Math.min(15, configuredSeconds));

                    var visibleDuration = configuredSeconds * 1000;
                    var removalDeadline = Date.now() + visibleDuration;
                    item.setAttribute('data-removal-scheduled', '1');
                    item.setAttribute('data-popup-duration', String(configuredSeconds));

                    function beginToastRemoval() {
                        var remaining = removalDeadline - Date.now();
                        if (remaining > 25) {
                            window.setTimeout(beginToastRemoval, remaining);
                            return;
                        }

                        item.classList.add('is-fading');
                        window.setTimeout(function() {
                            if (item && item.parentNode) {
                                item.parentNode.removeChild(item);
                            }
                            toggleToastToolbar();
                            var stack = document.getElementById('dashboard-session-toast-stack');
                            var list = document.getElementById('dashboard-session-toast-list');
                            if (stack && list && list.children.length === 0) {
                                stack.remove();
                            }
                        }, 560);
                    }

                    window.setTimeout(beginToastRemoval, visibleDuration);
                }

                function createSessionToast(eventData) {
                    var isLogin = eventData.type === 'login';
                    var isRadiusAlert = eventData.type === 'radius';
                    var isClientState = eventData.type === 'client-state';
                    var stack = isClientState ? ensureClientStateToastStack() : ensureToastStack();
                    var list = isClientState ? stack : ensureToastList(stack);
                    if (!isClientState) ensureToastToolbar(stack);
                    var item = document.createElement('div');

                    item.className = 'dashboard-session-toast ' + (eventData.guide ? 'is-guide' : (isRadiusAlert ? 'is-guide' : (isClientState ? 'is-client-state is-client-state-' + (eventData.action_style || 'unlocked') : (isLogin ? 'is-login' : 'is-logout'))));
                    item.setAttribute('data-event-id', eventData.id);
                    if (eventData.persistent) {
                        item.setAttribute('data-persistent', '1');
                    }

                    var iconClass = eventData.icon ? eventData.icon : (isRadiusAlert ? 'bi bi-exclamation-triangle-fill' : (isLogin ? 'bi bi-box-arrow-in-right' : 'bi bi-box-arrow-right'));
                    var label = eventData.label ? eventData.label : (isRadiusAlert ? 'Alerta Radius' : (isLogin ? 'Cliente conectou' : 'Cliente desconectou'));
                    var concentratorHtml = '';
                    if (eventData.concentrator && eventData.concentrator !== '-') {
                        concentratorHtml = '<span><i class="bi bi-hdd-network"></i>' + eventData.concentrator + '</span>';
                    }
                    var contractStatus = eventData.contract_status ? eventData.contract_status : (isRadiusAlert ? 'inactive' : 'active');
                    var contractIcon = eventData.contract_icon ? eventData.contract_icon : (isRadiusAlert ? 'bi bi-exclamation-triangle-fill' : 'bi bi-shield-check');
                    var contractLabel = eventData.contract_label ? eventData.contract_label : (isRadiusAlert ? 'Falha de integração' : 'Contrato ativo');
                    var radiusAction = isRadiusAlert ? '<div class="dashboard-session-toast-actions"><button type="button" class="dashboard-radius-retest" data-radius-retest="1">Executar novamente</button><span class="dashboard-radius-result"></span></div>' : '';
                    var descriptionHtml = eventData.description ? '<p class="dashboard-session-toast-description">' + eventData.description + '</p>' : '';
                    var connectionIcon = eventData.connection_state === 'online' ? 'bi-wifi' : (eventData.connection_state === 'disconnected' ? 'bi-wifi-off' : 'bi-circle-fill');
                    var connectionHtml = eventData.connection_label ? '<div class="dashboard-session-toast-status is-' + (eventData.connection_state || 'offline') + '"><i class="bi ' + connectionIcon + '"></i><span>' + eventData.connection_label + '</span></div>' : '';

                    if (isClientState) {
                        item.innerHTML =
                            '<button type="button" class="dashboard-session-toast-close" data-close-session-toast="1" aria-label="Fechar">&times;</button>' +
                            '<span class="dashboard-session-toast-icon"><i class="' + iconClass + '"></i></span>' +
                            '<div class="dashboard-session-toast-content">' +
                            '<div class="dashboard-client-state-identity"><span class="dashboard-session-toast-label">' + label + '</span><p class="dashboard-session-toast-title">' + eventData.name + '</p></div>' +
                            '<div class="dashboard-client-state-context">' +
                            '<div class="dashboard-session-toast-meta"><span><i class="bi bi-person-badge"></i>' + eventData.login + '</span><span><i class="bi bi-clock"></i>' + eventData.formatted_time + '</span></div>' +
                            '</div>' + connectionHtml + '</div>';
                    } else item.innerHTML =
                        '<button type="button" class="dashboard-session-toast-close" data-close-session-toast="1" aria-label="Fechar">&times;</button>' +
                        '<span class="dashboard-session-toast-icon"><i class="' + iconClass + '"></i></span>' +
                        '<div class="dashboard-session-toast-content">' +
                        '<span class="dashboard-session-toast-label">' + label + '</span>' +
                        '<p class="dashboard-session-toast-title">' + eventData.name + '</p>' +
                        descriptionHtml +
                        '<div class="dashboard-session-toast-meta">' +
                        '<span><i class="bi bi-person-badge"></i>' + eventData.login + '</span>' +
                        concentratorHtml +
                        '<span><i class="bi bi-clock"></i>' + eventData.formatted_time + '</span>' +
                        '</div>' +
                        connectionHtml + (eventData.show_contract === false ? '' : '<div class="dashboard-session-toast-status is-' + contractStatus + '"><i class="' + contractIcon + '"></i><span>' + contractLabel + '</span></div>') + radiusAction +
                        '</div>';

                    list.prepend(item);
                    if (sessionStorage.getItem('dashboard-session-popups-minimized') === '1') {
                        setToastStackMinimized(true);
                    }
                    toggleToastToolbar();
                    scheduleToastRemoval(item);
                }

                function clearVisibleSessionToasts() {
                    jQuery('#dashboard-session-toast-list .dashboard-session-toast').remove();
                    toggleToastToolbar();
                    jQuery('#dashboard-session-toast-stack').remove();
                    sessionStorage.removeItem('dashboard-session-popups-minimized');
                }

                function showSessionGuideToast() {
                    createSessionToast({
                        id: 'guide-' + Date.now(),
                        type: 'guide',
                        label: 'Notificações pausadas',
                        name: 'Você pode ativar novamente em Configurações > Popup de Clientes ao Logar/Deslogar.',
                        login: 'dashboard',
                        concentrator: '',
                        formatted_time: '',
                        contract_status: 'inactive',
                        contract_label: 'Reativação manual',
                        contract_icon: 'bi bi-gear-fill',
                        icon: 'bi bi-info-circle-fill',
                        show_contract: false,
                        guide: true
                    });
                }

                function disableSessionPopups() {
                    return jQuery.ajax({
                        url: 'session_events.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'disable_notifications'
                        }
                    }).done(function() {
                        window.dashboardSessionPopupEnabled = false;
                        sessionStorage.setItem('dashboard-session-popups-disabled', '1');
                        jQuery('#dashboard-session-toast-stack').remove();
                        showSessionGuideToast();
                    });
                }

                function hydrateExistingSessionToasts() {
                    jQuery('.dashboard-session-toast').each(function(index, item) {
                        var eventId = item.getAttribute('data-event-id');
                        if (eventId) {
                            sessionStorage.setItem('dashboard-session-toast-' + eventId, '1');
                        }
                        window.setTimeout(function() {
                            scheduleToastRemoval(item);
                        }, index * 240);
                    });
                    toggleToastToolbar();
                }

                function fetchSessionToasts() {
                    if (!window.dashboardSessionPopupEnabled) {
                        return;
                    }

                    jQuery.getJSON('session_events.php')
                        .done(function(response) {
                            if (!response || response.enabled !== true || !Array.isArray(response.events)) {
                                return;
                            }

                            response.events.slice().reverse().forEach(function(eventData) {
                                if (!eventData.id) {
                                    return;
                                }
                                var storageKey = 'dashboard-session-toast-' + eventData.id;
                                if (sessionStorage.getItem(storageKey)) {
                                    return;
                                }
                                sessionStorage.setItem(storageKey, '1');
                                createSessionToast(eventData);
                            });
                        });
                }

                <?php if ($radius_alert_payload !== null) { ?>
                (function() {
                    var radiusBootstrapToast = <?= json_encode($radius_alert_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                    var radiusBootstrapKey = 'dashboard-session-toast-' + radiusBootstrapToast.id;
                    if (!sessionStorage.getItem(radiusBootstrapKey)) {
                        sessionStorage.setItem(radiusBootstrapKey, '1');
                        createSessionToast(radiusBootstrapToast);
                    }
                })();
                <?php } ?>

                jQuery(document).on('click', '[data-disable-session-popups="1"]', function(event) {
                    event.preventDefault();
                    disableSessionPopups();
                });

                jQuery(document).on('click', '[data-clear-session-popups="1"]', function(event) {
                    event.preventDefault();
                    clearVisibleSessionToasts();
                });

                jQuery(document).on('click', '[data-close-session-toast="1"]', function(event) {
                    event.preventDefault();
                    var item = jQuery(this).closest('.dashboard-session-toast');
                    item.remove();
                    toggleToastToolbar();
                    var list = document.getElementById('dashboard-session-toast-list');
                    if (list && list.children.length === 0) {
                        jQuery('#dashboard-session-toast-stack').remove();
                    }
                });

                jQuery(document).on('click', '[data-radius-retest="1"]', function(event) {
                    event.preventDefault();
                    var button = jQuery(this);
                    var toast = button.closest('.dashboard-session-toast');
                    var result = toast.find('.dashboard-radius-result');
                    button.prop('disabled', true).text('Testando...');
                    result.text('Verificando todos os ramais');
                    jQuery.ajax({url: 'radius_test.php', method: 'POST', dataType: 'json'})
                        .done(function(response) {
                            result.text(response.message || 'Teste concluído.');
                            if (response.ok) {
                                toast.find('.dashboard-session-toast-status').removeClass('is-inactive').addClass('is-active').html('<i class="bi bi-check-circle-fill"></i><span>Integração normalizada</span>');
                                button.remove();
                                window.setTimeout(function () { toast.remove(); }, 3500);
                            } else {
                                button.prop('disabled', false).text('Executar novamente');
                            }
                        })
                        .fail(function () {
                            result.text('Não foi possível executar o teste.');
                            button.prop('disabled', false).text('Executar novamente');
                        });
                });

                jQuery(document).on('click', '[data-minimize-session-popups="1"]', function(event) {
                    event.preventDefault();
                    var stack = ensureToastStack();
                    setToastStackMinimized(!stack.classList.contains('is-minimized'));
                });

                if (sessionStorage.getItem('dashboard-session-popups-disabled') === '1') {
                    window.dashboardSessionPopupEnabled = false;
                    jQuery('#dashboard-session-toast-stack').remove();
                }

                hydrateExistingSessionToasts();
                // Remove apenas a faixa azul legada deste mesmo evento para não duplicar o aviso.
                function hideLegacyClientStateNotice(root) {
                    if (!root || root.nodeType !== 1) return;
                    var selector = '.notification, .toast, .toastify, .snackbar, .alert-info, [id*="snackbar"]';
                    var candidates = [];
                    if (root.matches && root.matches(selector)) candidates.push(root);
                    if (root.querySelectorAll) candidates = candidates.concat(Array.prototype.slice.call(root.querySelectorAll(selector)));
                    candidates.forEach(function(candidate) {
                        if (candidate.closest && candidate.closest('#dashboard-session-toast-stack')) return;
                        var message = (candidate.textContent || '').replace(/\s+/g, ' ').trim();
                        if (/login\s+\S+\s+(?:des)?bloqueado\s+por/i.test(message)) candidate.style.display = 'none';
                    });
                }
                hideLegacyClientStateNotice(document.body);
                new MutationObserver(function(records) {
                    records.forEach(function(record) {
                        Array.prototype.forEach.call(record.addedNodes || [], hideLegacyClientStateNotice);
                    });
                }).observe(document.body, {childList: true, subtree: true});
                if (sessionStorage.getItem('dashboard-session-popups-minimized') === '1') {
                    setToastStackMinimized(true);
                }
                window.setInterval(fetchSessionToasts, 10000);
            });
        </script>
</body>

</html>
