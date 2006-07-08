<?php

$write = html_header();

$write .= "<p>Welcome! Here you'll find compile logs, valgrind logs and reports of PHP regression tests.</p>\n";

// test summary
$write .= "<p>&nbsp;</p>\n";
$write .= "<h2>Test Summary</h2>\n";
$write .= "<pre>$test_summary</pre>\n";

// valgrind summary
$write .= "<p>&nbsp;</p>\n";
$write .= "<h2>Valgrind Report Summary</h2>\n";
$write .= '<pre>' . file_get_contents("$tmpdir/php_valgrind_summary.txt") . "</pre>\n";

// extensions
$write .= "<p>&nbsp;</p>\n";
$write .= "<h2>Providing testing for the following extensions</h2>\n";
$modules = `$phpdir/sapi/cli/php -m`;
preg_match_all('/^[^\[\n]+/m', $modules, $modules);
$write .= '<p><ul><li>' . implode('</li><li>', $modules[0]) . "</li></ul></p>\n";

//links
$write .= "<p>&nbsp;</p>\n";
$write .= <<< HTML
<h2>Available Reports:</h2>
<p><a href="compile_errors.php">Compile errors/warnings</a><br/>
<a href="lcov_html/">Test Coverage Report</a><br/>
<a href="tests.php">Test Failures</a><br/>
<a href="valgrind.php">Valgrind Reports</a><br/>
<a href="system.php">System Info</a>
</p>
HTML;

$write .= html_footer();
file_put_contents("$outdir/index.php", $write);

?>
