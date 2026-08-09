<?php
            $sql_planos = mysqli_query($conn, "SELECT nome, valor FROM sis_plano ORDER BY nome");
            if (!$sql_planos) {
                echo "Invalid query<br/>";
                echo mysqli_error($link);
            } else {
                //$num_planos = mysql_num_rows($result);
                //echo "<b>Total Clientes Cancelados = $num_desativados</b></br></br>";

                while ($row = mysqli_fetch_array($sql_planos)) {
                    $nome_plano = $row['nome'];
                    $valor_plano = $row['valor'];

                    $sql_clientes_plano = mysqli_query($conn, "SELECT plano FROM sis_cliente WHERE plano LIKE '$nome_plano' AND cli_ativado LIKE 's'");

                    $sql_clientes_plano_add = mysqli_query($conn, "SELECT plano FROM sis_adicional WHERE plano LIKE '$nome_plano'");

                    $num_clientes_plano = mysqli_num_rows($sql_clientes_plano) + mysqli_num_rows($sql_clientes_plano_add);

                    $lista_planos[$nome_plano] = $num_clientes_plano;
                    $valores_planos[$nome_plano] = $valor_plano;

                    $soma_valores[$nome_plano] = $valor_plano * $num_clientes_plano;
                }
            }
            ?>

