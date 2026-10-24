<?php
// Monitoring preferences do not change native NAS authentication or customer sessions.
function mka_router_monitoring_disabled($db) {
    $q=$db->query("SELECT valor FROM sis_opcao WHERE nome='suite_router_monitoring_disabled' LIMIT 1");
    $r=$q?$q->fetch_assoc():null;
    $values=$r?json_decode((string)$r['valor'],true):array();
    return is_array($values)?array_values(array_filter($values,'is_string')):array();
}
function mka_router_monitoring_routers($db) {
    $q=$db->query("SELECT nasname,shortname FROM nas WHERE nasname IS NOT NULL AND nasname<>'' ORDER BY nasname");
    return $q?$q->fetch_all(MYSQLI_ASSOC):array();
}
function mka_router_monitoring_save($db,$enabled) {
    if(!is_array($enabled))throw new InvalidArgumentException('Lista de ramais inválida.');
    $disabled=array();
    foreach(mka_router_monitoring_routers($db) as $router) {
        if(!in_array($router['nasname'],$enabled,true))$disabled[]=$router['nasname'];
    }
    $json=json_encode(array_values(array_unique($disabled)));
    $s=$db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('suite_router_monitoring_disabled',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
    $s->bind_param('s',$json);
    if(!$s->execute())throw new RuntimeException('Não foi possível salvar o monitoramento dos ramais.');
}
