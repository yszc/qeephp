<?php

class Table_Comments extends QTable_Base
{
    public $table_name = 'comments';
    public $pk = 'comment_id';

    protected $belongs_to = array(
        array(
            'table_class'   => 'Table_Contents',
            'mapping_name'  => 'content',
            'foreign_key'   => 'content_id',
        ),
    );
}
