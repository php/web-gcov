<?php

function html_header($title = '') {

	$h1 = $title ? $title : 'PHP Q&amp;A Server';
	return <<< HTML

<html>
<head>
<title> :: PHP Q&amp;A server :: $title</title>
</head>
<body>
<h1 align="center">$h1</h1>

HTML;
}


function html_footer($closehtml=true) 
{
	date_default_timezone_set('UTC');
	$timestamp = time();
	$date      = date('r', $timestamp);
	$current_time_zone = date_default_timezone_get();

	return <<< HTML
<?php
	function time_diff() 
	{
		date_default_timezone_set('UTC');
		\$diff = time() - $timestamp;

		if (\$diff < 60) 
		{
			echo "\$diff seconds";
		}
		elseif(\$diff < 3600) 
		{
			echo intval(\$diff/60) ." minutes";
		} else 
		{
			echo intval(\$diff/3600) ." hours";
		}
	}
?>

<p>&nbsp;</p>
<p align="center"><small>Generated at $date $current_time_zone (<?php time_diff(); ?> ago)</small></p>

<?php if(\$closehtml)
{
	echo '</body>'."\n".'</html>'."\n";
}
?>

HTML;
}
?>
