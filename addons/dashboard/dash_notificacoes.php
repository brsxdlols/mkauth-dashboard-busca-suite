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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="scripts/jquery.js"></script>
    <script src="scripts/mk-auth.js"></script>
</head>

<body>
    <?php include('topo.php'); ?>

    <div class="container-fluid">
        <?php

        $new_nota_data = str_replace("T", " ", $_POST['data']);
        //echo ($new_nota_data);
        $new_nota_mensagem = trim($_POST['mensagem']);
        $new_nota_usuario = $_POST['id_usuario'];
        $new_nota_remetente = $_POST['id_remetente'];

        //debug($_POST);
        if (isset($_POST['new_nota']) && $new_nota_mensagem != "") {
            $query = mysqli_query($conn, "INSERT INTO dashboard_am_sis_notificacoes (data, mensagem, id_usuario, id_remetente) VALUES ('$new_nota_data', '$new_nota_mensagem', '$new_nota_usuario', '$new_nota_remetente')");

            if (!$query) {
                echo mysqli_error($conn);
            }
        }

        $user_select = $_GET['id_destinatario'] == "" ? "%" : $_GET['id_destinatario'];

        $data = $_GET['data'] == "" ? $data_abreviada : $_GET['data'];

        //debug($_GET);
        ?>
        <form action="" method="get">
            <div class="row g-1">

                <div class="form-floating col">
                    <input type="date" class="form-control" name="data" id="floatingPassword" placeholder="teste" value="<?php echo $data; ?>">
                    <label for="floatingPassword">Data</label>
                </div>

                <div class="form-floating mb-1 col">
                    <select name="id_destinatario" class="form-select" id="floatingSelect">
                        <?php
                        $usuario_id['%'] = "Todos";
                        foreach ($usuario_id as $key => $value) {
                            $selected = ($user_select == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                        }
                        ?>
                    </select>
                    <label for="floatingSelect">Destinatario</label>
                </div>

                <div class="col-auto">
                    <input type="submit" value="OK" class="btn btn-primary">
                </div>
            </div>

        </form>


        <?php
       $query_ler_notificacoes = mysqli_query($conn, "SELECT * FROM dashboard_am_sis_notificacoes WHERE DATE(data) <= '$data' AND (id_remetente LIKE '$usuario[$usuario_logado]' OR id_usuario LIKE '$usuario[$usuario_logado]') AND id_usuario LIKE '$user_select'");


       if (isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']); // Sanitiza o ID
        $query_delete = mysqli_query($conn, "DELETE FROM dashboard_am_sis_notificacoes WHERE id = $delete_id");
    
        if ($query_delete) {
            echo "<div class='alert alert-success'>Notificação deletada com sucesso!</div>";
        } else {
            echo "<div class='alert alert-danger'>Erro ao deletar a notificação: " . mysqli_error($conn) . "</div>";
        }
    }

    if (isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']); // Sanitiza o ID
        $stmt = mysqli_prepare($conn, "DELETE FROM dashboard_am_sis_notificacoes WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
    
        if (mysqli_stmt_execute($stmt)) {
            echo "success"; // Retorna sucesso se a exclusão for bem-sucedida
        } else {
            echo "error"; // Retorna erro se a exclusão falhar
        }
    }
    
    

        if (!$query_ler_notificacoes) {
            echo mysqli_error($conn);
        }
        if (mysqli_num_rows($query_ler_notificacoes) > 0) {
            echo "<table class='table table-striped table-hover '>";
            echo "<thead class='bg-primary text-uppercase'>
            
            <tr class=''>
            <th class='fw-bold text-white'>Data</th>
            <th class='fw-bold text-white'>Mensagem</th>
            <th class='fw-bold text-white'>Usuario</th>
            <th class='fw-bold text-white'>Remetente</th>

            </tr></thead>
            <tbody>";
            while ($n = mysqli_fetch_array($query_ler_notificacoes)) {
                $data = $n['data'];
                $data = date('d/m/Y H:i:s', strtotime($data));
            
                $mensagem = $n['mensagem'];
                $id_usuario = $n['id_usuario'];
                $id_remetente = $n['id_remetente'];
            
                echo "<tr>
    <td>$data</td>
    <td>$mensagem</td>
    <td>$usuario_id[$id_usuario]</td>
    <td>$usuario_id[$id_remetente]</td>
    <!-- Alteração para um botão -->
    <td><button class='btn btn-danger btn-sm delete-notification' data-id='{$n['id']}'>Excluir</button></td>
</tr>";

            }
            


            echo "</tbody></table>";
            echo "<a href='dash_nova_notificacao.php' class='btn bg-primary text-white'>NOVA NOTIFICAÇÃO</a>";
        } else {
            echo "<a href='dash_nova_notificacao.php' class='btn bg-primary text-white'>NOVA NOTIFICAÇÃO</a>";
        }




        ?>
    </div>

    <?php include('baixo.php'); ?>
    <script src="menu.js.php"></script>

    <script>
        $('#systopo').hide();
    </script>

<script>
    $(document).ready(function() {
        // Quando o botão de exclusão for clicado
        $(".delete-notification").on("click", function() {
            var notificationId = $(this).data('id'); // Captura o ID da notificação
            var row = $(this).closest("tr"); // Obtém a linha da tabela da notificação

            // Faz a requisição AJAX para excluir a notificação
            $.ajax({
                url: '', // O caminho para o arquivo PHP que processa a exclusão (pode ser o mesmo arquivo)
                type: 'GET',
                data: { delete_id: notificationId }, // Envia o ID da notificação para exclusão
                success: function(response) {
                    // Se a exclusão foi bem-sucedida, remove a linha da tabela
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                },
                error: function() {
                    alert("Erro ao excluir a notificação.");
                }
            });
        });
    });
</script>

</body>

</html>