<?php 
// PHP GCOV Website
// Name: Template
// Desc: default template for new site pages
	
// Include the site API
include_once 'site.api.php';

// Initialize the core components
api_init($appvars);

// Define page variables
$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
$appvars['page']['head'] = 'PHP: Test and Code Coverage Analysis';

// Outputs the site header to the screen
api_showheader($appvars);

?>

<?php
// Outputs the site footer to the screen
api_showfooter($appvars);
