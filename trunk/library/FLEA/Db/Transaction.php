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
 * 定义 FLEA_Db_Transaction 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Database
 * @version $Id$
 */

/**
 * FLEA_Db_Transaction 类封装了一个事务操作
 *
 * @package Database
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_Transaction
{
    /**
     * 数据库访问对象
     *
     * @var FLEA_Db_Driver_Abstract
     */
    protected $_dbo;

    /**
     * 指示当前是否在事务中
     *
     * @var boolean
     */
    protected $_inTran;

    /**
     * 构造函数
     *
     * @param FLEA_Db_Driver_Abstract $dbo
     */
    public function __construct(FLEA_Db_Driver_Abstract $dbo)
    {
        $this->_dbo = $dbo;
        $this->_dbo->startTrans();
        $this->_inTran = true;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        if ($this->_inTran) {
            $this->_dbo->completeTrans();
        }
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->_dbo->completeTrans();
        $this->_inTran = false;
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->_dbo->completeTrans(false);
        $this->_inTran = false;
    }
}
