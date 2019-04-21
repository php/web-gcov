<?php
/*
  +----------------------------------------------------------------------+
  | PHP QA GCOV Website                                                  |
  +----------------------------------------------------------------------+
  | Copyright (c) The PHP Group                                          |
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

if (!defined('IN_GCOV_CODE')) exit;

$inputfile = "./$version/skip.inc";
$raw_data  = @file_get_contents($inputfile);
$data      = unserialize($raw_data);


if (!$raw_data) {
	$content = "<p>Sorry, but this data isn't available at this time.</p>";
	return;

} elseif (isset($_GET['file'])) {
	$file = $_GET['file'];

	if (isset($data[$file])) {
		$data   = $data[$file];
		$file   = htmlspecialchars($file);
		$script = highlight_string($data[0], true);
		$reason = htmlspecialchars($data[1] ? $data[1] : '(no reason given)');

		$appvars['page']['title'] = "PHP: $version Skip Report for $file";
		$appvars['page']['head']  = "Skip Report for $file";

		$content = <<< HTML
<h2>Script</h2>
$script
<h2>Reason</h2>
<pre>$reason</pre>
HTML;

	} else {
		$content = "<p>Invalid file ID.</p>\n";
	}

} elseif ($data) {

	$content = '<p><b>'.count($data) . " tests were skipped:</b></p>\n";
	$old_dir = '';

	$content .= <<< HTML
<table border="1">
HTML;

	foreach ($data as $path => $entry) {
		$dir     = htmlspecialchars(dirname($path));
		$file    = htmlspecialchars(basename($path));
		$urlfile = htmlspecialchars(urlencode($path));
		$reason  = htmlspecialchars($entry[1] ? $entry[1] : '(no reason given)');

		if ($dir !== $old_dir) {
			$old_dir = $dir;
			$content .= <<< HTML
<tr>
 <td colspan="2" align="center"><b>$dir</b></td>
</tr>
<tr>
 <td><b>File</b></td>
 <td><b>Reason</b></td>
</tr>
HTML;
		}

		$content .= <<< HTML
<tr>
 <td><a href="/viewer.php?version=$version&amp;func=skip&amp;file=$urlfile">$file</a></td>
 <td>$reason</td>
</tr>
HTML;

	}

	$content .= <<< HTML
</table>
HTML;

} else {
	$content = "<p>Currently there are no skipped tests!</p>\n";
}

$content .= footer_timestamp(@filemtime($inputfile));
