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

$stmt = $mysqlconn->prepare('SELECT * FROM versions WHERE version_name=?');
$stmt->execute(array($version));
$data = $stmt->fetch();

$buildtime = time_diff($data['version_last_build_time']);

if ($data['version_last_attempted_build_date'] === $data['version_last_successful_build_date']) {
	$buildstatus = '<font color="green"><b>OK</b></font>';
} else {
	$buildstatus = '<font color="red"><b>FAILED!</b></font> Please check the <a href="/viewer.php?version='.$version.'&amp;func=compile_results">compile errors</a>';
}


$stmt = $mysqlconn->prepare('SELECT * FROM local_builds NATURAL JOIN versions WHERE version_name=? ORDER BY build_id DESC LIMIT 1');
$stmt->execute(array($version));
$data = $stmt->fetch();

$c_errors       = $data['build_numerrors'];
$c_warns        = $data['build_numwarnings'];
$coverage       = $data['build_percent_code_coverage'];
$test_failures  = $data['build_numfailures'];
$test_xfailures = $data['build_numxfailures'];
$valgrind       = $data['build_numleaks'];


$content = <<< HTML
<p>
<b>Build Status:</b> $buildstatus<br/>
<b>Last Build Time:</b> $buildtime
</p>
<p>
HTML;

if ($c_errors) {
	$content .= "<b>Compile Errors:</b> $c_errors<br/>\n";
}

$content .= <<< HTML
<b>Compile Warnings:</b> $c_warns<br/>
HTML;

if ($coverage !== NULL) {
	$content .= "<b>Code Coverage:</b> $coverage%<br/>\n";
}

if ($test_failures !== NULL) {
	$content .= "<b>Test Failures:</b> $test_failures<br/>\n";
}

if ($test_xfailures !== NULL) {
	$content .= "<b>Expected Test Failures:</b> $test_xfailures<br/>\n";
}

if ($valgrind !== NULL) {
	$content .= "<b>Valgrind Reports:</b> $valgrind<br/>\n";
}

$content .= "</p>\n";
