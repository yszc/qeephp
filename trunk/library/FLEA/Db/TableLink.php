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
 * 定义 FLEA_Db_TableLink 类及其继承类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id: TableLink.php 887 2007-07-05 10:05:15Z dualface $
 */

/**
 * FLEA_Db_TableLink 封装数据表之间的关联关系
 *
 * FLEA_Db_TableLink 是一个完全供 QeePHP 内部使用的类，
 * 开发者不应该直接构造 FLEA_Db_TableLink 对象。
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.2
 */
abstract class FLEA_Db_TableLink
{
    /**
     * 该连接的名字，用于检索指定的连接
     *
     * 同一个数据表的多个关联不能使用相同的名字。如果定义关联时没有指定名字，
     * 则以关联对象的 $mappingName 属性作为这个关联的名字。
     *
     * @var string
     */
    public $name;

    /**
     * 该关联所使用的表数据入口对象名
     *
     * @var string
     */
    public $tableClass;

    /**
     * 外键字段名
     *
     * @var string
     */
    public $foreignKey;

    /**
     * 关联数据表结果映射到主表结果中的字段名
     *
     * @var string
     */
    public $mappingName;

    /**
     * 指示连接两个数据集的行时，是一对一连接还是一对多连接
     *
     * @var boolean
     */
    public $oneToOne;

    /**
     * 关联的类型
     *
     * @var enum
     */
    public $type;

    /**
     * 对关联表进行查询时使用的排序参数
     *
     * @var string
     */
    public $sort;

    /**
     * 对关联表进行查询时使用的条件参数
     *
     * @var string
     */
    public $conditions;

    /**
     * 对关联表进行查询时要获取的关联表字段
     *
     * @var string|array
     */
    public $fields = '*';

    /**
     * 对关联表进行查询时限制查出的记录数
     *
     * @var int
     */
    public $limit = null;

    /**
     * 当 enabled 为 false 时，表数据入口的任何操作都不会处理该关联
     *
     * enabled 的优先级高于 linkRead、linkCreate、linkUpdate 和 linkRemove。
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * 指示是否在主表读取记录时也读取该关联对应的关联表的记录
     *
     * @var boolean
     */
    public $linkRead = true;

    /**
     * 指示是否在主表创建记录时也创建该关联对应的关联表的记录
     *
     * @var boolean
     */
    public $linkCreate = true;

    /**
     * 指示是否在主表更新记录时也更新该关联对应的关联表的记录
     *
     * @var boolean
     */
    public $linkUpdate = true;

    /**
     * 指示是否在主表删除记录时也删除该关联对应的关联表的记录
     *
     * @var boolean
     */
    public $linkRemove = true;

    /**
     * 当删除主表记录而不删除关联表记录时，用什么值填充关联表记录的外键字段
     *
     * @var mixed
     */
    public $linkRemoveFillValue = 0;

    /**
     * 指示当保存关联数据时，采用何种方法，默认为 save，可以设置为 create、update 或 replace
     *
     * @var string
     */
    public $saveAssocMethod = 'save';

    /**
     * 主表的表数据入口对象
     *
     * @var FLEA_Db_TableDataGateway
     */
    public $mainTDG;

    /**
     * 关联表的表数据入口对象
     *
     * @var FLEA_Db_TableDataGateway
     */
    public $assocTDG = null;

    /**
     * 必须设置的对象属性
     *
     * @var array
     */
    protected $_req = array(
        'name',             // 关联的名字
        'tableClass',       // 关联的表数据入口对象名
        'mappingName',      // 字段映射名
    );

    /**
     * 可选的参数
     *
     * @var array
     */
    protected $_optional = array(
        'foreignKey',
        'sort',
        'conditions',
        'fields',
        'limit',
        'enabled',
        'linkRead',
        'linkCreate',
        'linkUpdate',
        'linkRemove',
        'linkRemoveFillValue',
        'saveAssocMethod',
    );

    /**
     * 外键字段的完全限定名
     *
     * @var string
     */
    public $qforeignKey;

    /**
     * 数据访问对象
     *
     * @var FLEA_Db_Driver_Prototype
     */
    public $dbo;

    /**
     * 关联表数据入口的对象名
     *
     * @var string
     */
    public $assocTDGObjectId;

    /**
     * 指示关联的表数据入口是否已经初始化
     *
     * @var boolean
     */
    public $init = false;

    /**
     * 构造函数
     *
     * 开发者不应该自行构造 FLEA_Db_TableLink 实例。而是应该通过
     * FLEA_Db_TableLink::createLink() 静态方法来构造实例。
     *
     * @param array $define
     * @param enum $type
     * @param FLEA_Db_TableDataGateway $mainTDG
     *
     * @return FLEA_Db_TableLink
     */
    protected function __construct($define, $type, $mainTDG)
    {
        static $defaultDsnId = null;

        // 检查必须的属性是否都已经提供
        foreach ($this->_req as $key) {
            if (!isset($define[$key]) || $define[$key] == '') {
                require_once 'FLEA/Db/Exception/MissingLinkOption.php';
                throw new FLEA_Db_Exception_MissingLinkOption($key);
            } else {
                $this->{$key} = $define[$key];
            }
        }
        // 设置可选属性
        foreach ($this->_optional as $key) {
            if (isset($define[$key])) {
                $this->{$key} = $define[$key];
            }
        }
        $this->type = $type;
        $this->mainTDG = $mainTDG;
        $this->dbo = $this->mainTDG->dbo;
        $dsnid = $this->dbo->dsn['id'];

        if (is_null($defaultDsnId)) {
            $defaultDSN = FLEA::getAppInf('dbDSN');
            if ($defaultDSN) {
                $defaultDSN = FLEA::parseDSN($defaultDSN);
                $defaultDsnId = $defaultDSN['id'];
            } else {
                $defaultDsnId = -1;
            }
        }
        if ($dsnid == $defaultDsnId) {
            $this->assocTDGObjectId = null;
        } else {
            $this->assocTDGObjectId = "{$this->tableClass}-{$dsnid}";
        }

        $this->init = false;
    }

    /**
     * 创建 FLEA_Db_TableLink 对象实例
     *
     * @param array $define
     * @param enum $type
     * @param FLEA_Db_TableDataGateway $mainTDG
     *
     * @return FLEA_Db_TableLink
     */
    static function createLink($define, $type, $mainTDG)
    {
        static $typeMap = array(
            HAS_ONE         => 'FLEA_Db_HasOneLink',
            BELONGS_TO      => 'FLEA_Db_BelongsToLink',
            HAS_MANY        => 'FLEA_Db_HasManyLink',
            MANY_TO_MANY    => 'FLEA_Db_ManyToManyLink',
        );

        // 检查 $type 参数
        if (!isset($typeMap[$type])) {
            require_once 'FLEA/Db/Exception/InvalidLinkType.php';
            throw new FLEA_Db_Exception_InvalidLinkType($type);
        }

        // tableClass 属性是必须提供的
        if (!isset($define['tableClass'])) {
            require_once 'FLEA/Db/Exception/MissingLinkOption.php';
            throw new FLEA_Db_Exception_MissingLinkOption('tableClass');
        }
        // 如果没有提供 mappingName 属性，则使用 tableClass 作为 mappingName
        if (!isset($define['mappingName'])) {
            $define['mappingName'] = $define['tableClass'];
        }
        // 如果没有提供 name 属性，则使用 mappingName 属性作为 name
        if (!isset($define['name'])) {
            $define['name'] = $define['mappingName'];
        }

        // 如果是 MANY_TO_MANY 连接，则检查是否提供了 joinTable 属性或者 joinTableClass 属性，
        // 以及assocForeignKey 属性
        if ($type == MANY_TO_MANY) {
            if (!isset($define['joinTable']) && !isset($define['joinTableClass'])) {
                require_once 'FLEA/Db/Exception/MissingLinkOption.php';
                throw new FLEA_Db_Exception_MissingLinkOption('joinTable');
            }
        }

        return new $typeMap[$type]($define, $type, $mainTDG);
    }

    /**
     * 生成一个 MANY_TO_MANY 关联需要的中间表名称
     *
     * @param string $table1
     * @param string $table2
     *
     * @return string
     */
    public function getMiddleTableName($table1, $table2)
    {
        if (strcmp($table1, $table2) < 0) {
            return $this->dbo->dsn['dbTablePrefix'] . "{$table1}_{$table2}";
        } else {
            return $this->dbo->dsn['dbTablePrefix'] . "{$table2}_{$table1}";
        }
    }

    /**
     * 创建或更新主表记录时，保存关联的数据
     *
     * @param array $row 要保存的关联数据
     * @param mixed $pkv 主表的主键字段值
     *
     * @return boolean
     */
    public function saveAssocData(& $row, $pkv)
    {
        throw new FLEA_Exception(FLEA_Exception::t('%s::saveAssocData() not implemented.', get_class($this)));
    }

    /**
     * 初始化关联对象
     */
    public function init()
    {
        if ($this->init) { return; }
        if (FLEA::isRegistered($this->assocTDGObjectId)) {
            $this->assocTDG = FLEA::registry($this->assocTDGObjectId);
        } else {
            if ($this->assocTDGObjectId) {
                FLEA::loadClass($this->tableClass);
                $this->assocTDG = new $this->tableClass(array('dbo' => & $this->dbo));
                FLEA::register($this->assocTDG, $this->assocTDGObjectId);
            } else {
                $this->assocTDG = FLEA::getSingleton($this->tableClass);
            }
        }
        $this->init = true;
    }

    /**
     * 返回用于查询关联表数据的 SQL 语句
     *
     * @param string $sql
     * @param string $in
     *
     * @return string
     */
    protected function _getFindSQLBase($sql, $in)
    {
        if ($in) {
            $sql .= "\nWHERE {$this->qforeignKey} {$in}";
        }
        if ($this->conditions) {
            if (is_array($this->conditions)) {
                $conditions = FLEA_Db_SqlHelper::parseConditions($this->conditions, $this->assocTDG);
                if (is_array($conditions)) {
                    $conditions = $conditions[0];
                }
            } else {
                $conditions = $this->conditions;
            }
            if ($conditions) {
                $sql .= " AND {$conditions}";
            }
        }
        if ($this->sort) {
            $sql .= "\nORDER BY {$this->sort}";
        }

        return $sql;
    }

    /**
     * 创建或更新主表记录时，保存关联的数据
     *
     * @param array $row 要保存的关联数据
     *
     * @return boolean
     */
    protected function _saveAssocDataBase(& $row)
    {
        switch (strtolower($this->saveAssocMethod)) {
        case 'create':
            return $this->assocTDG->create($row);
        case 'update':
            return $this->assocTDG->update($row);
        case 'replace':
            return $this->assocTDG->replace($row);
        default:
            return $this->assocTDG->save($row);
        }
    }
}

/**
 * FLEA_Db_HasOneLink 封装 has one 关系
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_HasOneLink extends FLEA_Db_TableLink
{
    public $oneToOne = true;

    /**
     * 返回用于查询关联表数据的SQL语句
     *
     * @param string $in
     *
     * @return string
     */
    public function getFindSQL($in)
    {
        if (!$this->init) { $this->init(); }
        $fields = $this->qforeignKey . ' AS ' . $this->mainTDG->pka . ', ' . $this->dbo->qfields($this->fields, $this->assocTDG->fullTableName);
        $sql = <<<EOT
SELECT {$fields}
FROM {$this->assocTDG->qtableName}
EOT;

        return parent::_getFindSQLBase($sql, $in);
    }

    /**
     * 创建或更新主表记录时，保存关联的数据
     *
     * @param array $row 要保存的关联数据
     * @param mixed $pkv 主表的主键字段值
     *
     * @return boolean
     */
    public function saveAssocData(& $row, $pkv)
    {
        if (empty($row)) { return true; }
        if (!$this->init) { $this->init(); }
        $row[$this->foreignKey] = $pkv;
        return $this->_saveAssocDataBase($row);
    }

    /**
     * 删除关联的数据
     *
     * @param mixed $qpkv
     *
     * @return boolean
     */
    public function deleteByForeignKey($qpkv)
    {
        if (!$this->init) { $this->init(); }
        $conditions = "{$this->qforeignKey} = {$qpkv}";
        if ($this->linkRemove) {
            return $this->assocTDG->removeByConditions($conditions);
        } else {
            return $this->assocTDG->updateField($conditions, $this->foreignKey, $this->linkRemoveFillValue);
        }
    }

    /**
     * 完全初始化关联对象
     */
    public function init()
    {
        parent::init();
        if ($this->foreignKey == null) {
            $this->foreignKey = $this->mainTDG->primaryKey;
        }
        $this->qforeignKey = $this->dbo->qfield($this->foreignKey, $this->assocTDG->fullTableName);
    }
}

/**
 * FLEA_Db_BelongsToLink 封装 belongs to 关系
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_BelongsToLink extends FLEA_Db_TableLink
{
    public $oneToOne = true;

    /**
     * 返回用于查询关联表数据的SQL语句
     *
     * @param string $in
     *
     * @return string
     */
    public function getFindSQL($in)
    {
        if (!$this->init) { $this->init(); }
        $fields = $this->mainTDG->qpk . ' AS ' . $this->mainTDG->pka . ', ' . $this->dbo->qfields($this->fields, $this->assocTDG->fullTableName);

        $sql = <<<EOT
SELECT {$fields}
FROM {$this->assocTDG->qtableName}
LEFT JOIN {$this->mainTDG->qtableName}
    ON {$this->mainTDG->qpk} {$in}
WHERE {$this->qforeignKey} = {$this->assocTDG->qpk}
EOT;

        $in = '';
        return parent::_getFindSQLBase($sql, $in);
    }

    /**
     * 创建或更新主表记录时，保存关联的数据
     *
     * @param array $row 要保存的关联数据
     * @param mixed $pkv 主表的主键字段值
     *
     * @return boolean
     */
    public function saveAssocData(& $row, $pkv)
    {
        if (empty($row)) { return true; }
        if (!$this->init) { $this->init(); }
        return $this->_saveAssocDataBase($row);
    }

    /**
     * 完全初始化关联对象
     */
    public function init()
    {
        parent::init();
        if ($this->foreignKey == null) {
            $this->foreignKey = $this->assocTDG->primaryKey;
        }
        $this->qforeignKey = $this->dbo->qfield($this->foreignKey, $this->mainTDG->fullTableName);
    }
}

/**
 * FLEA_Db_HasManyLink 封装 has many 关系
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_HasManyLink extends FLEA_Db_HasOneLink
{
    public $oneToOne = false;

    /**
     * 创建或更新主表记录时，保存关联的数据
     *
     * @param array $row 要保存的关联数据
     * @param mixed $pkv 主表的主键字段值
     *
     * @return boolean
     */
    public function saveAssocData(& $row, $pkv)
    {
        if (empty($row)) { return true; }
        if (!$this->init) { $this->init(); }

        foreach ($row as $arow) {
            if (!is_array($arow)) { continue; }
            $arow[$this->foreignKey] = $pkv;
            if (!$this->_saveAssocDataBase($arow)) {
                return false;
            }
        }
        return true;
    }
}

/**
 * FLEA_Db_ManyToManyLink 封装 many to many 关系
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_ManyToManyLink extends FLEA_Db_TableLink
{
    /**
     * 组合关联数据时是否是一对一
     *
     * @var boolean
     */
    public $oneToOne = false;

    /**
     * 在处理中间表时，是否要将中间表当做实体
     *
     * @var boolean
     */
    public $joinTableIsEntity = false;

    /**
     * 中间表是实体时对应的表数据入口
     *
     * @var FLEA_Db_TableDataGateway
     */
    public $joinTDG = null;

    /**
     * 中间表的名字
     *
     * @var string
     */
    public $joinTable = null;

    /**
     * 中间表的完全限定名
     *
     * @var string
     */
    public $qjoinTable = null;

    /**
     * 中间表中保存关联表主键值的字段
     *
     * @var string
     */
    public $assocForeignKey = null;

    /**
     * 中间表中保存关联表主键值的字段的完全限定名
     *
     * @var string
     */
    public $qassocForeignKey = null;

    /**
     * 中间表对应的表数据入口
     *
     * @var FLEA_Db_TableDataGateway
     */
    public $joinTableClass = null;

    /**
     * 构造函数
     *
     * @param array $define
     * @param enum $type
     * @param FLEA_Db_TableDataGateway $mainTDG
     */
    public function __construct($define, $type, $mainTDG)
    {
        $this->_optional[] = 'joinTable';
        $this->_optional[] = 'joinTableClass';
        $this->_optional[] = 'assocForeignKey';
        parent::__construct($define, $type, $mainTDG);
        if ($this->joinTableClass != '') {
            $this->joinTableIsEntity = true;
        }
    }

    /**
     * 返回用于查询关联表数据的SQL语句
     *
     * @param string $in
     *
     * @return string
     */
    public function getFindSQL($in)
    {
        static $joinFields = array();

        if (!$this->init) { $this->init(); }

        $fields = $this->qforeignKey . ' AS ' . $this->mainTDG->pka . ', ' . $this->dbo->qfields($this->fields, $this->assocTDG->fullTableName);

        if ($this->joinTableIsEntity) {
            if (!isset($joinFields[$this->joinTDG->fullTableName])) {
                $f = '';
                foreach ($this->joinTDG->meta as $field) {
                    $f .= ', ' . $this->joinTDG->qfield($field['name']) . '  AS _join_' . $field['name'];
                }
                $joinFields[$this->joinTDG->fullTableName] = $f;
            }
            $fields .= $joinFields[$this->joinTDG->fullTableName];

            $sql = <<<EOT
SELECT {$fields}
FROM {$this->joinTDG->qtableName}
INNER JOIN {$this->assocTDG->qtableName}
    ON {$this->assocTDG->qpk} = {$this->qassocForeignKey}
EOT;
        } else {
            $sql = <<<EOT
SELECT {$fields}
FROM {$this->qjoinTable}
INNER JOIN {$this->assocTDG->qtableName}
    ON {$this->assocTDG->qpk} = {$this->qassocForeignKey}
EOT;
        }

        return parent::_getFindSQLBase($sql, $in);
    }

    /**
     * 创建或更新主表记录时，保存关联的数据
     *
     * @param array $row 要保存的关联数据
     * @param mixed $pkv 主表的主键字段值
     *
     * @return boolean
     */
    public function saveAssocData(& $row, $pkv)
    {
        if (!$this->init) { $this->init(); }
        $apkvs = array();
        $entityRowset = array();

        foreach ($row as $arow) {
            if (!is_array($arow)) {
                $apkvs[] = $arow;
                continue;
            }

            if (!isset($arow[$this->assocTDG->primaryKey])) {
                // 如果关联记录尚未保存到数据库，则创建一条新的关联记录
                $newrowid = $this->assocTDG->create($arow);
                if ($newrowid == false) {
                    return false;
                }
                $apkv = $newrowid;
            } else {
                $apkv = $arow[$this->assocTDG->primaryKey];
            }
            $apkvs[] = $apkv;

            if ($this->joinTableIsEntity && isset($arow['#JOIN#'])) {
                $entityRowset[$apkv] =& $arow['#JOIN#'];
            }
        }

        // 首先取出现有的关联信息
        $qpkv = $this->dbo->qstr($pkv);
        $sql = <<<EOT
SELECT {$this->qassocForeignKey}
FROM {$this->qjoinTable}
WHERE {$this->qforeignKey} = {$qpkv}
EOT;

        $existsMiddle = (array)$this->dbo->getCol($sql);

        // 然后确定要添加的关联信息
        $insertAssoc = array_diff($apkvs, $existsMiddle);
        $removeAssoc = array_diff($existsMiddle, $apkvs);

        if ($this->joinTableIsEntity) {
            $insertEntityRowset = array();
            foreach ($insertAssoc as $assocId) {
                if (isset($entityRowset[$assocId])) {
                    $row = $entityRowset[$assocId];
                } else {
                    $row = array();
                }
                $row[$this->foreignKey] = $pkv;
                $row[$this->assocForeignKey] = $assocId;
                $insertEntityRowset[] = $row;
            }
            if ($this->joinTDG->createRowset($insertEntityRowset) === false) {
                return false;
            }
        } else {
            $sql = <<<EOT
INSERT INTO {$this->qjoinTable}
    ({$this->qforeignKey}, {$this->qassocForeignKey})
VALUES
    ({$qpkv},
EOT;

            foreach ($insertAssoc as $assocId) {
                if (!$this->dbo->execute($sql . $this->dbo->qstr($assocId) . ')')) {
                    return false;
                }
            }
        }

        // 最后删除不再需要的关联信息
        if ($this->joinTableIsEntity) {
            $conditions = array($this->foreignKey => $pkv);
            foreach ($removeAssoc as $assocId) {
                $conditions[$this->assocForeignKey] = $assocId;
                if ($this->joinTDG->removeByConditions($conditions) === false) {
                    return false;
                }
            }
        } else {
            $sql = <<<EOT
DELETE FROM {$this->qjoinTable}
WHERE {$this->qforeignKey} = {$qpkv} AND {$this->qassocForeignKey} =
EOT;
            foreach ($removeAssoc as $assocId) {
                if (!$this->dbo->execute($sql . $this->dbo->qstr($assocId))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 根据主表的外键字段值，删除中间表的数据
     *
     * @param mixed $qpkv
     *
     * @return boolean
     */
    public function deleteMiddleTableDataByMainForeignKey($qpkv)
    {
        if (!$this->init) { $this->init(); }
        $sql = <<<EOT
DELETE FROM {$this->qjoinTable}
WHERE {$this->qforeignKey} = {$qpkv}
EOT;
        return $this->dbo->execute($sql);
    }

    /**
     * 根据关联表的外键字段值，删除中间表的数据
     *
     * @param mixed $pkv
     *
     * @return boolean
     */
    public function deleteMiddleTableDataByAssocForeignKey($pkv)
    {
        if (!$this->init) { $this->init(); }
        $qpkv = $this->dbo->qstr($pkv);
        $sql = <<<EOT
DELETE FROM {$this->qjoinTable}
WHERE {$this->qassocForeignKey} = {$qpkv}
EOT;
        return $this->dbo->execute($sql);
    }

    /**
     * 完全初始化关联对象
     */
    public function init()
    {
        parent::init();
        if ($this->joinTableClass) {
            $this->joinTDG = FLEA::getSingleton($this->joinTableClass);
            $this->joinTable = $this->joinTDG->tableName;
        }
        if ($this->joinTable == null) {
            $this->joinTable = $this->getMiddleTableName($this->mainTDG->tableName, $this->assocTableName);
        }
        if ($this->foreignKey == null) {
            $this->foreignKey = $this->mainTDG->primaryKey;
        }
        $this->joinTable = $this->dbo->dsn['dbTablePrefix'] . $this->joinTable;
        $this->qjoinTable = $this->dbo->qtable($this->joinTable);
        $this->qforeignKey = $this->dbo->qfield($this->foreignKey, $this->joinTable);
        if ($this->assocForeignKey == null) {
            $this->assocForeignKey = $this->assocTDG->primaryKey;
        }
        $this->qassocForeignKey = $this->dbo->qfield($this->assocForeignKey, $this->joinTable);
    }
}

