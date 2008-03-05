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
 * 定义 QDB_ActiveRecord_Behavior_Interface 接口
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Behavior_Interface 接口明确了 ActiveRecord 行为插件必须实现的方法
 *
 * @package database
 */
interface QDB_ActiveRecord_Behavior_Interface extends QDB_ActiveRecord_Events
{
    /**
     * 该方法返回行为插件定义的回调事件以及扩展的方法
     *
     * @return array
     */
    function __callbacks();
}
