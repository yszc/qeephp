<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../init.php';

class Test_QTable extends PHPUnit_Framework_TestCase
{
    /**
     * @var QTable_Base
     */
    protected $table;

    protected function setUp()
    {
        $dbo = QDBO_Abstract::get_dbo();
        $params = array(
            'table_name' => 'posts',
            'pk'         => 'post_id',
            'dbo'        => $dbo
        );
        $this->table = new QTable_Base($params, false);
    }

    function test_connect()
    {
        $this->table->connect();
        $this->assertTrue($this->table->is_connected());
    }

    function test_find()
    {
        $select = $this->table->find();
        $this->assertType('QTable_Select', $select);
    }

    function test_find2()
    {
        $select = $this->table->find('`post_id` = :post_id AND created > :created', array('post_id' => 1, 'created' => 0));
        $actual = trim($select->to_string());
        $expected = 'SELECT * FROM `rx_posts` WHERE (`post_id` = 1 AND created > 0)';
        $this->assertEquals($expected, $actual);
    }

    function test_create()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
        );
        $id = $this->table->create($row);
        $this->assertFalse(empty($id));

        $find = $this->table->find_by_sql("SELECT * FROM {$this->table->q_table_name} WHERE post_id = {$id}");
        $this->assertType('array', $find);
        $find = reset($find);
        $this->assertEquals($row['title'], $find['title']);
        $this->assertEquals($row['body'], $find['body']);
        $this->assertFalse(empty($find['created']));
        $this->assertFalse(empty($find['updated']));
    }

    function test_create_rowset()
    {
        $rowset = array();
        for ($i = 0, $max = mt_rand(1, 5); $i < $max; $i++) {
            $rowset[] = array('title' => 'Title :' . mt_rand() . ':', 'body' => 'Body :' . mt_rand());
        }

        $id_list = $this->table->create_rowset($rowset);
        $this->assertEquals($max, count($id_list));
    }

    function test_update()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
        );
        $id = $this->table->create($row);
        $this->assertFalse(empty($id));

        $sql = "SELECT * FROM {$this->table->q_table_name} WHERE post_id = {$id}";
        $find = $this->table->find_by_sql($sql);
        $find = reset($find);

        sleep(2);

        $find['title'] = 'Title -' . mt_rand();
        $affected_rows = $this->table->update($find);
        $this->assertEquals(1, $affected_rows);

        $find2 = $this->table->find_by_sql($sql);
        $find2 = reset($find2);

        $this->assertTrue($find2['updated'] > $find['updated']);
    }

    function test_update_where1()
    {
        $rowset = $this->table->find_by_sql("SELECT COUNT(*) AS row_count FROM {$this->table->q_table_name}");
        $row = reset($rowset);
        $count = $row['row_count'];

        $pairs = array('title' => 'Title =' . mt_rand());
        $affected_rows = $this->table->update_where($pairs, null);

        $this->assertEquals($count, $affected_rows);
    }

    function test_update_where2()
    {
        $rowset = $this->table->find_by_sql("SELECT COUNT(*) AS row_count FROM {$this->table->q_table_name}");
        $row = reset($rowset);
        $count = $row['row_count'];

        $pairs = array('title' => 'Title =' . mt_rand(), 'body' => 'Body =' . mt_rand());
        $affected_rows = $this->table->update_where($pairs, 'created > ?', array(0));
        $this->assertEquals($count, $affected_rows);
    }

    function test_incr_where()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
            'hint' => 5,
        );
        $id = $this->table->create($row);

        $sql = "SELECT * FROM {$this->table->q_table_name} WHERE post_id = {$id}";
        $exists = $this->table->find_by_sql($sql);
        $exists = reset($exists);

        sleep(2);
        $this->table->incr_where('hint', 1, "`post_id` = {$id}");

        $row = $this->table->find_by_sql($sql);
        $row = reset($row);

        $this->assertEquals($exists['hint'] + 1, $row['hint']);
        $this->assertTrue($row['updated'] > $exists['updated']);
    }

    function test_decr_where()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
            'hint' => 9,
        );
        $id = $this->table->create($row);

        $sql = "SELECT * FROM {$this->table->q_table_name} WHERE post_id = {$id}";
        $exists = $this->table->find_by_sql($sql);
        $exists = reset($exists);

        sleep(2);
        $this->table->decr_where('hint', 2, "`post_id` = {$id}");

        $row = $this->table->find_by_sql($sql);
        $row = reset($row);

        $this->assertEquals($exists['hint'] - 2, $row['hint']);
        $this->assertTrue($row['updated'] > $exists['updated']);
    }

    function test_remove()
    {
        $sql = "SELECT post_id FROM {$this->table->q_table_name} ORDER BY post_id ASC";
        $row = $this->table->find_by_sql($sql);
        $row = reset($row);
        $id = $row['post_id'];

        $this->table->remove($id);
        $sql = "SELECT post_id FROM {$this->table->q_table_name} WHERE post_id = {$id}";
        $row = $this->table->find_by_sql($sql);
        $this->assertTrue(empty($row));
    }

    function test_remove_where()
    {
        $row = array('title' => 'delete', 'body' => 'delete');
        $id = $this->table->create($row);
        $affected_rows = $this->table->remove_where("post_id = {$id}");
        $this->assertEquals(1, $affected_rows);

        $affected_rows = $this->table->remove_where(null);
        $this->assertTrue($affected_rows > 1);
    }

    function test_next_id()
    {
        $id = $this->table->next_id();
        $next_id = $this->table->next_id();
        $this->assertTrue($next_id > $id);
    }

    function test_parse_where_string1()
    {
        $where = 'user_id = 1';
        $this->assertEquals($where, $this->table->parse_where($where));
    }

    function test_parse_where_string2()
    {
        $where = 'user_id = ?';
        $args = array(1);
        $expected = 'user_id = 1';
        $actual = $this->table->parse_where($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parse_where_string3()
    {
        $where = 'user_id IN (?)';
        $args = array(array(1, 2, 3));
        $expected = 'user_id IN (1,2,3)';
        $actual = $this->table->parse_where($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parse_where_string4()
    {
        $where = '`user_id` = ? AND `level_ix` > ?';
        $args = array(1, 3);
        $expected = '`user_id` = 1 AND `level_ix` > 3';
        $actual = $this->table->parse_where($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parse_where_string5()
    {
        $where = '`posts.user_id` = :user_id AND `level.level_ix` > :level_ix';
        $args = array('user_id' => 2, 'level_ix' => 55);
        $expected = '`rx_posts`.`user_id` = 2 AND `level`.`level_ix` > 55';
        $actual = $this->table->parse_where($where, $args);
        $this->assertEquals($expected, $actual);
    }


    function test_parse_where_string6()
    {
        $where = '`user_id` IN (:users_id) AND `schema.level.level_ix` > :level_ix';
        $args = array('users_id' => array(1, 2, 3), 'level_ix' => 55);
        $expected = '`user_id` IN (1,2,3) AND `schema`.`level`.`level_ix` > 55';
        $actual = $this->table->parse_where($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parse_where_array1()
    {
        $where = array(
            'user_id' => 1,
            'level_ix' => 3,
        );
        $expected = '`user_id` = 1 AND `level_ix` = 3';
        $actual = $this->table->parse_where($where);
        $this->assertEquals($expected, $actual);
    }

    function test_parse_where_array2()
    {
        $where = array('(', 'user_id' => 1, 'OR', 'level_ix' => 3, ')', 'credits' => 5, 'test' => 6);
        $expected = '( `user_id` = 1 OR `level_ix` = 3 ) AND `credits` = 5 AND `test` = 6';
        $actual = $this->table->parse_where($where);
        $this->assertEquals($expected, $actual);
    }

    function test_parse_where_array3()
    {
        $where = array('(', 'user_id' => array(1,2,3), 'OR', 'level_ix' => 3, ')', 'credits' => 5, 'test' => 6);
        $expected = '( `user_id` IN (1,2,3) OR `level_ix` = 3 ) AND `credits` = 5 AND `test` = 6';
        $actual = $this->table->parse_where($where);
        $this->assertEquals($expected, $actual);
    }

    function test_parse_where_array4()
    {
        $where = array('posts.user_id' => 1, 'OR', '(' , 'level.level_ix' => 3, 'schema.mytable.credits' => 5, ')');
        $expected = '`rx_posts`.`user_id` = 1 OR ( `level`.`level_ix` = 3 AND `schema`.`mytable`.`credits` = 5 )';
        $actual = $this->table->parse_where($where);
        $this->assertEquals($expected, $actual);
    }
}
