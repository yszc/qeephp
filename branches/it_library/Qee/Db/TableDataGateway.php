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
 * 定义 Qee_Db_TableDataGateway 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Database
 * @version $Id$
 */

// {{{ includes
require_once 'Qee/Db/TableLink.php';
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
 * Qee_Db_TableDataGateway 类（表数据入口）封装了数据表的 CRUD 操作
 *
 * 开发者应该从 Qee_Db_TableDataGateway 派生自己的类，
 * 并通过添加方法来封装针对该数据表的更复杂的数据库操作。
 *
 * 对于每一个表数据入口对象，都必须在类定义中通过 $tableName 和 $primaryKey
 * 来分别指定数据表的名字和主键字段名。
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.3
 */
class Qee_Db_TableDataGateway
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
     * @var Qee_Helper_Verifier
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
     * @var Qee_Db_Driver_Abstract
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
     * 构造 Qee_Db_TableDataGateway 实例
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
     * @return Qee_Db_TableDataGateway
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
            $this->verifier = Qee::getSingleton($provider);
        }
        if ($this->autoValidating && $this->verifier == null) {
            $provider = Qee::getAppInf('helper.verifier');
            $this->verifier = Qee::getSingleton($provider);
        }

        if (!isset($params['skipConnect']) || $params['skipConnect'] == false) {
            if (!isset($params['dbo'])) {
                $dsn = isset($params['dbDSN']) ? $params['dbDSN'] : 0;
                $dbo = Qee::getDBO($dsn);
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
     * @param Qee_Db_Driver_Abstract $dbo
     */
    public function setDBO(Qee_Db_Driver_Abstract $dbo)
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
    }

    /**
     * 返回该表数据入口对象使用的数据访问对象
     *
     * @return Qee_Db_Driver_Abstract
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
            $handle = $this->dbo->selectLimit($sql, $length, $offset);
        } else {
            $handle = $this->dbo->execute($sql);
        }

        if ($enableLinks) {
            /**
             * 查询时同时将主键值单独提取出来，
             * 并且准备一个以主键值为键名的二维数组用于关联数据的装配
             */
            $pkvs = array();
            $assocRowset = null;
            $rowset = $handle->getAllWithFieldRefs($this->Pka, $pkvs, $assocRowset);
        } else {
            $rowset = $handle->getAll();
        }
        unset($handle);

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
            /* @var $link Qee_Db_TableLink */
            if (!$link->enabled || !$link->linkRead) { continue; }
            array_walk($assocRowset, $callback, $link->mappingName);
            $sql = $link->getFindSQL($pkvs);
            $this->dbo->assemble($sql, $assocRowset, $link->mappingName, $link->oneToOne, $this->pka, $link->limit);
        }

        return $rowset;
    }

    /**
     * 直接使用 sql 语句获取记录（该方法不会处理关联数据表）
     *
     * @param string $sql
     * @param mixed $limit
     * @param array $inputarr
     *
     * @return array
     */
    public function & findBySql($sql, $limit = null, array $inputarr = null)
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

        $handle = $this->dbo->selectLimit($sql, $length, $offset, $inputarr);
        $rowset = $handle->getAll();
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
            $fields = $this->dbo->qfields($fields, $this->fullTableName, $this->schema);
        }
        $sql = "SELECT {$distinct}COUNT({$fields})\nFROM {$this->qtableName}{$whereby}";
        return (int)$this->dbo->getOne($sql);
    }

    /**
     * 保存数据到数据库
     *
     * 如果数据不包含主键值或 $forceCreate 为 true，则会调用 create()，否则调用 update()。
     *
     * @param array $row
     * @param boolean $saveLinks
     * @param boolean $forceCreate
     */
    public function save(array & $row, $saveLinks = true, $forceCreate = false)
    {
        if (!isset($row[$this->primaryKey]) || empty($row[$this->primaryKey]) || $forceCreate) {
            $this->create($row, $saveLinks);
        } else {
            $this->update($row, $saveLinks);
        }
    }

    /**
     * 替换一条现有记录或插入新记录，返回记录的主键值
     *
     * @param array $row
     *
     * @return mixed
     */
    public function replace(array & $row) {
        $tran = ($this->autoStartTrans) ? $this->dbo->beginTrans() : null;

        $this->filterRowData($row);
        $this->setCreatedTimeFields($row);
        $fields = implode(',', $this->dbo->qfields(array_keys($row), $this->fullTableName, $this->schema));
        $params = $this->prepareSqlParameters($row);
        $sql = "REPLACE INTO {$this->qtableName} ({$fields}) VALUES ({$params})";
        $this->dbo->execute($sql, $row);

        if (isset($row[$this->primaryKey]) && !empty($row[$this->primaryKey])) {
            return $row[$this->primaryKey];
        }

        return $this->dbo->insertId();
    }

    /**
     * 更新一条现有的记录
     *
     * @param array $row
     * @param boolean $saveLinks
     */
    public function update(array & $row, $saveLinks = true)
    {
        // 检查是否提供了主键值
        if (!isset($row[$this->primaryKey])) {
            require_once 'Qee/Db/Exception/MissingPrimaryKey.php';
            throw new Qee_Db_Exception_MissingPrimaryKey($this->primaryKey);
        }

        // 自动填写记录的最后更新时间字段
        $this->filterRowData($row);
        $this->setUpdatedTimeFields($row);

        // 如果提供了验证器，则进行数据验证
        if ($this->autoValidating && $this->verifier != null) {
            if (!$this->checkRowData($row, true)) {
                // 验证失败抛出异常
                require_once 'Qee/Exception/ValidationFailed.php';
                throw new Qee_Exception_ValidationFailed($this->getLastValidation(), $row);
            }
        }

        // 开始事务
        $tran = ($this->autoStartTrans) ? $this->dbo->beginTrans() : null;

        // 生成 SQL 语句，并执行更新操作
        $pkv = $row[$this->primaryKey];
        unset($row[$this->primaryKey]);
        if (!empty($row)) {
            $params = $this->prepareSqlParametersPair($row);
            $wparams = $this->prepareSqlParameters(array($this->primaryKey => $pkv));
            $row[$this->primaryKey] = $pkv;
            $sql = "UPDATE {$this->qtableName} SET {$params} WHERE {$this->qpk} = {$wparams}";
            $this->dbo->execute($sql, $row);
        }

        // 处理对关联数据的更新
        if ($this->autoLink && $saveLinks) {
            foreach ($this->links as $link) {
                /* @var $link Qee_Db_TableLink */
                // 跳过不需要处理的关联
                if (!$link->enabled || !$link->linkUpdate || !isset($row[$link->mappingName]) || !is_array($row[$link->mappingName])) {
                    continue;
                }
                $link->saveAssocData($row[$link->mappingName], $pkv);
            }
        }

        return true;
    }

    /**
     * 更新符合条件的记录，成功返回更新的记录总数
     *
     * @param mixed $conditions
     * @param array $$row
     *
     * @return int
     */
    public function updateByConditions($conditions, array & $data)
    {
        $whereby = $this->getWhere($conditions, false);
        $this->filterRowData($data);
        $this->setUpdatedTimeFields($data);
        $params = $this->prepareSqlParametersPair($data);
        $sql = "UPDATE {$this->qtableName} SET {$params} {$whereby}";
        $this->dbo->execute($sql, $data);
        return $this->dbo->affectedRows();
    }

    /**
     * 更新记录的指定字段，返回更新的记录总数
     *
     * @param mixed $conditions
     * @param string $field
     * @param mixed $value
     *
     * @return int
     */
    public function updateField($conditions, $field, $value)
    {
        $pair = array($field => $value);
        return $this->updateByConditions($conditions, $pair);
    }

    /**
     * 增加符合条件的记录的指定字段的值，返回更新的记录总数
     *
     * @param mixed $conditions
     * @param string $field
     * @param int $incr
     *
     * @return int
     */
    public function incrField($conditions, $field, $incr = 1)
    {
        $field = $this->dbo->qfield($field, $this->fullTableName, $this->schema);
        $incr = (int)$incr;
        $whereby = $this->getWhere($conditions, false);
        $sql = "UPDATE {$this->qtableName} SET {$field} = {$field} + {$incr} {$whereby}";
        $this->dbo->execute($sql, $data);
        return $this->dbo->affectedRows();
    }

    /**
     * 减小符合条件的记录的指定字段的值，返回更新的记录总数
     *
     * @param mixed $conditions
     * @param string $field
     * @param int $decr
     *
     * @return int
     */
    public function decrField($conditions, $field, $decr = 1)
    {
        $field = $this->dbo->qfield($field, $this->fullTableName, $this->schema);
        $decr = (int)$decr;
        $whereby = $this->getWhere($conditions, false);
        $sql = "UPDATE {$this->qtableName} SET {$field} = {$field} - {$decr} {$whereby}";
        $this->dbo->execute($sql, $data);
        return $this->dbo->affectedRows();
    }

    /**
     * 插入一条新记录及关联数据，并返回新记录的主键值
     *
     * 如果数据库不能返回新记录的主键值，则无法自动插入关联表数据。
     *
     * @param array $row
     * @param boolean $saveLinks
     *
     * @return mixed
     */
    public function create(array & $row, $saveLinks = true)
    {
        // 自动设置日期字段
        $this->filterRowData($row);
        $this->setCreatedTimeFields($row);

        // 处理主键
        $mpk = strtoupper($this->primaryKey);
        $insertId = null;
        $unsetpk = true;
        if (isset($this->meta[$mpk]['autoIncrement']) && $this->meta[$mpk]['autoIncrement']) {
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
            if (!isset($row[$this->primaryKey]) || empty($row[$this->primaryKey])) {
                // 没有提供主键字段值时，则获取一个新的主键字段值
                $insertId = $this->newInsertId();
                $row[$this->primaryKey] = $insertId;
            } else {
                // 使用开发者提交的主键字段值
                $insertId = $row[$this->primaryKey];
                $unsetpk = false;
            }
        }

        // 如果提供了验证器，则进行数据验证
        if ($this->autoValidating && $this->verifier != null) {
            if (!$this->checkRowData($row)) {
                // 验证失败抛出异常
                require_once 'Qee/Exception/ValidationFailed.php';
                throw new Qee_Exception_ValidationFailed($this->getLastValidation(), $row);
            }
        }

        // 生成 SQL 语句
        $fields = implode(',', $this->dbo->qfields(array_keys($row), $this->fullTableName, $this->schema));
        $params = $this->prepareSqlParameters($row);
        $sql = "INSERT INTO {$this->qtableName} ({$fields}) VALUES ({$params})";

        // 开始事务，并插入数据
        $tran = ($this->autoStartTrans) ? $this->dbo->beginTrans() : null;
        $this->dbo->execute($sql, $row);

        /**
         * 如果提交的数据中没有主键字段值，则尝试获取新插入记录的主键值，
         * 如果数据库无法返回新插入记录的主键值，则直接返回 null，忽略关联表的数据
         */
        if ($insertId == null) {
            $insertId = $this->dbo->insertId();
            if ($insertId === null) { return null; }
        }

        // 处理关联数据表
        if ($this->autoLink && $saveLinks) {
            foreach ($this->links as $link) {
                /* @var $link Qee_Db_TableLink */
                if (!$link->enabled || !$link->linkCreate || !isset($row[$link->mappingName]) || !is_array($row[$link->mappingName])) {
                    // 跳过没有关联数据的关联和不需要处理的关联
                    continue;
                }

                $link->saveAssocData($row[$link->mappingName], $insertId);
            }
        }
        if ($unsetpk) { unset($row[$this->primaryKey]); }

        return $insertId;
    }

    /**
     * 删除记录
     *
     * @param array $row
     * @param boolean $removeLinks
     */
    public function remove(array & $row, $removeLinks = true)
    {
        if (!isset($row[$this->primaryKey])) {
            require_once 'Qee/Db/Exception/MissingPrimaryKey.php';
            throw new Qee_Db_Exception_MissingPrimaryKey($this->primaryKey);
        }
        $this->removeByPkv($row[$this->primaryKey], $removeLinks);
    }

    /**
     * 根据主键值删除记录
     *
     * @param mixed $pkv
     * @param boolean $removeLinks
     */
    public function removeByPkv($pkv, $removeLinks = true)
    {
        // 开始事务
        $tran = ($this->autoStartTrans) ? $this->dbo->beginTrans() : null;

        /**
         * 首先删除关联表数据，再删除主表数据
         */
        $qpkv = $this->dbo->qstr($pkv);

        // 处理关联数据表
        if ($this->autoLink && $removeLinks) {
            foreach ($this->links as $link) {
                /* @var $link Qee_Db_TableLink */
                if (!$link->enabled) { continue; }
                switch ($link->type) {
                case MANY_TO_MANY:
                    /* @var $link Qee_Db_ManyToManyLink */
                    $link->deleteMiddleTableDataByMainForeignKey($qpkv);
                    break;
                case HAS_ONE:
                case HAS_MANY:
                    /**
                     * 对于 HAS_ONE 和 HAS_MANY 关联，分为两种情况处理
                     *
                     * 当 $link->linkRemove 为 true 时，直接删除关联表中的关联数据
                     * 否则更新关联数据的外键值为 $link->linkRemoveFillValue
                     */
                    /* @var $link Qee_Db_HasOneLink */
                    $link->deleteByForeignKey($qpkv);
                    break;
                }
            }
        }

        // 删除主表数据
        $sql = "DELETE FROM {$this->qtableName}\nWHERE {$this->qpk} = {$qpkv}";
        $this->dbo->execute($sql);
    }

    /**
     * 删除符合条件的记录，返回被删除的记录总数
     *
     * @param mixed $conditions
     *
     * @return int
     */
    public function removeByConditions($conditions)
    {
        $rowset = $this->findAll($conditions, null, null, $this->primaryKey, false);
        // 开始事务
        $tran = ($this->autoStartTrans) ? $this->dbo->beginTrans() : null;
        foreach ($rowset as $row) {
            $this->removeByPkv($row[$this->primaryKey]);
            $count++;
        }
        return $count;
    }

    /**
     * 删除所有记录，返回被删除的记录总数
     *
     * @param boolean $removelinks
     *
     * @return int
     */
    public function removeAll($removelinks = false)
    {
        // 开始事务
        $tran = ($this->autoStartTrans) ? $this->dbo->beginTrans() : null;

        if ($this->autoLink && $removelinks) {
            foreach ($this->links as $link) {
                /* @var $link Qee_Db_TableLink */
                switch ($link->type) {
                case MANY_TO_MANY:
                    /* @var $link Qee_Db_ManyToManyLink */
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
                $this->dbo->execute($sql);
            }
        }

        $this->dbo->execute("DELETE FROM {$this->qtableName}");
        return $this->dbo->affectedRows();
    }

    /**
     * 启用所有关联
     */
    public function enableLinks()
    {
        $this->autoLink = true;
        foreach ($this->links as $link) {
            $link->enabled = true;
        }
    }

    /**
     * 启用指定关联，并返回该关联
     *
     * @param string $linkName
     *
     * @return Qee_Db_TableLink
     */
    public function enableLink($linkName)
    {
        $link = $this->getLink($linkName);
        $link->enabled = true;
        $this->autoLink = true;
        return $link;
    }

    /**
     * 禁用所有关联
     */
    public function disableLinks()
    {
        $this->autoLink = false;
        foreach ($this->links as $link) {
            $link->enabled = false;
        }
    }

    /**
     * 禁用指定关联，并返回该关联
     *
     * @param string $linkName
     *
     * @return Qee_Db_TableLink
     */
    public function disableLink($linkName)
    {
        $link = $this->getLink($linkName);
        $link->enabled = true;
        return $link;
    }

    /**
     * 清除所有关联
     */
    public function clearLinks()
    {
        $keys = array_keys($this->links);
        foreach ($keys as $key) {
            unset($this->links[$key]);
        }
        $this->links = array();
    }

    /**
     * 根据类定义的 $hasOne、$hasMany、$belongsTo 和 $manyToMany 成员变量重建所有关联
     */
    public function relink()
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
     * @return Qee_Db_TableLink
     */
    public function getLink($linkName)
    {
        $linkName = strtoupper($linkName);
        if (!isset($this->links[$linkName])) {
            require_once 'Qee/Db/Exception/MissingLink.php';
            throw new Qee_Db_Exception_MissingLink($linkName);
        }
        return $this->links[$linkName];
    }

    /**
     * 检查指定名字的关联是否存在
     *
     * @param string $name
     *
     * @return boolean
     */
    public function existsLink($linkName)
    {
        return isset($this->links[strtoupper($linkName)]);
    }

    /**
     * 建立关联
     *
     * @param array $defines
     * @param enum $type
     */
    public function createLink(array $defines, $type)
    {
        if (!is_array(reset($defines))) {
            $defines = array($defines);
        }
        foreach ($defines as $define) {
            $link = Qee_Db_TableLink::createLink($define, $type, $this);
            $this->links[strtoupper($link->name)] = $link;
        }
    }

    /**
     * 删除指定的关联
     *
     * @param string $linkName
     */
    public function removeLink($linkName)
    {
        $linkName = strtoupper($linkName);
        if (!isset($this->links[$linkName])) {
            require_once 'Qee/Db/Exception/MissingLink.php';
            throw new Qee_Db_Exception_MissingLink($linkName);
        }
        unset($this->links[$linkName]);
    }

    /**
     * 对数据进行验证
     *
     * 派生类可以覆盖此方法，以便进行附加的验证。
     *
     * @param array $row
     * @param boolean $skipNotExists
     *
     * @return boolean
     */
    public function checkRowData(array & $row, $skipNotExists = false)
    {
        if (is_null($this->verifier)) { return false; }
        $this->lastValidationResult = $this->verifier->checkAll($row, $this->meta, $skipNotExists);
        return empty($this->lastValidationResult);
    }

    /**
     * 返回最后一次数据验证的结果
     *
     * @return mixed
     */
    public function getLastValidation()
    {
        return $this->lastValidationResult;
    }

    /**
     * 返回当前数据表的下一个插入ID
     *
     * @return mixed
     */
    public function newInsertId()
    {
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
    public function execute($sql, array $inputarr = null)
    {
        return $this->dbo->execute($sql, $inputarr);
    }

    /**
     * 分析查询条件，返回 WHERE 子句
     *
     * @param mixed $conditions
     * @param boolean $queryLinks
     *
     * @return string|array
     */
    public function getWhere($conditions, $queryLinks = true) {
        // 处理查询条件
        $where = self::parseConditions($conditions, $this);
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
                /* @var $link Qee_Db_TableLink */
                if (!$link->init) { $link->init(); }
                $distinct = 'DISTINCT ';

                switch ($link->type) {
                case HAS_ONE:
                case HAS_MANY:
                    /* @var $link Qee_Db_HasOneLink */
                    $sqljoin .= <<<EOT
LEFT JOIN {$link->assocTDG->qtableName}
ON {$link->mainTDG->qpk} = {$link->qforeignKey}
EOT;
                    break;
                case BELONGS_TO:
                    /* @var $link Qee_Db_BelongsToLink */
                    $sqljoin .= <<<EOT
LEFT JOIN {$link->assocTDG->qtableName}
ON {$link->assocTDG->qpk} = {$link->qforeignKey}
EOT;
                    break;
                case MANY_TO_MANY:
                    /* @var $link Qee_Db_ManyToManyLink */
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
                break;
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
     * 过滤掉记录中多余的字段，只保留数据表中定义了的字段
     *
     * @param array $row
     */
    public function filterRowData(& $row)
    {
        $fields = array_map('strtoupper', array_keys($row));
        foreach ($fields as $field) {
            if (!isset($this->meta[$field])) {
                unset($row[$field]);
            }
        }
    }

    /**
     * 强制刷新缓存的数据表 meta 信息
     */
    public function flushMeta()
    {
        $this->_prepareMeta(true);
    }

    /**
     * 开启一个事务，成功返回事务对象，否则返回 null
     */
    public function beginTrans()
    {
        return $this->dbo->beginTrans();
    }

    /**
     * 更新记录的 updated 等字段
     *
     * @param array $row
     */
    public function setUpdatedTimeFields(array & $row)
    {
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
    public function setCreatedTimeFields(array & $row)
    {
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
     */
    protected function _prepareMeta($flushCache = false)
    {
        $cached = Qee::getAppInf('dbMetaCached', false);
        if ($cached && !$flushCache) {
            $metaID = strtoupper($this->schema . '.' . $this->fullTableName);
            $cachedAllMeta = Qee::getAppInf('QeePHP.Cache.AllMeta');
            if (isset($cachedAllMeta[$metaID])) {
                $this->meta = $cachedAllMeta[$metaID];
                return;
            }

            $cacheId = $this->dbo->dsn['id'];
            $allMeta = Qee::getCache($cacheId, Qee::getAppInf('dbMetaLifetime'));
            if (is_array($allMeta)) {
                Qee::setAppInf('QeePHP.Cache.AllMeta', $allMeta);
            }
            if (isset($allMeta[$metaID])) {
                $this->meta = $allMeta[$metaID];
                return;
            }
        }

        $this->meta = $this->dbo->metaColumns($this->fullTableName);
        if ($cached) {
            $allMeta[$metaID] = $this->meta;
            Qee::writeCache($cacheId, $allMeta);
        }
    }

    /**
     * 准备查询参数字符串
     *
     * @param array $inputarr
     * @param enum $mode
     *
     * @return string
     */
    public function prepareSqlParameters(array & $inputarr, $mode = null)
    {
        if ($mode === null) {
            $mode = $this->dbo->paramStyle;
        }

        switch ($mode) {
        case Qee_Db_Driver_Abstract::PARAM_QM:
            // 问号作为参数占位符
            $params = str_repeat('?,', count($inputarr) - 1) . '?';
            break;
        case Qee_Db_Driver_Abstract::PARAM_DL_SEQUENCE:
            // $符号开始的序列
            $params = '$' . implode(',$', range(1, count($inputarr)));
            break;
        default:
            $prefix = ($mode == Qee_Db_Driver_Abstract::PARAM_AT_NAMED) ? '@' : ':';
            $params = $prefix . implode(',' . $prefix, array_keys($inputarr));
        }
        return $params;
    }

    /**
     * 准备成对的查询参数字符串
     *
     * @param array $inputarr
     * @param enum $mode
     *
     * @return string
     */
    public function prepareSqlParametersPair(array & $inputarr, $mode)
    {
        $keys = array_keys($inputarr);
        $params = array();
        switch ($mode) {
        case Qee_Db_Driver_Abstract::PARAM_QM:
            // 问号作为参数占位符
            foreach ($keys as $field) { $params[] = "{$field} = ?"; }
            break;
        case Qee_Db_Driver_Abstract::PARAM_DL_SEQUENCE:
            // $符号开始的序列
            foreach ($keys as $offset => $field) { $params[] = "{$field} = \$" . ($offset + 1); }
            break;
        default:
            $prefix = ($mode == Qee_Db_Driver_Abstract::PARAM_AT_NAMED) ? '@' : ':';
            foreach ($keys as $field) { $params[] = "{$field} = {$prefix}{$field}"; }
        }
        return implode(',', $params);
    }

    /**
     * 分析查询条件
     *
     * @param mixed $conditions
     * @param Qee_Db_TableDataGateway $table
     *
     * @return array
     */
    static public function parseConditions($conditions, Qee_Db_TableDataGateway $table = null)
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

        // 如果数组的所有键名都是数字，且第一个键名是整数 0，则假定为 IN 查询
        $keys = array_filter(array_keys($conditions), 'is_int');
        if (count($keys) == count($conditions) && $keys[0] === 0) {
            return ' IN (' . implode(',', $conditions). ')';
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
}
