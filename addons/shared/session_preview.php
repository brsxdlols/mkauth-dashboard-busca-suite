<?php
function mka_session_preview($payload) {
    if (($_SERVER['REQUEST_METHOD'] ?? '')!=='GET' || empty($payload['enabled'])) return $payload;
    $user=(string)($_SESSION['MKA_Usuario'] ?? $_SESSION['MM_Usuario'] ?? '');
    if ($user==='') return $payload;
    $path=__DIR__.'/session-previews/'.hash('sha256',$user).'.json';
    if (!is_file($path)) return $payload;
    $event=json_decode((string)file_get_contents($path),true);
    if (is_array($event) && !empty($event['demo']) && ($event['expires'] ?? 0)>time()) {
        if (!isset($payload['events']) || !is_array($payload['events'])) $payload['events']=array();
        $payload['events'][]=$event;
    }
    return $payload;
}
