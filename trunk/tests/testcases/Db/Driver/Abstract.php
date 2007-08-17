<?php

require_once dirname(__FILE__) . '/../../_common.php';

abstract class Test_DB_Driver_Abstract extends PHPUnit_Framework_TestCase
{
    /**
     * 要测试的驱动的名字
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
     * 数据库访问对象
     *
     * @var FLEA_Db_Driver
     */
    protected $_dbo;

    public function __construct($name, $driver)
    {
        parent::__construct($name);
        $this->_driver = $driver;
        $dsnList = include TEST_SUPPORT_DIR . '/DSN.php';
        $this->_dsn = $dsnList[$driver];
    }

    public function setUp() {
        $filename = str_replace('_', DIRECTORY_SEPARATOR, $this->_driver);
        require_once $filename . '.php';
        $this->_dbo = new $this->_driver($this->_dsn);
        $this->_dbo->connect();
    }

    public function tearDown() {
        $this->_dbo->close();
        unset($this->_dbo);
        $this->_dbo = null;
    }
}
