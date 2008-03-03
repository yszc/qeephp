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
     * 获得当前请求的 URL 地址
     *
     * 感谢 tsingson 提供该函数，用于修正 QeePHP 原有 url() 函数不能适应 CGI 模式的问题。
     *
     * @param boolean $query_mode 是否将 URL 查询参数附加在返回结果中
     *
     * @return string
     */
    function detectURIBase($query_mode = false)
    {
        $url_parts = array();

        // Try to get the request URL
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $arr = parse_url($_SERVER['SCRIPT_NAME']);
            $url_parts['path'] = $arr['path'];
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $_SERVER['REQUEST_URI']);
            $p = strpos($_SERVER['REQUEST_URI'], ':');
            if ($p > 0 && substr($_SERVER['REQUEST_URI'], $p + 1, 2) != '//') {
                $url_parts = array('path' => $_SERVER['REQUEST_URI']);
            } else {
                $url_parts = parse_url($_SERVER['REQUEST_URI']);
            }
            if (isset($url_parts['path']) && isset($_SERVER['PATH_INFO'])) {
                $url_parts['path'] = substr(urldecode($url_parts['path']), 0, - strlen($_SERVER['PATH_INFO']));
            }
        }

        // Fill in the empty values
        if (empty($url_parts['scheme'])) {
            if (!empty($_SERVER['HTTP_SCHEME'])) {
                $url_parts['scheme'] = $_SERVER['HTTP_SCHEME'];
            } else {
                $url_parts['scheme'] = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' : 'http';
            }
        }

        if (empty($url_parts['host'])) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $p = strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ':');
                if ($p > 0) {
                    $url_parts['host'] = substr($_SERVER['HTTP_X_FORWARDED_HOST'], 0, $p);
                    $url_parts['port'] = substr($_SERVER['HTTP_X_FORWARDED_HOST'], $p + 1);
                } else {
                    $url_parts['host'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
                }
            } else if (!empty($_SERVER['HTTP_HOST'])) {
                $p = strpos($_SERVER['HTTP_HOST'], ':');
                if ($p > 0) {
                    $url_parts['host'] = substr($_SERVER['HTTP_HOST'], 0, $p);
                    $url_parts['port'] = substr($_SERVER['HTTP_HOST'], $p + 1);
                } else {
                    $url_parts['host'] = $_SERVER['HTTP_HOST'];
                }
            } else if (!empty($_SERVER['SERVER_NAME'])) {
                $url_parts['host'] = $_SERVER['SERVER_NAME'];
            }
        }

        if (empty($url_parts['port']) && !empty($_SERVER['SERVER_PORT'])) {
            $url_parts['port'] = $_SERVER['SERVER_PORT'];
        }

        if (empty($url_parts['path'])) {
            if (!empty($_SERVER['PATH_INFO'])) {
                $sPath = parse_url($_SERVER['PATH_INFO']);
            } else {
                $sPath = parse_url($_SERVER['PHP_SELF']);
            }
            $url_parts['path'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $sPath['path']);
            unset($sPath);
        }

        // Build the URL: Start with scheme, user and pass
        $url_build = $url_parts['scheme'].'://';
        if (!empty($url_parts['user'])) {
            $url_build .= $url_parts['user'];
            if (!empty($url_parts['pass'])) {
                $url_build .= ':'.$url_parts['pass'];
            }
            $url_build .= '@';
        }

        // Add the host
        $url_build .= $url_parts['host'];

        // Add the port if needed
        if (!empty($url_parts['port']) &&
            (($url_parts['scheme'] == 'http' && $url_parts['port'] != 80)
            || ($url_parts['scheme'] == 'https' && $url_parts['port'] != 443)))
        {
            $url_build .= ':'.$url_parts['port'];
        }

        $url_build .= $url_parts['path'];

        // Add the path and the query string
        if ($query_mode && isset($url_parts['query'])) {
            $url_build .= $url_parts['query'];
        }

        unset($url_parts);
        return $url_build;
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

