<?php

class Table_Authors extends QTable_Base
{
    public $tableName = 'authors';
    public $primaryKey = 'author_id';

    protected $hasMany = array(
        array(
            'tableClass' => 'Table_Posts',
            'mappingName' => 'posts',
            'foreignKey' => 'author_id',
        ),
        array(
            'tableClass' => 'Table_Comments',
            'mappingName' => 'comments',
            'foreignKey' => 'author_id',
        ),
    );

}

