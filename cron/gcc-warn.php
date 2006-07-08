<?php

// todo: make alphabetical sorting on compile_errors.php

// todo: START adding for specific compile_errors.php generation only
//$phpdir="/home/quadra23/temp/PHP_5_2";
//$tmpdir="/home/quadra23/temp/tmp";
////$outdir="/var/www/PHP_5_2err";
//$outdir="/var/www/gcov/PHP_5_2/";
//VALGRIND=valgrind
// todo: END adding for specific compile_errors.php generation only

//$phpsrc .= '/';

$result['numerrors'] = 0;
$result['numwarnings'] = 0;

require_once 'template.php';

$data = file_get_contents("$tmpdir/php_builderr.log");


// REGEX to fetch the gcc errors/warnings. tuned for gcc 3.4.x and 4.0.x
$gcc_regex = '/^(.+): In function [`\'](\w+)\':\s+'.
	     '\1:(\d+):\s+(.+)'.
	     str_repeat('(?:\s+\1:(\d+):\s+(.+))?', 99). // nick hack to capture up to 100 errors in the same function
	     '/m';

preg_match_all($gcc_regex, $data, $data, PREG_SET_ORDER);

$stats = array();

foreach ($data as $error) 
{

	$file     = $error[1];

	// Remove the phpdir portion from the file path if it occurs
	if(substr($file, 0, strlen($phpdir)) == $phpdir)
	{
		$filepath = substr($file, strlen($phpdir));
	}
	else // todo; fix the need for the additional / in front
	{
		$filepath = '/'.$file;
		$file = '/'.$file;
	}
	
	$function = $error[2];

	$write = '';

	for ($i = 3; isset($error[$i]); $i += 2) {
		$line = $error[$i];
		$msg  = $error[$i+1];

		$write .= <<< HTML
 <tr>
  <td>$function</td>
  <td><a href="http://lxr.php.net/source/php-src{$filepath}#{$line}">$line</a></td>
  <td>$msg</td>
 </tr>
HTML;
		if(substr($msg, 0, strlen('error')) == 'error')
		{
			$result['numerrors']++;
		}		
		elseif(substr($msg, 0, strlen('warning')) == 'warning')
		{
			$result['numwarnings']++;
		}
		else 
		{
		}
	
	}

	@$stats[$file][0] += ($i-3)/2; // number of errors in this file
	@$stats[$file][1] .= $write;   // data to write

}

// now write the stuff
$fp = fopen("$outdir/compile_results.inc", 'w');
//fwrite($fp, html_header('Compile errors/warnings'));
fwrite($fp, '<table border="1"><tr><td>File</td><td>Number of errors</td></tr>');

$phpdir_len = strlen($phpdir)+1;
$total = 0;

foreach ($stats as $file => $data) 
{

	$hash       = md5($file); // files have consistent start character
	
	//$short_file = substr($file, $phpdir_len);

	// Compare first portion of file name to phpsrc
	if(substr($file, 0, strlen($phpdir)) == $phpdir)	
	{
		$short_file = substr($file, strlen($phpdir));
	}
	else // If phpsrc does not occur, display full file name (todo: verify)
	{
		$short_file = $file;
	}
		
	$total     += $data[0];
	fwrite($fp, "<tr><td><a href='viewer.php?version=$version&func=compile_results&file=$hash'>$short_file</a></td><td>$data[0]</td></tr>\n");

	$write = <<< HTML
<table border="1">
 <tr>
  <td>Function</td>
  <td>Line</td>
  <td>Message</td>
 </tr>
HTML;

	file_put_contents("$outdir/$hash.inc",
		//html_header('Messages specific to "'. basename($file) . '"').
		//' $filename="'.$basename($file).'"; '.
		'<?php $filename="'.basename($file).'"; ?>'.
		$write.
		$data[1].
		'</table>'.
		html_footer());
}

fwrite($fp, "</table><p>Errors: {$result['numerrors']}<br />Warnings: {$result['numwarnings']}<br />Total: $total</p>" . html_footer());
fclose($fp);
?>
