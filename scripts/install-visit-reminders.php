<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';require __DIR__.'/../addons/shared/visit_reminders.php';
try{$db=mka_installer_database_connect();mka_visit_reminders_schema($db);echo "[visitas] Lembretes persistentes de chamados e instalacoes disponiveis.\n";}
catch(Throwable $e){fwrite(STDERR,"[erro] Nao foi possivel instalar os lembretes de visitas.\n");exit(1);}
