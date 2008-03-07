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
 * 定义多个用户视图中的辅助函数
 *
 * @package mvc
 * @version $Id$
 */

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
 * 调用 WebControls
 *
 * @param string $type
 * @param string $name
 * @param array $attribs
 */
function control($type, $name, array $attribs = null)
{
    static $ui = null;
    if (!$ui) {
        $ui = Q::getSingleton('QWebControls');
    }
    /* @var $ui QWebControls */
    $ui->control($type, $name, $attribs);
}
