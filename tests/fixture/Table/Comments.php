<?php

class Table_Comments extends QTable_Base
{
    public $tableName = 'comments';
    public $primaryKey = 'comment_id';

    protected $belongsTo = array(
        array(
            'tableClass' => 'Table_Posts',
            'mappingName' => 'post',
            'foreignKey' => 'post_id',
            'counterCache' => 'comments_count',
        ),
    );

}

