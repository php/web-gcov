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
  | Author: Daniel Pronych <pronych@php.net>                             |
  |         Nuno Lopes <nlopess@php.net>                                 |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

// File to process memory leaks detected by valgrind

// $data: contains the contents of $tmpdir/php_test.log

$leak_re = '/LEAK(:(?P<testtype>[a-z|A-Z]))? (?P<title>.+) \[(?P<file>[^\]]+)\]/';
preg_match_all($leak_re, $data, $leaks, PREG_SET_ORDER);

$valgrind = array();

foreach ($leaks as $test) {

	$base = "$phpdir/".substr($test['file'],0,-4);

	$dir   = dirname($test['file']);
	$file  = basename($test['file']);
	$title = $test['title'];

	if(isset($test['testtype']) && strtolower($test['testtype']) == 'u') {
		$type = 'Unicode';
		$report_file = $base.'u.mem';
	} else {
		$type = 'Native';
		$report_file = $base.'mem';
	}

	$report = @file_get_contents($report_file);
	$script = @file_get_contents($base.'php');

	$valgrind[$test['file']] = array($title, $type, $script, $report);
}

// sort by filename
ksort($valgrind);

$totalnumleaks = count($leaks);

file_put_contents("$outdir/valgrind.inc", serialize($valgrind));
