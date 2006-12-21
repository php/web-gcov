<?php
/*
  +----------------------------------------------------------------------+
  | PHP QA GCOV Website                                                  |
  +----------------------------------------------------------------------+
  | Copyright (c) 2005-2006 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Nuno Lopes <nlopess@php.net>                                 |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

if (!defined('IN_GCOV_CODE')) exit;

$inputfile = "./$version/fail.inc";
$raw_data  = @file_get_contents($inputfile);
$data      = unserialize($raw_data);
$old_dir   = '';


if (!$raw_data) {
	$content = "<p>Sorry, but this data isn't available at this time.</p>";
	return;

} elseif (isset($_GET['file'])) {
	$file = $_GET['file'];

	$appvars['page']['title'] = "PHP: $version Test Failure Report for $file";
	$appvars['page']['head']  = "Test Failure Report for $file";

	if (isset($data[$file])) {
		$data   = $data[$file];
		$diff   = htmlspecialchars($data[2]);
		$exp    = htmlspecialchars($data[3]);
		$output = htmlspecialchars($data[4]);
		$script = highlight_string($data[5], true);

		$content = <<< HTML
<h2>Script</h2>
$script
<h2>Expected</h2>
<pre>$exp</pre>
<h2>Output</h2>
<pre>$output</pre>
<h2>Diff</h2>
<pre>$diff</pre>
HTML;

	} else {
		$content = "<p>Invalid file ID.</p>\n";
	}

} elseif ($data) {

	$content = '<p><b>'.count($data) . " tests failed:</b></p>\n";

	$content .= <<< HTML
<table border="1">
HTML;

	foreach ($data as $path => $entry) {
		$dir     = dirname($path);
		$file    = basename($path);
		$urlfile = htmlspecialchars(urlencode($path));
		$type    = $entry[0];
		$title   = $entry[1];

		if ($dir !== $old_dir) {
			$old_dir = $dir;
			$content .= <<< HTML
<tr>
 <td colspan="3" align="center"><b>$dir</b></td>
</tr>
<tr>
 <td><b>File</b></td>
 <td><b>Type</b></td>
 <td><b>Name</b></td>
</tr>
HTML;
		}

		$content .= <<< HTML
<tr>
 <td><a href="/viewer.php?version=$version&amp;func=tests&amp;file=$urlfile">$file</a></td>
 <td>$type</td>
 <td>$title</td>
</tr>
HTML;

	}

	$content .= <<< HTML
</table>
HTML;

} else {
	$content = "<p>Congratulations! Currently there are no test failures!</p>\n";
}

$content .= footer_timestamp(@filemtime($inputfile));
