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
 * 定义 Generator_Table 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package Scripts
 * @version $Id$
 */

// {{{ includes
require_once dirname(__FILE__) . '/abstract.php';
// }}}

/**
 * Generator_Table 根据应用程序的数据库设置创建需要的表数据入口对象定义文件
 *
 * @package Scripts
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.0
 */
class Generator_Table extends Generator_Abstract
{
    function execute(array $opts)
    {
        $table_name = array_shift($opts);
        $tableClass = 'Table_' . ucfirst($this->camelName($table_name));

        if ($filename = $this->existsClassFile($tableClass)) {
            echo "Class '{$tableClass}' declare file '{$filename}' exists.\n";
            return -1;
        }

        $content = $this->getCode($table_name, $tableClass);
        if ($content !== -1 && !empty($content)) {
            return $this->createClassFile($tableClass, $content);
        } else {
            return -1;
        }
    }

    /**
     * 生成代码
     *
     * @param string $table_name
     * @param string $tableClass
     *
     * @return string
     */
    function getCode($table_name, $tableClass)
    {
        /**
         * 首先判断指定的数据表是否存在
         */
        $dbo = QDBO_Abstract::get_dbo($this->config['dsn']);
        $dbo->connect();
        $tables = $dbo->meta_tables();
        if (!in_array($table_name, $tables)) {
            echo "Database table '{$table_name}' not exists.\n";
            return -1;
        }
        $meta = $dbo->meta_columns($table_name);
        $pk = '';
        foreach ($meta as $field) {
            if ($field['pk']) {
                $pk = $field['name'];
                break;
            }
        }

        $viewdata = array(
            'table_name'     => $table_name,
            'tableClass'    => $tableClass,
            'pk'    => $pk,
        );
        return $this->parseTemplate('table', $viewdata);
    }
}