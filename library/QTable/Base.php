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
 * 定义 QTable_Base 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * QTable_Base 类（表数据入口）封装数据表的 CRUD 操作，并提供了扩展接口。
 *
 * 所有 QTable_Base 的扩展都是 Table_Extension_Abstract 的继承类，并且类名称必须为 Extension_????。
 *
 * 对于每一个 QTable_Base 的继承类，开发者必须通过 $tableName 成员变量指定要操作的数据表。
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 */
class QTable_Base
{
    /**
     * 关联关系
     */
    const has_one       = 'has one';
    const has_many      = 'has many';
    const belongs_to    = 'belongs to';
    const many_to_many  = 'many to many';

    /**
     * 数据表的 schema
     *
     * @var string
     */
    public $schema = '';

    /**
     * 不包含前缀的数据表名称
     *
     * @var string
     */
    public $tableName;

    /**
     * 包含前缀的数据表名称
     *
     * @var string
     */
    public $fullTableName;

    /**
     * 数据表的完全限定名（不包含 schema）
     *
     * @var string
     */
    public $qtableName;

    /**
     * 主键字段名
     *
     * @var sring
     */
    public $pk;

    /**
     * 主键字段的完全限定名（包含 full table name 和 schema）
     *
     * @var string
     */
    public $qpk;

    /**
     * 定义 HAS ONE 关联
     *
     * @var array
     */
    protected $hasOne;

    /**
     * 定义 HAS MANY 关联
     *
     * @var array
     */
    protected $hasMany;

    /**
     * 定义 BELONGS TO 关联
     *
     * @var array
     */
    protected $belongsTo;

    /**
     * 定义 MANY TO MANY 关联
     *
     * @var array
     */
    protected $manyToMany;

    /**
     * 保存该表数据入口的所有关联
     *
     * @var array
     */
    protected $links = array();

    /**
     * 创建记录时，要自动填入当前时间的字段
     *
     * 只要数据表具有下列字段之一，则调用 create() 方法创建记录时，
     * 将以服务器时间自动填充该字段。
     *
     * @var array
     */
    protected $createdTimeFields = array('created', 'updated');

    /**
     * 创建和更新记录时，要自动填入当前时间的字段
     *
     * 只要数据表具有下列字段之一，则调用 create() 方法创建记录或 update() 更新记录时，
     * 将以服务器时间自动填充该字段。
     *
     * @var array
     */
    protected $updatedTimeFields = array('updated');

    /**
     * 数据库访问对象
     *
     * 开发者不应该直接访问该成员变量，而是通过 set_dbo() 和 get_dbo() 方法
     * 来访问表数据入口使用数据访问对象。
     *
     * @var QDBO_Abstract
     */
    protected $dbo;

    /**
     * 指示表数据入口对象是否已经初始化
     *
     * @var boolean
     */
    protected $isInit = false;

    /**
     * 当前数据表的元数据
     *
     * 元数据是一个二维数组，每一个元素的键名就是全大写的字段名，
     * 而键值则是该字段的数据表定义。
     *
     * @var array
     */
    protected static $tablesMETA;

    /**
     * 构造 Table 实例
     *
     * $params 参数允许有下列选项：
     *   - schema: 指定数据表的 schema
     *   - tableName: 指定数据表的名称
     *   - fullTableName: 指定数据表的完整名称
     *   - pk: 指定主键字段名
     *   - dbo: 指定数据库访问对象
     *
     * @param array $params
     * @param boolean $connect_now 指示是否立即连接数据库
     *
     * @return Table
     */
    function __construct(array $params = null, $connectNow = false)
    {
        if (!empty($params['schema'])) {
            $this->schema = $params['schema'];
        }
        if (!empty($params['tableName'])) {
            $this->tableName = $params['tableName'];
        }
        if (!empty($params['fullTableName'])) {
            $this->fullTableName = $params['fullTableName'];
            if (empty($this->tableName)) {
                $this->tableName = $this->fullTableName;
            }
        }
        if (!empty($params['pk'])) {
            $this->pk = $params['pk'];
        }
        if (!empty($params['dbo'])) {
            $this->setDBO($params['dbo']);
        }
        if ($connectNow) {
            $this->connect();
        }

        $this->relinks();
    }
    
    function clearLinks()
    {
        foreach ($this->links as $offset => $link) {
            unset($this->links[$offset]);
            unset($link);    
        }
    }

    /**
     * 根据类定义的 $has_one、$has_many、$belongs_to 和 $many_to_many 成员变量重建所有关联
     */
    function relinks()
    {
        $this->clearLinks();
        if (is_array($this->hasOne)) {
            $this->createLink($this->hasOne, self::has_one);
        }
        if (is_array($this->belongsTo)) {
            $this->createLink($this->belongsTo, self::belongs_to);
        }
        if (is_array($this->hasMany)) {
            $this->createLink($this->hasMany, self::has_many);
        }
        if (is_array($this->manyToMany)) {
            $this->createLink($this->manyToMany, self::many_to_many);
        }
    }

    /**
     * 建立关联，并且返回新建立的关联对象
     *
     * @param array $line_define
     * @param const $type
     *
     * @return QTable_Link_Abstract
     */
    function createLink(array $linkDefine, $type)
    {
        if (!is_array(reset($linkDefine))) {
            $linkDefine = $linkDefine;
        }
        $return = array();
        foreach ($linkDefine as $def) {
            if (!is_array($def)) {
                throw new QTable_Exception(__('$linkDefine typemismatch, expected array, actual is %s', gettype($def)));
            }
            $link = QTable_Link_Abstract::createLink($def, $type, $this);
            $this->links[$link->name] = $link;
            $return[] = $link;
        }
        if (count($return) == 1) {
            return $return[0];
        } else {
            return $return;
        }
    }

    /**
     * 创建一个查询对象
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QTable_Select
     */
    function find($where = null, array $args = null)
    {
        return new QTable_Select($this, $where, $args);
    }

    /**
     * 直接执行一个 SQL 语句，并返回结果集（数组）
     *
     * @param string $sql
     * @param array $args
     *
     * @return array
     */
    function findBySQL($sql, array $args = null)
    {
        return $this->dbo->getAll($sql, $args);
    }

    /**
     * 创建一条新记录，返回新记录的主键值
     *
     * @param array $row
     *
     * @return mixed
     */
    function create(array $row)
    {
        $this->_fillFieldsWithCurrentTime($row, $this->createdTimeFields);
        if (empty($row[$this->pk]) || $row[$this->pk] == 0) {
            unset($row[$this->pk]);
        }
        $mpk = strtolower($this->pk);
        if (!isset($row[$this->pk]) && !self::$tablesMETA[$this->cacheID][$mpk]['autoIncr']) {
            // 如果主键字段不是自增字段，并且 $row 没有包含主键字段值时，则获取一个新的主键字段值
            $row[$this->pk] = $this->nextID();
        }
        $sql = $this->dbo->getInsertSQL($row, $this->fullTableName, $this->schema);
        $this->dbo->execute($sql, $row);
        $insertID = !empty($row[$this->pk]) ? $row[$this->pk] : $this->dbo->insertID();
        return $insertID;
    }

    /**
     * 批量创建新记录，并返回包含新记录主键值的数组
     *
     * @param array $rowset
     *
     * @return array
     */
    function createRowset(array $rowset)
    {
        $return = array();
        foreach (array_keys($rowset) as $offset) {
            $return[] = $this->create($rowset[$offset]);
        }
        return $return;
    }

    /**
     * 更新一条记录，返回被更新记录的总数
     *
     * @param array $row
     *
     * @return int
     */
    function update(array $row)
    {
        $this->_fillFieldsWithCurrentTime($row, $this->updatedTimeFields);
        $sql = $this->dbo->getUpdateSQL($row, $this->pk, $this->fullTableName, $this->schema);
        $this->dbo->execute($sql, $row);
        return $this->dbo->affectedRows();
    }

    /**
     * 批量更新记录，返回被更新记录的总数
     *
     * @param array $rowset
     */
    function updateRowset(array $rowset)
    {
        $update_count = 0;
        foreach (array_keys($rowset) as $offset) {
            $update_count += (int)$this->update($rowset[$offset]);
        }
        return $update_count;
    }

    /**
     * 更新所有符合条件的记录，返回被更新记录的总数
     *
     * @param array $pairs
     * @param array|string $where
     * @param array $args
     *
     * @return int
     */
    function updateWhere(array $pairs, $where, array $args = null)
    {
        $where = $this->parseWhere($where, $args);
        if (is_array($where)) {
            $where = reset($where);
        }

        $args = $this->dbo->getPlaceholderPairs($pairs);
        $sql = "UPDATE {$this->qtableName} SET " . implode(',', $args[0]);
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        $this->dbo->execute($sql, $args[1]);
        return $this->dbo->affectedRows();
    }

    /**
     * 执行一个 UPDATE 操作，并且确保同时更新记录的 updated 字段，返回被更新记录的总数
     *
     * @param string $sql
     *
     * @return int
     */
    function updateBySQL($sql)
    {
        $parts = preg_split('/[ \n]set[ \n]/i', $sql);
        $sql = $parts[0] . ' SET ' . $this->getUpdatedTimeSQL() . ', ';
        for ($i = 1, $max = count($parts); $i < $max; $i++) {
            $sql .= $parts[$i];
        }
        $this->dbo->execute($sql);
        return $this->dbo->affectedRows();
    }

    /**
     * 获得用于更新 updated 字段的 sql 语句
     *
     * @return string
     */
    function getUpdatedTimeSQL()
    {
        $sql = '';
        $time = time();
        $db_time = $this->dbo->dbTimestamp($time);
        foreach ($this->updatedTimeFields as $field) {
            $field = strtolower($field);
            $sql .= self::$tablesMETA[$this->cacheID][$field]['name'];
            $sql .= ' = ';
            switch (self::$tablesMETA[$this->cacheID][$field]['ptype']) {
            case 'd':
            case 't':
                $sql .= $db_time;
                break;
            default:
                $sql .= $time;
            }
            $sql .= ', ';
        }

        return substr($sql, 0, -2);
    }

    /**
     * 增加所有符合条件记录的指定字段值，返回被更新记录的总数
     *
     * @param string $field
     * @param int $step
     * @param array|string $where
     * @param array $args
     *
     * @return mixed
     */
    function incrWhere($field, $step = 1, $where, array $args = null)
    {
        return $this->_incrDecrWhereBase($field, $step, true, $where, $args);
    }

    /**
     * 减小所有符合条件记录的指定字段值，返回被更新记录的总数
     *
     * @param string $field
     * @param int $step
     * @param array|string $where
     * @param array $args
     *
     * @return mixed
     */
    function decrWhere($field, $step = 1, $where, array $args = null)
    {
        return $this->_incrDecrWhereBase($field, $step, false, $where, $args);
    }

    /**
     * 根据是否包含主键字段值，创建或更新一条记录
     *
     *
     * @return mixed
     */
    function save(array $row)
    {
        if (isset($row[$this->pk])) {
            return $this->update($row);
        } else {
            return $this->create($row);
        }
    }

    /**
     * 根据是否包含主键字段值，创建或更新一组记录，返回包含主键值的数组
     *
     * @param array $rowset
     *
     * @return array
     */
    function saveRowset(array $rowset)
    {
        $return = array();
        foreach (array_keys($rowset) as $offset) {
            $return[] = $this->save($rowset[$offset]);
        }
        return $return;
    }

    /**
     * 替换一条现有记录或插入新记录，返回记录的主键值
     *
     * @param array $row
     *
     * @return mixed
     */
    function replace(array $row)
    {
        $this->_fillFieldsWithCurrentTime($row, $this->createdTimeFields);
        $sql = $this->dbo->getReplaceSQL($row, $this->qtableName);
        $this->dbo->execute($sql, $row);
        return empty($row[$this->pk]) ? $this->dbo->insertID() : $row[$this->pk];
    }

    /**
     * 替换一组现有记录或插入新记录，返回包含记录主键值的数组
     *
     * @param array $rowset
     *
     * @return array
     */
    function replaceRowset(array $rowset)
    {
        $return = array();
        foreach (array_keys($rowset) as $offset) {
            $return[] = $this->replace($rowset[$offset]);
        }
        return $return;
    }

    /**
     * 删除指定主键值的记录，返回被删除的记录总数
     *
     * @param mixed $pkv
     *
     * @return int
     */
    function remove($pkv)
    {
        $pkv = $this->dbo->qstr($pkv);
        $sql = "DELETE FROM {$this->qtableName} WHERE {$this->qpk} = {$pkv}";
        $this->dbo->execute($sql);
        return $this->dbo->affectedRows();
    }

    /**
     * 删除所有符合条件的记录，返回被删除的记录总数
     *
     * @param mixed $where
     * @param array $args
     *
     * @return ini
     */
    function removeWhere($where, array $args = null)
    {
        $where = $this->parseWhere($where, $args);
        if (is_array($where)) {
            $where = reset($where);
        }

        $sql = "DELETE FROM {$this->qtableName}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $this->dbo->execute($sql);
        return $this->dbo->affectedRows();
    }

    /**
     * 为当前数据表产生一个新的主键值
     *
     * @return mixed
     */
    function nextID()
    {
        $seqname = $this->dbo->qtable($this->fullTableName . '_seq', $this->schema);
        return $this->dbo->nextID($seqname);
    }

    /**
     * 返回所有字段的元数据
     *
     * @return array
     */
    function columns()
    {
        $this->connect();
        return self::$tablesMETA[$this->cacheID];
    }

    /**
     * 确认是否已经连接到数据库
     *
     * @return boolean
     */
    function isConnected()
    {
        return !is_null($this->dbo) && $this->dbo->isConnected();
    }

    /**
     * 返回该表数据入口对象使用的数据访问对象
     *
     * @return QDBO_Abstract
     */
    function getDBO()
    {
        return $this->dbo;
    }

    /**
     * 设置数据库访问对象
     *
     * @param QDBO_Abstract $dbo
     */
    function setDBO($dbo)
    {
        $this->dbo = $dbo;
        if ($this->schema == '' && $dbo->getSchema() != '') {
            $this->schema = $dbo->getSchema();
        }
        if ($this->fullTableName == '' && $dbo->getTablePrefix() != '') {
            $this->fullTableName = $dbo->getTablePrefix() . $this->tableName;
        } else {
            $this->fullTableName = $this->tableName;
        }
        $this->qtableName = $this->dbo->qtable($this->fullTableName);
        $this->qpk = $this->dbo->qfield($this->pk, $this->fullTableName, $this->schema);
    }

    /**
     * 魔法方法
     *
     * @param string $varname
     *
     * @return mixed
     */
    function __get($varname)
    {
        /**
         * 当第一次访问对象的 cacheID 属性时，将连接数据库
         * 通过这个技巧，可以避免无谓的判断和函数调用
         */
        if ($varname == 'cacheID') {
            $this->connect();
            return $this->cacheID;
        }
        throw new QTable_Exception(__('Undefined property "%s"', $varname));
    }

    /**
     * 连接到数据库
     */
    function connect()
    {
        if ($this->isInit) { return; }

        if (is_null($this->dbo)) {
            $dbo = QDBO_Abstract::getInstance();
            $this->setDBO($dbo);
        }

        if (!$this->dbo->isConnected()) {
            $this->dbo->connect();
        }
        $this->cacheID = $this->dbo->getID() . '/' . $this->qtableName;
        $this->_prepareMETA();

        // 如果没有指定主键，则自动设置主键字段
        if ($this->pk == '') {
            foreach (self::$tablesMETA[$this->cacheID] as $field) {
                if ($field['pk']) {
                    $this->pk = $field['name'];
                    break;
                }
            }
            if ($this->pk == '') {
                throw new QTable_Exception(__('Not found primary key in table "%s"', $this->qtableName));
            }
        }

        // 过滤 createdTimeFields 和 updatedTimeFields
        foreach ($this->createdTimeFields as $offset => $field) {
            if (!isset(self::$tablesMETA[$this->cacheID][strtolower($field)])) {
                unset($this->createdTimeFields[$offset]);
            }
        }

        foreach ($this->updatedTimeFields as $offset => $field) {
            if (!isset(self::$tablesMETA[$this->cacheID][strtolower($field)])) {
                unset($this->updatedTimeFields[$offset]);
            }
        }
    }

    function qfields($fields)
    {
        return $this->dbo->qfields($fields, $this->fullTableName, $this->schema);
    }

    /**
     * 分析查询条件和参数
     *
     * 模式1：
     * where('user_id = ?', array($user_id))
     * where('user_id = :user_id', array('user_id' => $user_id))
     * where('user_id in (?)', array(array($id1, $id2, $id3)))
     *
     * 模式2：
     * where(array(
     *      'user_id' => $user_id,
     *      'level_ix' => $level_ix,
     * ))
     *
     * @param array|string $where
     * @param array $args
     *
     * @return array|string
     */
    function parseWhere($where, array $args = null)
    {
        if (is_null($args)) {
            $args = array();
        }
        if (is_array($where)) {
            return $this->_parseWhereArray($where);
        } else {
            return $this->_parseWhereString($where, $args);
        }
    }
    
    /**
     * 按照模式2对查询条件进行分析
     *
     * @param array $where
     *
     * @return array|string
     */
    protected function _parseWhereArray(array $where)
    {
        /**
         * 模式2：
         * where(array('user_id' => $user_id))
         * where(array('user_id' => $user_id, 'level_ix' => 1))
         * where(array('(', 'user_id' => $user_id, 'OR', 'level_ix' => $level_ix, ')'))
         * where(array('user_id' => array($id1, $id2, $id3)))
         */

        $parts = array();
        $callback = array($this->dbo, 'qstr');
        $next_op = '';

        foreach ($where as $key => $value) {
            if (is_int($key)) {
                $parts[] = $value;
                if ($value == ')') {
                    $next_op = 'AND';
                } else {
                    $next_op = '';
                }
            } else {
                if ($next_op != '') {
                    $parts[] = $next_op;
                }
                $field = $this->_parseWhereQfield(array('', $key));
                if (is_array($value)) {
                    $value = array_map($callback, $value);
                    $parts[] = $field . ' IN (' . implode(',', $value) . ')';
                } else {
                    $value = $this->dbo->qstr($value);
                    $parts[] = $field . ' = ' . $value;
                }
                $next_op = 'AND';
            }
        }

        return implode(' ', $parts);
    }
    
    /**
     * 按照模式1对查询条件进行分析
     *
     * @param string $where
     * @param array $args
     *
     * @return array|string
     */
    protected function _parseWhereString($where, array $args = null)
    {
        /**
         * 模式1：
         * where('user_id = ?', array($user_id))
         * where('user_id = :user_id', array('user_id' => $user_id))
         * where('user_id in (?)', array(array($id1, $id2, $id3)))
         * where('user_id = :user_id', array('user_id' => $user_id))
         * where('user_id IN (:users_id)', array('users_id' => array(1, 2, 3)))
         */

        // 首先从查询条件中提取出可以识别的字段名
        if (strpos($where, '`') !== false) {
            // 提取字段名
            $where = preg_replace_callback('/`([a-z0-9_\-\.]+)`/i', array($this, '_parseWhereQfield'), $where);
        }

        // 分析查询条件中的参数占位符
        if (strpos($where, '?') !== false) {
            // 使用 ? 作为占位符的情况
            return $this->dbo->qinto($where, $args, QDBO_Abstract::param_qm);
        }

        if (strpos($where, ':') !== false) {
            // 使用 : 开头的命名参数占位符
            return $this->dbo->qinto($where, $args, QDBO_Abstract::param_cl_named);
        }

        return $where;
    }

    /**
     * 将字段名替换为转义后的完全限定名
     *
     * @param array $matches
     *
     * @return string
     */
    private function _parseWhereQfield($matches)
    {
        $p = explode('.', $matches[1]);
        switch (count($p)) {
        case 3:
            list($schema, $table, $field) = $p;
            if ($table == $this->tableName) {
                $table = $this->fullTableName;
            }
            return $this->dbo->qfield($field, $table, $schema);
        case 2:
            list($table, $field) = $p;
            if ($table == $this->tableName) {
                $table = $this->fullTableName;
            }
            return $this->dbo->qfield($field, $table);
        default:
            return $this->dbo->qfield($p[0]);
        }
    }

    /**
     * 在指定字段填充当前时间
     *
     * @param array $row
     * @param array $fields
     */
    protected function _fillFieldsWithCurrentTime(array & $row, array $fields)
    {
        $curr = time();
        $curr_db_time = $this->dbo->dbTimestamp($curr);

        foreach ($fields as $field) {
            $mf = strtolower($field);
            if (!isset(self::$tablesMETA[$this->cacheID][$mf])) { continue; }
            switch (self::$tablesMETA[$this->cacheID][$mf]['ptype']) {
            case 'd':
            case 't':
                $row[$field] = $curr_db_time;
                break;
            case 'i':
                $row[$field] = $curr;
            }
        }
    }

    protected function _incrDecrWhereBase($field, $step, $is_incr, $where, arary $args = null)
    {
        $where = $this->parseWhere($where, $args);
        if (is_array($where)) { $where = reset($where); }
        $field = $this->dbo->qfield($field);
        $step = (int)$step;
        $op = ($is_incr) ? '+' : '-';
        $sql = "UPDATE {$this->qtableName} SET {$field} = {$field} {$op} {$step}";
        if (!empty($where)) { $sql .= " WHERE {$where}"; }
        return $this->updateBySQL($sql);
    }
    
    /**
     * 准备当前数据表的元数据
     *
     * @return boolean
     */
    private function _prepareMETA()
    {
        if (isset(self::$tablesMETA[$this->cacheID])) { return; }
        $cached = Q::getIni('db_meta_cached');

        if ($cached) {
            // 尝试从缓存读取
            $policy = array('encoding_filename' => true, 'serialize' => true);
            $backend = Q::getIni('db_meta_cache_backend');
            $meta = Q::getCache($this->cacheID, $policy, $backend);
            if (is_array($meta)) {
                self::$tablesMETA[$this->cacheID] = $meta;
                return;
            }
        }

        // 从数据库获得 meta
        $meta = $this->dbo->metaColumns($this->fullTableName, $this->schema);
        self::$tablesMETA[$this->cacheID] = $meta;
        if ($cached) {
            // 缓存数据
            Q::setCache($this->cacheID, $meta, $policy, $backend);
        }
    }
}
