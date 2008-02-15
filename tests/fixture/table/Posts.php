<?php

class Table_Posts extends QTable_Base
{
    public $tableName = 'posts';
    public $primaryKey = 'post_id';

    protected $hasMany = array(
        array(
            'tableClass' => 'Table_Comments',
            'mappingName' => 'comments',
            'foreignKey' => 'post_id',
        ),
    );

    protected $hasOne = array(
        array(
            'tableClass' => 'Table_PostsCopy',
            'mappingName' => 'copy',
            'foreignKey' => 'post_id',
        ),
    );

    protected $belongsTo = array(
        array(
            'tableClass' => 'Table_Authors',
            'mappingName' => 'author',
            'foreignKey' => 'author_id',
        ),
    );

    protected $manyToMany = array(
        array(
            'tableClass' => 'Table_Tags',
            'mappingName' => 'tags',
            'midTableClass' => 'Table_PostHasTags',
        ),
        array(
            'tableClass' => 'Table_Diggs',
            'mappingName' => 'diggs',
            'midTableName' => 'post_has_diggs',
            'foreignKey' => 'post_id',
            'assocForeignKey' => 'digg_id',
        ),
    );

}

