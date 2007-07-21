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
 * 定义 FLEA_Db_Exception_SqlQuery 异常
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
 * FLEA_Db_Exception_SqlQuery 异常指示一个 SQL 语句执行错误
 *
 * @package Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Db_Exception_SqlQuery extends FLEA_Exception
{
    /**
     * 发生错误的 SQL 语句
     *
     * @var string
     */
    public $sql;

    /**
     * 错误消息
     *
     * @var string
     */
    public $error;

    public function __construct($sql, $error)
    {
        parent::__construct(self::t('%s. with script "%s"', $error, $sql));
        $this->sql = $sql;
        $this->error = $error;
    }
}
