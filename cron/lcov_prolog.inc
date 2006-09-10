<?php
// find the path to the php-gcov-web checkout automatically
include substr(__FILE__, 0, -strlen($_SERVER['SCRIPT_NAME'])).'/site.api.php';

api_init($appvars);

// Define page variables
$appvars['page']['title'] = '@pagetitle@';
$appvars['page']['head']  = '';
$appvars['page']['css'][] = '@basedir@gcov.css';

// Outputs the site header to the screen
api_showheader($appvars);
?>
