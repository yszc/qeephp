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
 * 定义 FLEA_Db_Exception_MissingLink 异常
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
 * FLEA_Db_Exception_MissingLink 异常指示尝试访问的关联不存在
 *
 * @package Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Exception_MissingLink extends FLEA_Exception
{
    var $name;

    /**
     * 构造函数
     *
     * @param $name
     *
     * @return FLEA_Db_Exception_MissingLink
     */
    function FLEA_Db_Exception_MissingLink($name)
    {
        $this->name = $name;
        $code = 0x06ff009;
        parent::FLEA_Exception(sprintf(_ET($code), $name), $code);
    }
}
