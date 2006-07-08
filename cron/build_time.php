<?php

// script to get the build time of the php version

$filename = '../tmp/PHP_5_2/php_test_valgrind.log';
$fh = fopen($filename, 'r');
$content = fread($fh, filesize($filename));
fclose($fh);

//$buildtime = preg_match('/Time taken[ ]? :[ ]?(\d*)[ ]?seconds/', $content);

preg_match('/Time taken[ ]*:[ ]* ([0-9]+) seconds/', $content, $matches);

//echo $buildtime.' seconds'."\n";

print_r($matches);
echo "\n";

if(is_array($matches))
{
	echo $matches[1]."\n";
}
