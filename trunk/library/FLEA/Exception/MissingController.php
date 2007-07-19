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
 * 定义 FLEA_Exception_MissingController 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Exception
 * @version $Id: MissingController.php 861 2007-06-01 16:37:41Z dualface $
 */

/**
 * FLEA_Exception_MissingController 指示请求的控制器没有找到
 *
 * @package Exception
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Exception_MissingController extends FLEA_Exception
{
    /**
     * 控制器的名字
     *
     * @var string
     */
    public $controllerName;

    /**
     * 控制器类名称
     *
     * @var string
     */
    public $controllerClass;

    /**
     * 动作名
     *
     * @var string
     */
    public $actionName;

    /**
     * 动作方法名
     *
     * @var string
     */
    public $actionMethod;

    /**
     * 调用参数
     *
     * @var mixed
     */
    public $arguments;

    /**
     * 构造函数
     *
     * @param string $controllerName
     * @param string $actionName
     * @param mixed $arguments
     * @param string $controllerClass
     * @param string $actionMethod
     */
    function __construct($controllerName, $actionName, $arguments = null, $controllerClass = null, $actionMethod = null)
    {
        parent::__construct(self::t('Controller "%s" is missing.', $controllerName));
        $this->controllerName = $controllerName;
        $this->actionName = $actionName;
        $this->arguments = $arguments;
        $this->controllerClass = $controllerClass;
        $this->actionMethod = $actionMethod;
    }
}
