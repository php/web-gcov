<?php

// stolen from cron.php
if ($argc != 4) die("only 3 arguments: [tmp] [out] [phpsrc]\n\n");

$tmpdir  = $argv[1];
$outdir  = $argv[2];
$phpdir  = $argv[3];
$workdir = dirname(__FILE__);

$unicode = false;		// assume unicode is disabled

$write = '';

$valgrind = array();

require $workdir.'/template.php';
// -----------------

 // todo: make this dynamic or based on the tags instead
$version = basename($phpdir, __FILE__); 

// fix filename after the others are fixed
$data  = file_get_contents("$tmpdir/php_test.log");

//$write = html_header('Valgrind Reports');

// Check if unicode testing is enabled (is this sufficient for PHP > 6?)
if(preg_match('/UNICODE[ ]*:[ ]*ON[ ]?/', $data))
{
	// Regular expression to match leaks if unicode is enabled
	$leak_re = '/(?P<teststatus>FAIL|LEAK):(?P<testtype>[a-z|A-Z]) (?P<title>.+) \[(?P<file>[^\]]+)\]/';
	$unicode = true;
	echo 'unicode: on'."\n";
}
else
{
	// /LEAK (?P<title>.+) \[(?P<file>[^\]]+)\]/
	$leak_re = '/(?P<teststatus>FAIL|LEAK) (?P<title>.+) \[(?P<file>[^\]]+)\]/';
	echo 'unicode: off'."\n";
}

$test_regex = '/Number of tests :\s*\d+\s+\d+\n'.
	       'Tests skipped   :\s*\d+\s*\(\s*\d+\.\d+%\) --------\n'.
	       'Tests warned    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)\n'.
	       'Tests failed    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)\n'.
	       'Tests passed    :\s*\d+\s*\(\s*\d+\.\d+%\) \(\s*\d+\.\d+%\)/';

preg_match($test_regex, $data, $m);

file_put_contents("$tmpdir/php_valgrind_summary.txt", $m[0]);

//file_put_contents("$tmpdir/valgrind_summary.inc", $m[0]);


// todo: is this FAIL or LEAK?  (was fail previously)
//preg_match_all('/LEAK:(\s) (?P<title>.+) \[(?P<file>[^\]]+)\]/', $data, $failed, PREG_SET_ORDER);

// todo: verify PHP 6 is used and that unicode is specified

preg_match_all($leak_re, $data, $leaks, PREG_SET_ORDER);

//print_r($leaks);

$old_dir = '';

if (count($leaks) == 0) {
	$write .= "<p>Congratulations! Currently there are no test failures!</p>\n";
} else {
	$totalnumleaks = count($leaks);

	$write .= '<table border="1">'."\n";
/*
	$write .= <<< HTML
<table border="1">
 <tr>
  <td>File</td>
  <td>Test Type</td>
  <td>Name</td>
 </tr>
HTML;

*/
}


foreach ($leaks as $test) 
{

	$base = "$phpdir/".substr($test['file'],0,-4);

	$file  = basename($test['file']);

	$report_file = ''; // Used internally to determine unicode or native filename

	$t = array();

	// If test is a leak perform all operations
	if(strtolower($test['teststatus']) == 'leak')
	{
		// If test mode is not unicode it is native
		if((isset($test['testtype'])) && (strtolower($test['testtype']) == 'u'))
		{
			$testtype = 'Unicode';
			$report_file = $base.'u.mem';
		}
		else
		{
			$testtype = 'Native';
			$report_file = $base.'mem';
		}

		$title = $test['title'];
		$dir   = dirname($test['file']);
		$hash  = 'v' . md5($test['file']);

		if ($old_dir != $dir) 
		{
			$old_dir = $dir;
			// todo: track if there are 2 or 3 columns
			$write .= '<tr>'."\n".'<td colspan="3" align="center">'
				."<b>$dir</b></td>\n</tr>\n";

			$write .= "<tr>\n<td>File</td>\n<td>Test Type</td>\n<td>Name</td>\n</tr>\n";
		}
	
		// todo: if testtype is not set don't output it
		$write .= "<tr><td><a href='viewer.php?version=$version&func=valgrind&file=$hash'>$file</a></td><td>$testtype</td><td>$title</td></tr>\n";

		// todo: added str_replace to individual report output
	
		$base = "$phpdir/".substr($test['file'],0,-4);

		$script = highlight_file($base.'php', true)
			or $script = 'Script contents were not available.';
	
		$report = file_get_contents($report_file);

		if($report === false)
			$report = 'Memory leak file contents were not available.';
		else
			$report = str_replace($phpdir,'',$report);

		$additional =
			"<h2>Script</h2><pre>\n" .$script.
			"\n</pre><h2>Report</h2><pre>\n".$report."\n</pre>";
		// .out replaced by .mem
		// now create the test's page
		file_put_contents("$outdir/$hash.inc",
			//html_header("Valgrind Report: $file").
			'<?php $filename="'.basename($file).'"; ?>'
			.$additional.
			html_footer()
		);

		$t['file'] = $test['file'];
		$t['teststatus'] = $test['teststatus'];
		$t['script'] = $script;
		$t['report'] = $report;
	}
	else	// For valgrind failure we only know file and status
	{
		// todo: fix this if more information can be gathered
                $t['file'] = $test['file'];
		$t['teststatus'] = $test['teststatus'];
	}

	$valgrind[] = $t;

}

if (count($leaks))
	$write .= "</table>\n";

$write .= html_footer();

file_put_contents("$outdir/valgrind.inc", $write);

// Serialize testing

// This prevents writing the file if there is no content
if(count($valgrind) > 0)
{
	ob_start();

	echo serialize($valgrind);

	$contents = ob_get_clean();

	file_put_contents("$outdir/valgrind.txt", $contents);
}

?>
