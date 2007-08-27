<?php

/**
 * ORM 实现了一个简单的 ORM 系统
 *
 * @package System
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class ORM
{
    /**
     * 每个类的参数设置
     *
     * @var array
     */
    protected $_classOpts = array();

    /**
     * 构造函数
     */
    public function __construct()
    {

    }

    /**
     * 获取指定类型、指定 ID 的对象实例，如果指定类型不存在或者指定的 ID 不存在则抛出异常
     *
     * @param string $class
     * @param mixed $id
     */
    public function find($class, $id)
    {
        if (!isset($this->_classOpts[$class])) {
            Qee::loadClass($class);
            $opts = call_user_func(array($class, '__setupORM'));
            if (isset($opts['tableClass'])) {
                $table = Qee::getSingleton($opts['tableClass']);
            } elseif (isset($opts['tableName'])) {
                Qee::loadClass('Qee_Db_TableDataGateway');
                $table = new Qee_Db_TableDataGateway(array('tableName' => $opts['tableName']));
            } else {
                throw new Exception('不完整的属性', 0);
            }
            if (!isset($opts['field'])) { $opts['field'] = '*'; }
            $opts['table'] = $table;
            $this->_classOpts[$class] = $opts;
        } else {
            $opts = $this->_classOpts[$class];
        }

        $row = $opts['table']->find(array($table->primaryKey => $id), null, $opts['fields'], false);
        if (!empty($row)) {
            throw new Exception('指定的 ID 不存在', 0);
        }

        return self::attach($obj, $row, $opts);
    }

    /**
     * 将对象保存到数据库，出错时抛出异常
     *
     * @param IActiveRecord $obj
     */
    public function save(IActiveRecord $obj)
    {
        $class = get_class($obj);
    }

    /**
     * 将一个数据库记录行附着到一个对象上
     *
     * 如果数据附着过程中产生了新对象，则新创建的对象会自动添加到 ORM 中。
     *
     * @param object $obj
     * @param array $data
     * @param boolean $enableLinks
     * @param string $tableKey
     */
    function attach(& $obj, & $data, $enableLinks = true, $tableKey = null)
    {
        if ($tableKey == null) {
            $tableKey = strtolower(get_class($obj));
        }
        $table =& $this->_tables[$tableKey];
        /* var $table Qee_Db_TableDataGateway */

        $this->_setObjectProperties($obj, $table->meta, $data);
        if (!$enableLinks) { return; }

        // 处理关联
        foreach ($table->_links as $link) {
            /* @var $link Qee_Db_TableLink */
            $fieldName = $link->mappingName;

            if (!isset($data[$fieldName]) || !is_array($data[$fieldName])) {
                $assocData = array();
            } else {
                $assocData =& $data[$fieldName];
            }

            if ($link->recordObjectClass == '') {
                // 如果关联没有指定对象名，则将关联数据直接作为对象的属性
                $obj->{$fieldName} = $assocData;
                continue;
            }

            $key = strtolower($link->recordObjectClass);
            if (!isset($this->_tables[$key])) {
                $assocTDG =& Qee::getSingleton($link->tableClass);
                /* @var $assocTDG Qee_Db_TableDataGateway */
                $this->_tables[$key] =& $assocTDG;
                $this->_idFields[$key] = $assocTDG->primaryKey;
                $idField = $assocTDG->primaryKey;
            } else {
                $assocTDG =& $this->_tables[$key];
                $idField = $this->_idFields[$key];
            }

            Qee::loadClass($link->recordObjectClass);

            if ($link->oneToOne) {
                $objId = $assocData[$idField];
                $this->_instances[$key][$objId] =& new $link->recordObjectClass();
                $this->_setObjectProperties(
                        $this->_instances[$key][$objId],
                        $assocTDG->meta, $assocData);
                $shadowData =& $this->_getObjectProperties(
                        $this->_instances[$key][$objId],
                        $assocTDG->meta);
                $this->_shadow[$key][$objId] = serialize($shadowData);
                $this->_queryParameters[$key][$objId] = array('enableLinks' => false);
                $obj->{$fieldName} =& $this->_instances[$key][$objId];
            } else {
                $obj->{$fieldName} = array();
                foreach ($assocData as $assocItem) {
                    $objId = $assocItem[$idField];
                    $this->_instances[$key][$objId] =& new $link->recordObjectClass();
                    $this->_setObjectProperties(
                            $this->_instances[$key][$objId],
                            $assocTDG->meta, $assocItem);
                    $shadowData =& $this->_getObjectProperties(
                            $this->_instances[$key][$objId],
                            $assocTDG->meta);
                    $this->_shadow[$key][$objId] = serialize($shadowData);
                    $this->_queryParameters[$key][$objId] = array('enableLinks' => false);
                    $obj->{$fieldName}[] =& $this->_instances[$key][$objId];
                }
            }
        }
    }

    /**
     * 将对象保存到数据库
     *
     * @param object $obj
     * @param boolean $enableLinks
     *
     * @return boolean
     */
    function save(& $obj, $enableLinks = true)
    {
        $key = strtolower(get_class($obj));
        if (!isset($this->_map[$key])) {
            return false;
        }

        $idField = $this->_idFields[$key];
        $objId = $obj->{$idField};

        $table =& $this->_tables[$key];
        /* @var $table Qee_Db_TableDataGateway */

        $shadowData =& $this->_getObjectProperties($obj, $table->meta);
        $serialize = serialize($shadowData);
        if ($this->_shadow[$key][$objId] == $serialize) {
            // 如果对象的数据没有发生变化，则无需保存到数据库
            return true;
        }

        // 从对象获取数据
        $data = array();
        foreach ($table->meta as $field) {
            $fieldName = $field['name'];
            $data[$fieldName] = $obj->{$fieldName};
        }

        if ($enableLinks) {
            // 处理对象关联的数据
            foreach ($table->_links as $link) {
                /* @var $link Qee_Db_TableLink */
                $mappingName = $link->mappingName;
                if (!isset($obj->{$mappingName})) { continue; }
                if ($link->recordObjectClass == '') {
                    $data[$mappingName] = $obj->{$mappingName};
                    continue;
                }

                $key = strtolower($link->recordObjectClass);
                $assocTDG =& $this->_tables[$key];
                /* @var $assocTDG Qee_Db_TableDataGateway */
                $data[$mappingName] = array();
                if ($link->oneToOne) {
                    $assocObject =& $obj->{$mappingName};
                    $assocObjId = $assocObject->{$this->_idFields[$key]};
                    $shadowData = $this->_getObjectProperties($assocObject, $assocTDG->meta);
                    $serialize = serialize($shadowData);
                    // 跳过没有改变的对象
                    if ($this->_shadow[$key][$assocObjId] == $serialize) { continue; }

                    foreach ($assocTDG->meta as $field) {
                        $fieldName = $field['name'];
                        $data[$mappingName][$fieldName] = $obj->{$mappingName}->{$fieldName};
                    }
                } else {
                    $offset = 0;
                    foreach ($obj->{$mappingName} as $assocObject) {
                        $assocObjId = $assocObject->{$this->_idFields[$key]};
                        $shadowData = $this->_getObjectProperties($assocObject, $assocTDG->meta);
                        $serialize = serialize($shadowData);
                        // 跳过没有改变的对象
                        if ($this->_shadow[$key][$assocObjId] == $serialize) { continue; }

                        foreach ($assocTDG->meta as $field) {
                            $fieldName = $field['name'];
                            $data[$mappingName][$offset][$fieldName] = $assocObject->{$fieldName};
                        }
                        $offset++;
                    }
                }
            }
        }

        $ret = $table->update($data);
        if ($ret == false) {
            return false;
        }

        /**
         * 重新读取保存后的对象数据，因为对象数据很有可能在保存到数据库时发生了变化
         *
         * 但在读取时，应该使用取出对象时使用的查询参数，
         * 避免对象保存后却和保存前多出来关联的数据。
         */
        $enableLinks = $this->_queryParameters[$key][$objId]['enableLinks'];
        $oldEnableLinks = $table->autoLink;
        $table->autoLink = $enableLinks;
        $data = $table->find(array($idField => $objId));
        $table->autoLink = $oldEnableLinks;

        $this->attach($obj, $table, $data, $enableLinks);
        return true;
    }

    /**
     * 保存 ORM 中的所有对象
     *
     * 所有修改过的对象都将被保存。
     */
    function saveAll()
    {
        $dboUsed = array();

        foreach ($this->_instances as $key => $objs) {
            $idField = $this->_idFields[$key];
            $offsetList = array_keys($objs);

            $table =& $this->_tables[$key];
            /* @var $table Qee_Db_TableDataGateway */
            $oldEnableLinks = $table->autoLink;
            $table->autoLink = false;

            $dbo =& $table->getDBO();
            $dsn = serialize($dbo->dsn);
            if (!isset($dboUsed[$dsn])) {
                $dboUsed[$dsn] =& $dbo;
                $dbo->startTrans();
            }

            foreach ($offsetList as $offset) {
                $obj =& $objs[$offset];
                $objId = $obj->{$idField};

                $shadowData =& $this->_getObjectProperties($obj, $table->meta);
                $serialize = serialize($shadowData);
                if ($this->_shadow[$key][$objId] == $serialize) { continue; }
                $table->save($shadowData);
            }

            $table->autoLink = $oldEnableLinks;
        }

        $dboIDs = array_keys($dboUsed);
        foreach ($dboIDs as $dboID) {
            $dboUsed[$dboID]->completeTrans();
        }
    }

    /**
     * 添加一个对象到 ORM 中
     *
     * 要添加的对象类型必须是已经用 ORM::register() 注过的类型。
     *
     * @param object $obj
     */
    function add(& $obj)
    {
        $key = strtolower(get_class($obj));
        if (!isset($this->_map[$key])) {
            return;
        }

        if (!isset($this->_tables[$key])) {
            list($objClass, $tableClass) = $this->_map[$key];

            Qee::loadClass($tableClass);
            $table =& Qee::getSingleton($tableClass);
            $this->_tables[$key] =& $table;
            $this->_idFields[$key] = $table->primaryKey;
        }
        $idField = $this->_idFields[$key];
        if (!isset($obj->{$idField})) { return; }
        $objId = $obj->{$idField};

        $this->_instances[$key][$objId] =& $obj;
    }

    /**
     * 为对象设置属性
     *
     * @param object $obj
     * @param array $meta
     * @param array $data
     */
    function _setObjectProperties(& $obj, & $meta, & $data)
    {
        foreach ($meta as $field) {
            $fieldName = $field['name'];
            if (isset($data[$fieldName])) {
                $obj->{$fieldName} = $data[$fieldName];
            } else {
                $obj->{$fieldName} = null;
            }
        }
    }

    /**
     * 返回对象的属性
     *
     * @param object $obj
     * @param array $meta
     *
     * @return array
     */
    function & _getObjectProperties(& $obj, & $meta)
    {
        $data = array();
        foreach ($meta as $field) {
            $fieldName = $field['name'];
            $data[$fieldName] = $obj->{$fieldName};
        }
        return $data;
    }
}

/**
$member = new Member();
$member->username = 'dualface';
$member->passsword = '123456';

$orm = new ORM();
$orm->save($member);

$post = $orm->find('Post', $postid);
echo $post->title;
*/
