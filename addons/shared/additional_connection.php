<?php
function mka_additional_connection($db,$uuid,$scope) {
    $q=$db->prepare("SELECT a.uuid_adicional,a.username,a.nome,a.plano,a.bloqueado,a.ip,a.mac,a.ramal,c.login AS parent_login,c.cli_ativado FROM sis_adicional a INNER JOIN sis_cliente c ON c.login=a.login WHERE a.uuid_adicional=? AND ($scope) LIMIT 1");
    $q->bind_param('s',$uuid);$q->execute();$row=$q->get_result()->fetch_assoc();$q->close();
    if(!$row)throw new DomainException('Adicional não encontrado ou sem permissão.');
    return $row;
}
function mka_additional_repair_methods(){
    // Native method pairs verified in the additional branch of reparar.hhvm (25.08 / 26.02).
    foreach(array(
        array('FAUAUMTAKAUMKATMKMHKHMHAUAHAHKAAH','FMUMMAHKTMTMUKUAMKMMAKUMKKMAMAHAU'),
        array('FMKAMMMMMKHMUAHMAATKMAKAHKUMAKUAT','FMTAUMTMMAUKAATKMAKKTMUAUKAAHMHMM')
    ) as $pair){
        $update=array('MKAC_XRADIUS',$pair[0]);$disconnect=array('MKAC_FREERADIUS',$pair[1]);
        if(is_callable($update)&&is_callable($disconnect))return array($update,$disconnect);
    }
    return null;
}
