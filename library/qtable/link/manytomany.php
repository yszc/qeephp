<?php

class QTable_Link_ManyToMany extends QTable_Link_Abstract
{
    public $one_to_one = false;

    /**
     * 中间表的名字
     *
     * @var string
     */
    public $mid_table = null;

    /**
     * 中间表中保存关联表主键值的字段
     *
     * @var string
     */
    public $assoc_fk = null;

    function get_find_sql(array $fkvs)
    {
        $this->init();

        /**
         * 如果中间表不是作为实体，那么直接生成 SQL 进行查询
         */

        /**
         * SELECT post_has_tags.post_id AS link__ref__key, tags.* FROM post_has_tags INNER JOIN tags ON tags.tag_id = post_has_tags.tag_id AND post_has_tags.post_id IN (1, 2)
         */
        $rk = $this->assoc_dbo->qfield($this->fk, $this->mid_table);
        $fields = $this->assoc_table->qfields($this->fields);
        $join_table = $this->assoc_dbo->qtable($this->mid_table);
        $mid_fk = $this->assoc_dbo->qfield($this->mid_fk, $this->mid_table);
        $sql = "SELECT {$rk} AS link__ref__key, {$fields} FROM {$this->assoc_table->qtable_name} INNER JOIN {$mid_table} ON {$this->assoc_table->qpk} = {$mid_fk}";
        return parent::get_find_sql_base($sql, $this->fk, $fkvs);
    }

    function save_assoc_row($row, $fkv)
    {
        $this->init();
        $apkvs = array();
        $entityRowset = array();

        foreach ($row as $arow) {
            if (!is_array($arow)) {
                $apkvs[] = $arow;
                continue;
            }

            if (!isset($arow[$this->assocTDG->pk])) {
                // 如果关联记录尚未保存到数据库，则创建一条新的关联记录
                $newrowid = $this->assocTDG->create($arow);
                if ($newrowid == false) {
                    return false;
                }
                $apkv = $newrowid;
            } else {
                $apkv = $arow[$this->assocTDG->pk];
            }
            $apkvs[] = $apkv;

            if ($this->mid_table_is_entity && isset($arow['#JOIN#'])) {
                $entityRowset[$apkv] =& $arow['#JOIN#'];
            }
        }

        // 首先取出现有的关联信息
        $qpkv = $this->dbo->qstr($pkv);
        $sql = "SELECT {$this->qmid_fk} FROM {$this->qmid_table} WHERE {$this->qforeignKey} = {$qpkv} ";
        $existsMiddle = (array)$this->dbo->get_col($sql);

        // 然后确定要添加的关联信息
        $insertAssoc = array_diff($apkvs, $existsMiddle);
        $removeAssoc = array_diff($existsMiddle, $apkvs);

        if ($this->mid_table_is_entity) {
            $insertEntityRowset = array();
            foreach ($insertAssoc as $assocId) {
                if (isset($entityRowset[$assocId])) {
                    $row = $entityRowset[$assocId];
                } else {
                    $row = array();
                }
                $row[$this->foreignKey] = $pkv;
                $row[$this->mid_fk] = $assocId;
                $insertEntityRowset[] = $row;
            }
            if ($this->mid_table->createRowset($insertEntityRowset) === false) {
                return false;
            }
        } else {
            $sql = "INSERT INTO {$this->qmid_table} ({$this->qforeignKey}, {$this->qmid_fk}) VALUES ({$qpkv}, ";
            foreach ($insertAssoc as $assocId) {
                if (!$this->dbo->execute($sql . $this->dbo->qstr($assocId) . ')')) {
                    return false;
                }
            }
        }

        // 最后删除不再需要的关联信息
        if ($this->mid_table_is_entity) {
            $conditions = array($this->foreignKey => $pkv);
            foreach ($removeAssoc as $assocId) {
                $conditions[$this->mid_fk] = $assocId;
                if ($this->mid_table->removeByConditions($conditions) === false) {
                    return false;
                }
            }
        } else {
            $sql = "DELETE FROM {$this->qmid_table} WHERE {$this->qforeignKey} = {$qpkv} AND {$this->qmid_fk} = ";
            foreach ($removeAssoc as $assocId) {
                if (!$this->dbo->execute($sql . $this->dbo->qstr($assocId))) {
                    return false;
                }
            }
        }

        if ($this->counterCache) {
            $sql = "UPDATE {$this->mainTDG->qtable_name} SET {$this->counterCache} = (SELECT COUNT(*) FROM {$this->qmid_table} WHERE {$this->qforeignKey} = {$qpkv}) WHERE {$this->mainTDG->qpk} = {$qpkv}";
            $this->mainTDG->dbo->execute($sql);
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
    function deleteMiddleTableDataByMainForeignKey($qpkv)
    {
        if (!$this->init) { $this->init(); }
        $sql = "DELETE FROM {$this->qmid_table} WHERE {$this->qforeignKey} = {$qpkv} ";
        return $this->dbo->execute($sql);
    }

    /**
     * 根据关联表的外键字段值，删除中间表的数据
     *
     * @param mixed $pkv
     *
     * @return boolean
     */
    function deleteMiddleTableDataBymid_fk($pkv)
    {
        if (!$this->init) { $this->init(); }
        $qpkv = $this->dbo->qstr($pkv);
        $sql = "DELETE FROM {$this->qmid_table} WHERE {$this->qmid_fk} = {$qpkv} ";
        return $this->dbo->execute($sql);
    }

    /**
     * 完全初始化关联对象
     */
    function init()
    {
        parent::init();
        if ($this->mid_table_class) {
            $this->mid_table = Q::getSingleton($this->mid_table_class);
            $this->mid_table = $this->mid_table->table_name;
            $joinSchema = $this->mid_table->schema;
        } else {
            $joinSchema = $this->mainTDG->schema;
        }
        if (is_null($this->mid_table)) {
            $this->mid_table = $this->getMiddleTableName($this->mainTDG->table_name, $this->assocTableName);
        }
        if (is_null($this->foreignKey)) {
            $this->foreignKey = $this->mainTDG->pk;
        }
        $this->mid_table = $this->dbo->dsn['prefix'] . $this->joinTable;
        $this->qmid_table = $this->dbo->qtable($this->joinTable, $joinSchema);
        $this->qforeignKey = $this->dbo->qfield($this->foreignKey, $this->mid_table, $joinSchema);
        if (is_null($this->mid_fk)) {
            $this->mid_fk = $this->assocTDG->pk;
        }
        $this->qmid_fk = $this->dbo->qfield($this->assocForeignKey, $this->mid_table, $this->mainTDG->schema);
    }
}
