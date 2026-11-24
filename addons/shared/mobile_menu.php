<?php
function mka_mobile_menu_version($db) {
    $q=$db->query("SELECT valor FROM sis_opcao WHERE nome='suite_mobile_menu_version' LIMIT 1");
    if(!$q)throw new RuntimeException('Não foi possível consultar a versão do menu.');
    $row=$q->fetch_assoc();return $row&&$row['valor']==='legada'?'legada':'nova';
}
function mka_mobile_menu_save($db,$value,$allowed,$expectedToken,$submittedToken) {
    if(!$allowed || !is_string($expectedToken) || $expectedToken==='' || !is_string($submittedToken) || !hash_equals($expectedToken,$submittedToken))throw new DomainException('Sessão inválida ou permissão insuficiente.',403);
    if(!is_string($value)||!in_array($value,array('nova','legada'),true))throw new InvalidArgumentException('Versão de menu inválida.',422);
    $q=$db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('suite_mobile_menu_version',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
    if(!$q)throw new RuntimeException('Não foi possível preparar a configuração do menu.');
    $q->bind_param('s',$value);if(!$q->execute())throw new RuntimeException('Não foi possível salvar a configuração do menu.');
    return $value;
}
