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
     * @param array $links
     */
    function __construct($class, QDB_Table $table, array $attribs, array $links)
    {
        parent::__construct($table);
        $this->class = $class;
        $this->table = $table;
        $this->table->connect();
        $this->attribs = $attribs;

        // 根据对象聚合创建关联
        foreach ($links as $define) {
            $mapping_name = $define['alias'];
            if ($this->table->existsLink($mapping_name)) { continue; }
            $ref = QDB_ActiveRecord_Abstract::__reflection($define['class']);
            $table = $ref['table'];

            $link = $define['assoc_options'];
            $link['table_obj'] = $table;
            $link['mapping_name'] = $define['alias'];

            switch ($define['assoc']) {
            case QDB_Table::has_one:
            case QDB_Table::has_many:
                if (empty($link['assoc_key'])) {
                    $link['assoc_key'] = strtolower($class) . '_id';
                }
                break;
            case QDB_Table::belongs_to:
                if (empty($link['main_key'])) {
                    $link['main_key'] = strtolower($define['clsas']) . '_id';
                }
                break;
            case QDB_Table::many_to_many:
                if (empty($link['mid_main_key'])) {
                    $link['mid_main_key'] = strtolower($class) . '_id';
                }
                if (empty($link['mid_assoc_key'])) {
                    $link['mid_assoc_key'] = strtolower($define['class']) . '_id';
                }
            }
            $this->table->createLinks($link, $define['assoc']);
            $this->table->getLink($define['alias'])->init();
        }
        $this->links = $this->table->getAllLinks();
        $this->as_object($class);
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
