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
 * 定义 FLEA_Db_Exception_ExtNotLoaded 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Database
 * @subpackage Exception
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Exception.php';
// }}}

/**
 * FLEA_Db_Exception_ExtNotLoaded 异常指示需要的 PHP 扩展没有载入
 *
 * @package Database
 * @subpackage Exception
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Db_Exception_ExtNotLoaded extends FLEA_Exception
{
    public $extname;
    public $dbtype;

    public function __construct($dbtype, $extname)
    {
        parent::__construct(self::t('PHP extension "%s" required by "%s".', $extname, $dbtype));
        $this->dbtype = $dbtype;
        $this->extname = $extname;
    }
}
