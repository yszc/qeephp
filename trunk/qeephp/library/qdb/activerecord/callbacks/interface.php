<?php

interface QActiveRecord_Callbacks_Interface
{
    /**
     * 预定义的 callback 事件
     */
    const after_find                  = 0xf101; // 查询后
    const after_initialize            = 0xf102; // 初始化后
    const before_save                 = 0xf201; // 保存之前
    const after_save                  = 0xf202; // 保存之后
    const before_create               = 0xf203; // 创建之前
    const after_create                = 0xf204; // 创建之后
    const before_update               = 0xf205; // 更新之前
    const after_update                = 0xf206; // 更新之后
    const before_validation           = 0xf301; // 验证之前
    const after_validation            = 0xf302; // 验证之后
    const before_validation_on_create = 0xf303; // 创建记录验证之前
    const after_validation_on_create  = 0xf304; // 创建记录验证之后
    const before_validation_on_update = 0xf305; // 更新记录验证之前
    const after_validation_on_update  = 0xf306; // 更新记录验证之后
    const before_destroy              = 0xf401; // 销毁之前
    const after_destroy               = 0xf402; // 销毁之后

    /**
     * 行为插件可用的其他类型 callback
     */
    const custom_callback             = 0xff01; // 行为插件自定义方法
}
