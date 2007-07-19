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
 * 定义 FLEA_Dispatcher_Simple 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id$
 */

/**
 * FLEA_Dispatcher_Simple 分析 HTTP 请求，并转发到合适的 Controller 对象处理
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_Dispatcher_Simple
{
    /**
     * 保存了请求信息的数组
     *
     * @var array
     */
    protected $_request;

    /**
     * 原始的请求信息数组
     *
     * @var array
     */
    protected $_requestBackup;

    /**
     * 构造函数
     *
     * @param array $request
     */
    public function __construct(& $request)
    {
        $this->_requestBackup =& $request;
        $controllerAccessor = strtolower(FLEA::getAppInf('controllerAccessor'));
        $actionAccessor = strtolower(FLEA::getAppInf('actionAccessor'));

        $r = array_change_key_case($request, CASE_LOWER);
        $data = array('controller' => null, 'action' => null);
        if (isset($r[$controllerAccessor])) {
            $data['controller'] = $request[$controllerAccessor];
        }
        if (isset($r[$actionAccessor])) {
            $data['action'] = $request[$actionAccessor];
        }
        $this->_request = $data;
    }

    /**
     * 从请求中分析 Controller、Action 和 Package 名字，然后执行指定的 Action 方法
     *
     * @return mixed
     */
    public function dispatching()
    {
        $controllerName = $this->getControllerName();
        $actionName = $this->getActionName();
        return $this->_executeAction($controllerName, $actionName, $this->getControllerClass($controllerName));
    }

    /**
     * 执行指定的 Action 方法
     *
     * @param string $controllerName
     * @param string $actionName
     * @param string $controllerClass
     *
     * @return mixed
     */
    protected function _executeAction($controllerName, $actionName, $controllerClass)
    {
        // 确定动作方法名
        $actionPrefix = FLEA::getAppInf('actionMethodPrefix');
        if ($actionPrefix != '') { $actionName = ucfirst($actionName); }
        $actionMethod = $actionPrefix . $actionName . FLEA::getAppInf('actionMethodSuffix');

        $controller = null;
        do {
            // 载入控制对应的类定义
            try {
                FLEA::loadClass($controllerClass);
            } catch (Exception $ex) {
                break;
            }

            // 构造控制器对象
            FLEA::setAppInf('FLEA.internal.currentControllerName', $controllerName);
            FLEA::setAppInf('FLEA.internal.currentActionName', $actionName);
            $controller = new $controllerClass($controllerName);
            if (!method_exists($controller, $actionMethod)) { break; }
            if (method_exists($controller, '__setController')) {
                $controller->__setController($controllerName);
            }
            if (method_exists($controller, '__setDispatcher')) {
                $controller->__setDispatcher($this);
            }

            // 调用 _beforeExecute() 方法
            if (method_exists($controller, '_beforeExecute')) {
                $controller->_beforeExecute($actionMethod);
            }
            // 执行 action 方法
            $ret = $controller->{$actionMethod}();
            // 调用 _afterExecute() 方法
            if (method_exists($controller, '_afterExecute')) {
                $controller->_afterExecute($actionMethod);
            }

            return $ret;
        } while (false);

        $callback = FLEA::getAppInf('dispatcherFailedCallback');
        if ($callback) {
            // 检查是否调用应用程序设置的错误处理程序
            $args = array($controllerName, $actionName, $controllerClass);
            return call_user_func_array($callback, $args);
        }

        if ($controller == null) {
            require_once 'FLEA/Exception/MissingController.php';
            throw new FLEA_Exception_MissingController($controllerName, $actionName, $this->_requestBackup, $controllerClass, $actionMethod);
        }

        require_once 'FLEA/Exception/MissingAction.php';
        throw new FLEA_Exception_MissingAction($controllerName, $actionName, $this->_requestBackup, $controllerClass, $actionMethod);
    }

    /**
     * 从请求中取得 Controller 名字
     *
     * 如果没有指定 Controller 名字，则返回配置文件中定义的默认 Controller 名字。
     *
     * @return string
     */
    public function getControllerName()
    {
        $controllerName = preg_replace('/[^a-z0-9_]+/i', '', $this->_request['controller']);
        if ($controllerName == '') {
            $controllerName = FLEA::getAppInf('defaultController');
        }
        if (FLEA::getAppInf('urlLowerChar')) {
            $controllerName = strtolower($controllerName);
        }
        return $controllerName;
    }

    /**
     * 设置要访问的控制器名字
     *
     * @param string $controllerName
     */
    public function setControllerName($controllerName)
    {
        $this->_request['controller'] = $controllerName;
    }

    /**
     * 从请求中取得 Action 名字
     *
     * 如果没有指定 Action 名字，则返回配置文件中定义的默认 Action 名字。
     *
     * @return string
     */
    public function getActionName()
    {
        $actionName = preg_replace('/[^a-z0-9]+/i', '', $this->_request['action']);
        if ($actionName == '') {
            $actionName = FLEA::getAppInf('defaultAction');
        }
        return $actionName;
    }

    /**
     * 设置要访问的动作名字
     *
     * @param string $actionName
     */
    public function setActionName($actionName)
    {
        $this->_request['action'] = $actionName;
    }

    /**
     * 返回指定控制器对应的类名称
     *
     * @param string $controllerName
     *
     * @return string
     */
    public function getControllerClass($controllerName)
    {
        $controllerClass = FLEA::getAppInf('controllerClassPrefix');
        if (FLEA::getAppInf('urlLowerChar')) {
            $controllerClass .= ucfirst(strtolower($controllerName));
        } else {
            $controllerClass .= $controllerName;
        }
        return $controllerClass;
    }
}
