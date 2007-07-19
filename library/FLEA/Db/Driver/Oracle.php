<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// 该文件由“夜猫子”共享，特此感谢！
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 FLEA_Db_Driver_Oracle 驱动
 *
 * 参考 AdoDB 的 MetaColumns() 及 SelectLimit() 方法。
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id$
 */

/**
 * 用于 pgsql 扩展的数据库驱动程序
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_Driver_Oracle
{
    /**
     * 用于 genSeq()、dropSeq() 和 nextId() 的 SQL 查询语句
     */
    var $NEXT_ID_SQL    = "SELECT (%s.nextval) FROM DUAL";
    var $CREATE_SEQ_SQL = "CREATE SEQUENCE %s START WITH %s";
    var $DROP_SEQ_SQL   = "DROP SEQUENCE %s";

    /**
     * 用于描绘 true、false 和 null 的数据库值
     */
    var $TRUE_VALUE  = 1;
    var $FALSE_VALUE = 0;
    var $NULL_VALUE = 'NULL';

    /**
     * 用于获取元数据的 SQL 查询语句
     */
    var $META_COLUMNS_SQL = "select cname,coltype,width, SCALE, PRECISION, NULLS, DEFAULTVAL from col where tname=%s order by colno";

    /**
     * 数据库连接信息
     *
     * @var array
     */
    var $dsn = null;

    /**
     * 数据库连接句柄
     *
     * @var resource
     */
    var $conn = null;

    /**
     * 所有 SQL 查询的日志
     *
     * @var array
     */
    var $log = array();

    /**
     * 指示是否记录 SQL 语句（部署模式时该设置默认为 false）
     *
     * @var boolean
     */
    var $enableLog = false;

    /**
     * 最后一次数据库操作的错误信息
     *
     * @var mixed
     */
    var $lasterr = null;

    /**
     * 最后一次数据库操作的错误代码
     *
     * @var mixed
     */
    var $lasterrcode = null;

    /**
     * 是否强制转换字段名为小写
     *
     * @var boolean
     */
    var $fieldNameLower = true;

    /**
     * 最近一次插入操作或者 nextId() 操作返回的插入 ID
     *
     * @var mixed
     */
    var $_insertId = null;

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
     * 查询的提交状态
     *
     * @var int
     */
    var $_commitMode = OCI_COMMIT_ON_SUCCESS;

    /**
     * 最后一次查询的结果
     *
     * @var mixed
     */
    var $_lastrs = null;

    /**
     * 构造函数
     *
     * @param array $dsn
     */
    function FLEA_Db_Driver_Oracle($dsn = false)
    {
        $tmp = (array)$dsn;
        unset($tmp['password']);
        $this->dsn = $dsn;
        $this->enableLog = !defined('DEPLOY_MODE') || DEPLOY_MODE != true;
        if (!function_exists('log_message')) {
            $this->enableLog = false;
        }
    }

    /**
     * 连接数据库
     *
     * @param array $dsn
     *
     * @return boolean
     */
    function connect($dsn = false)
    {
        $this->lasterr = null;
        $this->lasterrcode = null;

        if ($this->conn && $dsn == false) { return true; }
        if (!$dsn) {
            $dsn = $this->dsn;
        } else {
            $this->dsn = $dsn;
        }
        if (isset($dsn['charset']) && $dsn['charset'] != '') {
            $charset = $dsn['charset'];
        } else {
            $charset = FLEA::getAppInf('databaseCharset');
        }
        if (strtoupper($charset) == 'GB2312') { $charset = 'GBK'; }
        if ($charset != '') {
            $this->conn = ocilogon("{$dsn['login']}", $dsn['password'], $dsn['database'], $charset);
        } else {
            $this->conn = ocilogon($dsn['login'], $dsn['password'], $dsn['database']);
        }

        if (!$this->conn) {
            FLEA::loadClass('FLEA_Db_Exception_SqlQuery');
            $err = ocierror();
            __THROW(new FLEA_Db_Exception_SqlQuery("ocilogon('{$dsn['login']}') failed.", $err['message'], $err['code']));
            return false;
        }

        return true;
    }

    /**
     * 关闭数据库连接
     */
    function close()
    {
        if ($this->conn) {
            ocilogoff($this->conn);
        }
        $this->conn = null;
        $this->lasterr = null;
        $this->lasterrcode = null;
        $this->_transCount = 0;
        $this->_transCommit = true;
    }

    /**
     * 执行一个查询，返回一个 resource 或者 boolean 值
     *
     * @param string $sql
     * @param array $inputarr
     * @param boolean $throw 指示查询出错时是否抛出异常
     *
     * @return resource|boolean
     */
    function execute($sql, $inputarr = null, $throw = true)
    {
        do {
            if (is_array($inputarr)) {
                $sql = $this->_prepareSql($sql, $inputarr);
            }
            if ($this->enableLog) {
                $this->log[] = $sql;
                log_message("sql:\n{$sql}", 'debug');
            }
            $stmt = @ociparse($this->conn, $sql);
            if (!$stmt) { break; }
            if (!@ociexecute($stmt)) { break; }
            $this->_lastrs = $stmt;
            $this->lasterr = null;
            $this->lasterrcode = null;
            return $stmt;
        } while (false);

        if ($stmt) {
            $err = ocierror($stmt);
        } else {
            $err = ocierror();
        }
        $this->lasterr = $err['message'];
        $this->lasterrcode = $err['code'];

        if ($throw) {
            FLEA::loadClass('FLEA_Db_Exception_SqlQuery');
            __THROW(new FLEA_Db_Exception_SqlQuery($sql, $this->lasterr, $this->lasterrcode));
        }
        return false;
    }

    /**
     * 转义字符串
     *
     * @param string $value
     *
     * @return mixed
     */
    function qstr($value)
    {
        if (is_bool($value)) { return $value ? $this->TRUE_VALUE : $this->FALSE_VALUE; }
        if (is_null($value)) { return $this->NULL_VALUE; }
        return  "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * 将数据表名字转换为完全限定名
     *
     * @param string $tableName
     *
     * @return string
     */
    function qtable($tableName)
    {
        if (substr($tableName, 0, 1) == '"') { return strtoupper($tableName); }
        return '"' . strtoupper($tableName) . '"';
    }

    /**
     * 将字段名转换为完全限定名，避免因为字段名和数据库关键词相同导致的错误
     *
     * @param string $fieldName
     * @param string $tableName
     *
     * @return string
     */
    function qfield($fieldName, $tableName = null)
    {
        $pos = strpos($fieldName, '.');
        if ($pos !== false) {
            $tableName = substr($fieldName, 0, $pos);
            $fieldName = substr($fieldName, $pos + 1);
        }
        if ($tableName != "") {
        	if ($fieldName != '*') {
            	return '"' . strtoupper($tableName) . '"."' . strtoupper($fieldName) . '"';
        	} else {
            	return '"' . strtoupper($tableName) . '".*';
        	}
        } else {
        	if ($fieldName != '*') {
            	return '"' . strtoupper($fieldName) . '"';
        	} else {
        		return '*';
        	}
        }
    }

    /**
     * 一次性将多个字段名转换为完全限定名
     *
     * @param string|array $fields
     * @param string $tableName
     *
     * @return string
     */
    function qfields($fields, $tableName = null)
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
                	$return[] = '"' . strtoupper($tableName) . '"."' . strtoupper($fieldName) . '"';
            	} else {
            		$return[] = '"' . strtoupper($tableName) . '".*';
            	}
            } else {
            	if ($fieldName != '*') {
                	$return[] = '"' . strtoupper($fieldName) . '"';
            	} else {
            		$return[] = '*';
            	}
            }
        }
        return implode(', ', $return);
    }

    /**
     * 为数据表产生下一个序列值，失败返回 false
     *
     * @param string $seqName
     * @param string $startValue
     *
     * @return int
     */
    function nextId($seqName = 'SDBO_SEQ', $startValue = 1)
    {
        $getNextId = sprintf($this->NEXT_ID_SQL, strtoupper($seqName));
        $stmt = $this->execute($getNextId, null, false);
        if (!$stmt) {
            // 序列不存在，建立该序列
            if (!$this->createSeq($seqName, $startValue)) { return false; }
            $stmt = $this->execute($getNextId);
            if (!$stmt) { return false; }
        }

        $row = $this->fetchRow($stmt);
        $this->freeRes($stmt);
        $nextId = reset($row);
        $this->_insertId = $nextId;
        return $nextId;
    }

    /**
     * 创建一个新的序列，成功返回 true，失败返回 false
     *
     * @param string $seqName
     * @param int $startValue
     *
     * @return boolean
     */
    function createSeq($seqName = 'SDBO_SEQ', $startValue = 1)
    {
        $sql = sprintf($this->CREATE_SEQ_SQL, strtoupper($seqName), $startValue);
        return $this->execute($sql);
    }

    /**
     * 删除一个序列，成功返回 true，失败返回 false
     *
     * @param string $seqName
     */
    function dropSeq($seqName = 'SDBO_SEQ')
    {
        return $this->execute(sprintf($this->DROP_SEQ_SQL, strtoupper($seqName)));
    }

    /**
     * 获取自增字段的最后一个值
     *
     * 如果没有可返回的值，则抛出异常。
     *
     * @return mixed
     */
    function insertId()
    {
        return $this->_insertId;
    }

    /**
     * 返回最近一次数据库操作受到影响的记录数
     *
     * @return int
     */
    function affectedRows()
    {
        if (is_resource($this->_lastrs)) { return ocirowcount($this->_lastrs); }
        return 0;
    }

    /**
     * 从记录集中返回一行数据
     *
     * @param resouce $stmt
     *
     * @return array
     */
    function & fetchRow($stmt)
    {
        $row = array();
        ocifetchinto($stmt, $row, OCI_NUM | OCI_RETURN_LOBS);
        if ($this->fieldNameLower) {
            $row = array_change_key_case($row, CASE_LOWER);
        }
        return $row;
    }

    /**
     * 从记录集中返回一行数据，字段名作为键名
     *
     * @param resouce $stmt
     *
     * @return array
     */
    function & fetchAssoc($stmt)
    {
        $row = array();
        ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS);
        if ($this->fieldNameLower) {
            return array_change_key_case($row, CASE_LOWER);
        } else {
            return $row;
        }
    }

    /**
     * 释放查询句柄
     *
     * @param resource $stmt
     */
    function freeRes($stmt)
    {
        ocifreestatement($stmt);
    }

    /**
     * 进行限定记录集的查询
     *
     * @param string $sql
     * @param int $length
     * @param int $offset
     */
    function selectLimit($sql, $length = 'ALL', $offset = 0)
    {
        if (strpos($sql, '/*+') !== false) {
            $sql = str_replace('/*+ ', '/*+FIRST_ROWS ', $sql);
        } else {
            $sql = preg_replace('/^[ \t\n]*select/i', 'SELECT /*+FIRST_ROWS*/', $sql);
        }

        $selectOffsetAlg1 = 100;
        $inputarr = array();
        if ($offset < $selectOffsetAlg1) {
            if ($length > 0) {
                if ($offset > 0) { $length += $offset; }
                $sql = "select * from ({$sql}) where rownum <= ?";
                $inputarr[] = $length;
            }
            $stmt = $this->execute($sql, $inputarr);
            for ($i = 0; $i < $offset; $i++) {
                ocifetch($stmt);
            }
            return $stmt;
        } else {
             // Algorithm by Tomas V V Cox, from PEAR DB oci8.php

             // Let Oracle return the name of the columns
            $qfields = "SELECT * FROM ({$sql}) WHERE NULL = NULL";
            $stmt = ociparse($this->conn, $qfields);
            if (!$stmt) { return false; }

            if (is_array($inputarr)) {
                foreach($inputarr as $k => $v) {
                    if (is_array($v)) {
                        if (sizeof($v) == 2) { // suggested by g.giunta@libero.
                            ocibindbyname($stmt, ":{$k}", $inputarr[$k][0], $v[1]);
                        } else {
                            ocibindbyname($stmt, ":{$k}", $inputarr[$k][0], $v[1], $v[2]);
                        }
                    } else {
                        if ($v === ' ') {
                            $len = 1;
                        } else {
                            $len = -1;
                        }
                        ocibindbyname($stmt, ":{$k}", $inputarr[$k], $len);
                    }
                }
            }

            if (!ociexecute($stmt, OCI_DEFAULT)) {
                ocifreestatement($stmt);
                return false;
            }

            $ncols = ocinumcols($stmt);
            for ($i = 1; $i <= $ncols; $i++) {
                $cols[] = '"' . ocicolumnname($stmt, $i) . '"';
            }

            ocifreestatement($stmt);
            $fields = implode(', ', $cols);
            $length += $offset;
            $offset += 1; // in Oracle rownum starts at 1

            $sql = "SELECT {$fields} FROM (SELECT rownum as adodb_rownum, {$fields} FROM ({$sql}) WHERE rownum <= ?) WHERE adodb_rownum >= ?";
            $inputarr[] = $length;
            $inputarr[] = $offset;

            return $this->execute($sql, $inputarr);
        }
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
            $stmt = $sql;
        } else {
            $stmt = $this->execute($sql);
        }
        $data = array();
        $row = false;
        if ($this->fieldNameLower) {
            while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                $data[] = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                $data[] = $row;
            }
        }
        ocifreestatement($stmt);
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
    function & getAllGroupBy($sql, & $groupBy)
    {
        if (is_resource($sql)) {
            $stmt = $sql;
        } else {
            $stmt = $this->execute($sql);
        }
        $data = array();
        $row = false;
        if ($this->fieldNameLower) {
            ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS);
            if ($row != false) {
                $row = array_change_key_case($row, CASE_LOWER);
                if ($groupBy === true) {
                    $groupBy = key($row);
                }
                do {
                    $rkv = $row[$groupBy];
                    unset($row[$groupBy]);
                    $data[$rkv][] = $row;
                    ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS);
                    if ($row == false) { break; }
                    $row = array_change_key_case($row, CASE_LOWER);
                } while (true);
            }
        } else {
            ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS);
            if ($row != false) {
                if ($groupBy === true) {
                    $groupBy = key($row);
                }
                do {
                    $rkv = $row[$groupBy];
                    unset($row[$groupBy]);
                    $data[$rkv][] = $row;
                    ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS);
                } while ($row != false);
            }
        }
        ocifreestatement($stmt);
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
            $stmt = $sql;
        } else {
            $stmt = $this->execute($sql);
        }
        $fieldValues = array();
        $reference = array();
        $offset = 0;
        $data = array();
        $row = false;
        if ($this->fieldNameLower) {
            while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                $data[$offset] = array_change_key_case($row, CASE_LOWER);
                $fieldValue = $data[$offset][$field];
                unset($data[$offset][$field]);
                $fieldValues[$offset] = $fieldValue;
                $reference[$fieldValue] =& $data[$offset];
                $offset++;
            }
        } else {
            while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                $data[$offset] = $row;
                $fieldValue = $data[$offset][$field];
                unset($data[$offset][$field]);
                $fieldValues[$offset] = $fieldValue;
                $reference[$fieldValue] =& $data[$offset];
                $offset++;
            }
        }
        ocifreestatement($stmt);
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
            $stmt = $sql;
        } else {
            if ($limit !== null) {
                if (is_array($limit)) {
                    list($length, $offset) = $limit;
                } else {
                    $length = $limit;
                    $offset = 0;
                }
                $stmt = $this->selectLimit($sql, $length, $offset);
            } else {
                $stmt = $this->execute($sql);
            }
        }
        $row = false;
        if ($oneToOne) {
            // 一对一组装数据
            if ($this->fieldNameLower) {
                while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                    $row = array_change_key_case($row, CASE_LOWER);
                    $rkv = $row[$refKeyName];
                    unset($row[$refKeyName]);
                    $assocRowset[$rkv][$mappingName] = $row;
                }
            } else {
                while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                    $rkv = $row[$refKeyName];
                    unset($row[$refKeyName]);
                    $assocRowset[$rkv][$mappingName] = $row;
                }
            }
        } else {
            // 一对多组装数据，需要检查是否有全 NULL 的记录
            if ($this->fieldNameLower) {
                while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                    $row = array_change_key_case($row, CASE_LOWER);
                    $rkv = $row[$refKeyName];
                    unset($row[$refKeyName]);
                    $assocRowset[$rkv][$mappingName][] = $row;
                }
            } else {
                while (ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS)) {
                    $rkv = $row[$refKeyName];
                    unset($row[$refKeyName]);
                    $assocRowset[$rkv][$mappingName][] = $row;
                }
            }
        }
        ocifreestatement($stmt);
    }

    /**
     * 执行查询，返回第一条记录的第一个字段
     *
     * @param string $sql
     *
     * @return mixed
     */
    function getOne($sql)
    {
        if (is_resource($sql)) {
            $stmt = $sql;
        } else {
            $stmt = $this->execute($sql);
        }
        $row = array();
        ocifetchinto($stmt, $row, OCI_NUM);
        ocifreestatement($stmt);
        return isset($row[0]) ? $row[0] : null;
    }

    /**
     * 执行查询，返回第一条记录
     *
     * @param string $sql
     *
     * @return mixed
     */
    function & getRow($sql)
    {
        if (is_resource($sql)) {
            $stmt = $sql;
        } else {
            $stmt = $this->execute($sql);
        }
        $row = array();
        ocifetchinto($stmt, $row, OCI_ASSOC | OCI_RETURN_LOBS);
        ocifreestatement($stmt);
        if ($this->fieldNameLower) {
            return array_change_key_case($row, CASE_LOWER);
        } else {
            return $row;
        }
    }

    /**
     * 执行查询，返回结果集的第一列
     *
     * @param string|resource $sql
     * @param int $col 要返回的列，0 为第一列
     *
     * @return mixed
     */
    function & getCol($sql, $col = 0)
    {
        if (is_resource($sql)) {
            $stmt = $sql;
        } else {
            $stmt = $this->execute($sql);
        }
        $data = array();
        $row = array();
        while (ocifetchinto($stmt, $row, OCI_NUM | OCI_RETURN_LOBS)) {
            $data[] = $row[$col];
        }
        ocifreestatement($stmt);
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
     * @param boolean $normalize 指示是否规格化字段名
     *
     * @return array
     */
    function & metaColumns($table, $normalize = true)
    {
        static $typeMap = array(
            'VARCHAR' => 'C',
            'VARCHAR2' => 'C',
            'CHAR' => 'C',
            'VARBINARY' => 'C',
            'BINARY' => 'C',
            'NCHAR' => 'C',
            'NVARCHAR' => 'C',
            'NVARCHAR2' => 'C',

            'NCLOB' => 'X',
            'LONG' => 'X',
            'LONG VARCHAR' => 'X',
            'CLOB' => 'X',

            'LONG RAW' => 'B',
            'LONG VARBINARY' => 'B',
            'BLOB' => 'B',

            'DATE' => 'D',


            'TIMESTAMP' => 'T',

            'INT' => 'I',
            'SMALLINT' => 'I',
            'INTEGER' => 'I',
        );

        $table = $this->qstr(strtoupper($table));
        $stmt =& $this->execute(sprintf($this->META_COLUMNS_SQL, $table));
        if (!$stmt) { return false; }

        $retarr = array();
        $row = array();
        while (ocifetchinto($stmt, $row, OCI_NUM)) {
            $field = array();
            if ($this->fieldNameLower) {
                $field['name'] = strtolower($row[0]);
            } else {
                $field['name'] = $row[0];
            }
            $field['type'] = $row[1];
            $field['maxLength'] = $row[2];
            $field['scale'] = isset($row[3]) ? $row[3] : null;
            if ($field['type'] == 'NUMBER') {
                if ($field['scale'] == 0) { $field['type'] = 'INT'; }
                $field['maxLength'] = isset($row[4]) ? $row[4] : null;
            }
            $field['notNull'] = (strncmp($row[5], 'NOT',3) === 0);
            $field['binary'] = (strpos($field['type'], 'BLOB') !== false);
            $field['hasDefault'] = isset($row[6]);
            $field['defaultValue'] = isset($row[6]) ? $row[6] : null;

            $t = strtoupper($field['type']);
            if (isset($typeMap[$t])) {
                $field['simpleType'] = $typeMap[$t];
            } else {
                $field['simpleType'] = 'N';
            }
            $field['autoIncrement'] = false;
            $field['primaryKey'] = false;

            if ($normalize) {
                $retarr[strtoupper($field['name'])] = $field;
            } else {
                $retarr[$field['name']] = $field;
            }
        }
        ocifreestatement($stmt);

        // 确定主键字段
        $ptab = 'USER_';
        $sql = "SELECT /*+ RULE */ distinct b.column_name FROM {$ptab}CONSTRAINTS a, {$ptab}CONS_COLUMNS b WHERE ( UPPER(b.table_name) = ({$table}))  AND (UPPER(a.table_name) = ({$table}) and a.constraint_type = 'P') AND (a.constraint_name = b.constraint_name)";
        $stmt = $this->execute($sql);
        if ($stmt) {
            $row = array();
            while (ocifetchinto($stmt, $row, OCI_NUM)) {
                $pkname = strtoupper($row[0]);
                if (isset($retarr[$pkname])) {
                    $retarr[$pkname]['primaryKey'] = true;
                    if ($retarr[$pkname]['type'] == 'INT') {
                        $retarr[$pkname]['simpleType'] = 'R';
                    }
                }
            }
            ocifreestatement($stmt);
        }

        return $retarr;
    }

    /**
     * 返回数据库可以接受的日期格式
     *
     * @param int $timestamp
     */
    function dbTimeStamp($timestamp)
    {
        if (empty($timestamp) && $timestamp !== 0) { return 'null'; }
        return 'TO_DATE(' . date('Y-m-d, h:i:s A', $timestamp) . ", 'RRRR-MM-DD, HH:MI:SS AM')";
    }

    /**
     * 启动事务
     */
    function startTrans()
    {
        $this->_transCount += 1;
        $this->_commitMode = OCI_DEFAULT;
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
        if ($this->_transCount < 1) { return; }
        if ($this->_transCount > 1) {
            $this->_transCount -= 1;
            return;
        }
        $this->_transCount = 0;
        $this->_commitMode = OCI_COMMIT_ON_SUCCESS;

        if ($this->_transCommit && $commitOnNoErrors) {
            ocicommit($this->conn);
        } else {
            ocirollback($this->conn);
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
