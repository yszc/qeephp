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
 * 定义 Qee_Db_Transaction 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Database
 * @version $Id$
 */

/**
 * Qee_Db_Transaction 类封装了一个事务操作
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class Qee_Db_Transaction
{
    /**
     * 数据库访问对象
     *
     * @var Qee_Db_Driver
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
     * @param Qee_Db_Driver $dbo
     */
    public function __construct(Qee_Db_Driver $dbo)
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
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commitOnNoErrors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commitOnNoErrors 参数为 false，则强制回滚事务。
     *
     * @param $commitOnNoErrors
     */
    public function commit($commitOnNoErrors = true)
    {
        $this->_dbo->completeTrans($commitOnNoErrors);
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

    /**
     * 指示在调用 completeTrans() 时回滚事务
     */
    public function failTrans()
    {
        $this->_dbo->failTrans();
    }

    /**
     * 指示在调用 complateTrans() 时提交事务（如果 $commitOnNoErrors 参数为 true）
     */
    public function successTrans()
    {
        $this->_dbo->successTrans();
    }

    /**
     * 检查事务过程中是否出现失败的查询
     */
    public function hasFailedQuery()
    {
        return $this->_dbo->hasFailedQuery();
    }
}
