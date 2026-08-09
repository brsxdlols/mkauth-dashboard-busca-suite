<?php include('nav/header.php'); ?>

<body class="">

    <?php include('../../topo.php'); ?>

    <ul class="nav nav-tabs justify-content-center py-2">
        <li class="nav-item">
            <a href="#" class="nav-link" onClick="window.history.back()">
            <i class="fa-solid fa-circle-chevron-left fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php" class="nav-link" aria-current="page"><?php echo $Manifest->{'name'} . " - V " . $Manifest->{'version'}; ?></a>
        </li>
        <li class="nav-item">
            <a href="cli_conn_alerta.php" class="nav-link">
            <i class="fa-solid fa-circle-exclamation fs-4 text-warning"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="chamados_abertos.php" class="nav-link active">
            <i class="fa-solid fa-headset fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="score.php" class="nav-link">
            <i class="fa-solid fa-ranking-star fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="relcontratos.php" class="nav-link">
            <i class="fa-solid fa-file-signature fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="cfg.php" class="nav-link">
            <i class="fa-solid fa-gear fs-4"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onClick="window.print()" >
            <i class="fa-solid fa-print fs-4"></i>
            </a>
        </li>

    </ul>

    <?php

    $busca = isset($_GET['busca']) == '' ? '' : $_GET['busca'];

    $busca=trim($busca);		
    $busca = str_replace(" ","%", $busca);	

    $organizar = isset($_GET['organizar']) == '' ? 'endereco' : $_GET['organizar'];

    $lista_organizar = array(
            "endereco"  => "endereco A-Z",
            "endereco DESC"  => "endereco Z-A",
            "nome" => "nome A-Z",
            "nome DESC" => "nome Z-A"
    );

    
    $query_motivo_chamado = mysqli_query($link, "SELECT DISTINCT assunto FROM sis_suporte ORDER BY assunto");
    $motivo_chamado["%"] = "TODOS";
    while ($row2 = mysqli_fetch_array($query_motivo_chamado)){
        $motivo_chamado[$row2['assunto']] = $row2['assunto'];
    }

    if ($_GET['assunto'] == "TODOS" || $_GET['assunto'] == ""){
        $assunto = "%";
    }else{
        $assunto = $_GET['assunto'];
    }

    $query_tecnico_chamado = mysqli_query($link, "SELECT id, nome FROM sis_func ORDER BY nome");
    $tecnico_chamado["%"] = "TODOS";
    while ($row3 = mysqli_fetch_array($query_tecnico_chamado)){
        $tecnico_chamado[$row3['id']] = $row3['nome'];
    }

    if ($_GET['tecnico'] == "TODOS" || $_GET['tecnico'] == ""){
        $tecnico = "%";
    }else{
        $tecnico = $_GET['tecnico'];
    }

    ?>

    <form action="" method="get" id="form_pesquisa" class="no_print">
        <table id="buscar">
            <tr>
            <td class="input_pesquisar">Digite o que procura:</td>
            <td class="input_pesquisar"><b>Assunto:</b></td>
            <td class="input_pesquisar"><b>Técnico:</b></td>
            <td><b>Organizar por:</b></td>
            <td></td>
            </tr>
            <tr>
            <td><input type="search" class="input_pesquisar" name="busca" 
            placeholder="Busque por nome ou endereco" value="<?php echo $busca; ?>">
            </input>
            </td>

            <td>
                <select name="assunto" class="select_organizar">
                    <?php
                        foreach ($motivo_chamado as $key => $value) {
                            $selected = ($assunto == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                    }
                    ?>
                    </select>
            </td>

            <td>
                <select name="tecnico" class="select_organizar">
                    <?php
                        foreach ($tecnico_chamado as $key => $value) {
                            $selected = ($tecnico == $key) ? "selected=\"selected\"" : null;
                            echo "<option value=\"$key\" $selected >$value</option>";
                    }
                    ?>
                    </select>
            </td>

            <td>            
            <select name="organizar" class="select_organizar">
            <?php
                foreach ($lista_organizar as $key => $value) {
                    $selected = ($organizar == $key) ? "selected=\"selected\"" : null;
                    echo "<option value=\"$key\" $selected >$value</option>";
            }
            ?>
            </select>
            </td>
            <td><input type="submit" name="submit" value="Buscar" id="btn_buscar" /></td>
            </tr>
            </table>
    </form>


    <?php

    /*$key = "-i /root/.ssh/id_rsa";
    $ssh = shell_exec("sudo ssh -l mkauth@10.10.150.1 -p 22");
    if($ssh){
        echo "foi!";
    }else{
        echo "error!";
    }*/
    //echo $comando;  'ip firewall address-list add address=10.10.150.3 list=test3
  // \"ipv6 firewall address-list add list=BloqV6 comment=$usuario address=[/ipv6 pool used get [find info=$usuario] prefix]\" 
?>


    <?php
        //$lista_chamados = mysqli_query($link, "SELECT * FROM sis_suporte WHERE status = 'aberto' AND login IN (SELECT login FROM sis_cliente WHERE cli_ativado = 's' ORDER BY endereco)");


        
        // Carrega as configuracoes do banco
        $ler_busca_inteligente_cfg = mysqli_query($link, "SELECT * FROM busca_inteligente_cfg");
        while($cfg = mysqli_fetch_array($ler_busca_inteligente_cfg)){
            $links_ext = $cfg['links_ext'];
        }

        function mascara($mascara,$string){
            $del_chars = array (' ', '(', ')', '-');
            $string = str_replace($del_chars,"",$string);
            for($i=0;$i<strlen($string);$i++){
                $mascara[strpos($mascara,"#")] = $string[$i];
            }
            return $mascara;
        }

        $lista_chamados = mysqli_query($link, "SELECT c.endereco, c.numero, c.bairro, c.cidade, c.complemento, c.estado, c.plano, c.login, c.senha, c.celular, c.celular2, sup.nome, sup.assunto, sup.abertura, sup.chamado, sup.visita FROM 
        sis_cliente c LEFT JOIN 
        sis_suporte sup ON
        c.login = sup.login WHERE 
        c.cli_ativado = 's' AND 
        /*c.login IN (SELECT login FROM sis_suporte WHERE status = 'aberto') AND */
        (c.nome LIKE '%$busca%' OR 
        c.endereco LIKE '%$busca%' OR 
        c.bairro LIKE '%$busca%' OR 
        c.cidade LIKE '%$busca%') AND
        sup.status = 'aberto' AND
        sup.assunto LIKE '$assunto' AND
        sup.tecnico LIKE '$tecnico'
        ORDER BY $organizar");

        /*if(!$lista_chamados){
            echo mysqli_error();
        }*/
        
        $tot_chamados_abertos = mysqli_num_rows($lista_chamados);

        echo "<span class='no_print'><b>Total Chamados = $tot_chamados_abertos</b></span>";

        while($row_cli = mysqli_fetch_array($lista_chamados)){
            //print_r($row_cli);
            // Dados do Cliente
            $login = $row_cli['login'];
            $pass_cli = $row_cli['senha'];
            $plano_cli = $row_cli['plano'];

            $end_cli = "{$row_cli['endereco']} nº {$row_cli['numero']}";
            $bairro_cli = $row_cli['bairro'];
            $cidade_uf_cli = $row_cli['cidade'].' / '.$row['estado'];
            $complemento_cli = $row_cli['complemento'];
            $fone_cli = $row_cli['celular'];
            if($fone_cli != ""){
                $fone_cli = mascara("(###) ####-####", $fone_cli);
            }
            $fone2_cli = $row_cli['celular2'];
            if($fone2_cli != ""){
                $fone2_cli = mascara("(###) ####-####", $fone_cli2);
            }

            /*$query_chamados = mysqli_query($link, "SELECT * FROM sis_suporte WHERE login = '$login'");
            while($ticket = mysqli_fetch_array($query_chamados)){

            }*/

            // Dados do suporte
            $assunto_ticket = $row_cli['assunto'];
            $abertura = $row_cli['abertura'];
            $abertura = date('d/m/Y h:i', strtotime($abertura));
            $chamado = $row_cli['chamado'];
            
            $nome = $row_cli['nome'];
            $visita = $row_cli['visita'];
            $visita = date('d/m/Y h:i', strtotime($visita));

            $query_texto_chamado = mysqli_query($link, "SELECT msg, msg_data FROM sis_msg WHERE chamado = '$chamado' ORDER BY msg_data");

            echo "";
            echo "
            <div class='div_chamados'>
            <table class='tab_chamados_abertos'>
            <tr>
                <td class='col_nome'>
                    <span class='tit_table'>Cliente:</span>
                    </br><a href='index.php?busca=$nome' title='Ver Cliente'>$nome</a></br>
                </td>
                <td class='col_end'>
                    <span class='tit_table'>End.:</span>
                    </br>$end_cli $complemento_cli
                </td>
                <td>
                    <span class='tit_table'>Chamado / Assunto</span></br>
                    $chamado / $assunto_ticket
                </td>
                <td>
                    <span class='tit_table'>Data Registro:</span>
                    </br>$abertura
                </td>
            </tr>
            <tr>
                <td class='col_nome'>
                    <span class='tit_table'>Data Visita:</span>
                    </br>$visita
                </td>
                <td>
                    <span class='tit_table'>Login / Senha</span>
                    </br>$login / $pass_cli
                </td>
                <td>
                    <span class='tit_table'>Contato:</span>
                    </br> $fone_cli / $fone2_cli
                </td>
                <td>
                    <span class='tit_table'>Plano:</span>
                    </br>$plano_cli
                </td>
            </tr>
            <tr>
                <td colspan='4'>
                <span class='tit_table'>Relatos:</span><br/>";

                while($row_msg = mysqli_fetch_array($query_texto_chamado)){
                    $texto_chamado = $row_msg['msg'];

                    //Converter UTF-8 do Banco de Dados
                    //$texto_chamado = html_entity_decode(htmlentities($texto_chamado, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-1');

                    $data_msg = $row_msg['msg_data'];
                    if($data_msg != ""){
                        $data_msg = date('d/m/Y h:i', strtotime($data_msg));
                    }
                    echo "<span class='tit_table'>Data:</span> $data_msg - $texto_chamado</br>";
                }
                echo "
                </td>
            </tr>
            <tr>
                <td colspan='3'>
                Assinatura: ____________________________________________________________
                </td>
            </tr>
            <tr>
            <td colspan='4' class='no_print link'>
            <a href='../../suporte_fechar.$links_ext?&chamado=$chamado' target='_blank'><b>Fechar Chamado: </b> $chamado</a>
            </td>
            </tr>
            </table>
            
            </div>";
            
            
            //echo "____________________________________________________________</br>Assinatura</br>";

        }


        mysqli_close($link); // Finaliza a conexao com o Banco de Dados

        ?>
                



<?php include('../../baixo.php'); ?>

        <script src="../../menu.js.php"></script>

    </body>
</html>