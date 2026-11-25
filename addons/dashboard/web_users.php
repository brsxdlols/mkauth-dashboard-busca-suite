<?php
// Compatibility route: both views use the native session cards.
require __DIR__.'/../shared/notification_bootstrap.php';
header('Cache-Control: no-store, private');
$extension=is_file(dirname(__DIR__,2).'/sessoes.hhvm')?'.hhvm':'.php';
header('Location: /admin/sessoes'.$extension.'?scope=all&v=20261125-5');
exit;
