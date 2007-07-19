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
 * 定义 FLEA_Db_Driver_Mysqli 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Database
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Db/Driver/Abstract.php';
// }}}

/**
 * FLEA_Db_Driver_Mysqli 是 MySQLi 扩展的驱动程序
 *
 * @package Database
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_Driver_Mysqli extends FLEA_Db_Driver_Abstract
{
    /**
     * 用于 genSeq()、dropSeq() 和 nextId() 的 SQL 查询语句
     */
    const NEXT_ID_SQL    = "UPDATE %s SET id = LAST_INSERT_ID(id + 1)";
    const CREATE_SEQ_SQL = "CREATE TABLE %s (id INT NOT NULL)";
    const INIT_SEQ_SQL   = "INSERT INTO %s VALUES (%s)";
    const DROP_SEQ_SQL   = "DROP TABLE %s";

    /**
     * 用于获取元数据的 SQL 查询语句
     */
    const META_COLUMNS_SQL = "SHOW COLUMNS FROM %s";

    /**
     * 指示事务启动次数
     *
     * @var int
     */
    protected $_transCount = 0;

    /**
     * 指示事务是否提交
     *
     * @var boolean
     */
    protected $_transCommit = true;

    /**
     * 连接数据库，失败时抛出异常
     */
    public function connect()
    {
        if ($this->_conn) { return; }
        $this->_lasterr = null;
        $this->_lasterrcode = null;

        $dsn = $this->_dsn;
        $host = empty($dsn['host']) ? 'localhost' : $dsn['host'];
        $port = empty($dsn['port']) ? null : $dsn['port'];
        $username = empty($dsn['login']) ? null : $dsn['login'];
        $password = empty($dsn['password']) ? null : $dsn['password'];
        $socket = empty($dsn['socket']) ? null : $dsn['socket'];
        $database = empty($dsn['database']) ? null : $dsn['database'];
        $options = empty($dsn['options']) ? null : $dsn['options'];
        $flags = empty($dsn['flags']) ? null : $dsn['flags'];

        $this->_conn = mysqli_init();
        if (!$this->_conn) {
            require_once 'FLEA/Db/Exception/ConnectionFailed.php';
            throw new FLEA_Db_Exception_ConnectionFailed('MySQL', 'mysqli');
        }

		foreach($options as $pair) {
			mysqli_options($this->_conn, $pair[0], $pair[1]);
		}

		$ret = mysqli_real_connect($this->_conn, $host, $username, $password, $database, $port, $socket, $flags);
        if (!$ret) {
            require_once 'FLEA/Db/Exception/ConnectionFailed.php';
            throw new FLEA_Db_Exception_ConnectionFailed('MySQL', 'mysqli');
        }

        $charset = empty($dsn['charset']) ? FLEA::getAppInf('databaseCharset') : $dsn['charset'];
        $this->execute('SET NAMES ' . $this->qstr($charset));
    }

    /**
     * 关闭数据库连接，失败时抛出异常
     */
    public function close()
    {
        if ($this->_conn) {
            if (!mysqli_close($this->_conn)) {
                require_once 'FLEA/Db/Exception/DisconnectionFailed.php';
                throw new FLEA_Db_Exception_DisconnectionFailed('MySQL', 'mysqli');
            }
        }
        $this->_conn = null;
        $this->_insertId = null;
        $this->_transCount = 0;
        $this->_transCommit = true;
    }

    /**
     * 执行一个查询，返回一个 resource 或者 boolean 值，出错时抛出异常
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return resource|boolean
     */
    public function execute($sql, $inputarr = null)
    {
        if ($this->enableLog) { $this->log[] = $sql; }

        if (empty($inputarr)) {
            $stmt = mysqli_prepare($this->_conn, $sql);
            $types = '';
            foreach ($inputarr as $v) {
                if (is_string($v)) {
                    $types .= 's';
                } else if (is_int($v)) {
                    $types .= 'i';
                } else {
                    $types .= 'd';
                }
            }
            array_unshift($inputarr, array($stmt, $types));
            call_user_func_array('mysqli_stmt_bind_param', $inputarr);
            $ret = mysqli_stmt_execute($stmt);
        } else {
            $ret = mysqli_query($this->_conn, $sql);
        }

        if ($ret) { return $ret; }

        require_once 'FLEA/Db/Exception/SqlQuery.php';
        throw new FLEA_Db_Exception_SqlQuery($sql, mysqli_error($this->_conn), mysqli_errno($this->_conn));
    }

    /**
     * 转义值
     *
     * @param mixed $value
     *
     * @return string
     */
    public function qstr($value)
    {
        if (is_bool($value)) { return $value ? self::TRUE_VALUE : self::FALSE_VALUE; }
        if (is_null($value)) { return self::NULL_VALUE; }
        return "'" . mysqli_real_escape_string($this->_conn, $value) . "'";
    }

    /**
     * 将数据表名字转换为完全限定名
     *
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    public function qtable($tableName, $schema = null)
    {
        return "`{$tableName}`";
    }

    /**
     * 将字段名转换为完全限定名，避免因为字段名和数据库关键词相同导致的错误
     *
     * @param string $fieldName
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    public function qfield($fieldName, $tableName = null, $schema = null)
    {
        $pos = strpos($fieldName, '.');
        if ($pos !== false) {
            $tableName = substr($fieldName, 0, $pos);
            $fieldName = substr($fieldName, $pos + 1);
        }
        if ($tableName != '') {
            if ($fieldName != '*') {
                return "`{$tableName}`.`{$fieldName}`";
            } else {
                return "`{$tableName}`.*";
            }
        } else {
            if ($fieldName != '*') {
                return "`{$fieldName}`";
            } else {
                return "*";
            }
        }
    }

    /**
     * 一次性将多个字段名转换为完全限定名
     *
     * @param string|array $fields
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    public function qfields($fields, $tableName = null, $schema = null)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $return = array();
        foreach ($fields as $fieldName) {
            $fieldName = trim($fieldName);
            if ($fieldName == '') { continue; }
            $pos = strpos($fieldName, '.');
            if ($pos !== false) {
                $tableName = substr($fieldName, 0, $pos);
                $fieldName = substr($fieldName, $pos + 1);
            }
            if ($tableName != '') {
                if ($fieldName != '*') {
                    $return[] = "`{$tableName}`.`{$fieldName}`";
                } else {
                    $return[] = "`{$tableName}`.*";
                }
            } else {
                if ($fieldName != '*') {
                    $return[] = "`{$fieldName}`";
                } else {
                    $return[] = '*';
                }
            }
        }
        return implode(', ', $return);
    }

    /**
     * 为数据表产生下一个序列值，失败时抛出异常
     *
     * @param string $seqName
     * @param string $startValue
     *
     * @return int
     */
    public function nextId($seqName = 'sdboseq', $startValue = 1)
    {
        try {
            $this->execute(sprintf(self::NEXT_ID_SQL, $seqName), null, false);
        } catch (FLEA_Db_Exception_SqlQuery $ex) {
            $this->createSeq($seqName, $startValue);
            $this->execute(sprintf(self::NEXT_ID_SQL, $seqName));
        }

        $id = $this->insertId();
        if ($id) { return $id; }
        $this->execute(sprintf(self::INIT_SEQ_SQL, $seqName, $startValue));
        return $startValue;
    }

    /**
     * 创建一个新的序列，失败时抛出异常
     *
     * @param string $seqName
     * @param int $startValue
     */
    public function createSeq($seqName = 'sdboseq', $startValue = 1)
    {
        try {
            $this->execute(sprintf(self::CREATE_SEQ_SQL, $seqName));
        } catch (FLEA_Db_Exception_SqlQuery $ex) {
            $this->execute(sprintf(self::INIT_SEQ_SQL, $seqName, $startValue - 1));
        }
    }

    /**
     * 删除一个序列，失败时抛出异常
     *
     * @param string $seqName
     */
    public function dropSeq($seqName = 'sdboseq')
    {
        $this->execute(sprintf(self::DROP_SEQ_SQL, $seqName));
    }

    /**
     * 获取自增字段的最后一个值或者 nextId() 方法产生的最后一个值
     *
     * @return mixed
     */
    public function insertId()
    {
        return mysqli_insert_id($this->_conn);
    }

    /**
     * 返回最近一次数据库操作受到影响的记录数
     *
     * @return int
     */
    public function affectedRows()
    {
        return mysqli_affected_rows($this->_conn);
    }

    /**
     * 从记录集中返回一行数据，失败抛出异常
     *
     * @param resouce $res
     *
     * @return array
     */
    public function fetchRow($res)
    {
        return mysqli_fetch_row($res);
    }

    /**
     * 从记录集中返回一行数据，字段名作为键名，失败抛出异常
     *
     * @param resouce $res
     *
     * @return array
     */
    public function fetchAssoc($res)
    {
        return mysqli_fetch_assoc($res);
    }

    /**
     * 释放查询句柄
     *
     * @param resource $res
     *
     * @return boolean
     */
    function freeRes($res)
    {
        return mysql_free_result($res);
    }

    /**
     * 进行限定记录集的查询
     *
     * @param string $sql
     * @param int $length
     * @param int $offset
     *
     * @return resource
     */
    function selectLimit($sql, $length = null, $offset = null)
    {
        if ($offset !== null) {
            $sql .= "\nLIMIT " . (int)$offset;
            if ($length !== null) {
                $sql .= ', ' . (int)$length;
            } else {
                $sql .= ', 4294967294';
            }
        } elseif ($length !== null) {
            $sql .= "\nLIMIT " . (int)$length;
        }
        return $this->execute($sql);
    }

    /**
     * 执行一个查询，返回查询结果记录集
     *
     * @param string|resource $sql
     *
     * @return array
     */
    function & getAll($sql)
    {
        if (is_resource($sql)) {
            $res = $sql;
        } else {
            $res = $this->execute($sql);
        }
        $data = array();
        while ($row = mysql_fetch_assoc($res)) {
            $data[] = $row;
        }
        mysql_free_result($res);
        return $data;
    }

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
    function & getAllGroupBy($sql, $groupBy)
    {
        if (is_resource($sql)) {
            $res = $sql;
        } else {
            $res = $this->execute($sql);
        }
        $data = array();

        $row = mysql_fetch_assoc($res);
        if ($row != false) {
            if ($groupBy === true) {
                $groupBy = key($row);
            }
            do {
                $rkv = $row[$groupBy];
                unset($row[$groupBy]);
                $data[$rkv][] = $row;
            } while ($row = mysql_fetch_assoc($res));
        }

        mysql_free_result($res);

        return $data;
    }

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
    function getAllWithFieldRefs($sql, $field, & $fieldValues, & $reference)
    {
        if (is_resource($sql)) {
            $res = $sql;
        } else {
            $res = $this->execute($sql);
        }

        $fieldValues = array();
        $reference = array();
        $offset = 0;
        $data = array();
        while ($row = mysql_fetch_assoc($res)) {
            $fieldValue = $row[$field];
            unset($row[$field]);
            $data[$offset] = $row;
            $fieldValues[$offset] = $fieldValue;
            $reference[$fieldValue] =& $data[$offset];
            $offset++;
        }
        mysql_free_result($res);
        return $data;
    }

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
    function assemble($sql, & $assocRowset, $mappingName, $oneToOne, $refKeyName, $limit = null)
    {
        if (is_resource($sql)) {
            $res = $sql;
        } else {
            if ($limit !== null) {
                if (is_array($limit)) {
                    list($length, $offset) = $limit;
                } else {
                    $length = $limit;
                    $offset = 0;
                }
                $res = $this->selectLimit($sql, $length, $offset);
            } else {
                $res = $this->execute($sql);
            }
        }

        if ($oneToOne) {
            // 一对一组装数据
            while ($row = mysql_fetch_assoc($res)) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName] = $row;
            }
        } else {
            // 一对多组装数据
            while ($row = mysql_fetch_assoc($res)) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName][] = $row;
            }
        }

        mysql_free_result($res);
    }

    /**
     * 执行查询，返回第一条记录的第一个字段
     *
     * @param string|resource $sql
     *
     * @return mixed
     */
    function getOne($sql)
    {
        if (is_resource($sql)) {
            $res = $sql;
        } else {
            $res = $this->execute($sql);
        }
        $row = mysql_fetch_row($res);
        mysql_free_result($res);
        return isset($row[0]) ? $row[0] : null;
    }

    /**
     * 执行查询，返回第一条记录
     *
     * @param string|resource $sql
     *
     * @return mixed
     */
    function & getRow($sql)
    {
        if (is_resource($sql)) {
            $res = $sql;
        } else {
            $res = $this->execute($sql);
        }
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);
        return $row;
    }

    /**
     * 执行查询，返回结果集的指定列
     *
     * @param string|resource $sql
     * @param int $col 要返回的列，0 为第一列
     *
     * @return mixed
     */
    function & getCol($sql, $col = 0)
    {
        if (is_resource($sql)) {
            $res = $sql;
        } else {
            $res = $this->execute($sql);
        }
        $data = array();
        while ($row = mysql_fetch_row($res)) {
            $data[] = $row[$col];
        }
        mysql_free_result($res);
        return $data;
    }

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
    function & metaColumns($table)
    {
        /**
         *  C 长度小于等于 250 的字符串
         *  X 长度大于 250 的字符串
         *  B 二进制数据
         *  N 数值或者浮点数
         *  D 日期
         *  T TimeStamp
         *  L 逻辑布尔值
         *  I 整数
         *  R 自动增量或计数器
         */
        static $typeMap = array(
            'BIT'           => 'I',
            'TINYINT'       => 'I',
            'BOOL'          => 'L',
            'BOOLEAN'       => 'L',
            'SMALLINT'      => 'I',
            'MEDIUMINT'     => 'I',
            'INT'           => 'I',
            'INTEGER'       => 'I',
            'BIGINT'        => 'I',
            'FLOAT'         => 'N',
            'DOUBLE'        => 'N',
            'DOUBLEPRECISION' => 'N',
            'FLOAT'         => 'N',
            'DECIMAL'       => 'N',
            'DEC'           => 'N',

            'DATE'          => 'D',
            'DATETIME'      => 'T',
            'TIMESTAMP'     => 'T',
            'TIME'          => 'T',
            'YEAR'          => 'I',

            'CHAR'          => 'C',
            'NCHAR'         => 'C',
            'VARCHAR'       => 'C',
            'NVARCHAR'      => 'C',
            'BINARY'        => 'B',
            'VARBINARY'     => 'B',
            'TINYBLOB'      => 'X',
            'TINYTEXT'      => 'X',
            'BLOB'          => 'X',
            'TEXT'          => 'X',
            'MEDIUMBLOB'    => 'X',
            'MEDIUMTEXT'    => 'X',
            'LONGBLOB'      => 'X',
            'LONGTEXT'      => 'X',
            'ENUM'          => 'C',
            'SET'           => 'C',
        );

        $table = $this->qtable($table);
        $rs = $this->execute(sprintf($this->META_COLUMNS_SQL, $table));
        if (!$rs) { return false; }
        $retarr = array();
        while (($row = mysql_fetch_row($rs))) {
            $field = array();
            $field['name'] = $row[0];
            $type = $row[1];

            $field['scale'] = null;
            $queryArray = false;
            if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $field['maxLength'] = is_numeric($queryArray[2]) ? $queryArray[2] : -1;
                $field['scale'] = is_numeric($queryArray[3]) ? $queryArray[3] : -1;
            } elseif (preg_match('/^(.+)\((\d+)/', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $field['maxLength'] = is_numeric($queryArray[2]) ? $queryArray[2] : -1;
            } elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $arr = explode(",",$queryArray[2]);
                $field['enums'] = $arr;
                $zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
                $field['maxLength'] = ($zlen > 0) ? $zlen : 1;
            } else {
                $field['type'] = $type;
                $field['maxLength'] = -1;
            }
            $field['simpleType'] = $typeMap[strtoupper($field['type'])];
            if ($field['simpleType'] == 'C' && $field['maxLength'] > 250) {
                $field['simpleType'] = 'X';
            }
            $field['notNull'] = ($row[2] != 'YES');
            $field['primaryKey'] = ($row[3] == 'PRI');
            $field['autoIncrement'] = (strpos($row[5], 'auto_increment') !== false);
            if ($field['autoIncrement']) { $field['simpleType'] = 'R'; }
            $field['binary'] = (strpos($type,'blob') !== false);
            $field['unsigned'] = (strpos($type,'unsigned') !== false);

            if (!$field['binary']) {
                $d = $row[4];
                if ($d != '' && $d != 'NULL') {
                    $field['hasDefault'] = true;
                    $field['defaultValue'] = $d;
                } else {
                    $field['hasDefault'] = false;
                }
            }
            $retarr[strtoupper($field['name'])] = $field;
        }
        mysql_free_result($rs);
        return $retarr;
    }

    /**
     * 返回数据库可以接受的日期格式
     *
     * @param int $timestamp
     */
    function dbTimeStamp($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * 启动事务
     */
    function startTrans()
    {
    }

    /**
     * 完成事务，根据查询是否出错决定是提交事务还是回滚事务
     *
     * 如果 $commitOnNoErrors 参数为 true，当事务中所有查询都成功完成时，则提交事务，否则回滚事务
     * 如果 $commitOnNoErrors 参数为 false，则强制回滚事务
     *
     * @param $commitOnNoErrors 指示在没有错误时是否提交事务
     */
    function completeTrans($commitOnNoErrors = true)
    {
        return false;
    }

    /**
     * 强制指示在调用 completeTrans() 时回滚事务
     */
    function failTrans()
    {
        $this->_transCommit = false;
    }

    /**
     * 反复事务是否失败的状态
     */
    function hasFailedTrans()
    {
        return true;
    }

    /**
     * 根据 SQL 语句和提供的参数数组，生成最终的 SQL 语句
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return string
     */
    function _prepareSql($sql, & $inputarr)
    {
        $sqlarr = explode('?', $sql);
        $sql = '';
        $ix = 0;
        foreach ($inputarr as $v) {
            $sql .= $sqlarr[$ix];
            $typ = gettype($v);
            if ($typ == 'string') {
                $sql .= $this->qstr($v);
            } else if ($typ == 'double') {
                $sql .= $this->qstr(str_replace(',', '.', $v));
            } else if ($typ == 'boolean') {
                $sql .= $v ? $this->TRUE_VALUE : $this->FALSE_VALUE;
            } else if ($v === null) {
                $sql .= 'NULL';
            } else {
                $sql .= $v;
            }
            $ix += 1;
        }
        if (isset($sqlarr[$ix])) {
            $sql .= $sqlarr[$ix];
        }
        return $sql;
    }
}

/**
 * 与 FLEA_Db_Driver_Mysql 的唯一区别在于 FLEA_Db_Driver_Mysqlt 支持事务功能
 *
 * 要求表的存储引擎为 InnoDB 或者 BDB。
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.1
 */
class FLEA_Db_Driver_Mysqlt extends FLEA_Db_Driver_Mysql
{
    /**
     * 指示事务启动次数
     *
     * @var int
     */
    var $_transCount = 0;

    /**
     * 指示事务是否提交
     *
     * @var boolean
     */
    var $_transCommit = true;

    /**
     * 启动事务
     */
    function startTrans()
    {
        $this->_transCount += 1;
        if ($this->_transCount == 1) {
            $this->execute('SET AUTOCOMMIT=0');
            $this->execute('BEGIN');
        }
    }

    /**
     * 完成事务，根据查询是否出错决定是提交事务还是回滚事务
     *
     * 如果 $commitOnNoErrors 参数为 true，当事务中所有查询都成功完成时，则提交事务，否则回滚事务
     * 如果 $commitOnNoErrors 参数为 false，则强制回滚事务
     *
     * @param $commitOnNoErrors 指示在没有错误时是否提交事务
     */
    function completeTrans($commitOnNoErrors = true)
    {
        if ($this->_transCount < 1) { return false; }
        if ($this->_transCount > 1) {
            $this->_transCount -= 1;
            return true;
        }
        $this->_transCount = 0;

        if ($this->_transCommit && $commitOnNoErrors) {
            $ret = $this->execute('COMMIT');
            $this->execute('SET AUTOCOMMIT=1');
            return $ret;
        } else {
            $this->execute('ROLLBACK');
            $this->execute('SET AUTOCOMMIT=1');
            return false;
        }
    }

    /**
     * 强制指示在调用 completeTrans() 时回滚事务
     */
    function failTrans()
    {
        $this->_transCommit = false;
    }

    /**
     * 反复事务是否失败的状态
     */
    function hasFailedTrans()
    {
        if ($this->_transCount > 0) {
            return $this->_transCommit === false;
        }
        return false;
    }
}
