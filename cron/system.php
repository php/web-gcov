<?php

$write = html_header('System Info');

// configure (linker?)
$write .= "<h2>Configure Used:</h2>\n";
$config = file("$phpdir/config.nice");
$config = array_slice($config, 4); //remove inital comments
$configureinfo = implode('', $config);
$write .= "<pre>".$configureinfo."</pre>\n";

// Compiler
$write .= "<h2>Compiler Used:</h2>\n";
$compiler = explode("\n", `cc --version`);
$compilerinfo = $compiler[0];
$write .= '<p>'.$compilerinfo. "</p>\n";

// OS
// todo:os info should work for Windows and other systems without uname
$write .= "<h2>Operating System:</h2>\n";
$osinfo = `uname -srmi`;
$write .= '<p>'.$osinfo."</p>\n";

// Valgrind (calculated but not displayed)
$valgrind = explode("\n", `valgrind --version`);
$valgrindinfo = $valgrind[0];


$write .= html_footer();

file_put_contents("$outdir/system.inc", $write);
?>
