<?php
// Caller must resolve the registered login within the administrator's groups first.
function radius_current_state($db,$login,$client) {
    $state=array('state'=>'unknown','login'=>$login,'name'=>$client['name'] ?? '',
        'client_url'=>$client['url'] ?? '', 'checked_at'=>date('d/m/Y H:i:s'));
    if(!$client)return $state;
    try {
        $s=$db->prepare("SELECT acctstarttime,acctstoptime FROM radacct WHERE acctstoptime IS NULL AND LOWER(TRIM(username))=LOWER(TRIM(?)) ORDER BY acctstarttime DESC LIMIT 1");
        if(!$s)return $state;
        $s->bind_param('s',$login);if(!$s->execute())return $state;
        $session=$s->get_result()->fetch_assoc();
        $state['state']=$session?'online':'offline';
        if(!$session){
            // Indexed username lookup, matching the existing Last connections report.
            $indexHint='';$indexes=$db->query("SHOW INDEX FROM radacct WHERE Column_name='username' AND Seq_in_index=1");
            if($indexes)while($index=$indexes->fetch_assoc())if(preg_match('/^[A-Za-z0-9_]+$/D',$index['Key_name'])){$indexHint=' FORCE INDEX (`'.$index['Key_name'].'`)';break;}
            // Never scan the full accounting history in a three-second monitor.
            if($indexHint!==''){
                $last=$db->prepare("SELECT acctstarttime,acctstoptime FROM radacct".$indexHint." WHERE username=? AND acctstoptime IS NOT NULL ORDER BY acctstoptime DESC LIMIT 1");
                if($last){$last->bind_param('s',$login);if($last->execute())$session=$last->get_result()->fetch_assoc();}
            }
        }
        $state['session']=$session ?: null;
    }catch(Throwable $e){ /* Never infer Offline from a failed status query. */ }
    return $state;
}

function radius_current_event($state,$rows,$history=null) {
    if(!in_array($state['state'] ?? '',array('online','offline'),true))return null;
    $online=$state['state']==='online';$session=$state['session'] ?? null;
    $time=$session ? ($online?$session['acctstarttime']:$session['acctstoptime']) : null;
    if($online && $time){
        $start=strtotime($time);$candidates=$rows;
        if($history && !empty($history['row']))$candidates[]=$history['row'];
        foreach($candidates as $row){
            $stamp=radius_log_timestamp($row['line']);
            if($row['type']==='conectados' && $stamp!==null && $start!==false && $stamp>=$start-30){
                $row['label']='Conexão atual';return $row;
            }
        }
    }
    $label=$online?'Conexão atual':'Última desconexão';
    $date=$time && strtotime($time)!==false ? date('d/m/Y H:i:s',strtotime($time)) : null;
    return array('type'=>$online?'conectados':'desconectados','label'=>$label,
        'login'=>$state['login'],'name'=>$state['name'],'client_url'=>$state['client_url'],
        'line'=>$date ? $date.' — '.($online?'Início da sessão atualmente aberta':'Encerramento da última sessão').' no MK-AUTH (radacct).' : 'Estado atual: '.($online?'Online':'Offline').'. Registro da '.($online?'conexão atual':'última desconexão').' indisponível no accounting.');
}
