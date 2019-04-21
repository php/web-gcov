<?php
/*
  +----------------------------------------------------------------------+
  | PHP QA GCOV Website                                                  |
  +----------------------------------------------------------------------+
  | Copyright (c) The PHP Group                                          |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Daniel Pronych <pronych@php.net>                             |
  |         Nuno Lopes <nlopess@php.net>                                 |
  +----------------------------------------------------------------------+
*/

// Name: GCOV Viewer page
// Desc: page for view PHP version information such as code coverage


// Include the site API
include 'site.api.php';

// Initialize the core components
api_init($appvars);

$content  = ''; // Stores content collected during execution
$error    = ''; // Start by assuming no error has occurred
$fileroot = ''; // base directory for including external files (used for external builds)

$file     = isset($_REQUEST['file']) ? basename($_REQUEST['file']) : '';
$version  = isset($_REQUEST['version']) && isset($appvars['site']['tags'][$_REQUEST['version']]) ? $_REQUEST['version'] : '';

define('IN_GCOV_CODE', true);

if(isset($_REQUEST['username']) && ctype_alnum($_REQUEST['username']) &&
   file_exists('other_platforms/'.$_REQUEST['username'].'/'.$version))
{
	$fileroot = 'other_platforms/'.$_REQUEST['username'].'/';
	$username = $_REQUEST['username'];
	$appvars['site']['builderusername'] = $username;
}


// Define the function array
// each array element starts with the name of the command
// title: page title
// option: options include phpinc (parse file as a PHP include) text (use pre tags)
$func_array = array(
	'compile_results' =>
		array(
			'pagetitle' => 'PHP: Compile Results for '.$version,
			'pagehead' => 'Compile Results'
		),
	'graph' =>
		array(
			'pagetitle' => 'PHP: '.$version. ' Graphs',
			'pagehead' => 'Graphs'
		),
	'lcov' =>
		array(
			'pagetitle' => 'PHP: '. $version.' Code Coverage Report',
			'pagehead' => $version.': Code Coverage Report'
		),
	'params' =>
		array(
			'pagetitle' => 'PHP: Parameter Parsing Report for '.$version,
			'pagehead' => 'Parameter Parsing Report'
		),
	'skip' =>
		array(
			'pagetitle' => 'PHP: Skipped tests for '.$version,
			'pagehead' => 'Skipped Tests'
		),
	'stats' =>
		array(
			'pagetitle' => "Overview of $version",
			'pagehead'  => "Overview of $version"
		),
	'system' =>
		array(
			'pagetitle' => 'PHP: System Info',
			'pagehead' => 'System Info'
		),
	'tested_functions' =>
		array(
			'pagetitle' => 'PHP: Tested Funtions for '.$version,
			'pagehead' => 'Tested Functions'
		),
	'tests' =>
		array(
			'pagetitle' => 'PHP: Test Failures for '.$version,
			'pagehead' => 'Test Failures'
		),
	'expected_tests' =>
		array(
			'pagetitle' => 'PHP: Expected Test Failures for '.$version,
			'pagehead' => 'Expected Test Failures'
		),
	'valgrind' =>
		array(
			'pagetitle' => 'PHP: Valgrind Reports for '.$version,
			'pagehead' => 'Valgrind Reports'
		),
);

if(isset($_REQUEST['func'])) {
	$func = $_REQUEST['func'];
} else {
	$func = 'stats';
}
$appvars['site']['func'] = $func;

if($version || $func === 'search')
{
	$appvars['site']['mytag'] = $version;

	if($func == 'search')
	{
		if(isset($_REQUEST['os']))
		{
			$count = 0;
			$os = $_REQUEST['os'];
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
			if ($stmt = $mysqlconn->prepare($sql))
				$stmt->execute($stmtarray);

			// todo: allow check to be narrowed down to a specific PHP version
			while($stmt && $row = $stmt->fetch())
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
						++$count;
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
			if ($stmt = $mysqlconn->prepare($sql))
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

			while($stmt && $row = $stmt->fetch())
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

	elseif (isset($func_array[$func])) {
		$appvars['page']['title']     = $func_array[$func]['pagetitle'];
		$appvars['page']['head']      = $func_array[$func]['pagehead'];
		$appvars['page']['headtitle'] = $version;

		require "$func.php";

		if(isset($username)) {
			$appvars['page']['head'] .= " (builder: $username)";
		}

	} else {
		// Define page variables
		$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
		$appvars['page']['head'] = 'PHP function not active';

		$error .= 'The PHP version specified exists but the function specified does not appear to serve any purpose at this time.';
	}

} else {
	// Define page variables
	$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
	$appvars['page']['head'] = 'PHP version not active';
	
	$error .= 'The PHP version specified does not appear to exist on the website.';
}

// Outputs the site header to the screen
api_showheader($appvars);

// If an error occurred the command did not exist
if ($error) {
	echo 'Oops!  Seems we were unable to execute your command.  The following are the errors the system found: <br />'.$error;
} else {
	echo $content;
}

// Outputs the site footer to the screen
api_showfooter($appvars);
