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
 * 定义 QDB_ActiveRecord_Validate_Exception 异常
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Validate_Exception 异常封装了 ActiveRecord 对象的验证失败事件
 *
 * @package database
 */
class QDB_ActiveRecord_Validate_Exception extends QException
{
    /**
     * 验证失败的结果
     *
     * @var array
     */
    public $validate_error;

    /**
     * 被验证的对象
     *
     * @var QDB_ActiveRecord_Abstract
     */
    public $validate_obj;

    /**
     * 构造函数
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $error
     */
    function __construct(QDB_ActiveRecord_Abstract $obj, array $error)
    {
        $this->validate_error = $error;
        $this->validate_obj = $obj;
    }
}
