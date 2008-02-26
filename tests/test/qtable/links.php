<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 针对表数据入口的单元测试（多表关联的 CRUD 操作）
 *
 * @package test
 * @version $Id$
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../init.php';

class Test_QTable_Links extends PHPUnit_Framework_TestCase
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

}

