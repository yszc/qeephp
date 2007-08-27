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
 * 定义 Qee_Db_Exception_InvalidInsertID 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Database
 * @subpackage Exception
 * @version $Id$
 */

// {{{ includes
require_once 'Qee/Exception.php';
// }}}

/**
 * Qee_Db_Exception_InvalidInsertID 异常指示无法获取刚刚插入的记录的主键值
 *
 * @package Database
 * @subpackage Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class Qee_Db_Exception_InvalidInsertID extends Qee_Exception
{
    /**
     * 构造函数
     *
     * @return Qee_Db_Exception_InvalidInsertID
     */
    public function __construct()
    {
        $code = 0x06ff008;
        parent::__construct($code);
    }
}
