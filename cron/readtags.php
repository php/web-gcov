<?php

$filename = 'tags.inc';
$fh = fopen($filename, 'r');
$content = fread($fh, filesize($filename));
fclose($fh);

//$tagarray = preg_split('/((\S)?\s(\S)?\s(\S)?\s(\S)?[\n])?/', $content);

$content = trim($content);

$elements = explode("\n", $content);
$tagarray = array();
foreach($elements as $element) {
	$elementname = explode(' ', $element);
	$tagarray[$elementname[0]] = array_splice($elementname, 1);
}

print_r($tagarray);
//var_dump(
