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
 * 定义 Helper_Url 类
 *
 * @package Helper
 * @version $Id$
 */

/**
 * Helper_Url 类为控制器提供了与 URL 有关的辅助操作
 *
 * @package Helper
 * @author 起源科技 (www.qeeyuan.com)
 */
class Helper_Url
{
    /**
     * QController_Abstract 实例
     *
     * @var QController_Abstract
     */
    protected $_controller;

    /**
     * 构造函数
     *
     * @param QController_Abstract $controller
     */
    function __construct(QController_Abstract $controller)
    {
        $this->_controller = $controller;
    }

    /**
     * 构造 url
     *
     * 构造 url 需要提供两个参数：控制器名称和控制器动作名。如果省略这两个参数或者其中一个。
     * 则 url() 函数会使用应用程序设置中指定的默认控制名称和默认控制器动作名。
     *
     * url() 会根据应用程序设置 url_mode 生成不同的 URL 地址：
     * - standard - 标准模式（默认），例如 index.php?url=login&action=reject&id=1
     * - pathinfo - PATHINFO 模式，例如 index.php/login/reject/id/1
     * - rewrite  - URL 重写模式，例如 /login/reject/id/1
     *
     * 生成的 url 地址，还要受下列应用程序设置的影响：
     *   - controller_accessor
     *   - default_controller
     *   - action_accessor
     *   - default_action
     *   - url_mode
     *
     * 用法：
     * <code>
     * $url = url('login', 'checkuser');
     * // $url 现在为 index.php?controller=login&action=checkuser
     *
     * $url = url('login', 'checkuser', array('username' => 'dualface'));
     * // $url 现在为 index.php?controller=login&action=checkuser&username=dualface
     *
     * $url = url('article', 'view', array('id' => 1'), '#details');
     * // $url 现在为 index.php?controller=article&action=view&id=1#details
     * </code>
     *
     * @param string $controllerName
     * @param string $actionName
     * @param array $params
     * @param string $anchor
     * @param array $options
     *
     * @return string
     */
    function url($controllerName = null, $actionName = null, $params = null, $anchor = null, array $options = null)
    {
        static $baseurl = null, $currentBootstrap = null;

        // 确定当前的 URL 基础地址和入口文件名
        if (is_null($baseurl)) {
            $baseurl = $this->detectURIBase();
            $p = strrpos($baseurl, '/');
            $currentBootstrap = substr($baseurl, $p + 1);
            $baseurl = substr($baseurl, 0, $p);
        }

        // 确定生成 url 要使用的 bootstrap
        $options = (array)$options;
        if (isset($options['bootstrap'])) {
            $bootstrap = $options['bootstrap'];
        } else if ($currentBootstrap == '') {
            $bootstrap = Q::getIni('url_bootstrap');
        } else {
            $bootstrap = $currentBootstrap;
        }

        // 确定控制器和动作的名字
        $controllerName = empty($controller_name) ?
            strtolower(Q::getIni('default_controller')) :
            strtolower($controller_name);
        $actionName = empty($action_name) ?
            strtolower(Q::getIni('default_action')) :
            strtolower($action_name);

        $url = '';
        $mode = isset($options['mode']) ? $options['mode'] : Q::getIni('url_mode');
        $mode = strtolower($mode);

        // PATHINFO 和 REWRITE 模式
        if ($mode == 'pathinfo' || $mode == 'rewrite') {
            $url = $baseurl;
            if ($mode == 'pathinfo') {
                $url .= '/' . $bootstrap;
            }
            if ($controllerName != '' && $actionName != '') {
                $url .= '/' . rawurlencode($controllerName);
                $url .= '/' . rawurlencode($actionName);
                if (is_array($params) && !empty($params)) {
                    $url .= '/' . $this->encodeArgs($params, $mode);
                }
            }
            if ($anchor) { $url .= '#' . $anchor; }
            return $url;
        }

        // 标准模式
        $url = $baseurl . '/' . $bootstrap;
        $url .= '?' . Q::getIni('controller_accessor'). '=' . $controllerName;
        $url .= '&' . Q::getIni('action_accessor') . '=' . $actionName;

        if (is_array($params) && !empty($params)) {
            $url .= '&' . $this->encodeArgs($params, $mode);
        }
        if ($anchor) { $url .= '#' . $anchor; }

        return $url;
    }

    /**
     * 将数组转换为可通过 url 传递的字符串连接
     *
     * 用法：
     * <code>
     * $string = encode_url_args(array('username' => 'dualface', 'mode' => 'md5'));
     * // $string 现在为 username=dualface&mode=md5
     * </code>
     *
     * @param array $args
     * @param enum $url_mode
     *
     * @return string
     */
    function encodeArgs($args, $url_mode = 'standard')
    {
        $str = '';
        switch ($url_mode) {
        case 'standard':
            $pair = '=';
            $sc = '&';
            break;
        case 'pathinfo':
        case 'rewrite':
            $pair = '/';
            $sc = '/';
            break;
        }

        foreach ($args as $key => $value) {
            if (is_null($value) || $value === '') { continue; }
            if (is_array($value)) {
                $append = encode_url_args($value, $url_mode);
            } else {
                $append = rawurlencode($key) . $pair . rawurlencode($value);
            }
            if (substr($str, -1) != $sc) {
                $str .= $sc;
            }
            $str .= $append;
        }
        return substr($str, 1);
    }
}

