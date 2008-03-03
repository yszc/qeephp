<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../init.php';

class Test_QActiveRecord extends PHPUnit_Framework_TestCase
{

    function setUp()
    {
        Q::loadClass('Fixture_QActiveRecord_Post');
        Q::loadClass('Fixture_QActiveRecord_User');
    }

    function test_find_where()
    {
        $select = Fixture_QActiveRecord_Post::find_where("post_id > 0");
        $this->assertType('QActiveRecord_Select', $select);
    }

    function test_find2()
    {
    }

    function test_force_create()
    {
    }

    function test_save()
    {
    }

    function test_delete()
    {
    }

    function test_delete_all()
    {
    }

}


