<?php
// Caller must resolve the registered login within the administrator's groups first.
function radius_current_state($db,$login,$client) {
    $state=array('state'=>'unknown','login'=>$login,'name'=>$client['name'] ?? '',
        'client_url'=>$client['url'] ?? '', 'checked_at'=>date('d/m/Y H:i:s'));
    if(!$client)return $state;
    try {
        $s=$db->prepare("SELECT 1 FROM radacct WHERE acctstoptime IS NULL AND LOWER(TRIM(username))=LOWER(TRIM(?)) LIMIT 1");
        if(!$s)return $state;
        $s->bind_param('s',$login);if(!$s->execute())return $state;
        $state['state']=$s->get_result()->num_rows>0?'online':'offline';
    }catch(Throwable $e){ /* Never infer Offline from a failed status query. */ }
    return $state;
}
