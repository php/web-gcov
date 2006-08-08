<?php 
// PHP GCOV Website
// Name: GCOV Viewer page

// Desc: page for view PHP version information such as code coverage
	
// Include the site API
include_once 'site.api.php';

// Initialize the core components
api_init($appvars);

$content = ''; // Stores content collected during execution
$error = ''; // Start by assuming no error has occurred
$file = '';

$fn = '';

$incfile = '';

// Pull in defined variables
$file = $_REQUEST['file'];
$version = $_REQUEST['version'];

if(isset($_REQUEST['date']))
{
	list($year, $month, $day) = split('[^0-9]', $_REQUEST['date'], 3);

	// Ensure each variable is numeric before checking the date
	if(is_numeric($month) && (is_numeric($day)) && (is_numeric($year)) 
		&& ($year > 1909))
	{
		if(checkdate($month, $day, $year))
		{
			$date = $_REQUEST['date'];
		}
	}
}

// Define the function array
// each array element starts with the name of the command
// title: page title
// option: options include phpinc (parse file as a PHP include) text (use pre tags)
$func_array = array(
	'compile_results' =>
		array(
			'option' => 'phpinc', 
			'pagetitle' => 'PHP: Compile Results for '.$version, 
			'pagehead' => 'Compile Results'
		),
	'php_test_log' =>
		array(
			'option' => 'text',
			'pagetitle' => 'PHP: Test Log for '. $version,
			'pagehead' => 'Test Log'
		),
	'valgrind' =>
		array(
			'option' => 'phpinc', // or text at this point
			'pagetitle' => 'PHP: Valgrind Report for '.$version, 
			'pagehead' => 'Valgrind Report'
		),
	'tests' =>
		array(
			'option' => 'phpinc', 
			'pagetitle' => 'PHP: Test Failures for '.$version, 
			'pagehead' => 'Test Failures'
		),
	'system' =>
		array(
			'option' => 'phpinc',
			'pagetitle' => 'PHP: System Info',
			'pagehead' => 'System Info'
		)
	);

if(isset($_REQUEST['func']))
{
	$func = $_REQUEST['func'];
}
else
{
	$func = 'lcov';
}

// Accepts: date in format of YYYY_MM_DD (non-numeric separator character)
// Returns: date in textual format of this example: Jan 1 2006
function datestring_fromdate($date)
{
	//$retstring = '';
	//if(strlen($date) > 9)
	//{
		list($year, $month, $day) = split('[^0-9]', $date, 3);
	
		// Hour, Minute, Second, Month, Day, Year
		$time = mktime(0,0,0,$month,$day,$year);
	return date('M j Y', $time);
	//}
	//else
	//{
		//$retstring = false;
	//}
	//return $retstring;
}

// Ensure the version specified is valid (todo: more security required?)
// note: !== false is required since PHP_4.4.1 has the 0th place
if(array_search($version, $appvars['site']['tags']) !== false)
{
	$appvars['site']['mytag'] = $version;

	// todo: make functions an array or similiar
	$func_element = array_search($func, $func_array);
	
	if($func == 'build')
	// Displays the build content for this version
	{
		// Collect file content
		$fn = $version.'/build.sh';
		// Open file handle
		$fh = @fopen($fn, 'r');
		// Obtain file size
		$content = @fread($fh, filesize($fn));
		@fclose($fh);		

		// If the file is not readable, set up error content and header
		if($content === false)
		{
			// Define page variables		
			$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis of '.$version;
			$appvars['page']['head'] = $version.': no build file';
			$appvars['page']['headtitle'] = $version;
			$content = 'There is no build file available to be read.  Please try again in a few minutes.';
		}
		else
		{
			// Define page variables		
			$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis of '.$version;
			$appvars['page']['head'] = $version.': build.sh';
			$appvars['page']['headtitle'] = $version;
			$content = "<pre>\n".$content
					."</pre>\n";
			
		} // End of content value check
	}
	
	else if(@array_key_exists($func,$func_array))
	{
		// Determine the file to use
		if(isset($func_array[$func]['file']))
		{
			$incfile = $func_array[$func]['file'];
		}
		elseif($file == '')
		{
			$incfile = $func;
		}
		else
		{
			$incfile = basename($file);
		}

		// Determine the file path
		$filepath = $version.'/'.$incfile.'.inc';

		// Obtain file contents by the required method
		if($func_array[$func]['option'] == 'phpinc') // Parse file as a PHP script
		{
	                ob_start();
        	        if(file_exists($filepath))
                	{
				include_once $filepath;
			}
			$content = ob_get_clean();
			ob_end_flush();

			if(isset($filename))
			{
				$func_array[$func]['pagehead'] .= ' for \''.$filename.'\'';
			}
		}
		else // Treat the file contents as regular text file
		{
	                // Collect file content
        	        $fn = $version.'/build.sh';
			// Open file handle
			$fh = @fopen($filepath, 'r');
			// Read file contents
			if($func_array[$func]['option'] == 'text')
				$content = '<pre>'.@fread($fh, filesize($filepath)).'</pre>';
			else
				$content = @fread($fh, filesize($filepath));
				
			@fclose($fh);
		}

		// Determine title based on success or failure
		if(($content == '') || ($content === false))
		{
			$appvars['page']['title'] = $func_array[$func]['pagetitle'];
			$appvars['page']['head'] = $func_array[$func]['pagehead'].' Data File Not Available';
			$appvars['page']['headtitle'] = $version;

			$content = 'File could not be opened.  Please try again in a few minutes, or return to the <a href="viewer.php?version='.$version.'&func='.$func.'">listing</a> page.';
		}
		else
		{
                        $appvars['page']['title'] = $func_array[$func]['pagetitle'];
	                $appvars['page']['head'] = $func_array[$func]['pagehead'];
	                $appvars['page']['headtitle'] = $version;
		}

		// todo: is this correct?
		$content = str_replace($phpdir, 'replaced', $content);
	}
	else if($func == 'graph')
	{
		// If date is not set display all available dates
		// todo: format date as the actual date instead of numbers
		if(!isset($date))
		{
			$file = $version.DIRECTORY_SEPARATOR.'graph.inc';

			$dates = @file_get_contents($file);

			$temp_date = '';
			$x = 0;

			while($x < strlen($dates))
			{
				//$date = '';
				if(($dates[$x] != "\n") && ($x < strlen($dates)))
				{	
					$temp_date .= $dates[$x];
				}
				else
				{
					$content .= '<a href="viewer.php?version='.$version.'&amp;func=graph&date='.$temp_date.'">'.datestring_fromdate($temp_date).'</a>'.'<br />';
					$temp_date = '';
				}
				$x++;
			}

			if($dates === false)
			{
				$content = 'No graphs currently exist for this version.';
			}

			else
			{
				$content = 'Choose a date to view the associated graphs:<br />'.$content;
			}
			
			$appvars['page']['title'] = 'PHP: '.$version.' Graphs';
			$appvars['page']['head'] = 'Graphs';
			$appvars['page']['headtitle'] = $version;

		}
		else // Display the graphs for the specified PHP version and date
		{
			$datestring = datestring_fromdate($date);
			// todo: if graphs don't exist display something else like a not found
			if($datestring === false) 
			{

				$appvars['page']['title'] = 'PHP: '.$version.' Graphs Not Found For '.$datestring;
				$appvars['page']['head'] = 'Graphs Not Found For '.$datestring;
				$appvars['page']['headtitle'] = $version;

				$content = 'No graphs could be located for this PHP version for the specified date.';

			}
			else
			{
				$appvars['page']['title'] = 'PHP: '.$version.' Graphs For '.$datestring;
				$appvars['page']['head'] = 'Graphs For '.$datestring;
				$appvars['page']['headtitle'] = $version;

				$date = basename($date); // ensure a path is not specified here
				$content .= '<p>The following images show the changes in number of compile errors, compile warnings, memory leaks and test failures over the past week up to the date of '.$datestring.'.</p>';

				if(file_exists($version.'/graphs/'.'errors_'.$date.'.png'))
					$content .= '<img src="'.$version.'/graphs/'.'errors_'.$date.'.png" />&nbsp;';
				if(file_exists($version.'/graphs/'.'failures_'.$date.'.png'))
					$content .= '<img src="'.$version.'/graphs/'.'failures_'.$date.'.png" /><br />';
				if(file_exists($version.'/graphs/'.'memleaks_'.$date.'.png'))
					$content .= '<img src="'.$version.'/graphs/'.'memleaks_'.$date.'.png" />&nbsp;';
				if(file_exists($version.'/graphs/'.'warnings_'.$date.'.png'))
					$content .= '<img src="'.$version.'/graphs/'.'warnings_'.$date.'.png" /><br />';
			}
		}
	}

	else if($func == 'run_tests') 
	// Displays run-tests content for this version
	{		
		// Collect file content
		$fn = $appvars['site']['basepath'].'/'.$version
		.'/run-tests.html.inc';
		// Open file handle
		$fh = @fopen($fn, 'r');
		// Obtain file size
		$content = @fread($fh, filesize($fn));
		@fclose($fh);

		// If the file is not readable, set up error content and header
		if($content === false)
		{
			// Define page variables		
			$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis of '.$version;
			$appvars['page']['head'] = $version.': no run-tests log';
			$appvars['page']['headtitle'] = $version;
			$content = 'There is no run tests log available to be read.  Please try again in a few minutes.';
		}
		else // If the file was readable, set up page headers
		{
			// Define page variables
			$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis of '.$version;
			$appvars['page']['head'] = $version.': run-tests.php report';
			$appvars['page']['headtitle'] = $version;			
		} // End of content value check
	}
	else if($func == 'lcov') 
	// Displays the lcov content for this version
	{
		// Define page variables		
		$appvars['page']['title'] = 'PHP: '. $version.' Code Coverage Report';
		$appvars['page']['head'] = $version.': Code Coverage Report';
		$appvars['page']['headtitle'] = $version;		

		// Collect file content
		$content = 'content for code coverage would be here.';

		// todo: if there is no lcov content, inform user here
	}
	else
	{
		// Define page variables
		$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
		$appvars['page']['head'] = 'PHP function not active';
		
		$error .= 'The PHP version specified exists but the function specified does not appear to serve any purpose at this time.';
	}
}
else
{
	// Define page variables
	$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
	$appvars['page']['head'] = 'PHP version not active';
	
	$error .= 'The PHP version specified does not appear to exist on the website.';
}

// Outputs the site header to the screen
api_showheader($appvars);

// If an error occurred the command did not exist
if($error != '')
{
	echo 'Oops!  Seems we were unable to execute your command.  The following are the errors the system found: <br />'.$error;
}
else
{
	echo $content;
}
?>

<?php
// Outputs the site footer to the screen
api_showfooter($appvars);
