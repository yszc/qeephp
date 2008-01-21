<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2007 QeePHP.org (www.qee.org)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QDispatcher 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * QDispatcher 分析 HTTP 请求，并转发到合适的 Controller 对象处理
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 */
class QDispatcher
{
    /**
     * 当前请求的控制器名字
     *
     * @var string
     */
    protected $_controllerName;

    /**
     * 当前请求的动作名字
     *
     * @var string
     */
    protected $_actionName;

    /**
     * 用于提供验证服务的对象实例
     *
     * @var QACL
     */
    protected $_acl;

    /**
     * 构造函数
     *
     * @param array $request
     */
    function __construct($request)
    {
        $c = strtolower(Q::getIni('controller_accessor'));
        $a = strtolower(Q::getIni('action_accessor'));
        $r = array_change_key_case($request, CASE_LOWER);
        $this->_controllerName = isset($r[$c]) ? $r[$c] : Q::getIni('default_controller');
        $this->_actionName = isset($r[$a]) ? $r[$a] : Q::getIni('default_action');
        $this->_controllerName = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $this->_controllerName));
        $this->_actionName = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $this->_actionName));
        $this->_acl = Q::getSingleton(Q::getIni('dispatcher_acl'));
    }

    /**
     * 执行控制器及动作
     *
     * @return mixed
     */
    function dispatching()
    {
        $ret = $this->executeAction($this->_controllerName, $this->_actionName);
        if (is_object($ret) && method_exists($ret, 'execute')) {
            return $ret->execute();
        } else {
            return $ret;
        }
    }

    /**
     * 执行指定的 Action 方法
     *
     * @param string $controllerName
     * @param string $actionName
     *
     * @return mixed
     */
    function executeAction($controllerName, $actionName)
    {
        // 检查是否有权限访问
        if (!$this->checkAuthorized($controllerName, $actionName)) {
            return call_user_func_array(Q::getIni('on_access_denied'), array($controllerName, $actionName));
        }

        Q::setIni('q/current_controller_name', $controllerName);
        Q::setIni('q/current_action_name', $actionName);
        // 初始化控制器
        $className = 'Controller_' . ucfirst($controllerName);
        $controller = new $className();
        /* @var $controller QController_Abstract */
        Q::reg($controller);
        Q::reg($controller, 'current_controller');

        return $controller->execute($actionName);
    }

    /**
     * 检查当前用户是否有权限访问指定的控制器和动作
     *
     * @param string $controllerName
     * @param string $actionName
     *
     * @return boolean
     */
    function checkAuthorized($controllerName, $actionName)
    {
        // 如果控制器没有提供 ACT，或者提供了一个空的 ACT，则假定允许用户访问
        $rawACT = $this->getControllerACT($controllerName);
        if (is_null($rawACT) || empty($rawACT)) { return true; }

        $act = $this->_acl->formatACT($rawACT);
        $act['actions'] = array();
        if (isset($rawACT['actions']) && is_array($rawACT['actions'])) {
            foreach ($rawACT['actions'] as $rawactionName => $rawActionsACT) {
                if ($rawactionName !== 'action_all') {
                    $rawactionName = strtolower($rawactionName);
                }
                $act['actions'][$rawactionName] = $this->_acl->formatACT($rawActionsACT);
            }
        }

        // 取出用户角色信息
        $roles = $this->getUserRoles();
        // 首先检查用户是否可以访问该控制器
        if (!$this->_acl->check($roles, $act)) { return false; }

        // 接下来验证用户是否可以访问指定的控制器方法
        $actionName = strtolower($actionName);
        if (isset($act['actions'][$actionName])) {
            return $this->_acl->check($roles, $act['actions'][$actionName]);
        }

        // 如果当前要访问的控制器方法没有在 act 中指定，则检查 act 中是否提供了 ACTION_ALL
        if (!isset($act['actions']['action_all'])) { return true; }
        return $this->_acl->check($roles, $act['actions']['action_all']);
    }

    /**
     * 从请求中取得 Controller 名字
     *
     * 如果没有指定 Controller 名字，则返回配置文件中定义的默认 Controller 名字。
     *
     * @return string
     */
    function getControllerName()
    {
        return $this->_controllerName;
    }

    /**
     * 从请求中取得 Action 名字
     *
     * 如果没有指定 Action 名字，则返回配置文件中定义的默认 Action 名字。
     *
     * @return string
     */
    function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * 返回当前使用的验证服务对象
     *
     * @return ACL
     */
    function getACL()
    {
        return $this->_acl;
    }

    /**
     * 获取指定控制器的访问控制表（ACT）
     *
     * @param string $controllerName
     *
     * @return array
     */
    function getControllerACT($controllerName)
    {
        // 首先尝试从全局 ACT 查询控制器的 ACT
        $act = Q::getIni('global_act/' . $controllerName);
        if ($act) {
            return $act;
        } else {
            return Q::getIni('default_act');
        }
    }

    /**
     * 将用户数据保存到 session 中
     *
     * @param array $user
     * @param mixed $roles
     */
    function setUser(array $user, $roles)
    {
        $roles = normalize($roles);
        $user[Q::getIni('acl_roles_key')] = implode(',', $roles);
        $key = Q::getIni('acl_session_key');
        $_SESSION[$key] = $user;
    }

    /**
     * 获取保存在 session 中的用户数据
     *
     * @return array
     */
    function getUser()
    {
        $key = Q::getIni('acl_session_key');
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    /**
     * 从 session 中清除用户数据
     */
    function cleanUser()
    {
        $key = Q::getIni('acl_session_key');
        unset($_SESSION[$key]);
    }

    /**
     * 获取 session 中用户信息包含的角色
     *
     * @return array
     */
    function getUserRoles()
    {
        $user = $this->getUser();
        $key = Q::getIni('acl_roles_key');
        $roles = isset($user[$key]) ? $user[$key] : '';
        return explode(',', $roles);
    }
}

