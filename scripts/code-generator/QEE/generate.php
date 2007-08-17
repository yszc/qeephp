<?php
array_shift($argv);
$argc--;

if ($argc < 2) {
    generate_help();
}

$type = strtolower($argv[0]);
$name = $argv[1];
array_shift($argv);
array_shift($argv);
$argc-=2;

switch ($type) {
case 'controller':
    require dirname(__FILE__) . '/Generator/Controller.php';
    $generator = new Generator_Controller();
    break;
case 'table':
    require dirname(__FILE__) . '/Generator/Table.php';
    $generator = new Generator_Table();
    break;
case 'model':
    require dirname(__FILE__) . '/Generator/Model.php';
    $generator = new Generator_Model();
    break;
default:
    echo <<<EOT
Invalid type: {$type}


EOT;

    generate_help();
}

return $generator->run($name, $argv);


function generate_help()
{
    echo <<<EOT
php do.php qee generate <type> <name>

example:
    generate controller Controller_Manage
    generate table Table_Members members
    generate model Member :class Table_Members
    generate model Product :table products

EOT;

    exit(-1);
}