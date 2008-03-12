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
 * 定义 Helper_Control 类
 *
 * @package helper
 * @version $Id$
 */

/**
 * Helper_Control 类实现了 WebControls
 *
 * @package helper
 */
class Helper_Control
{
    /**
     * QController_Abstract 实例
     *
     * @var QController_Abstract
     */
    protected $controller;

    /**
     * 构造函数
     *
     * @param QController_Abstract $controller
     */
    function __construct(QController_Abstract $controller = null)
    {
        $this->controller = $controller;
        require_once dirname(__FILE__) . '/control/controls.standard.php';
    }

    /**
     * 载入指定的控件
     *
     * @param string $type
     */
    function load($type)
    {
        if ($this->controller->request->module_name) {
            $dir = ROOT_DIR . '/module/' . $this->controller->request->module_name . '/ui';
        } else {
            $dir = ROOT_DIR . '/app/ui';
        }
        if ($this->controller->request->namespace) {
            $dir .= '/' . $this->controller->request->namespace;
        }
        $filename = 'control.' . strtolower($type) . '.php';
        Q::loadFile($filename, true, array($dir));
    }

    /**
     * 构造一个控件
     *
     * @param string $type
     * @param string $name
     * @param array $attribs
     * @param boolean $return
     *
     * @return string
     */
    function make($type, $name, $attribs = null, $return = false)
    {
        $function = 'ctl_' . strtolower($type);
        if (!function_exists($function)) {
            $this->load($type);
        }
        if (function_exists($function)) {
            $attribs = (array)$attribs;
            if ($return) { ob_start(); }
            $ret = call_user_func_array($function, array($name, $attribs, $this));
            if ($return) {
                return ob_get_clean();
            } else {
                echo $ret;
                return '';
            }
        } else {
            // LC_MSG: Invalid QWebControls type "%s".
            throw new QException(__('Invalid QWebControls type "%s".', $type));
        }
    }

    /**
     * 构造控件的属性字符串
     *
     * @param array $attribs
     *
     * @return string
     */
    static function attribs_to_string($attribs)
    {
        $out = '';
        foreach ($attribs as $attrib => $value) {
            $out .= $attrib . '="' . str_replace('"', '\'', $value) . '" ';
        }
        return $out;
    }

    /**
     * 从属性数组中导出需要的属性
     *
     * @param array $attribs
     * @param array $req
     *
     * @return array
     */
    static function extract_attribs($attribs, $req)
    {
        $extract = array();
        foreach ($req as $attrib) {
            if (array_key_exists($attrib, $attribs)) {
                $extract[$attrib] = $attribs[$attrib];
                unset($attribs[$attrib]);
            } else {
                $extract[$attrib] = null;
            }
        }
        return $extract;
    }

    /**
     * 从属性数组中合并嵌套的数组，但消除嵌套数组中的数组
     *
     * @param array $attribs
     */
    static function merge_attribs($attribs)
    {
        $args = array();
        foreach ($attribs as $key => $arg) {
            if (is_array($arg)) {
                $args = array_merge($args, $arg);
            } else if (!is_null($arg)) {
                $args[$key] = $arg;
            }
        }
        $attribs = $args;
    }
}
