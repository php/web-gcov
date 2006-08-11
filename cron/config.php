<?php

// Cron configuration file for the PHP scripts

  // Todo: if possible integrate the website config to use this config for db

// PHP Mailer configuration settings
$mail_from_name = 'GCOV Admin';
$mail_from_email = 'pronych@php.net';

$mail_smtp_mode = 'disabled';
$mail_smtp_host = null;
$mail_smtp_port = null;
$mail_smtp_user = null;

// Database configuration definitions
$dbuser = 'phpgcov';
$dbpass = 'phpfi';
$dsn = 'mysql:host=localhost;dbname=phpqagcov';

// Sets up a persistent connection to the database
$dsn_attr = array(
		PDO::ATTR_PERSISTENT => true
	);


// Try to perform a database connection, on failure exit
try
{
	$mysqlconn = new PDO($dsn, $dbuser, $dbpass, $dsn_attr);
}
catch(PDOException $e)
{
	// On failure we might want to output to a log
	//echo 'Error: '.$e->getMessage().'<br />';
	exit;
}
