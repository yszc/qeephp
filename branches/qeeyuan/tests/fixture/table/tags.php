<?php

class Table_Tags extends QTable_Base
{
    public $tableName = 'tags';
    public $primaryKey = 'tag_id';

    protected $manyToMany = array(
        array(
            'tableClass' => 'Table_Posts',
            'mappingName' => 'posts',
            'midTableClass' => 'Table_PostHasTags',
        ),
    );

}

