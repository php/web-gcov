<?php
// Project: PHP QA GCOV Website
// File: API Site Functions
// Desc: contains function definitions for use by the PHP GCOV system
	
// todo: if only shown on index.php move to index.php instead
/*
function show_link($tag, $link, $path, $file = NULL, $l_time = false)
{

	if (is_null($file))
	{
		$file = $link;
	}
	$m_time = @filemtime($path. "/$tag/$file");
	if (file_exists($path. "/$tag/$file") && ($l_time === false || $m_time > $l_time))
	{
		echo '<td align="left">'
		.'<a href="viewer.php?version='.$tag.'&amp;func='.$link.'">'
		.date("M d Y H:i:s", $m_time).'</a></td>';
	}
	else
	{
		echo "<td>&nbsp;N/A</td>";
	}
	return $m_time;
}
*/
