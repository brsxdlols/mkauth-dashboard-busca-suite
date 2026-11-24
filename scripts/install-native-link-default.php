<?php
/** Prepare native links; the installer CLI selects HHVM as requested. */
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
    mysqli_report(MYSQLI_REPORT_OFF);
    $db=mka_installer_database_connect();
    if(isset($argv[1])){if(!is_dir($argv[1]))mkdir($argv[1],0700,true);$before=$db->query('SELECT id,links_ext FROM busca_inteligente_cfg ORDER BY id DESC LIMIT 1');file_put_contents($argv[1].'/native-links-before.json',json_encode($before?$before->fetch_assoc():null));chmod($argv[1].'/native-links-before.json',0600);}
    // Installation explicitly selects the supported native HHVM routes.
    mka_install_native_link_default($db);
    if(!$db->query("UPDATE busca_inteligente_cfg SET links_ext='hhvm' ORDER BY id DESC LIMIT 1"))throw new RuntimeException('Nao foi possivel selecionar HHVM.');
    echo '[busca] Links nativos: HHVM selecionado e conferido.'.PHP_EOL;
    $db->close();
}
