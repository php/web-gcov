<?php

// script to get the build time of the php version

$content = file_get_contents($tmpdir.DIRECTORY_SEPARATOR.'php_test.log');

// Grab the build time
preg_match('/Time taken[ ]*:[ ]* ([0-9]+) seconds/', $content, $matches);

if(is_array($matches))
{
	//echo $matches[1]."\n";
	$build_time = $matches[1];
}
else
{
	// This setting will apply when the build time could not be calculated
	// -1 may be a good choice since it could indicate an error in calc
	$build_time = -1;
}

// Grab the code coverage rate
preg_match('/Overall coverage rate: .+ \((.+)%\)/', $content, $matches);

if(is_array($matches))
{
	$codecoverage_percent = $matches[1];
}
else
{
	// an error occurred in reading coverage rate
}
