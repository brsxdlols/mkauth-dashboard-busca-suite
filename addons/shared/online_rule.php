<?php
// $groups is the existing server-generated access predicate, using alias c.
// One key per normalized registered login, including children of active clients.
// Unclosed rows require recent accounting (five 3-minute interim intervals).
function mka_online_sql($groups = '') {
    return "SELECT DISTINCT active.login_key AS username FROM
      (SELECT DISTINCT LOWER(TRIM(username)) login_key FROM radacct
       WHERE acctstoptime IS NULL
       AND COALESCE(acctupdatetime,acctstarttime)>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)
       AND TRIM(username)<>'') active
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
    // Materialize the small current-login set once, instead of scanning accounting
    // again for every customer and every additional login in online/offline search.
    $active="SELECT DISTINCT LOWER(TRIM(username)) login_key FROM radacct
      WHERE acctstoptime IS NULL
      AND COALESCE(acctupdatetime,acctstarttime)>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)
      AND TRIM(username)<>''";
    return "LOWER(TRIM(c.login)) IN (SELECT owner_key FROM (
      SELECT login_key owner_key FROM ($active) current_main
      UNION
      SELECT LOWER(TRIM(a.login)) owner_key FROM sis_adicional a
      INNER JOIN ($active) current_additional
        ON LOWER(TRIM(a.username))=current_additional.login_key
    ) current_families)";
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
