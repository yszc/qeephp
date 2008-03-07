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
    protected $template_dir = '';

    /**
     * 模板变量
     *
     * @var array
     */
    protected $vars = array();

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
        $filename = rtrim($this->template_dir, '/\\') . DS . $viewname . '.html';
        ob_start();
        self::_fetch($filename, $this->vars);
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

    static function _fetch($filename, array $viewdata)
    {
        extract($viewdata);
        require $filename;
    }
}
