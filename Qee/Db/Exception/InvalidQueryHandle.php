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
 * 定义 Qee_Db_Exception_InvalidQueryHandle 异常
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
 * Qee_Db_Exception_InvalidQueryHandle 指示视图操作一个无效的数据库查询句柄
 *
 * @package Database
 * @subpackage Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class Qee_Db_Exception_InvalidQueryHandle extends Qee_Exception
{
    public $dbtype;
    public $driver;

    public function __construct($dbtype, $driver)
    {
        parent::__construct(self::t('Invalid query handle, "%s" Server with dirver "%s".', $dbtype, $driver));
        $this->dbtype = $dbtype;
        $this->driver = $driver;
    }
}
