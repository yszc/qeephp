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
 * 定义 FLEA_Db_Exception_UseDatabaseFailed 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Database
 * @subpackage Exception
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Exception.php';
// }}}

/**
 * FLEA_Db_Exception_UseDatabaseFailed 异常指示选择要使用的数据库时出错
 *
 * @package Database
 * @subpackage Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Exception_UseDatabaseFailed extends FLEA_Exception
{
    public $dbtype;
    public $driver;
    public $database;

    public function __construct($dbtype, $driver, $database)
    {
        parent::__construct(self::t('Switch to use database "%s" on a "%s" Server with dirver "%s" failed.', $database, $dbtype, $driver));
        $this->dbtype = $dbtype;
        $this->driver = $driver;
        $this->database = $database;
    }
}
