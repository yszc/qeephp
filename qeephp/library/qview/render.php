<?php

class QView_Abstract
{
    protected $viewname;
    protected $viewdata;
    protected $callbacks;

    function __construct($viewname, array $viewdata = null, array $callbacks = null)
    {
        $this->viewname = $viewname;
        $this->viewdata = is_array($viewdata) ? $viewdata : array();
        $this->callbacks = $callbacks;
    }

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
