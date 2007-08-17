<?php

require_once 'Abstract.php';

class Test_DB_Driver_Mysqli extends Test_DB_Driver_Abstract
{
    public function __construct()
    {
        parent::__construct(__CLASS__, 'FLEA_Db_Driver_Mysqli');
        if (!function_exists('mysqli_init')) {
            dl('php_mysqli.' . PHP_SHLIB_SUFFIX);
        }
    }

    public function test_qstr()
    {
        $actual = "This'is a qstr test:;\".";
        $qstr = $this->_dbo->qstr($actual);
        $this->assertEquals("'This\\'is a qstr test:;\\\".'", $qstr);
    }

    public function test_qtable()
    {
        $tableName = 'products_has_tags';
        $schema = 'test_db';
        $qtable = $this->_dbo->qtable($tableName, $schema);
        $this->assertEquals('`test_db`.`products_has_tags`', $qtable);
        $qtable = $this->_dbo->qtable($tableName);
        $this->assertEquals('`products_has_tags`', $qtable);
    }

    public function test_qfield()
    {
        $field = 'name';
        $table = 'products';
        $this->assertEquals('`products`.`name`', $this->_dbo->qfield($field, $table));
        $field = '*';
        $table = 'products';
        $this->assertEquals('`products`.*', $this->_dbo->qfield($field, $table));
        $field = 'title';
        $table = '';
        $this->assertEquals('`title`', $this->_dbo->qfield($field, $table));
        $field = '*';
        $this->assertEquals('*', $this->_dbo->qfield($field, $table));

        $field = 'name';
        $table = 'products';
        $schema = 'test_db';
        $this->assertEquals('`test_db`.`products`.`name`', $this->_dbo->qfield($field, $table, $schema));
        $field = '*';
        $table = 'products';
        $this->assertEquals('`test_db`.`products`.*', $this->_dbo->qfield($field, $table, $schema));
        $field = 'title';
        $table = '';
        $this->assertEquals('`title`', $this->_dbo->qfield($field, $table, $schema));
        $field = '*';
        $this->assertEquals('*', $this->_dbo->qfield($field, $table, $schema));
    }

    public function test_qfields()
    {
        $fields = 'name,title';
        $table = 'products';
        $this->assertEquals('`products`.`name`, `products`.`title`', $this->_dbo->qfields($fields, $table));
        $fields = 'name  ,   title';
        $this->assertEquals('`products`.`name`, `products`.`title`', $this->_dbo->qfields($fields, $table));
        $fields = array('name', 'title', '');
        $this->assertEquals('`products`.`name`, `products`.`title`', $this->_dbo->qfields($fields, $table));
        $fields = array('name', 'title', '*');
        $this->assertEquals('`name`, `title`, *', $this->_dbo->qfields($fields, ''));

        $fields = 'name,title';
        $table = 'products';
        $schema = 'test_db';
        $this->assertEquals('`test_db`.`products`.`name`, `test_db`.`products`.`title`', $this->_dbo->qfields($fields, $table, $schema));
        $fields = 'name  ,   title';
        $this->assertEquals('`test_db`.`products`.`name`, `test_db`.`products`.`title`', $this->_dbo->qfields($fields, $table, $schema));
        $fields = array('name', 'title', '*');
        $arr = $this->_dbo->qfields($fields, $table, $schema, true);
        $this->assertEquals(array('`test_db`.`products`.`name`', '`test_db`.`products`.`title`', '`test_db`.`products`.*'), $arr);
    }

    public function test_sequence()
    {
        $id = $this->_dbo->nextId('test_seq');
        $next = $this->_dbo->nextId('test_seq');
        $this->assertTrue($next > $id, "expected \$next > \$id, \$next = {$next}, \$id = {$id}");
        $this->_dbo->dropSeq('test_seq');

        $this->_dbo->createSeq('test_seq2', 5);
        $id = $this->_dbo->insertId();
        $this->assertEquals(5, $id);
        $this->_dbo->dropSeq('test_seq2');
    }

    public function test_insertId()
    {
        srand(time());
        $title = $this->_dbo->qstr('title' . rand());
        $sql = "INSERT INTO gametype (title) VALUES ({$title})";
        mysqli_query($this->_dbo->handle(), $sql);
        $id = $this->_dbo->insertId();

        $sql = "SELECT title FROM gametype WHERE id = {$id}";
        $r = mysqli_query($this->_dbo->handle(), $sql);
        $row = mysqli_fetch_assoc($r);
        mysqli_free_result($r);

        $this->assertEquals($title, $row['title']);
    }

    public function test_affectedRows()
    {
        $this->_dbo->nextId('test_seq');
        $this->assertTrue($this->_dbo->affectedRows() == 1);
        $this->_dbo->dropSeq('test_seq');
    }

    public function test_execute()
    {
        $sql = "SELECT id FROM game";
        $handle = $this->_dbo->execute($sql);
        $this->assertType('FLEA_Db_Driver_Handle_Abstract', $handle);
        unset($handle);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $handle = $this->_dbo->execute($sql, array(970, 14));
        $this->assertType('FLEA_Db_Driver_Handle_Abstract', $handle);
        unset($handle);

        $sql = "SELECT id FROM game_file WHERE title = ?";
        $handle = $this->_dbo->execute($sql, array('2005081546235.jar'));
        $this->assertType('FLEA_Db_Driver_Handle_Abstract', $handle);
        unset($handle);
    }

    public function test_selectLimit()
    {
        $sql = "SELECT id FROM game";
        $handle = $this->_dbo->selectLimit($sql, 100);
        $this->assertType('FLEA_Db_Driver_Handle_Abstract', $handle);
        unset($handle);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $handle = $this->_dbo->selectLimit($sql, 50, 10, array(970, 14));
        $this->assertType('FLEA_Db_Driver_Handle_Abstract', $handle);
        unset($handle);
    }

    public function test_getAll()
    {
        $sql = "SELECT id FROM game";
        $rowset = $this->_dbo->getAll($sql);
        $this->assertEquals(499, count($rowset));
        unset($rowset);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $rowset = $this->_dbo->getAll($sql, array(970, 14));
        $this->assertEquals(17, count($rowset));
        unset($rowset);
    }

    public function testGetAllWithFeildRefs()
    {
        $sql = "SELECT * FROM game WHERE id > ?";
    }
}
