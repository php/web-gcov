<?php

$write = html_header('System Info');

// configure
$write .= "<h2>Configure used:</h2>\n";
$config = file("$phpdir/config.nice");
$config = array_slice($config, 4); //remove inital comments
$write .= "<pre>". implode('', $config) ."</pre>\n";

// compiler
$write .= "<h2>Compiler used</h2>\n";
$compiler = explode("\n", `gcc --version`);
$write .= '<p>' . $compiler[0] . "</p>\n";

// SO
$write .= "<h2>Operating System:</h2>\n";
$write .= '<p>' . `uname -srmi` . "</p>\n";

$write .= html_footer();

file_put_contents("$outdir/system.inc", $write);
?>
