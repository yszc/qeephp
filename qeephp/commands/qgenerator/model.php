<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QGenerator_Model 类
 *
 * @package generator
 * @version $Id$
 */

/**
 * QGenerator_Model 创建 ActiveRecord 对象代码
 *
 * @package generator
 */
class QGenerator_Model extends QGenerator_Abstract
{
    /**
     * 执行代码生成器
     *
     * @param array $opts
     *
     * @return mixed
     */
    function execute(array $opts)
    {
        $model_name = array_shift($opts);
        $table_name = array_shift($opts);
        if (empty($model_name) || empty($table_name)) { return false; }

        // $class_name = 'Model_' . ucfirst($this->camelName($model_name));
        $class_name = ucfirst($this->camelName($model_name));
        if (($filename = $this->existsClassFile($class_name))) {
            echo "Class '{$class_name}' declare file '{$filename}' exists.\n";
            return false;
        }

        $content = $this->getCode($table_name, $class_name);
        if ($content !== -1 && !empty($content)) {
            return $this->createClassFile($class_name, $content);
        } else {
            return false;
        }

    }

    /**
     * 生成代码
     *
     * @param string $table_name
     * @param string $class_name
     *
     * @return string
     */
    function getCode($table_name, $class_name)
    {
        if (substr($table_name, 0, 6) == 'Table_') {
            // 如果指定的是表数据入口类名，则载入该表数据入口
            $table_class = $table_name;
            unset($table_name);
            try {
                Q::loadClass($table_class);
            } catch (Exception $ex) {
                echo $ex->__toString();
                return false;
            }

            $table = new $table_class();
            /* @var $table QDB_Table */
            $meta = $table->columns();
            $table_name = $table->table_name;
            $pk = Q::normalize($table->pk);
        } else {
            $table_class = null;
            // 尝试读取数据表的信息
            $dbo = QDB::getConn();
            $dbo->connect();
            $tables = $dbo->metaTables();
            if (!in_array($table_name, $tables)) {
                echo "Database table '{$table_name}' not exists.\n";
                return -1;
            }

            $meta = $dbo->metaColumns($table_name);
            $pk = array();
            foreach ($meta as $field) {
                if ($field['pk']) {
                    $pk[] = $field['name'];
                }
            }
        }

        $viewdata = array(
            'table_name' => $table_name,
            'class_name' => $class_name,
            'table_class' => $table_class,
            'meta' => $meta,
            'pk' => $pk,
        );
        return $this->parseTemplate('model', $viewdata);
    }
}
