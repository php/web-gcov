<?php

$write = html_header('System Info');

// configure (linker?)
$write .= "<h2>Configure used:</h2>\n";
$config = file("$phpdir/config.nice");
$config = array_slice($config, 4); //remove inital comments
$linkerinfo = implode('', $config);
$write .= "<pre>".$linkerinfo."</pre>\n";

// compiler
$write .= "<h2>Compiler used</h2>\n";
$compiler = explode("\n", `gcc --version`);
$compilerinfo = $compiler[0];
$write .= '<p>'.$compilerinfo. "</p>\n";

// OS
$write .= "<h2>Operating System:</h2>\n";
$osinfo = `uname -srmi`;
$write .= '<p>'.$osinfo."</p>\n";

$write .= html_footer();

file_put_contents("$outdir/system.inc", $write);
?>
