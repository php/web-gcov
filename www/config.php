<?php

/*
// Project: PHP QA GCOV Website
// File: Core Site Configuration File
// Desc: contains core site settings, this file is essential and always included by the API.
*/

// Set up the key variables
$appvars = array(); // Application variables array
$appvars['site'] = array(); // Application site-specific variable array
$appvars['page'] = array(); // Application page-specific variable array

// Define the file that contains the text for each PHP version
$appvars['site']['tagsfile'] = '/home/quadra23/temp/phpqa/tags.inc';

// Define the file that contains all needed database connections
$appvars['site']['dbcsfile'] = '/home/quadra23/temp/phpqa/database.php';

// Define links for the side bar (used in site.api.php)
$appvars['site']['sidebarsubitems_localbuilds'] = array(
		'coverage'  => 'lcov',
		'compile-results' => 'compile_results',
		'graphs' => 'graph',
		'system' => 'system',
		'test-failures' => 'tests',
		'valgrind' => 'valgrind'
	);

	// Define sidebar links that are active when viewing a builder's submissions
$appvars['site']['sidebarsubitems_otherplatforms'] = array(
		'compile-results' => 'compile_results',
		'system' => 'system',
		'test-failures' => 'tests',
		'valgrind' => 'valgrind'
	);

