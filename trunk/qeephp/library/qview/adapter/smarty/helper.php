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
 * 定义 QView_Adapter_Smarty_Helper 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QView_Adapter_Smarty_Helper 将 QeePHP 的功能集成到 Smarty 模板引擎中
 *
 * @package mvc
 */
abstract class QView_Adapter_Smarty_Helper
{
    /**
     * 为 Smarty 绑定插件
     *
     * @param Smarty $tpl
     */
    static function bind($smarty) {
        $smarty->register_function('url',           array(__CLASS__, 'func_url'));
        $smarty->register_function('control',       array(__CLASS__, 'func_webcontrol'));
        $smarty->register_function('get_int',       array(__CLASS__, 'func_get_ini'));
        $smarty->register_function('dump_ajax_js',  array(__CLASS__, 'func_dump_ajax_js'));

        $smarty->register_modifier('parse_str',     array(__CLASS__, 'pi_mod_parse_str'));
        $smarty->register_modifier('to_hashmap',    array(__CLASS__, 'pi_mod_to_hashmap'));
        $smarty->register_modifier('col_values',    array(__CLASS__, 'pi_mod_col_values'));
    }

    /**
     * 提供对 QeePHP url() 函数的支持
     */
    static function func_url($params)
    {
        $controller_name = isset($params['controller']) ? $params['controller'] : null;
        unset($params['controller']);
        $action_name = isset($params['action']) ? $params['action'] : null;
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

        return url($controller_name, $action_name, $args, $anchor, $options);
    }

    /**
     * 提供对 QeePHP QWebControls 的支持
     */
    static function func_webcontrol($params)
    {
        $type = isset($params['type']) ? $params['type'] : 'textbox';
        unset($params['type']);
        $name = isset($params['name']) ? $params['name'] : null;
        unset($params['name']);

        $ui = Q::getSingleton('QWebControls');
        $ui->control($type, $name, $params);
    }

    /**
     * 提供对 QeePHP _T() 函数的支持
     */
    static function func_t($params)
    {
        return _T($params['key'], isset($params['lang']) ? $params['lang'] : null);
    }

    /**
     * 提供对 Q::getIni() 方法的支持
     */
    static function func_get_ini($params)
    {
        return Q::getIni($params['key']);
    }

    /**
     * 输出 Ajax 生成的脚本
     */
    static function func_dump_ajax_js($params)
    {
        $wrapper = isset($params['wrapper']) ? (bool)$params['wrapper'] : true;
        $ajax =& Q::getInitAjax();
        /* @var $ajax Ajax */
        return $ajax->dumpJs(true, $wrapper);
    }

    /**
     * 将字符串分割为数组
     */
    static function pi_mod_parse_str($string)
    {
        $arr = array();
        parse_str(str_replace('|', '&', $string), $arr);
        return $arr;
    }

    /**
     * 将二维数组转换为 hashmap
     */
    static function pi_mod_to_hashmap($data, $f_key, $f_value = '')
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
    static function pi_mod_col_values($data, $f_value)
    {
        $arr = array();
        if (!is_array($data)) { return $arr; }
        foreach ($data as $row) {
            $arr[] = $row[$f_value];
        }
        return $arr;
    }
}
