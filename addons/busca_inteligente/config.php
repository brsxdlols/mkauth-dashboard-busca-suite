<?php include('addons.class.php'); ?>

<?php

if(!file_exists(__DIR__."/../../login.hhvm")){
    $ext_mk = '.php';
    // VERIFICA SE O USUARIO ESTA LOGADO --------------------------------------------------------------
    session_name('mka');
    if (!isset($_SESSION)) session_start();
    if (!isset($_SESSION['mka_logado'])) exit('Acesso negado... <a href="/admin/login.php">Fazer Login</a>');
}else{
    $ext_mk = '.hhvm';
}



$now = date('Y-m-d');

        $link = mysqli_connect("127.0.0.1", "root", "vertrigo", 'mkradius');

        if (!$link) {
            echo "Error: Falha ao conectar-se com o banco de dados MySQL." . PHP_EOL;
            echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
            exit;
        }

     

        $usuario_logado = $_SESSION['MKA_Usuario'] == '' ? $_SESSION['MM_Usuario'] : $_SESSION['MKA_Usuario'];

		// Fix MK-AUTH versoes antigas
		if(isset($_SESSION['MM_Usuario'])){
			echo '<script src="../../scripts/vue.js"></script>';
        }

		$permissao = "perm_totais";

		$query_permissao = mysqli_query($link, "SELECT usuario FROM sis_perm WHERE nome LIKE '$permissao' AND usuario LIKE '$usuario_logado' AND permissao LIKE 'sim'");	
		
		if ($query_permissao) {
			
			$liberar_permissao = mysqli_num_rows($query_permissao);
            if ($liberar_permissao >= 1)
			{
				//echo "Acesso Liberado!"; - TUDO OK.
				$acesso_permitido = true;
			}
			else {
				//echo "Acesso Negado!";
				$acesso_permitido = false;
				//mysql_close($link);
			}
		}

  
    DEFINE('PORT_API',8728);

    function debug($v){
        echo "<pre>", print_r($v), "</pre>";
    }

        function diffDate2($data_inicio, $data_fim){
            $d1 = new DateTime($data_inicio);
            $d2 = new DateTime($data_fim);
    
            // Resgata diferen a entre as datas
            //$dateInterval = ;

            if($d1 < $d2){
                return $d1->diff($d2)->days;
            }else{
                return ($d1->diff($d2)->days) * (-1);
            }
            //echo "</br>";
        }

        function diffDate($ds, $de)
        {
            $d1 = new DateTime($ds);
            $d2 = new DateTime($de);
    
            // Resgata diferenca entre as datas
            //$dateInterval = ;
    
            if ($d1 < $d2) {
                $diff = $d1->diff($d2)->days;
            } else {
                $diff = ($d1->diff($d2)->days) * (-1);
            }
    
            if ($diff < 0) {
                return "<b>Contrato:</b> <span class='conn_alerta'>Vencido</span>";
            } else if ($diff <= 60) {
                return "<b>Contrato:</b> A vencer";
            } else {
                return "<b>Contrato:</b> <span class='conn_boa'>Ativo</span>";
            }
        }
    
        //Funçço parar verificaçço de String iniciada por um termo
        // Case sensitive
        function startsWith($string, $startString)
        {
            $len = strlen($startString);
            return (strtolower(substr($string, 0, $len)) === $startString);
        }
    /*$query_nas = mysql_query("SELECT * FROM nas WHERE nasname LIKE '$router'");
    if(!$query_nas){
        echo mysql_error();
    }
    while($nas = mysql_fetch_array($query_nas)){
        $login_router = $nas['userapi'];
        $pass_router  = $nas['senha'];
    }*/

?>