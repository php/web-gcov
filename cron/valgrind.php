<?php

if(!defined('CRON_PHP'))
{
        echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	exit;
}

$unicode = false;		// assume unicode is disabled

$write = '';

require_once $workdir.'/template.php';
// -----------------


// fix filename after the others are fixed
$data  = file_get_contents("$tmpdir/php_test.log");

// Check if unicode testing is enabled (is this sufficient for PHP > 6?)
if(preg_match('/UNICODE[ ]*:[ ]*ON[ ]?/', $data))
{
	// Easy way to track that unicode is enabled
	$unicode = true;
}

///LEAK(:(?P<testtype>[a-z|A-Z]))? (?P<title>.+) \[(?P<file>[^\]]+)\]/

$leak_re = '/LEAK(:(?P<testtype>[a-z|A-Z]))? (?P<title>.+) \[(?P<file>[^\]]+)\]/';

// Check for Leaks
preg_match_all($leak_re, $data, $leaks, PREG_SET_ORDER);

$old_dir = '';

// If there are no leaks just notify that no leaks occurred
if (count($leaks) < 1) 
{
	$write .= "<p>Congratulations! Currently no memory leaks were found!</p>\n";
} 
else 
{
	$totalnumleaks = count($leaks);
	$write .= '<table border="1">'."\n";
}

foreach ($leaks as $test) 
{

	$base = "$phpdir/".substr($test['file'],0,-4);

	$file  = basename($test['file']);

	$report_file = ''; // Used internally to determine unicode or native filename

	$t = array();

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
	
	// todo: urlencode
	$write .= "<tr><td><a href='viewer.php?version=$phpver&func=valgrind&file=$hash'>$file</a></td><td>$testtype</td><td>$title</td></tr>\n";

	// todo: added str_replace to individual report output
	
	$base = "$phpdir/".substr($test['file'],0,-4);

	$script_php = highlight_file($base.'php', true);
	$script_text = '';

	if($script_php === false)
	{
		$script_php = 'Script contents were not available.';
		$script_text = 'Script contents were not available.';
	}
	else
	{
		$script_text = file_get_contents($base.'php')
		or $script_text = 'Script contents were not available.';
	}
	
	$report = file_get_contents($report_file);

	if($report === false)
		$report = 'Memory leak file contents were not available.';
	else
		$report = str_replace($phpdir,'',$report);

	$additional =
		"<h2>Script</h2><pre>\n" .$script_php.
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
	
	$t['script'] = $script_text;
	
	$t['report'] = $report;
	
	$xmlarray['valgrind'][] = $t;
}

if (count($leaks) > 0)
{
	$write .= "</table>\n";
}

$write .= html_footer();

file_put_contents("$outdir/valgrind.inc", $write);

?>
