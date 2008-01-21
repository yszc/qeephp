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
 * 定义 QController_Abstract 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * QController_Abstract 实现了一个其它控制器的超类，
 * 为开发者自己的控制器提供了一些方便的成员变量和方法
 *
 * 开发者不一定需要从这个类继承来构造自己的控制器。
 * 但从这个类派生自己的控制器可以获得一些便利性。
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 */
abstract class QController_Abstract
{
    /**
     * 当前控制器要使用的组件
     *
     * @var array|string
     */
    protected $_components = '';

    /**
     * 当前控制的名字，用于 $this->url() 方法
     *
     * @var string
     */
    private $_controllerName = null;

    /**
     * 当前调用的动作名
     *
     * @var string
     */
    private $_actionName = null;

    /**
     * 当前使用的调度器的名字
     *
     * @var QDispatcher
     */
    private $_dispatcher = null;

    /**
     * 构造函数
     */
    function __construct()
    {
        $this->_components = array_flip(normalize($this->_components));
    }

    /**
     * 执行指定的动作
     *
     * @param string $actionName
     * 
     * @return mixed
     */
    function execute($actionName = null)
    {
        $this->_controllerName = Q::getIni('q/current__controller_name');
        if (!empty($actionName)) {
            Q::setIni('q/current_action_name', $actionName);
        }
        $this->_actionName = Q::getIni('q/current_action_name');
        $actionMethod = 'action' . ucfirst(strtolower($this->_actionName));
        if (method_exists($this, $actionMethod)) {
            $this->_beforeExecute($this->_actionName);
            $ret = $this->{$actionMethod}();
            $this->_afterExecute($this->_actionName);
            return $ret;
        } else {
            throw new QController_Exception('Controller method "%s::%s()" is missing.', $this->_controllerName, $this->_actionName);
        }
    }

    /**
     * 魔法方法，用于自动加载 components
     *
     * @param string $varname
     *
     * @return mixed
     */
    function __get($varname)
    {
        if (isset($this->components[$varname])) {
            $className = 'Component_' . ucfirst($varname);
        } else {
            throw new QException(__('Property "%s" not defined.', $varname));
        }

        Q::loadClass($className);
        $this->{$varname} = new $className($this);
        return $this->{$varname};
    }

    /**
     * 获得当前使用的 Dispatcher
     *
     * @return Dispatcher
     */
    function getDispatcher()
    {
        if (empty($this->_dispatcher)) {
            $this->_dispatcher = Q::registry('current_dispatcher');
        }
        return $this->_dispatcher;
    }

    /**
     * 获得当前控制器的名字
     *
     * @return string
     */
    function getControllerName()
    {
        if (empty($this->_controllerName)) {
            $this->_controllerName = Q::getIni('q/current_controller_name');
        }
        return $this->_controllerName;
    }

    /**
     * 获得当前执行动作的名字
     *
     * @return string
     */
    function getActionName()
    {
        if (empty($this->_actionName)) {
            $this->_actionName = Q::getIni('q/current_action_name');
        }
        return $this->_actionName;
    }

    function _renderCallback(array & $viewdata, $engine, $viewname)
    {
    }

    function _beforeExecute($actionName)
    {
    }

    function _afterExecute($actionName)
    {
    }

    /**
     * 返回视图对象
     *
     * @param string $viewname
     * @param array $viewdata
     *
     * @return QView_Abstract
     */
    protected function getView($viewname, array $viewdata = null)
    {
        $className = Q::getIni('view_engine');
        Q::loadClass('view_engine');
        $engine = new $className($viewname, $viewdata);
        $engine->addCallback(array($this, '_renderCallback'));
        return $engine;
    }
    
    /**
     * 判断 HTTP 请求是否是 POST 方法
     *
     * @return boolean
     */
    protected function isPOST()
    {
        return strtolower($_SERVER['REQUEST_METHOD']) == 'post';
    }

    /**
     * 判断 HTTP 请求是否是通过 XMLHttp 发起的
     *
     * @return boolean
     */
    protected function isAJAX()
    {
        $r = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '';
        return strtolower($r) == 'xmlhttprequest';
    }
}
