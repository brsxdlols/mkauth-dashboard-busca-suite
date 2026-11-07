<?php
require $argv[1].'/finance_period.php';
$cases = array(
    array('2026-11-06','week','2026-11-01','2026-11-08','2026-11-07'),
    array('2026-11-01','week','2026-11-01','2026-11-08','2026-11-07'),
    array('2026-11-07','week','2026-11-01','2026-11-08','2026-11-07'),
    array('2027-01-01','week','2026-12-27','2027-01-03','2027-01-02'),
    array('2026-11-06','fortnight','2026-11-06','2026-11-21','2026-11-20'),
    array('2026-12-25','fortnight','2026-12-25','2027-01-09','2027-01-08'),
    array('2024-02-29','month','2024-02-01','2024-03-01','2024-02-29'),
    array('2026-12-31','month','2026-12-01','2027-01-01','2026-12-31'),
    array('2024-02-29','day','2024-02-29','2024-03-01','2024-02-29')
);
foreach ($cases as $c) {
    $r = mka_finance_period($c[0],$c[1]);
    if (array_values($r)!==array_slice($c,2)) throw new RuntimeException('Calendar boundary mismatch');
}
try { mka_finance_period('2026-11-06','arbitrary'); throw new RuntimeException('Invalid period accepted'); }
catch (InvalidArgumentException $e) {}
echo "PASS Sunday/Saturday, year rollover, leap day and invalid period\n";
