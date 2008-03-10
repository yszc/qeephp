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
 * 定义 QDB_Table_Select 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table_Select 利用方法链，实现灵活的查询构造
 *
 * @package database
 */
class QDB_Table_Select extends QDB_Select_Abstract
{
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
     * 生成 SQL 语句时的内部处理
     *
     * @param string $sql
     *
     * @return string
     */
    protected function toStringInternalCallback($sql)
    {
        if ($this->count) {
            list($expr, $alias) = $this->count;
            $expr = $this->toStringPart($expr);
            $sql .= ", COUNT({$expr}) AS {$alias}";
        }
        if ($this->avg) {
            list($expr, $alias) = $this->avg;
            $expr = $this->toStringPart($expr);
            $sql .= ", AVG({$expr}) AS {$alias} ";
        }
        if ($this->max) {
            list($expr, $alias) = $this->max;
            $expr = $this->toStringPart($expr);
            $sql .= ", MAX({$expr}) AS {$alias} ";
        }
        if ($this->min) {
            list($expr, $alias) = $this->min;
            $expr = $this->toStringPart($expr);
            $sql .= ", MIN({$expr}) AS {$alias} ";
        }
        if ($this->sum) {
            list($expr, $alias) = $this->sum;
            $expr = $this->toStringPart($expr);
            $sql .= ", SUM({$expr}) AS {$alias} ";
        }

        return $sql;
    }
}
