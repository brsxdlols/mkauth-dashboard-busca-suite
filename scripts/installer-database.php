<?php
// Installer only: bounded reconnects; local socket fallback never redirects a configured host.
function mka_installer_database_endpoints($configuredHost) {
    return $configuredHost!==false && $configuredHost!==''
        ? array($configuredHost,$configuredHost,$configuredHost)
        : array('127.0.0.1','localhost','127.0.0.1','localhost');
}
function mka_installer_database_connect($connector=null,$pause=null) {
    $hosts=mka_installer_database_endpoints(getenv('MYSQL_HOST'));
    $user=getenv('MYSQL_USER')?:'root';$password=getenv('MYSQL_PASS')?:'vertrigo';$schema=getenv('MYSQL_DB')?:'mkradius';
    $port=getenv('MYSQL_PORT')?:((int)ini_get('mysqli.default_port')?:3306);
    if (!ctype_digit((string)$port) || (int)$port<1 || (int)$port>65535) throw new RuntimeException('MYSQL_PORT invalido.');
    $socket=getenv('MYSQL_SOCKET')?:null;
    if ($connector===null) $connector=function($host,$user,$password,$schema,$port,$socket) {
        $db=mysqli_init();$db->options(MYSQLI_OPT_CONNECT_TIMEOUT,3);
        try {
            if (!@$db->real_connect($host,$user,$password,$schema,(int)$port,$socket))
                throw new RuntimeException(mysqli_connect_error(),mysqli_connect_errno());
            return $db;
        } catch (Throwable $error) {
            try {$db->close();} catch (Throwable $ignored) {}
            throw $error;
        }
    };
    if ($pause===null) $pause=function(){usleep(300000);};
    foreach ($hosts as $index=>$host) {
        try { return $connector($host,$user,$password,$schema,$port,$socket); }
        catch (Throwable $error) {
            if (!in_array((int)$error->getCode(),array(2002,2003,2006,2013),true) || $index===count($hosts)-1)
                throw new RuntimeException('Conexao do instalador ao banco falhou ('.$host.', codigo '.(int)$error->getCode().').',0,$error);
            $pause();
        }
    }
    throw new RuntimeException('Banco indisponivel.');
}
