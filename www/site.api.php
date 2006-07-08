<?php
// Project: PHP QA GCOV Website
// File: Core API
// Desc: contains core display functions, essential for all pages to include this file
	
// Include core files
include_once 'config.php'; // Includes configuration settings
include_once 'site.funcs.php'; // Includes the site functions

// Application Initialization Function
// appsvars is passed to the script by reference
function api_init(&$appvars = array())
{

	$appvars['site']['mytag'] = null;
	
	// Load tags from a file and trim new line elements from each element
	
	//$appvars['site']['tags'] = array_map('rtrim',file($appvars['site']['phpvtags'])); 

	// start temp -- attempt to share same tag inc file between php and bash
	$filename = 'tags.inc';
	$fh = fopen($filename, 'r');
	$content = fread($fh, filesize($filename));
	fclose($fh);

	$content = trim($content);

	$elements = explode("\n", $content);
	$tagarray = array();
	foreach($elements as $element) 
	{
        	$elementname = explode(' ', $element);
	        $appvars['site']['tags'][$elementname[0]] = $elementname[0];
	}	
	// end temp
	
	// Creates instances such as the database connection, if needed

	$appvars['page']['headtitle'] = 'PHP: Test and Code Coverage Analysis';
}

// Application Header Function
function api_showheader($appvars=array())
{

// start content from main.inc
//$tags = array('PHP_4_4', 'PHP_5_1', 'PHP_5_2', 'PHP_HEAD'); $mytag = NULL;
if (isset($maindir))
{
	$sub = substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($maindir) + 1);
	$pos = strpos($sub, '/');
	if ($pos)
	{
		$mytag = substr($sub, 0, $pos);
	}
}
// end content from main.inc

/*	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
*/

?><html>
<head>
<title><?php
	// Output the header for the web browser title
	if(isset($appvars['page']['title']))
	{
		echo $appvars['page']['title'];
	}
	else
	{
		echo 'PHP: Test and Code Coverage Analysis';
	}
?></title>
<link rel="stylesheet" href="style.css" />
<link rel="shortcut icon" href="favicon.ico" />
</head>
<body bgcolor="#ffffff" text="#000000" link="#000099" alink="#0000ff" vlink="#000099">
<?php // end content from header.inc ?>
	
<!-- start header -->
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr bgcolor="#9999cc">
<td rowspan="3" align="center" valign="top" width="126"><a href="http://php.net/"><img src="/images/php.gif" alt="PHP" width="120" height="67" hspace="3" vspace="0" /></a></td>
<td valign="top"><img src="/images/spacer.gif" width="1" height="17" border="0" alt="" />&nbsp;</td>
</tr>
<tr bgcolor="#9999cc">
<td align="left" class="top" valign="middle">&nbsp;<?php 
	
	// header title to the right of the logo
	if(isset($appvars['page']['headtitle']))
	{
		echo $appvars['page']['headtitle'];
	}
	else
	{
		echo 'PHP: Test and Code Coverage analysis';
	}	
?></td>
</tr>
<tr bgcolor="#9999cc">
<td align="right" valign="bottom"><img src="/images/spacer.gif" width="1" height="15" border="0" alt="" /><a href="http://php.net/downloads.php" class="header small">downloads</a> | <a href="http://qa.php.net" class="header small">QA</a> | <a href="http://php.net/docs.php" class="header small">documentation</a> | <a href="http://php.net/FAQ.php" class="header small">faq</a> | <a href="http://php.net/support.php" class="header small">getting help</a> | <a href="http://php.net/mailing-lists.php" class="header small">mailing lists</a> | <a href="http://bugs.php.net/" class="header small">reporting bugs</a> | <a href="http://php.net/sites.php" class="header small">php.net sites</a> | <a href="http://php.net/links.php" class="header small">links</a> | <a href="http://php.net/my.php" class="header small">my php.net</a>&nbsp;</td>
</tr>
<tr><td colspan="2" bgcolor="#000000" height="1"><img src="gfx/spacer.gif" width="1" height="1" border="0" alt="" /></td></tr>
<tr><td colspan="2" bgcolor="#7777cc" class="header small">&nbsp;</td></tr>
<tr><td colspan="2" bgcolor="#000000" height="1"><img src="gfx/spacer.gif" width="1" height="1" border="0" alt="" /></td></tr>


</table>
<!-- end header -->	

<?php // end content from header.inc ?>
	
<!-- start outer -->
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td align="left" valign="top" width="120" bgcolor="#f0f0f0">
<?php // start content from sidebar.inc ?>

<!-- start sidebar -->
<table class='sidebartoc' width="100%" cellpadding="2" cellspacing="0" border="0">
<tr valign="top"><td class="sidebartoc">
<ul id="sidebartoc">
<li class="header home"><a href="/">PHP&nbsp;GCOV</a></li>
<?php
$cnt = count($appvars['site']['tags']);
foreach($appvars['site']['tags'] as $tag)
{

	$cls = '';
	if (!--$cnt && isset($appvars['site']['mytag']))
	{
		$cls = 'last';
	}
	if ($tag == $appvars['site']['mytag'])
	{
		if (strlen($cls))
		{
			$cls .= ' ';
		}
		$cls .= 'active';
	}
	if (strlen($cls))
	{
		$cls = " class='$cls'";
	}	

	echo "<li$cls><a href='viewer.php?version=$tag&amp;func=lcov'>$tag</a></li>\n";
	
	//echo "<li$cls><a href='/$tag'>$tag</a></li>\n";
}

if (isset($appvars['site']['mytag']))
{
	$subitems = array(
		'coverage'  => 'lcov',
		//'Run Tests' => 'run_tests',
		//'Make Log'  => 'make_log',
		//'build.sh'  => 'build',
		'compile-results' => 'compile_results',
		'system' => 'system',
		'compile-failures' => 'tests',
		'valgrind' => 'valgrind'
		);

	foreach($subitems as $item => $href)
	{
		$cls = " class='small'";
		// was as below:
		//echo "<li$cls><a href='viewer.php?version=$tag&amp;func=$href'>$item</a></li>\n";
		// changed to following: (changes by Daniel Pronych on Jun 9)
		echo "<li$cls><a href='viewer.php?version={$appvars['site']['mytag']}&amp;func=$href'>$item</a></li>\n";
	}
}

?>
</ul>
</td>
</tr>
</table>
<!-- end sidebar -->

<?php // end content from sidebar.inc ?>

</td>
<td bgcolor="#cccccc" background="/images/checkerboard.gif" width="1"><img src="/images/spacer.gif" width="1" height="1" border="0" alt="" /></td>
<td align="left" valign="top">
<table cellpadding="10" cellspacing="0" width="100%"><tr><td align="left" valign="top">

<!-- start content -->
<?php
?>
<h1><?php
	// page header that starts the content section
	if(isset($appvars['page']['head']))
	{
		echo $appvars['page']['head'];
	}
	else
	{
		echo 'PHP: Test and Code Coverage analysis';
	}
	
?></h1>
<?php
	// End header output, content begins next
}

// Application Footer function
function api_showfooter($appvars = array())
{
?>

</table>
<!-- end content -->
</td>
</tr>
</table>
<!-- end outer -->

<table border="0" cellspacing="0" cellpadding="6" width="100%">
<tr valign="top" bgcolor="#cccccc">
<td><small><a href="http://php.net/copyright.php">Copyright &copy; 2005-<?php echo date("Y"); ?> The PHP Group</a><br />All rights reserved.</small></td>
<td align="right">&nbsp;</td>
</tr>
</table>
</body>
</html>
<?php
	// End content footer
}
