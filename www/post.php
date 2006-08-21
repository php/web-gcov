<?php

// This file receives posts of php builds and stores the values in a database

// todo: verify username and password before accepting contents

// Include the site API so the database connection can be used
include_once 'site.api.php';

// Include the post function definitions file
include_once 'post.funcs.php';

// Initialize the core components
api_init($appvars);

// This flag can be used to determine success or failure of posting data
$flag = true;

$username = '';
$password = '';
$contents = '';

$steps = '';

if(count($_POST) > 0)
{
	$steps .= "Checking for Posted Content: OK\n";

	$username = $_REQUEST['username'];
	$password = $_REQUEST['password'];
	$contents = $_REQUEST['contents'];

	//file_put_contents('../temp/a2/b.xml.bz2.b64', $contents);

	// decode the submitted contents from base64
	$contents = base64_decode($contents);

	//file_put_contents('../temp/a2/b.xml.bz2', $contents);

	// Bzip decompress the submitted contents
	$contents = bzdecompress($contents);

	//file_put_contents('../temp/a2/b.xml', $contents);

	$stmt = null;

	// Select User ID for verification
	$sql = 'SELECT user_id FROM remote_builds WHERE user_name=? AND user_pass=?';
	$stmt = $mysqlconn->prepare($sql);
	$stmt->execute(array($username, $password));
	$stmt->bindColumn(1, $user_id, PDO::PARAM_INT);
	$stmt->fetch(PDO::FETCH_BOUND);

	$dataarray = array();

	$steps .= "Check for Username and Password: ";

	// todo: eliminate the need for the while
	if(($user_id > 0) && is_int($user_id))
	{
		$steps .= " OK\n";

		$dir = 'other_platforms/'.$username;

		// Ensure the user has their own builds dir
		if(!@file_exists($dir))
		{
			$steps .= "Verifying User Directory: ";

			// It might be a good idea to log these errors
			if(@mkdir($dir))
				$steps .= "OK \n";
			else
			{
				$flag = false;

				$steps .= "Unable to access your user directory at this time.\n";
			}
		}

		// Load the XML into an XML object
		$xml = simplexml_load_string($contents);

		// Start parsing the XML
		$version = $xml->buildinfo->version;

		$steps .= "Verifying PHP Version Tag: ";

		// Validate the PHP version given before proceeding
		if(array_search($version, $appvars['site']['tags']) !== false)
		{
			$steps .= "OK\n";

			$dir = 'other_platforms/'.$username.'/'.$version;
				
			$steps .= "Verifying PHP Version Tag Directory: ";

			// Check once if the version tag exists, if not, try creating
			if(!@file_exists($dir))
			{
				@mkdir($dir);
			}

			// Check for directory again, if it fails again, we should log something
			if(!@file_exists($dir))
			{
				$flag = false;
				$steps .= "Unable to access your php version tag directory at this time.\n";
			}
			else
			{
				// If directory now exists, we can continue

				$steps .= "OK\n";

				// todo: XML should really be validated at this point

				$outdir = $dir;

				$buildstatus = $xml->buildinfo->buildstatus;
				$buildtime = $xml->buildinfo->buildtime;
				$codecoverage = $xml->buildinfo->codecoverage;
				$compiler = $xml->buildinfo->compiler;
				$configure = $xml->buildinfo->configure;
				$os = $xml->buildinfo->os;
				//$valgrind = $xml->buildinfo->valgrind;

				$steps .= "Checking for Compile Results: ";

				// Check for definition of compile_results
				if(isset($xml->builddata->compile_results))
				{
					$steps .= " OK\n";

					// compile_results.inc
					$index_out = <<< HTML
<table border="1">
<tr>
<td>File</td>
<td>Number of Errors</td>
<td>Number of Warnings</td>
</tr>
HTML;
					// count the number of messages
					$count = count($xml->builddata->compile_results->message);
					$lastfile = '';
					$file_numerrors = 0;
					$file_numwarnings = 0;
					
					// starting output to hash file
					$hash_out = <<< HTML
<table border="1">
<tr>
<td>Function</td>
<td>Line</td>
<td>Message</td>
</tr>
HTML;

					// loop through all the messages
					for($i = 0; $i <= $count; $i++)
					{
						$message = $xml->builddata->compile_results->message[$i];

						// Check for file change or the last message
						if(($message['file'] != $lastfile) || ($i == $count-1))
						{
							// If message is for a different file, start a new file
							if($lastfile != '')
							{
								$hash_out .= '</table>';
								$hash = md5($message['file']);
								$filename = $outdir.'/'.$hash.'.inc';

								$file = basename($lastfile);

								// todo: is there a better method?
								file_put_contents($filename, 
									'<?php $filename = "'.htmlspecialchars($file).'"; ?>'."\n"
									.$hash_out.html_footer(false));

								// Add page information to the index page for this section
								$index_out .= <<< HTML
<tr>
<td><a href="viewer.php?username=$username&version=$version&func=compile_results&file=$hash">$lastfile</a></td>
<td>$file_numerrors</td>
<td>$file_numwarnings</td>
</tr>
HTML;
								// Reset hash output
								$hash_out = <<< HTML
<table border="1">
<tr>
<td>Function</td>
<td>Line</td>
<td>Message</td>
</tr>
HTML;

								$file_numerrors = 0;
								$file_numwarnings = 0;

							} // End check to prevent a non-file from generating output

							// Set last file name to the new file
							$lastfile = $message['file'];
	
						} // End check for file change or last message

						if($message['type'] == 'error')
						{
							$file_numerrors++;
						}
						elseif($message['type'] == 'warning')
						{
							$file_numwarnings++;
						}
						else
						{
	
						}

						$lxrpath = '';

						if(substr($message['file'], 0, 6) == '/Zend/')
						{
							$lxrpath = str_replace('/Zend/','/ZendEngine2/', $message['file']);
							$lxrpath = "http://lxr.php.net/source{$lxrpath}#{$message[line]}";
						}
						else
						{
							$lxrpath = "http://lxr.php.net/source/php-src{$message[file]}#{$message[line]}";
						}

						$hash_out .= <<< HTML
<tr>
<td>$message[function]</td>
<td><a href="$lxrpath">$message[line]</td>
<td>$message[type]: $message</td>
</tr>
HTML;

					} // End for loop through compile_results

					$index_out .= '</table>';
	
					file_put_contents($outdir.'/compile_results.inc', $index_out.html_footer(false));

				} // End check for compile_results definition
     	  else
				{
						$steps .= "N/A\n";
				}

				$steps .= "Checking for Tests: ";

				// Check for definition of tests and build success
				if((isset($xml->builddata->tests)) && ($buildstatus == 'pass'))
				{
					$steps .= "OK\n";

					$index_out = <<< HTML
<table border="1">
HTML;
					$lastdirname = '';

					foreach($xml->builddata->tests->test as $test)
					{

						if($test['status'] == 'fail')
						{
							$title = $test->title;
							$script = highlight_string(htmlspecialchars_decode($test->script, ENT_QUOTES), true);
							$difference = base64_decode($test->difference);
							$expected = base64_decode($test->expected);
							$output = base64_decode($test->output);

							$hash_out = <<< HTML
<h2>Script</h2>
$script
<h2>Expected</h2>
<pre>$expected</pre>
<h2>Output</h2>
<pre>$output</pre>
<h2>Difference</h2>
<pre>$difference</pre>
HTML;

							// Generate hash based on unicode of native test
							$hash = '';
					
							if($test['type'] == 'Unicode')
								$hash = md5($test['file'].'u');
							else
								$hash = md5($test['file']);

							$filename = $outdir.'/'.$hash.'.inc';
							$file = basename($test['file']);
							$title = $test->title;

							if(dirname($test['file']) != $lastdirname)
							{
								$lastdirname = dirname($test['file']);
								
								$index_out .= <<< HTML
<tr>
<td colspan="3" align="center"><b>$lastdirname</b></td>
</tr>
<tr>
<td width="190">File</td>
<td width="80">Test Type</td>
<td width="*">Description</td>
</tr>
HTML;
							}

							$index_out .= <<< HTML
<tr>
<td><a href="viewer.php?username=$username&version=$version&func=tests&file=$hash">$file</a></td>
<td>$test[type]</td>
<td>$title</td>
</tr>
HTML;

							// todo: append file name
							file_put_contents($filename, '<?php $filename = "'.htmlspecialchars($file).'"; ?>'."\n"
								.$hash_out.html_footer(false));

						} // End check for test failure

					} // End loop through each test

					$index_out .= '</table>';

					file_put_contents($outdir.'/tests.inc', $index_out.html_footer(false));

				} // End check for tests definition
				else
				{
					if($buildstatus == 'pass')
						$steps .= "No test failures occurred in this build.\n";
					else
						$steps .= "OK - test failures do not occur for build failures.\n";
				}

				$steps .= "Checking for Valgrind: ";

				if((isset($xml->builddata->valgrind)) && ($buildstatus == 'pass'))
				{
					$steps .= "OK\n";

					$index_out = <<< HTML
<table border="1">
HTML;
					$lastdirname = '';

					foreach($xml->builddata->valgrind->leak as $leak)
					{
						$script =  highlight_string(htmlspecialchars_decode($leak->script, ENT_QUOTES), true);
						$report = $leak->report;

						$hash_out = <<< HTML
<h2>Script</h2>
<pre>$script</pre>
<h2>Report</h2>
<pre>$report</pre>
HTML;

						// Generate hash based on unicode of native leak
						$hash = '';
				
						if($leak['type'] == 'Unicode')
					 		$hash = md5($leak['file'].'u');
						else
							$hash = md5($leak['file']);

						$filename = $outdir.'/'.$hash.'.inc';
						$file = basename($leak['file']);

						if(dirname($leak['file']) != $lastdirname)
						{
							$lastdirname = dirname($leak['file']);

							$index_out .= <<< HTML
<tr>
<td colspan="3" align="center"><b>$lastdirname</b></td>
</tr>
<tr>
<td width="190">File</td>
<td width="80">Leak Type</td>
<td width="*">Description</td>
</tr>
HTML;
						}

						$title = $leak->title;

						$index_out .= <<< HTML
<tr>
<td><a href="viewer.php?username=$username&version=$version&func=valgrind&file=$hash">$file</a></td>
<td>$leak[type]</td>
<td>$title</td>
</tr>
HTML;

						file_put_contents($filename, '<?php $filename = "'.htmlspecialchars($file).'"; ?>'
							."\n".$hash_out.html_footer(false));

					}

					$index_out .= '</table>';

					file_put_contents($outdir.'/valgrind.inc', $index_out.html_footer(false));

				} // End check for valgrind definition

  	    else
				{
					if($buildstatus == 'pass')
						$steps .= "No memory leaks occurred in this build, or valgrind is not enabled on either your system or for the current build configuration.\n";
					else
					{
							$steps .= "OK - memory leaks can not be found for failed builds.\n";
					}

				}

				$steps .= "Checking OS: ";

				if(isset($xml->buildinfo->os))
				{	
					$steps .= "OK\n";

					preg_match('/(?P<osname>.+) (?P<osversion>[0-9|.]+)/', $xml->buildinfo->os, $osarray);

					$stmt = null;
					$sql = 'UPDATE remote_builds SET last_build_xml=?, last_user_os=?, last_user_os_version=? WHERE user_id=?';
				 	$stmt = $mysqlconn->prepare($sql);
		  		$stmt->execute(array($contents, $osarray['osname'], $osarray['osversion'], $user_id));

					$codecoverage_string = '';

					if($codecoverage >= 0)
						$codecoverage_string = $codecoverage.'%';
					else
						$codecoverage_string = 'N/A';

					$buildtime_string = '';

					if($buildtime >= 0)
						$buildtime_string = $buildtime. ' seconds';
					else
						$buildtime_string = 'N/A';

					// Assemble content for system.inc
					$index_out = '';

					$index_out .= <<< HTML
<h2>Build Information:</h2>
<table border="0">
<tr>
<td>Build Status:</td>
<td>$buildstatus</td>
</tr>
<tr>
<td>Build Time:</td>
<td>$buildtime_string</td>
</tr>
<tr>
<td>Code Coverage:</td>
<td>$codecoverage_string</td>
</tr>
</table>
<h2>Configure Used:</h2>
<pre>$configure</pre>
<h2>Compiler Used:</h2>
<pre>$compiler</pre>
<h2>Operating System:</h2>
<pre>$os</pre>
HTML;

					$filename = $outdir.'/system.inc';
					file_put_contents($filename, $index_out.html_footer(false));
				
				}
				else
				{
					$steps .= "N/A\n";
				}

			} // End else portion of the check for a valid PHP version given

		} // End check for availability of the output directory
		else
		{
			$flag = false;

			$steps .= "Unable to access PHP tag directory in your user directory.\n";
		}

	} // End check for a valid user

	else
	{
		$flag = false;

		$steps .= " Username and/or password are incorrect.\n";
	}

	// Send notification on pass or fail of post operation
	if($flag)
	{
		echo "The post process seems to have succeeded!\n";
	}
	else
	{
		echo "Unfortunately, it appears the post process did not succeed.\n";
	}

	// Post the specific results
	echo "PHP QA GCOV Server Post Results:\n".$steps;
	
} // End check for posted content
else
{
	echo 'File called with invalid parameters.';
	exit;
}

// Any clean up would be performed here
