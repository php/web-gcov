<?php

if ($argc != 5) die("only 4 arguments: [tmp] [out] [phpsrc] [makestatus]\n\n");

define('CRON_PHP',true);

$tmpdir  = $argv[1];
$outdir  = $argv[2];
$phpdir  = $argv[3];
$makestatus = $argv[4]; // make status from bash script (fail or pass)
$workdir = dirname(__FILE__);

$makedata = '';

// Set up variables that apply to all scripts
$version = basename($phpdir, __FILE__); // todo: make this dynamic or based on the tags instead

require $workdir.'/template.php';

//process stuff
require $workdir.'/compile_results.php';   // make compile errors/warnings file

// If the make passes
if(trim($makestatus) == 'pass')
{
	require $workdir.'/tests.php';      
	#require $workdir.'/run_tests.php';
	require $workdir.'/system.php';     

	$makedata = 'pass';
}
else
{
	$makedata = 'fail';
}

$fh = fopen($outdir.DIRECTORY_SEPARATOR.'last_make_status.inc', 'w');
#todo: if write fails we need to do something about it
fwrite($fh, $makedata);
fclose($fh);

//require $workdir.'/make-index.php'; // make the index file
?>
