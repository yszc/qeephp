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
 * 定义 Behavior_Acluser 类
 *
 * @package database
 * @version $Id$
 */

/**
 * Behavior_Acluser 类实现了基于 ACL 的用户访问控制
 *
 * @package database
 */
class Behavior_Acluser implements QDB_ActiveRecord_Behavior_Interface
{
    /**
     * 密码加密的方式
     *
     * @var string
     */
    public $encode_type = 'md5';


    /**
     * 该方法返回行为插件定义的回调事件以及扩展的方法
     *
     * @return array
     */
    function __callbacks()
    {
        return array(
            /**
             * 指定 password 属性的 setter 方法
             *
             * 属性对应的 setter 方法名必须为 get属性名() 或者 set属性名()
             */
            array(self::setter, 'password'),
            // 新对象保存到数据库前调用
            array(self::before_create,   array($this, 'beforeCreate')),
            // 为 ActiveRecord 对象增加一个 checkPassword() 方法
            array(self::custom_callback, array($this, 'checkPassword')),
        );
    }

    /**
     * 对密码字段加密
     *
     * @param string $password
     *
     * @return string;
     */
    function setPassword($password)
    {
        QDebug::dump($password, __CLASS__ . "::setPassword()");
        switch ($this->encode_type) {
        case 'md5':
            return md5($password);
        case 'crypt':
        default:
            return crypt($password);
        }
    }

    /**
     * 在 ActiveRecord 保存到数据库前，填充 register_ip 属性
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function beforeCreate(QDB_ActiveRecord_Abstract $obj, array & $props)
    {
        if (array_key_exists('register_ip', $props)) {
            $props['register_ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'none';
        }
    }

    /**
     * 检查密码是否符合要求
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param string $password
     *
     * @return boolean
     */
    function checkPassword(QDB_ActiveRecord_Abstract $obj, array & $props, $password)
    {
        switch ($this->encode_type) {
        case 'md5':
            return md5($password) == $props['password'];
        case 'crypt':
        default:
            return crypt($password) == $props['password'];
        }
    }
}
