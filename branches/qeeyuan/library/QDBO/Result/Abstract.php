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
 * 定义 QDBO_Result_Abstract 类
 *
 * @package DB
 * @version $Id$
 */

/**
 * QDBO_Result_Abstract 是封装查询结果对象的抽象基础类
 *
 * @package DB
 */
abstract class QDBO_Result_Abstract
{
    /**
     * 指示返回结果集的形式
     *
     * @var const
     */
    public $fetchMode;

    /**
     * 查询句柄
     *
     * @var resource
     */
    protected $_handle = null;

    /**
     * 构造函数
     *
     * @param resource $handle
     * @param const $fetchMode
     */
    function __construct($handle, $fetchMode)
    {
        if (is_resource($handle) || is_object($handle)) {
            $this->_handle = $handle;
        }
        $this->fetchMode = $fetchMode;
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        $this->free();
    }

    /**
     * 返回句柄
     *
     * @return resource
     */
    function handle()
    {
        return $this->_handle;
    }

    /**
     * 指示句柄是否有效
     *
     * @return boolean
     */
    function valid()
    {
        return $this->_handle != null;
    }

    /**
     * 释放句柄
     */
    abstract function free();

    /**
     * 从查询句柄提取一条记录
     *
     * @return array
     */
    abstract function fetchRow();

    /**
     * 从查询句柄中提取记录集
     *
     * @return array
     */
    function fetchAll()
    {
        $rowset = array();
        while (($row = $this->fetchRow())) {
            $rowset[] = $row;
        }
        return $rowset;
    }

    /**
     * 从查询句柄提取一条记录，并返回该记录的第一个字段
     *
     * @return mixed
     */
    function fetchOne()
    {
        $row = $this->fetchRow();
        return $row ? reset($row) : null;
    }

    /**
     * 从查询句柄提取记录集，并返回包含每行指定列数据的数组，如果 $col 为 0，则返回第一列
     *
     * @param int $col
     *
     * @return array
     */
    function fetchCol($col = 0)
    {
        $mode = $this->fetchMode;
        $this->fetchMode = QDBO_Abstract::fetch_mode_array;
        $cols = array();
        while (($row = $this->fetchRow())) {
            $cols[] = $row[$col];
        }
        $this->fetchMode = $mode;
        return $cols;
    }

    /**
     * 返回记录集和指定字段的值集合，以及以该字段值作为索引的结果集
     *
     * 假设数据表 posts 有字段 post_id 和 title，并且包含下列数据：
     *
     * <code>
     * +---------+-----------------------+
     * | post_id | title                 |
     * +---------+-----------------------+
     * |       1 | It's live             |
     * +---------+-----------------------+
     * |       2 | QeePHP Recipes        |
     * +---------+-----------------------+
     * |       7 | QeePHP User manual    |
     * +---------+-----------------------+
     * |      15 | QeePHP Quickstart     |
     * +---------+-----------------------+
     * </code>
     *
     * 现在我们查询 posts 表的数据，并以 post_id 的值为结果集的索引值：
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts";
     * $handle = $dbo->execute($sql);
     *
     * $fieldValues = array();
     * $reference = array();
     * $rowset = $handle->fetchAllRefby('post_id', $fieldValues, $reference);
     * </code>
     *
     * 上述代码执行后，$rowset 包含 posts 表中的全部 4 条记录。
     * 而 $fieldValues 则是一个包含 4 条记录 post_id 字段值的一维数组 array(1, 2, 7, 15)。
     * 最后，$reference 是如下形式的数组：
     *
     * <code>
     * $reference = array(
     *      1 => & array(...),
     *      2 => & array(...),
     *      7 => & array(...),
     *     15 => & array(...)
     * );
     * </code>
     *
     * $reference 用 post_id 字段值作为索引值，并且指向 $rowset 中 post_id 值相同的记录。
     * 由于是以引用方式构造的 $reference 数组，因此并不会占用双倍内存。
     *
     * @param string $field
     * @param array $fieldValues
     * @param array $reference
     *
     * @return array
     */
    function fetchAllRefby($field, array & $fieldValues, array & $reference)
    {
        $fieldValues = array();
        $reference = array();
        $offset = 0;
        $data = array();

        while (($row = $this->fetchRow())) {
            $fieldValue = $row[$field];
            $data[$offset] = $row;
            $fieldValues[$offset] = $fieldValue;
            $reference[$fieldValue] =& $data[$offset];
            $offset++;
        }

        return $data;
    }

    /**
     * 将两个数据集按照指定字段的值进行组装
     *
     * 表数据入口使用该方法组装来自两个数据表的数据。
     *
     * @param QDBO_Result_Abstract $handle
     * @param array $assocRowset
     * @param string $mappingName
     * @param boolean $oneToOne
     * @param string $refKeyName
     */
    function assemble(QDBO_Result_Abstract $handle, array & $assocRowset, $mappingName, $oneToOne, $refKeyName)
    {
        if ($oneToOne) {
            // 一对一组装数据
            while (($row = $handle->fetchRow())) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName] = $row;
            }
        } else {
            // 一对多组装数据
            while (($row = $handle->fetchRow())) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName][] = $row;
            }
        }
    }
}
