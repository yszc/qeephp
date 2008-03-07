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
 * 定义 QResponse_View 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QResponse_View 封装了对模板引擎的调用
 *
 * @package mvc
 */
class QResponse_View
{
    /**
     * 要传递到视图的数据
     *
     * @var array
     */
    public $data;

    /**
     * 要渲染的视图的名称
     *
     * @var string
     */
    public $viewname;

    /*
     * 控制器
     *
     * @var QController_Abstract
     */
    public $controller;

    /**
     * 构造函数
     *
     * @param QController_Abstract $controller
     * @param string $viewname
     * @param array $data
     */
    function __construct(QController_Abstract $controller, $viewname, array $data = null)
    {
        $this->controller = $controller;
        $this->viewname = $viewname;
        $this->data = $data;
    }

    /**
     * 执行
     */
    function run()
    {
        $class_name = Q::getIni('view_adapter');
        Q::loadClass($class_name);
        $adapter = new $class_name();
        /* @var $adapter QView_Adapter_Interface */
        $adapter->assign($this->data);
        $adapter->display($this->viewname);
    }
}
