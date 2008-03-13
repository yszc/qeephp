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
        Q::loadClass('QUI_Control');
    }

    /**
     * 构造一个控件
     *
     * @param string $type
     * @param string $id
     * @param array $attribs
     * @param boolean $return
     *
     * @return string
     */
    function make($type, $id, array $attribs = null, $return = false)
    {
        $control = QUI_Control::instance($type, $id, $attribs,
                $this->controller->request->namespace, $this->controller->request->module_name);
        $control->viewdata = $this->controller->view;
        return $control->render($return);
    }
}
