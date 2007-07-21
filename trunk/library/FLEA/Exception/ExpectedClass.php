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
 * 定义 FLEA_Exception_ExpectedClass 异常
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
 * FLEA_Exception_ExpectedClass 异常指示需要的类没有找到
 *
 * @package Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Exception_ExpectedClass extends FLEA_Exception
{
    /**
     * 类名称
     *
     * @var string
     */
    public $className;

    /**
     * 类定义文件
     *
     * @var string
     */
    public $classFilename;

    function __construct($className, $filename)
    {
        parent::__construct(self::t('File "%s" was loaded but class "%s" was not found in the file.', $className, $file));
        $this->className = $className;
        $this->classFilename = $filename;
    }
}
