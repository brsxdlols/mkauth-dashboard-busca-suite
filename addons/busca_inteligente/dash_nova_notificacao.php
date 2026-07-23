<?php include('config.php'); ?>

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

    <link href="estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/estilo.css" rel="stylesheet" type="text/css" />

    <script src="scripts/jquery.js"></script>
    <script src="scripts/mk-auth.js"></script>
</head>

<body>
    <?php include('topo.php'); ?>

    <div class="container-fluid">
        <form action="dash_notificacoes.php" method="post">

            <div class="input-group my-1">
                <span class="input-group-text" id="basic-addon1">Data / Hora</span>
                <input name="data" type="datetime-local" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="<?php echo $data_atual; ?>">
            </div>

            <div class="input-group mb-1">
                <span class="input-group-text" id="basic-addon1">Mensagem</span>
                <textarea name="mensagem" class="form-control" aria-label="With textarea"></textarea>

            </div>

            <div class="form-floating mb-1">
                <select name="id_usuario" class="form-select" id="floatingSelect" aria-label="Floating label select example">
                    <?php
                    foreach ($usuario_id as $key => $value) {
                        $selected = ($usuario_logado == $value) ? "selected=\"selected\"" : null;
                        echo "<option value=\"$key\" $selected >$value</option>";
                    }
                    ?>
                </select>
                <label for="floatingSelect">Usuario</label>
            </div>

            <input type="hidden" name="id_remetente" value="<?php echo  $usuario[$usuario_logado]; ?>"/>

            <input type="submit" value="ENVIAR" name="new_nota" class="btn btn-primary btn-lg">

        </form>
    </div>

    <?php include('baixo.php'); ?>
    <script src="menu.js.php"></script>

    <script>
        $('#systopo').hide();
    </script>
</body>

</html>