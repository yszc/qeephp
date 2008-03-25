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
 * 定义 QDB_ActiveRecord_Behavior_Abstract 类
 *
 * @package database
 * @version $Id: interface.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QDB_ActiveRecord_Behavior_Abstract 抽象类是所有行为插件的基础类
 *
 * @package database
 */
abstract class QDB_ActiveRecord_Behavior_Abstract
{
    /**
     * 预定义的事件
     */
    const after_find                  = 'after_find';                   // 查询后
    const after_initialize            = 'after_initialize';             // 初始化后

    const before_save                 = 'before_save';                  // 保存之前
    const after_save                  = 'after_save';                   // 保存之后

    const before_create               = 'before_create';                // 创建之前
    const after_create                = 'after_create';                 // 创建之后

    const before_update               = 'before_update';                // 更新之前
    const after_update                = 'after_update';                 // 更新之后

    const before_validation           = 'before_validation';            // 验证之前
    const after_validation            = 'after_validation';             // 验证之后

    const before_validation_on_create = 'before_validation_on_create';  // 创建记录验证之前
    const after_validation_on_create  = 'after_validation_on_create';   // 创建记录验证之后

    const before_validation_on_update = 'before_validation_on_update';  // 更新记录验证之前
    const after_validation_on_update  = 'after_validation_on_update';   // 更新记录验证之后

    const before_destroy              = 'before_destroy';               // 销毁之前
    const after_destroy               = 'after_destroy';                // 销毁之后

    /**
     * 其他类型 callback
     */
    const custom_callback             = 'custom_callback';              // 行为插件自定义方法

    /**
     * 属性方法
     */
    const getter                      = 'getter';                       // 读属性
    const setter                      = 'setter';                       // 写属性

    /**
     * 插件的设置信息
     *
     * @var array
     */
    protected $settings = array();

    /**
     * 插件绑定到了哪一个 ActiveRecord 类
     *
     * @var string
     */
    protected $class;

    /**
     * ActiveRecord 类的反射信息
     *
     * @var array
     */
    protected $ref;

    /**
     * 该方法返回行为插件定义的回调事件以及扩展的方法
     *
     * @return array
     */
    abstract function __callbacks();

    /**
     * 构造函数
     *
     * @param string $class
     * @param array $settings
     */
    function __construct($class, array $settings)
    {
        $this->class = $class;
        foreach ($this->settings as $key => $value) {
            if (!empty($settings[$key])) {
                $this->settings[$key] = $settings[$key];
            }
        }
    }

    /**
     * 插件绑定完成后调用
     *
     * @param array $ref
     */
    function __bindFinished(array & $ref)
    {
        $this->ref =& $ref;
    }

    /**
     * 取得字段名的别名
     *
     * @param string $field
     *
     * @return string
     */
    protected function alias_name($field)
    {
        return $this->ref['alias'][$field];
    }

    /**
     * 取得别名对应的字段名
     *
     * @param string $alias
     *
     * @return string
     */
    protected function field_name($alias)
    {
        return $this->ref['ralias'][$alias];
    }
}
