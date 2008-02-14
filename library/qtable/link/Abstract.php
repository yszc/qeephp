<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QTable_Link_Abstract 类及其继承类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * QTable_Link_Abstract 封装数据表之间的关联关系
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.4
 */
abstract class QTable_Link_Abstract
{
    /**
     * 该连接的名字，用于检索指定的连接
     *
     * 同一个数据表的多个关联不能使用相同的名字。如果定义关联时没有指定名字，
     * 则以关联对象的 $mapping_name 属性作为这个关联的名字。
     *
     * @var string
     */
    public $name;

    public $assoc_table_class;

    /**
     * 外键字段名
     *
     * @var string
     */
    public $fk;

    /**
     * 关联数据表结果映射到主表结果中的字段名
     *
     * @var string
     */
    public $mapping_name;

    /**
     * 指示连接两个数据集的行时，是一对一连接还是一对多连接
     *
     * @var boolean
     */
    public $one_to_one = false;

    /**
     * 关联的类型
     *
     * @var const
     */
    public $type;

    /**
     * 对关联表进行查询时使用的排序参数
     *
     * @var string
     */
    public $sort;

    /**
     * 对关联表进行查询时使用的条件参数
     *
     * @var string
     */
    public $where;

    /**
     * 对关联表进行查询时要获取的关联表字段
     *
     * @var string|array
     */
    public $fields = '*';

    /**
     * 对关联表进行查询时限制查出的记录数
     *
     * @var int
     */
    public $limit = null;

    /**
     * 当 enabled 为 false 时，表数据入口的任何操作都不会处理该关联
     *
     * enabled 的优先级高于 linkRead、linkCreate、linkUpdate 和 linkRemove。
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * 主表的表数据入口对象
     *
     * @var Table
     */
    public $main_table;

    /**
     * 外键字段的完全限定名
     *
     * @var string
     */
    public $qfk;

    /**
     * 指示关联的表数据入口是否已经初始化
     *
     * @var boolean
     */
    protected $is_init = false;

    /**
     * 构造函数
     *
     * @param array $def
     * @param const $type
     * @param QTable_Base $table
     *
     * @return QTable_Link_Abstract
     */
    protected function __construct(array $def, $type, QTable_Base $table)
    {
        static $options = array('name', 'assoc_table_class', 'mapping_name', 'fk', 'sort', 'where', 'fileds', 'limit', 'enabled');

        foreach ($options as $key) {
            if (!empty($def[$key])) {
                $this->{$key} = $def[$key];
            }
        }
        $this->name = strtolower($this->name);
        $this->type = $type;
        $this->main_table = $table;
    }

    /**
     * 创建 QTable_Link_Abstract 对象实例
     *
     * @param array $def
     * @param const $type
     * @param QTable_Base $table
     *
     * @return QTable_Link_Abstract
     */
    static function create_link(array $def, $type, QTable_Base $table)
    {
        static $type_to_class = array(
            QTable_Base::has_one        => 'QTable_Link_Has_One',
            QTable_Base::has_many       => 'QTable_Link_Has_Many',
            QTable_Base::belongs_to     => 'QTable_Link_Belongs_To',
            QTable_Base::many_to_many   => 'QTable_Link_Many_to_Many',
        );

        if (!isset($type_to_class[$type])) {
            throw new QTable_Link_Exception(__('Invalid parameter type: "%s".', $type));
        }

        if (empty($def['assoc_table_class'])) {
            throw new QTable_Link_Exception(__('Invalid parameter "assoc_table_class".'));
        }

        // 如果没有提供 mapping_name 属性，则使用 table_class 最后一个单词作为 mapping_name
        if (empty($def['mapping_name'])) {
            $words = explode('_', $def['table_class']);
            $def['mapping_name'] = strtolower($words[count($words) - 1]);
        }
        if (empty($def['name'])) {
            $def['name'] = $def['mapping_name'];
        }
        return new $type_to_class[$type]($def, $type, $table);
    }

    function __get($varname)
    {
        if ($varname == 'assoc_table' || $varname == 'assoc_dbo') {
            $this->init();
            return $this->{$varname};
        } else {
            throw new QTable_Link_Exception(__('Invalid property: "%s".', $varname));
        }
    }

    /**
     * 初始化关联对象
     */
    protected function init()
    {
        if (!$this->is_init) {
            $this->assoc_table = Q::getSingleton($this->assoc_table_class);
            $this->assoc_dbo = $this->assoc_table->get_dbo();
            $this->is_init = true;
        }
    }

    /**
     * 返回用于查询关联表数据的 SQL 语句
     *
     * @param string $sql
     * @param array $pkvs
     *
     * @return string
     */
    protected function get_find_sql_base($sql, array $pkvs)
    {
        if (!empty($pkvs)) {
            $sql .= $this->assoc_table->qinto(" WHERE {$this->qfk} IN (?)", $pkvs);
        }
        if ($this->conditions) {
            if (is_array($this->conditions)) {
                $conditions = Table_SqlHelper::parseConditions($this->conditions, $this->assoc_table);
                if (is_array($conditions)) {
                    $conditions = $conditions[0];
                }
            } else {
                $conditions = $this->conditions;
            }
            if ($conditions) {
                $sql .= " AND {$conditions}";
            }
        }
        if ($this->sort && $this->countOnly == false) {
            $sql .= " ORDER BY {$this->sort}";
        }

        return $sql;
    }
}

