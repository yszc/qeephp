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
 * 定义 Qee_Db_Exception_MissingPrimaryKey 异常
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
 * Qee_Db_Exception_MissingPrimaryKey 异常指示没有提供主键字段值
 *
 * @package Database
 * @subpackage Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class Qee_Db_Exception_MissingPrimaryKey extends Qee_Exception
{
    /**
     * 主键字段名
     *
     * @var string
     */
    public $primaryKey;

    public function __construct($pk)
    {
        parent::__construct(self::t('The value of primary key "%s" is missing.', $pk));
        $this->primaryKey = $pk;
    }
}
