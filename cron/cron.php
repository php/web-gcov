<?php

if ($argc != 6) die("cron.php requires 5 arguments: [tmp] [out] [phpsrc] [makestatus] [phpversion]\n\n");

define('CRON_PHP',true);

$tmpdir  = $argv[1];
$outdir  = $argv[2];
$phpdir  = $argv[3];
$makestatus = $argv[4]; // make status from bash script (fail or pass)
$phpver = $argv[5];
$workdir = dirname(__FILE__);

// Initialize core variables
$build_time = -1;	// Total time required for build (build_time.php)

$totalnumerrors = 0; 	// Total number of errors (compile_errors.php)
$totalnumwarnings = 0;	// Total number of warnings (compile_errors.php)

$totalnumleaks = 0;	// Total number of memory leaks (valgrind.php)
$totalnumfailures = 0;	// Total number of test failures (tests.php)

$linkerinfo = 'N/A';	// Information regarding linker (system.php)
$compilerinfo = 'N/A';	// Information regarding compiler (system.php)
$osinfo = 'N/A';	// Information regarding operating system (system.php)

$version_id = 0;

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

// Get version ID for current version

try
{
	$sql = 'SELECT version_id FROM versions WHERE version_name = ?';
	$stmt = $mysqlconn->prepare($sql);
	$stmt->execute(array($phpver));
	$version_id = $stmt->fetchColumn();
}
catch(PDOException $e)
{
	// if error occurs log, output and ensure the version_id is not legimate
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
	        require $workdir.'/tests.php';
	        require $workdir.'/build_time.php';

		// todo: if a Sunday, call graphs otherwise skip
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


}
else
{
	// we may not want to log this as the PHP version may have been made up
}

file_put_contents($outdir.DIRECTORY_SEPARATOR.'last_make_status.inc', $makestatus);
#todo: if write fails we need to do something about it

// todo: start of XML output

//$xml_array = array('php_build.log','php_test.log');


// Parse valgrind output file
$valgrind_serialized = file_get_contents("$outdir/valgrind.txt") or $valgrind_serialized = null;

if(!is_null($valgrind_serialized))
{
	//$xml_array['valgrind'] = array();
	$xmlarray['valgrind'] = unserialize($valgrind_serialized);

	//$valgrind = unserialize($valgrind_serialized);

}
else
{
	echo 'no valgrind to read'."\n";
}

$xml_out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
	.'<build>'."\n"
	.'<buildinfo>'."\n"
	.'<username>johndoe'.'</username>'."\n"
	.'<version>'.$phpver.'</version>'."\n"
	.'<buildstatus>'.$makestatus.'</buildstatus>'."\n"
	.'<compiler>'.$compilerinfo.'</compiler>'."\n"
	.'<linker>'.$linkerinfo.'</linker>'."\n"
	.'<os>'.$osinfo.'</os>'."\n"
	.'</buildinfo>'."\n"
	.'<builddata>'."\n";

$xml_search_for = array($phpdir);
$xml_replace_with = array('');


if(isset($xmlarray['compile_results']))
{
	$xml_out .= '<compile_results>'."\n";
	//$xml_out .= '<files>'."\n";

	foreach($xmlarray['compile_results'] as $res)
	{
		$xml_out .= '<message file="'.$res['file'].'" '
			.'function="'.$res['function'].'" '
			.'line="'.$res['line'].'" '
			.'type="'.$res['type'].'">'
			.$res['msg'].'</message>'."\n";
	}
	
	$xml_out .= '</compile_results>'."\n";
} // End check for compile results


if(isset($xmlarray['tests']))
{
	$xml_out .= '<tests>'."\n";

	foreach($xmlarray['tests'] as $test)
	{
		$xml_out .= '<test status="'.$test['status'].'" '
			.'file="'.$test['file'].'">';

		if(strtolower($test['status']) == 'fail')
		{
			$xml_out .= "\n"
				.'<expected>'
				.htmlspecialchars($test['expected'])
				.'</expected>'."\n"
				.'<output>'
				.htmlspecialchars($test['output'])
				.'</output>'."\n"
				.'<difference>'."\n"
				.htmlspecialchars($test['difference'])
				.'</difference>';
		}
		else
		{
		}
		$xml_out .= '</test>'."\n";
	}

	$xml_out .= '</tests>'."\n";

} // End check for tests


$xml_out .= '</builddata>'."\n"
		.'</build>'."\n";

if(file_put_contents($tmpdir.'/build.new.xml', $xml_out))
{
	echo 'XML file written'."\n";
}


file_put_contents($outdir.'/build.new.xml', $xml_out);



ob_start();

print_r($xmlarray);

$contents = ob_get_clean();

file_put_contents($outdir.'/build.new.txt', $contents);


//require $workdir.'/make-index.php'; // make the index file
?>
