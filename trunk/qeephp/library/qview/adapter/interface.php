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
 * 定义 QView_Adapter_Interface 接口
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QView_Adapter_Interface 定义了模板引擎适配器必须实现的方法
 *
 * @package mvc
 */
interface QView_Adapter_Interface
{
    /**
     * 过滤器类型
     */
    const before_render     = 'before_render';
    const after_render      = 'after_render';

    /**
     * 指定模板引擎要使用的数据
     *
     * @param mixed $data
     * @param mixed $value
     */
    function assign($data, $value = null);

    /**
     * 选择视图
     *
     * @param string $viewname
     */
    function selectView($viewname);

    /**
     * 渲染指定的视图，并输出渲染结果
     *
     * @param string $viewname
     */
    function display($viewname = null);

    /**
     * 渲染指定的视图，并返回渲染结果
     *
     * @param string $viewname
     *
     * @return string
     */
    function fetch($viewname);

    /**
     * 清除已经设置的所有数据
     */
    function clear();

    /**
     * 对内容执行过滤器
     *
     * @param string $content
     * @param enum $filter_type
     *
     * @return string
     */
    function filter($content, $filter_type);
}
