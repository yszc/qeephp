<?php

class Table_PostHasTags extends QTable_Base
{
    public $tableName = 'post_has_tags';
    public $primaryKey = array('post_id', 'tag_id');

}

