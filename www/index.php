<?php 
// Include the site API
include_once 'site.api.php';

// Initialize the core components
api_init($appvars);

// Define page variables
$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
$appvars['page']['head'] = 'PHP: Test and Code Coverage Analysis';

// Function for displaying the tags
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

// Outputs the site header to the screen
api_showheader($appvars);

?>
<p>
This page is dedicated to automatic PHP code coverage testing. On a regular 
basis current CVS snapshots are being built and tested on this machine. 
After all tests are done the results are visualized along with a code coverage
analysis.
</p>
<p>
<!-- start links -->
<table class="standard" border="1" cellspacing="0" cellpadding="4">
<tr>
<th>TAG</th>
<th>coverage</th>
<th>run-tests</th>
<th>make log</th>
<th>running make</th>
<th>Last Make Status</th>
</tr>
<?php
	
$path = $appvars['site']['basepath'];

// Output PHP versions into a table
foreach($appvars['site']['tags'] as $tag)
{
	echo "<tr>";
	echo "<th align='left'>$tag</th>";
	show_link($tag, 'lcov', $path,'index.php');
	show_link($tag, 'run_tests', $path, 'run-tests.html.inc');
	$l_time = show_link($tag, 'make_log', $path, 'make.log');
	show_link($tag, 'make_log_new', $path, 'make.log.new', $l_time);

	// added for last make status
	echo "<td>";
	$filepath = $tag.'/last_make_status.inc';
	$fh = @fopen($filepath, 'r');
	// Read file contents
	$content = @fread($fh, filesize($filepath));
	if(($content === false) || ($content == ''))
	{
		$make_last_status = 'N\A';
	}
	elseif(trim($content) == strtolower('pass'))
	{
		 $make_last_status = 'Success';
	}
	else
	{
		 $make_last_status = 'Failed - check Compile Results';
	}
	echo $make_last_status."</td>\n";
	// End additions
	echo "</tr>\n";
}
?>
</table>
<!-- end links -->
</p>
<h1>ToDo</h1>
<p>
<ul>
<li>Integrate gcov testing into PHP_4_4 (<a href='PHP_4_4-gcov-20060323.diff.txt.bz2'>patch</a>)</li>
<li>Running the tests from a cron job (5.1 takes ~25 hours, HEAD takes ~52 hours).</li>
<li>Enable all core extensions.</li>
<li>Integrate PECL extensions.</li>
<li>Integrate PEAR classes.</li>
<li>Integrate external components.</li>
</ul>
</p>
</td>
</tr>

<?php
// Outputs the site footer to the screen
api_showfooter($appvars);

