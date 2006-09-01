<?php

// File to process memory leaks detected by valgrind

if(!defined('CRON_PHP'))
{
        echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	exit;
}

require_once $workdir.'/template.php';

// data: contains the contents of $tmpdir/php_test.log
// unicode: true if unicode is included in the log files

// Output for core file
$index_write = '';

// Output for individual files
$write = '';

// Regular expression to select leaks for both unicode and not
$leak_re = '/LEAK(:(?P<testtype>[a-z|A-Z]))? (?P<title>.+) \[(?P<file>[^\]]+)\]/';

// Find memory leaks in the data
preg_match_all($leak_re, $data, $leaks, PREG_SET_ORDER);

// Clear the old directory variable
$old_dir = '';

// If there are no leaks just notify that no leaks occurred
if (count($leaks) < 1) 
{
	$index_write .= "<p>Congratulations! Currently no memory leaks were found!</p>\n";
} 
else 
{
	$totalnumleaks = count($leaks);
	$index_write .= '<table border="1">'."\n";
} // End check for number of leaks

// Loop through each result
foreach ($leaks as $test) 
{

	$base = "$phpdir/".substr($test['file'],0,-4);

	$dir = dirname($test['file']);

	$file = basename($test['file']);

	$hash  = 'v' . md5($report_file);

	$report_file = ''; // Used internally to determine unicode or native filename

	$testtype = '';

	$title = $test['title'];

	// If test mode is not unicode it is native
	if((isset($test['testtype'])) && (strtolower($test['testtype']) == 'u'))
	{
		$testtype = 'Unicode';

		// This ensure the hash for unicode differs from the native leak
		$report_file = $base.'u.mem';
	}
	else
	{
		$testtype = 'Native';

		$report_file = $base.'mem';
	} // End check for test type

	// Check if master server
	if($is_master)
	{
		if ($old_dir != $dir) 
		{
			$old_dir = $dir;
		
			$index_write .= <<< HTML
<tr>
<td colspan="3" align="center"><b>$dir</b></td>
</tr>
<tr>
<td width="190">File</td>
<td width="100">Test Type</td>
<td width="*">Name</td>
</tr>
HTML;

		} // End check for directory change

		// Add the test to valgrind main page output
		$index_write .= <<< HTML
<tr>
<td><a href="viewer.php?version=$phpver&func=valgrind&file=$hash">$file</a></td>
<td>$testtype</td>
<td>$title</td>
</tr>
HTML;

	} // End check for master server

	$script_text = file_get_contents($base.'php');

	if($script_text === false)
	{
		$script_text = 'Script contents were not available.';
		$script_php = $script_text;
	}
	else
	{
		$script_php = highlight_string($script_text, true);
	} // End check for ability to obtain the script file contents
	
	$report = file_get_contents($report_file);

	if($report === false)
	{
		$report = 'Memory leak file contents were not available.';
	}
	else
	{
		$report = str_replace($phpdir,'',$report);
	} // End check for ability to obtain the contents of the mem file

	if($is_master)
	{
		$write = <<< HTML
<h2>Script</h2>
<pre>$script_php</pre>
<h2>Report</h2>
<pre>$report</pre>
HTML;

		// Output content for the individual test page
		file_put_contents("$outdir/$hash.inc",
			'<?php $filename="'.basename($file).'"; ?>'."\n"
			.$write.
			html_footer()
		);

	} 
	else
	{
		// If not master server, add the leak to the output array

		$newleak = array();
		$newleak['testtype'] = $testtype;
	  $newleak['title'] = $test['title'];
		$newleak['file'] = $test['file'];
    $newleak['script'] = $script_text;
    $newleak['report'] = $report;
    $xmlarray['valgrind'][] = $newleak;

	} // End check for master server 

} // End loop through each leak

// End check for master server
if($is_master)
{
	if (count($leaks) > 0)
	{
		$index_write .= "</table>\n";
	}

	file_put_contents("$outdir/valgrind.inc", 
		$index_write.html_footer());

} // End check for master server

?>
