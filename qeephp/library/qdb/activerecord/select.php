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
 * 定义 QDB_ActiveRecord_Select 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Select 类完成 ActiveRecord 对象的查找操作
 *
 * @package database
 */
class QDB_ActiveRecord_Select extends QDB_Select_Abstract
{
    /**
     * @var string
     */
    protected $class;

    /**
     * ActiveRecord 对象的属性和字段影射
     *
     * @var array
     */
    protected $attribs;

    /**
     * 查询参数
     *
     * @var array
     */
    protected $params;

    /**
     * @var QDB_Table
     */
    protected $table;

    /**
     * 构造函数
     *
     * @param string $class
     * @param QDB_Table $table
     * @param array $attribs
     */
    function __construct($class, QDB_Table $table, array $attribs)
    {
        parent::__construct($table);
        $this->class = $class;
        $this->table = $table;
        $this->table->connect();
        $this->attribs = $attribs;

        // 根据对象聚合创建关联
        foreach ($attribs['__links'] as $define) {
            $mapping_name = $define['alias'];
            if ($this->table->existsLink($mapping_name)) { continue; }
            $class_define = call_user_func(array($define['class'], '__define'));
            if (!empty($class_define['table_class'])) {
                $table = Q::getSingleton($class_define['table_class']);
            } else {
                $id = 'model_table_' . strtolower($class_define['table_name']);
                if (Q::isRegistered($id)) {
                    $table = Q::registry($id);
                } else {
                    $table = new QDB_Table(array('table_name' => $class_define['table_name']));
                    Q::register($table, $id);
                }
            }
            $link = array(
                'table_obj' => $table,
                'mapping_name' => $define['alias'],
            );
            $this->table->createLinks($link, $define['assoc']);
            $this->table->getLink($define['alias'])->init();
            QDebug::dump($this->table->getLink($define['alias']));
        }
    }

    /**
     * 执行查询
     *
     * @param boolean $clean_up 是否清理数据集中的临时字段
     *
     * @return mixed
     */
    function query($clean_up = true)
    {
        $data = parent::query($clean_up);
        return $data;
    }

    /**
     * 继承类应该重写此方法，以便对 SQL 构造过程进行控制
     *
     * @param string $sql
     *
     * @return $sql
     */
    protected function toStringInternalCallback($sql)
    {
        return $sql;
    }

}
