<?php

// This file receives posts of php builds and stores the values in a database

// todo: verify username and password before accepting contents

// Include the site API so the database connection can be used
include_once 'site.api.php';

// Initialize the core components
api_init($appvars);

$flag = false;

$username = '';
$password = '';
$contents = '';

if(count($_POST) > 0)
{
	$username = $_REQUEST['username'];
	$password = $_REQUEST['password'];
	$contents = $_REQUEST['contents'];

	// This sql is only for testing purposes
	$sql = 'INSERT INTO test(content) VALUES(?)';

	$stmt = $mysqlconn->prepare($sql);

	$stmt->execute(array($username.' '.$password));

	// decode from base64
	$contents = base64_decode($contents);

	// decompress to the XML
	$contents = bzdecompress($contents);

        $stmt->execute(array($contents));
}
// else, an error probably should be logged

