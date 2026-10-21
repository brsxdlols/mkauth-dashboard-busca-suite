<?php
require $argv[1];$file=tempnam(sys_get_temp_dir(),'radius-reader-test-');
try{
 if(mka_radius_tail($file,100)!==array())throw new RuntimeException('Empty log should be successful.');
 file_put_contents($file,"first\nsecond\nthird\n");if(mka_radius_tail($file,2)!==array('second','third'))throw new RuntimeException('Tail with final newline.');
 file_put_contents($file,"first\r\nsecond\r\nthird");if(mka_radius_tail($file,2)!==array('second','third'))throw new RuntimeException('CRLF tail without final newline.');
 $lines=array();for($i=0;$i<3000;$i++)$lines[]=$i.' '.str_repeat('x',60);file_put_contents($file,implode("\n",$lines));$tail=mka_radius_tail($file,100);if(count($tail)!==100||$tail[0]!==$lines[2900]||$tail[99]!==$lines[2999])throw new RuntimeException('Chunk boundary tail.');
 echo "Radius reader: empty log, final newline, CRLF and chunk boundaries OK\n";
}finally{unlink($file);}
