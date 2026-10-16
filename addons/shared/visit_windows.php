<?php
// Native timestamps remain valid; the original message identifies visits with only a period.
function mka_visit_period($db,$reference,$visit){
 if(substr((string)$visit,11,5)!=='00:00')return '';
 $q=$db->prepare("SELECT msg FROM sis_msg WHERE chamado=? AND (msg LIKE '%[Periodo da visita: manha; sem horario definido]%' OR msg LIKE '%[Periodo da visita: tarde; sem horario definido]%') ORDER BY id DESC LIMIT 1");$reference=(string)$reference;$q->bind_param('s',$reference);$q->execute();$r=$q->get_result()->fetch_assoc();
 if(!$r)return '';return strpos($r['msg'],'[Periodo da visita: manha;')!==false?'manha':'tarde';
}
