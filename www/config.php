<?php

// Set up the key variables
$appvars = array(); // Application variables array
$appvars['site'] = array(); // Application site-specific variable array
$appvars['page'] = array(); // Application page-specific variable array

// todo: should mytag be considered site-specific or page-specific?
//$appvars['site']['tags'] = array('PHP_4_4', 'PHP_5_1', 'PHP_5_2', 'PHP_HEAD'); 
// Define the root directory of the scripts (todo: verify this is needed)
//$appvars['site']['basepath'] = '/var/www/gcov/';

$user = 'phpgcov';
$pass = 'phpfi';
$dsn = 'mysql:host=localhost;dbname=phpqagcov';

try
{
	$mysqlconn = new PDO($dsn, $user, $pass);
} 
catch(PDOException $e)
{
	echo 'Error: '.$e->getMessage().'<br />';
	exit;
}

// Define the file that contains the text for each PHP version
$appvars['site']['phpvtags'] = 'tags.inc';

// Define links for the side bar (used in site.api.php)
	$appvars['site']['sidebarsubitems'] = array(
		'coverage'  => 'lcov',
		'compile-results' => 'compile_results',
		'graphs' => 'graph',
		'system' => 'system',
		'test-failures' => 'tests',
		'valgrind' => 'valgrind'
	);
