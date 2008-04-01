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
 * @version $Id: transaction.php 955 2008-03-16 23:52:44Z dualface $
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
    protected $in_transaction;

    /**
     * 父事务
     *
     * @var QDB_Transaction
     */
    private $parent_transaction = null;

    /**
     * 子事务
     *
     * @var QDB_Transaction
     */
    private $child_transaction = null;

    /**
     * 事务对象的 ID
     *
     * @var string
     */
    private $id;

    /**
     * 是否启用日志
     *
     * @var boolean
     */
    private static $log_enabled = false;

    /**
     * 事务对象堆栈
     *
     * @var array
     */
    private static $transactions_stack = array();

    /**
     * 构造函数
     *
     * @param QDB_Adapter_Abstract $dbo
     */
    function __construct(QDB_Adapter_Abstract $dbo)
    {
        if (!self::$log_enabled) {
            self::$log_enabled = function_exists('log_message');
        }
        if (self::$log_enabled) {
            log_message("QDB_Transaction object: {$this->id} constructed.", 'debug');
        }

        // 如果事务堆栈中已经有事务对象，则把当前对象加入前一个事务对象的链表
        $last = array_pop(self::$transactions_stack);
        /* @var $last QDB_Transaction */
        if ($last) {
            $last->child_transaction = $this;
            array_push(self::$transactions_stack, $last);
            $this->parent_transaction = $last;
        } else {
            // 只有构造堆栈中第一个事务对象时才设置异常处理函数
            set_exception_handler(array(__CLASS__, '__exceptionHandler'));
            if (self::$log_enabled) {
                log_message("QDB_Transaction object - set_exception_handler().", 'debug');
            }
        }

        $this->id = 'tran-' . count(self::$transactions_stack);
        array_push(self::$transactions_stack, $this);

        $this->dbo = $dbo;
        $this->dbo->startTrans();
        $this->in_transaction = true;
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        $this->commit();
        if (self::$log_enabled) {
            log_message("QDB_Transaction object: {$this->id} destructed.", 'debug');
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
        if (!$this->in_transaction) { return; }

        if ($this->child_transaction) {
            // 先完成子事务的提交
            $this->child_transaction->commit($commit_on_no_errors);
        }

        if (self::$log_enabled) {
            log_message("QDB_Transaction object: {$this->id} commit transaction.", 'debug');
        }

        $this->dbo->completeTrans($commit_on_no_errors);
        $this->in_transaction = false;
    }

    /**
     * 回滚事务
     */
    function rollback()
    {
        if (!$this->in_transaction) { return; }

        if ($this->child_transaction) {
            // 先完成子事务的回滚
            $this->child_transaction->rollback();
        }

        if (self::$log_enabled) {
            log_message("QDB_Transaction object: {$this->id} rollback transaction.", 'debug');
        }

        $this->dbo->completeTrans(false);
        $this->in_transaction = false;
    }

    /**
     * 指示在调用 complete_trans() 时回滚事务
     */
    function failTrans()
    {
        if (!$this->in_transaction) { return; }

        if (self::$log_enabled) {
            log_message("QDB_Transaction object: {$this->id} set failed status.", 'debug');
        }

        if ($this->child_transaction) {
            $this->child_transaction->failTrans();
        }
        $this->dbo->failTrans();
    }

    /**
     * 确定事务过程中是否出现失败的查询
     */
    function hasFailedQuery()
    {
        if (!$this->in_transaction) { return null; }
        return $this->dbo->hasFailedQuery();
    }

    /**
     * 异常处理函数
     *
     * @param Exception $ex
     */
    static function __exceptionHandler($ex)
    {
        // 如果没有父事务，则还原异常处理例程
        restore_exception_handler();

        if (self::$log_enabled) {
            log_message("QDB_Transaction - restore_exception_handler()", 'debug');
        }

        // 回滚所有事物
        $transaction = reset(self::$transactions_stack);
        /* @var $transaction QDB_Transaction */
        if (is_object($transaction)) {
            $transaction->rollback();
        }

        // 重新抛出异常
        // throw $ex;
    }
}
