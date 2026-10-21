<?php
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/installer-database.php';
$admin=rtrim($argv[1]??'/opt/mk-auth/admin','/');$backup=$argv[2]??null;
require $admin.'/addons/shared/search_performance.php';
if(strpos((string)file_get_contents($admin.'/addons/busca_inteligente/index.php'),"define('MKA_SEARCH_PREEXISTING_DIRECT',true)")!==false)define('MKA_SEARCH_PREEXISTING_DIRECT',true);
try{$db=mka_installer_database_connect();if($backup){if(!is_dir($backup))mkdir($backup,0700,true);file_put_contents($backup.'/flag.before.json',mka_perf_flag($db));chmod($backup.'/flag.before.json',0600);}$result=mka_perf_revalidate_approved($db);echo ($result['status']==='not_approved'?'[busca] Nenhuma otimizacao anterior aprovada para revalidar. A analise automatica sera executada na proxima etapa.':'[busca] Revalidacao da otimizacao anterior: '.$result['status']).PHP_EOL;}catch(Throwable $e){fwrite(STDERR,"[aviso] Revalidacao da busca nao concluida; execute a analise de desempenho.\n");exit(1);}
