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

// {{{ includes
Q::loadFile('general.php', true, Q_DIR . '/qview/helper');
// }}}

/**
 * QView_Adapter_Gingko 是 QeePHP 内置的一个模板引擎
 *
 * @package mvc
 */
class QView_Adapter_Gingko extends QView_Adapter_Abstract
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
    protected $vars = array();

    /**
     * 响应对象
     *
     * @var QResponse
     */
    protected $response;

    /**
     * 设置响应对象对应的控制器
     *
     * @var QController_Abstract
     */
    public $controller;

    function __construct(QResponse_Render $response)
    {
        $this->response = $response;
        $this->controller = $response->controller;
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
        if (empty($this->template_dir)) {
            if ($this->module) {
                $root = ROOT_DIR . DS . 'module' . DS . 'view' . DS;
            } else {
                $root = ROOT_DIR . DS . 'app' . DS . 'view' . DS;
            }
            if ($this->namespace) {
                $root .= $this->namespace . DS;
            }
            $this->template_dir = $root;
            $___filename = $this->template_dir . $viewname . '.html';
        } else {
            $___filename = rtrim($this->template_dir, '/\\') . DS . $viewname . '.html';
        }
        ob_start();
        extract($this->vars);
        require $___filename;
        $content = ob_get_clean();
        return $this->filter($content);
    }

    /**
     * 清除已经设置的所有数据
     */
    function clear()
    {
        $this->vars = array();
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
        if (isset($this->controller->helpers[$varname])) {
            $class_name = 'Helper_' . ucfirst($varname);
        } else {
            // LC_MSG: Property "%s" not defined.
            throw new QException(__('Property "%s" not defined.', $varname));
        }

        Q::loadClass($class_name, null, 'QController');
        $this->{$varname} = new $class_name($this->controller);
        return $this->{$varname};
    }
}
