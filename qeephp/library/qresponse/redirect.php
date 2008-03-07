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
 * 定义 QResponse_Redirect 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QResponse_Redirect 类封装了一个浏览器重定向操作
 *
 * @package mvc
 */
class QResponse_Redirect implements QResponse_Interface
{
    /**
     * 重定向 URL
     *
     * @var string
     */
    public $url;

    /**
     * 重定向延时（秒）
     *
     * @var int
     */
    public $delay;

    /**
     * 构造函数
     *
     * @param string $url
     * @param int $delay
     */
    function __construct($url, $delay = 0)
    {
        $this->url = $url;
        $this->delay = $delay;
    }

    /**
     * 执行
     */
    function run()
    {
        $delay = (int)$this->delay;
        $url = $this->url;
        if ($delay > 0) {
            echo <<<EOT
<html>
<head>
<meta http-equiv="refresh" content="{$delay};URL={$url}" />
</head>
</html>
EOT;
        } else {
            header("Location: {$url}");
        }
        exit;
    }
}
