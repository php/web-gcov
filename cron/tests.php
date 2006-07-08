<?php

// Ensure the write variable is empty before inserting content
$write = '';

$data  = file_get_contents("$tmpdir/php_test.log");
//$write = html_header('Test Failures');

$test_regex = '/Number of tests :\s*\d+\s+\d+\n'.
	       'Tests skipped   :\s*\d+\s*\(\s*\d+\.\d+%\) --------\n'.
	       'Tests warned    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)\n'.
	       'Tests failed    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)\n'.
	       'Tests passed    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)/';

preg_match($test_regex, $data, $m);
$test_summary = $m[0];


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
	$hash  = md5($test['file']);

	if ($old_dir != $dir) {
		$old_dir = $dir;
		$write .= "<tr><td colspan='2' align='center'><b>$dir</b></td></tr>\n";
	}

	$write .= "<tr><td><a href='viewer.php?version=$version&func=tests&file=$hash'>$file</a></td><td>$title</td></tr>\n";

	// todo: did str_replace of phpdir, is this a good idea?

	$base = "$phpdir/".substr($test['file'],0,-4);
	$additional =
 		"<h2>Script</h2><pre>\n" . highlight_file($base.'php', true).
		"\n</pre><h2>Expected</h2><pre>\n" . htmlentities(file_get_contents($base.'exp')).
		"\n</pre><h2>Output</h2><pre>\n" .htmlentities(str_replace($phpdir,'',file_get_contents($base.'out'))).
		"\n</pre><h2>Diff</h2><pre>\n".htmlentities(str_replace($phpdir,'',file_get_contents($base.'diff')))."\n</pre>";

	// now create the test's page
	file_put_contents("$outdir/$hash.inc",
		//html_header("Test Failure: $file").
		'<?php $filename="'.basename($file).'"; ?>'.
		$additional.
		html_footer()
	);
}

if (count($failed))
	$write .= "</table>\n";

$write .= html_footer();

file_put_contents("$outdir/tests.inc", $write);
?>
