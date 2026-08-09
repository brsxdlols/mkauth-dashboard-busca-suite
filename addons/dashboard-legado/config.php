<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
include_once('addons.class.php');

$now = date('Y-m-d');

// Fix for MKAUTH 22.02
if (!file_exists("login.hhvm")) {
	$ext_mk = '.php';

	// VERIFICA SE O USUARIO ESTA LOGADO --------------------------------------------------------------
	session_name('mka');
	if (!isset($_SESSION)) session_start();
	if (!isset($_SESSION['MKA_Logado'])) exit('Acesso negado... <a href="/admin/login.php">Fazer Login</a>');
} else {
	$ext_mk = '.hhvm';
}

// debug($_SESSION);

?>
<?php

//Conexão com o Banco de Dados
$conn = mysqli_connect("127.0.0.1", "root", "vertrigo", "mkradius");

if (!$conn) {
	echo "Error: Falha ao conectar-se com o banco de dados MySQL." . PHP_EOL;
	echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
	exit;
}

require_once __DIR__ . '/../shared/layout_mode.php';

if (mka_suite_get_layout_mode($conn) !== 'legado') {
	header('Location: ../dashboard/');
	exit;
}

// Fix MK-AUTH versoes antigas
if (isset($_SESSION['MM_Usuario'])) {
	echo '<script src="scripts/vue.js"></script>';
}



function permissao($permissao)
{
	$link2 = mysqli_connect("127.0.0.1", "root", "vertrigo", "mkradius");

	$usuario_logado = $_SESSION['MKA_Usuario'] == '' ? $_SESSION['MM_Usuario'] : $_SESSION['MKA_Usuario'];

	$query_permissao = mysqli_query($link2, "SELECT usuario FROM sis_perm WHERE nome LIKE '$permissao' AND usuario LIKE '$usuario_logado' AND permissao LIKE 'sim'");

	if ($query_permissao) {

		$liberar_permissao = mysqli_num_rows($query_permissao);
		if ($liberar_permissao >= 1) {
			$ok = true;
		} else {
			$ok = false;
		}
	}
	return $ok;
}


function debug($pre)
{
	echo "<pre>";
	print_r($pre);
	echo "</pre>";
}

function startsWith($string, $startString)
{
	$len = strlen($startString);
	return (strtolower(substr($string, 0, $len)) === $startString);
}

$data_abreviada = date('Y-m-d');
$data_atual = date('Y-m-d H:i:s');

$nova_data_1 = date('Y-m-d H:i:s', strtotime('+1 days', strtotime($data_atual)));
$nova_data_5 = date('Y-m-d H:i:s', strtotime('+5 days', strtotime($data_atual)));

$usuario_logado = $_SESSION['MKA_Usuario'] == '' ? $_SESSION['MM_Usuario'] : $_SESSION['MKA_Usuario'];

$query_ler_usuarios = mysqli_query($conn, "SELECT idacesso, login FROM sis_acesso");
while ($user = mysqli_fetch_array($query_ler_usuarios)) {
	$usuario[$user['login']] = $user['idacesso'];
	$usuario_id[$user['idacesso']] = $user['login'];
}

//debug($usuario);
if (!$query_ler_usuarios) {
	echo mysqli_error($conn);
}


?>
