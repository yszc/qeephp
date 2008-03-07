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
     * 构造函数
     *
     * @param string $viewname
     * @param array $data
     */
    function __construct($viewname, array $data = null)
    {
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
        /* @var $adapter QView_Adapter_Abstract */

        $adapter->filters[] = new QFilter_View_Macros();
        $adapter->assign($this->data);
        $adapter->display($this->viewname);
    }
}
