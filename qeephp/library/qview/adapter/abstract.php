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
 * 定义 QView_Adapter_Abstract 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QView_Adapter_Abstract 是所有模板引擎适配器的基础类
 *
 * @package mvc
 */
abstract class QView_Adapter_Abstract
{
    /**
     * 过滤器集合
     *
     * @var QCol
     */
    public $filters;

    /**
     * 视图名称
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
     * 构造函数
     *
     * @param string $viewname
     */
    function __construct($viewname = null)
    {
        $this->viewname = $viewname;
        $this->filters = new QColl('QFilter_Interface');

        $view_config = (array)Q::getIni('view_config');
        foreach ($view_config as $key => $value) {
            if (isset($this->{$key})) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * 指定模板引擎要使用的数据
     *
     * @param mixed $data
     * @param mixed $value
     */
    abstract function assign($data, $value = null);

    /**
     * 渲染指定的视图，并输出渲染结果
     *
     * @param string $viewname
     */
    abstract function display($viewname = null);

    /**
     * 渲染指定的视图，并返回渲染结果
     *
     * @param string $viewname
     *
     * @return string
     */
    abstract function fetch($viewname);

    /**
     * 检查指定的视图是否存在
     *
     * @param string $viewname
     *
     * @return boolean
     */
    abstract function exists($viewname);

    /**
     * 清除已经设置的所有数据
     */
    abstract function clear();

    /**
     * 对内容执行过滤器
     *
     * @param string $content
     *
     * @return string
     */
    function filter($content)
    {
        foreach ($this->filters as $filter) {
            /* @var $filter QFilter_Interface */
            $content = $filter->apply($content);
        }
        return $content;
    }
}
