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
  | Author: Daniel Pronych <pronych@php.net>                             |
  |         Nuno Lopes <nlopess@php.net>                                 |
  +----------------------------------------------------------------------+
*/

// This file gathers the essential information regarding the configuration for the build

// Configure Section
$config = file("$phpdir/config.nice");
$config = array_slice($config, 4); //remove inital comments
$configureinfo = implode('', $config);

// Compiler Section
$compiler = explode("\n", `cc --version`);
$compilerinfo = $compiler[0];

// Operating System Section
// Todo: this section need to be revised for systems without the uname command
$osinfo = trim(`uname -srm`);

$system_data = array($configureinfo, $compilerinfo, $osinfo);

file_put_contents("$outdir/system.inc", serialize($system_data));
