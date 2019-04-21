<?php
/*
  +----------------------------------------------------------------------+
  | PHP QA GCOV Website                                                  |
  +----------------------------------------------------------------------+
  | Copyright (c) The PHP Group                                          |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Daniel Pronych <pronych@php.net>                             |
  |         Nuno Lopes <nlopess@php.net>                                 |
  +----------------------------------------------------------------------+
*/

// Compile Results generation script

$data = file_get_contents("$tmpdir/php_build.log");

// Check if build file was readable, and if not, notify the user
if ($data === false) {
	die("compile_results.php: it appears the build process has succeeded but the PHP build log file at $tmpdir/php_build.log could not be opened for processing.\n");
}


// Regular expression to select the error and warning information
// tuned for gcc 3.4, 4.0 and 4.1
$gcc_regex = '/^((.+)(\(\.[a-z]+\+0x[[:xdigit:]]+\))?: In function [`\'](\w+)\':\s+)?'.
	'((?(1)(?(3)[^:\n]+|\2)|[^:\n]+)):(\d+)(?::\d+)?: (?:(error|note|warning):\s+)?(.+)'.
	str_repeat('(?:\s+\5:(\d+)(?::\d+)?: (?:(error|note|warning):\s+)?(.+))?', 99). // capture up to 100 errors
	'/mS';

preg_match_all($gcc_regex, $data, $data, PREG_SET_ORDER);

$compile_results = array();
$mail_error = array();

$phpdir_len = strlen($phpdir);
if ($phpdir[$phpdir_len-1] != '/') {
	$phpdir_strip_len = $phpdir_len + 1; // remove the trailing slash
}

foreach ($data as $error) {

	$file     = $error[5];
	$function = $error[4] ? $error[4] : '(top level)';

	// Remove the phpdir portion from the file path if it occurs
	if (substr($file, 0, $phpdir_len) == $phpdir) {
		$filepath = substr($file, $phpdir_strip_len);
	} else {
		$filepath = $file;
	}

	// the real data starts at 6th element
	for ($i = 6; isset($error[$i]); $i += 3) {
		$line = $error[$i];
		$type = $error[$i+1] ? $error[$i+1] : 'error'; // warning or error (default)
		$msg  = $error[$i+2];

		// skip notes for now. they should be associated with a warning/error.
		if ($type === 'note')
			continue;

		if ($type === 'error') {
			// Only send to list error messages
			$mail_error[$filepath][] = array($function, $line, $msg);
			++$totalnumerrors;
		} else {
			++$totalnumwarnings;
		}

		$compile_results[$filepath][] = array($function, $line, $type, $msg);
	}
}

// sort by filename
ksort($compile_results);

file_put_contents("$outdir/compile_results.inc", serialize($compile_results));

if (!empty($mail_error)) {
	$mail_message = '';

	foreach ($mail_error as $path => $fileentry) {
		$mail_message .= sprintf(">>> File: %s\n", $path);
		
		foreach ($fileentry as $entry) {
			$mail_message .= sprintf("Function: %s:%d %s (%s)\n",
				$entry[0],
				$entry[1],
				make_lxr_link($phpver, $path, $entry[1]),
				$entry[2]);
		}
		$mail_message .= "\n\n";
	}
	
	$headers  = "MIME-Version: 1.0\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\n";
	$headers .= "From: PHP GCOV <php-qa@lists.php.net>";

	mail('php-qa@lists.php.net',
		sprintf('[PHP-GCOV] Build error - %s (%s)', $phpver, date('Y-m-d')),
		$mail_message,
		$headers,
		'-f noreply@php.net');
}
