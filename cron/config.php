<?php

// Cron configuration file for the PHP scripts


// If is_master = true; after build, stores the outcome locally
// If is_master = false; after build, sends data to remote server
// Note: if is_master = false; a valid username and password is required
// to post the data to the central server
$is_master = false;

// Note: these options are specific to is_master = false
$server_submit_url = 'http://gcov.php.net/post.php';
$server_submit_user = 'johndoe';
$server_submit_pass = 'john';

// PHP Mailer configuration settings
if($is_master)
{
	// PHP Mailer configuration settings
	$mail_from_name = 'GCOV Admin';
	$mail_from_email = 'internals@lists.php.net';

	$mail_smtp_mode = 'disabled';
	$mail_smtp_host = null;
	$mail_smtp_port = null;
	$mail_smtp_user = null;

	// Include the required database connections
	require_once 'database.php';

} // End check if server is a master
else
{
	// Any client configuration would be made here

} // End  check if client instance
