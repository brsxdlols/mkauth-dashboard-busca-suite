<?php
// Reuse the authenticated report and its existing settings without duplicating queries.
$mkaOfflineIntegrated = true;
chdir(__DIR__.'/../rel_cli_offline');
require './index.php';
