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
 * 定义 Exception_DuplicateEntry 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Exception
 * @version $Id$
 */

// {{{ includes
//require_once 'Qee/Exception.php';
// }}}

/**
 * Exception_DuplicateEntry 异常指示存在重复的实例或者键值
 *
 * @package Exception
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class Exception_DuplicateEntry extends Qee_Exception
{
    public $keyname;
    public $keyvalue;

    public function __construct($keyname, $keyvalue)
    {
        $this->keyname = $keyname;
        $this->keyvalue = $keyvalue;
        parent::__construct(self::t('Duplicate entry "%s" for key "%s"', $keyvalue, $keyname));
    }
}
