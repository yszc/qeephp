<?php

class Table_Diggs extends QTable_Base
{
    public $tableName = 'diggs';
    public $primaryKey = 'digg_id';

    protected $manyToMany = array(
        array(
            'tableClass' => 'Table_Posts',
            'mappingName' => 'posts',
            'midTableName' => 'post_has_diggs',
            'foreignKey' => 'digg_id',
            'assocForeignKey' => 'post_id',
        ),
    );

}

