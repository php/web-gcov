<?php

if ($argc != 6) 
{
	die("cron.php requires 5 arguments: [tmp] [out] [phpsrc] [makestatus] [phpversion]\n\n");
}

define('CRON_PHP',true);

$tmpdir  = $argv[1];	// Temporary storage directory for this PHP version
$outdir  = $argv[2];	// Output directory for this PHP version (set, but only used in master)
$phpdir  = $argv[3];	// Directory where the PHP build source files are located
$makestatus = $argv[4]; // Make status from bash script (fail or pass)
$phpver = $argv[5];	// The version identifier for this PHP build (i.e. PHP_4_4)

$workdir = dirname(__FILE__); // Get the working directory to simplify php file includes

// Initialize core variables
$build_time = -1;	// Total time required for build (build_time.php)

$totalnumerrors = 0; 	// Total number of errors (compile_errors.php)
$totalnumwarnings = 0;	// Total number of warnings (compile_errors.php)

$totalnumleaks = 0;	// Total number of memory leaks (valgrind.php)
$totalnumfailures = 0;	// Total number of test failures (tests.php)

$configureinfo = 'N/A';	// Information regarding configure (system.php)

$compilerinfo = 'N/A';	// Information regarding compiler (system.php)

$osinfo = 'N/A';	// Information regarding operating system (system.php)

$valgrindinfo = 'N/A'; // Information regarding valgrind (system.php)

$codecoverage_percent = -1; // Information regarding the code coverage

$version_id = 0;	// Start by assuming the version_id is unknown

$xmlarray = array();

// Set up variables that apply to all scripts
$version = basename($phpdir, __FILE__); // todo: make this dynamic or based on the tags instead

// Load main configuration including database connection
require $workdir.'/config.php';

// Load templates
require $workdir.'/template.php';

//process stuff
require $workdir.'/compile_results.php';   // make compile errors/warnings file

require $workdir.'/system.php';

// This section is required for either system configuration
if($makestatus == 'pass')
{
	// todo: summarize what variables each page modifies


	$data  = file_get_contents("$tmpdir/php_test.log");

	// If file could not be opened we should track the error
	if($data === false)
	{
		echo "unable to open log"."\n";
	}
	else
	{
		// Check for unicode (is this sufficient for PHP > 6?)
		if(preg_match('/UNICODE[ ]*:[ ]*ON[ ]?/', $data))
		{
			// Easy way to track that unicode is enabled
			$unicode = true;
		}

		// Run the PHP tests
		require $workdir.'/tests.php';
		// Run the valgrind code
		require $workdir.'/valgrind.php';
		// Get the time it took to create the build
		require $workdir.'/build_time.php';
	}
}

// Start Master Only Section //
if($is_master)
{
	// Get version ID for the current PHP version
	try
	{
		$sql = 'SELECT version_id FROM versions WHERE version_name = ?';
		$stmt = $mysqlconn->prepare($sql);
		$stmt->execute(array($phpver));
		$version_id = $stmt->fetchColumn();
	}
	catch(PDOException $e)
	{
		// if error occurs this might be good to log
		$version_id = 0;
	}

	if($version_id > 0)
	{
		// Add new build to the build tables
		$build_date = date('Y-m-d');
		$build_datetime = date('Y-m-d H-i-s');
		$stmt = null;

		$sql = 'INSERT INTO builds (version_id, build_date, build_datetime, build_numerrors, build_numwarnings, build_numfailures, build_numleaks, build_os_info, build_compiler_info, build_linker_info) '.
'VALUES (:version_id, :build_date, :build_datetime, :build_numerrors, :build_numwarnings, :build_numfailures, :build_numleaks, :build_os_info, :build_compiler_info, :build_linker_info) ';
		$stmt = $mysqlconn->prepare($sql);

		$stmt->bindParam(':version_id', $version_id);
		$stmt->bindParam(':build_date', $build_date);
		$stmt->bindParam(':build_datetime', $build_datetime);
		$stmt->bindParam(':build_numerrors', $totalnumerrors);
		$stmt->bindParam(':build_numwarnings', $totalnumwarnings);
		$stmt->bindParam(':build_numfailures', $totalnumfailures);
		$stmt->bindParam(':build_numleaks', $totalnumleaks);
		$stmt->bindParam(':build_os_info', $osinfo);
		$stmt->bindParam(':build_compiler_info', $compilerinfo);
		$stmt->bindParam(':build_linker_info', $linkerinfo);
		$stmt->execute();
		$stmt = null;

		if($makestatus == 'pass')
		{
			
			// todo:for weekly,  if a Sunday, call graphs otherwise skip 
			if(date('D') == 'Sun')
			{
				require $workdir.'/graph.php';
			}

			// Do SQL updates for the specific PHP version

			$sql = 'UPDATE versions SET version_last_build_time = ?, version_last_attempted_build_date = ?, version_last_successful_build_date = ? WHERE version_id = ?';
			$stmt_arr = array($build_time, $build_datetime, $build_datetime, $version_id);
		}
		else
		{
			// If build fails only update the last attempted build date for the version
			$sql = 'UPDATE versions SET version_last_attempted_build_date = ? WHERE version_id = ?';
			$stmt_arr = array($build_datetime, $version_id);
		}

		$stmt = $mysqlconn->prepare($sql);
		$stmt->execute($stmt_arr);

		// Update the existing version information
		echo 'Build Time: '.$build_time."\n";
		echo 'Code Coverage: '.$codecoverage_percent.'%'."\n";
	
		file_put_contents($outdir.DIRECTORY_SEPARATOR.'last_make_status.inc', $makestatus);
		#todo: if write fails we need to do something about it

	} // End check for version > 0

        if(file_put_contents($tmpdir.'/build.xml', $xml_out))
	        {
	                echo 'XML file written'."\n";
		}

} // End Master Only Section
else
{
	// Start Slave Only Section //

	// todo: recode XML creation so its easier to maintain

	// load in the test log
	$contents = file_get_contents($tmpdir.'/php_test.log');

	$search_for = array($phpdir, $tmpdir);
	$replace_with = array('/'.$phpver,'/'.$phpver);

	$contents = str_replace($search_for, $replace_with, $contents);
	file_put_contents($outdir.'/php_test.log.txt', $contents);
	// end test log

	$xml_out = '<?xml version="1.0" encoding="UTF-8"?>';
	
	$xml_out .= <<< XML

<build>
<buildinfo>
<username>$server_submit_user</username>
<version>$phpver</version>
<buildstatus>$makestatus</buildstatus>
<buildtime>$build_time</buildtime>
<codecoverage>$codecoverage_percent</codecoverage>
<compiler>$compilerinfo</compiler>
<configure>$configureinfo</configure>
<os>$osinfo</os>
<valgrind>$valgrindinfo</valgrind>
</buildinfo>
<builddata>
XML;

	$xml_search_for = array($phpdir);
	$xml_replace_with = array('');

	// Ensure the section actually exists before appending to the XML
	if(isset($xmlarray['compile_results']))
	{
		$xml_out .= <<< XML
<compile_results>
XML;

		foreach($xmlarray['compile_results'] as $res)
		{
			$xml_out .= <<< XML
			
<message file="$res[file]" function="$res[function]" line="$res[line]" type="$res[type]">$res[msg]</message>
XML;
		}
	
                $xml_out .= <<< XML
</compile_results>
XML;
	} // End check for compile results definition

	// todo: output failure and pass type (i.e. native or unicode)
	if(isset($xmlarray['tests']))
	{
		$xml_out .= <<< XML
<tests>
XML;

		foreach($xmlarray['tests'] as $test)
		{
			$xml_out .= <<< XML
<test status="$test[status]" file="$test[file]" type="$test[testtype]">
XML;

			// Since more info is available for failed tests
			if(strtolower($test['status']) == 'fail')	
			{
				$diff = base64_encode($test['difference']);
				$exp = base64_encode($test['expected']);
				$out = base64_encode($test['output']);

				$xml_out .= <<< XML
<difference>$diff</difference>
<expected>$exp</expected>
<output>$out</output>
XML;

			}

			$xml_out .= <<< XML
</test>
XML;
		}

		$xml_out .= <<< XML
</tests>
XML;

	} // End check for tests definition

	// todo: output leak type (i.e. native or unicode)
	if(isset($xmlarray['valgrind']))
	{
		$xml_out .= <<< XML
<valgrind>
XML;

	       	foreach($xmlarray['valgrind'] as $valgrind)
		{	

			$xml_out .= <<< XML
<leak file="$valgrind[file]" type="$valgrind[testtype]">
XML;

/*
			$xml_out .= <<< XML
<leak file="$valgrind[file]" type="$valgrind[testtype]">
<script><![CDATA[' . $valgrind[script] . ']]></script>
<report><![CDATA[' . $valgrind[report] . ']]></report>

XML;
*/

			$xml_out .= 
				'<script>'
				.'<![CDATA['
				.$valgrind['script']
				.']]>'
				.'</script>'."\n"
				.'<report>'
				.'<![CDATA['
				.$valgrind['report']
				.']]>'
				.'</report>'."\n";

			$xml_out .= <<< XML
</leak>
XML;
		}

		$xml_out .= <<< XML
</valgrind>
XML;
	} // End check for valgrind definition

	// End XML
	$xml_out .= <<< XML
</builddata>
</build>
XML;

	if(file_put_contents($tmpdir.'/build.xml', $xml_out))
	{
		echo 'XML file written'."\n";
	}

	// todo: this file would be gzipped and then sent to server
	file_put_contents($outdir.'/build.xml', $xml_out);

	// Get the array for output (testing only)
	ob_start();

	print_r($xmlarray);

	$contents = ob_get_clean();

	file_put_contents($outdir.'/build.txt', $contents);

	// Setup for data post to remote server
	$contents = bzcompress($xml_out);
	$contents = base64_encode($contents);

	// todo: only for testing the size of file, actual decompression needs to be into a string

	file_put_contents($tmpdir.'/build.bz2', $contents);

	// Set up the post data
	$postdata = array('username' => $server_submit_user,
        	                'password' => $server_submit_pass,
                	        'contents' => $contents
	        );


	$result = file_get_contents($server_submit_url, false, 
		stream_context_create(
			array('http' =>
				array(
				'method'=>'POST',
				'headers'=> 'Content-type: application/x-www-form-urlencoded', 
				'content' => http_build_query($postdata)
				)
			)
		)
	);

	if($result === false)
		echo 'not posted';
	else
        	echo 'posted';
	echo "\n";
}

?>
