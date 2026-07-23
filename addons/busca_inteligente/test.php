<?php

/*
Taken from http://www.php.net/manual/de/function.eval.php#59862
Directions:
1. Save this snippet as decrypt.php
2. Save encoded PHP code in coded.txt
3. Create a blank file called decoded.txt (from shell do CHMOD 0666 decoded.txt)
4. Execute this script (visit decrypt.php in a web browser or do php decrypt.php in the shell)
5. Open decoded.txt, the PHP should be decrypted: https://medium.com/@danilosapad/decoding-eval-gzinflate-base64-decode-a4eb07997b87
*/
echo "\nDECODE nested eval(gzinflate()) by DEBO Jurgen <jurgen@person.be>\n\n";
echo "1. Reading coded.txt\n";
$fp1 = fopen("coded.txt", "r");
$contents = fread($fp1, filesize("coded.txt"));
fclose($fp1);
echo "2. Decoding\n";
echo "<pre>", print_r($contents), "</pre>";
while (preg_match("/eval/", $contents)) {
    $contents = preg_replace("/< \?|\?>/", "", $contents);
    eval(preg_replace("/@eval/", "\$contents=", $contents));
}
echo "3. Writing decoded.txt\n";
$fp2 = fopen("decoded.txt", "w");
fwrite($fp2, trim($contents));
fclose($fp2);
