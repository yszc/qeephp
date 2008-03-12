<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QDispatcher 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QDispatcher 分析 HTTP 请求，并转发到合适的 QController_Abstract 继承类实例处理
 *
 * @package mvc
 */
class QDispatcher
{
    /**
     * 封装请求信息的 QRequest 对象
     *
     * @var QRequest
     */
    protected $request;

    /**
     * 用于提供验证服务的对象实例
     *
     * @var QACL
     */
    protected $acl;

    /**
     * 构造函数
     *
     * @param QRequest $request
     */
    function __construct(QRequest $request)
    {
        $this->request = $request;
        $this->acl = Q::getSingleton(Q::getIni('dispatcher_acl_class'));
    }

    /**
     * 执行控制器及动作
     *
     * @return mixed
     */
    function dispatching()
    {
        $this->executeAction(
            $this->request->getControllerName(),
            $this->request->getActionName(),
            $this->request->getNamespace(),
            $this->request->getModuleNmae()
        );
    }

    /**
     * 执行指定的 Action 方法
     *
     * @param string $controller_name
     * @param string $action_name
     * @param string $namespace
     * @param string $module
     *
     * @return mixed
     */
    function executeAction($controller_name, $action_name, $namespace, $module)
    {
        // 检查是否有权限访问
        if (!$this->checkAuthorized($controller_name, $action_name, $namespace, $module)) {
            return call_user_func_array(Q::getIni('on_access_denied'), array($controller_name, $action_name, $namespace, $module));
        }

        // 尝试载入控制器
        $class_name = 'Controller_' . ucfirst($controller_name);
        if ($module) {
            $dir = ROOT_DIR . DS . 'module' . DS . $module . DS . 'controller';
        } else {
            $dir = ROOT_DIR . DS . 'app' . DS . 'controller';
        }
        if ($namespace) {
            $dir .= DS . $namespace;
        }

        // 构造控制器对象
        $filename = $controller_name . '_controller.php';
        Q::loadClassFile($filename, array($dir), $class_name);

        $controller = new $class_name($this->request);
        /* @var $controller QController_Abstract */

        Q::register($controller, 'current_controller');
        $ret = $controller->execute($action_name, $namespace, $module);

        if (is_object($ret) && ($ret instanceof QResponse_Interface)) {
            $ret->controller = $controller;
            return $ret->run();
        }

        $data = $controller->view;
        if (is_array($data)) {
            $viewname = $controller_name . DS . $action_name;
            $response = new QResponse_Render($viewname, $data);
            $response->module = $module;
            $response->namespace = $namespace;
            $response->controller = $controller;
            return $response->run();
        } elseif (!is_null($data)) {
            echo $data;
        }
        return null;
    }

    /**
     * 检查当前用户是否有权限访问指定的控制器和动作
     *
     * @param string $controller_name
     * @param string $action_name
     * @param string $namespace
     * @param string $module
     *
     * @return boolean
     */
    function checkAuthorized($controller_name, $action_name, $namespace, $module)
    {
        // 如果控制器没有提供 ACT，或者提供了一个空的 ACT，则假定允许用户访问
        $raw_act = $this->getControllerACT($controller_name);
        if (is_null($raw_act) || empty($raw_act)) { return true; }

        $act = $this->acl->formatACT($raw_act);
        $act['actions'] = array();
        if (isset($raw_act['actions']) && is_array($raw_act['actions'])) {
            foreach ($raw_act['actions'] as $rawaction_name => $raw_actions_act) {
                if ($rawaction_name !== 'action_all') {
                    $rawaction_name = strtolower($rawaction_name);
                }
                $act['actions'][$rawaction_name] = $this->acl->formatACT($raw_actions_act);
            }
        }

        // 取出用户角色信息
        $roles = $this->getUserRoles();
        // 首先检查用户是否可以访问该控制器
        if (!$this->acl->check($roles, $act)) { return false; }

        // 接下来验证用户是否可以访问指定的控制器方法
        $action_name = strtolower($action_name);
        if (isset($act['actions'][$action_name])) {
            return $this->acl->check($roles, $act['actions'][$action_name]);
        }

        // 如果当前要访问的控制器方法没有在 act 中指定，则检查 act 中是否提供了 ACTION_ALL
        if (!isset($act['actions']['action_all'])) { return true; }
        return $this->acl->check($roles, $act['actions']['action_all']);
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
        return $this->controller_name;
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
        return $this->action_name;
    }

    /**
     * 返回当前使用的验证服务对象
     *
     * @return QACL
     */
    function getACL()
    {
        return $this->acl;
    }

    /**
     * 获取指定控制器的访问控制表（ACT）
     *
     * @param string $controller_name
     *
     * @return array
     */
    function getControllerACT($controller_name)
    {
        // 首先尝试从全局 ACT 查询控制器的 ACT
        $act = Q::getIni('global_act/' . $controller_name);
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
        $roles = Q::normalize($roles);
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

