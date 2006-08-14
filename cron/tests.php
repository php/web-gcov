<?php


// data: contains the contents of $tmpdir/php_test.log
// unicode: true if unicode is included

if(!defined('CRON_PHP'))
{
        echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	        exit;
}

// Ensure the write variable is empty before inserting content
$write = '';

// Regular expression used to find a single failure
$fail_re = '/FAIL(:(?P<testtype>[a-z|A-Z]))?/';

// Regular expression to find all tests with a pass or failure
$tests_re = '/(?P<status>FAIL|PASS)(:(?P<testtype>[a-z|A-Z]))? (?P<title>.+) \[(?P<file>[^\]]+)\]/';

// Grab all tests that match the tests regular expression
preg_match_all($tests_re, $data, $tests, PREG_SET_ORDER);

$old_dir = '';

// A single failure is enough to verify that a failure occurred
if (preg_match($fail_re, $data) < 1) 
{
	$write .= "<p>Congratulations! Currently there are no test failures!</p>\n";
} else {
	$write .= <<< HTML
<table border="1">
 <tr>
  <td>File</td>
  <td>Test Type</td>
  <td>Name</td>
 </tr>
HTML;
}

foreach ($tests as $test) 
{

	$dir   = dirname($test['file']);
	$file  = basename($test['file']);
	$status = $test['status']; // FAIL or PASS
	$title = $test['title'];

	// Note: that the following period is preserved
	$base = "$phpdir/".substr($test['file'],0,-4);

	$newtest = array();
	$newtest['script'] = file_get_contents($base.'php')
		or $newtest['script'] = 'Script contents not available.';

	if((isset($test['testtype'])) 
			&& (strtolower($test['testtype']) == 'u'))
	{
		$newtest['testtype'] = 'Unicode';
		$base .= 'u.';
	}
	else
	{
		$newtest['testtype'] = 'Native';
	}

	// Note: the hash reflects the exact filename for native
	// i.e. unicode would be file.u.phpt and native file.phpt
	$hash  = md5($base.'phpt'); 

	if(strtolower($status) == 'fail')
	{
		$newtest['status'] = 'fail';
		$newtest['file'] = $test['file'];
		$newtest['expected'] = file_get_contents($base.'exp')
			or $newtest['expected'] = 'N\A';
                $newtest['output'] = file_get_contents($base.'out')
			or $newtest['output'] = 'N\A';
		$newtest['difference'] = file_get_contents($base.'diff')
			or $newtest['difference'] = 'N\A';
	}
	else // status == 'PASS'
	{
		$newtest['status'] = 'pass';
		$newtest['file'] = $test['file'];
	}

	$xmlarray['tests'][] = $newtest;

	// This loop writes the content for a failed test
	if(strtolower($status) == 'fail')
	{

        	if ($old_dir != $dir) 
		{
			$old_dir = $dir;
			$write .= "<tr><td colspan='3' align='center'><b>$dir</b></td></tr>\n";
		}

	        $write .= "<tr><td><a href='viewer.php?version=$version&func=tests&file=$hash'>$file</a></td><td>{$newtest['testtype']}</td><td>$title</td></tr>\n";

		$additional =
			"<h2>Script</h2><pre>\n" 
			.highlight_string($newtest['script'], true)
			."\n</pre><h2>Expected</h2><pre>\n" 
			.htmlspecialchars($newtest['expected'])
			."\n</pre><h2>Output</h2><pre>\n" 
			.htmlspecialchars(str_replace($phpdir,'',$newtest['output']))
			."\n</pre><h2>Diff</h2><pre>\n"
			.htmlspecialchars(str_replace($phpdir,'',$newtest['difference']))."\n</pre>";

		// now create the Page for the test
		file_put_contents("$outdir/$hash.inc",
		'<?php $filename="'.htmlspecialchars(basename($file)).'"; ?>'.
			$additional.
			html_footer()
		);

		$totalnumfailures += 1;
	}
}

if ($totalnumfailures > 0)
	$write .= "</table>\n";

$write .= html_footer(false);

file_put_contents("$outdir/tests.inc", $write);
?>
