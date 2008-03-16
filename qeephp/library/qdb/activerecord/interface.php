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
 * 定义 QDB_ActiveRecord_Interface 接口
 *
 * @package core
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Interface 接口确定了所有 QDB_ActiveRecord_Abstract 继承类必须实现的静态方法
 *
 * @package core
 */
interface QDB_ActiveRecord_Interface
{
    /**
     * 返回对象的定义
     *
     * @static
     *
     * @return array
     */
    static function __define();

    /**
     * 开启一个查询，查找符合条件的对象或对象集合
     *
     * @static
     *
     * @return QDB_ActiveRecord_Select
     */
    static function find();

    /**
     * 实例化所有符合条件的对象，并调用这些对象的 destroy() 方法，返回成功删除的对象的数量
     *
     * @static
     *
     * @param mixed $where
     *
     * @return int
     */
    static function destroyWhere($where);

    /**
     * 对数据进行验证，返回所有未通过验证数据的名称错误信息
     *
     * @param array $data
     *
     * @return array
     */
    static function validate(array $data);
}
