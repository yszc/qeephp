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
 * 定义 QFilter_Interface 接口
 *
 * @package core
 * @version $Id$
 */

/**
 * QFilter_Interface 定义了过滤器接口
 *
 * @package core
 */
interface QFilter_Interface
{
    /**
     * 对特定内容应用过滤器
     *
     * @param string $content
     *
     * @return string
     */
    function apply($content);
}
