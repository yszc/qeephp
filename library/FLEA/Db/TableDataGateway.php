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
 * 定义 FLEA_Db_TableDataGateway 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Database
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Db/TableLink.php';
// }}}

// {{{ constants
/**
 * HAS_ONE 关联表示一个记录拥有另一个关联的记录
 */
define('HAS_ONE',       1);

/**
 * BELONGS_TO 关联表示一个记录属于另一个记录
 */
define('BELONGS_TO',    2);

/**
 * HAS_MANY 关联表示一个记录拥有多个关联的记录
 */
define('HAS_MANY',      3);

/**
 * MANY_TO_MANY 关联表示两个数据表的数据互相引用
 */
define('MANY_TO_MANY',  4);
// }}}

/**
 * FLEA_Db_TableDataGateway 类（表数据入口）封装了数据表的 CRUD 操作
 *
 * 开发者应该从 FLEA_Db_TableDataGateway 派生自己的类，
 * 并通过添加方法来封装针对该数据表的更复杂的数据库操作。
 *
 * 对于每一个表数据入口对象，都必须在类定义中通过 $tableName 和 $primaryKey
 * 来分别指定数据表的名字和主键字段名。
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.2
 */
class FLEA_Db_TableDataGateway
{
    /**
     * 指示是否自动开启事务
     *
     * @var boolean
     */
    public $autoStartTrans = true;

    /**
     * 数据表所处 schema
     *
     * @var string
     */
    public $schema = null;

    /**
     * 数据表名（没有添加前缀）
     *
     * @var string
     */
    public $tableName = null;

    /**
     * 包含前缀的完整数据表名称
     *
     * @var string
     */
    public $fullTableName = null;

    /**
     * 主键字段名
     *
     * @var sring
     */
    public $primaryKey = null;

    /**
     * 定义一对一关联
     *
     * @var array
     */
    public $hasOne = null;

    /**
     * 定义从属关联
     *
     * @var array
     */
    public $belongsTo = null;

    /**
     * 定义一对多关联
     *
     * @var array
     */
    public $hasMany = null;

    /**
     * 定义多对多关联
     *
     * @var array
     */
    public $manyToMany = null;

    /**
     * 当前数据表的元数据
     *
     * 元数据是一个二维数组，每一个元素的键名就是全大写的字段名，而键值则是该字段的数据表定义。
     *
     * @var array
     */
    public $meta = null;

    /**
     * 指示是否对数据进行自动验证
     *
     * 当 autoValidating 为 true 时，create()、save() 和 update() 方法将对数据进行验证。
     *
     * @var boolean
     */
    public $autoValidating = false;

    /**
     * 用于数据验证的对象
     *
     * @var FLEA_Helper_Verifier
     */
    public $verifier = null;

    /**
     * 附加的验证规则
     *
     * @var array
     */
    public $validateRules = null;

    /**
     * 创建记录时，要自动填入当前时间的字段
     *
     * 只要数据表具有下列字段之一，则调用 create() 方法创建记录时，
     * 将以服务器时间自动填充该字段。
     *
     * @var array
     */
    public $createdTimeFields = array('CREATED', 'CREATED_ON', 'CREATED_AT');

    /**
     * 创建和更新记录时，要自动填入当前时间的字段
     *
     * 只要数据表具有下列字段之一，则调用 create() 方法创建记录或 update() 更新记录时，
     * 将以服务器时间自动填充该字段。
     *
     * @var array
     */
    public $updatedTimeFields = array('UPDATED', 'UPDATED_ON', 'UPDATED_AT');

    /**
     * 指示进行 CRUD 操作时是否处理关联
     *
     * 开发者应该使用 enableLinks() 和 disableLinks() 方法来启用或禁用关联处理。
     *
     * @var boolean
     */
    public $autoLink = true;

    /**
     * 数据库访问对象
     *
     * 开发者不应该直接访问该成员变量，而是通过 setDBO() 和 getDBO() 方法
     * 来访问表数据入口使用数据访问对象。
     *
     * @var FLEA_Db_Driver_Abstract
     */
    public $dbo = null;

    /**
     * 存储关联信息
     *
     * $links 是一个数组，数组中保存 TableLink 对象。
     * 开发者应该使用 getLink() 和 createLink() 等方法来访问这些关联对象。
     *
     * @var array
     */
    public $links = array();

    /**
     * 包含前缀的数据表完全限定名
     *
     * @var string
     * @access private
     */
    public $qtableName;

    /**
     * 主键字段完全限定名
     *
     * @var string
     * @access private
     */
    public $qpk;

    /**
     * 用于关联查询时的主键字段别名
     */
    public $pka;

    /**
     * 用于关联查询时的主键字段完全限定名
     *
     * @var string
     * @access private
     */
    public $qpka;

    /**
     * 保存最后一次数据验证的结果
     *
     * 调用 getLastValidation() 方法可以获得最后一次数据验证的结果。
     *
     * @var array
     */
    public $lastValidationResult;

    /**
     * 构造 FLEA_Db_TableDataGateway 实例
     *
     * $params 参数允许有下列选项：
     *
     * 'autoStartTrans', 'schema', 'tableName', 'primaryKey',
     * 'hasOne', 'belongsTo', 'hasMany', 'manyToMany', 'autoValidating',
     * 'verifier', 'validateRules', 'createdTimeFields', 'updatedTimeFields', 'autoLink'
     *
     * 以及：
     *
     * verifierProvider: 指定要使用的数据验证服务对象。如果未指定。则使用应用程序设置 helper.verifier 指定的验证服务提供对象；
     * skipConnect: 指示初始化表数据入口对象时是否不连接到数据库；
     * dbDSN: 指定连接数据库要使用的 DSN，如果未指定则使用默认的 DSN 设置；
     * dbo: 指定要使用的数据库访问对象;
     * skipCreateLinks: 指示初始化表数据入口时，是否不建立关联关系。
     *
     * @param array $params
     *
     * @return FLEA_Db_TableDataGateway
     */
    public function __construct(array $params = array())
    {
        static $opts = array(
                'autoStartTrans', 'schema', 'tableName', 'primaryKey',
                'hasOne', 'belongsTo', 'hasMany', 'manyToMany', 'autoValidating',
                'verifier', 'validateRules', 'createdTimeFields', 'updatedTimeFields',
                'autoLink'
        );

        foreach ($opts as $key) {
            if (isset($params[$key])) {
                $this->{$key} = $params[$key];
            }
        }

        if (isset($params['verifierProvider'])) {
            $provider = $params['verifierProvider'];
            $this->verifier = FLEA::getSingleton($provider);
        }
        if ($this->autoValidating && $this->verifier == null) {
            $provider = FLEA::getAppInf('helper.verifier');
            $this->verifier = FLEA::getSingleton($provider);
        }

        if (!isset($params['skipConnect']) || $params['skipConnect'] == false) {
            if (!isset($params['dbo'])) {
                $dsn = isset($params['dbDSN']) ? $params['dbDSN'] : 0;
                $dbo = FLEA::getDBO($dsn);
            } else {
                $dbo = $params['dbo'];
            }
            $this->setDBO($dbo);
            if (!isset($params['skipCreateLinks']) || $params['skipCreateLinks'] == false) {
                $this->relink();
            }
        }
    }

    /**
     * 设置数据库访问对象
     *
     * @param FLEA_Db_Driver_Prototype $dbo
     *
     * @return boolean
     */
    public function setDBO(FLEA_Db_Driver_Prototype $dbo)
    {
        $this->dbo = $dbo;
        $this->fullTableName = $dbo->dsn['tablePrefix'] . $this->tableName;
        $this->qtableName = $dbo->qtable($this->fullTableName, $this->schema);
        $this->_prepareMeta();

        if (is_array($this->validateRules)) {
            foreach ($this->validateRules as $fieldName => $rules) {
                $fieldName = strtoupper($fieldName);
                if (!isset($this->meta[$fieldName])) { continue; }
                foreach ((array)$rules as $ruleName => $rule) {
                    $this->meta[$fieldName][$ruleName] = $rule;
                }
            }
        }

        // 如果没有指定主键，则尝试自动获取
        if ($this->primaryKey == null) {
            foreach ($this->meta as $field) {
                if ($field['primaryKey']) {
                    $this->primaryKey = $field['name'];
                    break;
                }
            }
        }

        $this->qpk = $dbo->qfield($this->primaryKey, $this->fullTableName, $this->schema);
        $this->pka = 'qee_pkref_' . strtolower($this->primaryKey);
        $this->qpka = $this->qpk . ' AS ' . $this->pka;

        return true;
    }

    /**
     * 返回该表数据入口对象使用的数据访问对象
     *
     * @return FLEA_Db_Driver_Prototype
     */
    public function getDBO()
    {
        return $this->dbo;
    }

    /**
     * 返回符合条件的第一条记录及所有关联的数据，查询出错抛出异常
     *
     * @param mixed $conditions
     * @param string $sort
     * @param mixed $fields
     * @param mixed $queryLinks
     *
     * @return array
     */
    public function & find($conditions, $sort = null, $fields = '*', $queryLinks = true)
    {
        $rowset =& $this->findAll($conditions, $sort, 1, $fields, $queryLinks);
        $row = reset($rowset);
        unset($rowset);
        return $row;
    }

    /**
     * 查询所有符合条件的记录及相关数据，返回一个包含多行记录的二维数组，失败时返回 false
     *
     * @param mixed $conditions
     * @param string $sort
     * @param mixed $limit
     * @param mixed $fields
     * @param mixed $queryLinks
     *
     * @return array
     */
    public function & findAll($conditions = null, $sort = null, $limit = null, $fields = '*', $queryLinks = true)
    {
        list($whereby, $distinct) = $this->getWhere($conditions);
        // 处理排序
        $sortby = $sort != '' ? "\nORDER BY {$sort}" : '';
        // 处理 $limit
        if (is_array($limit)) {
            list($length, $offset) = $limit;
        } else {
            $length = $limit;
            $offset = null;
        }

        // 构造从主表查询数据的 SQL 语句
        $enableLinks = count($this->links) > 0 && $this->autoLink && $queryLinks;
        $fields = $this->dbo->qfields($fields);
        if ($enableLinks) {
            // 当有关联需要处理时，必须获得主表的主键字段值
            $sql = "SELECT {$distinct}{$this->qpka}, {$fields}\nFROM {$this->qtableName}{$whereby}{$sortby}";
        } else {
            $sql = "SELECT {$distinct}{$fields}\nFROM {$this->qtableName}{$whereby}{$sortby}";
        }

        // 根据 $length 和 $offset 参数决定是否使用限定结果集的查询
        if (null !== $length || null !== $offset) {
            $result = $this->dbo->selectLimit($sql, $length, $offset);
        } else {
            $result = $this->dbo->execute($sql);
        }

        if ($enableLinks) {
            /**
             * 查询时同时将主键值单独提取出来，
             * 并且准备一个以主键值为键名的二维数组用于关联数据的装配
             */
            $pkvs = array();
            $assocRowset = null;
            $rowset = $this->dbo->getAllWithFieldRefs($result, $this->pka, $pkvs, $assocRowset);
            $in = 'IN (';
            foreach ($pkvs as $pkv) {
                $in .= $this->dbo->qstr($pkv);
                $in .= ',';
            }
            $in = substr($in, 0, -1) . ')';
        } else {
            $rowset = $this->dbo->getAll($result);
        }
        unset($result);

        // 如果没有关联需要处理或者没有查询结果，则直接返回查询结果
        if (!$enableLinks || empty($rowset) || !$this->autoLink) {
            return $rowset;
        }

        /**
         * 遍历每一个关联对象，并从关联对象获取查询语句
         *
         * 查询获得数据后，将关联表的数据和主表数据装配在一起
         */
        $callback = create_function('& $row, $offset, $mappingName', '$row[$mappingName] = null;');
        foreach ($this->links as $link) {
            /* @var $link FLEA_Db_TableLink */
            if (!$link->enabled || !$link->linkRead) { continue; }
            array_walk($assocRowset, $callback, $link->mappingName);
            $sql = $link->getFindSQL($in);
            $this->dbo->assemble($sql, $assocRowset, $link->mappingName, $link->oneToOne, $this->pka, $link->limit);
        }

        return $rowset;
    }

    /**
     * 返回具有指定字段值的第一条记录
     *
     * @param string $field
     * @param mixed $value
     * @param string $sort
     * @param mixed $fields
     *
     * @return array
     */
    public function & findByField($field, $value, $sort = null, $fields = '*')
    {
        return $this->find(array($field => $value), $sort, $fields);
    }

    /**
     * 返回具有指定字段值的所有记录
     *
     * @param string $field
     * @param mixed $value
     * @param string $sort
     * @param array $limit
     * @param mixed $fields
     *
     * @return array
     */
    public function & findAllByField($field, $value, $sort = null, $limit = null, $fields = '*')
    {
        return $this->findAll(array($field => $value), $sort, $limit, $fields);
    }

    /**
     * 直接使用 sql 语句获取记录（该方法不会处理关联数据表）
     *
     * @param string $sql
     * @param mixed $limit
     *
     * @return array
     */
    public function & findBySql($sql, $limit = null)
    {
        // 处理 $limit
        if (is_array($limit)) {
            list($length, $offset) = $limit;
        } else {
            $length = $limit;
            $offset = null;
        }
        if (is_null($length) && is_null($offset)) {
            return $this->dbo->getAll($sql);
        }

        $result = $this->dbo->selectLimit($sql, $length, $offset);
        $rowset = $this->dbo->getAll($result);
        return $rowset;
    }

    /**
     * 统计符合条件的记录的总数
     *
     * @param mixed $conditions
     * @param string|array $fields
     *
     * @return int
     */
    public function findCount($conditions = null, $fields = null)
    {
        list($whereby, $distinct) = $this->getWhere($conditions);
        if ($fields == null) {
            $fields = $this->qpk;
        } else {
            $fields = $this->dbo->qfields($fields, $this->fullTableName);
        }
        $sql = "SELECT {$distinct}COUNT({$fields})\nFROM {$this->qtableName}{$whereby}";
        return (int)$this->dbo->getOne($sql);
    }

    /**
     * 保存数据到数据库
     *
     * 如果数据包含主键值，则 save() 会调用 update() 来更新记录，否则调用 create() 来创建记录。
     *
     * @param array $row
     * @param boolean $saveLinks
     */
    public function save(& $row, $saveLinks = true)
    {
        if (!isset($row[$this->primaryKey]) || empty($row[$this->primaryKey])) {
            $this->create($row, $saveLinks);
        } else {
            $this->update($row, $saveLinks);
        }
    }

    /**
     * 保存一个记录集（多行数据）
     *
     * @param array $rowset
     * @param boolean $saveLinks
     */
    public function saveRowset(& $rowset, $saveLinks = true)
    {
        $tran = $this->autoStartTrans ? $tran = $this->dbo->beginTrans() : null;
        foreach ($rowset as $row) {
            $this->save($row, $saveLinks);
        }
    }

    /**
     * 替换一条现有记录或插入新记录，返回记录的主键值，失败返回 false
     *
     * @param array $row
     *
     * @return mixed
     */
    public function replace(& $row) {
        $tran = $this->autoStartTrans ? $tran = $this->dbo->beginTrans() : null;
        $this->_setCreatedTimeFields($row);
        $fields = '';
        $values = '';
        foreach ($row as $field => $value) {
            if (!isset($this->meta[strtoupper($field)])) { continue; }
            $fields .= $this->dbo->qfield($field) . ', ';
            $values .= $this->dbo->qstr($value) . ', ';
        }
        $fields = substr($fields, 0, -2);
        $values = substr($values, 0, -2);
        $sql = "REPLACE INTO {$this->qtableName}\n    ({$fields})\nVALUES ({$values})";
        $this->dbo->execute($sql);

        if (isset($row[$this->primaryKey]) && !empty($row[$this->primaryKey])) {
            return $row[$this->primaryKey];
        }

        return $this->dbo->insertId();
    }

    /**
     * 替换记录集（多行数据），返回记录集的主键字段值，失败返回 false
     *
     * @param array $rowset
     *
     * @return array
     */
    public function replaceRowset(& $rowset)
    {
        $tran = $this->autoStartTrans ? $tran = $this->dbo->beginTrans() : null;
        $ids = array();
        foreach ($rowset as $row) {
            $id = $this->replace($row);
            $ids[] = $id;
        }
        return $ids;
    }

    /**
     * 更新一条现有的记录，成功返回 true，失败返回 false
     *
     * 该操作会引发 _beforeUpdate()、_beforeUpdateDb() 和 _afterUpdateDb() 事件。
     *
     * @param array $row
     * @param boolean $saveLinks
     *
     * @return boolean
     */
    public function update(& $row, $saveLinks = true)
    {
        if (!$this->_beforeUpdate($row)) {
            return false;
        }

        // 检查是否提供了主键值
        if (!isset($row[$this->primaryKey])) {
            require_once 'FLEA/Db/Exception/MissingPrimaryKey.php';
            throw new FLEA_Db_Exception_MissingPrimaryKey($this->primaryKey);
        }

        // 自动填写记录的最后更新时间字段
        $this->_setUpdatedTimeFields($row);

        // 如果提供了验证器，则进行数据验证
        if ($this->autoValidating && $this->verifier != null) {
            if (!$this->checkRowData($row, true)) {
                // 验证失败抛出异常
                require_once 'FLEA/Exception/ValidationFailed.php';
                throw new FLEA_Exception_ValidationFailed($this->getLastValidation(), $row);
            }
        }

        // 开始事务
        $this->dbo->startTrans();

        // 调用 _beforeUpdateDb() 事件
        if (!$this->_beforeUpdateDb($row)) {
            $this->dbo->completeTrans(false);
            return false;
        }

        // 生成 SQL 语句
        $pkv = $row[$this->primaryKey];
        unset($row[$this->primaryKey]);
        $fieldValuePairs = array();
        foreach ($row as $fieldName => $value) {
            if (!isset($this->meta[strtoupper($fieldName)])) { continue; }
            $fieldValuePairs[] = $this->dbo->qfield($fieldName) . ' = ' . $this->dbo->qstr($value);
        }
        $fieldValuePairs = implode(', ', $fieldValuePairs);

        $row[$this->primaryKey] = $pkv;
        $qpkv = $this->dbo->qstr($pkv);
        $where = "{$this->qpk} = {$qpkv}";
        if ($fieldValuePairs != '') {
            $sql = "UPDATE {$this->qtableName}\nSET {$fieldValuePairs}\nWHERE {$where}";
        } else {
            $sql = null;
        }

        // 执行更新操作
        if ($sql) {
            if (!$this->dbo->execute($sql)) {
                $this->dbo->completeTrans(false);
                return false;
            }
        }

        // 处理对关联数据的更新
        if ($this->autoLink && $saveLinks) {
            foreach ($this->links as $link) {
                /* @var $link FLEA_Db_TableLink */
                // 跳过不需要处理的关联
                if (!$link->enabled || !$link->linkUpdate
                    || !isset($row[$link->mappingName])
                    || !is_array($row[$link->mappingName]))
                {
                    continue;
                }

                if (!$link->saveAssocData($row[$link->mappingName], $pkv)) {
                    $this->dbo->completeTrans(false);
                    return false;
                }
            }
        }

        // 提交事务
        $this->dbo->completeTrans();

        $this->_afterUpdateDb($row);

        return true;
    }

    /**
     * 更新记录集（多行记录）
     *
     * @param array $rowset
     * @param boolean $saveLinks
     *
     * @return boolean
     */
    function updateRowset(& $rowset, $saveLinks = true)
    {
        $this->dbo->startTrans();
        foreach ($rowset as $row) {
            if (!$this->update($row, $saveLinks)) {
                $this->dbo->completeTrans(false);
                return false;
            }
        }
        $this->dbo->completeTrans();
        return true;
    }

    /**
     * 更新符合条件的记录，成功返回更新的记录总数，失败返回 false
     *
     * 该操作不会引发任何事件，也不会处理关联数据。
     *
     * @param mixed $conditions
     * @param array $$row
     *
     * @return int|boolean
     */
    function updateByConditions($conditions, & $row)
    {
        $whereby = $this->getWhere($conditions, false);
        $this->_setUpdatedTimeFields($row);
        $fieldValuePairs = array();
        foreach ($row as $field => $value) {
            if (is_int($field)) {
                $fieldValuePairs[] = $value;
            } else {
                $fieldValuePairs[] = $this->dbo->qfield($field) . ' = ' . $this->dbo->qstr($value);
            }
        }
        $fieldValuePairs = implode(', ', $fieldValuePairs);
        $sql = "UPDATE {$this->qtableName}\nSET {$fieldValuePairs}{$whereby}";
        return $this->dbo->execute($sql);
    }

    /**
     * 更新记录的指定字段，返回更新的记录总数
     *
     * 该操作不会引发任何事件，也不会处理关联数据。
     *
     * @param mixed $conditions
     * @param string $field
     * @param mixed $value
     *
     * @return int
     */
    function updateField($conditions, $field, $value)
    {
        $row = array($field => $value);
        return $this->updateByConditions($conditions, $row);
    }

    /**
     * 增加符合条件的记录的指定字段的值，返回更新的记录总数
     *
     * 该操作不会引发任何事件，也不会处理关联数据。
     *
     * @param mixed $conditions
     * @param string $field
     * @param int $incr
     *
     * @return mixed
     */
    function incrField($conditions, $field, $incr = 1)
    {
        $field = $this->dbo->qfield($field, $this->fullTableName);
        $incr = (int)$incr;
        $row = array("{$field} = {$field} + {$incr}");
        return $this->updateByConditions($conditions, $row);
    }

    /**
     * 减小符合条件的记录的指定字段的值，返回更新的记录总数
     *
     * 该操作不会引发任何事件，也不会处理关联数据。
     *
     * @param mixed $conditions
     * @param string $field
     * @param int $decr
     *
     * @return mixed
     */
    function decrField($conditions, $field, $decr = 1)
    {
        $field = $this->dbo->qfield($field, $this->fullTableName);
        $decr = (int)$decr;
        $row = array("{$field} = {$field} - {$decr}");
        return $this->updateByConditions($conditions, $row);
    }

    /**
     * 插入一条新记录，返回新记录的主键值
     *
     * create() 操作会引发 _beforeCreate()、_beforeCreateDb() 和 _afterCreateDb() 事件。
     *
     * @param array $row
     * @param boolean $saveLinks
     *
     * @return mixed
     */
    function create(& $row, $saveLinks = true)
    {
        if (!$this->_beforeCreate($row)) {
            return false;
        }

        // 自动设置日期字段
        $this->_setCreatedTimeFields($row);

        // 处理主键
        $mpk = strtoupper($this->primaryKey);
        $insertId = null;
        $unsetpk = true;
        if (isset($this->meta[$mpk]['autoIncrement'])
            && $this->meta[$mpk]['autoIncrement'])
        {
            if (isset($row[$this->primaryKey])) {
                if (empty($row[$this->primaryKey])) {
                    // 如果主键字段是自增，而提供的记录数据虽然包含主键字段，
                    // 但却是空值，则删除这个空值
                    unset($row[$this->primaryKey]);
                } else {
                    $unsetpk = false;
                }
            }
        } else {
            // 如果主键字段不是自增字段，并且没有提供主键字段值时，则获取一个新的主键字段值
            if (!isset($row[$this->primaryKey]) || empty($row[$this->primaryKey])) {
                $insertId = $this->newInsertId();
                $row[$this->primaryKey] = $insertId;
            } else {
                // 使用开发者提交的主键字段值
                $insertId = $row[$this->primaryKey];
                $unsetpk = false;
            }
        }

        // 自动验证数据
        if ($this->autoValidating && $this->verifier != null) {
            if (!$this->checkRowData($row)) {
                FLEA::loadClass('FLEA_Exception_ValidationFailed');
                __THROW(new FLEA_Exception_ValidationFailed($this->getLastValidation(), $row));
                return false;
            }
        }

        // 调用 _beforeCreateDb() 事件
        $this->dbo->startTrans();

        if (!$this->_beforeCreateDb($row)) {
            if ($unsetpk) { unset($row[$this->primaryKey]); }
            $this->dbo->completeTrans(false);
            return false;
        }

        // 生成 SQL 语句
        $fields = array();
        $values = array();
        foreach ($row as $field => $value) {
            $mfield = strtoupper($field);
            if (!isset($this->meta[$mfield])) { continue; }

            $fields[] = $field;
            $values[] = $this->dbo->qstr($value);
        }
        if (empty($fields)) { return false; }

        $fields = $this->dbo->qfields($fields);
        $values = implode(', ', $values);
        $sql = "INSERT INTO {$this->qtableName}\n    ({$fields})\nVALUES ({$values})";

        // 插入数据
        if (!$this->dbo->Execute($sql)) {
            if ($unsetpk) { unset($row[$this->primaryKey]); }
            $this->dbo->completeTrans(false);
            return false;
        }

        // 如果提交的数据中没有主键字段值，则尝试获取新插入记录的主键值
        if ($insertId == null) {
            $insertId = $this->dbo->insertId();
            if (!$insertId) {
                if ($unsetpk) { unset($row[$this->primaryKey]); }
                $this->dbo->completeTrans(false);
                FLEA::loadClass('FLEA_Db_Exception_InvalidInsertID');
                __THROW(new FLEA_Db_Exception_InvalidInsertID());
                return false;
            }
        }

        // 处理关联数据表
        if ($this->autoLink && $saveLinks) {
            foreach ($this->links as $link) {
                /* @var $link FLEA_Db_TableLink */
                if (!$link->enabled
                    || !$link->linkCreate
                    || !isset($row[$link->mappingName])
                    || !is_array($row[$link->mappingName]))
                {
                    // 跳过没有关联数据的关联和不需要处理的关联
                    continue;
                }

                if (!$link->saveAssocData($row[$link->mappingName], $insertId)) {
                    if ($unsetpk) { unset($row[$this->primaryKey]); }
                    $this->dbo->completeTrans(false);
                    return false;
                }
            }
        }

        // 提交事务
        $this->dbo->CompleteTrans();

        $row[$this->primaryKey] = $insertId;
        $this->_afterCreateDb($row);
        if ($unsetpk) { unset($row[$this->primaryKey]); }

        return $insertId;
    }

    /**
     * 插入多行记录，返回包含所有新记录主键值的数组，如果失败则返回 false
     *
     * @param array $rowset
     * @param boolean $saveLinks
     *
     * @return array
     */
    function createRowset(& $rowset, $saveLinks = true)
    {
        $insertids = array();
        $this->dbo->startTrans();
        foreach ($rowset as $row) {
            $insertid = $this->create($row, $saveLinks);
            if (!$insertid) {
                $this->dbo->completeTrans(false);
                return false;
            }
            $insertids[] = $insertid;
        }
        $this->dbo->completeTrans();
        return $insertids;
    }

    /**
     * 删除记录
     *
     * remove() 操作会引发 _beforeRemove()、_beforeRemoveDbByPkv()、_afterRemoveDbByPkv 和 _afterRemoveDb() 事件。
     *
     * @param array $row
     *
     * @return boolean
     */
    function remove(& $row)
    {
        if (!$this->_beforeRemove($row)) {
            return false;
        }

        if (!isset($row[$this->primaryKey])) {
            FLEA::loadClass('FLEA_Db_Exception_MissingPrimaryKey');
            __THROW(new FLEA_Db_Exception_MissingPrimaryKey($this->primaryKey));
            return false;
        }
        $ret = $this->removeByPkv($row[$this->primaryKey]);
        if ($ret) {
            $this->_afterRemoveDb($row);
        }

        return $ret;
    }

    /**
     * 根据主键值删除记录
     *
     * removeByPkv() 引发 _beforeRemoveDbByPkv() 和 _afterRemoveDbByPkv() 事件。
     *
     * @param mixed $pkv
     *
     * @return boolean
     */
    function removeByPkv($pkv)
    {
        $this->dbo->startTrans();

        if (!$this->_beforeRemoveDbByPkv($pkv)) {
            $this->dbo->completeTrans(false);
            return false;
        }

        /**
         * 首先删除关联表数据，再删除主表数据
         */
        $qpkv = $this->dbo->qstr($pkv);

        // 处理关联数据表
        if ($this->autoLink) {
            foreach ($this->links as $link) {
                /* @var $link FLEA_Db_TableLink */
                if (!$link->enabled) { continue; }
                switch ($link->type) {
                case MANY_TO_MANY:
                    /* @var $link FLEA_Db_ManyToManyLink */
                    if (!$link->deleteMiddleTableDataByMainForeignKey($qpkv)) {
                        $this->dbo->completeTrans(false);
                        return false;
                    }
                    break;
                case HAS_ONE:
                case HAS_MANY:
                    /**
                     * 对于 HAS_ONE 和 HAS_MANY 关联，分为两种情况处理
                     *
                     * 当 $link->linkRemove 为 true 时，直接删除关联表中的关联数据
                     * 否则更新关联数据的外键值为 $link->linkRemoveFillValue
                     */
                    /* @var $link FLEA_Db_HasOneLink */
                    if ($link->deleteByForeignKey($qpkv) === false) {
                        $this->dbo->completeTrans(false);
                        return false;
                    }
                    break;
                }
            }
        }

        // 删除主表数据
        $sql = "DELETE FROM {$this->qtableName}\nWHERE {$this->qpk} = {$qpkv}";
        if ($this->dbo->execute($sql) == false) {
            $this->dbo->completeTrans(false);
            return false;
        }

        // 提交事务
        $this->dbo->completeTrans();

        $this->_afterRemoveDbByPkv($pkv);

        return true;
    }

    /**
     * 删除符合条件的记录
     *
     * @param mixed $conditions
     *
     * @return boolean
     */
    function removeByConditions($conditions)
    {
        $rowset = $this->findAll($conditions, null, null, $this->primaryKey, false);
        $count = 0;
        $this->dbo->startTrans();
        foreach ($rowset as $row) {
            if (!$this->removeByPkv($row[$this->primaryKey])) { break; }
            $count++;
        }
        $this->dbo->completeTrans();
        $rows = $this->dbo->affectedRows();
        if ($rows > 0) { return $count; }
        return 0;
    }

    /**
     * 删除数组中所有主键值的记录，该操作不会处理关联
     *
     * @param array $pkvs
     *
     * @return boolean
     */
    function removeByPkvs($pkvs)
    {
        $ret = true;
        $this->dbo->startTrans();
        foreach ($pkvs as $id) {
            $ret = $this->removeByPkv($id);
            if ($ret === false) { break; }
        }
        $this->dbo->completeTrans();
        return $ret;
    }

    /**
     * 删除所有记录
     *
     * @return boolean
     */
    function removeAll()
    {
        $sql = "DELETE FROM {$this->qtableName}";
        return $this->execute($sql);
    }

    /**
     * 删除所有记录及关联的数据
     *
     * @return boolean
     */
    function removeAllWithLinks()
    {
        $this->dbo->startTrans();

        // 处理关联数据表
        if ($this->autoLink) {
            foreach ($this->links as $link) {
                /* @var $link FLEA_Db_TableLink */
                switch ($link->type) {
                case MANY_TO_MANY:
                    /* @var $link FLEA_Db_ManyToManyLink */
                    $link->init();
                    $sql = "DELETE FROM {$link->joinTable}";
                    break;
                case HAS_ONE:
                case HAS_MANY:
                    $link->init();
                    $sql = "DELETE FROM {$link->assocTDG->fullTableName}";
                    break;
                default:
                    continue;
                }
                if ($this->dbo->execute($sql) == false) {
                    $this->dbo->completeTrans(false);
                    return false;
                }
            }
        }

        $sql = "DELETE FROM {$this->qtableName}";
        if ($this->dbo->execute($sql) == false) {
            $this->dbo->completeTrans(false);
            return false;
        }

        // 提交事务
        $this->dbo->completeTrans();

        return true;
    }

    /**
     * 启用所有关联
     */
    function enableLinks()
    {
        $this->autoLink = true;
        $keys = array_keys($this->links);
        foreach ($keys as $key) {
            $this->links[$key]->enabled = true;
        }
    }

    /**
     * 启用指定关联
     *
     * @param string $linkName
     *
     * @return FLEA_Db_TableLink
     *
     */
    function enableLink($linkName)
    {
        $link =& $this->getLink($linkName);
        if ($link) { $link->enabled = true; }
        $this->autoLink = true;
        return $link;
    }

    /**
     * 禁用所有关联
     */
    function disableLinks()
    {
        $this->autoLink = false;
        $keys = array_keys($this->links);
        foreach ($keys as $key) {
            $this->links[$key]->enabled = false;
        }
    }

    /**
     * 禁用指定关联
     *
     * @param string $linkName
     *
     * @return FLEA_Db_TableLink
     */
    function disableLink($linkName)
    {
        $link =& $this->getLink($linkName);
        if ($link) { $link->enabled = false; }
        return $link;
    }

    /**
     * 清除所有关联
     */
    function clearLinks()
    {
        $this->links = array();
    }

    /**
     * 根据类定义的 $hasOne、$hasMany、$belongsTo 和 $manyToMany 成员变量重建所有关联
     */
    function relink()
    {
        $this->clearLinks();
        $this->createLink($this->hasOne,     HAS_ONE);
        $this->createLink($this->belongsTo,  BELONGS_TO);
        $this->createLink($this->hasMany,    HAS_MANY);
        $this->createLink($this->manyToMany, MANY_TO_MANY);
    }

    /**
     * 获取指定名字的关联
     *
     * @param string $linkName
     *
     * @return FLEA_Db_TableLink
     */
    function & getLink($linkName)
    {
        $linkName = strtoupper($linkName);
        if (isset($this->links[$linkName])) {
            return $this->links[$linkName];
        }

        FLEA::loadClass('FLEA_Db_Exception_MissingLink');
        __THROW(new FLEA_Db_Exception_MissingLink($linkName));
        $ret = false;
        return $ret;
    }

    /**
     * 检查指定名字的关联是否存在
     *
     * @param string $name
     *
     * @return boolean
     */
    function & existsLink($name)
    {
        $name = strtoupper($name);
        return isset($this->links[$name]);
    }

    /**
     * 建立关联，并且返回新建立的关联对象
     *
     * @param array $defines
     * @param enum $type
     *
     * @return FLEA_Db_TableLink
     */
    function createLink($defines, $type)
    {
        if (!is_array($defines)) { return; }
        if (!is_array(reset($defines))) {
            $defines = array($defines);
        }

        // 创建关联对象
        foreach ($defines as $define) {
            if (!is_array($define)) { continue; }
            // 构造连接对象实例
            $link =& FLEA_Db_TableLink::createLink($define, $type, $this);
            $this->links[strtoupper($link->name)] =& $link;
        }
    }

    /**
     * 删除指定的关联
     *
     * @param string $linkName
     */
    function removeLink($linkName)
    {
        $linkName = strtoupper($linkName);
        if (isset($this->links[$linkName])) {
            unset($this->links[$linkName]);
        }
    }

    /**
     * 对数据进行验证
     *
     * 派生类可以覆盖此方法，以便进行附加的验证。
     *
     * @param array $row
     * @param boolean $skipEmpty
     *
     * @return boolean
     */
    function checkRowData(& $row, $skipEmpty = false) {
        if (is_null($this->verifier)) { return false; }
        $this->lastValidationResult =
                $this->verifier->checkAll($row, $this->meta, $skipEmpty);
        return empty($this->lastValidationResult);
    }

    /**
     * 返回最后一次数据验证的结果
     *
     * @return mixed
     */
    function getLastValidation() {
        return $this->lastValidationResult;
    }

    /**
     * 返回当前数据表的下一个插入 ID
     *
     * @return mixed
     */
    function newInsertId() {
        return $this->dbo->nextId($this->fullTableName . '_seq');
    }

    /**
     * 直接执行一个 sql 语句
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    function execute($sql, $inputarr = false)
    {
        return $this->dbo->execute($sql, $inputarr);
    }

    /**
     * 返回转义后的数据
     *
     * @param mixed $value
     *
     * @return string
     */
    function qstr($value)
    {
        return $this->dbo->qstr($value);
    }

    /**
     * 获得一个字段名的完全限定名
     *
     * @param string $fieldName
     * @param string $tableName
     *
     * @return string
     */
    function qfield($fieldName, $tableName = null)
    {
        if ($tableName == null) {
            $tableName = $this->fullTableName;
        }
        return $this->dbo->qfield($fieldName, $tableName);
    }

    /**
     * 获得多个字段名的完全限定名
     *
     * @param string|array $fieldsName
     * @param string $tableName
     *
     * @return string
     */
    function qfields($fieldsName, $tableName = null)
    {
        if ($tableName == null) {
            $tableName = $this->fullTableName;
        }
        return $this->dbo->qfields($fieldsName, $tableName);
    }

    /**
     * 分析查询条件，返回 WHERE 子句
     *
     * @param array $conditions
     * @param boolean $queryLinks
     *
     * @return string
     */
    function getWhere($conditions, $queryLinks = true) {
        // 处理查询条件
        $where = FLEA_Db_SqlHelper::parseConditions($conditions, $this);
        $sqljoin = '';
        $distinct = '';

        do {
            if (!is_array($where)) {
                $whereby = $where != '' ? "\nWHERE {$where}" : '';
                break;
            }

            $arr = $where;
            list($where, $linksWhere) = $arr;
            unset($arr);

            if (!$this->autoLink || !$queryLinks) {
                $whereby = $where != '' ? "\nWHERE {$where}" : '';
                break;
            }

            foreach ($linksWhere as $linkid => $lws) {
                if (!isset($this->links[$linkid]) || !$this->links[$linkid]->enabled) {
                    continue;
                }

                $link =& $this->links[$linkid];
                /* @var $link FLEA_Db_TableLink */
                if (!$link->init) { $link->init(); }
                $distinct = 'DISTINCT ';

                switch ($link->type) {
                case HAS_ONE:
                case HAS_MANY:
                    /* @var $link FLEA_Db_HasOneLink */
                    $sqljoin .= <<<EOT
LEFT JOIN {$link->assocTDG->qtableName}
ON {$link->mainTDG->qpk} = {$link->qforeignKey}
EOT;
                    break;
                case BELONGS_TO:
                    /* @var $link FLEA_Db_BelongsToLink */
                    $sqljoin .= <<<EOT
LEFT JOIN {$link->assocTDG->qtableName}
ON {$link->assocTDG->qpk} = {$link->qforeignKey}
EOT;
                    break;
                case MANY_TO_MANY:
                    /* @var $link FLEA_Db_ManyToManyLink */
                    $sqljoin .= <<<EOT
INNER JOIN {$link->qjoinTable}
ON {$link->qforeignKey} = {$this->qpk}
INNER JOIN {$link->assocTDG->qtableName}
ON {$link->assocTDG->qpk} = {$link->qassocForeignKey}
EOT;
                    break;
                }

                $whereby = $where != '' ? "WHERE {$where} AND " : 'WHERE';
                foreach ($lws as $lw) {
                    list($field, $value, $op, $expr, $isCommand) = $lw;
                    if (!$isCommand) {
                        $field = $link->assocTDG->qfield($field);
                        $value = $this->dbo->qstr($value);
                        $whereby .= " {$field} {$op} {$value} {$expr}";
                    } else {
                        $whereby .= " {$value} {$expr}";
                    }
                }
                $whereby = substr($whereby, 0, - (strlen($expr) + 1));

                unset($link);
            }

            $whereby = "\n{$sqljoin}\n{$whereby}";
        } while (false);

        if ($queryLinks) {
            return array($whereby, $distinct);
        } else {
            return $whereby;
        }
    }

    /**
     * 强制刷新缓存的数据表 meta 信息
     */
    function flushMeta()
    {
        $this->_prepareMeta(true);
    }

    /**
     * 开启一个事务
     */
    function beginTrans() {
        if ($this->autoStartTrans) {
            return $this->dbo->beginTrans();
        } else {
            return null;
        }
    }

    /**
     * 更新记录的 updated 等字段
     *
     * @param array $row
     */
    function _setUpdatedTimeFields(& $row) {
        foreach ($this->updatedTimeFields as $af) {
            $af = strtoupper($af);
            if (!isset($this->meta[$af])) { continue; }
            switch ($this->meta[$af]['simpleType']) {
            case 'D': // 日期
            case 'T': // 日期时间
                // 由数据库驱动获取时间格式
                $row[$this->meta[$af]['name']] = $this->dbo->dbTimeStamp(time());
                break;
            case 'I': // Unix 时间戳
                $row[$this->meta[$af]['name']] = time();
                break;
            }
        }
    }

    /**
     * 更新记录的 created 和 updated 等字段
     *
     * @param array $row
     */
    function _setCreatedTimeFields(& $row) {
        $currentTime = time();
        $currentTimeStamp = $this->dbo->dbTimeStamp(time());
        foreach (array_merge($this->createdTimeFields, $this->updatedTimeFields) as $af) {
            $af = strtoupper($af);
            if (!isset($this->meta[$af])) { continue; }
            $afn = $this->meta[$af]['name'];
            if (!empty($row[$afn])) { continue; }

            switch ($this->meta[$af]['simpleType']) {
            case 'D': // 日期
            case 'T': // 日期时间
                // 由数据库驱动获取时间格式
                $row[$afn] = $currentTimeStamp;
                break;
            case 'I': // Unix 时间戳
                $row[$afn] = $currentTime;
                break;
            }
        }
    }

    /**
     * 准备当前数据表的元数据
     *
     * @param boolean $flushCache
     *
     * @return boolean
     */
    function _prepareMeta($flushCache = false) {
        $cached = FLEA::getAppInf('dbMetaCached');
        if ($cached && !$flushCache) {
            $metaID = strtoupper($this->fullTableName);
            $cachedAllMeta = FLEA::getAppInf('QeePHP.Cache.AllMeta');
            if (isset($cachedAllMeta[$metaID])) {
                $this->meta = $cachedAllMeta[$metaID];
                return true;
            }

            $cacheId = $this->dbo->dsn['id'];
            $allMeta = FLEA::getCache($cacheId, FLEA::getAppInf('dbMetaLifetime'));
            if (is_array($allMeta)) {
                FLEA::setAppInf('QeePHP.Cache.AllMeta', $allMeta);
            }
            if (isset($allMeta[$metaID])) {
                $this->meta = $allMeta[$metaID];
                return true;
            }
        }

        $this->meta = $this->dbo->metaColumns($this->fullTableName);
        if ($this->meta == false) {
            FLEA::loadClass('FLEA_Db_Exception_MetaColumnsFailed');
            __THROW(new FLEA_Db_Exception_MetaColumnsFailed($this->tableName));
            return false;
        }
        if ($cached) {
            $allMeta[$metaID] = $this->meta;
            return FLEA::writeCache($cacheId, $allMeta);
        } else {
            return true;
        }
    }

    /**
     * 调用 create() 方法后立即引发 _beforeCreate 事件
     *
     * 如果要阻止 create() 创建记录，该方法应该返回 false，否则返回 true。
     *
     * @param array $row
     *
     * @return boolean
     */
    function _beforeCreate(& $row)
    {
        return true;
    }

    /**
     * 调用 create() 方法后，表数据入口对数据进行处理后存入数据库前引发 _beforeCreateDb 事件
     *
     * 如果要阻止 create() 创建记录，该方法应该返回 false，否则返回 true。
     *
     * @param array $row
     *
     * @return boolean
     */
    function _beforeCreateDb(& $row)
    {
        return true;
    }

    /**
     * 调用 create() 方法并且成功将数据存入数据库后引发 _afterCreateDb 事件
     *
     * @param array $row
     */
    function _afterCreateDb(& $row)
    {
    }


    /**
     * 调用 update() 方法后立即引发 _beforeUpdate 事件
     *
     * 如果要阻止 update() 更新记录，该方法应该返回 false，否则返回 true。
     *
     * @param array $row
     *
     * @return boolean
     */
    function _beforeUpdate(& $row)
    {
        return true;
    }

    /**
     * 调用 update() 方法后，表数据入口对数据进行处理后存入数据库前引发 _beforeUpdateDb 事件
     *
     * 如果要阻止 update() 更新记录，该方法应该返回 false，否则返回 true。
     *
     * @param array $row
     *
     * @return boolean
     */
    function _beforeUpdateDb(& $row)
    {
        return true;
    }

    /**
     * 调用 update() 方法并且成功将数据更新到数据库后引发 _afterUpdateDb 事件
     *
     * @param array $row
     */
    function _afterUpdateDb(& $row)
    {
    }

    /**
     * 调用 remove() 方法后立即引发 _beforeRemove 事件
     *
     * 如果要阻止 remove() 删除记录，该方法应该返回 false，否则返回 true。
     *
     * @param array $row
     *
     * @return boolean
     */
    function _beforeRemove(& $row)
    {
        return true;
    }

    /**
     * 调用 remove() 方法并且成功删除记录后引发 _afterRemoveDb 事件
     *
     * @param array $row
     */
    function _afterRemoveDb($row)
    {
    }

    /**
     * 调用 remove() 或 removeByPkv() 方法后立即引发 _beforeRemoveDbByPkv 事件
     *
     * 调用 remove() 方法时，_beforeRemoveDbByPkv 事件出现在 _beforeRemove 事件之后。
     *
     * 如果要阻止 remove() 或 removeByPkv() 删除记录，
     * 该方法应该返回 false，否则返回 true。
     *
     * @param mixed $pkv
     *
     * @return boolean
     */
    function _beforeRemoveDbByPkv($pkv)
    {
        return true;
    }

    /**
     * 调用 remove() 或 removeByPkv() 方法并且成功删除记录后引发 _afterRemoveDbByPkv 事件
     *
     * @param array $row
     */
    function _afterRemoveDbByPkv($pkv)
    {
    }
}


/**
 * FLEA_Db_SqlHelper 类提供了各种生成 SQL 语句的辅助方法
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_SqlHelper
{
    /**
     * 分析查询条件
     *
     * @param mixed $conditions
     * @param FLEA_Db_TableDataGateway $table
     *
     * @return array
     */
    function parseConditions($conditions, & $table)
    {
        // 对于 NULL，直接返回 NULL
        if (is_null($conditions)) { return null; }

        // 如果是数字，则假定为主键字段值
        if (is_numeric($conditions)) {
            return "{$table->qpk} = {$conditions}";
        }

        // 如果是字符串，则假定为自定义条件
        if (is_string($conditions)) {
            return $conditions;
        }

        // 如果不是数组，说明提供的查询条件有误
        if (!is_array($conditions)) {
            return null;
        }

        $where = '';
        $linksWhere = array();
        $expr = '';

        foreach ($conditions as $offset => $cond) {
            $expr = 'AND';
            /**
             * 不过何种条件形式，一律转换为 (字段名, 值, 操作, 连接运算符, 值是否是SQL命令) 的形式
             */
            if (is_string($offset)) {
                if (!is_array($cond)) {
                    // 字段名 => 值
                    $cond = array($offset, $cond);
                } else {
                    // 字段名 => 数组
                    array_unshift($cond, $offset);
                }
            } elseif (is_int($offset)) {
                if (!is_array($cond)) {
                    // 值
                    $cond = array('', $cond, '', $expr, true);
                }
            } else {
                continue;
            }

            if (!isset($cond[0])) { continue; }
            if (!isset($cond[2])) { $cond[2] = '='; }
            if (!isset($cond[3])) { $cond[3] = $expr; }
            if (!isset($cond[4])) { $cond[4] = false; }

            list($field, $value, $op, $expr, $isCommand) = $cond;

            $str = '';
            do {
                if (strpos($field, '.') !== false) {
                    list($scheme, $field) = explode('.', $field);
                    $linkname = strtoupper($scheme);
                    if (isset($table->links[$linkname])) {
                        $linksWhere[$linkname][] = array($field, $value, $op, $expr, $isCommand);
                        break;
                    } else {
                        $field = "{$scheme}.{$field}";
                    }
                }

                if (!$isCommand) {
                    $field = $table->qfield($field);
                    $value = $table->dbo->qstr($value);
                    $str = "{$field} {$op} {$value} {$expr} ";
                } else {
                    $str = "{$value} {$expr} ";
                }
            } while (false);

            $where .= $str;
        }

        $where = substr($where, 0, - (strlen($expr) + 2));
        if (empty($linksWhere)) {
            return $where;
        } else {
            return array($where, $linksWhere);
        }
    }

    /**
     * 格式化输出 SQL 日志
     *
     * @param array $log
     */
    function dumpLog(& $log)
    {
        foreach ($log as $ix => $sql) {
            dump($sql, 'SQL ' . ($ix + 1));
        }
    }
}

