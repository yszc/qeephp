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
 * 定义 FLEA_Dispatcher_Exception_CheckFailed 异常
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Exception
 * @version $Id$
 */

/**
 * FLEA_Dispatcher_Exception_CheckFailed 异常指示用户试图访问的控制器方法不允许该用户访问
 *
 * @package Exception
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Dispatcher_Exception_CheckFailed extends FLEA_Exception
{
    public $controllerName;
    public $actionName;
    public $roles;
    public $act;

    /**
     * 构造函数
     *
     * @param string $controllerName
     * @param string $actionName
     * @param array $act
     * @param array $roles
     */
    function __construct($controllerName, $actionName, $act = null, $roles = null)
    {
        parent::__construct(self::t('Access to controller action "%s::%s" is denied.', $controllerName, $actionName));
        $this->controllerName = $controllerName;
        $this->actionName = $actionName;
        $this->act = $act;
        $this->roles = $roles;
    }
}
