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

$inputfile = "./$version/check_params.inc";
$raw_data  = @file_get_contents($inputfile);
$data      = unserialize($raw_data);
$total     = 0;

if (!$raw_data) {
	$content = "<p>Sorry, but this data isn't available at this time.</p>";
	return;

} elseif ($data) {

	$content = <<< HTML
<table border="1">
HTML;

	foreach ($data as $path => $fileentry) {

		$content .= <<< HTML
<tr>
 <td colspan="3" align="center"><b>$path</b></td>
</tr>
<tr>
 <td><b>Line</b></td>
 <td><b>Function</b></td>
 <td><b>Message</b></td>
</tr>
HTML;

		foreach ($fileentry as $entry) {
			$line     = $entry[0];
			$function = $entry[1];
			$msg      = htmlspecialchars($entry[2]);
			$lxrlink  = make_lxr_link($path, $line);
			$cvslink  = make_cvs_link($path, $line);

			++$total;

			$content .= <<< HTML
<tr>
 <td>$cvslink $lxrlink</td>
 <td>$function</td>
 <td>$msg</td>
</tr>
HTML;

		}
	}

	$content = <<< HTML
<p><b>$total possible problems found:</b></p>
$content

</table>
<p><strong>Note</strong>: the lxr links are made against the HEAD branch, and thus the line numbers may be incorrect.</p>
HTML;

} else {
	$content = "<p>Congratulations! Currently there are no compiler warnings/errors!</p>\n";
}

$content .= footer_timestamp(@filemtime($inputfile));
