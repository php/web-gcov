<?php

function html_footer()
{
	date_default_timezone_set('UTC');
	$timestamp = time();
	$date      = date('r', $timestamp);

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
<p align="center"><small>Generated at $date (<?php time_diff(); ?> ago)</small></p>

HTML;
}
?>
