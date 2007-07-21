<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2005 - 2007 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 FLEA_Dispatcher_Auth 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Dispatcher/Simple.php';
// }}}

/**
 * FLEA_Dispatcher_Auth 分析 HTTP 请求，并转发到合适的 Controller 对象处理
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class FLEA_Dispatcher_Auth extends FLEA_Dispatcher_Simple
{
    /**
     * 用于提供验证服务的对象实例
     *
     * @var FLEA_Rbac
     */
    protected $_auth;

    /**
     * 构造函数
     *
     * @param array $request
     */
    public function __construct(& $request)
    {
        parent::__construct($request);
        $this->_auth = FLEA::getSingleton(FLEA::getAppInf('dispatcherAuthProvider'));
    }

    /**
     * 返回当前使用的验证服务对象
     *
     * @return FLEA_Rbac
     */
    public function getAuthProvider()
    {
        return $this->_auth;
    }

    /**
     * 设置要使用的验证服务对象
     *
     * @param FLEA_Rbac $auth
     */
    public function setAuthProvider($auth)
    {
        $this->_auth = $auth;
    }

    /**
     * 通过验证服务对象的 setUser 方法将用户数据保存到 session 中
     *
     * @param array $userData
     * @param mixed $rolesData
     */
    public function setUser($userData, $rolesData = null)
    {
        $this->_auth->setUser($userData, $rolesData);
    }

    /**
     * 通过验证服务对象的 getUser 方法从 session 中获取保存的用户数据
     *
     * @return array
     */
    public function getUser()
    {
        return $this->_auth->getUser();
    }

    /**
     * 通过验证服务对象的 getRolesArray 方法从 session 中获取保存的用户角色数据
     *
     * @return array
     */
    public function getUserRoles()
    {
        return $this->_auth->getRolesArray();
    }

    /**
     * 通过验证服务对象的 getUser 方法清理保存在 session 中的用户数据
     *
     * @return array
     */
    public function clearUser()
    {
        $this->_auth->clearUser();
    }

    /**
     * 执行控制器方法
     *
     * @return mixed
     */
    public function dispatching()
    {
        $controllerName  = $this->getControllerName();
        $actionName      = $this->getActionName();
        $controllerClass = $this->getControllerClass($controllerName);

        if ($this->check($controllerName, $actionName, $controllerClass)) {
            // 检查通过，执行控制器方法
            return $this->_executeAction($controllerName, $actionName, $controllerClass);
        } else {
            // 检查失败
            $callback = FLEA::getAppInf('dispatcherAuthFailedCallback');

            $rawACT = $this->getControllerACT($controllerName, $controllerClass);
            if ($rawACT == null || empty($rawACT)) { return true; }
            $ACT = $this->_auth->prepareACT($rawACT);
            $roles = $this->_auth->getRolesArray();

            if ($callback) {
                $args = array($controllerName, $actionName, $controllerClass, $ACT, $roles);
                return call_user_func_array($callback, $args);
            } else {
                require_once 'FLEA/Dispatcher/Exception/CheckFailed.php';
                throw new FLEA_Dispatcher_Exception_CheckFailed($controllerName, $actionName, $rawACT, $roles);
            }
        }
    }

    /**
     * 检查当前用户是否有权限访问指定的控制器和方法
     *
     * 验证步骤如下：
     *
     * 1、通过 authProiver 获取当前用户的角色信息；
     * 2、调用 getControllerACT() 获取指定控制器的访问控制表；
     * 3、根据 ACT 对用户角色进行检查，通过则返回 true，否则返回 false。
     *
     * @param string $controllerName
     * @param string $actionName
     * @param string $controllerClass
     *
     * @return boolean
     */
    public function check($controllerName, $actionName = null, $controllerClass = null)
    {
        if ($controllerClass == null) {
            $controllerClass = $this->getControllerClass($controllerName);
        }
        if ($actionName == null) {
            $actionName = $this->getActionName();
        }
        // 如果控制器没有提供 ACT，或者提供了一个空的 ACT，则假定允许用户访问
        $rawACT = $this->getControllerACT($controllerName, $controllerClass);
        if ($rawACT == null || empty($rawACT)) { return true; }

        $ACT = $this->_auth->prepareACT($rawACT);
        $ACT['actions'] = array();
        if (isset($rawACT['actions']) && is_array($rawACT['actions'])) {
            foreach ($rawACT['actions'] as $rawActionName => $rawActionACT) {
                $rawActionName = strtolower($rawActionName);
                $ACT['actions'][$rawActionName] = $this->_auth->prepareACT($rawActionACT);
            }
        }
        // 取出用户角色信息
        $roles = $this->_auth->getRolesArray();
        // 首先检查用户是否可以访问该控制器
        if (!$this->_auth->check($roles, $ACT)) { return false; }

        // 接下来验证用户是否可以访问指定的控制器方法
        $actionName = strtolower($actionName);
        if (!isset($ACT['actions'][$actionName])) { return true; }
        return $this->_auth->check($roles, $ACT['actions'][$actionName]);
    }

    /**
     * 获取指定控制器的访问控制表（ACT）
     *
     * @param string $controllerName
     * @param string $controllerClass
     *
     * @return array
     */
    public function getControllerACT($controllerName, $controllerClass)
    {
        $actFilename = FLEA::getFilePath($controllerClass . '.act.php');
        if (!$actFilename) {
            if (FLEA::getAppInf('autoQueryDefaultACTFile')) {
                $ACT = $this->getControllerACTFromDefaultFile($controllerName);
                if ($ACT) { return $ACT; }
            }

            if (FLEA::getAppInf('controllerACTLoadWarning')) {
                trigger_error(sprintf(_ET(0x0701006), $controllerName), E_USER_WARNING);
            }
            return FLEA::getAppInf('defaultControllerACT');
        }

        return $this->_loadACTFile($actFilename);
    }

    /**
     * 从默认 ACT 文件中载入指定控制器的 ACT
     *
     * @param string $controllerName
     */
    public function getControllerACTFromDefaultFile($controllerName)
    {
        $actFilename = realpath(FLEA::getAppInf('defaultControllerACTFile'));
        if (!$actFilename) {
            if (FLEA::getAppInf('controllerACTLoadWarning')) {
                trigger_error(FLEA_Exception::t('ACT file \'%s\' is not found.', $controllerName), E_USER_WARNING);
            }
            return FLEA::getAppInf('defaultControllerACT');
        }

        $ACT = $this->_loadACTFile($actFilename);
        if ($ACT === false) { return false; }

        $ACT = array_change_key_case($ACT, CASE_UPPER);
        $controllerName = strtoupper($controllerName);
        return isset($ACT[$controllerName]) ? $ACT[$controllerName] : FLEA::getAppInf('defaultControllerACT');
    }

    /**
     * 载入 ACT 文件
     *
     * @param string $actFilename
     *
     * @return mixed
     */
    protected function _loadACTFile($actFilename)
    {
        static $files = array();

        if (isset($files[$actFilename])) {
            return $files[$actFilename];
        }

        $ACT = include $actFilename;
        if (is_array($ACT)) {
            $files[$actFilename] = $ACT;
            return $ACT;
        }

        // 当控制器的 ACT 文件没有返回 ACT 时抛出异常
        require_once 'FLEA/Rbac/Exception/InvalidACTFile.php';
        throw new FLEA_Rbac_Exception_InvalidACTFile($actFilename, $ACT);
    }
}
