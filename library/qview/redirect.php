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
 * 定义 QView_Redirect 类
 *
 * @package View
 * @version $Id$
 */

/**
 * QVide_Redirect 类实现了对浏览器的重定向操作
 *
 * @package View
 * @author 起源科技 (www.qeeyuan.com)
 */
class QView_Redirect
{
    /**
     * 要重定向的 URL
     *
     * @var string
     */
    protected $url;

    /**
     * 重定向页面前的等待
     *
     * @var int
     */
    protected $delay;

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
     * 执行重定向
     */
    function execute()
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

