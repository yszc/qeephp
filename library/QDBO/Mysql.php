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
 * 定义 QDBO_Mysql 驱动
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * 用于 mysql 扩展的数据库驱动程序
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.1
 */
class QDBO_Mysql extends QDBO_Abstract
{
    protected $BIND_ENABLED = false;

    function __construct($dsn, $id)
    {
        if (!is_array($dsn)) {
            $dsn = QDBO_Abstract::parse_dsn($dsn);
        }
        parent::__construct($dsn, $id);
        $this->schema = $dsn['database'];
    }

    function connect($pconnect = false, $forcenew = false)
    {
        if ($this->conn) { return; }

        $this->last_err = null;
        $this->last_errcode = null;

        if (isset($this->dsn['port']) && $this->dsn['port'] != '') {
            $host = $this->dsn['host'] . ':' . $this->dsn['port'];
        } else {
            $host = $this->dsn['host'];
        }
        if (!isset($this->dsn['login'])) { $this->dsn['login'] = ''; }
        if (!isset($this->dsn['password'])) { $this->dsn['password'] = ''; }

        if ($pconnect) {
            $this->conn = mysql_pconnect($host, $this->dsn['login'], $this->dsn['password'], $forcenew);
        } else {
            $this->conn = mysql_connect($host, $this->dsn['login'], $this->dsn['password'], $forcenew);
        }

        if (!$this->conn) {
            throw new QDBO_Exception('CONNECT DATABASE', mysql_error(), mysql_errno());
        }

        if (!empty($this->dsn['database'])) {
            $this->select_db($this->dsn['database']);
        }

        if (isset($this->dsn['charset']) && $this->dsn['charset'] != '') {
            $charset = $this->dsn['charset'];
            $this->execute("SET NAMES '" . $charset . "'");
        }
    }

    function pconnect()
    {
        $this->connect(true);
    }

    function nconnect()
    {
        $this->connect(false, true);
    }

    function is_connected()
    {
        return !is_null($this->conn);
    }

    function close()
    {
        if ($this->conn) { mysql_close($this->conn); }
        parent::close();
    }

    function select_db($database)
    {
        if (!mysql_select_db($database, $this->conn)) {
            throw new QDBO_Exception("USE {$database}", mysql_error($this->conn), mysql_errno($this->conn));
        }
    }

    function qstr($value)
    {
        if (is_int($value)) { return $value; }
        if (is_bool($value)) { return $value ? $this->TRUE_VALUE : $this->FALSE_VALUE; }
        if (is_null($value)) { return $this->NULL_VALUE; }
        return "'" . mysql_real_escape_string($value, $this->conn) . "'";
    }

    function qtable($table_name, $schema = null)
    {
        if (strpos($table_name, '.') !== false) {
            $parts = explode('.', $table_name);
            $table_name = $parts[1];
            $schema = $parts[0];
        }
        $table_name = trim($table_name, '`');
        $schema = trim($schema, '`');
        return $schema != '' ? "`{$schema}`.`{$table_name}`" : "`{$table_name}`";
    }

    function qfield($field_name, $table_name = null, $schema = null)
    {
        if (strpos($field_name, '.') !== false) {
            $parts = explode('.', $field_name);
            if (isset($parts[2])) {
                $schema = $parts[0];
                $table_name = $parts[1];
                $field_name = $parts[2];
            } elseif (isset($parts[1])) {
                $table_name = $parts[0];
                $field_name = $parts[1];
            }
        }
        $field_name = trim($field_name, '`');
        $field_name = ($field_name == '*') ? '*' : "`{$field_name}`";
        return $table_name != '' ? $this->qtable($table_name, $schema) . '.' . $field_name : $field_name;
    }

    function next_id($seqname = 'dboseq', $startValue = 1)
    {
        $nextSql = sprintf('UPDATE %s SET id = LAST_INSERT_ID(id + 1)', $seqname);

        $successed = false;
        try {
            // 首先产生下一个序列值
            $this->execute($nextSql);
            if ($this->affected_rows() > 0) {
                $successed = true;
            }
        } catch (QDBO_Exception $ex) {
            // 产生序列值失败，创建序列表
            $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $seqname));
        }

        if (!$successed) {
            // 没有更新任何记录或者新创建序列表，都需要插入初始的记录
            if ($this->get_one(sprintf('SELECT COUNT(*) FROM %s', $seqname)) == 0) {
                $sql = sprintf('INSERT INTO %s VALUES (%s)', $seqname, $startValue);
                $this->execute($sql);
            }
            $this->execute($nextSql);
        }
        // 获得新的序列值
        $this->insert_id = $this->insert_id();
        return $this->insert_id;
    }

    function create_seq($seqname = 'dboseq', $startValue = 1)
    {
        $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $seqname));
        $sql = sprintf('INSERT INTO %s VALUES (%s)', $seqname, $startValue);
        $this->execute($sql);
    }

    function drop_seq($seqname = 'dboseq')
    {
        $this->execute(sprintf('DROP TABLE %s', $seqname));
    }

    function insert_id()
    {
        return mysql_insert_id($this->conn);
    }

    function affected_rows()
    {
        return mysql_affected_rows($this->conn);
    }

    function execute($sql, $inputarr = null)
    {
        $this->query_count++;
        if ($this->LOG_QUERY) {
            $this->log[] = $sql;
            // log_message($sql, 'debug', 'SQL');
        }

        if (is_array($inputarr)) {
            $sql = $this->bind($sql, $inputarr);
        }

        $result = mysql_query($sql, $this->conn);
        if (is_resource($result)) {
            return new QDBO_Result_Mysql($result, $this->fetch_mode);
        } elseif ($result) {
            $this->last_err = null;
            $this->last_errcode = null;
            return $result;
        } else {
            $this->last_err = mysql_error($this->conn);
            $this->last_errcode = mysql_errno($this->conn);
            $this->has_failed_query = true;
            throw new QDBO_Exception($sql, $this->last_err, $this->last_errcode);
        }
    }

    function select_limit($sql, $length = null, $offset = null, array $inputarr = null)
    {
        if (!is_null($offset)) {
            $sql .= " LIMIT " . (int)$offset;
            if (!is_null($length)) {
                $sql .= ', ' . (int)$length;
            } else {
                $sql .= ', 18446744073709551615';
            }
        } elseif (!is_null($length)) {
            $sql .= " LIMIT " . (int)$length;
        }
        return $this->execute($sql, $inputarr);
    }

    function start_trans()
    {
        if (!$this->TRANSACTION_ENABLED) { return false; }
        if ($this->trans_count == 0) {
            $this->execute('START TRANSACTION');
            $this->has_failed_query = false;
        } elseif ($this->trans_count && $this->SAVEPOINT_ENABLED) {
            $savepoint = 'savepoint_' . $this->trans_count;
            $this->execute("SAVEPOINT `{$savepoint}`");
            array_push($this->savepoints_stack, $savepoint);
        }
        $this->trans_count++;
    }

    function complete_trans($commit_on_no_errors = true)
    {
        if ($this->trans_count == 0) { return; }
        $this->trans_count--;
        if ($this->trans_count == 0) {
            if ($this->has_failed_query == false && $commit_on_no_errors) {
                $this->execute('COMMIT');
            } else {
                $this->execute('ROLLBACK');
            }
        } elseif ($this->SAVEPOINT_ENABLED) {
            $savepoint = array_pop($this->savepoints_stack);
            if ($this->has_failed_query || $commit_on_no_errors == false) {
                $this->execute("ROLLBACK TO SAVEPOINT `{$savepoint}`");
            }
        }
    }

    function meta_columns($table_name, $schema = null, $quoteTablename = true)
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

        if ($quoteTablename) {
            $table_name = $this->qtable($table_name, $schema);
        }
        $rs = $this->execute(sprintf('SHOW FULL COLUMNS FROM %s', $table_name));
        if (!$rs) { return false; }
        /* @var $rs DBO_Result */
        $retarr = array();
        $rs->fetch_mode = self::fetch_mode_array;
        while (($row = $rs->fetch_row())) {
            $field = array();
            $field['name'] = $row['Field'];
            $type = strtolower($row['Type']);

            $field['scale'] = null;
            $queryArray = false;
            if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $field['max_length'] = is_numeric($queryArray[2]) ? $queryArray[2] : -1;
                $field['scale'] = is_numeric($queryArray[3]) ? $queryArray[3] : -1;
            } elseif (preg_match('/^(.+)\((\d+)/', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $field['max_length'] = is_numeric($queryArray[2]) ? $queryArray[2] : -1;
            } elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $queryArray)) {
                $field['type'] = $queryArray[1];
                $arr = explode(",",$queryArray[2]);
                $field['enums'] = $arr;
                $zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
                $field['max_length'] = ($zlen > 0) ? $zlen : 1;
            } else {
                $field['type'] = $type;
                $field['max_length'] = -1;
            }
            $field['ptype'] = $typeMap[strtolower($field['type'])];
            if ($field['ptype'] == 'C' && $field['max_length'] > 250) {
                $field['ptype'] = 'X';
            }
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

            if ($field['type'] == 'tinyint' && $field['max_length'] == 1) {
                $field['ptype'] = 'l';
            }

            $field['description'] = isset($row['Comment']) ? $row['Comment'] : '';

            $retarr[strtolower($field['name'])] = $field;
        }
        return $retarr;
    }

    function meta_tables($pattern = null, $schema = null)
    {
        $sql = 'SHOW TABLES';
		if ($schema != '') {
		    $sql .= " FROM `{$schema}`";
		}
		if ($pattern != '') {
		    $sql .= ' LIKE ' . $this->qstr($pattern);
		}
		$rs = $this->execute($sql);
		$tables = array();
		while (($table_name = $rs->fetch_one())) {
		   $tables[] = $table_name;
		}
		return $tables;
    }

    protected function bind($sql, & $inputarr)
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

/**
 * QDBO_Result_Mysql 封装了一个查询句柄，便于释放资源
 *
 * @package core
 * @author 起源科技(www.qeeyuan.com)
 */
class QDBO_Result_Mysql extends QDBO_Result
{
    function free()
    {
        if ($this->handle) { mysql_free_result($this->handle); }
        $this->handle = null;
    }

    function fetch_row()
    {
        if ($this->fetch_mode == QDBO_Abstract::fetch_mode_assoc) {
            return mysql_fetch_assoc($this->handle);
        } else {
            return mysql_fetch_array($this->handle);
        }
    }
}
