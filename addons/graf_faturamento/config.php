<!-- 
    RELATÓRIO DE FATURAMENTO
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php include('addons.class.php'); ?>

<?php

		//Conexão com o Banco de Dados
        $link = mysqli_connect("127.0.0.1", "root", "vertrigo", "mkradius");

        if (!$link) {
            echo "Error: Falha ao conectar-se com o banco de dados MySQL." . PHP_EOL;
            echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
            exit;
        }

        /*$database = mysql_select_db('mkradius', $link);

        if (!$database) {
            echo 'Erro ao selecionar DB';
        } else {
            //echo 'Database conectada';
        }*/


		
		//DEBUG
		//print_r($_SESSION);

		$exibir_graf_emprestimos = false;
		
		$usuario_logado = $_SESSION['MKA_Usuario'] == '' ? $_SESSION['MM_Usuario'] : $_SESSION['MKA_Usuario'];
		
		// Fix MK-AUTH versoes antigas
		if(isset($_SESSION['MM_Usuario'])){
			echo '<script src="../../scripts/vue.js"></script>';
		}

		// Fix for MKAUTH 22.02
		if(!file_exists("../../login.hhvm")){
			$ext_mk = '.php';
		}else{
			$ext_mk = '.hhvm';
		}

		$permissao = "perm_relFat";

		$query_permissao = mysqli_query($link, "SELECT usuario FROM sis_perm WHERE nome LIKE '$permissao' AND usuario LIKE '$usuario_logado' AND permissao LIKE 'sim'");	
		
		if ($query_permissao) {
			
			$liberar_permissao = mysqli_num_rows($query_permissao);
			if ($liberar_permissao >= 1)
			{
				//echo "Acesso Liberado!"; - TUDO OK.
				$acesso_permitido = true;
			}
			else {
				echo "Acesso Negado!";
				$acesso_permitido = false;
				mysqli_close($link);
			}
		}
?>