"""Known historical query shape; no router or database access."""
from reconcile_session_identity import patch_session_identity
old = '''
        $stmt = $db->prepare("SELECT radacctid FROM radacct FORCE INDEX (acctstoptime) WHERE LOWER(TRIM(username)) = ? AND acctstoptime IS NULL ORDER BY radacctid DESC LIMIT 1");
        $stmt->bind_param('s', $login);
        if (!$stopped) {
            $stmt = $db->prepare("SELECT radacctid, acctsessionid, acctstarttime, acctstoptime FROM radacct WHERE LOWER(TRIM(username)) = ? AND nasipaddress = ? ORDER BY radacctid DESC LIMIT 1");
            $stmt->bind_param('ss', $login, $router);
            $stmt->execute();
            $stopped = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
                $stmt = $db->prepare("UPDATE radacct SET acctstoptime = NULL");
            $unique = md5($router . '|' . $session . '|' . $login . '|' . $startTime);
'''

new=patch_session_identity(old)
assert 'mka-session-identity-v1' in new and 'AND nasipaddress = ?' in new
assert patch_session_identity(new)==new
try:patch_session_identity(old.replace('ORDER BY radacctid DESC LIMIT 1','ORDER BY radacctid ASC LIMIT 2'))
except ValueError:pass
else:raise AssertionError('Unknown lookup must be preserved')
print('PASS known index hint upgrade, repeat and unknown source refusal')
