<?php

// script to get the build time of the php version

$content = file_get_contents($tmpdir.DIRECTORY_SEPARATOR.'php_test.log');

//$buildtime = preg_match('/Time taken[ ]? :[ ]?(\d*)[ ]?seconds/', $content);

preg_match('/Time taken[ ]*:[ ]* ([0-9]+) seconds/', $content, $matches);

//echo $buildtime.' seconds'."\n";

//print_r($matches);
//echo "\n";

if(is_array($matches))
{
	//echo $matches[1]."\n";
	$build_time = $matches[1];
}
