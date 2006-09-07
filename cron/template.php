<?php
date_default_timezone_set('UTC');

function html_footer()
{
	$timestamp = time();
	$date      = date('r', $timestamp);

	return <<< HTML
<p>&nbsp;</p>
<p align="center"><small>Generated at $date (<?php echo time_diff($timestamp, true); ?> ago)</small></p>

HTML;
}
?>
