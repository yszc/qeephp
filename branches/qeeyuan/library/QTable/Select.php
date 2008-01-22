<?php

/**
 * QTable_Select 利用方法链，实现灵活的查询构造
 */
class QTable_Select
{
    /**
     * 查询使用的表数据入口对象
     *
     * @var QTable_Base
     */
    protected $_table;

    /**
     * SELECT 子句后要查询的内容
     *
     * @var string
     */
    protected $_select = '*';
    
    /**
     * 查询条件
     *
     * @var array
     */
    protected $_where = array();
    
    /**
     * 查询的排序
     *
     * @var string
     */
    protected $_order = null;
    
    /**
     * 限定结果集大小
     *
     * @var mixed
     */
    protected $_limit = 1;

    /**
     * 分页查询时，页码的基数
     *
     * @var int
     */
    protected $_pageBase = 1;
    
    /**
     * 添加 GROUP BY 子句
     *
     * @var string
     */
    protected $_groupBy = null;
    
    /**
     * 添加 HAVING 子句
     *
     * @var string
     */
    protected $_having = array();
    
    /**
     * 使用 FOR UPDATE 模式
     *
     * @var boolean
     */
    protected $_forUpdate = false;
    
    /**
     * 使用 DISTINCT 模式
     *
     * @var boolean
     */
    protected $_distinct = false;
    
    /**
     * 统计记录数
     *
     * @var string
     */
    protected $_count = null;
    
    /**
     * 统计平均值
     *
     * @var string
     */
    protected $_avg = null;
    
    /**
     * 统计最大值
     *
     * @var string
     */
    protected $_max = null;
    
    /**
     * 统计最小值
     *
     * @var string
     */
    protected $_min = null;
    
    /**
     * 统计合计
     *
     * @var string
     */
    protected $_sum = null;

    /**
     * 查询结果
     *
     * @var QDBO_Result_Abstract
     */
    protected $_handle = null;

    /**
     * 构造函数
     *
     * @param QTable_Base $table
     * @param array|string $where
     * @param array $args
     */
    function __construct(QTable_Base $table, $where = null, array $args = null)
    {
        $this->_table = $table;
        if (!is_null($where)) {
            $this->where($where, $args);
        }
    }

    /**
     * 指定 SELECT 子句后要查询的内容
     *
     * @param string $expr
     *
     * @return QTable_Select
     */
    function select($expr = '*')
    {
        $this->_select = $expr;
        return $this;
    }

    /**
     * 添加查询条件
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QTable_Select
     */
    function where($where, array $args = null)
    {
        $this->_where[] = $this->_table->parseWhere($where, $args);
        return $this;
    }

    /**
     * 指定查询的排序
     *
     * @param string $expr
     *
     * @return QTable_Select
     */
    function order($expr)
    {
        $this->_order = $expr;
        return $this;
    }

    /**
     * 查询所有符合条件的记录
     *
     * @return QTable_Select
     */
    function all()
    {
        $this->_limit = null;
        return $this;
    }

    /**
     * 限制查询结果总数
     *
     * @param int $count
     * @param int $offset
     *
     * @return QTable_Select
     */
    function limit($count, $offset = 0)
    {
        $this->_limit = array($count, $offset);
        return $this;
    }

    /**
     * 设置分页查询
     *
     * @param int $page
     * @param int $page_size
     * @param int $base
     *
     * @return QTable_Select
     */
    function limitPage($page, $page_size = 20, $base = 1)
    {
        $this->_pageBase = $base;
        $page -= $base;
        $this->_limit = array($page_size, $page * $page_size);
        return $this;
    }

    /**
     * 获得查询后的分页信息
     *
     * @return array
     */
    function getPageInfo()
    {
        if (is_null($this->_result)) { return array(); }
        // 统计记录总数

        // 返回分页信息

    }

    /**
     * 指定 GROUP BY 子句
     *
     * @param string $expr
     *
     * @return QTable_Select
     */
    function groupBy($expr)
    {
        $this->_groupBy = $expr;
    }

    /**
     * 指定 HAVING 子句的条件
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QTable_Select
     */
    function having($where, array $args = null)
    {
        $this->_having[] = $this->_table->parseWhere($where, $args);
    }
    
    /**
     * 是否构造一个 FOR UPDATE 查询
     *
     * @param boolean $flag
     *
     * @return QTable_Select
     */
    function forUpdate($flag = true)
    {
        $this->_forUpdate = (bool)$flag;
        return $this;
    }

    /**
     * 是否构造一个 DISTINCT 查询
     *
     * @param boolean $flag
     *
     * @return QTable_Select
     */
    function distinct($flag = true)
    {
        $this->_distinct = (bool)$flag;
        return $this;
    }

    /**
     * 统计符合条件的记录数
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QTable_Select
     */
    function count($expr = '*', $alias = 'row_count')
    {
        $this->_count = array($expr, $alias);
        return $this;
    }

    /**
     * 统计平均值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QTable_Select
     */
    function avg($expr, $alias = 'avg_value')
    {
        $this->_avg = array($expr, $alias);
        return $this;
    }

    /**
     * 统计最大值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QTable_Select
     */
    function max($expr, $alias = 'max_value')
    {
        $this->_max = array($expr, $alias);
        return $this;
    }

    /**
     * 统计最小值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QTable_Select
     */
    function min($expr, $alias = 'min_value')
    {
        $this->_min = array($expr, $alias);
        return $this;
    }

    /**
     * 统计合计
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QTable_Select
     */
    function sum($expr, $alias = 'sum_value')
    {
        $this->_sum = array($expr, $alias);
        return $this;
    }

    /**
     * 执行查询
     *
     * @return mixed
     */
    function query()
    {
        $sql = $this->toString();
        if (!is_array($this->_limit)) {
            if (is_null($this->_limit)) {
                $handle = $this->_table->getDBO()->execute($sql);
            } else {
                $handle = $this->_table->getDBO()->selectLimit($sql, intval($this->_limit));
            }
        } else {
            list($count, $offset) = $this->_limit;
            $handle = $this->_table->getDBO()->selectLimit($sql, $count, $offset);
        }

        if ($this->_limit == 1) {
            return is_null($this->count) ? $handle->fetchRow() : $handle->fetch_one();
        } else {
            return is_null($this->count) ? $handle->fetchAll() : $handle->fetch_col();
        }
    }

    /**
     * 返回完整的 SQL 语句
     *
     * @return string
     */
    function toString()
    {
        $sql = 'SELECT ';
        if ($this->_distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= $this->_select;
        if ($this->_count) {
            list($expr, $alias) = $this->_count;
            $sql .= ", COUNT({$expr}) AS {$alias}";
        }
        if ($this->_avg) {
            list($expr, $alias) = $this->_avg;
            $sql .= ", AVG({$expr}) AS {$alias} ";
        }
        if ($this->_max) {
            list($expr, $alias) = $this->_max;
            $sql .= ", MAX({$expr}) AS {$alias} ";
        }
        if ($this->_min) {
            list($expr, $alias) = $this->_min;
            $sql .= ", MIN({$expr}) AS {$alias} ";
        }
        if ($this->_sum) {
            list($expr, $alias) = $this->_sum;
            $sql .= ", SUM({$expr}) AS {$alias} ";
        }

        $sql .= " FROM {$this->_table->qtableName} ";

        $c = array();
        foreach ($this->_where as $where) {
            if (is_array($where)) {
                $c[] = '(' . $where[0] . ')';
            } else {
                $c[] = '(' . $where . ')';
            }
        }
        if (!empty($c)) {
            $c = implode(' AND ', $c);
            $sql .= "WHERE {$c} ";
        }

        if ($this->_groupBy) {
            $sql .= "GROUP BY {$this->_groupBy} ";
        }

        $c = array();
        foreach ($this->_having as $where) {
            if (is_array($where)) {
                $c[] = '(' . $where[0] . ')';
            } else {
                $c[] = '(' . $where . ')';
            }
        }
        if (!empty($c)) {
            $c = implode(' AND ', $c);
            $sql .= "HAVING {$c} ";
        }

        if ($this->_order) {
            $sql .= "ORDER BY {$this->_order} ";
        }

        if ($this->_forUpdate) {
            $sql .= "FOR UPDATE";
        }

        return $sql;
    }


//        list($whereby, $distinct) = $this->getWhere($where);
//        // 处理排序
//        $sortby = $sort != '' ? " ORDER BY {$sort}" : '';
//        // 处理 $limit
//        if (is_array($limit)) {
//            list($offset, $length) = $limit;
//        } else {
//            $length = $limit;
//            $offset = null;
//        }
//
//        // 构造从主表查询数据的 SQL 语句
//        $fields = isset($params['fields']) ? $params['fields'] : '*';
//        $queryLinks = isset($params['links']) ? $params['links'] : true;
//        $enableLinks = count($this->links) > 0 && $queryLinks;
//        if ($enableLinks) {
//            $fields = $this->dbo->qfields($fields, $this->full_table_name, $this->schema);
//        } else {
//            $fields = $this->dbo->qfields($fields);
//        }
//        if ($enableLinks) {
//            // 当有关联需要处理时，必须获得主表的主键字段值
//            $sql = "SELECT {$distinct} {$this->qpka}, {$fields} FROM {$this->qtable_name} {$whereby} {$sortby}";
//        } else {
//            $sql = "SELECT {$distinct} {$fields} FROM {$this->qtable_name} {$whereby} {$sortby}";
//        }
//
//        // 根据 $length 和 $offset 参数决定是否使用限定结果集的查询
//        if (null !== $length || null !== $offset) {
//            $result = $this->dbo->select_limit($sql, $length, $offset);
//        } else {
//            $result = $this->dbo->execute($sql);
//        }
//
//        if ($enableLinks) {
//            /**
//             * 查询时同时将主键值单独提取出来，
//             * 并且准备一个以主键值为键名的二维数组用于关联数据的装配
//             */
//            $pkvs = array();
//            $assocRowset = array();
//            $rowset = $result->fetch_all_refby($this->pk, $pkvs, $assocRowset);
//            $in = 'IN (' . implode(',', array_map(array($this->dbo, 'qstr'), $pkvs)) . ')';
//        } else {
//            $rowset = $result->fetch_all();
//        }
//        unset($result);
//
//        // 如果没有关联需要处理或者没有查询结果，则直接返回查询结果
//        if (!$enableLinks || empty($rowset) || !$this->autoLink) {
//            return $rowset;
//        }
//
//        /**
//         * 遍历每一个关联对象，并从关联对象获取查询语句
//         *
//         * 查询获得数据后，将关联表的数据和主表数据装配在一起
//         */
//        $callback = create_function('& $r, $o, $m', '$r[$m] = null;');
//        foreach ($this->links as $link) {
//            /* @protected $link Table_Link */
//            $mn = $link->mappingName;
//            if (!$link->enabled || !$link->linkRead) { continue; }
//            if (!$link->countOnly) {
//                array_walk($assocRowset, $callback, $mn);
//                $sql = $link->getFindSQL($in);
//                $this->dbo->assemble($sql, $assocRowset, $mn, $link->oneToOne, $this->pka, $link->limit);
//            } else {
//                $link->calcCount($assocRowset, $mn, $in);
//            }
//        }
//
//        return $rowset;
}

