<?php

class Table_PostsCopy extends QTable_Base
{
    public $tableName = 'posts_copy';
    public $primaryKey = 'copy_id';

    protected $belongsTo = array(
        array(
            'tableClass' => 'Table_Posts',
            'mappingName' => 'post',
            'foreignKey' => 'post_id',
        ),
    );

}

