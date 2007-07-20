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
 * 定义 FLEA_Db_Driver_Abstract 类和 FLEA_Db_Driver_Handle_Abstract 类
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
     * 处理查询参数的方式
     */
    const PARAM_QM       = 1;
    const PARAM_NAMED    = 2;
    const PARAM_SEQUENCE = 3;
    const PARAM_AT_NAMED = 4;

    /**
     * 用于描绘 true、false 和 null 的数据库值
     */
    const TRUE_VALUE  = 1;
    const FALSE_VALUE = 0;
    const NULL_VALUE  = 'NULL';

    /**
     * 指示查询参数的样式，继承类必须重载该成员变量
     *
     * @var const
     */
    public $paramStyle = self::PARAM_QM;

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
     * 执行一个查询，返回一个 FLEA_Db_Driver_Handle_Abstract 或者 boolean 值，出错时抛出异常
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return FLEA_Db_Driver_Handle_Abstract
     */
    abstract public function execute($sql, $inputarr = null);

    /**
     * 进行限定范围的查询，并且返回 FLEA_Db_Driver_Handle_Abstract 对象
     *
     * @param string $sql
     * @param int $length
     * @param int $offset
     * @param array $inputarr
     *
     * @return FLEA_Db_Driver_Handle_Abstract
     */
    abstract public function selectLimit($sql, $length = null, $offset = null, array $inputarr = null);

    /**
     * 执行一个查询并返回记录集
     *
     * 如果 $groupby 参数如果为字符串，表示结果集根据 $groupby 指定的字段进行分组。
     * 如果 $groupby 参数为 true，则表示根据每行记录的第一个字段进行分组。
     * 如果 $groupby 参数为 false，则表示不分组。
     *
     * @param string $sql
     * @param array $inputarr
     * @param string|boolean $groupby
     *
     * @return array
     */
    abstract public function & getAll($sql, array $inputarr = null, $groupby = false);

    /**
     * 执行一个查询，并且返回指定字段的值集合以及以该字段值分组后的记录集
     *
     * @param string $sql
     * @param string $field
     * @param array $fieldValues
     * @param array $reference
     * @param array $inputarr
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
     * @param array $inputarr
     */
    abstract public function assemble($sql, & $assocRowset, $mappingName, $oneToOne, $refKeyName, $limit = null, array $inputarr = null);

    /**
     * 执行查询，返回第一条记录的第一个字段
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    abstract public function & getOne($sql, array $inputarr = null);

    /**
     * 执行查询，返回第一条记录
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    abstract public function & getRow($sql, array $inputarr = null);

    /**
     * 执行查询，返回结果集的指定列
     *
     * @param string|resource $sql
     * @param int $col 要返回的列，0 为第一列
     * @param array $inputarr
     *
     * @return mixed
     */
    abstract public function & getCol($sql, $col = 0, array $inputarr = null);

    /**
     * 返回指定表（或者视图）的元数据
     *
     * 每个字段包含下列属性：
     *
     * - name:            字段名
     * - scale:           小数位数
     * - type:            字段类型
     * - simpleType:      简单字段类型（与数据库无关）
     * - maxLength:       最大长度
     * - notNull:         是否不允许保存 NULL 值
     * - primaryKey:      是否是主键
     * - autoIncrement:   是否是自动增量字段
     * - binary:          是否是二进制数据
     * - unsigned:        是否是无符号数值
     * - hasDefault:      是否有默认值
     * - defaultValue:    默认值
     * - description:     字段描述
     *
     * simpleType 可能是下列值之一：
     *
     * - C 长度小于等于 250 的字符串
     * - X 长度大于 250 的字符串
     * - B 二进制数据
     * - N 数值或者浮点数
     * - D 日期
     * - T TimeStamp
     * - L 逻辑布尔值
     * - I 整数
     * - R 自动增量或计数器
     *
     * @param string $table
     * @param string $schema
     *
     * @return array
     */
    abstract public function & metaColumns($table, $schema = null);

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
     * @param $commitOnNoErrors
     */
    abstract public function completeTrans($commitOnNoErrors = true);

    /**
     * 强制指示在调用 completeTrans() 时回滚事务
     */
    abstract public function failTrans();

    /**
     * 检查事务过程中是否出现失败的查询
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

/**
 * FLEA_Db_Driver_Handle_Abstract 是封装查询句柄的基础类
 *
 * @package Database
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
abstract class FLEA_Db_Driver_Handle_Abstract
{
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
     */
    public function __construct($handle)
    {
        if (is_resource($handle)) {
            $this->_handle = $handle;
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * 返回句柄
     *
     * @return resource
     */
    public function handle()
    {
        return $this->_handle;
    }

    /**
     * 指示句柄是否有效
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_handle != null;
    }

    /**
     * 释放句柄
     */
    abstract public function free();

    /**
     * 从查询句柄中提取记录集
     *
     * 如果 $groupby 参数如果为字符串，表示结果集根据 $groupby 指定的字段进行分组。
     * 如果 $groupby 参数为 true，则表示根据每行记录的第一个字段进行分组。
     * 如果 $groupby 参数为 false，则表示不分组。
     *
     * @param string|boolean $groupby
     *
     * @return array
     */
    abstract public function & getAll($groupby = false);

    /**
     * 从查询句柄中提取记录集，并且返回指定字段的值集合以及以该字段值分组后的记录集
     *
     * @param string $field
     * @param array $fieldValues
     * @param array $reference
     *
     * @return array
     */
    abstract public function & getAllWithFieldRefs($field, & $fieldValues, & $reference);

    /**
     * 从查询句柄中提取记录集，并将数据按照指定字段分组后与 $assocRowset 记录集组装在一起
     *
     * @param array $assocRowset
     * @param string $mappingName
     * @param boolean $oneToOne
     * @param string $refKeyName
     */
    abstract public function assemble(array & $assocRowset, $mappingName, $oneToOne, $refKeyName);

    /**
     * 从查询句柄提取一条记录，并返回该记录的第一个字段
     *
     * @return mixed
     */
    abstract public function getOne();

    /**
     * 从查询句柄提取一条记录
     *
     * @return array
     */
    abstract public function getRow();

    /**
     * 从查询句柄提取记录集，并返回包含每行指定列数据的数组，如果 $col 为 0，则返回第一列
     *
     * @param int|string $col
     *
     * @return array
     */
    abstract function getCol($col = 0);
}
