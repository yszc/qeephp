<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2007 QeePHP.org (www.qee.org)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QACL 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * QACL 提供基于角色的权限检查服务
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 */
class QACL
{
    /**
     * 检查访问控制表是否允许指定的角色访问
     *
     * @param array $roles
     * @param array $act
     *
     * @return boolean
     */
    function check($roles, $act)
    {
        $roles = array_map('strtolower', $roles);
        if ($act['allow'] == 'acl_everyone') {
            // 如果 allow 允许所有角色，deny 没有设置，则检查通过
            if ($act['deny'] == 'acl_null') { return true; }
            // 如果 deny 为 acl_no_role，则只要用户具有角色就检查通过
            if ($act['deny'] == 'acl_no_role') {
                if (empty($roles)) { return false; }
                return true;
            }
            // 如果 deny 为 acl_has_role，则只有用户没有角色信息时才检查通过
            if ($act['deny'] == 'acl_has_role') {
                if (empty($roles)) { return true; }
                return false;
            }
            // 如果 deny 也为 acl_everyone，则表示 ACT 出现了冲突
            if ($act['deny'] == 'acl_everyone') {
                throw new ACL_Exception('Invalid ACT');
            }

            // 只有 deny 中没有用户的角色信息，则检查通过
            foreach ($roles as $role) {
                if (in_array($role, $act['deny'], true)) { return false; }
            }
            return true;
        }

        do {
            // 如果 allow 要求用户具有角色，但用户没有角色时直接不通过检查
            if ($act['allow'] == 'acl_has_role') {
                if (!empty($roles)) { break; }
                return false;
            }

            // 如果 allow 要求用户没有角色，但用户有角色时直接不通过检查
            if ($act['allow'] == 'acl_no_role') {
                if (empty($roles)) { break; }
                return false;
            }

            if ($act['allow'] != 'acl_null') {
                // 如果 allow 要求用户具有特定角色，则进行检查
                $passed = false;
                foreach ($roles as $role) {
                    if (in_array($role, $act['allow'], true)) {
                        $passed = true;
                        break;
                    }
                }
                if (!$passed) { return false; }
            }
        } while (false);

        // 如果 deny 没有设置，则检查通过
        if ($act['deny'] == 'acl_null') { return true; }
        // 如果 deny 为 acl_no_role，则只要用户具有角色就检查通过
        if ($act['deny'] == 'acl_no_role') {
            if (empty($roles)) { return false; }
            return true;
        }
        // 如果 deny 为 acl_has_role，则只有用户没有角色信息时才检查通过
        if ($act['deny'] == 'acl_has_role') {
            if (empty($roles)) { return true; }
            return false;
        }
        // 如果 deny 为 acl_everyone，则检查失败
        if ($act['deny'] == 'acl_everyone') {
            return false;
        }

        // 只有 deny 中没有用户的角色信息，则检查通过
        foreach ($roles as $role) {
            if (in_array($role, $act['deny'], true)) { return false; }
        }
        return true;
    }

    /**
     * 对原始 ACT 进行分析和整理，返回整理结果
     *
     * @param array $act
     *
     * @return array
     */
    function formatACT($act)
    {
        $act = array_change_key_case($act, CASE_LOWER);
        $ret = array();
        $arr = array('allow', 'deny');
        foreach ($arr as $key) {
            do {
                if (!isset($act[$key])) {
                    $value = 'acl_null';
                    break;
                }

                $act[$key] = strtolower($act[$key]);
                if ($act[$key] == 'acl_everyone' || $act[$key] == 'acl_has_role'
                    || $act[$key] == 'acl_no_role' || $act[$key] == 'acl_null') {
                    $value = $act[$key];
                    break;
                }

                $value = explode(',', $act[$key]);
                $value = array_filter(array_map('trim', $value), 'trim');
                if (empty($value)) { $value = 'acl_null'; }
            } while (false);
            $ret[$key] = $value;
        }

        return $ret;
    }
}
