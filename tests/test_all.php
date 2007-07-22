<?php
ini_set('include_path', '.;' . realpath(dirname(__FILE__) . '/../library'));

require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');

define('TEST_SUPPORT_DIR', realpath(dirname(__FILE__) . '/support'));
$testsRootDir = dirname(__FILE__) . '/testcases';
$testFiles = fetchTestsFromDirs($testsRootDir);

$test = new TestSuite('All tests');
foreach ($testFiles as $filename) {
    $test->addTestFile($filename);
}
$test->run(new HtmlReporter());

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
