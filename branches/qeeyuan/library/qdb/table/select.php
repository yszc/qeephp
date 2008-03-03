<?php

/**
 * QDB_Table_Select 利用方法链，实现灵活的查询构造
 */
class QDB_Table_Select
{
    /**
     * 查询使用的表数据入口对象
     *
     * @var QDB_Table
     */
    protected $table;

    /**
     * SELECT 子句后要查询的内容
     *
     * @var string
     */
    protected $select = '*';

    /**
     * 查询条件
     *
     * @var array
     */
    protected $where = array();

    /**
     * 查询的排序
     *
     * @var string
     */
    protected $order = null;

    /**
     * 限定结果集大小
     *
     * @var mixed
     */
    protected $limit = 1;

    /**
     * 分页查询时，页码的基数
     *
     * @var int
     */
    protected $page_base = 1;

    /**
     * 添加 GROUP BY 子句
     *
     * @var string
     */
    protected $group = null;

    /**
     * 添加 HAVING 子句
     *
     * @var string
     */
    protected $having = array();

    /**
     * 使用 FOR UPDATE 模式
     *
     * @var boolean
     */
    protected $for_update = false;

    /**
     * 使用 DISTINCT 模式
     *
     * @var boolean
     */
    protected $distinct = false;

    /**
     * 统计记录数
     *
     * @var string
     */
    protected $count = null;

    /**
     * 统计平均值
     *
     * @var string
     */
    protected $avg = null;

    /**
     * 统计最大值
     *
     * @var string
     */
    protected $max = null;

    /**
     * 统计最小值
     *
     * @var string
     */
    protected $min = null;

    /**
     * 统计合计
     *
     * @var string
     */
    protected $sum = null;

    /**
     * 返回的结果封装为什么对象
     *
     * @var string
     */
    protected $as_object = null;

    /**
     * 查询结果
     *
     * @var QDB_Result_Abstract
     */
    protected $handle = null;

    /**
     * 要查询的关联
     *
     * @var array
     */
    protected $links;

    /**
     * 递归关联查询的层数
     *
     * @var int
     */
    protected $recursion = 1;

    /**
     * 发起递归查询的表数据入口关联
     *
     * @var QDB_Table_Link
     */
    protected $recursion_link = null;

    /**
     * 构造函数
     *
     * @param QDB_Table $table
     * @param array|string $where
     * @param array $args
     * @param array $links
     */
    function __construct(QDB_Table $table, $where = null, array $args = null, array $links = null)
    {
        $this->table = $table;
        if (!is_array($links)) { $links = array(); }
        $this->links = $links;
        if (!is_null($where)) {
            $this->where[] = $this->table->parseSQLInternal($where, $args);
        }
    }

    /**
     * 指定 SELECT 子句后要查询的内容
     *
     * @param array|string $expr
     *
     * @return QDB_Table_Select
     */
    function select($expr = '*')
    {
        $this->select = $expr;
        return $this;
    }

    /**
     * 设置递归关联查询的层数（默认为1层）
     *
     * @param int $recursion
     * @param QDB_Table_Link $link
     *
     * @return QDB_Table_Select
     */
    function recursion($recursion, QDB_Table_Link $link = null)
    {
        $this->recursion = abs($recursion);
        $this->recursion_link = $link;
        return $this;
    }

    /**
     * 设置关联查询时要使用的关联
     *
     * $links 可以是数组或字符串。如果 $links 为 null，则表示不查询关联。
     *
     * @param array|string $links
     *
     * @return QDB_Table_Select
     */
    function links($links)
    {
        if (empty($links)) {
            $this->links = array();
        } else {
            $links = Q::normalize($links);
            $enabled = array();
            foreach ($links as $link) {
                if (isset($this->links[$link])) {
                    $enabled[$link] = $this->links[$link];
                }
            }
            $this->links = $enabled;
        }
        return $this;
    }

    /**
     * 添加查询条件
     *
     * @param array|string $where
     *
     * @return QDB_Table_Select
     */
    function where($where)
    {
        $args = func_get_args();
        array_shift($args);
        list($sql, ) = $this->table->parseSQLInternal($where, $args);
        if (!empty($sql)) {
            $this->where[] = $sql;
        }
        return $this;
    }

    /**
     * 指定查询的排序
     *
     * @param string $expr
     *
     * @return QDB_Table_Select
     */
    function order($expr)
    {
        $this->order = $expr;
        return $this;
    }

    /**
     * 指示查询所有符合条件的记录
     *
     * @return QDB_Table_Select
     */
    function all()
    {
        $this->limit = null;
        return $this;
    }

    /**
     * 限制查询结果总数
     *
     * @param int $count
     * @param int $offset
     *
     * @return QDB_Table_Select
     */
    function limit($count, $offset = 0)
    {
        $this->limit = array($count, $offset);
        return $this;
    }

    /**
     * 设置分页查询
     *
     * @param int $page
     * @param int $page_size
     * @param int $base
     *
     * @return QDB_Table_Select
     */
    function limitPage($page, $page_size = 20, $base = 1)
    {
        $this->page_base = $base;
        $page -= $base;
        $this->limit = array($page_size, $page * $page_size);
        return $this;
    }

    /**
     * 获得查询后的分页信息
     *
     * @return array
     */
    function getPageInfo()
    {
        if (is_null($this->result)) { return array(); }
        // 统计记录总数

        // 返回分页信息

    }

    /**
     * 指定 GROUP BY 子句
     *
     * @param string $expr
     *
     * @return QDB_Table_Select
     */
    function group($expr)
    {
        $this->group = $expr;
    }

    /**
     * 指定 HAVING 子句的条件
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Table_Select
     */
    function having($where, array $args = null)
    {
        $this->having[] = $this->table->parseSQL($where, $args);
    }

    /**
     * 是否构造一个 FOR UPDATE 查询
     *
     * @param boolean $flag
     *
     * @return QDB_Table_Select
     */
    function forUpdate($flag = true)
    {
        $this->for_update = (bool)$flag;
        return $this;
    }

    /**
     * 是否构造一个 DISTINCT 查询
     *
     * @param boolean $flag
     *
     * @return QDB_Table_Select
     */
    function distinct($flag = true)
    {
        $this->distinct = (bool)$flag;
        return $this;
    }

    /**
     * 指示将返回的记录封装为特定的对象
     *
     * @param string $class_name
     *
     * @return QDB_Table_Select
     */
    function asObject($class_name)
    {
        $this->as_object = $class_name;
        return $this;
    }

    /**
     * 统计符合条件的记录数
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Table_Select
     */
    function count($expr = '*', $alias = 'row_count')
    {
        $this->count = array($expr, $alias);
        return $this;
    }

    /**
     * 统计平均值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Table_Select
     */
    function avg($expr, $alias = 'avg_value')
    {
        $this->avg = array($expr, $alias);
        return $this;
    }

    /**
     * 统计最大值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Table_Select
     */
    function max($expr, $alias = 'max_value')
    {
        $this->max = array($expr, $alias);
        return $this;
    }

    /**
     * 统计最小值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Table_Select
     */
    function min($expr, $alias = 'min_value')
    {
        $this->min = array($expr, $alias);
        return $this;
    }

    /**
     * 统计合计
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Table_Select
     */
    function sum($expr, $alias = 'sum_value')
    {
        $this->sum = array($expr, $alias);
        return $this;
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
        list($sql, $is_stat, $used_links) = $this->toStringInternal();

        QDebug::dump($sql, 'query() - ' . $this->table->table_name);

        if (!is_array($this->limit)) {
            if (is_null($this->limit)) {
                $handle = $this->table->getConn()->execute($sql);
            } else {
                $handle = $this->table->getConn()->selectLimit($sql, intval($this->limit));
            }
        } else {
            list($count, $offset) = $this->limit;
            $handle = $this->table->getConn()->selectLimit($sql, $count, $offset);
        }

        /* @var $handle QDB_Result_Abstract */

        if ($this->recursion > 0) {
            $refs_value = null;
            $refs = null;
            $used_alias = array_keys($used_links);
            $rowset = $handle->fetchAllRefby($used_alias, $refs_value, $refs, $clean_up);

//            QDebug::dump($rowset, '$rowset for table: ' . get_class($this->table));
//            QDebug::dump($refs_value, '$refs_value for table: ' . get_class($this->table));
//            QDebug::dump($refs, '$refs for table: ' . get_class($this->table));

            // 进行关联查询，并组装数据集
            foreach ($used_links as $mka => $link) {
                /* @var $link QDB_Table_Link */
                if ($link->assoc_table->qtable_name == $this->table->qtable_name) {
                    continue;
                }

                $select = $link->assoc_table->find("[{$link->assoc_key}] IN (?)", $refs_value[$mka])
                                            ->recursion($this->recursion - 1, $link)
                                            ->order($link->on_find_order)
                                            ->select($link->on_find_fields)
                                            ->where($link->on_find_where);
                if (is_int($link->on_find) || is_array($link->on_find)) {
                    $select->limit($link->on_find);
                } else {
                    $select->all();
                }

                $assoc_rowset = $select->query(false);
                if (is_int($link->on_find) && $link->on_find == 1) {
                    $assoc_rowset = array($assoc_rowset);
                }

//                $label = '$assoc_rowset for ' . $link->name . ', mka = ' . $mka;
//                if ($this->recursion_link) {
//                    $label .= ' from ' . get_class($this->recursion_link);
//                }
//                QDebug::dump($assoc_rowset, $label);

                // 组装数据集
                if ($link->one_to_one) {
                    foreach (array_keys($assoc_rowset) as $offset) {
                        $v = $assoc_rowset[$offset][$mka];
                        unset($assoc_rowset[$offset][$mka]);
                        foreach (array_keys($refs[$mka][$v]) as $i) {
                            $refs[$mka][$v][$i][$link->mapping_name] = $assoc_rowset[$offset];
                            unset($refs[$mka][$v][$i][$mka]);
                        }
                    }
                } else {
                    foreach (array_keys($assoc_rowset) as $offset) {
                        $v = $assoc_rowset[$offset][$mka];
                        unset($assoc_rowset[$offset][$mka]);
                        foreach (array_keys($refs[$mka][$v]) as $i) {
                            $refs[$mka][$v][$i][$link->mapping_name][] = $assoc_rowset[$offset];
                            unset($refs[$mka][$v][$i][$mka]);
                        }
                    }
                }
            }

            if ($this->as_object) {
                Q::loadClass($this->as_object);
                $col = array();
                foreach (array_keys($rowset) as $offset) {
                    $col[] = new $this->as_object($rowset[$offset]);
                }
                $rowset = $col;
            }

            if ($this->limit == 1) {
                return reset($rowset);
            } else {
                return $rowset;
            }
        } else {
            if ($this->limit == 1) {
                return $handle->fetchRow();
            } else {
                return $handle->fetchAll();
            }
        }
    }

    /**
     * 返回完整的 SQL 语句
     *
     * @return string
     */
    function toString()
    {
        list($sql) = $this->toStringInternal(false);
        return $sql;
    }

    /**
     * 将一个字段列表或者一个表达式转换为字符串
     *
     * @param mixed $part
     *
     * @return string
     */
    protected function toStringPart($part)
    {
        if (is_object($part)) {
            return $part->toString();
        } else {
            return $this->table->getConn()->qfields($part, $this->table->full_table_name);
        }
    }

    /**
     * 返回查询语句以及相关关联的信息
     *
     * @param boolean $use_links
     *
     * @return array
     */
    protected function toStringInternal($use_links = true)
    {
        $sql = 'SELECT ';
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        if ($this->recursion_link) {
            $dbo = $this->recursion_link->assoc_table->getConn();
            $sql .= $dbo->qfield($this->recursion_link->assoc_key) .
                    ' AS ' .
                    $dbo->qfield($this->recursion_link->main_key_alias) . ', ';
        }

        $sql .= $this->toStringPart($this->select);
        $is_stat = false;

        if ($this->count) {
            $is_stat = true;
            list($expr, $alias) = $this->count;
            $expr = $this->toStringPart($expr);
            $sql .= ", COUNT({$expr}) AS {$alias}";
        }
        if ($this->avg) {
            $is_stat = true;
            list($expr, $alias) = $this->avg;
            $expr = $this->toStringPart($expr);
            $sql .= ", AVG({$expr}) AS {$alias} ";
        }
        if ($this->max) {
            $is_stat = true;
            list($expr, $alias) = $this->max;
            $expr = $this->toStringPart($expr);
            $sql .= ", MAX({$expr}) AS {$alias} ";
        }
        if ($this->min) {
            $is_stat = true;
            list($expr, $alias) = $this->min;
            $expr = $this->toStringPart($expr);
            $sql .= ", MIN({$expr}) AS {$alias} ";
        }
        if ($this->sum) {
            $is_stat = true;
            list($expr, $alias) = $this->sum;
            $expr = $this->toStringPart($expr);
            $sql .= ", SUM({$expr}) AS {$alias} ";
        }

        $used_links = array();
        if (!$is_stat && $use_links && $this->recursion > 0) {
            // 如果使用了任何统计函数，则不进行关联查询
            foreach ($this->links as $link) {
                /* @var $link QDB_Table_Link */
                if (!$link->enabled || $link->on_find == 'skip') { continue; }
                $link->init();
                if ($link->assoc_table === $this->recursion_link) { continue; }
                $sql .= ', ' . $this->table->getConn()->qfield($link->main_key) . ' AS ' . $link->main_key_alias;
                $used_links[$link->main_key_alias] = $link;
            }
        }

        $sql .= " FROM {$this->table->qtable_name}";

        $c = array();
        foreach ($this->where as $where) {
            $c[] = '(' . $this->table->parseSQL($where) . ')';
        }
        if (!empty($c)) {
            $sql .= ' WHERE ' . implode(' AND ', $c);
        }

        if ($this->group) {
            $group = $this->table->parseSQL($this->group);
            $sql .= " GROUP BY {$group}";
        }

        $c = array();
        foreach ($this->having as $where) {
            $c[] = '(' . $this->table->parseSQL($where) . ')';
        }
        if (!empty($c)) {
            $sql .= ' HAVING ' . implode(' AND ', $c);
        }

        if ($this->order) {
            $order = $this->table->parseSQL($this->order);
            $sql .= " ORDER BY {$order}";
        }

        if ($this->for_update) {
            $sql .= ' FOR UPDATE';
        }

        return array($sql, $is_stat, $used_links);
    }
}
