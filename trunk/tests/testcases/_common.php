<?php
$testsRootDir = dirname(__FILE__);
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . realpath($testsRootDir . '/../../library'));
define('TEST_SUPPORT_DIR', realpath($testsRootDir . '/../support'));

require_once 'PHPUnit/Framework.php';
