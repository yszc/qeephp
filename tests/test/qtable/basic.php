<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../init.php';

class Test_QTable_Basic extends PHPUnit_Framework_TestCase
{
    /**
     * @var QTable_Base
     */
    protected $table;

    protected function setUp()
    {
        $dbo = QDBO::getConn();
        $params = array(
            'table_name' => 'posts',
            'pk'         => 'post_id',
            'dbo'        => $dbo
        );
        $this->table = new QTable_Base($params, false);
    }

    function test_find()
    {
        $select = $this->table->find();
        $this->assertType('QTable_Select', $select);
    }

    function test_find2()
    {
        $conditions = '[post_id] = :post_id AND created > :created';
        $select = $this->table->find($conditions, array('post_id' => 1, 'created' => 0));
        $actual = trim($select->toString());
        $expected = 'SELECT * FROM `q_posts` WHERE (`test`.`q_posts`.`post_id` = 1 AND created > 0)';
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

        $find = $this->table->findBySQL("SELECT * FROM {$this->table->qtable_name} WHERE post_id = {$id}");
        $this->assertType('array', $find);
        $find = reset($find);
        $this->assertEquals($row['title'], $find['title']);
        $this->assertEquals($row['body'], $find['body']);
        $this->assertFalse(empty($find['created']));
        $this->assertFalse(empty($find['updated']));
    }

    function test_createRowset()
    {
        $rowset = array();
        for ($i = 0, $max = mt_rand(1, 5); $i < $max; $i++) {
            $rowset[] = array('title' => 'Title :' . mt_rand() . ':', 'body' => 'Body :' . mt_rand());
        }

        $id_list = $this->table->createRowset($rowset);
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

        $sql = "SELECT * FROM {$this->table->qtable_name} WHERE post_id = {$id}";
        $find = $this->table->findBySQL($sql);
        $find = reset($find);

        sleep(1);

        $find['title'] = 'Title -' . mt_rand();
        $affected_rows = $this->table->update($find);
        $this->assertEquals(1, $affected_rows);

        $find2 = $this->table->findBySQL($sql);
        $find2 = reset($find2);

        $this->assertTrue($find2['updated'] > $find['updated']);
    }

    function test_updateWhere1()
    {
        $rowset = $this->table->findBySQL("SELECT COUNT(*) AS row_count FROM {$this->table->qtable_name}");
        $row = reset($rowset);
        $count = $row['row_count'];

        $pairs = array('title' => 'Title =' . mt_rand());
        $affected_rows = $this->table->updateWhere($pairs, null);

        $this->assertEquals($count, $affected_rows);
    }

    function test_updateWhere2()
    {
        $rowset = $this->table->findBySQL("SELECT COUNT(*) AS row_count FROM {$this->table->qtable_name}");
        $row = reset($rowset);
        $count = $row['row_count'];

        $pairs = array('title' => 'Title =' . mt_rand(), 'body' => 'Body =' . mt_rand());
        $affected_rows = $this->table->updateWhere($pairs, 'created > ?', array(0));
        $this->assertEquals($count, $affected_rows);
    }

    function test_incrWhere()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
            'hint' => 5,
        );
        $id = $this->table->create($row);

        $sql = "SELECT * FROM {$this->table->qtable_name} WHERE post_id = {$id}";
        $exists = $this->table->findBySQL($sql);
        $exists = reset($exists);

        sleep(1);
        $this->table->incrWhere('hint', 1, "`post_id` = {$id}");

        $row = $this->table->findBySQL($sql);
        $row = reset($row);

        $this->assertEquals($exists['hint'] + 1, $row['hint']);
        $this->assertTrue($row['updated'] > $exists['updated']);
    }

    function test_decrWhere()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
            'hint' => 9,
        );
        $id = $this->table->create($row);

        $sql = "SELECT * FROM {$this->table->qtable_name} WHERE post_id = {$id}";
        $exists = $this->table->findBySQL($sql);
        $exists = reset($exists);

        sleep(1);
        $this->table->decrWhere('hint', 2, "`post_id` = {$id}");

        $row = $this->table->findBySQL($sql);
        $row = reset($row);

        $this->assertEquals($exists['hint'] - 2, $row['hint']);
        $this->assertTrue($row['updated'] > $exists['updated']);
    }

    function test_remove()
    {
        $sql = "SELECT post_id FROM {$this->table->qtable_name} ORDER BY post_id ASC";
        $row = $this->table->findBySQL($sql);
        $row = reset($row);
        $id = $row['post_id'];

        $this->table->remove($id);
        $sql = "SELECT post_id FROM {$this->table->qtable_name} WHERE post_id = {$id}";
        $row = $this->table->findBySQL($sql);
        $this->assertTrue(empty($row));
    }

    function test_removeWhere()
    {
        $row = array('title' => 'delete', 'body' => 'delete');
        $id = $this->table->create($row);
        $affected_rows = $this->table->removeWhere("post_id = {$id}");
        $this->assertEquals(1, $affected_rows);

        $affected_rows = $this->table->removeWhere(null);
        $this->assertTrue($affected_rows > 1);
    }

    function test_nextID()
    {
        $id = $this->table->nextID();
        $next_id = $this->table->nextID();
        $this->assertTrue($next_id > $id);
    }

    function test_parseWhereString1()
    {
        $where = 'user_id = 1';
        $expected = array();
        $expected[] = $where;
        $expected[] = array();
        $this->assertEquals($expected, $this->table->parseWhere($where));
    }

    function test_parseWhereString2()
    {
        $where = 'user_id = ?';
        $args = array(1);
        $expected = array('user_id = 1', array());
        $actual = $this->table->parseWhere($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parseWhereString3()
    {
        $where = 'user_id IN (?)';
        $args = array(array(1, 2, 3));
        $expected = array('user_id IN (1,2,3)', array());
        $actual = $this->table->parseWhere($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parseWhereString4()
    {
        $where = '[user_id] = ? AND [level_ix] > ?';
        $args = array(1, 3);
        $expected = array('`test`.`q_posts`.`user_id` = 1 AND `test`.`q_posts`.`level_ix` > 3', array());
        $actual = $this->table->parseWhere($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parseWhereString5()
    {
        $where = '[posts.user_id] = :user_id AND [level.level_ix] > :level_ix';
        $args = array(array('user_id' => 2, 'level_ix' => 55));
        $expected = array('`test`.`posts`.`user_id` = 2 AND `test`.`level`.`level_ix` > 55', array());
        $actual = $this->table->parseWhere($where, $args);
        $this->assertEquals($expected, $actual);
    }


    function test_parseWhereString6()
    {
        $where = '[user_id] IN (:users_id) AND [schema.level.level_ix] > :level_ix';
        $args = array(array('users_id' => array(1, 2, 3), 'level_ix' => 55));
        $expected = array('`test`.`q_posts`.`user_id` IN (1,2,3) AND `schema`.`level`.`level_ix` > 55', array());
        $actual = $this->table->parseWhere($where, $args);
        $this->assertEquals($expected, $actual);
    }

    function test_parseWhereArray1()
    {
        $where = array(
            'user_id' => 1,
            'level_ix' => 3,
        );
        $expected = '`user_id` = 1 AND `level_ix` = 3';
        $actual = $this->table->parseWhere($where);
        $this->assertEquals($expected, $actual);
    }

    function test_parseWhereArray2()
    {
        $where = array('(', 'user_id' => 1, 'OR', 'level_ix' => 3, ')', 'credits' => 5, 'test' => 6);
        $expected = '( `user_id` = 1 OR `level_ix` = 3 ) AND `credits` = 5 AND `test` = 6';
        $actual = $this->table->parseWhere($where);
        $this->assertEquals($expected, $actual);
    }

    function test_parseWhereArray3()
    {
        $where = array('(', 'user_id' => array(1,2,3), 'OR', 'level_ix' => 3, ')', 'credits' => 5, 'test' => 6);
        $expected = '( `user_id` IN (1,2,3) OR `level_ix` = 3 ) AND `credits` = 5 AND `test` = 6';
        $actual = $this->table->parseWhere($where);
        $this->assertEquals($expected, $actual);
    }

    function test_parseWhereArray4()
    {
        $where = array('posts.user_id' => 1, 'OR', '(' , 'level.level_ix' => 3, 'schema.mytable.credits' => 5, ')');
        $expected = '`q_posts`.`user_id` = 1 OR ( `level`.`level_ix` = 3 AND `schema`.`mytable`.`credits` = 5 )';
        $actual = $this->table->parseWhere($where);
        $this->assertEquals($expected, $actual);
    }

}
