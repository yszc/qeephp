<?php

require_once dirname(__FILE__) . '/../../_common.php';

abstract class Test_DB_TableDataGateway_Abstract extends PHPUnit_Framework_TestCase
{
    /**
     * 使用的驱动类型
     *
     * @var string
     */
    protected $_driver;

    /**
     * 数据库连接信息
     *
     * @var array
     */
    protected $_dsn;

    /**
     * 测试对象
     *
     * @var FLEA_Db_TableDataGateway
     */
    protected $_table;

    public function __construct($name, $driver)
    {
        parent::__construct($name);
        $this->_driver = $driver;
        $dsnList = include TEST_SUPPORT_DIR . '/DSN.php';
        $this->_dsn = $dsnList[$driver];
    }

    public function setUp()
    {
        $opts = array('dsn' => $this->_dsn);
        $this->_table = new FLEA_Db_TableDataGateway($opts);
    }

    public function tearDown() {
        $this->_table->dbo->close();
        unset($this->_table);
        $this->_table = null;
    }
}
