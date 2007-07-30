<?php

$testsRootDir = dirname(__FILE__) . '/testcases';
$testFiles = fetchTestsFromDirs($testsRootDir);
array_shift($argv);
$args = implode(' ', $argv);

$prefixlen = strlen($testsRootDir);
foreach ($testFiles as $filename) {
    $filename = realpath($filename);
    $basename = basename($filename);
    $class = substr(basename($filename, '.php'), 4);
    $path = substr($filename, $prefixlen, strlen($filename) - $prefixlen - strlen($basename));
    $path .= $class;
    $class = 'Test' . str_replace(DIRECTORY_SEPARATOR, '_', $path);
    echo "run test: {$class} \"{$filename}\"\n";

    $cmd = "phpunit {$args} {$class} \"{$filename}\"";
    $return = 0;
    system($cmd, $return);
    echo "\n--------------------------------------------------------------------------------\n\n";
    if ($return) { break; }
}

function fetchTestsFromDirs($dir)
{
    $tests = array();
    foreach (glob($dir . '/*') as $name) {
        if (is_dir($name)) {
            $tests = array_merge($tests, fetchTestsFromDirs($name));
        } else {
            if (substr(basename($name), 0, 4) == 'test') {
                $tests[] = $name;
            }
        }
    }
    return $tests;
}
