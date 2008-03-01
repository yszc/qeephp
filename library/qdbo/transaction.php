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
 * 定义 QDB_Transaction 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Transaction 类封装了数据库事务操作
 *
 * @package database
 */
class QDB_Transaction
{
    /**
     * 数据库访问对象
     *
     * @var QDB_Adapter_Abstract
     */
    protected $dbo;

    /**
     * 指示当前是否在事务中
     *
     * @var boolean
     */
    protected $in_tran;

    /**
     * 构造函数
     *
     * @param QDB_Adapter_Abstract $dbo
     */
    function __construct(QDB_Adapter_Abstract $dbo)
    {
        $this->dbo = $dbo;
        $this->dbo->startTrans();
        $this->in_tran = true;
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        if ($this->in_tran) {
            $this->dbo->completeTrans();
        }
    }

    /**
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commit_on_no_errors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commit_on_no_errors 参数为 false，则强制回滚事务。
     *
     * @param $commit_on_no_errors
     */
    function commit($commit_on_no_errors = true)
    {
        $this->dbo->completeTrans($commit_on_no_errors);
        $this->inTran = false;
    }

    /**
     * 回滚事务
     */
    function rollback()
    {
        $this->dbo->completeTrans(false);
        $this->inTran = false;
    }

    /**
     * 指示在调用 complete_trans() 时回滚事务
     */
    function failTrans()
    {
        $this->dbo->failTrans();
    }

    /**
     * 检查事务过程中是否出现失败的查询
     */
    function hasFailedQuery()
    {
        return $this->dbo->hasFailedQuery();
    }
}
