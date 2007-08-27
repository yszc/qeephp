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
 * 定义 FLEA_Db_Driver_Mysqli 类、FLEA_Db_Driver_Mysqli_Handle 类和 FLEA_Db_Driver_Mysqli_Statement 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Database
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Db/Driver.php';
// }}}

/**
 * FLEA_Db_Driver_Mysqli 是 MySQLi 扩展的驱动程序
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Driver_Mysqli extends FLEA_Db_Driver
{
    /**
     * 指示查询参数的样式
     *
     * @var const
     */
    public $paramStyle = self::PARAM_QM;

    /**
     * 指示事务启动次数
     *
     * @var int
     */
    protected $_transCount = 0;

    public function connect()
    {
        if ($this->_conn) { return; }

        $dsn = $this->_dsn;
        $host       = empty($dsn['host'])     ? 'localhost' : $dsn['host'];
        $port       = empty($dsn['port'])     ? null : $dsn['port'];
        $username   = empty($dsn['login'])    ? null : $dsn['login'];
        $password   = empty($dsn['password']) ? null : $dsn['password'];
        $socket     = empty($dsn['socket'])   ? null : $dsn['socket'];
        $database   = empty($dsn['database']) ? null : $dsn['database'];
        $options    = empty($dsn['options'])  ? array() : (array)$dsn['options'];
        $flags      = empty($dsn['flags'])    ? null : $dsn['flags'];

        if (!function_exists('mysqli_init')) {
            require_once 'FLEA/Db/Exception/ExtNotLoaded.php';
            throw new FLEA_Db_Exception_ExtNotLoaded('MySQL Improved', 'mysqli');
        }
        $this->_conn = mysqli_init();
        if (!$this->_conn) {
            require_once 'FLEA/Db/Exception/ConnectionFailed.php';
            throw new FLEA_Db_Exception_ConnectionFailed('MySQL Improved', 'mysqli');
        }

		foreach($options as $pair) {
		    if (!isset($pair[1])) { continue; }
			mysqli_options($this->_conn, $pair[0], $pair[1]);
		}

		$ret = mysqli_real_connect($this->_conn, $host, $username, $password, $database, $port, $socket, $flags);
        if (!$ret) {
            require_once 'FLEA/Db/Exception/ConnectionFailed.php';
            throw new FLEA_Db_Exception_ConnectionFailed('MySQL Improved', 'mysqli');
        }

        if (!empty($dsn['charset'])) {
            $this->execute('SET NAMES ' . $this->qstr($dsn['charset']));
        }

        $this->_insertId = null;
        $this->_transCount = 0;
        $this->_hasFailedQuery = false;
    }

    public function close()
    {
        if ($this->_conn) {
            mysqli_close($this->_conn);
        }
        $this->_conn = null;
        $this->_insertId = null;
        $this->_transCount = 0;
        $this->_hasFailedQuery = true;
    }

    public function selectDB($database)
    {
        if (!mysqli_select_db($this->_conn, $database)) {
            require_once 'FLEA/Db/Exception/UseDatabaseFailed.php';
            throw new FLEA_Db_Exception_UseDatabaseFailed('MySQL Improved', 'mysqli', $database);
        }
    }

    public function qstr($value)
    {
        if (is_bool($value)) { return $value ? $this->_TRUE_VALUE : $this->_FALSE_VALUE; }
        if (is_null($value)) { return $this->_NULL_VALUE; }
        return "'" . mysqli_real_escape_string($this->_conn, $value) . "'";
    }

    public function qtable($tableName, $schema = null)
    {
        if (empty($schema)) {
            return "`{$tableName}`";
        } else {
            return "`{$schema}`.`{$tableName}`";
        }
    }

    public function qfield($fieldName, $tableName = null, $schema = null)
    {
        $schema = empty($schema) ? '' : "`{$schema}`.";
        $tableName = empty($tableName) ? '' : "`{$tableName}`.";
        if ($fieldName == '*') {
            return "{$schema}{$tableName}*";
        } else {
            return "{$schema}{$tableName}`{$fieldName}`";
        }
    }

    public function qfields($fields, $tableName = null, $schema = null, $returnArray = false)
    {
        $schema = empty($schema) ? '' : "`{$schema}`.";
        $tableName = empty($tableName) ? '' : "`{$tableName}`.";
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $fields = array_filter(array_map('trim', $fields), 'strlen');
        $return = array();
        foreach ($fields as $fieldName) {
            if ($fieldName == '*') {
                $return[] = "{$schema}{$tableName}*";
            } else {
                $return[] = "{$schema}{$tableName}`{$fieldName}`";
            }
        }
        if ($returnArray) { return $return; }
        return implode(', ', $return);
    }

    public function nextId($seqName = 'sdboseq', $startValue = 1)
    {
        $tableName = $this->qtable($seqName);
        $startValue = (int)$startValue;
        try {
            $this->execute("UPDATE {$tableName} SET id = LAST_INSERT_ID(id + 1)");
            if (mysqli_affected_rows($this->_conn) == 0) {
                $startValue--;
                $this->execute("INSERT INTO {$tableName} (id) VALUES ({$startValue})");
                $this->execute("UPDATE {$tableName} SET id = LAST_INSERT_ID(id + 1)");
            }
        } catch (Exception $ex) {
            $this->createSeq($seqName, $startValue);
        }

        $nextid = $this->getOne('SELECT LAST_INSERT_ID()');
        $this->_insertId = $nextid;
        return $nextid;
    }

    public function createSeq($seqName = 'sdboseq', $startValue = 1)
    {
        $tableName = $this->qtable($seqName);
        $startValue = (int)$startValue - 1;
        $this->execute("CREATE TABLE {$tableName} (id INT NOT NULL)");
        $this->execute("INSERT INTO {$tableName} (id) VALUES ({$startValue})");
        $this->execute("UPDATE {$tableName} SET id = LAST_INSERT_ID(id + 1)");
        $this->_insertId = $startValue + 1;
    }

    public function dropSeq($seqName = 'sdboseq')
    {
        $tableName = $this->qtable($seqName);
        $this->execute("DROP TABLE {$tableName}");
    }

    /**
     * 获取自增字段的最后一个值或者 nextId() 方法产生的最后一个值
     *
     * @return mixed
     */
    public function insertId()
    {
        $insertId = $this->getOne('SELECT LAST_INSERT_ID()');
        if (!empty($insertId)) {
            return $insertId;
        } else {
            return $this->_insertId;
        }
    }

    public function affectedRows()
    {
        return mysqli_affected_rows($this->_conn);
    }

    public function execute($sql, $inputarr = null)
    {
        if (!empty($inputarr)) {
            $stmt = mysqli_prepare($this->_conn, $sql);
            if (!$stmt) {
                $this->_hasFailedQuery = true;
                require_once 'FLEA/Db/Exception/Query.php';
                throw new FLEA_Db_Exception_Query($sql, mysqli_error($this->_conn));
            }
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
            array_unshift($inputarr, $types);
            array_unshift($inputarr, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $inputarr);
            if (!mysqli_stmt_execute($stmt)) {
                $this->_hasFailedQuery = true;
                require_once 'FLEA/Db/Exception/Query.php';
                throw new FLEA_Db_Exception_Query($sql, mysqli_stmt_error($stmt));
            }

            return new FLEA_Db_Driver_Mysqli_Statement($stmt, $this->fetchMode);
        } else {
            $result = mysqli_query($this->_conn, $sql);
            if ($result === false) {
                $this->_hasFailedQuery = true;
                require_once 'FLEA/Db/Exception/Query.php';
                throw new FLEA_Db_Exception_Query($sql, mysqli_error($this->_conn));
            }

            return new FLEA_Db_Driver_Mysqli_Handle($result, $this->fetchMode);
        }
    }

    public function selectLimit($sql, $length = null, $offset = null, array $inputarr = null)
    {
        if ($offset !== null) {
            $sql .= "\nLIMIT " . (int)$offset;
            if ($length !== null) {
                $sql .= ', ' . (int)$length;
            } else {
                $sql .= ', 18446744073709551615';
            }
        } elseif ($length !== null) {
            $sql .= "\nLIMIT " . (int)$length;
        }
        return $this->execute($sql, $inputarr);
    }

    public function startTrans()
    {
        if ($this->_transCount == 0) {
            $this->execute('START TRANSACTION');
            $this->_hasFailedQuery = false;
        }
        $this->_transCount++;
        if ($this->savepointEnabled) {
            $savepoint = 'savepoint_' . $this->_transCount;
            $this->execute("SAVEPOINT `{$savepoint}`");
            array_push($this->_savepointStack, $savepoint);
        }
    }

    public function completeTrans($commitOnNoErrors = true)
    {
        if ($this->_transCount == 0) { return; }
        $this->_transCount--;
        if ($this->savepointEnabled) {
            $savepoint = array_pop($this->_savepointStack);
            if ($this->_hasFailedQuery || $commitOnNoErrors == false) {
                $this->execute("ROLLBACK TO SAVEPOINT `{$savepoint}`");
            }
            return;
        } else {
            if ($this->_transCount > 0) { return; }
        }
        if ($this->_hasFailedQuery == false && $commitOnNoErrors) {
            $this->execute('COMMIT');
        } else {
            $this->execute('ROLLBACK');
        }
    }

    public function & metaColumns($table, $schema = null)
    {
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

        $qtable = $this->qtable($table, $schema);
        $handle = $this->execute("SHOW COLUMNS FROM {$qtable}");

        $retarr = array();
        while ($row = $handle->getRow()) {
            $field = array();
            $field['name'] = $row['Field'];
            $type = $row['Type'];

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
                $zlen = max(array_map("strlen",$arr)) - 2;
                $field['maxLength'] = ($zlen > 0) ? $zlen : 1;
            } else {
                $field['type'] = $type;
                $field['maxLength'] = -1;
            }
            $field['simpleType'] = $typeMap[strtoupper($field['type'])];
            $field['notNull'] = ($row['Null'] != 'YES');
            $field['primaryKey'] = ($row['Key'] == 'PRI');
            $field['autoIncrement'] = (strpos($row['Extra'], 'auto_increment') !== false);
            if ($field['autoIncrement']) { $field['simpleType'] = 'R'; }
            $field['binary'] = (strpos($type,'blob') !== false);
            $field['unsigned'] = (strpos($type,'unsigned') !== false);

            if (!$field['binary']) {
                $d = $row['Default'];
                if ($d != '' && $d != 'NULL') {
                    $field['hasDefault'] = true;
                    $field['defaultValue'] = $d;
                } else {
                    $field['hasDefault'] = false;
                }
            }
            $retarr[strtoupper($field['name'])] = $field;
        }
        return $retarr;
    }
}

/**
 * FLEA_Db_Driver_Mysqli_Handle 封装了一个查询句柄，便于释放资源
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Driver_Mysqli_Handle extends FLEA_Db_Driver_Handle
{
    public function free()
    {
        if ($this->_handle) { mysqli_free_result($this->_handle); }
        $this->_handle = null;
    }

    public function fetchRow()
    {
        if ($this->fetchMode == FLEA_Db_Driver::FETCH_MODE_ASSOC) {
            return mysqli_fetch_assoc($this->_handle);
        } else {
            return mysqli_fetch_array($this->_handle);
        }
    }
}

/**
 * FLEA_Db_Driver_Mysqli_Statement 封装了一个查询过程对象，便于释放资源
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Driver_Mysqli_Statement extends FLEA_Db_Driver_Handle
{
    /**
     * 查询过程可能返回的结果集包含的字段
     *
     * @var array
     */
    protected $_stmtFields;

    /**
     * 保存当前记录的数据
     *
     * @var array
     */
    protected $_currentRow;

    /**
     * 用于组合查询记录的回调函数
     *
     * @var callback
     */
    protected $_callback;

    public function __construct(mysqli_stmt $handle, $fetchMode)
    {
        parent::__construct($handle, $fetchMode);
        $this->_prepareResult();
    }

    public function free()
    {
        if ($this->_handle) { mysqli_stmt_close($this->_handle); }
        $this->_handle = null;
    }

    public function fetchRow()
    {
        if (!mysqli_stmt_fetch($this->_handle)) {
            return null;
        }
        $row = call_user_func($this->_callback, $this->_currentRow);
        if ($this->fetchMode == FLEA_Db_Driver::FETCH_MODE_ASSOC) {
            return $row;
        } else {
            return array_values($row);
        }
    }

    protected function _prepareResult()
    {
        if (is_null($this->_handle)) { return; }
        $result = mysqli_stmt_result_metadata($this->_handle);
        if (!is_object($result)) { return; }
        /* @var $result mysqli_result */
        $meta = $result->fetch_fields();
        if (empty($meta)) { return; }

        $this->_stmtFields = array();
        $this->_currentRow = array();
        $bindVars = array();
        $func = 'return array(';
        foreach ($meta as $f) {
            $name = str_replace(' ', '_', $f->name);
            $this->_currentRow[$name] = null;
            $bindVars[] = & $this->_currentRow[$name];
            $this->_stmtFields[$name] = $f->name;
            $func .= "'{$f->name}' => \$row['{$name}'], ";
        }
        $func = substr($func, 0, -2);
        $func .= ');';
        $this->_callback = create_function('$row', $func);

        call_user_func_array(array($this->_handle, 'bind_result'), $bindVars);
    }
}
