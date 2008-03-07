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
 * 定义 QResponse_Interface 接口
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QResponse_Interface 定义了所有响应对象必须实现的方法
 *
 * @package mvc
 */
interface QResponse_Interface
{

    /**
     * 执行响应
     */
    function run();
}
