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


$inputfile = "./$version/system.inc";
$raw_data  = @file_get_contents($inputfile);
$data      = unserialize($raw_data);

if (!$raw_data) {
	$content = "<p>This data isn't available at this time.</p>\n";
	return;
}

$configureinfo = htmlspecialchars($data[0]);
$compilerinfo  = htmlspecialchars($data[1]);
$osinfo        = htmlspecialchars($data[2]);

$content .= <<< HTML
<h2>Configure</h2>
<pre>$configureinfo</pre>

<h2>Compiler</h2>
<pre>$compilerinfo</pre>

<h2>Operating System</h2>
<pre>$osinfo</pre>
HTML;

$content .= footer_timestamp(@filemtime($inputfile));
