
<?php

$login = $_GET['login'];

function liberacao_confianca($login){
    require_once('config.php');

    $data_atual = date('Y-m-d 00:00:00');
    $remover_obs_data = date('Y-m-d 00:00:00', strtotime('+3 days', strtotime($data_atual)));

    //echo $remover_obs_data;
    
    $query = mysqli_query($link, "UPDATE sis_cliente SET observacao = 'sim', rem_obs = '$remover_obs_data' WHERE login = '$login'");

    if(!$query){
        echo mysqli_error($link);
    }

    //echo "<br>UPDATE sis_cliente SET observacao = 'sim', rem_obs = '$remover_obs_data' WHERE login = '$login'<br>";

    return print "Cliente $login em Observacao com Sucesso!";
}

liberacao_confianca($login);

?>

<script> 
setTimeout("window.close()",2000) 
</script>