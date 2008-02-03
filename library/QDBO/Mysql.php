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
 * 定义 QDBO_Mysql 类
 *
 * @package DB
 * @version $Id$
 */

/**
 * QDBO_Mysql 提供了对 mysql 数据库的支持
 *
 * @package DB
 */
class QDBO_Mysql extends QDBO_Abstract
{
    protected $_BIND_ENABLED = false;

    function __construct($dsn, $id)
    {
        if (!is_array($dsn)) {
            $dsn = QDBO_Abstract::parseDSN($dsn);
        }
        parent::__construct($dsn, $id);
        $this->_schema = $dsn['database'];
    }

    function connect($pconnect = false, $forcenew = false)
    {
        if ($this->_conn) { return; }

        $this->_lastErr = null;
        $this->_lastErrCode = null;

        if (isset($this->_dsn['port']) && $this->_dsn['port'] != '') {
            $host = $this->_dsn['host'] . ':' . $this->_dsn['port'];
        } else {
            $host = $this->_dsn['host'];
        }
        if (!isset($this->_dsn['login'])) { $this->_dsn['login'] = ''; }
        if (!isset($this->_dsn['password'])) { $this->_dsn['password'] = ''; }

        if ($pconnect) {
            $this->_conn = mysql_pconnect($host, $this->_dsn['login'], $this->_dsn['password'], $forcenew);
        } else {
            $this->_conn = mysql_connect($host, $this->_dsn['login'], $this->_dsn['password'], $forcenew);
        }

        if (!$this->_conn) {
            throw new QDBO_Exception('CONNECT DATABASE', mysql_error(), mysql_errno());
        }

        if (!empty($this->_dsn['database'])) {
            $this->selectDB($this->_dsn['database']);
        }

        if (isset($this->_dsn['charset']) && $this->_dsn['charset'] != '') {
            $charset = $this->_dsn['charset'];
            $this->execute("SET NAMES '" . $charset . "'");
        }
    }

    function pconnect()
    {
        $this->_connect(true);
    }

    function nconnect()
    {
        $this->_connect(false, true);
    }

    function close()
    {
        if ($this->_conn) { mysql_close($this->_conn); }
        parent::close();
    }

    function selectDB($database)
    {
        if (!mysql_select_db($database, $this->_conn)) {
            throw new QDBO_Exception("USE {$database}", mysql_error($this->_conn), mysql_errno($this->_conn));
        }
    }

    function qstr($value)
    {
        if (is_int($value)) { return $value; }
        if (is_bool($value)) { return $value ? $this->_TRUE_VALUE : $this->_FALSE_VALUE; }
        if (is_null($value)) { return $this->_NULL_VALUE; }
        return "'" . mysql_real_escape_string($value, $this->_conn) . "'";
    }

    function qtable($tableName, $schema = null)
    {
        if (strpos($tableName, '.') !== false) {
            $parts = explode('.', $tableName);
            $tableName = $parts[1];
            $schema = $parts[0];
        }
        $tableName = trim($tableName, '`');
        $schema = trim($schema, '`');
        return $schema != '' ? "`{$schema}`.`{$tableName}`" : "`{$tableName}`";
    }

    function qfield($fieldName, $tableName = null, $schema = null)
    {
        if (strpos($fieldName, '.') !== false) {
            $parts = explode('.', $fieldName);
            if (isset($parts[2])) {
                $schema = $parts[0];
                $tableName = $parts[1];
                $fieldName = $parts[2];
            } elseif (isset($parts[1])) {
                $tableName = $parts[0];
                $fieldName = $parts[1];
            }
        }
        $fieldName = trim($fieldName, '`');
        $fieldName = ($fieldName == '*') ? '*' : "`{$fieldName}`";
        return $tableName != '' ? $this->qtable($tableName, $schema) . '.' . $fieldName : $fieldName;
    }

    function nextID($seqname = 'qdbo_global_seq', $startValue = 1)
    {
        $nextSql = sprintf('UPDATE %s SET id = LAST_INSERT_ID(id + 1)', $seqname);

        $successed = false;
        try {
            // 首先产生下一个序列值
            $this->execute($nextSql);
            if ($this->affectedRows() > 0) {
                $successed = true;
            }
        } catch (QDBO_Exception $ex) {
            // 产生序列值失败，创建序列表
            unset($ex);
            $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $seqname));
        }

        if (!$successed) {
            // 没有更新任何记录或者新创建序列表，都需要插入初始的记录
            if ($this->getOne(sprintf('SELECT COUNT(*) FROM %s', $seqname)) == 0) {
                $sql = sprintf('INSERT INTO %s VALUES (%s)', $seqname, $startValue);
                $this->execute($sql);
            }
            $this->execute($nextSql);
        }
        // 获得新的序列值
        $this->_insertID = $this->insertID();
        return $this->_insertID;
    }

    function createSeq($seqname = 'qdbo_global_seq', $startValue = 1)
    {
        $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $seqname));
        $sql = sprintf('INSERT INTO %s VALUES (%s)', $seqname, $startValue);
        $this->execute($sql);
    }

    function dropSeq($seqname = 'qdbo_global_seq')
    {
        $this->execute(sprintf('DROP TABLE %s', $seqname));
    }

    function insertID()
    {
        return mysql_insert_id($this->_conn);
    }

    function affectedRows()
    {
        return mysql_affected_rows($this->_conn);
    }

    function execute($sql, $inputarr = null)
    {
        $this->queryCount++;
        if ($this->_LOG_QUERY) {
            $this->log[] = $sql;
        }

        if (is_array($inputarr)) {
            $sql = $this->fakebind($sql, $inputarr);
        }

        $result = mysql_query($sql, $this->_conn);
        if (is_resource($result)) {
        	Q::loadClass('QDBO_Result_Mysql');
            return new QDBO_Result_Mysql($result, $this->_fetchMode);
        } elseif ($result) {
            $this->_lastErr = null;
            $this->_lastErrCode = null;
            return $result;
        } else {
            $this->_lastErr = mysql_error($this->_conn);
            $this->_lastErrCode = mysql_errno($this->_conn);
            $this->_hasFailedQuery = true;
            throw new QDBO_Exception($sql, $this->_lastErr, $this->_lastErrCode);
        }
    }

    function selectLimit($sql, $length = null, $offset = null, array $inputarr = null)
    {
        if (!is_null($offset)) {
            $sql .= ' LIMIT ' . (int)$offset;
            if (!is_null($length)) {
                $sql .= ', ' . (int)$length;
            } else {
                $sql .= ', 18446744073709551615';
            }
        } elseif (!is_null($length)) {
            $sql .= ' LIMIT ' . (int)$length;
        }
        return $this->execute($sql, $inputarr);
    }

    function startTrans()
    {
        if (!$this->_TRANSACTION_ENABLED) { return false; }
        if ($this->_transCount == 0) {
            $this->execute('START TRANSACTION');
            $this->_hasFailedQuery = false;
        } elseif ($this->_transCount && $this->_SAVEPOINT_ENABLED) {
            $savepoint = 'savepoint_' . $this->_transCount;
            $this->execute("SAVEPOINT `{$savepoint}`");
            array_push($this->_savepointsStack, $savepoint);
        }
        $this->_transCount++;
    }

    function completeTrans($commitOnNoErrors = true)
    {
        if ($this->_transCount == 0) { return; }
        $this->_transCount--;
        if ($this->_transCount == 0) {
            if ($this->_hasFailedQuery == false && $commitOnNoErrors) {
                $this->execute('COMMIT');
            } else {
                $this->execute('ROLLBACK');
            }
        } elseif ($this->_SAVEPOINT_ENABLED) {
            $savepoint = array_pop($this->_savepointsStack);
            if ($this->_hasFailedQuery || $commitOnNoErrors == false) {
                $this->execute("ROLLBACK TO SAVEPOINT `{$savepoint}`");
            }
        }
    }

    function metaColumns($tableName, $schema = null)
    {
        static $typeMap = array(
            'bit'           => 'i',
            'tinyint'       => 'i',
            'bool'          => 'l',
            'boolean'       => 'l',
            'smallint'      => 'i',
            'mediumint'     => 'i',
            'int'           => 'i',
            'integer'       => 'i',
            'bigint'        => 'i',
            'float'         => 'n',
            'double'        => 'n',
            'doubleprecision' => 'n',
            'float'         => 'n',
            'decimal'       => 'n',
            'dec'           => 'n',

            'date'          => 'd',
            'datetime'      => 't',
            'timestamp'     => 't',
            'time'          => 't',
            'year'          => 'i',

            'char'          => 'c',
            'nchar'         => 'c',
            'varchar'       => 'c',
            'nvarchar'      => 'c',
            'binary'        => 'b',
            'varbinary'     => 'b',
            'tinyblob'      => 'x',
            'tinytext'      => 'x',
            'blob'          => 'x',
            'text'          => 'x',
            'mediumblob'    => 'x',
            'mediumtext'    => 'x',
            'longblob'      => 'x',
            'longtext'      => 'x',
            'enum'          => 'c',
            'set'           => 'c',
        );

        $tableName = $this->qtable($tableName, $schema);
        $rs = $this->execute(sprintf('SHOW FULL COLUMNS FROM %s', $tableName));
        if (!$rs) { return false; }
        /* @var $rs QDBO_Result_Abstract */
        $retarr = array();
        $rs->fetchMode = self::FETCH_MODE_ARRAY;
        while (($row = $rs->fetchRow())) {
            $field = array();
            $field['name'] = $row['Field'];
            $type = strtolower($row['Type']);

            $field['scale'] = null;
            $queryArray = false;
            if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $field['length'] = is_numeric($queryArray[2]) ? $queryArray[2] : -1;
                $field['scale'] = is_numeric($queryArray[3]) ? $queryArray[3] : -1;
            } elseif (preg_match('/^(.+)\((\d+)/', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $field['length'] = is_numeric($queryArray[2]) ? $queryArray[2] : -1;
            } elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $arr = explode(",",$queryArray[2]);
                $field['enums'] = $arr;
                $zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
                $field['length'] = ($zlen > 0) ? $zlen : 1;
            } else {
                $field['type'] = $type;
                $field['length'] = -1;
            }
            $field['ptype'] = $typeMap[strtolower($field['type'])];
            $field['not_null'] = ($row['Null'] != 'YES');
            $field['pk'] = ($row['Key'] == 'PRI');
            $field['auto_incr'] = (strpos($row['Extra'], 'auto_incr') !== false);
            if ($field['auto_incr']) { $field['ptype'] = 'r'; }
            $field['binary'] = (strpos($type,'blob') !== false);
            $field['unsigned'] = (strpos($type,'unsigned') !== false);

            if (!$field['binary']) {
                $d = $row['Default'];
                if ($d != '' && $d != 'NULL') {
                    $field['has_default'] = true;
                    $field['default'] = $d;
                } else {
                    $field['has_default'] = false;
                }
            }

            if ($field['type'] == 'tinyint' && $field['length'] == 1) {
                $field['ptype'] = 'l';
            }

            $field['desc'] = isset($row['Comment']) ? $row['Comment'] : '';

            $retarr[strtolower($field['name'])] = $field;
        }
        return $retarr;
    }

    function metaTables($pattern = null, $schema = null)
    {
        $sql = 'SHOW TABLES';
		if ($schema != '') {
		    $sql .= " FROM `{$schema}`";
		}
		if ($pattern != '') {
		    $sql .= ' LIKE ' . $this->qstr($pattern);
		}
		$rs = $this->execute($sql);
		/* @var $rs QDBO_Result_Abstract */
		$tables = array();
		while (($tableName = $rs->fetchOne())) {
		   $tables[] = $tableName;
		}
		return $tables;
    }

    protected function fakebind($sql, & $inputarr)
    {
        $arr = explode('?', $sql);
        $sql = array_shift($arr);
        foreach ($inputarr as $value) {
            if (isset($arr[0])) {
                $sql .= $this->qstr($value) . array_shift($arr);
            }
        }
        return $sql;
    }
}
