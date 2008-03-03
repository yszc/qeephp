<?php

$generator = new Generator($argv);
$generator->run();

class Generator
{
    /**
     * 保存命令行参数
     *
     * @var array
     */
    protected $argv;

    protected $generatorList = array(
        'controller' => array(
            'argc'  => 1,
            'class' => 'Generator_Controller',
            'help'  => '<controller name>',
        ),
        'table'      => array(
            'argc'  => 1,
            'class' => 'Generator_Table',
            'help'  => '<database table name>',
        ),
        'model'      => array(
            'argc'  => 2,
            'class' => 'Generator_Model',
            'help'  => '<class name> <database table name>',
        ),
    );

    function __construct($argv)
    {
        array_shift($argv);
        $this->argv = $argv;
    }

    function run()
    {
        $argc = count($this->argv);
        if ($argc < 2) {
            $this->help();
        }

        $module = strtolower($this->argv[0]);
        $type = strtolower($this->argv[1]);

        if (!isset($this->generatorList[$type])) {
            $this->help();
            return;
        }

        if ($argc - 1 < $this->generatorList[$type]['argc']) {
            echo "php generate.php <module name> {$type} {$this->generatorList[$type]['help']}\n";
            echo "\n";
            return;
        }

        $argv = array_splice($this->argv, 2);

        $class = $this->generatorList[$type]['class'];
        Q::loadClass($class, dirname(__FILE__));

        $generator = new $class($module);
        $generator->execute($argv);
    }

    function help()
    {
        echo <<<EOT

    php generator.php <module name> <type> <....>

example:

EOT;

        foreach ($this->generatorList as $type => $generator) {
            echo "    php generate.php <module name> {$type} {$generator['help']}\n";
        }
        echo "\n";

        exit(-1);
    }
}
