<?php

$cmd = isset($argv[1]) ? $argv[1] : 'help';
$cmd = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $cmd));
array_shift($argv);
$argc--;

define('QEE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'QEE');

$filename = QEE_DIR . "/{$cmd}.php";
if (file_exists($filename)) {
    return require($filename);
} else {
    return require(QEE_DIR . '/help.php');
}
