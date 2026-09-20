<!-- 
    RELATÓRIO DE FATURAMENTO
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php require_once('config_graf_faturamento.php'); ?>

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

    <link href="css/bootstrap.min.5.3.2A.css" rel="stylesheet" type="text/css" />

    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <!-- <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" /> -->
    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css">

    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

    <!-- <link href="css/fontawesome.min.css" rel="stylesheet" type="text/css" /> -->

    <script src="js/all.min.js"></script>

    <style type="text/css">
        /* #container9 { height:100px; } */
    </style>

    <script>
        function abrirJanela(pagina, largura, altura) {
            // Definindo centro da tela
            var esquerda = (screen.width - largura) / 2;
            var topo = (screen.height - altura) / 2;
            // Abre a nova janela
            minhaJanela = window.open(pagina, '', 'height=' + altura + ', width=' + largura + ', top=' + topo + ', left=' + esquerda);
        }
    </script>
</head>

<body>



    <?php include('../../topo.php'); ?>