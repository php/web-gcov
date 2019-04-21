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

// available graph periods
$graph_mode_array = array(
	'Week'  => 'Weekly',
	'Month' => 'Monthly',
	'Year'  => 'Yearly'
);

// available graph types
$graph_types_array = array(
	'codecoverage',
	'failures',
	'memleaks',
	'warnings'
);


$mode = isset($_GET['mode']) ? $_GET['mode'] : '';

if(isset($graph_mode_array[$mode])) {

	$appvars['page']['title'] = 'PHP: '.$version.' '.$graph_mode_array[$mode] . ' Graphs';
	$appvars['page']['head'] = $graph_mode_array[$mode]. ' Graphs';

	$content .= '<p>The following images show the changes in code coverage, compile warnings, memory leaks and test failures:</p>';

	$graph_count = 0;

	foreach($graph_types_array as $graph_type) {
		$graph = "$version/graphs/{$graph_type}_$mode.png";

		if (file_exists($graph)) {
			$content .= '<img src="'.$graph.'" />&nbsp;'."\n";

			if(++$graph_count == 2) {
				$content .= "<br />\n";
			}
		}
	}

} else {
	$content .= <<< HTML
<p>Select the period of time you wish to view as a graphical progression:</p>
HTML;

	foreach($graph_mode_array as $idx => $graph_mode) {
		$content .= <<< HTML
<a href="viewer.php?version=$version&func=graph&mode=$idx">$graph_mode</a><br />
HTML;
	}
}
