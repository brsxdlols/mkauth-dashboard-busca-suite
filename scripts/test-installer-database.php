<?php
if (PHP_SAPI!=='cli') exit(1);
require __DIR__.'/installer-database.php';
function installer_db_check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
$saved=getenv('MYSQL_HOST');putenv('MYSQL_HOST');$calls=array();$pauses=0;$result=new stdClass();
try {
    $connector=function($host)use(&$calls,$result){$calls[]=$host;if($host==='127.0.0.1')throw new RuntimeException('refused',2002);return $result;};
    installer_db_check(mka_installer_database_connect($connector,function()use(&$pauses){$pauses++;})===$result && $calls===array('127.0.0.1','localhost') && $pauses===1,'default local connection falls back to socket after refusal');
    putenv('MYSQL_HOST=database.example');$calls=array();
    try {mka_installer_database_connect(function($host)use(&$calls){$calls[]=$host;throw new RuntimeException('refused',2002);},function(){});throw new LogicException('Expected failure');}
    catch(RuntimeException $error){installer_db_check($calls===array('database.example','database.example','database.example') && strpos($error->getMessage(),'codigo 2002')!==false,'configured host is retried without redirecting to local database');}
    putenv('MYSQL_HOST');$calls=array();
    try {mka_installer_database_connect(function($host)use(&$calls){$calls[]=$host;throw new RuntimeException('denied',1045);},function(){});throw new LogicException('Expected failure');}
    catch(RuntimeException $error){installer_db_check(count($calls)===1,'authentication errors do not trigger fallback');}
    $calls=array();
    installer_db_check(mka_installer_database_connect(function($host)use(&$calls,$result){$calls[]=$host;if(count($calls)<3)throw new RuntimeException('restart',2003);return $result;},function(){})===$result && count($calls)===3,'temporary restart can recover on a bounded retry');
} finally {if($saved===false)putenv('MYSQL_HOST');else putenv('MYSQL_HOST='.$saved);}
