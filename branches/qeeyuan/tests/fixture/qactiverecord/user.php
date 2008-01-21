<?php


class Fixture_QActiveRecord_User extends QActiveRecord_Abstract
{
    static function define()
    {
        return array(
            'table_name' => 'users',
        );
    }

    static function find_where()
    {
        $args = func_get_args();
        return parent::__find(__CLASS__, $args);
    }

    static function delete_where()
    {
    }

    static function destroy_where()
    {
    }
}


