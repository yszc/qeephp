<?php

/**
 * QTable_Link_Has_One 封装 has one 关系
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.1
 */
class QTable_Link_Has_One extends QTable_Link_Abstract
{
    public $one_to_one = true;

    function get_find_sql(array $fkvs)
    {
        /**
         * SELECT post_id AS ref__link__key, * FROM comments WHERE comments.post_id IN (1)
         */
        $rk = $this->assoc_dbo->qfield($this->fk, $this->assoc_table->full_table_name);
        $other = $this->assoc_dbo->qfield($this->fields, $this->assoc_table->full_table_name);
        $sql = "SELECT {$rk} AS ref__link__key, {$other} FROM {$this->assoc_table->qtable_name}";
        return parent::get_find_sql_base($sql, $this->fk, $fkvs);
    }

    function save_assoc_row(array $row, $fkv)
    {
        $row[$this->fk] = $fkv;
        return $this->assoc_table->save($row);
    }

    function delete_by_fkvs(array $fkvs)
    {
        /**
         * DELETE FROM comments WHERE post_id IN (1)
         */
        $fk = $this->assoc_dbo->qfield($this->fk);
        $sql = "DELETE FROM {$this->assoc_table->qtable_name} ";
        $sql .= $this->assoc_dbo->qinto("WHERE {$fk} IN (?)", $fkvs, QDBO_Abstract::param_qm);
        $this->assoc_dbo->execute($sql);
    }

    protected function init()
    {
        parent::init();
        if (empty($this->fk)) {
            $this->fk = $this->main_table->pk;
        }
    }
}

