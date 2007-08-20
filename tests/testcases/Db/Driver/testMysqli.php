<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * MySQLi 驱动的单元测试
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package tests
 * @version $Id$
 */

// {{{ includes
require_once 'Abstract.php';
// }}}

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
        $schema = '';
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
        $title = 'title' . rand();
        $qtitle = $this->_dbo->qstr($title);
        $sql = "INSERT INTO gametype (title) VALUES ({$qtitle})";
        mysqli_query($this->_dbo->handle(), $sql);
        $id = $this->_dbo->insertId();

        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $r = mysqli_query($this->_dbo->handle(), $sql);
        $row = mysqli_fetch_assoc($r);
        mysqli_free_result($r);

        $this->assertEquals($title, $row['title']);
        $this->assertEquals($id, $row['id']);

        $sql = "DELETE FROM gametype WHERE id = {$id}";
        mysqli_query($this->_dbo->handle(), $sql);
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
        $this->assertEquals(true, is_a($handle, 'FLEA_Db_Driver_Handle'));
        unset($handle);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $handle = $this->_dbo->execute($sql, array(970, 14));
        $this->assertEquals(true, is_a($handle, 'FLEA_Db_Driver_Handle'));
        unset($handle);

        $sql = "SELECT id FROM game_file WHERE title = ?";
        $handle = $this->_dbo->execute($sql, array('2005081546235.jar'));
        $this->assertEquals(true, is_a($handle, 'FLEA_Db_Driver_Handle'));
        unset($handle);
    }

    public function test_selectLimit()
    {
        $sql = "SELECT id FROM game";
        $handle = $this->_dbo->selectLimit($sql, 100);
        $this->assertEquals(true, is_a($handle, 'FLEA_Db_Driver_Handle'));
        unset($handle);

        $sql = "SELECT id FROM game WHERE id > ? AND gametype_id = ?";
        $handle = $this->_dbo->selectLimit($sql, 50, 10, array(970, 14));
        $this->assertEquals(true, is_a($handle, 'FLEA_Db_Driver_Handle'));
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

    public function test_getRow()
    {
        $sql = "SELECT * FROM gametype WHERE id = 9";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals(9, $row['id']);

        $sql = "SELECT * FROM gametype WHERE id = ?";
        $row = $this->_dbo->getRow($sql, array(9));
        $this->assertEquals(9, $row['id']);
    }

    public function test_getOne()
    {
        $sql = "SELECT id FROM gametype WHERE id = 9";
        $id = $this->_dbo->getOne($sql);
        $this->assertEquals(9, $id);

        $sql = "SELECT id FROM gametype WHERE id = ?";
        $id = $this->_dbo->getOne($sql, array(9));
        $this->assertEquals(9, $id);
    }

    public function test_getCol()
    {
        $sql = "SELECT id, title FROM gametype WHERE id > 0";
        $ids = $this->_dbo->getCol($sql, 0);
        $this->assertEquals(12, count($ids));

        $sql = "SELECT id, title FROM gametype WHERE id > ?";
        $ids = $this->_dbo->getCol($sql, 0, array(0));
        $this->assertEquals(12, count($ids));
    }

    public function test_fetchRow()
    {
        $sql = "SELECT * FROM gametype WHERE id = 9";
        $handle = $this->_dbo->execute($sql);
        $row = $handle->fetchRow();
        $this->assertEquals(9, $row['id']);
        unset($handle);

        $sql = "SELECT * FROM gametype WHERE id = ?";
        $handle = $this->_dbo->execute($sql, array(9));
        $row = $handle->fetchRow();
        $this->assertEquals(9, $row['id']);
        unset($handle);
    }

    public function test_transactionForceRollback()
    {
        $this->_dbo->startTrans();
        $sql = "INSERT INTO gametype (title) VALUES (?)";
        $this->_dbo->execute($sql, array('title' . rand()));
        $id = $this->_dbo->insertId();
        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals($id, $row['id']);
        $this->_dbo->completeTrans(false);

        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals(null, $row);
    }

    public function test_transactionRollbackWhenError()
    {
        $this->_dbo->startTrans();
        $sql = "INSERT INTO gametype (title) VALUES (?)";
        $this->_dbo->execute($sql, array('title' . rand()));
        $id = $this->_dbo->insertId();
        try {
            $sql = "SELECT id, title FROM gametype WHERE nonexist = {$id}";
            $row = $this->_dbo->getRow($sql);
        } catch (Exception $ex) {
            // 忽略这个异常
        }
        $this->_dbo->completeTrans();

        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals(null, $row);
    }

    public function test_transactionRollbackWhenError2()
    {
        $this->_dbo->startTrans();
        $sql = "INSERT INTO gametype (title) VALUES (?)";
        $this->_dbo->execute($sql, array('title' . rand()));
        $id = $this->_dbo->insertId();
        try {
            $sql = "SELECT id, title FROM gametype WHERE nonexist = ?";
            $row = $this->_dbo->getRow($sql, array(999));
        } catch (Exception $ex) {
            // 忽略这个异常
        }
        $this->_dbo->completeTrans();

        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals(null, $row);
    }

    public function test_savepoints()
    {
        $this->_dbo->savepointEnabled = true;
        $this->_dbo->startTrans();

        // savepoint 1
        $this->_dbo->startTrans();
        $sql = "INSERT INTO gametype (title) VALUES (?)";
        $this->_dbo->execute($sql, array('title' . rand()));
        $id = $this->_dbo->insertId();
        $this->_dbo->completeTrans(false);

        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals(null, $row);
        // savepoint 1 end

        // savepoint 2
        $this->_dbo->startTrans();
        $sql = "INSERT INTO gametype (title) VALUES (?)";
        $this->_dbo->execute($sql, array('title' . rand()));
        $id = $this->_dbo->insertId();
        $this->_dbo->completeTrans();

        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals($id, $row['id']);
        // savepoint 2 end

        $this->_dbo->completeTrans(false);

        $sql = "SELECT id, title FROM gametype WHERE id = {$id}";
        $row = $this->_dbo->getRow($sql);
        $this->assertEquals(null, $row);
    }
}
