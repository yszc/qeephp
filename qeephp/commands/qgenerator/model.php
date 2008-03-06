<?php
/////////////////////////////////////////////////////////////////////////////
// FleaPHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.fleaphp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 Generator_Model 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package Scripts
 * @version $Id: Model.php 63 2008-01-21 13:17:36Z dualface $
 */

// {{{ includes
require_once dirname(__FILE__) . '/abstract.php';
// }}}

/**
 * Generator_Model 根据应用程序的数据库设置创建需要的 ActiveRecord 对象定义文件
 *
 * @package Scripts
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.0
 */
class Generator_Model extends Generator_Abstract
{
    function execute(array $opts)
    {
        $modelName = array_shift($opts);
        $table_name = array_shift($opts);
        $modelClass = 'Model_' . ucfirst($modelName);
        if ($filename = $this->existsClassFile($modelClass)) {
            echo "Class '{$modelClass}' declare file '{$filename}' exists.\n";
            return -1;
        }

        /**
         * 首先判断需要的表数据入口对象是否存在
         */
        $tableClass = 'Table_' . ucfirst($this->camelName($table_name));
        if ($filename = $this->existsClassFile($tableClass)) {
            echo "Class '{$tableClass}' declare file '{$filename}' exists.\n";
        } else {
            /**
             * 创建需要的表数据入口对象
             */
            require_once dirname(__FILE__) . '/table.php';
            $generator = new Generator_Table($this->module());
            $generator->execute(array($table_name));
        }

        $content = $this->getCode($modelClass, $tableClass, $table_name);
        if ($content !== -1 && !empty($content)) {
            return $this->createClassFile($modelClass, $content);
        } else {
            return -1;
        }
    }

    function getCode($modelClass, $tableClass, $table_name)
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

        $propertiesMapping = array();
        $len = 0;
        $idname = null;

        $dbo = QDBO_Abstract::get_dbo($this->config['dsn']);
        $dbo->connect();
        $meta = $dbo->meta_columns($table_name);

        foreach ($meta as $field) {
            $prop = $this->camelName($field['name']);
            $len = strlen($prop) > $len ? strlen($prop) : $len;
            $field['phpType'] = $typeMap[$field['simpleType']];
            $propertiesMapping[$prop] = $field;
            if ($field['simpleType'] == 'R') {
                $idname = $prop;
            }
        }

        $viewdata = array(
            'modelClass' => $modelClass,
            'tableClass' => $tableClass,
            'mapping'    => $propertiesMapping,
            'len'        => $len,
            'idname'     => $idname,
        );

        return $this->parseTemplate('model', $viewdata);
    }
}
