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

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <!-- <link href="css/fontawesome.min.css" rel="stylesheet" type="text/css" /> -->
    <link href="css/css.css" rel="stylesheet" type="text/css" />
    <script src="js/am_scripts.js"></script>
    <script src="js/all.min.js"></script>

</head>
