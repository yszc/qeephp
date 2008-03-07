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
 * 定义 QView_Adapter_Gingko 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QView_Adapter_Gingko 是 QeePHP 内置的一个模板引擎
 *
 * @package mvc
 */
class QView_Adapter_Gingko implements QView_Adapter_Interface
{
    /**
     * 模板文件所在路径
     *
     * @var string
     */
    public $template_dir = '';

    /**
     * 模板变量
     *
     * @var array
     */
    protected $vars;

    /**
     * 过滤器
     *
     * @var array
     */
    protected $filters = array();

    /**
     * 视图名称
     *
     * @var string
     */
    protected $viewname;

    function __construct($viewname = null)
    {
        $this->viewname = $viewname;

        $view_config = Q::getIni('view_config');
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
    function assign($data, $value = null)
    {
        if (is_array($data)) {
            $this->vars = array_merge($this->vars, $data);
        } else {
            $this->vars[$data] = $value;
        }
    }
    /**
     * 选择视图
     *
     * @param string $viewname
     */
    function selectView($viewname)
    {
        $this->viewname = $viewname;
    }

    /**
     * 渲染指定的视图，并输出渲染结果
     *
     * @param string $viewname
     */
    function display($viewname = null)
    {
        echo $this->fetch(is_null($viewname) ? $this->viewname : $viewname);
    }

    /**
     * 渲染指定的视图，并返回渲染结果
     *
     * @param string $viewname
     *
     * @return string
     */
    function fetch($viewname)
    {
        $viewname = str_replace('_', DS, $viewname);
        $filename = rtrim($this->template_dir, '/\\') . DS . $viewname . '.html';

        extract($this->vars);
        ob_start();
        require $filename;
        $content = ob_get_clean();
        return $this->filter($content, self::after_render);
    }

    /**
     * 清除已经设置的所有数据
     */
    function clear()
    {
        $this->vars = array();
    }

    /**
     * 对内容执行过滤器
     *
     * @param string $content
     * @param enum $filter_type
     *
     * @return string
     */
    function filter($content, $filter_type)
    {
        if (empty($this->filters[$filter_type])) { return $content; }
        foreach ($this->filters[$filter_type] as $filter_class) {
            $filter = new $filter_class();
            /* @var $filter QView_Filter_Abstract */
            $filter->run($content);
        }
        return $content;
    }
}
