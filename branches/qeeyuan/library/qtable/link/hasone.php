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
 * 定义 QTable_Link_HasOne 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QTable_Link_HasOne 类封装了一个 has one 关联
 *
 * @package database
 */
class QTable_Link_HasOne extends QTable_Link_Abstract
{
    function getFindSql(array $fkvs)
    {
        /**
         * SELECT post_id AS ref__link__key, * FROM comments WHERE comments.post_id IN (1)
         */
        $rk = $this->assoc_dbo->qfield($this->fk, $this->assoc_table->full_table_name);
        $other = $this->assoc_dbo->qfield($this->fields, $this->assoc_table->full_table_name);
        $sql = "SELECT {$rk} AS ref__link__key, {$other} FROM {$this->assoc_table->qtable_name}";
        return parent::get_find_sql_base($sql, $this->fk, $fkvs);
    }

    function saveAssocRow(array $row, $fkv)
    {
        $row[$this->fk] = $fkv;
        return $this->assoc_table->save($row);
    }

    function deleteByFkvs(array $fkvs)
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

