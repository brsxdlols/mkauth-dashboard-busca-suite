<?php
/** Initialize native link preferences without overriding a saved PHP/HHVM choice. */
function mka_install_native_link_default($db,$table='busca_inteligente_cfg') {
    if(!preg_match('/^[a-z0-9_]+$/D',$table))throw new InvalidArgumentException('Tabela invalida');
    $query=function($sql)use($db){$result=$db->query($sql);if($result===false)throw new RuntimeException('Falha ao preparar extensao dos links nativos.');return $result;};
    $query("CREATE TABLE IF NOT EXISTS `$table` (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,num_conexoes INT NOT NULL DEFAULT 50,tempo_queda INT NOT NULL DEFAULT 60,porta_acesso INT NOT NULL DEFAULT 80,porta_acesso2 INT,porta_acesso3 INT,link_whats VARCHAR(255) DEFAULT 'https://api.whatsapp.com/send?phone=55',links_ext VARCHAR(10) DEFAULT 'hhvm',check_online VARCHAR(10) DEFAULT 'mkauth')");
    if($query("SHOW COLUMNS FROM `$table` LIKE 'links_ext'")->num_rows===0)$query("ALTER TABLE `$table` ADD links_ext VARCHAR(10) DEFAULT 'hhvm'");
    // Only the column default changes; stored, explicit choices are left intact.
    $query("ALTER TABLE `$table` ALTER COLUMN links_ext SET DEFAULT 'hhvm'");
    $result=$query("SELECT id,links_ext FROM `$table` ORDER BY id DESC LIMIT 1");
    if($result->num_rows===0){
        $query("INSERT INTO `$table` (num_conexoes,tempo_queda,porta_acesso,porta_acesso2,porta_acesso3,link_whats,links_ext,check_online) VALUES (50,60,80,0,0,'https://api.whatsapp.com/send?phone=55','hhvm','mkauth')");
        return 'hhvm inicializado';
    }
    $row=$result->fetch_assoc();$extension=strtolower(trim((string)$row['links_ext']));
    if(in_array($extension,array('php','hhvm'),true))return $extension.' existente preservado';
    $query("UPDATE `$table` SET links_ext='hhvm' WHERE id=".(int)$row['id']);
    return 'hhvm aplicado a configuracao vazia/invalida';
}
if(PHP_SAPI==='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
    require_once __DIR__.'/installer-database.php';
    $db=mka_installer_database_connect();
    echo '[links nativos] '.mka_install_native_link_default($db).PHP_EOL;
    $db->close();
}
