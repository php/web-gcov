<?php

// script to get the build time of the php version

$content = file_get_contents($tmpdir.DIRECTORY_SEPARATOR.'php_test.log');

// Grab the build time
if(preg_match('/Time taken\s*:\s*(\d+)\s*seconds/', $content, $matches))
{
	$build_time = $matches[1];
}
else
{
	// This setting will apply when the build time could not be located
	$build_time = -1;
}

// Grab the code coverage rate
if(preg_match('/Overall coverage rate: .+ \((.+)%\)/', $content, $matches))
{
	$codecoverage_percent = $matches[1];
}
else
{
	// an error occurred in reading coverage rate
	$codecoverage_percent = -1;
}
