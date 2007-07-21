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
 * 定义 FLEA_Db_Exception_MissingDSN 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Exception
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Exception.php';
// }}}

/**
 * FLEA_Db_Exception_MissingDSN 异常指示没有提供连接数据库需要的 dbDSN 应用程序设置
 *
 * @package Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Exception_MissingDSN extends FLEA_Exception
{
    /**
     * 构造函数
     *
     * @return FLEA_Db_Exception_MissingDSN
     */
    function FLEA_Db_Exception_MissingDSN()
    {
        $code = 0x06ff002;
        parent::FLEA_Exception(_ET($code), $code);
    }
}
