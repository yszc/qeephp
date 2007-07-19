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
 * 定义 FLEA_Db_Exception_InvalidInsertID 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Exception
 * @version $Id$
 */

/**
 * FLEA_Db_Exception_InvalidInsertID 异常指示无法获取刚刚插入的记录的主键值
 *
 * @package Exception
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_Exception_InvalidInsertID extends FLEA_Exception
{
    /**
     * 构造函数
     *
     * @return FLEA_Db_Exception_InvalidInsertID
     */
    function FLEA_Db_Exception_InvalidInsertID()
    {
        $code = 0x06ff008;
        parent::FLEA_Exception(_ET($code), $code);
    }
}
