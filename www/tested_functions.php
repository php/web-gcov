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
  | Author: Steve Seear <stevseea@php.net>                               |
  +----------------------------------------------------------------------+
*/

if (!defined('IN_GCOV_CODE')) exit;


$inputfile = "./$version/tested_functions.inc";
$fd = @fopen($inputfile, 'r');

if (!$fd) {
	$content = "<p>This data isn't available at this time.</p>\n";
	return;
}

$content = <<< HTML
<script type="text/javascript">
function showHide(id){
     var e = document.getElementById(id);
     if (e.style.display == "") {
          e.style.display = "none";
     } else {
          e.style.display = "";
     }
}
</script>

<p>This table lists core PHP functions and methods and specifies whether or not they are called from 
a PHPT test. A "yes" in this table for a particular method is not an indication of good test coverage
- it just means that that method is called from at least one PHPT test.</p>
<p>The analysis used to generate this table does not differentiate between methods of the same name
belonging to different classes. In cases where such a method call is detected, "verify" is listed
in the Tested column, along with the list of test files containing calls to a method of that name.</p>
HTML;

// fields in the csv file
define("EXTENSION", 0);
define("CLASS_NAME", 1);
define("METHOD_NAME", 2);
define("TESTED", 3);
define("TESTS", 4);


// table header
$content .= "<table border=\"0\">\n";
$content .= "<th align=\"left\">Extension</th>\n";
$content .= "<th align=\"left\">Class</th>\n";
$content .= "<th align=\"left\">Method</th>\n";
$content .= "<th align=\"left\">Tested</th>\n";
$content .= "<th align=\"left\">Test Files</th>\n";

$tests_id = 0;

// print table rows
while (true) {
	$line = fgetcsv($fd);
	if ($line === false) {
		break;
	}

	if (count($line) != 5) {
		continue;
	}

	$extension = $line[EXTENSION];
	$class = $line[CLASS_NAME];
	$method = $line[METHOD_NAME];
	$tested = $line[TESTED];

	$bgcolor = "red";
	$test_files_exist = false;
	if ($tested === "yes") {
		$bgcolor = "green";
		$test_files_exist = true;
	} else if ($tested === "no") {
		$bgcolor = "red";
	} else if ($tested === "verify") {
		$bgcolor = "orange";
		$test_files_exist = true;
	}

	$tests = $line[TESTS];

	$content .= "<tr>";
	$content .= "<td>$extension</td>";
	$content .= "<td>$class</td>";
	$content .= "<td>$method</td>";
	$content .= "<td bgcolor='$bgcolor'>$tested</td>";
	$content .= "<td>";
	if ($test_files_exist) {
		$content .= "<a href=\"#\" onClick='showHide(\"$tests_id\"); return false;'>click to show/hide test files</a>";
		$content .= "<div id='$tests_id' style='display:none'>$tests</div>";
	}
	$content .= "</td>";
	$content .= "</tr>\n";

	++$tests_id;
}

$content .= "</table>\n";
$content .= footer_timestamp(@filemtime($inputfile));
