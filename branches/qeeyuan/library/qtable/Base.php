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
 * 定义 QTable_Base 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QTable_Base 类（表数据入口）封装数据表的 CRUD 操作，并提供了扩展接口。
 *
 * @package database
 */
class QTable_Base
{
    /**
     * 关联关系
     */
    const HAS_ONE       = 'has_one';
    const HAS_MANY      = 'has_many';
    const BELONGS_TO    = 'belongs_to';
    const MANY_TO_MAMNY  = 'many_to_many';

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
    public $table_name;

    /**
     * 包含前缀的数据表名称
     *
     * @var string
     */
    public $full_table_name;

    /**
     * 数据表的完全限定名（不包含 schema）
     *
     * @var string
     */
    public $qtable_name;

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
    protected $has_one;

    /**
     * 定义 HAS MANY 关联
     *
     * @var array
     */
    protected $has_many;

    /**
     * 定义 BELONGS TO 关联
     *
     * @var array
     */
    protected $belongs_to;

    /**
     * 定义 MANY TO MANY 关联
     *
     * @var array
     */
    protected $many_to_many;

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
    protected $created_time_fields = array('created', 'updated');

    /**
     * 创建和更新记录时，要自动填入当前时间的字段
     *
     * 只要数据表具有下列字段之一，则调用 create() 方法创建记录或 update() 更新记录时，
     * 将以服务器时间自动填充该字段。
     *
     * @var array
     */
    protected $updated_time_fields = array('updated');

    /**
     * 数据库访问对象
     *
     * 开发者不应该直接访问该成员变量，而是通过 set_dbo() 和 get_dbo() 方法
     * 来访问表数据入口使用数据访问对象。
     *
     * @var QDBO_Adapter_Abstract
     */
    protected $dbo;

    /**
     * 指示表数据入口对象是否已经初始化
     *
     * @var boolean
     */
    protected $is_init = false;

    /**
     * 当前数据表的元数据
     *
     * 元数据是一个二维数组，每一个元素的键名就是全大写的字段名，
     * 而键值则是该字段的数据表定义。
     *
     * @var array
     */
    protected static $tables_meta;

    /**
     * 构造 Table 实例
     *
     * $params 参数允许有下列选项：
     *   - schema: 指定数据表的 schema
     *   - table_name: 指定数据表的名称
     *   - full_table_name: 指定数据表的完整名称
     *   - pk: 指定主键字段名
     *   - dbo: 指定数据库访问对象
     *
     * @param array $params
     * @param boolean $connect_now 指示是否立即连接数据库
     *
     * @return Table
     */
    function __construct(array $params = null, $connect_now = false)
    {
        if (!empty($params['schema'])) {
            $this->schema = $params['schema'];
        }
        if (!empty($params['table_name'])) {
            $this->table_name = $params['table_name'];
        }
        if (!empty($params['full_table_name'])) {
            $this->full_table_name = $params['full_table_name'];
            if (empty($this->table_name)) {
                $this->table_name = $this->full_table_name;
            }
        }
        if (!empty($params['pk'])) {
            $this->pk = $params['pk'];
        }
        if (!empty($params['dbo'])) {
            $this->setDBO($params['dbo']);
        }
        if ($connect_now) {
            $this->connect();
            $this->relinks();
        }
    }
    
    /**
     * 清除表数据入口对象实例的所有关联
     */
    function clearLinks()
    {
    	$this->links = null;
    }

    /**
     * 根据类定义的 $has_one、$has_many、$belongs_to 和 $many_to_many 成员变量重建所有关联
     */
    function relinks()
    {
        $this->clearLinks();
        if (is_array($this->has_one)) {
            $this->createLink($this->has_one, self::HAS_ONE);
        }
        if (is_array($this->belongs_to)) {
            $this->createLink($this->belongs_to, self::BELONGS_TO);
        }
        if (is_array($this->has_many)) {
            $this->createLink($this->has_many, self::HAS_MANY);
        }
        if (is_array($this->many_to_many)) {
            $this->createLink($this->many_to_many, self::MANY_TO_MAMNY);
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
    function createLink(array $link_define, $type)
    {
        if (!is_array(reset($link_define))) {
            $link_define = $link_define;
        }
        $return = array();
        foreach ($link_define as $def) {
            if (!is_array($def)) {
            	// LC_MSG: $link_define typemismatch, expected array, actual is %s
                throw new QTable_Exception(__('$link_define typemismatch, expected array, actual is %s', gettype($def)));
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
        $this->fillFieldsWithCurrentTime($row, $this->created_time_fields);
        if (empty($row[$this->pk]) || $row[$this->pk] == 0) {
            unset($row[$this->pk]);
        }
        $mpk = strtolower($this->pk);
        if (!isset($row[$this->pk]) && !self::$tables_meta[$this->cache_id][$mpk]['auto_incr']) {
            // 如果主键字段不是自增字段，并且 $row 没有包含主键字段值时，则获取一个新的主键字段值
            $row[$this->pk] = $this->nextID();
        }
        $sql = $this->dbo->getInsertSQL($row, $this->full_table_name, $this->schema);
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
        $this->fillFieldsWithCurrentTime($row, $this->updated_time_fields);
        $sql = $this->dbo->getUpdateSQL($row, $this->pk, $this->full_table_name, $this->schema);
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
        $sql = "UPDATE {$this->qtable_name} SET " . implode(',', $args[0]);
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
        foreach ($this->updated_time_fields as $field) {
            $field = strtolower($field);
            $sql .= self::$tables_meta[$this->cache_id][$field]['name'];
            $sql .= ' = ';
            switch (self::$tables_meta[$this->cache_id][$field]['ptype']) {
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
        $this->fillFieldsWithCurrentTime($row, $this->created_time_fields);
        $sql = $this->dbo->getReplaceSQL($row, $this->qtable_name);
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
        $sql = "DELETE FROM {$this->qtable_name} WHERE {$this->qpk} = {$pkv}";
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

        $sql = "DELETE FROM {$this->qtable_name}";
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
        $seqname = $this->dbo->qtable($this->full_table_name . '_seq', $this->schema);
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
        return self::$tables_meta[$this->cache_id];
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
     * @return QDBO_Adapter_Abstract
     */
    function getDBO()
    {
        return $this->dbo;
    }

    /**
     * 设置数据库访问对象
     *
     * @param QDBO_Adapter_Abstract $dbo
     */
    function setDBO($dbo)
    {
        $this->dbo = $dbo;
        if ($this->schema == '' && $dbo->getSchema() != '') {
            $this->schema = $dbo->getSchema();
        }
        if ($this->full_table_name == '' && $dbo->getTablePrefix() != '') {
            $this->full_table_name = $dbo->getTablePrefix() . $this->table_name;
        } else {
            $this->full_table_name = $this->table_name;
        }
        $this->qtable_name = $this->dbo->qtable($this->full_table_name);
        $this->qpk = $this->dbo->qfield($this->pk, $this->full_table_name, $this->schema);
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
         * 当第一次访问对象的 cache_id 属性时，将连接数据库
         * 通过这个技巧，可以避免无谓的判断和函数调用
         */
        if ($varname == 'cache_id') {
            $this->connect();
            return $this->cache_id;
        }
        throw new QTable_Exception(__('Undefined property "%s"', $varname));
    }

    /**
     * 连接到数据库
     */
    function connect()
    {
        if ($this->is_init) { return; }

        if (is_null($this->dbo)) {
            $dbo = QDBO::getConn();
            $this->setDBO($dbo);
        }

        if (!$this->dbo->isConnected()) {
            $this->dbo->connect();
        }
        $this->cache_id = $this->dbo->getID() . '/' . $this->qtable_name;
        $this->prepareMETA();

        // 如果没有指定主键，则自动设置主键字段
        if ($this->pk == '') {
            foreach (self::$tables_meta[$this->cache_id] as $field) {
                if ($field['pk']) {
                    $this->pk = $field['name'];
                    break;
                }
            }
            if ($this->pk == '') {
                throw new QTable_Exception(__('Not found primary key in table "%s"', $this->qtable_name));
            }
        }

        // 过滤 created_time_fields 和 updated_time_fields
        foreach ($this->created_time_fields as $offset => $field) {
            if (!isset(self::$tables_meta[$this->cache_id][strtolower($field)])) {
                unset($this->created_time_fields[$offset]);
            }
        }

        foreach ($this->updated_time_fields as $offset => $field) {
            if (!isset(self::$tables_meta[$this->cache_id][strtolower($field)])) {
                unset($this->updated_time_fields[$offset]);
            }
        }
        
        $this->is_init = true;
    }

    function qfields($fields)
    {
        return $this->dbo->qfields($fields, $this->full_table_name, $this->schema);
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
            return $this->parseWhereArray($where);
        } else {
            return $this->parseWhereString($where, $args);
        }
    }
    
    /**
     * 按照模式2对查询条件进行分析
     *
     * @param array $where
     *
     * @return array|string
     */
    protected function parseWhereArray(array $where)
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
                $field = $this->parseWhereQfield(array('', $key));
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
    protected function parseWhereString($where, array $args = null)
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
        if (strpos($where, '[') !== false) {
            // 提取字段名
            $where = preg_replace_callback('/\[([a-z0-9_\-\.]+)\]/i', array($this, 'parseWhereQfield'), $where);
        }

        // 分析查询条件中的参数占位符
        if (strpos($where, '?') !== false) {
            // 使用 ? 作为占位符的情况
            return $this->dbo->qinto($where, $args, QDBO::PARAM_QM);
        }

        if (strpos($where, ':') !== false) {
            // 使用 : 开头的命名参数占位符
            return $this->dbo->qinto($where, $args, QDBO::PARAM_CL_NAMED);
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
    private function parseWhereQfield($matches)
    {
        $p = explode('.', $matches[1]);
        switch (count($p)) {
        case 3:
            list($schema, $table, $field) = $p;
            if ($table == $this->table_name) {
                $table = $this->full_table_name;
            }
            return $this->dbo->qfield($field, $table, $schema);
        case 2:
            list($table, $field) = $p;
            if ($table == $this->table_name) {
                $table = $this->full_table_name;
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
    protected function fillFieldsWithCurrentTime(array & $row, array $fields)
    {
        $curr = time();
        $curr_db_time = $this->dbo->dbTimestamp($curr);

        foreach ($fields as $field) {
            $mf = strtolower($field);
            if (!isset(self::$tables_meta[$this->cache_id][$mf])) { continue; }
            switch (self::$tables_meta[$this->cache_id][$mf]['ptype']) {
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
        $sql = "UPDATE {$this->qtable_name} SET {$field} = {$field} {$op} {$step}";
        if (!empty($where)) { $sql .= " WHERE {$where}"; }
        return $this->updateBySQL($sql);
    }
    
    /**
     * 准备当前数据表的元数据
     *
     * @return boolean
     */
    private function prepareMETA()
    {
        if (isset(self::$tables_meta[$this->cache_id])) { return; }
        $cached = Q::getIni('db_meta_cached');

        if ($cached) {
            // 尝试从缓存读取
            $policy = array('encoding_filename' => true, 'serialize' => true);
            $backend = Q::getIni('db_meta_cache_backend');
            $meta = Q::getCache($this->cache_id, $policy, $backend);
            if (is_array($meta)) {
                self::$tables_meta[$this->cache_id] = $meta;
                return;
            }
        }

        // 从数据库获得 meta
        $meta = $this->dbo->metaColumns($this->full_table_name, $this->schema);
        self::$tables_meta[$this->cache_id] = $meta;
        if ($cached) {
            // 缓存数据
            Q::setCache($this->cache_id, $meta, $policy, $backend);
        }
    }
}
