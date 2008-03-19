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

        $this->links = $this->table->getAllLinks();
        $this->asObject($class);
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
