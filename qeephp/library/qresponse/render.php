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
 * 定义 QResponse_Render 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QResponse_Render 渲染输出数据
 *
 * @package mvc
 */
class QResponse_Render
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

    /**
     * 视图所属模块
     *
     * @var string
     */
    public $module;

    /**
     * 视图所属命名空间
     *
     * @var string
     */
    public $namespace;

    /**
     * 设置响应对象对应的控制器
     *
     * @var QController_Abstract
     */
    public $controller;

    /**
     * 视图布局
     *
     * @var string
     */
    protected $layouts;

    /**
     * 构造函数
     *
     * @param string $viewname
     * @param array $data
     * @param string $namespace
     * @param module
     */
    function __construct($viewname, array $data = null, $namespace = null, $module = null)
    {
        $this->viewname = $viewname;
        if (is_array($data)) {
            $this->data = $data;
        }
        $this->namespace = $namespace;
        $this->module = $module;
        $this->layouts = 'layouts_default';
    }

    /**
     * 指定要渲染的数据
     *
     * @param mixed $data
     * @param mixed $value
     */
    function assign($data, $value = null)
    {
        if (is_array($data)) {
            if (empty($this->data)) {
                $this->data = $data;
            } elseif (!empty($data)) {
                $this->data = array_merge($this->data, $data);
            }
        } else {
            $this->data[$data] = $value;
        }
    }

    /**
     * 执行
     */
    function run()
    {
        $class_name = Q::getIni('view_adapter');
        Q::loadClass($class_name);
        $adapter = new $class_name($this);
        /* @var $adapter QView_Adapter_Abstract */
        $adapter->module = $this->module;
        $adapter->namespace = $this->namespace;
        $adapter->filters[] = new QFilter_View_Macros();
        $adapter->assign($this->data);
        $content_for_layouts = $adapter->fetch($this->viewname);
        $adapter->assign('content_for_layouts', $content_for_layouts);
        $adapter->display($this->layouts);
    }
}
