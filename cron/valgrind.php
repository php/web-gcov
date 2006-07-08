<?php

// stolen from cron.php
if ($argc != 4) die("only 3 arguments: [tmp] [out] [phpsrc]\n\n");

$tmpdir  = $argv[1];
$outdir  = $argv[2];
$phpdir  = $argv[3];
$workdir = dirname(__FILE__);

require $workdir.'/template.php';
// -----------------

$version = basename($phpdir, __FILE__); // todo: make this dynamic or based on the tags instead

$data  = file_get_contents("$tmpdir/php_test_valgrind.log");

//$write = html_header('Valgrind Reports');

$test_regex = '/Number of tests :\s*\d+\s+\d+\n'.
	       'Tests skipped   :\s*\d+\s*\(\s*\d+\.\d+%\) --------\n'.
	       'Tests warned    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)\n'.
	       'Tests failed    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)\n'.
	       'Tests passed    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)/';

preg_match($test_regex, $data, $m);

file_put_contents("$tmpdir/php_valgrind_summary.txt", $m[0]);

//file_put_contents("$tmpdir/valgrind_summary.inc", $m[0]);

preg_match_all('/FAIL (?P<title>.+) \[(?P<file>[^\]]+)\]/', $data, $failed, PREG_SET_ORDER);

$old_dir = '';

if (count($failed) == 0) {
	$write .= "<p>Congratulations! Currently there are no test failures!</p>\n";
} else {
	$write .= <<< HTML
<table border="1">
 <tr>
  <td>File</td>
  <td>Name</td>
 </tr>
HTML;
}

foreach ($failed as $test) {

	$file  = basename($test['file']);
	$title = $test['title'];
	$dir   = dirname($test['file']);
	$hash  = 'v' . md5($test['file']);

	if ($old_dir != $dir) {
		$old_dir = $dir;
		$write .= "<tr><td colspan='2' align='center'><b>$dir</b></td></tr>\n";
	}

	$write .= "<tr><td><a href='viewer.php?version=$version&func=valgrind&file=$hash'>$file</a></td><td>$title</td></tr>\n";

	// todo: added str_replace to individual report output
	
	$base = "$phpdir/".substr($test['file'],0,-4);
	$additional =
		"<h2>Script</h2><pre>\n" . highlight_file($base.'php', true).
		"\n</pre><h2>Report</h2><pre>\n".htmlentities(str_replace($phpdir,'',file_get_contents($base.'out')))."\n</pre>";

	// now create the test's page
	file_put_contents("$outdir/$hash.inc",
		//html_header("Valgrind Report: $file").
		'<?php $filename="'.basename($file).'"; ?>'
		.$additional.
		html_footer()
	);
}

if (count($failed))
	$write .= "</table>\n";

$write .= html_footer();

file_put_contents("$outdir/valgrind.inc", $write);
?>
