<?php

	// Cron script to post files to remote server

/*
function fsockpost($uri, $postdata, $host)
{

	$fsp = fsockopen($host, 80, $errno, $errstr);
	
	if(!$fsp)
	{
		echo $errstr.' ('.$errno.'<br />'."\n";
		//$flag = true;
	}
	else
	{
		$response = '';

		$data = 'POST '.$uri.'  HTTP/1.1'."\r\n"
		.'Host: '.$host."\r\n"
		.'User-Agent: PHPQAGCOV_POST'."\r\n"
		.'Content-Type: application/x-www-form-urlencoded'."\r\n"
		.'Content-Length: '.strlen($postdata)."\r\n"
		.'Connection: close'."\r\n\r\n"
		.$postdata;

		fwrite($fsp, $data);

		while(!feof($fsp))
		{
			$response .= fgets($fsp, 128); // was 128
		}

		$response = explode("\r\n\r\n", $response);
		list($header, $content) = $response;
		//$header = $response[0];
		//$content = $response[1];

		if(!(strpos($header, 
			'Transfer-Encoding: chunked') === false))
		{
			$aux = explode("\r\n", $content);

			for($i = 0; $i < count($aux); $i++)
			{
				if(($i == 0) || (($i%2) == 0))
				{
					$aux[$i] = '';
					$content = implode('', $aux);
				} // End check for value of i
			} // End loop
			return chop($content);
		} // End transfer check
	} // End check for a valid fsock pointer


} // End function definition

$tmpdir  = $argv[1];

$postdata = 
	'username=johndoe '
	.'password=abc '
	.'contents='
	.file_get_contents($tmpdir.'/build.xml.bz2');

fsockpost('http://gcov.aristotle.dlitz.net/post.php', 
//base64_encode($postdata), 
$postdata,
'gcov.aristotle.dlitz.net');

*/

$tmpdir  = $argv[1];
$outdir  = $argv[2];

$contents = file_get_contents($outdir.'/build.xml');

$contents = bzcompress($contents);

$postdata = array('username' => 'johndoe',
			'password' => 'abc',
			'contents' => $contents
	);

$uri = 'http://gcov.aristotle.dlitz.net/post.php';

$result = file_get_contents($uri, false, stream_context_create(array('http'=>array('method'=>'POST','headers'=> 'Content-type: application/x-www-form-urlencoded', 'content' => http_build_query($postdata)))));

if($result === false)
	echo 'not posted';
else
	echo 'posted';
echo "\n";
