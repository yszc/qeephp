<?php

require_once 'Abstract.php';

class Test_DB_Driver_Mysqli extends Test_DB_Driver_Abstract
{
    public function __construct()
    {
        parent::__construct('mysqli', 'FLEA_Db_Driver_Mysqli');
    }

    public function test_qstr()
    {
        $actual = "This'is a qstr test:;\".";
        $qstr = $this->_dbo->qstr($actual);
        $this->assertEqual($qstr, "'This\\'is a qstr test:;\\\".'");
    }

    public function test_qtable()
    {
        $actual = 'products_has_tags';
        $qtable = $this->_dbo->qtable($actual);
        $this->assertEqual($qtable, '`products_has_tags`');
    }

    public function test_qfield()
    {
        $field = 'name';
        $table = 'products';
        $this->assertEqual($this->_dbo->qfield($field, $table), '`products`.`name`');
        $field = '*';
        $table = 'products';
        $this->assertEqual($this->_dbo->qfield($field, $table), '`products`.*');
        $field = 'title';
        $table = '';
        $this->assertEqual($this->_dbo->qfield($field, $table), '`title`');
        $field = '*';
        $this->assertEqual($this->_dbo->qfield($field, $table), '*');
    }

    public function test_qfields()
    {
        $fields = 'name,title';
        $table = 'products';
        $this->assertEqual($this->_dbo->qfields($fields, $table), '`products`.`name`, `products`.`title`');
        $fields = 'name  ,   title';
        $this->assertEqual($this->_dbo->qfields($fields, $table), '`products`.`name`, `products`.`title`');
        $fields = array('name', 'title', '');
        $this->assertEqual($this->_dbo->qfields($fields, $table), '`products`.`name`, `products`.`title`');
        $fields = array('name', 'title', '*');
        $this->assertEqual($this->_dbo->qfields($fields, ''), '`name`, `title`, *');
    }

    public function test_nextId()
    {
        $id = $this->_dbo->nextId('test_seq');
        $next = $this->_dbo->nextId('test_seq');
        $this->assertTrue($next > $id);
    }

    public function test_insertId()
    {
        $next = $this->_dbo->nextId('test_seq');
        $last = $this->_dbo->insertId();
        $this->assertEqual($next, $last);
    }

    public function test_affectedRows()
    {
        $this->_dbo->nextId('test_seq');
        $this->assertTrue($this->_dbo->affectedRows() == 1);
    }

    public function test_execute()
    {
        $sql = "SELECT id FROM game";
        $handle = $this->_dbo->execute($sql);
        $this->assertIsA($handle, 'FLEA_Db_Driver_Handle_Abstract');
        unset($handle);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $handle = $this->_dbo->execute($sql, array(970, 14));
        $this->assertIsA($handle, 'FLEA_Db_Driver_Handle_Abstract');
        unset($handle);
    }

    public function test_selectLimit()
    {
        $sql = "SELECT id FROM game";
        $handle = $this->_dbo->selectLimit($sql, 100);
        $this->assertIsA($handle, 'FLEA_Db_Driver_Handle_Abstract');
        unset($handle);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $handle = $this->_dbo->selectLimit($sql, 50, 10, array(970, 14));
        $this->assertIsA($handle, 'FLEA_Db_Driver_Handle_Abstract');
        unset($handle);
    }

    public function test_getAll()
    {
        $sql = "SELECT id FROM game";
        $rowset = $this->_dbo->getAll($sql);
        $this->assertEqual(count($rowset), 499);
        unset($rowset);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $rowset = $this->_dbo->getAll($sql, array(970, 14));
        $this->assertEqual(count($rowset), 17);
        unset($rowset);
    }
}
