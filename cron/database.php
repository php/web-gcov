<?php

// Database connection script

/*
Note: this script is only needed for the server instance but it is shared between the cron and www scripts
*/

$myuser = 'phpgcov';
$mypass = 'phpfi';
$mydsn = 'mysql:host=localhost;dbname=phpqagcov';

try
{
  $mysqlconn = new PDO($mydsn, $myuser, $mypass);
}
catch(PDOException $e)
{
	$mysqlconn = null;
}
