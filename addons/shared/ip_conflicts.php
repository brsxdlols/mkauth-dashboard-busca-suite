<?php
function mka_ip_conflict_groups($rows) {
    $groups=array();
    foreach($rows as $row){
        $ip=trim((string)$row['ip']);$nas=trim((string)$row['nas']);$login=trim((string)$row['login']);
        if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)||$ip==='0.0.0.0'||$nas===''||$nas==='0.0.0.0'||$login==='')continue;
        $key=$nas.'|'.$ip;$user=strtolower($login);
        if(!isset($groups[$key]))$groups[$key]=array('ip'=>$ip,'nas'=>$nas,'clients'=>array());
        $groups[$key]['clients'][$user]=array('login'=>$login,'name'=>(string)$row['name'],'additional'=>(bool)$row['additional'],'fixed_ip'=>(string)$row['fixed_ip'],'fixed_matches'=>trim((string)$row['fixed_ip'])===$ip);
    }
    $result=array();ksort($groups);
    foreach($groups as $group){if(count($group['clients'])<2)continue;ksort($group['clients']);$group['clients']=array_values($group['clients']);$result[]=$group;}
    return $result;
}
function mka_ip_conflicts($db,$scope){
    // An unclosed historical row is not evidence of a current collision.
    // Allow five missed 3-minute interim updates, including newly started sessions.
    // The same address on different NASes is not a collision.
    // Keep both branches scoped to the owning client, including additional logins.
    $sql="SELECT DISTINCT r.framedipaddress ip,r.nasipaddress nas,c.login login,c.nome name,c.ip fixed_ip,0 additional FROM radacct r INNER JOIN sis_cliente c ON c.login=r.username WHERE r.acctstoptime IS NULL AND COALESCE(r.acctupdatetime,r.acctstarttime)>=DATE_SUB(NOW(),INTERVAL 15 MINUTE) AND ($scope)
    UNION ALL SELECT DISTINCT r.framedipaddress ip,r.nasipaddress nas,a.username login,a.nome name,a.ip fixed_ip,1 additional FROM radacct r INNER JOIN sis_adicional a ON a.username=r.username INNER JOIN sis_cliente c ON c.login=a.login WHERE r.acctstoptime IS NULL AND COALESCE(r.acctupdatetime,r.acctstarttime)>=DATE_SUB(NOW(),INTERVAL 15 MINUTE) AND ($scope)";
    $q=$db->query($sql);return mka_ip_conflict_groups($q->fetch_all(MYSQLI_ASSOC));
}
