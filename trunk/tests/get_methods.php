<?php
if (!isset($argv[1])) {
    exit;
}
$class = $argv[1];
if (substr($class, 0, 4) == 'FLEA') {
    $testClass = 'Test' . substr($class, 4);
} else {
    $testClass = 'Test_' . $class;
}

$file = str_replace('_', '/', $class);
require dirname(__FILE__) .  '/../library/' . $file . '.php';

$methods = get_class_methods($class);
echo <<<EOT
<?php

class {$testClass}
{

EOT;

foreach ($methods as $m) {
    echo <<<EOT
    public function test_{$m}()
    {
    }


EOT;
}

echo <<<EOT
}

EOT;
