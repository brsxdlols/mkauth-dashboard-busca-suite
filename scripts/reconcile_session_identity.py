"""Narrow, idempotent upgrade of known RouterOS accounting reconciliation code."""
def patch_session_identity(text):
    marker='// mka-session-identity-v1'
    if marker in text:
        return text.replace('AND COALESCE(acctupdatetime,acctstarttime) >= DATE_SUB(NOW(),INTERVAL 15 MINUTE)', 'AND COALESCE(acctupdatetime,acctstarttime) >= DATE_SUB(NOW(),INTERVAL 3 MINUTE)')
    old='        $stmt = $db->prepare("SELECT radacctid FROM radacct WHERE LOWER(TRIM(username)) = ? AND acctstoptime IS NULL ORDER BY radacctid DESC LIMIT 1");\n        $stmt->bind_param(\'s\', $login);'
    new='        // mka-session-identity-v1: old NAS/session records do not prove this session is online.\n        if ($session === \'\') { log_line("SKIP_MISSING_SESSION login=$login router=$router"); continue; }\n        $stmt = $db->prepare("SELECT radacctid FROM radacct WHERE LOWER(TRIM(username)) = ? AND nasipaddress = ? AND LOWER(REPLACE(acctsessionid,\'0x\',\'\')) = ? AND acctstoptime IS NULL AND COALESCE(acctupdatetime,acctstarttime) >= DATE_SUB(NOW(),INTERVAL 3 MINUTE) ORDER BY radacctid DESC LIMIT 1");\n        $stmt->bind_param(\'sss\', $login, $router, $session);'
    if text.count(old)!=1:
        raise ValueError('Unrecognized online-session lookup; original preserved')
    text=text.replace(old,new,1)
    text=text.replace('AND LOWER(acctsessionid) = ?',"AND LOWER(REPLACE(acctsessionid,'0x','')) = ?")
    fallback_template='        if (!$stopped) {\n            $stmt = $db->prepare("SELECT PROJECTION FROM radacct WHERE LOWER(TRIM(username)) = ? AND nasipaddress = ? ORDER BY radacctid DESC LIMIT 1");\n            $stmt->bind_param(\'ss\', $login, $router);\n            $stmt->execute();\n            $stopped = $stmt->get_result()->fetch_assoc();\n            $stmt->close();\n        }'
    count=0
    for projection in ("*","radacctid, acctsessionid, acctstarttime, acctstoptime"):
        fallback=fallback_template.replace("PROJECTION",projection)
        count+=text.count(fallback)
        text=text.replace(fallback,"        // Reopen only the same session; preserve other session history.")
    if count!=1:
        raise ValueError("Unrecognized historical-session fallback; original preserved")
    text=text.replace("SELECT radacctid, acctsessionid, acctstarttime, acctstoptime FROM radacct", "SELECT * FROM radacct")
    if "row_php_base64" not in text:
        anchor='                $stmt = $db->prepare("UPDATE radacct SET acctstoptime = NULL'
        if text.count(anchor)!=1:
            raise ValueError("Unrecognized session update; original preserved")
        text=text.replace(anchor,"                $rollbackFile = dirname($cfg['state_file']).'/reopen-rollback-'.date('Ymd').'.jsonl';\n                $rollback = json_encode(array('saved_at'=>date('c'),'operation'=>'reopen','row_php_base64'=>base64_encode(serialize($stopped))));\n                if ($rollback===false || file_put_contents($rollbackFile,$rollback.PHP_EOL,FILE_APPEND|LOCK_EX)===false) throw new RuntimeException('Reopen backup failed');\n                chmod($rollbackFile,0600);\n"+anchor,1)
    insert="            $unique = md5($router . '|' . $session . '|' . $login . '|' . $startTime);"
    if text.count(insert)!=1:
        raise ValueError('Unrecognized session insertion; original preserved')
    clock="            // Accounting timestamps use the database clock, without changing server timezone.\n            $clock = $db->query('SELECT DATE_SUB(NOW(),INTERVAL '.(int)$sessionSeconds.' SECOND) AS started')->fetch_assoc();\n            $startTime = $clock['started'];\n"
    return text.replace(insert,clock+insert,1)
