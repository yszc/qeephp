<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 FLEA_View_SmartyHelper 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 廖宇雷 dualface@gmail.com
 * @package Core
 * @version $Id: SmartyHelper.php 861 2007-06-01 16:37:41Z dualface $
 */

/**
 * FLEA_View_SmartyHelper 扩展了 Smarty 和 TemplateLite 模版引擎，
 * 提供对 QeePHP 内置功能的直接支持。
 *
 * @package Core
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
class FLEA_View_SmartyHelper
{
    /**
     * 构造函数
     *
     * @param Smarty $tpl
     */
    public function __construct($tpl) {
        $tpl->register_function('url',          array(& $this, '_pi_func_url'));
        $tpl->register_function('webcontrol',   array(& $this, '_pi_func_webcontrol'));
        $tpl->register_function('_t',           array(& $this, '_pi_func_t'));
        $tpl->register_function('get_app_inf',  array(& $this, '_pi_func_get_app_inf'));
        $tpl->register_function('dump_ajax_js', array(& $this, '_pi_func_dump_ajax_js'));

        $tpl->register_modifier('parse_str',    array(& $this, '_pi_mod_parse_str'));
        $tpl->register_modifier('to_hashmap',   array(& $this, '_pi_mod_to_hashmap'));
        $tpl->register_modifier('col_values',   array(& $this, '_pi_mod_col_values'));
    }

    /**
     * 提供对 QeePHP url() 函数的支持
     */
    public function _pi_func_url($params)
    {
        $controllerName = isset($params['controller']) ? $params['controller'] : null;
        unset($params['controller']);
        $actionName = isset($params['action']) ? $params['action'] : null;
        unset($params['action']);
        $anchor = isset($params['anchor']) ? $params['anchor'] : null;
        unset($params['anchor']);

        $options = array('bootstrap' => isset($params['bootstrap']) ? $params['bootstrap'] : null);
        unset($params['bootstrap']);

        $args = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $args = array_merge($args, $value);
                unset($params[$key]);
            }
        }
        $args = array_merge($args, $params);

        return url($controllerName, $actionName, $args, $anchor, $options);
    }

    /**
     * 提供对 QeePHP WebControls 的支持
     */
    public function _pi_func_webcontrol($params)
    {
        $type = isset($params['type']) ? $params['type'] : 'textbox';
        unset($params['type']);
        $name = isset($params['name']) ? $params['name'] : null;
        unset($params['name']);

        $ui = FLEA::initWebControls();
        return $ui->control($type, $name, $params, true);
    }

    /**
     * 提供对 QeePHP _T() 函数的支持
     */
    public function _pi_func_t($params)
    {
        return _T($params['key'], isset($params['lang']) ? $params['lang'] : null);
    }

    /**
     * 提供对 FLEA::getAppInf() 方法的支持
     */
    public function _pi_func_get_app_inf($params)
    {
        return FLEA::getAppInf($params['key']);
    }

    /**
     * 输出 FLEA_Ajax 生成的脚本
     */
    public function _pi_func_dump_ajax_js($params)
    {
        $wrapper = isset($params['wrapper']) ? (bool)$params['wrapper'] : true;
        $ajax = FLEA::initAjax();
        /* @var $ajax FLEA_Ajax */
        return $ajax->dumpJs(true, $wrapper);
    }

    /**
     * 将字符串分割为数组
     */
    public function _pi_mod_parse_str($string)
    {
        $arr = array();
        parse_str(str_replace('|', '&', $string), $arr);
        return $arr;
    }

    /**
     * 将二维数组转换为 hashmap
     */
    public function _pi_mod_to_hashmap($data, $f_key, $f_value = '')
    {
        $arr = array();
        if (!is_array($data)) { return $arr; }
        if ($f_value != '') {
            foreach ($data as $row) {
                $arr[$row[$f_key]] = $row[$f_value];
            }
        } else {
            foreach ($data as $row) {
                $arr[$row[$f_key]] = $row;
            }
        }
        return $arr;
    }

    /**
     * 获取二维数组中指定列的数据
     */
    public function _pi_mod_col_values($data, $f_value)
    {
        $arr = array();
        if (!is_array($data)) { return $arr; }
        foreach ($data as $row) {
            $arr[] = $row[$f_value];
        }
        return $arr;
    }
}
