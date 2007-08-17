<?php

require_once dirname(__FILE__) . '/Abstract.php';

class Generator_Model extends Generator_Abstract
{
    public function __construct()
    {
        if (!function_exists('mysql_connect')) {
            dl('php_mysql.' . PHP_SHLIB_SUFFIX);
        }
    }

    public function run($name, $opts)
    {
        $opt = reset($opts);
        if (strpos($opt, '-class=') === 0) {
            $table = $this->_fromTableClass(substr($opt, 7));
        } else {
            $table = $this->_fromTable($opt);
        }
        echo $this->_outputCode($name, $table);
    }

    protected function _fromTableClass($tableClass)
    {
        __TRY();
        $table = FLEA::getSingleton($tableClass);
        $ex = __CATCH();
        return $table;
    }

    protected function _fromTable($tableName)
    {
        FLEA::loadClass('FLEA_Db_TableDataGateway');
        $opts = array(
            'tableName' => $tableName,
        );
        __TRY();
        $table = new FLEA_Db_TableDataGateway($opts);
        $ex = __CATCH();
        return $table;
    }

    protected function _outputCode($className, FLEA_Db_TableDataGateway $table)
    {
        static $typeMap = array(
            'C' => 'string',
            'X' => 'string',
            'B' => 'string',
            'N' => 'float',
            'D' => 'string',
            'T' => 'int',
            'L' => 'boolean',
            'I' => 'int',
            'R' => 'int',
        );

        $tableClass = get_class($table);
        $propertiesMapping = array();
        $len = 0;
        $propPrimaryKey = null;
        foreach ($table->meta as $field) {
            $prop = $this->_camelName($field['name']);
            $len = strlen($prop) > $len ? strlen($prop) : $len;
            $field['phpType'] = $typeMap[$field['simpleType']];
            $propertiesMapping[$prop] = $field;
            if ($field['simpleType'] == 'R') {
                $propPrimaryKey = $prop;
            }
        }

        ob_start();
        include(dirname(__FILE__) . '/Templates/model.php');
        return ob_get_clean();
    }
}
