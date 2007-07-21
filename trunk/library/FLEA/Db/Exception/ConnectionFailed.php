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
 * 定义 FLEA_Db_Exception_ConnectionFailed 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Database
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Exception.php';
// }}}

/**
 * FLEA_Db_Exception_ConnectionFailed 异常指示连接数据库失败
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Exception_ConnectionFailed extends FLEA_Exception
{
    public $dbtype;
    public $driver;

    public function __construct($dbtype, $driver)
    {
        parent::__construct(self::t('Connection to a "%s" Server with dirver "%s" failed.', $dbtype, $driver));
        $this->dbtype = $dbtype;
        $this->driver = $driver;
    }
}
