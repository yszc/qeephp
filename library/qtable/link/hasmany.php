<?php

/**
 * QTable_Link_Has_Many 封装 has many 关系
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.0
 */
class QTable_Link_HasMany extends QTable_Link_HasOne
{
    public $one_to_one = false;

    function save_assoc_rowset(array $rowset, $pkv)
    {
        $pkvs = array();
        foreach (array_keys($rowset) as $offset) {
            $pkvs[] = $this->save_assoc_row($rowset[$offset], $pkv);
        }
        return $pkvs;
    }
}

