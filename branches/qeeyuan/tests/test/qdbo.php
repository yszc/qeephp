<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../init.php';

class Test_QDBO extends PHPUnit_Framework_TestCase
{
    /**
     * @var QDBO_Abstract
     */
    protected $dbo;

    protected function setUp()
    {
        $this->dbo = QDBO_Abstract::get_dbo();
    }

    function test_get_id()
    {
        $id = $this->dbo->get_id();
        $this->assertTrue(!empty($id));
    }

    function test_get_schema()
    {
        $schema = $this->dbo->get_schema();
        $this->assertEquals(Q::getIni('dsn/database'), $schema);
    }

    function test_get_table_prefix()
    {
        $prefix = $this->dbo->get_table_prefix();
        $this->assertEquals(Q::getIni('dsn/prefix'), $prefix);
    }

    function test_connect()
    {
        $this->dbo->connect();
    }

    function test_is_connected()
    {
        $this->assertTrue($this->dbo->is_connected());
    }

    function test_qstr()
    {
        switch (Q::getIni('dsn/driver')) {
        case 'mysql':
        case 'mysqli':
        case 'pdomysql':
            $checks = array(
                array('12345', "'12345'"),
                array(12345, 12345),
                array(true, 1),
                array(false, 0),
                array(null, 'NULL'),
                array('string', "'string'"),
                array("string'string", "'string\\'string'"),
            );
            break;
        case 'pgsql':
        case 'pdopgsql':
            break;
        case 'oci8':
        case 'pdooci8':
            break;
        case 'mssql':
        case 'pdomssql':
            break;
        }

        foreach ($checks as $check) {
            $this->assertSame($check[1], $this->dbo->qstr($check[0]), "check qstr({$check[0]})");
        }
    }

    function test_qinto()
    {
        switch($this->dbo->param_style()) {
        case QDBO_Abstract::param_qm:
            $sql = "SELECT * FROM test_table WHERE level_ix > ? AND int_x = ?";
            $args = array(1, 2);
            break;
        case QDBO_Abstract::param_cl_named:
            $sql = "SELECT * FROM test_table WHERE level_ix > :level_ix AND int_x = :int_x";
            $args = array('level_ix' => 1, 'int_x' => 2);
            break;
        case QDBO_Abstract::param_dl_sequence:
            $sql = "SELECT * FROM test_table WHERE level_ix > $1 AND int_x = $2";
            $args = array(1, 2);
            break;
        case QDBO_Abstract::param_at_named:
            $sql = "SELECT * FROM test_table WHERE level_ix > @level_ix AND int_x = @int_x";
            $args = array('level_ix' => 1, 'int_x' => 2);
            break;
        }

        $expected = 'SELECT * FROM test_table WHERE level_ix > 1 AND int_x = 2';
        $this->assertEquals($expected, $this->dbo->qinto($sql, $args));
    }

    function test_qinto2()
    {
        $checks = array(
            array("SELECT * FROM test_table WHERE level_ix > ? AND int_x = ?", array(1, 2), QDBO_Abstract::param_qm),
            array("SELECT * FROM test_table WHERE level_ix > :level_ix AND int_x = :int_x", array('level_ix' => 1, 'int_x' => 2), QDBO_Abstract::param_cl_named),
            array("SELECT * FROM test_table WHERE level_ix > $1 AND int_x = $2", array(1, 2), QDBO_Abstract::param_dl_sequence),
            array("SELECT * FROM test_table WHERE level_ix > @level_ix AND int_x = @int_x", array('level_ix' => 1, 'int_x' => 2), QDBO_Abstract::param_at_named),
        );

        $expected = 'SELECT * FROM test_table WHERE level_ix > 1 AND int_x = 2';
        foreach ($checks as $check) {
            list($sql, $args, $param_style) = $check;
            $this->assertEquals($expected, $this->dbo->qinto($sql, $args, $param_style), $sql);
        }
    }

    function test_qtable()
    {
        switch (Q::getIni('dsn/driver')) {
        case 'mysql':
        case 'mysqli':
        case 'pdomysql':
            $checks = array(
                array(array('posts', null), '`posts`'),
                array(array('`posts`', null), '`posts`'),
                array(array('posts', 'test'), '`test`.`posts`'),
                array(array('`posts`', 'test'), '`test`.`posts`'),
                array(array('`posts`', '`test`'), '`test`.`posts`'),
            );
            break;
        case 'pgsql':
        case 'pdopgsql':
            break;
        case 'oci8':
        case 'pdooci8':
            break;
        case 'mssql':
        case 'pdomssql':
            break;
        }

        foreach ($checks as $check) {
            list($args, $expected) = $check;
            $actual = call_user_func_array(array($this->dbo, 'qtable'), $args);
            $this->assertEquals($expected, $actual, implode(', ', $args));
        }
    }

    function test_qfield()
    {
        switch (Q::getIni('dsn/driver')) {
        case 'mysql':
        case 'mysqli':
        case 'pdomysql':
            $checks = array(
                array(array('post_id', null), '`post_id`'),
                array(array('post_id', 'posts'), '`posts`.`post_id`'),
                array(array('post_id', 'posts', 'test'), '`test`.`posts`.`post_id`'),
                array(array('`post_id`', null), '`post_id`'),
                array(array('`post_id`','`posts`'), '`posts`.`post_id`'),
                array(array('post_id', null, 'test'), '`post_id`'),
            );
            break;
        case 'pgsql':
        case 'pdopgsql':
            break;
        case 'oci8':
        case 'pdooci8':
            break;
        case 'mssql':
        case 'pdomssql':
            break;
        }

        foreach ($checks as $check) {
            list($args, $expected) = $check;
            $actual = call_user_func_array(array($this->dbo, 'qfield'), $args);
            $this->assertEquals($expected, $actual, implode(', ', $args));
        }
    }

    function test_next_id()
    {
        $id = $this->dbo->next_id('test_seq');
        $next_id = $this->dbo->next_id('test_seq');
        $this->assertTrue($next_id > $id, "\$next_id({$next_id}) > \$id({$id})");
    }

    function test_insert_id()
    {
        $id_list = $this->insert_into_posts(1);
        $id = reset($id_list);
        $this->assertTrue(!empty($id), '!empty($id)');
    }

    function test_insert_id2()
    {
        $id = $this->dbo->next_id('test_seq');
        $insert_id = $this->dbo->insert_id();
        $this->assertEquals($id, $insert_id, '$id == $insert_id');
    }

    function test_affected_rows()
    {
        $id_list = implode(',', $this->insert_into_posts(10));
        $sql = "DELETE FROM rx_posts WHERE post_id IN ({$id_list})";
        $this->dbo->execute($sql);
        $affected_rows = $this->dbo->affected_rows();
        $this->assertEquals(10, $affected_rows, '10 == $affected_rows');
    }

    function test_execute()
    {
        switch (Q::getIni('dsn/driver')) {
        case 'mysql':
        case 'mysqli':
        case 'pdomysql':
            $sql = "INSERT INTO rx_posts (title, body, created, updated) VALUES (?, ?, ?, ?)";
            $args = array('title', 'body', time(), time());
            break;
        case 'pgsql':
        case 'pdopgsql':
            break;
        case 'oci8':
        case 'pdooci8':
            break;
        case 'mssql':
        case 'pdomssql':
            break;
        }

        $this->dbo->execute($sql, $args);
    }

    function test_select_limit()
    {
        $this->dbo->execute('DELETE FROM rx_posts');
        $id_list = $this->insert_into_posts(10);
        $sql = "SELECT post_id FROM rx_posts ORDER BY post_id ASC";

        $length = 10;
        $offset = 0;
        $rowset = $this->dbo->select_limit($sql, $length, $offset)->fetch_all();
        $this->assertEquals($length, count($rowset), "\$rowset = \$this->dbo->select_limit('{$sql}', {$length}, {$offset});");
        for ($i = $offset; $i < $offset + $length; $i++) {
            $this->assertEquals($id_list[$i], $rowset[$i - $offset]['post_id'], "\$length = {$length}, \$offset = {$offset}");
        }

        $length = 3; 
        $offset = 5;
        $rowset = $this->dbo->select_limit($sql, $length, $offset)->fetch_all();
        $this->assertEquals($length, count($rowset), "\$rowset = \$this->dbo->select_limit('{$sql}', {$length}, {$offset});");
        for ($i = $offset; $i < $offset + $length; $i++) {
            $this->assertEquals($id_list[$i], $rowset[$i - $offset]['post_id'], "\$length = {$length}, \$offset = {$offset}");
        }
    }

    function test_get_all()
    {
        $this->dbo->execute('DELETE FROM rx_posts');
        $id_list = $this->insert_into_posts(10);

        $sql = "SELECT post_id FROM rx_posts ORDER BY post_id ASC";
        $rowset = $this->dbo->get_all($sql);
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($id_list[$i], $rowset[$i]['post_id']);
        }
    }

    function test_get_col()
    {
        $this->dbo->execute('DELETE FROM rx_posts');
        $id_list = $this->insert_into_posts(10);

        $sql = "SELECT post_id FROM rx_posts ORDER BY post_id ASC";
        $rowset = $this->dbo->get_col($sql);
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($id_list[$i], $rowset[$i]);
        }
    }

    function test_begin_trans()
    {
        $sql = 'SELECT COUNT(*) FROM rx_posts';
        $count = $this->dbo->get_one($sql);
        $tran = $this->dbo->begin_trans();
        $this->assertType('QDBO_Transaction', $tran);
        $this->insert_into_posts(10);
        unset($tran);
        $new_count = $this->dbo->get_one($sql);
        $this->assertEquals($count + 10, $new_count);
    }

    function test_begin_trans2()
    {
        $sql = 'SELECT COUNT(*) FROM rx_posts';
        $count = $this->dbo->get_one($sql);
        $tran = $this->dbo->begin_trans();
        $this->assertType('QDBO_Transaction', $tran);
        $this->insert_into_posts(10);

        // 明确的回滚事务
        $tran->rollback();
        unset($tran);
        $new_count = $this->dbo->get_one($sql);
        $this->assertEquals($count, $new_count);
    }

    function test_begin_trans3()
    {
        $sql = 'SELECT COUNT(*) FROM rx_posts';
        $count = $this->dbo->get_one($sql);
        $tran = $this->dbo->begin_trans();
        $this->assertType('QDBO_Transaction', $tran);
        $this->insert_into_posts(10);
        try {
            // 当事务中出现数据库操作错误时回滚
            $this->dbo->execute('INSERT XXXX'); // must failed query
        } catch (Exception $ex) { }
        unset($tran);
        $new_count = $this->dbo->get_one($sql);
        $this->assertEquals($count, $new_count);
    }

    function test_get_insert_sql()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
            'created' => time(),
            'updated' => time(),
        );
        $sql = $this->dbo->get_insert_sql($row, 'rx_posts');
        $this->dbo->execute($sql, $row);
        $sql = 'SELECT * FROM rx_posts WHERE post_id = ' . $this->dbo->insert_id();
        $exists = $this->dbo->get_row($sql);
        $this->assertEquals($row['title'], $exists['title']);
    }

    function test_get_update_sql()
    {
        $id_list = $this->insert_into_posts(1);
        $id = reset($id_list);
        $sql = "SELECT * FROM rx_posts WHERE post_id = {$id}";
        $row = $this->dbo->get_row($sql);
        $row['title'] = 'Title +' . mt_rand();
        $update_sql = $this->dbo->get_update_sql($row, 'post_id', 'rx_posts');
        $this->dbo->execute($update_sql, $row);

        $exists = $this->dbo->get_row($sql);
        $this->assertEquals($row['title'], $exists['title']);
    }


    private function insert_into_posts($nums)
    {
        $time = time();
        $sql = "INSERT INTO rx_posts (title, body, created, updated) VALUES ('title', 'body', {$time}, {$time})";
        $id_list = array();
        for ($i = 0; $i < $nums; $i++) {
            $this->dbo->execute($sql);
            $id_list[] = $this->dbo->insert_id();
        }
        return $id_list;
    }
}

