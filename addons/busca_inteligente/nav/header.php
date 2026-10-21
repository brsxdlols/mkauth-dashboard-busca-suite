<?php require_once('config.php'); ?>

<!DOCTYPE html>
<?php
if (isset($_SESSION['MM_Usuario'])) {
    echo '<html lang="pt-BR">'; // Fix versço antiga MK-AUTH
} else {
    echo '<html lang="pt-BR" class="has-navbar-fixed-top">';
}
?>

<head>
    <script src="/admin/addons/shared/admin_asset_paths.js?v=20261014-1"></script>
    <link rel="icon" type="image/jpeg" href="/mkfiles/logo.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>MK - AUTH :: <?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'};  ?></title>

    <link href="css/bootstrap.min.5.3.2A.css" rel="stylesheet" type="text/css" />

    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css">
    <!--<script src="../../scripts/jquery.js"></script>-->

    <script src="js/jquery-3.6.0.js"></script>

    <script src="../../scripts/mk-auth.js"></script>
<script src="/admin/addons/shared/visit_reminders.js?v=20261019-1" defer></script>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <!-- <link href="css/fontawesome.min.css" rel="stylesheet" type="text/css" /> -->
    <link href="css/css.css" rel="stylesheet" type="text/css" />
    <script src="js/am_scripts.js"></script>
    <script src="js/all.min.js"></script>

<script src="/admin/addons/shared/priority_colors.js?v=20261015-13" defer></script>
<script src="/admin/addons/shared/visit_agenda.js?v=20261019-1" defer></script>
<link rel="stylesheet" href="/admin/addons/shared/visit_agenda.css?v=20261019-1">
<link rel="stylesheet" href="/admin/addons/shared/client_invoices.css?v=20261020-8">
<script src="/admin/addons/shared/client_invoices.js?v=20261020-15" defer></script>
<link rel="stylesheet" href="/admin/addons/shared/additional_connection.css?v=20261020-1">
<script src="/admin/addons/shared/additional_connection.js?v=20261020-1" defer></script>
<link rel="stylesheet" href="/admin/addons/shared/ip_conflicts.css?v=20261020-1">
<script src="/admin/addons/shared/ip_conflicts.js?v=20261020-1" defer></script>
<link rel="stylesheet" href="/admin/addons/shared/connection_settings.css?v=20261020-2">
<script src="/admin/addons/shared/connection_settings.js?v=20261021-1" defer></script>
<script src="/admin/addons/shared/live_client_states.js?v=20261021-1" defer></script>
</head>
