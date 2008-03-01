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
 * 定义 QDB_Table 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table 类（表数据入口）封装数据表的 CRUD 操作，并提供了扩展接口。
 *
 * @package database
 */
class QDB_Table
{
    /**
     * 关联关系
     */
    const HAS_ONE       = 'has_one';
    const HAS_MANY      = 'has_many';
    const BELONGS_TO    = 'belongs_to';
    const MANY_TO_MANY = 'many_to_many';

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
     * @var string|array
     */
    public $pk;

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
     * 保存该表数据入口的所有关联
     *
     * @var array
     */
    protected $links = array();

    /**
     * 指示是否使用了复合主键
     *
     * @var boolean
     */
    protected $is_cpk;

    /**
     * 指示主键字段的总数
     *
     * @var int
     */
    protected $pk_count;

    /**
     * 当前数据表的元数据
     *
     * 元数据是一个二维数组，每个元素的键名就是全小写的字段名，而键值则是该字段的数据表定义。
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
            $this->setConn($params['dbo']);
        }
        if ($connect_now) {
            $this->connect();
        }
        $this->relinks();
    }

    /**
     * 根据类定义的 $has_one、$has_many、$belongs_to 和 $many_to_many 成员变量重建所有关联
     */
    function relinks()
    {
        $this->removeAllLinks();
        if (is_array($this->has_one)) {
            $this->createLinks($this->has_one, self::HAS_ONE);
        }
        if (is_array($this->belongs_to)) {
            $this->createLinks($this->belongs_to, self::BELONGS_TO);
        }
        if (is_array($this->has_many)) {
            $this->createLinks($this->has_many, self::HAS_MANY);
        }
        if (is_array($this->many_to_many)) {
            $this->createLinks($this->many_to_many, self::MANY_TO_MANY);
        }
    }

    /**
     * 建立关联
     *
     * @param array $lines_define
     * @param const $type
     */
    function createLinks(array $links_define, $type)
    {
        if (!is_array(reset($links_define))) {
            $links_define = array($links_define);
        }
        Q::loadClass('QDB_Table_Link');
        foreach ($links_define as $define) {
            $link = QDB_Table_Link::createLink($define, $type, $this);
            $this->links[$link->name] = $link;
        }
    }

    /**
     * 检查指定名称的单个关联是否存在
     *
     * @param string $link_name
     *
     * @return boolean
     */
    function existsLink($link_name)
    {
        return isset($this->links[$link_name]);
    }

    /**
     * 返回指定名称的关联，如果关联不存在则抛出异常
     *
     * @param string $link_name
     *
     * @return QDB_Table_Link
     */
    function getLink($link_name)
    {
        if (isset($this->links[$link_name])) {
            return $this->links[$link_name];
        }

        // LC_MSG: Specified link "%s" not found.
        throw new QDB_Table_Exception(__('Specified link "%s" not found.', $link_name));
    }

    /**
     * 允许指定名称的关联，如果关联不存在则抛出异常
     *
     * @param array|string $links_name
     */
    function enableLinks($links_name)
    {
        if (!is_array($links_name)) {
            $links_name = Q::normalize($links_name);
        }
        foreach ($links_name as $name) {
            if (isset($this->links[$name])) {
                $this->links[$name]->enable();
            } else {
                // LC_MSG: Specified link "%s" not found.
                throw new QDB_Table_Exception(__('Specified link "%s" not found.', $name));
            }
        }
    }

    /**
     * 禁用指定名称的关联，如果关联不存在则抛出异常
     *
     * @param array|string $links_name
     */
    function disableLinks($links_name)
    {
        if (!is_array($links_name)) {
            $links_name = Q::normalize($links_name);
        }
        foreach ($links_name as $name) {
            if (isset($this->links[$name])) {
                $this->links[$name]->disable();
            } else {
                // LC_MSG: Specified link "%s" not found.
                throw new QDB_Table_Exception(__('Specified link "%s" not found.', $name));
            }
        }
    }

    /**
     * 清除指定名称的关联，如果关联不存在则抛出异常
     *
     * @param array|string $links_name
     */
    function removeLinks($links_name)
    {
        if (!is_array($links_name)) {
            $links_name = Q::normalize($links_name);
        }
        foreach ($links_name as $name) {
            if (isset($this->links[$name])) {
                unset($this->links[$name]);
            } else {
                // LC_MSG: Specified link "%s" not found.
                throw new QDB_Table_Exception(__('Specified link "%s" not found.', $name));
            }
        }
    }

    /**
     * 清除表数据入口对象实例的所有关联
     */
    function removeAllLinks()
    {
        $this->links = null;
    }

    /**
     * 创建一个查询对象
     *
     * @param string|array $where
     *
     * @return QDB_Table_Select
     */
    function find($where = null)
    {
        $args = func_get_args();
        array_shift($args);
        return new QDB_Table_Select($this, $where, $args, $this->links);
    }

    /**
     * 直接执行一个 SQL 语句，并返回结果集（数组）
     *
     * @param string $sql
     * @param array $args
     *
     * @return array
     */
    function findBySQL($sql)
    {
        $args = func_get_args();
        array_shift($args);
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
        // TODO: create() 实现对关联的处理
        $this->fillFieldsWithCurrentTime($row, $this->created_time_fields);

        $fill_pk = false;
        if ($this->is_cpk) {
            // 处理复合主键
            $pkvc = 0;
            foreach ($this->pk as $pk) {
                if (!isset($row[$pk])) { continue; }
                if ($row[$pk] === '' || $row[$pk] === 0) {
                    unset($row[$pk]);
                } else {
                    $pkvc++;
                }
            }
            if ($pkvc == 0 && $this->pk_count == 1) {
                /**
                 * 如果没有指定任何主键值，并且仅有一个主键时，判断该主键是否是自增
                 * 如果不是自增主键，则通过序列获取一个新的主键值
                 */
                $fill_pk = true;
                $pk = $this->pk[0];
            }
        } else {
            // 单一主键
            if (isset($row[$this->pk]) && ($row[$this->pk] === '' || $row[$this->pk] === 0)) {
                unset($row[$this->pk]);
            }
            $fill_pk = true;
            $pk = $this->pk;
        }
        // 如果没有设置主键字段，并且主键
        if ($fill_pk && !self::$tables_meta[$this->cache_id][strtolower($pk)]['auto_incr']) {
            $row[$pk] = $this->nextID();
            $insert_id = $row[$pk];
        }

        // 创建 INSERT 语句并执行
        $sql = $this->dbo->getInsertSQL($row, $this->full_table_name, $this->schema);
        $this->dbo->execute($sql, $row);

        if (isset($insert_id)) {
            return $insert_id;
        } elseif (!$this->is_cpk) {
            return $this->dbo->insertID();
        } else {
            // 对于复合主键记录，尝试返回所有可能的主键字段值
            $return = array();
            foreach ($this->pk as $pk) {
                if (isset($row[$pk])) {
                    $return[$pk] = $row[$pk];
                }
            }
            return $return;
        }
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
        // TODO: update() 实现对关联的处理
        // TODO: update() 实现对复合主键的处理
        $this->fillFieldsWithCurrentTime($row, $this->updated_time_fields);
        $sql = $this->dbo->getUpdateSQL($row, $this->pk, $this->full_table_name, $this->schema);
        unset($row[$this->pk]);
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
     * @param array $pairs 要更新字段的值
     * @param array|string $where
     *
     * @return int
     */
    function updateWhere(array $pairs, $where)
    {
        // TODO: updateWhere() 实现对关联的处理
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        list($where, ) = $this->parseSQLInternal($where, $args);
        list($holders, $values) = $this->dbo->getPlaceholderPairs($pairs);
        $sql = "UPDATE {$this->qtable_name} SET " . implode(',', $holders);
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $this->dbo->execute($sql, $values);
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
     * 增加所有符合条件记录的指定字段值，返回被更新记录的总数
     *
     * @param string $field
     * @param int $step
     * @param array|string $where
     *
     * @return mixed
     */
    function incrWhere($field, $step = 1, $where)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        return $this->incrOrDecrWhere($field, $step, true, $where, $args);
    }

    /**
     * 减小所有符合条件记录的指定字段值，返回被更新记录的总数
     *
     * @param string $field
     * @param int $step
     * @param array|string $where
     *
     * @return mixed
     */
    function decrWhere($field, $step = 1, $where)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        return $this->incrOrDecrWhere($field, $step, false, $where, $args);
    }

    /**
     * 根据是否包含主键字段值，创建或更新一条记录，返回记录的主键值
     *
     * @return mixed
     */
    function save(array $row)
    {
        if ($this->is_cpk) {
            // 对于复合主键的数据表，save() 方法无法判断是创建还是更新，所以抛出一个异常
            // LC_MSG: QDB_Table::save() with composite primary key not implemented.
            throw new QDB_Table_Exception(__('QDB_Table::save() with composite primary key not implemented.'));
        }

        if (isset($row[$this->pk])) {
            return $this->update($row);
        } else {
            return $this->create($row);
        }
    }

    /**
     * 批量保存记录集，返回所有记录的主键值
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
     * 用 SQL 的 REPLACE 操作替换一条现有记录或插入新记录，返回被影响的记录数
     *
     * @param array $row
     *
     * @return mixed
     */
    function replace(array $row)
    {
        $this->fillFieldsWithCurrentTime($row, $this->created_time_fields);
        $sql = $this->dbo->getReplaceSQL($row, $this->full_table_name, $this->schema);
        $this->dbo->execute($sql, $row);
        return $this->dbo->affectedRows();
    }

    /**
     * 替换一组现有记录或插入新记录，返回被影响的记录总数
     *
     * @param array $rowset
     *
     * @return array
     */
    function replaceRowset(array $rowset)
    {
        $update_count = 0;
        foreach (array_keys($rowset) as $offset) {
            $update_count += (int)$this->replace($rowset[$offset], true);
        }
        return $update_count;
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
        // TODO: remove() 实现对复合主键的处理
        // TODO: remove() 实现对关联的处理
        $pkv = $this->dbo->qstr($pkv);
        $sql = "DELETE FROM {$this->qtable_name} WHERE {$this->qpk} = {$pkv}";
        $this->dbo->execute($sql);
        return $this->dbo->affectedRows();
    }

    /**
     * 删除所有符合条件的记录，返回被删除的记录总数
     *
     * @param mixed $where
     *
     * @return ini
     */
    function removeWhere($where)
    {
        // TODO: removeWhere() 实现对关联的处理
        $args = func_get_args();
        array_shift($args);
        list($where, ) = $this->parseSQLInternal($where, $args);
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
     * 确认是否是复合主键
     *
     * @return boolean
     */
    function isCompositePK()
    {
        return $this->is_cpk;
    }

    /**
     * 返回该表数据入口对象使用的数据访问对象
     *
     * @return QDB_Adapter_Abstract
     */
    function getConn()
    {
        return $this->dbo;
    }

    /**
     * 设置数据库访问对象
     *
     * @param QDB_Adapter_Abstract $dbo
     */
    function setConn($dbo)
    {
        $this->dbo = $dbo;
        if (empty($this->schema) && $dbo->getSchema() != '') {
            $this->schema = $dbo->getSchema();
        }
        if (empty($this->full_table_name) && $dbo->getTablePrefix() != '') {
            $this->full_table_name = $dbo->getTablePrefix() . $this->table_name;
        } elseif (empty($this->full_table_name)) {
            $this->full_table_name = $this->table_name;
        }
        $this->qtable_name = $this->dbo->qtable($this->full_table_name);
    }

    /**
     * 设置表数据入口要使用的数据库访问对象
     */
    function setupDBO()
    {
        $dbo = QDB::getConn();
        $this->setConn($dbo);
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
        if ($varname == 'cache_id' || $varname == 'qpk') {
            $this->connect();
            return $this->{$varname};
        }
        if ($varname == 'dbo') {
            $this->setupDBO();
            return $this->dbo;
        }
        throw new QDB_Table_Exception(__('Undefined property "%s"', $varname));
    }

    /**
     * 连接到数据库
     */
    function connect()
    {
        if (!$this->dbo->isConnected()) {
            $this->dbo->connect();
        }
        $this->cache_id = $this->dbo->getID() . '/' . $this->qtable_name;
        $this->prepareMETA();

        // 处理主键字段
        $pk = Q::normalize($this->pk);
        $this->pk_count = count($pk);
        $this->is_cpk = $this->pk_count > 1;
        $this->pk = ($this->is_cpk) ? $pk : reset($pk);

        $this->qpk = ($this->is_cpk) ?
                     $this->dbo->qfields($this->pk, $this->full_table_name, null, true) :
                     $this->dbo->qfield($this->pk, $this->full_table_name);

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
    }

    /**
     * 转义字段
     *
     * @param string|array $fields
     * @param boolean $return_array
     *
     * @return string|array
     */
    function qfields($fields, $return_array = false)
    {
        if (!is_array($fields)) {
            $fields = Q::normalize($fields);
        }
        $return = array();
        foreach ($fields as $key => $value) {
            if (is_string($key)) {
                $field = $this->dbo->qfield($key, $this->full_table_name, $this->schema);
                $return[] =  $field . ' AS ' . $this->dbo->qfield($value);
            } else {
                $return[] = $this->dbo->qfield($value, $this->full_table_name, $this->schema);
            }
        }

        return ($return_array) ? $return : implode(', ', $return);
    }

    /**
     * 分析查询条件和参数
     *
     * @param array|string $where
     *
     * @return string
     */
    function parseSQL($where)
    {
        $args = func_get_args();
        array_shift($args);
        list($string, ) = $this->parseSQLInternal($where, $args);
        return $string;
    }

    /**
     * 内部使用的 parseSQL()
     *
     * @param mixed $where
     * @param array $args
     *
     * @return array
     */
    function parseSQLInternal($where, array $args = null)
    {
        if (empty($where)) { return array(null, null, null); }
        if (is_null($args)) {
            $args = array();
        }
        if (is_int($where)) {
            return array("{$this->qpk} = {$where}", array(), null);
        }

        if (is_array($where)) {
            return $this->parseSQLArray($where, $args);
        } else {
            return $this->parseSQLString($where, $args);
        }
    }

    /**
     * 按照模式2对查询条件进行分析
     *
     * @param array $where
     * @param array $args
     *
     * @return array|string
     */
    protected function parseSQLArray(array $where, array $args = null)
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
        $args_count = 0;

        foreach ($where as $key => $value) {
            if (is_int($key)) {
                // 递归调用，进一步分析 SQL
                list($part, , $count) = $this->parseSQLInternal($value, $args, $args_count);
                if (empty($part)) { continue; }
                $args_count += $count;
                $parts[] = $part;
                if ($value == ')') {
                    $next_op = 'AND';
                } else {
                    $next_op = '';
                }
            } else {
                if ($next_op != '') {
                    $parts[] = $next_op;
                }
                $field = $this->parseSQLQfield(array('', $key));
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

        return array(implode(' ', $parts), array(), null);
    }

    /**
     * 按照模式1对查询条件进行分析
     *
     * @param string $where
     * @param array $args
     * @param boolean|int $ignore_args
     *
     * @return array|string
     */
    protected function parseSQLString($where, array $args = null, $ignore_args = false)
    {
        /**
         * 模式1：
         * where('user_id = ?', array($user_id))
         * where('user_id = :user_id', array('user_id' => $user_id))
         * where('user_id in (?)', array(array($id1, $id2, $id3)))
         * where('user_id = :user_id', array('user_id' => $user_id))
         * where('user_id IN (:users_id)', array('users_id' => array(1, 2, 3)))
         */

        /**
         * 从查询条件中，需要分析出字段名，以及涉及到的关联表
         *
         */
        $matches = array();
        preg_match_all('/\[[a-z][a-z0-9_\.]*\]/i', $where, $matches, PREG_OFFSET_CAPTURE);
        $matches = reset($matches);

        $out = '';
        $offset = 0;
        $used_links = array();
        foreach ($matches as $m) {
            $len = strlen($m[0]);
            $field = substr($m[0], 1, $len - 2);
            $arr = explode('.', $field);
            switch(count($arr)) {
            case 3:
                $schema = $arr[0];
                $table = $arr[1];
                $field = $arr[2];
                break;
            case 2:
                $schema = $this->schema;
                $table = $arr[0];
                $field = $arr[1];
                break;
            default:
                $schema = $this->schema;
                $table = $this->full_table_name;
                $field = $arr[0];
            }

            if (isset($this->links[$table])) {
                // 找到一个关联表字段
                $link = $this->links[$table];
                /* @var $link QDB_Table_Link */
                $used_links[] = $link;
                // TODO: parseSQLString() 处理查询中的关联表
            } else {
                $field = $this->dbo->qfield($field, $table, $schema);
            }

            $out .= substr($where, $offset, $m[1] - $offset) . $field;
            $offset = $m[1] + $len;
        }
        $out .= substr($where, $offset);
        $where = $out;

        // 分析查询条件中的参数占位符
        $args_count = null;
        if (strpos($where, '?') !== false) {
            // 使用 ? 作为占位符的情况
            $ret = $this->dbo->qinto($where, $args, QDB::PARAM_QM, $ignore_args);
        } elseif (strpos($where, ':') !== false) {
            // 使用 : 开头的命名参数占位符
            $args = reset($args);
            $ret = $this->dbo->qinto($where, $args, QDB::PARAM_CL_NAMED, $ignore_args);
        } else {
            $ret = $where;
        }
        if (is_array($ret)) {
            list($where, $args_count) = $ret;
        } else {
            $where = $ret;
        }
        return array($where, $used_links, $args_count);
    }

    /**
     * 将字段名替换为转义后的完全限定名
     *
     * @param array $matches
     *
     * @return string
     */
    private function parseSQLQfield($matches)
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


    /**
     * 获得用于更新 updated 字段的 sql 语句
     *
     * @return string
     */
    protected function getUpdatedTimeSQL()
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

    protected function incrOrDecrWhere($field, $step, $is_incr, $where, array $args = null)
    {
        $where = $this->parseSQLInternal($where, $args);
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
        $cached = Q::getIni('db_meta_cached');

        if ($cached) {
            // 尝试从缓存读取
            $policy = array('encoding_filename' => true, 'serialize' => true);
            $backend = Q::getIni('db_meta_cache_backend');
            $meta = Q::getCache($this->cache_id, $policy, $backend);
            if (is_array($meta) && !empty($meta)) {
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

    /**
     * 准备主键
     */
    private function preparePK()
    {
        $this->pk = Q::normalize($this->pk);
        $this->is_cpk = count($this->pk) > 1;
        $this->qpk = $this->dbo->qfields($this->pk, $this->full_table_name);
    }
}
