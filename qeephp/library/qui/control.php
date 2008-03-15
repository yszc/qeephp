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
 * 定义 QUI_Control 类
 *
 * @package mvc
 * @version $Id$
 */

// {{{ includes
require_once Q_DIR . '/qui/control/_standard.php';
// }}}

/**
 * QUI_Control 类负责构造界面控件，以及处理控件的事件响应
 *
 * @package mvc
 */
abstract class QUI_Control
{
    /**
     * 所有已经实例化的控件
     *
     * @var array
     */
    static private $controls = array();

    /**
     * 实例化一个控件
     *
     * @param QView_Adapter_Abstract $view_adapter
     * @param string $type
     * @param string $id
     * @param array $attribs
     * @param string $namespace
     * @param string $module
     *
     * @return QUI_Control_Abstract
     */
    static function instance(QView_Adapter_Abstract $view_adapter, $type, $id, array $attribs = null, $namespace = null, $module = null)
    {
        $id = strtolower($id);
        if (isset(self::$controls[$id])) {
            // LC_MSG: 出现重复的控件 ID: "%s".
            throw new QException(__('出现重复的控件 ID: "%s".', $id));
        }

        $class_name = 'Control_' . ucfirst(strtolower($type));
        if (!class_exists($class_name, false)) {
            self::loadControl($type, $namespace, $module);
        }
        $control = new $class_name($view_adapter, $id, $attribs);
        /* @var $control QUI_Control_Abstract */
        $control->namespace = $namespace;
        $control->module = $module;
        self::$controls[$id] = $control;
        return $control;
    }

    /**
     * 载入指定控件类型的定义文件
     *
     * @param string $type
     * @param string $namespace
     * @param string $module
     */
    static private function loadControl($type, $namespace, $module)
    {
        $filename = strtolower($type) . '_control.php';
        if ($module) {
            $root = ROOT_DIR . '/module/' . $module . '/ui/';
        } else {
            $root = ROOT_DIR . '/app/ui/';
        }
        if ($namespace) {
            $root .= $namespace . '/';
        }
        $path = $root . $filename;
        if (Q::isReadable($path)) {
            require $path;
        } else {
            // LC_MSG: 指定类型 "%s" 控件的定义文件 "%s" 没有找到.
            throw new QException(__('指定类型 "%s" 控件的定义文件 "%s" 没有找到.', $type, $filename));
        }
    }

}
