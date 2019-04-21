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

$inputfile = "./$version/compile_results.inc";
$raw_data  = @file_get_contents($inputfile);
$data      = unserialize($raw_data);
$stats     = array('warning' => 0, 'error' => 0);


if (!$raw_data) {
	$content = "<p>Sorry, but this data isn't available at this time.</p>";
	return;

} elseif ($data) {

	$content = <<< HTML
<table border="1">
HTML;

	foreach ($data as $path => $fileentry) {
		$path = htmlspecialchars($path);

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
			$function = htmlspecialchars($entry[0]);
			$line     = htmlspecialchars($entry[1]);
			$type     = $entry[2];
			$msg      = htmlspecialchars($entry[3]);
			$lxrlink  = make_lxr_link($version, $path, $line);
			$cvslink  = make_cvs_link($path, $line);
			$funclink = make_lxr_func_link($function);
			$pretty_type = make_error_highlight($type);

			++$stats[$type];

			$content .= <<< HTML
<tr>
 <td>$cvslink $lxrlink</td>
 <td>$funclink</td>
 <td>$pretty_type: $msg</td>
</tr>
HTML;

		}
	}

	$total = $stats['warning'] + $stats['error'];

	$content = <<< HTML
<p>Number of Errors: $stats[error]<br />
Number of Warnings: $stats[warning]<br />
Total: $total</p>

$content
</table>
HTML;
	$content .= lxr_broken_links_note();

} else {
	$content = "<p>Congratulations! Currently there are no compiler warnings/errors/notes!</p>\n";
}

$content .= footer_timestamp(@filemtime($inputfile));
