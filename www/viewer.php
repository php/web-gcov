<?php 
// PHP GCOV Website
// Name: GCOV Viewer page

// Desc: page for view PHP version information such as code coverage

/*
Irregular Variable Usage:
$filename	= used for php include files where a filename is specified in PHP code
$os 			= used only when func=search

*/

// Include the site API
include_once 'site.api.php';

// Initialize the core components
api_init($appvars);

$content = ''; // Stores content collected during execution
$error = ''; // Start by assuming no error has occurred
$file = '';

$fn = ''; // represents the file name

$incfile = ''; // used for php includes of files
$fileroot = '';

// Pull in defined variables
$file = isset($_REQUEST['file']) ? $_REQUEST['file'] : '';
$version = isset($_REQUEST['version']) ? $_REQUEST['version'] : '';

// todo: in testing phase
if(isset($_REQUEST['username']))
{
	if(file_exists('other_platforms/'.$_REQUEST['username']))
	{
		if(file_exists('other_platforms/'.$_REQUEST['username'].'/'.$version))
		{
			$fileroot = 'other_platforms/'.$_REQUEST['username'].'/';
			$username = $_REQUEST['username'];

			$appvars['site']['builderusername'] = $username;
		}
	}
}

if(isset($_REQUEST['mode']))
{
	$mode = $_REQUEST['mode'];
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
	// todo: remove php_test_log
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

// Define the acceptable modes for the graphs
$graph_mode_array = array(
		'weekly' => 
			array(
				'name' => 'weekly',
				'title' => 'Weekly'
			),
		'monthly' =>
			array(
				'name' => 'monthly',
				'title' => 'Monthly'
			)
		);

$graph_types_array = array('codecoverage','failures','memleaks','warnings');


if(isset($_REQUEST['func']))
{
	$func = $_REQUEST['func'];

}
else
{
	// If username is not set, show lcov, if it is set, default to system
	if(!isset($username))
	{
		$func = 'menu';
	}
	else
	{
		$func = 'system';
	}
}
$appvars['site']['func'] = $func;

// Ensure the version specified is valid (todo: more security required?)
// note: !== false is required since PHP_4.4.1 has the 0th place
if((array_search($version, $appvars['site']['tags']) !== false) || ($func == 'search'))
{
	$appvars['site']['mytag'] = $version;

	// todo: make functions an array or similiar
	$func_element = array_search($func, $func_array);

	// todo: this function is experimental
	if($func == 'search')
	{
		if(isset($_REQUEST['os']))
		{
			$count = 0;
			$os = $_REQUEST['os'];
			$stmt = null;
			$stmtarray = array();

			$appvars['page']['title'] = 'PHP: Other Platform Search Results';
      $appvars['page']['head'] = 'Other Platform Search Results';
			$appvars['page']['headtitle'] = 'Search Results';

			if($os == 'all')
			{
      	$sql = 'SELECT user_name, last_user_os FROM remote_builds';
			}
			else
			{
				$sql = 'SELECT user_name, last_user_os FROM remote_builds WHERE last_user_os=?';
				$stmtarray = array($os);
			}
      $stmt = $mysqlconn->prepare($sql);
 	    $stmt->execute($stmtarray);

			// todo: allow check to be narrowed down to a specific PHP version
			while($row = $stmt->fetch())
			{
				list($user_name, $last_user_os) = $row;

				$dirroot = 'other_platforms/'.$user_name;

				foreach($appvars['site']['tags'] as $phpvertag)
				{

					if(file_exists($dirroot.'/'.$phpvertag.'/system.inc'))
					{
						$content .= <<< HTML
<a href="viewer.php?username=$user_name&version=$phpvertag">$user_name</a> (PHP Version: $phpvertag, OS: $last_user_os)<br />
HTML;
						$count++;
					} // End check if tag is active for this user

				} // End loop through each accepted PHP version

			} // End loop through results

			if($count == 0)
			{
				$content = 'Your search seems too narrow to have any results.';
			} // End check for no results

		} // End check if OS is set
		else
		{
			$stmt = null;
			$sql = 'SELECT DISTINCT last_user_os FROM remote_builds';
			$stmt = $mysqlconn->prepare($sql);
			$stmt->execute();

			$appvars['page']['title'] = 'PHP: Search Other Platforms';
			$appvars['page']['head'] = 'Other Platform Search';
			$appvars['page']['headtitle'] = 'Search';

			$content .= <<< HTML
<p>Select the platforms you wish to search for existing builds.</p>
<form method="post" action="viewer.php">
<input type="hidden" name="func" value="search" />
<table border="0">
<tr>
<td>Operating System(s):</td>
<td><select name="os">
<option value="all">All Platforms</option>
HTML;

			while($row = $stmt->fetch())
			{
		  	list($os) = $row;

		  	$content .= <<< HTML
<option value="$os">$os</option>
HTML;

			}

			$content .= <<< HTML
</select></td>
</table>
<input type="submit" value="Search" />
</form>
HTML;
		} // End check for Operating System set

	} // End check for function search	

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
		$filepath = $fileroot.$version.'/'.$incfile.'.inc';

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

			$content = 'File could not be opened.  Please try again in a few minutes, or return to the <a href="/">listing</a> page.';
		} // End check for no content or failure
		else
		{
			$appvars['page']['title'] = $func_array[$func]['pagetitle'];

			$appvars['page']['head'] = $func_array[$func]['pagehead'];

			if(isset($username))
			{
				$appvars['page']['head'] .= ' (builder: '.$username.')';
			} // End check if username is set

			$appvars['page']['headtitle'] = $version;
		} // End check for content

	} // End check for func defined in func_array

	else if($func == 'graph') // todo: revamp this section entirely
	{
		// If date is not set display all available dates
		// todo: format date as the actual date instead of numbers
		if(!@array_key_exists($mode, $graph_mode_array))
		{

			$appvars['page']['title'] = 'PHP: '.$version.' Graphs';
      $appvars['page']['head'] = 'Graphs';
      $appvars['page']['headtitle'] = $version;

			$content .= <<< HTML
<p>Select the period of time you wish to view as a graphical progression.</p>
HTML;

			foreach($graph_mode_array as $graph_mode)
			{
				$content .= <<< HTML
<a href="viewer.php?version=$version&func=graph&mode=$graph_mode[name]">Last $graph_mode[title]</a><br />
HTML;
			}

		}
		else // Display the graphs for the specified PHP version and date
		{
			$appvars['page']['title'] = 'PHP: '.$version.' Last '.$graph_mode_array[$mode]['title'] . ' Graphs';
			$appvars['page']['head'] = 'Last '.$graph_mode_array[$mode]['title']. ' Graphs';
			$appvars['page']['headtitle'] = $version;

			$content .= '<p>The following images show the changes in code coverage, compile warnings, memory leaks and test failures.</p>';

			$graph_count = 0;

			foreach($graph_types_array as $graph_type)
			{
				if(file_exists($version.'/graphs/'.$graph_type.'_'.$mode.'.png'))
				{
					$content .= '<img src="'.$version.'/graphs/'.$graph_type.'_'.$mode.'.png" />&nbsp;'."\n";

					if(++$graph_count == 2)
						$content .= '<br />'."\n";

				} // End check for graph file

			} // End loop through graph types
			
		} // End check for valid graph mode

	} // End check for func=graph

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
	else if($func == 'menu')
	{
		$content = 'Please choose one function from the menu on the left.';
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
