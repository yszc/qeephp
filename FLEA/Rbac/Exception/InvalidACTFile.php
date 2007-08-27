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
 * 定义 FLEA_Rbac_Exception_InvalidACTFile 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Exception
 * @version $Id$
 */

/**
 * FLEA_Rbac_Exception_InvalidACTFile 异常指示控制器的 ACT 文件无效
 *
 * @package Exception
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Rbac_Exception_InvalidACTFile extends FLEA_Exception
{
    /**
     * ACT 文件名
     *
     * @var string
     */
    public $actFilename;

    /**
     * 控制器名字
     *
     * @var string
     */
    public $controllerName;

    /**
     * 无效的 ACT 内容
     *
     * @var mixed
     */
    public $act;

    /**
     * 构造函数
     *
     * @param string $actFilename
     * @param string $controllerName
     * @param mixed $act
     */
    function __construct($actFilename, $act, $controllerName = null)
    {
        parent::__construct(self::t('Invalid "Access-Control-Table (ACT)" file: "%s".', $actFilename));
        $this->actFilename = $actFilename;
        $this->act = $act;
        $this->controllerName = $controllerName;
    }
}
