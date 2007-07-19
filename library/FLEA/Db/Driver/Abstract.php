<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 FLEA_Db_Driver_Abstract 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id$
 */

// includes
require_once 'FLEA/Db/Transaction.php';

/**
 * FLEA_Db_Driver_Abstract 是所有数据库驱动的基础类
 *
 * @package Database
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.2
 */
abstract class FLEA_Db_Driver_Abstract
{
    /**
     * 用于描绘 true、false 和 null 的数据库值
     */
    public $TRUE_VALUE  = 1;
    public $FALSE_VALUE = 0;
    public $NULL_VALUE  = 'NULL';

    /**
     * 数据库连接信息
     *
     * @var array
     */
    protected $_dsn = null;

    /**
     * 数据库连接句柄
     *
     * @var resource
     */
    protected $_conn = null;

    /**
     * 所有 SQL 查询的日志
     *
     * @var array
     */
    protected $_log = array();

    /**
     * 最后一次数据库操作的错误信息
     *
     * @var mixed
     */
    protected $_lasterr = null;

    /**
     * 最后一次数据库操作的错误代码
     *
     * @var mixed
     */
    protected $_lasterrcode = null;

    /**
     * 最近一次插入操作或者 nextId() 操作返回的插入 ID
     *
     * @var mixed
     */
    protected $_insertId = null;

    /**
     * 构造函数
     *
     * @param array|string $dsn
     */
    public function __construct($dsn)
    {
        $this->_dsn = $dsn;
        $this->enableLog = !defined('DEPLOY_MODE') || DEPLOY_MODE != true;
    }

    /**
     * 连接数据库，失败时抛出异常
     */
    abstract public function connect();

    /**
     * 关闭数据库连接，失败时抛出异常
     */
    abstract public function close();

    /**
     * 执行一个查询，返回一个 resource 或者 boolean 值，出错时抛出异常
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return resource|boolean
     */
    abstract public function execute($sql, $inputarr = null);

    /**
     * 转义值
     *
     * @param mixed $value
     *
     * @return string
     */
    abstract public function qstr($value);

    /**
     * 将数据表名字转换为完全限定名
     *
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    abstract public function qtable($tableName, $schema = null);

    /**
     * 将字段名转换为完全限定名，避免因为字段名和数据库关键词相同导致的错误
     *
     * @param string $fieldName
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    abstract public function qfield($fieldName, $tableName = null, $schema = null);

    /**
     * 一次性将多个字段名转换为完全限定名
     *
     * @param string|array $fields
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    abstract public function qfields($fields, $tableName = null, $schema = null);

    /**
     * 为数据表产生下一个序列值，失败时抛出异常
     *
     * @param string $seqName
     * @param string $startValue
     *
     * @return int
     */
    abstract public function nextId($seqName = 'sdboseq', $startValue = 1);

    /**
     * 创建一个新的序列，失败时抛出异常
     *
     * @param string $seqName
     * @param int $startValue
     */
    abstract public function createSeq($seqName = 'sdboseq', $startValue = 1);

    /**
     * 删除一个序列，失败时抛出异常
     *
     * @param string $seqName
     */
    abstract public function dropSeq($seqName = 'sdboseq');

    /**
     * 获取自增字段的最后一个值或者 nextId() 方法产生的最后一个值
     *
     * @return mixed
     */
    abstract public function insertId();

    /**
     * 返回最近一次数据库操作受到影响的记录数
     *
     * @return int
     */
    abstract public function affectedRows();

    /**
     * 从记录集中返回一行数据，失败抛出异常
     *
     * @param resouce $res
     *
     * @return array
     */
    abstract public function fetchRow($res);

    /**
     * 从记录集中返回一行数据，字段名作为键名，失败抛出异常
     *
     * @param resouce $res
     *
     * @return array
     */
    abstract public function fetchAssoc($res);

    /**
     * 释放查询句柄
     *
     * @param resource $res
     *
     * @return boolean
     */
    abstract public function freeRes($res);

    /**
     * 进行限定记录集的查询
     *
     * @param string $sql
     * @param int $length
     * @param int $offset
     *
     * @return resource
     */
    abstract public function selectLimit($sql, $length = null, $offset = null);

    /**
     * 执行一个查询，返回查询结果记录集
     *
     * @param string|resource $sql
     *
     * @return array
     */
    abstract public function & getAll($sql);

    /**
     * 执行一个查询，返回分组后的查询结果记录集
     *
     * $groupBy 参数如果为字符串或整数，表示结果集根据 $groupBy 参数指定的字段进行分组。
     * 如果 $groupBy 参数为 true，则表示根据每行记录的第一个字段进行分组。
     *
     * @param string|resource $sql
     * @param string|int|boolean $groupBy
     *
     * @return array
     */
    abstract public function & getAllGroupBy($sql, & $groupBy);

    /**
     * 执行一个查询，返回查询结果记录集、指定字段的值集合以及以该字段值分组后的记录集
     *
     * @param string|resource $sql
     * @param string $field
     * @param array $fieldValues
     * @param array $reference
     *
     * @return array
     */
    abstract public function & getAllWithFieldRefs($sql, $field, & $fieldValues, & $reference);

    /**
     * 执行一个查询，并将数据按照指定字段分组后与 $assocRowset 记录集组装在一起
     *
     * @param string|resource $sql
     * @param array $assocRowset
     * @param string $mappingName
     * @param boolean $oneToOne
     * @param string $refKeyName
     * @param mixed $limit
     */
    abstract public function assemble($sql, & $assocRowset, $mappingName, $oneToOne, $refKeyName, $limit = null);

    /**
     * 执行查询，返回第一条记录的第一个字段
     *
     * @param string|resource $sql
     *
     * @return mixed
     */
    abstract public function getOne($sql);

    /**
     * 执行查询，返回第一条记录
     *
     * @param string|resource $sql
     *
     * @return mixed
     */
    abstract public function & getRow($sql);

    /**
     * 执行查询，返回结果集的指定列
     *
     * @param string|resource $sql
     * @param int $col 要返回的列，0 为第一列
     *
     * @return mixed
     */
    abstract public function & getCol($sql, $col = 0);

    /**
     * 返回指定表（或者视图）的元数据
     *
     * 部分代码参考 ADOdb 实现。
     *
     * 每个字段包含下列属性：
     *
     * name:            字段名
     * scale:           小数位数
     * type:            字段类型
     * simpleType:      简单字段类型（与数据库无关）
     * maxLength:       最大长度
     * notNull:         是否不允许保存 NULL 值
     * primaryKey:      是否是主键
     * autoIncrement:   是否是自动增量字段
     * binary:          是否是二进制数据
     * unsigned:        是否是无符号数值
     * hasDefault:      是否有默认值
     * defaultValue:    默认值
     *
     * @param string $table
     *
     * @return array
     */
    abstract public function & metaColumns($table);

    /**
     * 返回数据库可以接受的日期格式
     *
     * @param int $timestamp
     */
    abstract public function dbTimeStamp($timestamp);

    /**
     * 启动事务
     */
    abstract public function startTrans();

    /**
     * 完成事务，根据查询是否出错决定是提交事务还是回滚事务
     *
     * 如果 $commitOnNoErrors 参数为 true，当事务中所有查询都成功完成时，则提交事务，否则回滚事务
     * 如果 $commitOnNoErrors 参数为 false，则强制回滚事务
     *
     * @param $commitOnNoErrors 指示在没有错误时是否提交事务
     */
    abstract public function completeTrans($commitOnNoErrors = true);

    /**
     * 强制指示在调用 completeTrans() 时回滚事务
     */
    abstract public function failTrans();

    /**
     * 反复事务是否失败的状态
     */
    abstract public function hasFailedTrans();

    /**
     * 开始一个事务，并且返回一个 FLEA_Db_Transaction 对象
     *
     * @return FLEA_Db_Transaction
     */
    final public function beginTrans()
    {
        return new FLEA_Db_Transaction($this);
    }
}
