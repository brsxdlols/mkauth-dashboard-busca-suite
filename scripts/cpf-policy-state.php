<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';
$db=mka_installer_database_connect();$db->set_charset('utf8mb4');
$action=$argv[1];$path=$argv[2];
$key='nn_permitir_sem_cpf';
$q=$db->query("SELECT valor FROM sis_opcao WHERE nome='nn_permitir_sem_cpf' LIMIT 1");$row=$q->fetch_assoc();$value=$row?$row['valor']:null;
if($action==='backup') {
    $all=$db->query('SELECT * FROM sis_opcao')->fetch_all(MYSQLI_ASSOC);
    $data=array('key'=>$key,'before'=>$value,'after'=>$value===null?'nao':$value,'table_schema'=>$db->query('SHOW CREATE TABLE sis_opcao')->fetch_row()[1],'table_rows'=>$all);
    $encoded=json_encode($data,JSON_PRETTY_PRINT);if($encoded===false)throw new RuntimeException('Backup da tabela contem dados nao serializaveis');
    if(file_put_contents($path,$encoded,LOCK_EX)===false)throw new RuntimeException('Backup da tabela sis_opcao falhou');chmod($path,0600);
} elseif($action==='initialize') {
    $data=json_decode(file_get_contents($path),true);
    if($value!==$data['before'])throw new RuntimeException('Politica alterada durante instalacao');
    $db->query("INSERT IGNORE INTO sis_opcao(nome,valor) VALUES('nn_permitir_sem_cpf','nao')");
} elseif($action==='restore') {
    $data=json_decode(file_get_contents($path),true);
    if($value!==$data['after'])throw new RuntimeException('Politica alterada depois da instalacao; revisar antes de reverter');
    if($data['before']===null)$db->query("DELETE FROM sis_opcao WHERE nome='nn_permitir_sem_cpf'");
    else {$s=$db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('nn_permitir_sem_cpf',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");$s->bind_param('s',$data['before']);$s->execute();}
} else throw new RuntimeException('Acao desconhecida');
echo json_encode(array('policy'=>$value,'action'=>$action));
