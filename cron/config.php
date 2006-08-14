<?php

// Cron configuration file for the PHP scripts

// Todo: if possible integrate the website config to use this config for db

// If is_master = true; after build, stores the outcome locally
// If is_master = false; after build, sends data to remote server
// Note: if is_master = false; a valid user and pass is required
$is_master = false;

// Note: these options are specific to is_master = false
$server_submit_url = 'http://gcov.aristotle.dlitz.net/post.php';
$server_submit_user = 'johndoe';
$server_submit_pass = 'john';

// PHP Mailer configuration settings
if($is_master)
{
	// PHP Mailer configuration settings
	$mail_from_name = 'GCOV Admin';
	$mail_from_email = 'some.address@example.com';

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
} // End check if server is a master
else
{

}
