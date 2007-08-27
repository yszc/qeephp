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
 * 定义 Qee_Db_Exception_InvalidDSN 异常
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
 * Qee_Db_Exception_InvalidDSN 异常指示没有提供有效的 DSN 设置
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @subpackage Exception
 * @version 1.0
 */
class Qee_Db_Exception_InvalidDSN extends Qee_Exception
{
    /**
     * 无效的 DSN 数据
     *
     * @var mixed
     */
    public $dsn;

    public function __construct($dsn)
    {
        unset($dsn['password']);
        parent::t('Invalid DSN(Data-Source-Name).');
        $this->dsn = $dsn;
    }
}
