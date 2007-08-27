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
 * 定义 ___uri_filter 函数
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

/**
 * 根据应用程序设置 'urlMode' 分析 $_GET 参数
 *
 * 该函数由框架自动调用，应用程序不需要调用该函数。
 */
function ___uri_filter()
{
    static $firstTime = true;

    if (!$firstTime || !isset($_SERVER['PATH_INFO'])) { return; }
    $firstTime = false;

    $_GET = array();
    $parts = explode('/', substr($_SERVER['PATH_INFO'], 1));
    $_GET[FLEA::getAppInf('controllerAccessor')] = isset($parts[0]) ? $parts[0] : '';
    $_GET[FLEA::getAppInf('actionAccessor')] = isset($parts[1]) ? $parts[1] : '';

    $style = FLEA::getAppInf('urlParameterPairStyle');
    if ($style == '/') {
        for ($i = 2; $i < count($parts); $i += 2) {
            if (isset($parts[$i + 1])) {
                $_GET[$parts[$i]] = $parts[$i + 1];
            }
        }
    } else {
        for ($i = 2; $i < count($parts); $i++) {
            $p = $parts[$i];
            $arr = explode($style, $p);
            if (isset($arr[1])) {
                $_GET[$arr[0]] = $arr[1];
            }
        }
    }

    // 将 $_GET 合并到 $_REQUEST，
    // 有时需要使用 $_REQUEST 统一处理 url 中的 id=? 这样的参数
    $_REQUEST = array_merge($_REQUEST, $_GET);
}

___uri_filter();
