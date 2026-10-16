<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';
require __DIR__.'/../addons/shared/support_attendance.php';
require __DIR__.'/../addons/shared/ticket_schedule.php';
require __DIR__.'/../addons/shared/ticket_notes.php';
require __DIR__.'/../addons/shared/priority_colors.php';
require_once __DIR__.'/../addons/shared/agenda_settings.php';
try{$db=mka_installer_database_connect();mka_attendance_schema($db);mka_ticket_notes_schema($db);mka_ticket_schedule_schema($db);mka_priority_schema($db);mka_agenda_schema($db);$db->close();echo "[atendimentos] Historico de atendimentos e agendamentos disponivel.\n";}
catch(Throwable $error){fwrite(STDERR,"[erro] Historico de atendimentos nao instalado.\n");exit(1);}
