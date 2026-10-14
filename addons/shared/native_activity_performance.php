<?php
// Optimize only the native read-only activity feed. Authentication remains native.
function mka_activity_recent_sql($sql)
{
    if (!preg_match('/^SELECT\s+\*\s+FROM\s+vtab_ativ\s+WHERE\s+(.+?)\s+ORDER\s+BY\s+data\s+DESC\s+LIMIT\s+(\d+)\s*,\s*(\d+)\s*$/is', trim($sql), $m)) return $sql;
    $offset=(int)$m[2];$count=(int)$m[3];$limit=$offset+$count;
    if ($count<1 || $count>1000 || $limit>10000) return $sql;
    // Preserve the native predicate, projection, UNION DISTINCT and pagination.
    // Each branch has unique IDs; its first offset+count rows contain every
    // candidate for the final page. LIMIT avoids materializing the full UNION.
    $where=$m[1];
    return "SELECT * FROM ((SELECT id,registro,data,login,ip,tipo,operacao FROM sis_ativ WHERE $where ORDER BY data DESC LIMIT $limit) UNION (SELECT id,registro,STR_TO_DATE(data,'%d/%m/%Y %H:%i:%s') AS data,login,'0.0.0.0' AS ip,tipo,operacao FROM sis_logs WHERE $where ORDER BY data DESC LIMIT $limit)) AS recent_activity ORDER BY data DESC LIMIT $offset,$count";
}

function mka_activity_patch_body($body)
{
    if (!preg_match('/(\$[A-Za-z_]\w*)\s*=\s*"SELECT\s+\*\s+FROM\s+vtab_ativ\b/s', $body, $m)) throw new RuntimeException('Unsupported native activity query.');
    $query=$m[1];
    $pattern='/\$[A-Za-z_]\w*\s*=\s*\$[A-Za-z_]\w*\(\s*\$[A-Za-z_]\w*\s*,\s*'.preg_quote($query,'/').'\s*\)/';
    if (preg_match_all($pattern,$body,$calls,PREG_OFFSET_CAPTURE)!==1) throw new RuntimeException('Unsupported native activity call.');
    $position=$calls[0][0][1];
    if (strpos(substr($body,$position),'$_SESSION')!==false) throw new RuntimeException('Native activity still uses the session after its query.');
    $insert=$query.'=mka_activity_recent_sql('.$query.');if(session_status()===PHP_SESSION_ACTIVE)session_write_close();';
    return substr($body,0,$position).$insert.substr($body,$position);
}

function mka_activity_decoder_source($source,$original)
{
    if (preg_match_all('/\beval\s*\(/',$source,$matches,PREG_OFFSET_CAPTURE)!==2) throw new RuntimeException('Unsupported native activity loader.');
    $position=$matches[0][1][1];
    $tail=substr($source,$position);
    $end=strpos($tail,'?>');
    if ($end===false || !preg_match('/^eval\s*\(([\s\S]*)\);\s*return\s*;\s*$/',substr($tail,0,$end),$m)) throw new RuntimeException('Unsupported native activity payload.');
    $expression=preg_replace('/\b__cFILE[a-f0-9]+__\b/',var_export($original,true),$m[1],-1,$replacements);
    if ($replacements<1) throw new RuntimeException('Native activity source reference missing.');
    // Only execute the vendor codec. Never execute authentication or SQL here.
    // Both ciphertext and its key must read the unmodified original file.
    return substr($source,0,$position).'$mkaActivityDecoded=('.$expression.');';
}
