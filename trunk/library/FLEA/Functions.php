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
 * 定义 QeePHP 的公共函数
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

/**
 * 重定向浏览器到指定的 URL
 *
 * @param string $url 要重定向的 url
 * @param int $delay 等待多少秒以后跳转
 * @param bool $js 指示是否返回用于跳转的 JavaScript 代码
 * @param bool $jsWrapped 指示返回 JavaScript 代码时是否使用 <script> 标签进行包装
 * @param bool $return 指示是否返回生成的 JavaScript 代码
 */
function redirect($url, $delay = 0, $js = false, $jsWrapped = true, $return = false)
{
    $delay = (int)$delay;
    if (!$js) {
        if (headers_sent() || $delay > 0) {
            echo <<<EOT
<html>
<head>
<meta http-equiv="refresh" content="{$delay};URL={$url}" />
</head>
</html>
EOT;
            exit;
        } else {
            header("Location: {$url}");
            exit;
        }
    }

    $out = '';
    if ($jsWrapped) {
        $out .= '<script language="JavaScript" type="text/javascript">';
    }
    if ($delay > 0) {
        $out .= "window.setTimeout(function () { document.location='{$url}'; }, {$delay});";
    } else {
        $out .= "document.location='{$url}';";
    }
    if ($jsWrapped) {
        $out .= '</script>';
    }

    if ($return) {
        return $out;
    }

    echo $out;
    exit;
}

/**
 * 构造 url
 *
 * 构造 url 需要提供两个参数：控制器名称和控制器动作名。如果省略这两个参数或者其中一个。
 * 则 url() 函数会使用应用程序设置中的确定的默认控制名称和默认控制器动作名。
 *
 * url() 会根据应用程序设置 urlMode 生成不同的 URL 地址：
 * - URL_STANDARD - 标准模式（默认），例如 index.php?url=Login&action=Reject&id=1
 * - URL_PATHINFO - PATHINFO 模式，例如 index.php/Login/Reject/id/1
 * - URL_REWRITE  - URL 重写模式，例如 /Login/Reject/id/1
 *
 * 生成的 url 地址，还要受下列应用程序设置的影响：
 *   - controllerAccessor
 *   - defaultController
 *   - actionAccessor
 *   - defaultAction
 *   - urlMode
 *   - urlLowerChar
 *
 * 用法：
 * <code>
 * $url = url('Login', 'checkUser');
 * // $url 现在为 ?controller=Login&action=checkUser
 *
 * $url = url('Login', 'checkUser', array('username' => 'dualface'));
 * // $url 现在为 ?controller=Login&action=checkUser&username=dualface
 *
 * $url = url('Article', 'View', array('id' => 1'), '#details');
 * // $url 现在为 ?controller=Article&action=View&id=1#details
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
function url($controllerName = null, $actionName = null, $params = null, $anchor = null, $options = null)
{
    static $baseurl = null, $currentBootstrap = null;

    // 确定当前的 URL 基础地址和入口文件名
    if ($baseurl == null) {
        $baseurl = detect_uri_base();
        $p = strrpos($baseurl, '/');
        $currentBootstrap = substr($baseurl, $p + 1);
        $baseurl = substr($baseurl, 0, $p);
    }

    // 确定生成 url 要使用的 bootstrap
    $options = (array)$options;
    if (isset($options['bootstrap'])) {
        $bootstrap = $options['bootstrap'];
    } else if ($currentBootstrap == '') {
        $bootstrap = FLEA::getAppInf('urlBootstrap');
    } else {
        $bootstrap = $currentBootstrap;
    }

    // 确定控制器和动作的名字
    if ($bootstrap != $currentBootstrap && $currentBootstrap != '') {
        $controllerName = !empty($controllerName) ? $controllerName : null;
        $actionName = !empty($actionName) ? $actionName : null;
    } else {
        $controllerName = !empty($controllerName) ? $controllerName : FLEA::getAppInf('defaultController');
        $actionName = !empty($actionName) ? $actionName : FLEA::getAppInf('defaultAction');
    }
    $lowerChar = isset($options['lowerChar']) ? $options['lowerChar'] : FLEA::getAppInf('urlLowerChar');
    if ($lowerChar) {
        $controllerName = strtolower($controllerName);
        $actionName = strtolower($actionName);
    }

    $url = '';
    $mode = isset($options['mode']) ? $options['mode'] : FLEA::getAppInf('urlMode');

    // PATHINFO 和 REWRITE 模式
    if ($mode == URL_PATHINFO || $mode == URL_REWRITE) {
        $url = $baseurl;
        if ($mode == URL_PATHINFO) {
            $url .= '/' . $bootstrap;
        }
        if ($controllerName != '' && $actionName != '') {
            $pps = isset($options['parameterPairStyle']) ? $options['parameterPairStyle'] : FLEA::getAppInf('urlParameterPairStyle');
            $url .= '/' . rawurlencode($controllerName) . '/' . rawurlencode($actionName);
            if (is_array($params) && !empty($params)) {
                $url .= '/' . encode_url_args($params, $mode, $pps);
            }
        }
        if ($anchor) { $url .= '#' . $anchor; }
        return $url;
    }

    // 标准模式
    $alwaysUseBootstrap = isset($options['alwaysUseBootstrap']) ? $options['alwaysUseBootstrap'] : FLEA::getAppInf('urlAlwaysUseBootstrap');
    $url = $baseurl . '/';

    if ($alwaysUseBootstrap || $bootstrap != FLEA::getAppInf('urlBootstrap')) {
        $url .= $bootstrap;
    }

    $parajoin = '?';
    if ($controllerName != '') {
        $url .= $parajoin . FLEA::getAppInf('controllerAccessor'). '=' . rawurlencode($controllerName);
        $parajoin = '&';
    }
    if ($actionName != '') {
        $url .= $parajoin . FLEA::getAppInf('actionAccessor') . '=' . rawurlencode($actionName);
        $parajoin = '&';
    }

    if (is_array($params) && !empty($params)) {
        $url .= $parajoin . encode_url_args($params, $mode);
    }
    if ($anchor) { $url .= '#' . $anchor; }

    return $url;
}

/**
 * 获得当前请求的 URL 地址
 *
 * 感谢 tsingson 提供该函数，用于修正 QeePHP 原有 url() 函数不能适应 CGI 模式的问题。
 *
 * @param boolean $queryMode 是否将 URL 查询参数附加在返回结果中
 *
 * @return string
 */
function detect_uri_base($queryMode = false)
{
    $aURL = array();

    // Try to get the request URL
    if (!empty($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $_SERVER['REQUEST_URI']);
        $p = strpos($_SERVER['REQUEST_URI'], ':');
        if ($p > 0 && substr($_SERVER['REQUEST_URI'], $p + 1, 2) != '//') {
            $aURL = array('path' => $_SERVER['REQUEST_URI']);
        } else {
            $aURL = parse_url($_SERVER['REQUEST_URI']);
        }
        if (isset($aURL['path']) && isset($_SERVER['PATH_INFO'])) {
            $aURL['path'] = substr($aURL['path'], 0, - strlen($_SERVER['PATH_INFO']));
        }
    }

    // Fill in the empty values
    if (empty($aURL['scheme'])) {
        if (!empty($_SERVER['HTTP_SCHEME'])) {
            $aURL['scheme'] = $_SERVER['HTTP_SCHEME'];
        } else {
            $aURL['scheme'] = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' : 'http';
        }
    }

    if (empty($aURL['host'])) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $p = strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ':');
            if ($p > 0) {
                $aURL['host'] = substr($_SERVER['HTTP_X_FORWARDED_HOST'], 0, $p);
                $aURL['port'] = substr($_SERVER['HTTP_X_FORWARDED_HOST'], $p + 1);
            } else {
                $aURL['host'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
            }
        } else if (!empty($_SERVER['HTTP_HOST'])) {
            $p = strpos($_SERVER['HTTP_HOST'], ':');
            if ($p > 0) {
                $aURL['host'] = substr($_SERVER['HTTP_HOST'], 0, $p);
                $aURL['port'] = substr($_SERVER['HTTP_HOST'], $p + 1);
            } else {
                $aURL['host'] = $_SERVER['HTTP_HOST'];
            }
        } else if (!empty($_SERVER['SERVER_NAME'])) {
            $aURL['host'] = $_SERVER['SERVER_NAME'];
        }
    }

    if (empty($aURL['port']) && !empty($_SERVER['SERVER_PORT'])) {
        $aURL['port'] = $_SERVER['SERVER_PORT'];
    }

    if (empty($aURL['path'])) {
        if (!empty($_SERVER['PATH_INFO'])) {
            $sPath = parse_url($_SERVER['PATH_INFO']);
        } else {
            $sPath = parse_url($_SERVER['PHP_SELF']);
        }
        $aURL['path'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $sPath['path']);
        unset($sPath);
    }

    // Build the URL: Start with scheme, user and pass
    $sURL = $aURL['scheme'].'://';
    if (!empty($aURL['user'])) {
        $sURL .= $aURL['user'];
        if (!empty($aURL['pass'])) {
            $sURL .= ':'.$aURL['pass'];
        }
        $sURL .= '@';
    }

    // Add the host
    $sURL .= $aURL['host'];

    // Add the port if needed
    if (!empty($aURL['port']) && (($aURL['scheme'] == 'http' && $aURL['port'] != 80) || ($aURL['scheme'] == 'https' && $aURL['port'] != 443))) {
        $sURL .= ':'.$aURL['port'];
    }

    $sURL .= $aURL['path'];

    // Add the path and the query string
    if ($queryMode && isset($aURL['query'])) {
        $sURL .= $aURL['query'];
    }

    unset($aURL);
    return $sURL;
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
 * @param enum $urlMode
 * @param string $parameterPairStyle
 *
 * @return string
 */
function encode_url_args($args, $urlMode = URL_STANDARD, $parameterPairStyle = null)
{
    $str = '';
    switch ($urlMode) {
    case URL_STANDARD:
        if (is_null($parameterPairStyle)) {
            $parameterPairStyle = '=';
        }
        $sc = '&';
        break;
    case URL_PATHINFO:
    case URL_REWRITE:
        if (is_null($parameterPairStyle)) {
            $parameterPairStyle = FLEA::getAppInf('urlParameterPairStyle');
        }
        $sc = '/';
        break;
    }

    foreach ($args as $key => $value) {
        if (is_array($value)) {
            $append = encode_url_args($value, $urlMode);
        } else {
            $append = rawurlencode($key) . $parameterPairStyle . rawurlencode($value);
        }
        if (substr($str, -1) != $sc) {
            $str .= $sc;
        }
        $str .= $append;
    }
    return substr($str, 1);
}

/**
 * 转换 HTML 特殊字符，等同于 htmlspecialchars()
 *
 * @param string $text
 *
 * @return string
 */
function h($text)
{
    return htmlspecialchars($text);
}

/**
 * 转换 HTML 特殊字符以及空格和换行符
 *
 * 空格替换为 &nbsp; ，换行符替换为 <br />。
 *
 * @param string $text
 *
 * @return string
 */
function t($text)
{
    return nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($text)));
}

/**
 * 通过 JavaScript 脚本显示提示对话框，并关闭窗口或者重定向浏览器
 *
 * 用法：
 * <code>
 * js_alert('Dialog message', '', $url);
 * // 或者
 * js_alert('Dialog message', 'window.close();');
 * </code>
 *
 * @param string $message 要显示的消息
 * @param string $after_action 显示消息后要执行的动作
 * @param string $url 重定向位置
 */
function js_alert($message = '', $after_action = '', $url = '')
{
    $out = "<script language=\"javascript\" type=\"text/javascript\">\n";
    if (!empty($message)) {
        $out .= "alert(\"";
        $out .= str_replace("\\\\n", "\\n", t2js(addslashes($message)));
        $out .= "\");\n";
    }
    if (!empty($after_action)) {
        $out .= $after_action . "\n";
    }
    if (!empty($url)) {
        $out .= "document.location.href=\"";
        $out .= $url;
        $out .= "\";\n";
    }
    $out .= "</script>";
    echo $out;
    exit;
}

/**
 * 将任意字符串转换为 JavaScript 字符串（不包括首尾的"）
 *
 * @param string $content
 *
 * @return string
 */
function t2js($content)
{
    return str_replace(array("\r", "\n"), array('', '\n'), addslashes($content));
}

/**
 * 调试和错误处理相关的全局函数
 */

/**
 * QeePHP 默认的异常处理例程
 *
 * @package Core
 *
 * @param Exception $ex
 */
function __FLEA_EXCEPTION_HANDLER(Exception $ex)
{
    if (!FLEA::getAppInf('displayErrors')) { exit; }
    if (FLEA::getAppInf('friendlyErrorsMessage')) {
        $language = FLEA::getAppInf('defaultLanguage');
        $language = preg_replace('/[^a-z0-9\-_]+/i', '', $language);

        $exclass = strtoupper(get_class($ex));
        $template = "FLEA/_Errors/{$language}/{$exclass}.php";
        if (!file_exists($template)) {
            $template = "FLEA/_Errors/{$language}/FLEA_EXCEPTION.php";
            if (!file_exists($template)) {
                $template = "FLEA/_Errors/default/FLEA_EXCEPTION.php";
            }
        }
        include $template;
    } else {
        FLEA_Exception::printEx($ex);
    }
    exit;
}

/**
 * 输出变量的内容，通常用于调试
 *
 * @package Core
 *
 * @param mixed $vars 要输出的变量
 * @param string $label
 * @param boolean $return
 */
function dump($vars, $label = '', $return = false)
{
    if (ini_get('html_errors')) {
        $content = "<pre>\n";
        if ($label != '') {
            $content .= "<strong>{$label} :</strong>\n";
        }
        $content .= htmlspecialchars(print_r($vars, true));
        $content .= "\n</pre>\n";
    } else {
        $content = $label . " :\n" . print_r($vars, true);
    }
    if ($return) { return $content; }
    echo $content;
    return null;
}

/**
 * 显示应用程序执行路径，通常用于调试
 *
 * @package Core
 *
 * @return string
 */
function dump_trace()
{
    $debug = debug_backtrace();
    $lines = '';
    $index = 0;
    for ($i = 0; $i < count($debug); $i++) {
        $file = $debug[$i];
        if ($file['file'] == '') { continue; }
        $line = "#{$index} {$file['file']}({$file['line']}): ";
        if (isset($file['class'])) {
            $line .= "{$file['class']}{$file['type']}";
        }
        $line .= "{$file['function']}(";
        if (isset($file['args']) && count($file['args'])) {
            foreach ($file['args'] as $arg) {
                $line .= gettype($arg) . ', ';
            }
            $line = substr($line, 0, -2);
        }
        $line .= ')';
        $lines .= $line . "\n";
        $index++;
    } // for
    $lines .= "#{$index} {main}\n";

    if (ini_get('html_errors')) {
        echo nl2br(str_replace(' ', '&nbsp;', $lines));
    } else {
        echo $lines;
    }
}
