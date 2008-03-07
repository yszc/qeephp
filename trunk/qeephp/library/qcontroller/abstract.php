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
     * 当前控制器要使用的助手
     *
     * @var array|string
     */
    protected $helper = '';

    /**
     * 封装请求的对象
     *
     * @var QRequest
     */
    protected $request;

    /**
     * 构造函数
     */
    function __construct(QRequest $request)
    {
        $this->request = $request;
        $this->helper = array_flip(Q::normalize($this->helper));
    }

    /**
     * 执行指定的动作
     *
     * @param string $action_name
     *
     * @return mixed
     */
    function execute($action_name)
    {
        $action_method = 'action' . ucfirst(strtolower($action_name));
        if (method_exists($this, $action_method)) {
            $this->beforeExecute($action_name);
            $ret = $this->{$action_method}();
            return $this->afterExecute($action_name, $ret);
        } else {
            throw new QController_Exception(__('Controller method "%s::%s()" is missing.',
                                            $this->getControllerName(), $action_name));
        }
    }

    /**
     * 魔法方法，用于自动加载 helper
     *
     * @param string $varname
     *
     * @return mixed
     */
    function __get($varname)
    {
        if (isset($this->helper[$varname])) {
            $class_name = 'Helper_' . ucfirst($varname);
        } else {
            // LC_MSG: Property "%s" not defined.
            throw new QException(__('Property "%s" not defined.', $varname));
        }

        Q::loadClass($class_name, null, 'Controller_');
        $this->{$varname} = new $class_name($this);
        return $this->{$varname};
    }

    /**
     * 获得当前使用的 Dispatcher
     *
     * @return Dispatcher
     */
    function getDispatcher()
    {
        return Q::registry('current_dispatcher');
    }

    /**
     * 执行控制器动作之前调用
     *
     * @param string $action_name
     */
    protected function beforeExecute($action_name)
    {
        return true;
    }

    /**
     * 执行控制器动作之后调用
     *
     * @param string $action_name
     * @param mixed $ret
     *
     * @return mixed
     */
    protected function afterExecute($action_name, $ret)
    {
        return $ret;
    }
}
