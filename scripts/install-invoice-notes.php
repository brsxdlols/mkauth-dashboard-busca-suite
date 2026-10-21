<?php
require __DIR__.'/installer-database.php';
require_once __DIR__.'/../addons/shared/invoice_financial_notes.php';
$db=mka_installer_database_connect();mka_invoice_notes_schema($db);
echo "Notas financeiras por fatura: estrutura pronta.\n";
