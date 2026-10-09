<?php
// $groups is the existing server-generated access predicate, using alias c.
// One key per normalized registered login, including children of active clients.
function mka_online_sql($groups = '') {
    return "SELECT DISTINCT active.login_key AS username FROM
      (SELECT DISTINCT LOWER(TRIM(username)) login_key FROM radacct
       WHERE acctstoptime IS NULL AND TRIM(username)<>'') active
      INNER JOIN (
        SELECT LOWER(TRIM(c.login)) login_key FROM sis_cliente c
        WHERE $groups c.cli_ativado='s' AND TRIM(c.login)<>''
        UNION
        SELECT LOWER(TRIM(a.username)) login_key FROM sis_adicional a
        INNER JOIN sis_cliente c ON LOWER(TRIM(a.login))=LOWER(TRIM(c.login))
        WHERE $groups c.cli_ativado='s' AND TRIM(a.username)<>''
      ) registered ON registered.login_key=active.login_key";
}
function mka_online_family_sql() {
    // Search returns customer cards, with their additional logins underneath.
    return "EXISTS (SELECT 1 FROM radacct r WHERE r.acctstoptime IS NULL
      AND TRIM(r.username)<>'' AND (LOWER(TRIM(r.username))=LOWER(TRIM(c.login))
      OR EXISTS (SELECT 1 FROM sis_adicional a
        WHERE LOWER(TRIM(a.login))=LOWER(TRIM(c.login))
        AND LOWER(TRIM(a.username))=LOWER(TRIM(r.username)))))";
}
function mka_online_ramal_sql($groups = '') {
    $online=mka_online_sql($groups);
    return "SELECT registered.ramal, COUNT(DISTINCT registered.login_key) total_clientes,
      COUNT(DISTINCT active.username) total_online FROM (
        SELECT LOWER(TRIM(c.login)) login_key,c.ramal FROM sis_cliente c
        WHERE $groups c.cli_ativado='s' AND TRIM(c.login)<>''
        UNION SELECT LOWER(TRIM(a.username)),c.ramal FROM sis_adicional a
        INNER JOIN sis_cliente c ON LOWER(TRIM(a.login))=LOWER(TRIM(c.login))
        WHERE $groups c.cli_ativado='s' AND TRIM(a.username)<>''
      ) registered LEFT JOIN ($online) active ON active.username=registered.login_key
      GROUP BY registered.ramal";
}
