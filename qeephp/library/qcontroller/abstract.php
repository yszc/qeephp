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
 * 定义 QController_Abstract 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QController_Abstract 实现了一个其它控制器的超类，
 * 为开发者自己的控制器提供了一些方便的成员变量和方法
 *
 * @package mvc
 */
abstract class QController_Abstract
{
    /**
     * 当前控制器要使用的组件
     *
     * @var array|string
     */
    protected $components = '';

    /**
     * 当前控制的名字，用于 $this->url() 方法
     *
     * @var string
     */
    private $controller_name = null;

    /**
     * 当前调用的动作名
     *
     * @var string
     */
    private $action_name = null;

    /**
     * 当前使用的调度器的名字
     *
     * @var QDispatcher
     */
    private $dispatcher = null;

    /**
     * 构造函数
     */
    function __construct()
    {
        $this->components = array_flip(Q::normalize($this->components));
    }

    /**
     * 执行指定的动作
     *
     * @param string $action_name
     * 
     * @return mixed
     */
    function execute($action_name = null)
    {
        $this->controller_name = Q::getIni('mvc/current_controller_name');
        if (!empty($action_name)) {
            Q::setIni('mvc/current_action_name', $action_name);
        }
        $this->action_name = Q::getIni('mvc/current_action_name');
        $action_method = 'action' . ucfirst(strtolower($this->action_name));
        if (method_exists($this, $action_method)) {
            $this->beforeExecute($this->action_name);
            $ret = $this->{$action_method}();
            $this->afterExecute($this->action_name);
            return $ret;
        } else {
            throw new QController_Exception('Controller method "%s::%s()" is missing.', 
                      $this->controller_name, $this->action_name);
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

        Q::loadClass($className, null, 'Controller_');
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
        if (empty($this->dispatcher)) {
            $this->dispatcher = Q::registry('current_dispatcher');
        }
        return $this->dispatcher;
    }

    /**
     * 获得当前控制器的名字
     *
     * @return string
     */
    function getControllerName()
    {
        if (empty($this->controller_name)) {
            $this->controller_name = Q::getIni('mvc/current_controller_name');
        }
        return $this->controller_name;
    }

    /**
     * 获得当前执行动作的名字
     *
     * @return string
     */
    function getActionName()
    {
        if (empty($this->action_name)) {
            $this->action_name = Q::getIni('mvc/current_action_name');
        }
        return $this->action_name;
    }

    /**
     * 渲染视图输出前调用
     *
     * @param array $viewdata
     * @param QView_Adapter_Abstract $engine
     * @param string $viewname
     */
    function renderCallback(array & $viewdata, QView_Adapter_Abstract $engine, $viewname)
    {
    }

    /**
     * 执行控制器动作之前调用
     *
     * @param string $action_name
     */
    protected function beforeExecute($action_name)
    {
    }

    /**
     * 执行控制器动作之后调用
     *
     * @param string $action_name
     */
    protected function afterExecute($action_name)
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
        /* @var $engine QView_Adapter_Abstract */
        $engine->addCallback(array($this, 'renderCallback'));
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
