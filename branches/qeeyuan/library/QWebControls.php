<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QWebControls 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * QWebControls 类提供一组支持 QWebControls 的静态方法
 *
 * 开发者不应该自行加载该文件，而是调用 init_webcontrols() 来进行 QWebControls 的初始化。
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.0
 */
class QWebControls
{
    /**
     * 扩展的控件
     *
     * @var array
     */
    protected $extends = array();

    /**
     * 保存扩展控件的目录
     *
     * @var array
     */
    protected $dirs = array();

    /**
     * 构造函数
     *
     * @return QWebControls
     */
    function __construct()
    {
        $this->add_ext_dir(Q::getIni('webcontrols_ext_dir'));
        $this->add_ext_dir(QEE_DIR . '/_webcontrols');
        if (defined('EXPRESS_MODE')) {
            $this->load('standard');
        }
    }

    /**
     * 添加扩展搜索目录
     *
     * @param array|string $dirs
     */
    function add_ext_dir($dirs)
    {
        if (!is_array($dirs)) {
            $dirs = explode(PATH_SEPARATOR, $dirs);
        }
        if (is_array($dirs) && count($dirs) > 0) {
            $this->dirs = array_merge($this->dirs, $dirs);
        }
    }

    /**
     * 载入指定的控件库
     *
     * @param string $libname
     */
    function load($libname)
    {
        static $loaded = array();

        if (isset($loaded[$libname])) { return; }
        $filename = 'controls.' . strtolower($libname) . '.php';
        Q::load_file($filename, true, $this->dirs, true);
        $loaded[$libname] = true;
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
    function control($type, $name, $attribs = null, $return = false)
    {
        $function = 'ctl_' . strtolower($type);
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
            throw new Exception('Invalid QWebControls type : ' . $type);
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

    /**
     * 返回视图对象
     *
     * @return object
     */
    function get_view()
    {
        $view_class = Q::getIni('view_engine');
        if (strtolower($view_class) != 'php') {
            return Q::getSingleton($view_class);
        } else {
            return null;
        }
    }
}
