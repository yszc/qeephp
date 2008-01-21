<?php

/**
 * 定义 QActiveRecord_Interface 接口
 *
 * QActiveRecord_Interface 要求所有的 QActiveRecord_Abstract 继承类都必须定义一个静态的 find() 方法用于查找对象实例。
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技(www.qeeyuan.com)
 * @package core
 * @version $Id$
 */
interface QActiveRecord_Interface
{
    /**
     * 返回对象的定义
     *
     * @static
     *
     * @return array
     */
    static function define();

    /**
     * 开启一个查询
     *
     * @static
     *
     * @return QActiveRecord_Select
     */
    static function find_where();

    /**
     * 删除符合条件的记录
     *
     * @static
     */
    static function delete_where();

    /**
     * 实例化所有符合条件的对象，并调用这些对象的 destroy() 方法
     *
     * @static
     */
    static function destroy_where();
}

