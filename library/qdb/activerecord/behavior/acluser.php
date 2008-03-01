<?php

class Behavior_Acluser implements QActiveRecord_Behavior_Interface
{
    static function get_callbacks()
    {
        return array(
            array(self::before_create, array(__CLASS__, 'before_create')),
            array(self::custom_callback, array(__CLASS__, 'check_password')),
        );
    }

    static function before_create(QActiveRecord_Abstract $obj, array & $props)
    {
        $props['password'] = md5($props['password']);
        if (array_key_exists('register_ip', $props)) {
            $props['register_ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'none';
        }
    }

    static function check_password(QActiveRecord_Abstract $obj, array & $props, $password)
    {
        return md5($password) == $props['password'];
    }
}

