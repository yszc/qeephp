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
    public



    protected $viewname;
    protected $viewdata;
    protected $callbacks;

    function execute()
    {
        $engine = $this->get_engine();

        if (is_array($this->callbacks)) {
            foreach ($this->callbacks as $callback) {
                call_user_func_array($callback, array(& $this->viewdata, $engine, $this->viewname));
            }
        }

        if (is_null($engine)) {
            $dir = Q::getIni('templates_dir');
            if (!empty($dir)) {
                $this->viewname = $dir . DIRECTORY_SEPARATOR . $this->viewname;
            }
            if (is_array($data)) {
                extract($data);
            }
            include $this->viewname;
        } else {
            if (is_array($this->viewdata)) {
                $engine->assign($this->viewdata);
            }
            $engine->display($this->viewname);
        }
    }

    function add_callback($callback)
    {
        if (!is_array($this->callbacks)) {
            $this->callbacks = array();
        }
        $this->callbacks[] = $callback;
    }

    function clean_callbacks()
    {
        $this->callbacks = array();
    }

    function get_engine($view_class = null)
    {
        if (is_null($view_class)) {
            $view_class = Q::getIni('view_engine');
        }
        if (strtolower($view_class) == 'php') {
            return null;
        }
        $engine = Q::getSingleton($view_class);
        return Q::register($engine, 'default_view_engine');
    }

    function get_viewdata()
    {
        return $this->viewdata;
    }

    function get_viewname()
    {
        return $this->viewname;
    }
}
