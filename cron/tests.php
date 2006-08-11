<?php

if(!defined('CRON_PHP'))
{
        echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	        exit;
}

// Ensure the write variable is empty before inserting content
$write = '';

$data  = file_get_contents("$tmpdir/php_test.log");
//$write = html_header('Test Failures');

// Grab the results according to failure or pass
preg_match_all('/(?P<status>FAIL|PASS) (?P<title>.+) \[(?P<file>[^\]]+)\]/', $data, $tests, PREG_SET_ORDER);

// todo: do a count instead of a bloated get all failures
preg_match_all('/FAIL (?P<title>.+) \[(?P<file>[^\]]+)\]/', $data, $failed, PREG_SET_ORDER);

$old_dir = '';

if (count($failed) < 1) {
	$write .= "<p>Congratulations! Currently there are no test failures!</p>\n";
} else {
	$totalnumfailures = count($failed);

	$write .= <<< HTML
<table border="1">
 <tr>
  <td>File</td>
  <td>Name</td>
 </tr>
HTML;
}


foreach ($tests as $test) {

	$file  = basename($test['file']);
	$status = $test['status'];
	$title = $test['title'];
	$dir   = dirname($test['file']);
	$hash  = md5($test['file']);

	$base = "$phpdir/".substr($test['file'],0,-4);

	$t = array();
	if(strtolower($status) == 'fail')
	{
		$t['status'] = 'fail';
		$t['file'] = $test['file'];
		$t['expected'] = file_get_contents($base.'exp')
			or $t['expected'] = 'N\A';
                $t['output'] = file_get_contents($base.'out')
			or $t['output'] = 'N\A';
		$t['difference'] = file_get_contents($base.'diff')
			or $t['difference'] = 'N\A';
	}
	else // status == 'PASS'
	{
		$t['status'] = 'pass';
		$t['file'] = $test['file'];
	}

	$xmlarray['tests'][] = $t;

	// This loop writes the content for a failed test
	if(strtolower($status) == 'fail')
	{

        	if ($old_dir != $dir) 
		{
			$old_dir = $dir;
			$write .= "<tr><td colspan='2' align='center'><b>$dir</b></td></tr>\n";
		}

		        $write .= "<tr><td><a href='viewer.php?version=$version&func=tests&file=$hash'>$file</a></td><td>$title</td></tr>\n";

			$additional =
 				"<h2>Script</h2><pre>\n" . highlight_file($base.'php', true).
				"\n</pre><h2>Expected</h2><pre>\n" . htmlspecialchars(file_get_contents($base.'exp')).
				"\n</pre><h2>Output</h2><pre>\n" .htmlspecialchars(str_replace($phpdir,'',file_get_contents($base.'out'))).
				"\n</pre><h2>Diff</h2><pre>\n".htmlspecialchars(str_replace($phpdir,'',file_get_contents($base.'diff')))."\n</pre>";

			// now create the test's page
			file_put_contents("$outdir/$hash.inc",
			//html_header("Test Failure: $file").
			'<?php $filename="'.htmlspecialchars(basename($file)).'"; ?>'.
			$additional.
			html_footer()
		);
	}
}

if (count($failed))
	$write .= "</table>\n";

$write .= html_footer();

file_put_contents("$outdir/tests.inc", $write);
?>
