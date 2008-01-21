<?php

class QTable_Link_Belongs_To extends QTable_Link_Abstract
{
    public $one_to_one = true;

    function get_find_sql(array $fkvs)
    {
        /**
         * SELECT posts.post_id AS ref__link__key, posts.* FROM posts WHERE posts.post_id IN (1)
         */
        $rk = $this->assoc_dbo->qfield($this->assoc_table->pk, $this->assoc_table->full_table_name);
        $other = $this->assoc_dbo->qfields($this->fields, $this->assoc_table->full_table_name);
        $sql = "SELECT {$rk} AS ref__link__key, {$other} FROM {$this->assoc_table->qtable_name}";
        return parent::get_find_sql_base($sql, $this->main_table->pk, $fkvs);
    }

    function init()
    {
        parent::init();
        if (is_null($this->fk)) {
            $this->fk = $this->assoc_table->pk;
        }
    }
}

